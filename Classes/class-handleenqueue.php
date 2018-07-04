<?php

namespace nicomartin\AdvancedWPPerformance;

class HandleEnqueue {

	public $options = '';
	public $styles = '';

	public function __construct() {

		$this->options = get_option( AWPP_SETTINGS_OPTION );
		$this->styles  = [];
	}

	public function run() {

		add_action( 'awpp_settings', [ $this, 'register_settings' ] );

		if ( is_admin() ) {
			return;
		}

		if ( awpp_is_frontend() ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'move_scripts_to_footer' ] );
			add_filter( 'script_loader_tag', [ $this, 'add_defer_attribute' ], 10, 2 );

			add_filter( 'style_loader_tag', [ $this, 'render_loadcss' ], 999, 4 );
			add_action( 'wp_footer', [ $this, 'add_relpreload_js' ] );
		}
	}

	public function register_settings() {

		$section = awpp_settings()->add_section( awpp_settings_page_assets(), 'delivery_opt', __( 'Delivery Optimization' ) );

		awpp_settings()->add_checkbox( $section, 'deliverycss', __( 'Optimize CSS Delivery', 'awpp' ) );
		awpp_settings()->add_checkbox( $section, 'deliveryjs', __( 'Optimize JS Delivery', 'awpp' ) );
	}

	/**
	 * Scripts
	 */

	public function move_scripts_to_footer() {

		if ( ! awpp_get_setting( 'deliveryjs' ) ) {
			return;
		}

		remove_action( 'wp_head', 'wp_print_scripts' );
		remove_action( 'wp_head', 'wp_print_head_scripts', 9 );
		remove_action( 'wp_head', 'wp_enqueue_scripts', 1 );
	}

	public function add_defer_attribute( $tag, $handle ) {
		if ( ! awpp_get_setting( 'deliveryjs' ) ) {
			return $tag;
		}

		return str_replace( ' src', ' defer = "defer" src', $tag );
	}

	/**
	 * Styles
	 */

	public function render_loadcss( $html, $handle, $href, $media ) {

		if ( ! awpp_get_setting( 'deliverycss' ) ) {
			return $html;
		}

		$html = str_replace( '\'', '"', $html );
		$html = str_replace( 'rel="stylesheet"', 'rel="preload" as="style" onload="this.onload=null;this.rel=\'stylesheet\'"', $html );

		return "$html<noscript><link rel='stylesheet' data-push-id='$handle' id='$handle' href='$href' type='text/css' media='$media'></noscript>\n";
	}

	public function add_relpreload_js() {

		if ( ! awpp_get_setting( 'deliverycss' ) ) {
			return;
		}

		$preload = plugin_dir_path( awpp_get_instance()->file ) . 'assets/scripts/cssrelpreload.min.js';
		if ( ! file_exists( $preload ) ) {
			wp_die( 'cssrelpreload.min.js not found!' );
		}

		echo '<script id="loadCSS">';
		echo file_get_contents( $preload );
		echo '</script>';
	}
}
