<?php

namespace nicomartin\AdvancedWPPerformance;

class HandleEnqueue {

	public $options = '';
	public $styles = '';

	public function __construct() {
		$this->options = get_option( awpp_get_instance()->Settings->settings_option );
		$this->styles  = [];
	}

	public function run() {

		if ( is_admin() ) {
			return;
		}

		if ( awpp_is_frontend() && 'off' != $this->options['scripts_to_footer'] ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'remove_header_scripts' ] );
			add_filter( 'script_loader_tag', [ $this, 'add_defer_attribute' ], 10, 2 );
		}

		if ( awpp_is_frontend() && 'off' != $this->options['loadcss'] ) {
			add_filter( 'style_loader_tag', [ $this, 'render_loadcss' ], 999, 4 );
			add_action( 'wp_head', [ $this, 'add_relpreload_js' ], 1 );
		}
	}

	/**
	 * Scripts
	 */

	public function remove_header_scripts() {

		remove_action( 'wp_head', 'wp_print_scripts' );
		remove_action( 'wp_head', 'wp_print_head_scripts', 9 );
		remove_action( 'wp_head', 'wp_enqueue_scripts', 1 );
	}

	public function add_defer_attribute( $tag, $handle ) {
		return str_replace( ' src', ' defer="defer" src', $tag );
	}

	/**
	 * Styles
	 */

	public function render_loadcss( $html, $handle, $href, $media ) {

		$html = str_replace( '\'', '"', $html );
		$html = str_replace( 'rel="stylesheet"', 'rel="preload" as="style" onload="this.rel=\'stylesheet\'"', $html );

		return "$html<noscript><link rel='stylesheet' data-push-id='$handle' id='$handle' href='$href' type='text/css' media='$media'></noscript>\n";
	}

	public function add_relpreload_js() {

		$loadcss = plugin_dir_path( awpp_get_instance()->file ) . 'assets/scripts/loadCSS.min.js';
		$preload = plugin_dir_path( awpp_get_instance()->file ) . 'assets/scripts/cssrelpreload.min.js';
		if ( ! file_exists( $loadcss ) || ! file_exists( $preload ) ) {
			wp_die( 'loadcss.min.js or cssrelpreload.min.js not found!' );
		}

		echo '<script id="loadCSS">';
		echo file_get_contents( $loadcss );
		echo file_get_contents( $preload );
		echo '</script>';
	}
}
