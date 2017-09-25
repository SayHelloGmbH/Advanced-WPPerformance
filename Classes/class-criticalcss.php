<?php

namespace nicomartin\AdvancedWPPerformance;

class CriticalCSS {

	public $base_path = '';
	public $base_url = '';
	public $default_critical_path = '';

	public $options = '';

	public function __construct() {

		$this->base_path             = ABSPATH;
		$this->base_url              = get_home_url() . '/';
		$this->default_critical_path = $this->base_path . 'wp-content/cache/awpp/critical/';
		$this->options               = get_option( awpp_get_instance()->Settings->settings_option );

		if ( ! file_exists( $this->default_critical_path ) ) {
			mkdir( $this->default_critical_path, 0777, true );
		}
	}

	public function run() {

		add_action( 'admin_bar_menu', [ $this, 'add_toolbar_item' ] );

		if ( awpp_is_frontend() && 'off' != $this->options['loadcss'] ) {
			add_action( 'wp_head', [ $this, 'add_critical_css' ], 1 );
		}
	}

	/**
	 * Toolbar
	 */

	public function add_toolbar_item( $wp_admin_bar ) {

		$html = '';
		$html .= '<input type="checkbox" id="awpp-check-criticalcss" />';
		$html .= '<label for="awpp-check-criticalcss">';
		$html .= __( 'Test Critical CSS', 'awpp' );
		$html .= '<span class="_info -on">(on)</span>';
		$html .= '<span class="_info -off">(off)</span>';
		$html .= '</label>';

		$args = [
			'id'     => awpp_get_instance()->Settings->adminbar_id . '-criticalcss',
			'parent' => awpp_get_instance()->Settings->adminbar_id,
			'title'  => 'Critical CSS',
			'href'   => '',
			'meta'   => [
				'class' => awpp_get_instance()->prefix . '-adminbar-criticalcss ' . $this->options['loadcss'],
			],
		];

		if ( awpp_is_frontend() && 'off' != $this->options['loadcss'] ) {
			$args['meta']['html'] = '<div class="ab-item ab-empty-item">' . $html . '</div>';
		}
		$wp_admin_bar->add_node( $args );
	}

	/**
	 * Header Output
	 */

	public function add_critical_css() {

		$path = apply_filters( 'awpp_critical_dir', $this->default_critical_path );

		$critical_id = '';
		$ids         = array_reverse( awpp_get_critical_keys() );
		foreach ( $ids as $id ) {
			if ( file_exists( $path . $id . '.css' ) ) {
				$critical_id = $id;
				break;
			}
		}

		$content = "/*\n";
		$content .= "Critical CSS: set by Advanced WP Performance.\n";
		if ( is_user_logged_in() ) {
			$critical_url = str_replace( ABSPATH, get_home_url() . '/', $path . $critical_id . '.css' );
			$content      .= "\nDebug Information (for logged in users):\n";
			$content      .= "- Critical ID: $critical_id\n";
			$content      .= "- File: $critical_url\n";
		}
		if ( '' == $critical_id ) {
			$content .= "\nError: Critical CSS File not found!\n";
			$content .= "*/\n";
		} else {
			$content .= "*/\n";
			$content .= file_get_contents( $path . $critical_id . '.css' );
		}

		echo "<style type='text/css' id='criticalCSS' media='all'>\n{$content}\n</style>\n";
	}
}
