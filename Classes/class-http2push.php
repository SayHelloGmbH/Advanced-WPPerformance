<?php

namespace nicomartin\AdvancedWPPerformance;

// Based on https://github.com/daveross/http2-server-push/

class Http2Push {

	public $max_header_size = 0;
	public $header_size_accumulator = 0;
	public $options = '';

	public $serverpush_scan_action = '';
	public $serverpush_files_option = '';
	public $serverpush_possfiles_option = '';
	public $htaccess = '';

	public function __construct() {
		$this->max_header_size         = 1024 * 4;
		$this->header_size_accumulator = 0;

		$this->serverpush_scan_action      = 'awpp_scan_htaccess_push';
		$this->serverpush_files_option     = 'awpp_serverpush_files';
		$this->serverpush_possfiles_option = 'awpp_serverpush_possible_files';
		$this->htaccess                    = new \nicomartin\Htaccess( 'Serverpush' );
	}

	public function run() {

		add_action( 'awpp_settings', [ $this, 'register_settings' ] );

		add_filter( 'script_loader_tag', [ $this, 'add_push_id_to_assets' ], 10, 2 );
		add_filter( 'style_loader_tag', [ $this, 'add_push_id_to_assets' ], 10, 2 );

		add_action( 'wp_ajax_' . $this->serverpush_scan_action, [ $this, 'ajax_get_frontpage_files' ] );
		add_action( 'awpp_sanitize', [ $this, 'save_serverpush_files' ] );
		add_action( 'awpp_sanitize', [ $this, 'maybe_do_serverpush_cron' ] );
		add_action( 'awpp_renew_htaccess_cron', [ $this, 'add_serverpush_htaccess' ] );

		register_uninstall_hook( awpp_get_instance()->file, [ 'clean_up' ] );

		if ( ! is_admin() ) {
			add_action( 'init', [ $this, 'ob_start' ] );
			add_filter( 'script_loader_src', [ $this, 'link_preload_header' ], 99, 1 );
			add_filter( 'style_loader_src', [ $this, 'link_preload_header' ], 99, 1 );
			if ( $this->should_render_prefetch_headers() ) {
				add_action( 'wp_head', [ $this, 'resource_hints' ], 99, 1 );
			}
		}
	}

	public function register_settings() {

		global $awpp_settings_page_server;
		$section = awpp_settings()->add_section( $awpp_settings_page_server, 'serverpush', __( 'HTTP/2 Server Push', 'awpp' ) );

		$choices = [
			'disabled' => __( 'Disabled', 'awpp' ),
			'php'      => __( 'PHP', 'awpp' ),
			'htaccess' => __( '.htaccess', 'awpp' ),
		];

		$after = '';
		$after .= '<div class="serverpush-htaccess-info" id="serverpush-htaccess-info" style="display:none">';
		$after .= '<p class="awpp-smaller infotext">';
		$after .= __( 'This option will add server push rules directly to your .htaccess. Please select all files that should be pushed on every pageload (Frontpage and all subpages).', 'awpp' );
		$after .= '</p>';

		$chosen_files  = get_option( $this->serverpush_files_option );
		$scanned_files = get_option( $this->serverpush_possfiles_option );
		if ( ! is_array( $scanned_files ) || empty( $scanned_files ) ) {
			$scanned_files = [
				'styles'  => [],
				'scripts' => [],
			];
		}
		foreach ( [ 'styles', 'scripts' ] as $type ) {
			$after .= '<p><b>' . ucfirst( $type ) . '</b></p>';
			$after .= '<ul id="' . $type . '" class="files-list">';
			if ( ! is_array( $scanned_files[ $type ] ) ) {
				$scanned_files[ $type ] = [];
			}
			foreach ( $scanned_files[ $type ] as $id => $url ) {
				$checked = '';
				if ( isset( $chosen_files[ $type ][ $id ] ) && 'on' == $chosen_files[ $type ][ $id ] ) {
					$checked = 'checked';
				}
				$after .= "<li id='$id'><label title='$url'><input type='checkbox' $checked name='awpp-settings[serverpush_files][$type][$id]'/> $id</label></li>";
			}
			$after .= '<li class="no-items">' . __( 'No files aviable', 'awpp' ) . '</li>';
			$after .= '</ul>';
		}
		$after .= '<p style="text-align: right;">';
		$after .= '<a id="scan-page" data-action="' . $this->serverpush_scan_action . '" data-ajaxurl="' . admin_url( 'admin-ajax.php' ) . '" class="button">' . __( 'Scan Frontpage', 'awpp' ) . '</a>';
		$after .= '</p>';
		$after .= '<div class="loader"></div>';
		$after .= '</div>';

		$args = [
			'after_field' => $after,
		];
		awpp_settings()->add_select( $section, 'serverpush', __( 'Enable HTTP/2 Server Push', 'awpp' ), $choices, '', $args );

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
			awpp_exit_ajax( $add['status'], $add['msg'], $add );
		}

		awpp_exit_ajax( 'success', '', $add );
	}

	public function save_serverpush_files( $data ) {
		if ( isset( $data['serverpush'] ) ) {
			if ( ! isset( $data['serverpush_files'] ) || 'htaccess' != $data['serverpush'] ) {
				$data['serverpush_files'] = [];
			}
			update_option( $this->serverpush_files_option, $data['serverpush_files'] );
			$this->add_serverpush_htaccess( $data['serverpush_files'] );
			unset( $data['serverpush_files'] );
		}

		return $data;
	}

	public function maybe_do_serverpush_cron( $data ) {
		if ( isset( $data['serverpush'] ) ) {
			if ( 'htaccess' != $data['serverpush'] ) {
				wp_clear_scheduled_hook( 'awpp_renew_htaccess_cron' );
				$this->htaccess->delete();
			} elseif ( ! wp_next_scheduled( 'awpp_renew_htaccess_cron' ) ) {
				wp_schedule_event( time(), 'twicedaily', 'awpp_renew_htaccess_cron' );
			}
		}
	}

	public function ob_start() {
		if ( 'php' == awpp_get_setting( 'serverpush' ) ) {
			ob_start();
		}
	}

	public function link_preload_header( $src ) {

		if ( 'php' != awpp_get_setting( 'serverpush' ) ) {
			return $src;
		}

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

		if ( 'php' != awpp_get_setting( 'serverpush' ) ) {
			return;
		}

		$resource_types = array( 'script', 'style' );
		array_walk( $resource_types, function ( $resource_type ) {
			$resources = $this->get_resources( $GLOBALS, $resource_type );
			array_walk( $resources, function ( $src ) use ( $resource_type ) {
				printf( '<link rel="preload" href="%1$s" as="%2$s">', esc_url( $src ), esc_html( $resource_type ) );
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

		if ( $http_code >= 300 ) {
			return [
				'status' => 'error',
				'msg'    => 'invalid HTTP Code: ' . $http_code,
				'body'   => $file,
			];
		} else {
			$return['status'] = 'success';
		}

		$attr_regex    = '/([a-zA-Z0-9-]+)="([^"]+)"/';
		$styles_regex  = '/<link rel=\'stylesheet\' (.*?)>/';
		$scripts_regex = '/<script type=\'text\/javascript\' (.*?)><\/script>/';

		$return['styles'] = [];
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

		$return['scripts'] = [];
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

		if ( empty( $return['scripts'] ) && empty( $return['styles'] ) ) {
			return [
				'status' => 'error',
				'msg'    => 'No scripts and styles found',
			];
		}

		update_option( $this->serverpush_possfiles_option, $return );

		return $return;
	}

	public function add_serverpush_htaccess( $options = '', $files = '' ) {

		if ( '' == $options ) {
			$options = get_option( $this->serverpush_files_option );
		}

		if ( 'htaccess' == awpp_get_setting( 'serverpush' ) ) {

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
				$lines[] = '<FilesMatch "(index\.php|\.(html|htm|gz)$)">';
				foreach ( [ 'styles', 'scripts' ] as $type ) {
					$lines[] = '# ' . $type;

					$as = 'style';
					if ( 'scripts' == $type ) {
						$as = 'script';
					}
					if ( isset( $options[ $type ] ) && is_array( $options[ $type ] ) ) {
						foreach ( $options[ $type ] as $id => $val ) {
							$lines[] = 'Header add Link "<' . $this->link_url_to_relative_path( $files[ $type ][ $id ] ) . '>; rel=preload; as=' . $as . '"';
						}
					}
				}
				$lines[] = '</FilesMatch>';
				$lines[] = '</IfModule>';

				$this->htaccess->set( implode( "\n", $lines ) );
			}
		} else {
			$this->htaccess->delete();
		}// End if().
	}

	/**
	 * Clean Up
	 */

	public function clean_up() {
		wp_clear_scheduled_hook( 'awpp_renew_htaccess_cron' );
		$this->htaccess->delete();
	}
}
