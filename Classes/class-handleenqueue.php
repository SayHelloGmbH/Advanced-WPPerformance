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

		if ( 'off' != $this->options['scripts_to_footer'] ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'remove_header_scripts' ] );
			add_filter( 'clean_url', [ $this, 'defer_scripts' ], 11, 1 );
		}

		if ( 'off' != $this->options['loadcss'] ) {
			add_action( 'wp_head', [ $this, 'add_loadcss' ], 1 );
			add_filter( 'style_loader_tag', [ $this, 'render_loadcss' ], 9999, 3 );
			if ( is_admin() || 'off' == $this->options['loadcss'] ) {

			}
		}
	}

	public function remove_header_scripts() {

		remove_action( 'wp_head', 'wp_print_scripts' );
		remove_action( 'wp_head', 'wp_print_head_scripts', 9 );
		remove_action( 'wp_head', 'wp_enqueue_scripts', 1 );
	}

	public function defer_scripts( $url ) {

		if ( false === strpos( $url, '.js' ) ) {
			return $url;
		}

		return "$url' defer onload='";
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

		$href = $a->getAttribute( 'href' );
		$media = $a->getAttribute( 'media' );
		$id = $a->getAttribute( 'id' );

		$return = "<script>loadCSS('$href', 0, '$media' );</script>\n";
		$return .= "<noscript><link rel='stylesheet' id='$id' href='$href' type='text/css' media='$media'></noscript>\n";

		return $return;
	}
}
