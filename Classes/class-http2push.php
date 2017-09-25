<?php

namespace nicomartin\AdvancedWPPerformance;

// Based on https://github.com/daveross/http2-server-push/

class Http2Push {

	public $max_header_size = 0;
	public $header_size_accumulator = 0;
	public $options = '';

	public $serverpush_scan_action = '';
	public $serverpush_possfiles_option = '';

	public function __construct() {
		$this->max_header_size         = 1024 * 4;
		$this->header_size_accumulator = 0;
		$this->options                 = get_option( awpp_get_instance()->Settings->settings_option );

		$this->serverpush_scan_action      = 'awpp_scan_htaccess_push';
		$this->serverpush_possfiles_option = 'awpp_serverpush_possible_files';
	}

	public function run() {

		add_filter( 'script_loader_tag', [ $this, 'add_push_id_to_assets' ], 10, 2 );
		add_filter( 'style_loader_tag', [ $this, 'add_push_id_to_assets' ], 10, 2 );

		add_action( 'wp_ajax_' . $this->serverpush_scan_action, [ $this, 'ajax_get_frontpage_files' ] );
		add_action( 'update_option_' . awpp_get_instance()->Settings->settings_option, [ $this, 'add_serverpush_htaccess_onoption' ], 100, 2 );

		if ( 'php' == $this->options['serverpush'] && ! is_admin() ) {
			add_action( 'init', [ $this, 'ob_start' ] );
			add_filter( 'script_loader_src', [ $this, 'link_preload_header' ], 99, 1 );
			add_filter( 'style_loader_src', [ $this, 'link_preload_header' ], 99, 1 );
			if ( $this->should_render_prefetch_headers() ) {
				add_action( 'wp_head', [ $this, 'resource_hints' ], 99, 1 );
			}
		}
	}

	public function add_push_id_to_assets( $html, $id ) {
		if ( current_filter() == 'script_loader_tag' ) {
			return str_replace( ' src', ' data-push-id="' . $id . '" src', $html );
		}

		return str_replace( ' href', ' data-push-id="' . $id . '" href', $html );
	}

	public function ajax_get_frontpage_files() {

		$add = $this->scan_frontpage_files();
		if ( 'success' != $add['status'] ) {
			awpp_exit_ajax( $add['status'], $add['msg'] );
		}

		awpp_exit_ajax( 'success', '', $add );
	}

	public function add_serverpush_htaccess_onoption( $oldvalue, $newvalue ) {
		$this->add_serverpush_htaccess( $newvalue );
	}

	public function ob_start() {
		ob_start();
	}

	public function link_preload_header( $src ) {

		if ( strpos( $src, home_url() ) !== false ) {

			$preload_src = apply_filters( 'awpp_link_preload_src', $src );

			if ( ! empty( $preload_src ) ) {
				$header = sprintf( 'Link: <%s>; rel=preload; as=%s', esc_url( $this->link_url_to_relative_path( $preload_src ) ), sanitize_html_class( $this->link_resource_hint_as( current_filter() ) ) );
				if ( ( $this->header_size_accumulator + strlen( $header ) ) < $this->max_header_size ) {
					$this->header_size_accumulator += strlen( $header );
					header( $header, false );
				}

				$GLOBALS[ 'awpp_' . $this->link_resource_hint_as( current_filter() ) . '_srcs' ][] = $this->link_url_to_relative_path( $preload_src );
			}
		}

		return $src;
	}

	public function resource_hints() {
		$resource_types = array( 'script', 'style' );
		array_walk( $resource_types, function ( $resource_type ) {
			$resources = $this->get_resources( $GLOBALS, $resource_type );
			array_walk( $resources, function ( $src ) use ( $resource_type ) {
				printf( '<link rel="preload" href="%s" as="%s">', esc_url( $src ), esc_html( $resource_type ) );
			} );
		} );
	}


	/**
	 * PHP Helpers
	 */

	public function get_resources( $globals = null, $resource_type ) {

		$globals           = ( null === $globals ) ? $GLOBALS : $globals;
		$resource_type_key = "awpp_{$resource_type}_srcs";

		if ( ! ( is_array( $globals ) && isset( $globals[ $resource_type_key ] ) ) ) {
			return array();
		} elseif ( ! is_array( $globals[ $resource_type_key ] ) ) {
			return array( $globals[ $resource_type_key ] );
		} else {
			return $globals[ $resource_type_key ];
		}
	}

	public function link_url_to_relative_path( $src ) {
		return '//' === substr( $src, 0, 2 ) ? preg_replace( '/^\/\/([^\/]*)\//', '/', $src ) : preg_replace( '/^http(s)?:\/\/[^\/]*/', '', $src );
	}

	public function should_render_prefetch_headers() {
		return apply_filters( 'awpp_render_resource_hints', ! function_exists( 'wp_resource_hints' ) );
	}

	public function link_resource_hint_as( $current_hook ) {
		return 'style_loader_src' === $current_hook ? 'style' : 'script';
	}

	/**
	 * Server Push Helpers
	 */

	public function scan_frontpage_files() {

		if ( has_action( 'cachify_flush_cache' ) ) {
			do_action( 'cachify_flush_cache' );
		} elseif ( function_exists( 'w3tc_pgcache_flush' ) ) {
			w3tc_pgcache_flush();
		} elseif ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		} elseif ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}

		$return = [];

		$agent = awpp_get_instance()->name . ' User Agent';
		$ch    = curl_init( get_home_url() );
		curl_setopt( $ch, CURLOPT_URL, get_home_url() );
		curl_setopt( $ch, CURLOPT_USERAGENT, $agent );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		$file      = curl_exec( $ch );
		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		//if ( $http_code >= 300 ) {
		return [
			'status' => 'error',
			'msg'    => 'invalid HTTP Code: ' . $http_code,
			'body'   => $file,
		];
		/*} else {
			$return['status'] = 'success';
		}*/

		$attr_regex    = '/([a-zA-Z0-9-]+)="([^"]+)"/';
		$styles_regex  = '/<link rel=\'stylesheet\' (.*?)>/';
		$scripts_regex = '/<script type=\'text\/javascript\' (.*?)><\/script>/';

		preg_match_all( $styles_regex, $file, $styles, PREG_SET_ORDER, 0 );
		foreach ( $styles as $style ) {
			preg_match_all( $attr_regex, str_replace( '\'', '"', $style[1] ), $attributes, PREG_SET_ORDER, 0 );
			$id  = '';
			$url = '';
			foreach ( $attributes as $a ) {
				$attr = $a[1];
				$val  = $a[2];
				if ( 'data-push-id' == $attr ) {
					$id = $val;
				} elseif ( 'href' == $attr ) {
					$url = $val;
				}
			}
			if ( strpos( $url, get_home_url() ) !== 0 || '' == $id || '' == $url ) {
				continue;
			}
			$return['styles'][ $id ] = $url;
		}

		preg_match_all( $scripts_regex, $file, $scripts, PREG_SET_ORDER, 0 );
		foreach ( $scripts as $script ) {
			preg_match_all( $attr_regex, str_replace( '\'', '"', $script[1] ), $attributes, PREG_SET_ORDER, 0 );
			$id  = '';
			$url = '';
			foreach ( $attributes as $a ) {
				$attr = $a[1];
				$val  = $a[2];
				if ( 'data-push-id' == $attr ) {
					$id = $val;
				} elseif ( 'src' == $attr ) {
					$url = $val;
				}
			}
			if ( strpos( $url, get_home_url() ) !== 0 ) {
				continue;
			}
			if ( strpos( $url, get_home_url() ) !== 0 || '' == $id || '' == $url ) {
				continue;
			}
			$return['scripts'][ $id ] = $url;
		}

		update_option( $this->serverpush_possfiles_option, $return );

		return $return;
	}

	public function add_serverpush_htaccess( $options = '', $files = '' ) {

		if ( '' == $options ) {
			$options = $this->options;
		}

		if ( 'htaccess' == $options['serverpush'] ) {

			$set_htaccess = true;

			if ( '' == $files ) {
				$files = $this->scan_frontpage_files();
				if ( 'success' != $files['status'] ) {
					$set_htaccess = false;
				}
			}

			if ( $set_htaccess ) {

				$lines   = [];
				$lines[] = '<IfModule mod_headers.c>';
				$lines[] = '<FilesMatch "\.(php|html|htm|gz)$">';
				foreach ( [ 'styles', 'scripts' ] as $type ) {
					$lines[] = '# ' . $type;

					$as = 'style';
					if ( 'scripts' == $type ) {
						$as = 'script';
					}
					if ( is_array( $options['serverpush_files'][ $type ] ) ) {
						foreach ( $options['serverpush_files'][ $type ] as $id => $val ) {
							$lines[] = 'Header add Link "<' . $this->link_url_to_relative_path( $files[ $type ][ $id ] ) . '>; rel=preload; as=' . $as . '"';
						}
					}
				}
				$lines[] = '</FilesMatch>';
				$lines[] = '</IfModule>';

				awpp_get_instance()->htaccess->set( implode( "\n", $lines ) );
			}
		} else {
			awpp_get_instance()->htaccess->delete( implode( "\n", $lines ) );
		}
	}
}
