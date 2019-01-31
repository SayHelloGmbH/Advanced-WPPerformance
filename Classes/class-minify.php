<?php

namespace nicomartin\AdvancedWPPerformance;

class Minify {

	public $base_path = '';
	public $base_url = '';
	public $default_cache_path = '';
	public $minify_files = '';

	public $options = '';

	public function __construct() {

		$this->base_path          = trailingslashit( ABSPATH );
		$this->base_url           = trailingslashit( get_site_url() );
		$this->default_cache_path = trailingslashit( WP_CONTENT_DIR ) . 'cache/awpp/';
		$this->minify_files       = [ 'css', 'js' ];

		$this->options = get_option( AWPP_SETTINGS_OPTION );
	}

	public function run() {
		add_action( 'awpp_settings', [ $this, 'register_settings' ] );

		add_action( 'admin_bar_menu', [ $this, 'add_toolbar_item' ] );
		add_action( 'wp_ajax_awpp_do_clear_minify_cache', [ $this, 'clear_cache' ] );

		add_filter( 'script_loader_src', [ $this, 'change_url' ], 30, 1 );
		add_filter( 'style_loader_src', [ $this, 'change_url' ], 30, 1 );
	}

	public function register_settings() {

		$section = awpp_settings()->add_section( awpp_settings_page_assets(), 'minify', __( 'Minify', 'awpp' ) );
		awpp_settings()->add_checkbox( $section, 'minify', __( 'Minify CSS and JS Files', 'awpp' ), true );
	}

	public function add_toolbar_item( $wp_admin_bar ) {

		$cache_dir = $this->get_cache_dir();

		$file_count = 0;
		$file_size  = 0;

		foreach ( $this->minify_files as $folder ) {

			$dir = $cache_dir . $folder . '/';

			if ( ! is_dir( $dir ) ) {
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
		$html .= '<span class="clear-cache"><button id="awpp-clear-cache" data-nonce="' . wp_create_nonce( 'awpp-clear-cache-nonce' ) . '" data-ajaxurl="' . admin_url( 'admin-ajax.php' ) . '">' . __( 'clear', 'awpp' ) . '</button></span>';
		$html .= '</p>';

		$args = [
			'id'     => awpp_get_instance()->Init->admin_bar_id . '-minify',
			'parent' => awpp_get_instance()->Init->admin_bar_id,
			'title'  => 'Minify Cache',
			'href'   => '',
			'meta'   => [
				'class' => awpp_get_instance()->prefix . '-adminbar-minify ' . ( awpp_get_setting( 'minify' ) ? '' : 'disabled' ),
				'html'  => '<div class="ab-item ab-empty-item">' . $html . '</div><div class="loader"></div>',
			],
		];
		$wp_admin_bar->add_node( $args );
	}

	public function clear_cache() {

		if ( ! wp_verify_nonce( $_POST['nonce'], 'awpp-clear-cache-nonce' ) ) {
			awpp_exit_ajax( 'error', __( 'Invalid nonce', 'awpp' ) );
		}

		$cache_dir     = $this->get_cache_dir();
		$files_deleted = 0;
		foreach ( $this->minify_files as $folder ) {
			$files_deleted = $files_deleted + $this->rrmdir( "{$cache_dir}{$folder}/" );
		}

		awpp_exit_ajax( 'success', sprintf( '%s Files deleted', $files_deleted ) );
	}

	public function change_url( $url ) {

		if ( is_admin() ) {
			return $url;
		}

		if ( ! awpp_get_setting( 'minify' ) ) {
			return $url;
		}

		$cache_dir = $this->get_cache_dir();

		$type = '';
		foreach ( $this->minify_files as $file ) {
			if ( strpos( $url, '.' . $file ) !== false ) {
				$type = $file;
			}
		}

		if ( '' == $type ) {
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

	public function rrmdir( $path ) {
		if ( ! is_dir( $path ) ) {
			return false;
		}
		$objects = scandir( $path );
		$count   = 0;
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
		$cache_dir = trailingslashit( $cache_dir );

		if ( strpos( $cache_dir, $this->base_url ) !== false ) {
			$cache_dir = str_replace( $this->base_url, ABSPATH, $cache_dir );
		}

		if ( '' == $cache_dir || '/' == $cache_dir ) {
			$cache_dir = $this->default_cache_path;
		}

		if ( is_multisite() ) {
			$cache_dir = trailingslashit( $cache_dir ) . get_current_blog_id() . '/';
		}

		return $cache_dir;
	}
}
