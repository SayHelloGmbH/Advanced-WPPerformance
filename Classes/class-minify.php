<?php

namespace nicomartin\AdvancedWPPerformance;

class Minify {

	public $base_path = '';
	public $base_url = '';
	public $default_cache_path = '';

	public $options = '';


	public function __construct() {

		$this->base_path          = ABSPATH;
		$this->base_url           = awpp_maybe_add_slash( get_home_url() );
		$this->default_cache_path = str_replace( $this->base_url, $this->base_path, awpp_maybe_add_slash( content_url() ) . 'awpp/assets/' );
		$this->options            = get_option( awpp_get_instance()->Settings->settings_option );
	}

	public function run() {

		add_action( 'admin_bar_menu', [ $this, 'add_toolbar_item' ] );
		add_action( 'wp_ajax_awpp_do_clear_minify_cache', [ $this, 'clear_cache' ] );
		if ( awpp_is_frontend() && 'off' != $this->options['minify'] ) {
			add_filter( 'clean_url', [ $this, 'change_url' ], 1, 1 );
		}
	}

	public function change_url( $url ) {

		$cache_dir = $this->get_cache_dir();

		if ( strpos( $url, '.js' ) !== false ) {
			$type = 'js';
		} elseif ( strpos( $url, '.css' ) !== false ) {
			$type = 'css';
		} else {
			return $url;
		}

		if ( strpos( $url, $this->base_url ) === false ) {
			return $url;
		}

		$new_filename = str_replace( $this->base_url, '', $url );
		$new_filename = hash( 'crc32', $new_filename, false ); // todo: check if those filenames are always(!) unique
		$new_filename = $new_filename . '.' . $type;

		$cache_type_dir = $cache_dir . $type . '/';

		$new_path = $cache_type_dir . $new_filename;
		$old_path = str_replace( $this->base_url, $this->base_path, $url );
		$new_url  = str_replace( $this->base_path, $this->base_url, $new_path );

		if ( strpos( $old_path, '?' ) != false ) {
			$old_path = explode( '?', $old_path )[0]; // Remove ?ver..
		}

		if ( file_exists( $new_path ) ) {
			return $new_url;
		}

		if ( ! file_exists( $cache_type_dir ) ) {
			mkdir( $cache_type_dir, 0777, true );
		}

		$path = plugin_dir_path( awpp_get_instance()->file ) . 'Classes/Libs';
		require_once $path . '/minify/autoload.php';
		require_once $path . '/path-converter/autoload.php';

		if ( 'js' == $type ) {
			$minifier = new \MatthiasMullie\Minify\JS( $old_path );
		} else {
			$minifier = new \MatthiasMullie\Minify\CSS( $old_path );
		}
		$minifier->minify( $new_path );

		return $new_url;
	}

	public function add_toolbar_item( $wp_admin_bar ) {

		$cache_dir = $this->get_cache_dir();

		$folders    = [ 'css', 'js' ];
		$file_count = 0;
		$file_size  = 0;

		foreach ( $folders as $folder ) {

			$dir = $cache_dir . $folder . '/';

			if ( ! file_exists( $dir ) ) {
				mkdir( $dir, 0777, true );
			}

			$files = scandir( $dir );
			foreach ( $files as $file ) {
				if ( '.' == $file || '..' == $file ) {
					continue;
				}
				//echo $dir . $file . ': ' . filesize( $dir . $file ) . '<br>';
				$file_count ++;
				$file_size = $file_size + filesize( $dir . $file );
			}
		}
		$file_size = $this->format_bytes( $file_size );

		// translators: x Files, x kB
		$text = sprintf( _n( '%1$s File, %2$s', '%1$s Files, %2$s', $file_count, 'awpp' ), "<span class='count'>$file_count</span>", "<span class='size'>$file_size</span>" );
		$html = '<p class="minify-content">';
		$html .= $text;
		$html .= '<span class="clear-cache"><button id="awpp-clear-cache" data-nonce="' . wp_create_nonce( 'awpp-clear-cache-nonce' ) . '" data-ajaxurl="' . admin_url( 'admin-ajax.php' ) . '">' . __( 'clear', 'sht' ) . '</button></span>';
		$html .= '</p>';

		$args = [
			'id'     => awpp_get_instance()->Settings->adminbar_id . '-minify',
			'parent' => awpp_get_instance()->Settings->adminbar_id,
			'title'  => 'Minify Cache',
			'href'   => '',
			'meta'   => [
				'class' => awpp_get_instance()->prefix . '-adminbar-minify ' . $this->options['minify'],
				'html'  => '<div class="ab-item ab-empty-item">' . $html . '</div><div class="loader"></div>',
			],
		];
		$wp_admin_bar->add_node( $args );
	}

	public function clear_cache() {

		if ( ! wp_verify_nonce( $_POST['nonce'], 'awpp-clear-cache-nonce' ) ) {
			$this->exit_ajax( 'error', __( 'Invalid nonce', 'awpp' ) );
		}

		$cache_dir     = $this->get_cache_dir();
		$files_deleted = $this->rrmdir( $cache_dir );

		$this->exit_ajax( 'success', sprintf( '%s Files deleted', $files_deleted ) );
	}

	/**
	 * Helpers
	 */

	public function format_bytes( $bytes, $precision = 2 ) {
		$units = [ 'B', 'KB', 'MB', 'GB', 'TB' ];

		$bytes = max( $bytes, 0 );
		$pow   = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow   = min( $pow, count( $units ) - 1 );

		// Uncomment one of the following alternatives
		// $bytes /= pow(1024, $pow);
		$bytes /= ( 1 << ( 10 * $pow ) );

		return round( $bytes, $precision ) . ' ' . $units[ $pow ];
	}

	public function exit_ajax( $type, $msg = '', $add = [] ) {

		$return = [
			'type'    => $type,
			'message' => $msg,
			'add'     => $add,
		];

		echo json_encode( $return );

		wp_die();
	}

	public function rrmdir( $path ) {
		if ( ! is_dir( $path ) ) {
			return false;
		}
		$objects = scandir( $path );
		foreach ( $objects as $object ) {
			if ( '.' != $object && '..' != $object ) {
				if ( filetype( $path . '/' . $object ) == 'dir' ) {
					$count = $count + $this->rrmdir( $path . '/' . $object );
				} else {
					$count ++;
					unlink( $path . '/' . $object );
				}
			}
		}

		return $count;
	}

	public function get_cache_dir() {

		$cache_dir = apply_filters( 'awpp_cache_dir', $this->default_cache_path );
		$cache_dir = awpp_maybe_add_slash( $cache_dir );
		if ( '' == $cache_dir || '/' == $cache_dir ) {
			$cache_dir = $this->default_cache_path;
		}

		return $cache_dir;
	}
}
