<?php

namespace nicomartin\AdvancedWPPerformance;

class Minify {

	public $cache_folder = '';
	public $base_path = '';
	public $base_url = '';
	public $options = '';

	public function __construct() {
		$this->cache_folder = ''; // default folder is set in check_empty()
		$this->base_path    = ABSPATH;
		$this->base_url     = get_home_url() . '/';
		$this->options      = get_option( awpp_get_instance()->Settings->settings_option );
	}

	public function run() {

		add_filter( 'awpp_cache_folder', 'awpp_check_format', 990 );
		add_filter( 'awpp_cache_folder', 'awpp_maybe_add_slash', 959 );
		add_filter( 'awpp_cache_folder', [ $this, 'check_empty' ], 999 );
		add_filter( 'awpp_cache_folder', function ( $str ) {
			return 'assets';
		}, 9 );
		add_action( 'admin_bar_menu', [ $this, 'add_toolbar_item' ] );
		add_action( 'wp_ajax_awpp_do_clear_minify_cache', [ $this, 'clear_cache' ] );

		if ( 'off' == $this->options['minify'] ) {
			return;
		}

		add_filter( 'clean_url', [ $this, 'change_url' ], 1, 1 );
	}

	public function check_empty( $string ) {
		if ( '' == $string || '/' == $string ) {
			return str_replace( $this->base_url, '', content_url() ) . '/awpp/assets/';
		}

		return $string;
	}

	public function change_url( $url ) {

		$cache_folder = apply_filters( 'awpp_cache_folder', $this->cache_folder );

		if ( strpos( $url, '.js' ) !== false ) {
			$type = 'js';
		} elseif ( strpos( $url, '.css' ) !== false ) {
			$type = 'css';
		} else {
			return $url;
		}

		$cache_typefolder = $cache_folder . $type . '/';

		$new_filename = str_replace( $this->base_url, '', $url );
		if ( true ) {
			$new_filename = hash( 'crc32', $new_filename, false ); // todo: check if those filenames are always(!) unique
			//$new_filename = md5( $new_filename );
		} else {
			$new_filename = str_replace( '/', '-', $this->check_format( $new_filename ) ); // debug
		}
		$new_filename = $new_filename . '.' . $type;

		$new_url  = $this->base_url . $cache_typefolder . $new_filename;
		$new_path = $this->base_path . $cache_typefolder . $new_filename;
		$old_path = str_replace( $this->base_url, $this->base_path, $url );
		if ( strpos( $old_path, '?' ) != false ) {
			$old_path = explode( '?', $old_path )[0]; // Remove ?ver..
		}

		if ( file_exists( $new_path ) ) {
			return $new_url;
		}

		if ( ! file_exists( $this->base_path . $cache_typefolder ) ) {
			mkdir( $this->base_path . $cache_typefolder, 0777, true );
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

		$cache_folder = apply_filters( 'awpp_cache_folder', $this->cache_folder );
		$folders      = [ 'css', 'js' ];
		$file_count   = 0;
		$file_size    = 0;

		foreach ( $folders as $folder ) {

			$dir = $this->base_path . $cache_folder . $folder . '/';

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

		$cache_folder  = apply_filters( 'awpp_cache_folder', $this->cache_folder );
		$path          = $this->base_path . $cache_folder;
		$files_deleted = $this->rrmdir( $path );

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
}
