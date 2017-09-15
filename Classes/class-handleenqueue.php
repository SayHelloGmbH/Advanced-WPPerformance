<?php

namespace nicomartin\AdvancedWPPerformance;

class HandleEnqueue {

	public $options = '';

	public function __construct() {
		$this->options = get_option( awpp_get_instance()->Settings->settings_option );
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
			add_action( 'wp_head', [ $this, 'add_loadcss' ], 1 );
			add_filter( 'style_loader_tag', [ $this, 'render_loadcss' ], 9999, 3 );
		}
	}

	public function remove_header_scripts() {

		remove_action( 'wp_head', 'wp_print_scripts' );
		remove_action( 'wp_head', 'wp_print_head_scripts', 9 );
		remove_action( 'wp_head', 'wp_enqueue_scripts', 1 );
	}

	public function add_defer_attribute( $tag, $handle ) {
		return str_replace( ' src', ' defer="defer" src', $tag );
	}

	public function add_loadcss() {

		$file = plugin_dir_path( awpp_get_instance()->file ) . 'assets/scripts/loadCSS.min.js';
		if ( ! file_exists( $file ) ) {
			echo 'loadCSS.min.js not found!';
			die;
		}

		echo '<script id="loadCSS">';
		echo file_get_contents( $file );
		echo '</script>';
	}

	public function render_loadcss( $html, $handle, $href ) {

		$dom = new \DOMDocument();
		$dom->loadHTML( $html );
		$a = $dom->getElementById( $handle . '-css' );

		$href  = $a->getAttribute( 'href' );
		$media = $a->getAttribute( 'media' );
		$id    = $a->getAttribute( 'id' );

		$return = "<script>loadCSS('$href', 0, '$media', '$id' );</script>\n";
		$return .= "<noscript><link rel='stylesheet' id='$id' href='$href' type='text/css' media='$media'></noscript>\n";

		return $return;
	}
}
