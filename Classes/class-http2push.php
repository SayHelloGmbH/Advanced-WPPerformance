<?php

namespace nicomartin\AdvancedWPPerformance;

class Http2Push {

	public $scripts = '';
	public $styles = '';

	public function __construct() {
		$this->scripts = [];
		$this->styles  = [];
	}

	public function run() {
		return;
		//muss da nochmal über die Bücher
		if ( ! is_admin() ) {

			add_action( 'init', function () {
				ob_start();
			} );
			add_filter( 'script_loader_src', [ $this, 'link_preload_header_scripts' ], 50, 1 );
			add_filter( 'style_loader_src', [ $this, 'link_preload_header_styles' ], 50, 1 );
		}
	}


	/**
	 * Helpers
	 */

	public function link_preload_header_scripts( $src ) {
		if ( strpos( $src, home_url() ) !== false ) {
			$this->_set_script( $src );
		}

		return $src;
	}

	public function link_preload_header_styles( $src ) {
		if ( strpos( $src, home_url() ) !== false ) {
			header( 'Link: <' . $this->_url_to_path( $src ) . '>; rel = preload' );
		}

		return $src;
	}

	/**
	 * Helpers
	 */

	private function _set_script( $src ) {
		global $wp_scripts;
	}

	private function _url_to_path( $src ) {
		return '//' === substr( $src, 0, 2 ) ? preg_replace( '/^\/\/([^\/]*)\//', '/', $src ) : preg_replace( '/^http(s)?:\/\/[^\/]*/', '', $src );
	}
}
