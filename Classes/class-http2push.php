<?php

namespace nicomartin\AdvancedWPPerformance;

// Based on https://github.com/daveross/http2-server-push/

class Http2Push {

	public $max_header_size = 0;
	public $header_size_accumulator = 0;

	public function __construct() {
		$this->max_header_size         = 1024 * 4;
		$this->header_size_accumulator = 0;
	}

	public function run() {
		if ( isset( $_GET['nopush'] ) ) {
			return;
		}
		add_action( 'init', [ $this, 'ob_start' ] );
		add_filter( 'script_loader_src', [ $this, 'link_preload_header' ], 99, 1 );
		add_filter( 'style_loader_src', [ $this, 'link_preload_header' ], 99, 1 );
		if ( ! is_admin() && $this->should_render_prefetch_headers() ) {
			add_action( 'wp_head', [ $this, 'resource_hints' ], 99, 1 );
		}
	}

	public function ob_start() {
		ob_start();
	}

	public function link_preload_header( $src ) {

		if ( strpos( $src, home_url() ) !== false ) {

			$preload_src = apply_filters( 'http2_link_preload_src', $src );

			if ( ! empty( $preload_src ) ) {
				$header = sprintf( 'Link: <%s>; rel=preload; as=%s', esc_url( $this->link_url_to_relative_path( $preload_src ) ), sanitize_html_class( $this->link_resource_hint_as( current_filter() ) ) );
				if ( ( $this->header_size_accumulator + strlen( $header ) ) < $this->max_header_size ) {
					$this->header_size_accumulator += strlen( $header );
					header( $header, false );
				}

				$GLOBALS[ 'http2_' . $this->link_resource_hint_as( current_filter() ) . '_srcs' ][] = $this->link_url_to_relative_path( $preload_src );
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
	 * Helpers
	 */

	public function get_resources( $globals = null, $resource_type ) {

		$globals           = ( null === $globals ) ? $GLOBALS : $globals;
		$resource_type_key = "http2_{$resource_type}_srcs";

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
		return apply_filters( 'http2_render_resource_hints', ! function_exists( 'wp_resource_hints' ) );
	}

	public function link_resource_hint_as( $current_hook ) {
		return 'style_loader_src' === $current_hook ? 'style' : 'script';
	}
}
