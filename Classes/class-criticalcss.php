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
		$this->default_critical_path = $this->base_path . 'wp-content/awpp/critical/';
		$this->options               = get_option( awpp_get_instance()->Settings->settings_option );

		if ( ! file_exists( $this->default_critical_path ) ) {
			mkdir( $this->default_critical_path, 0777, true );
		}
	}

	public function run() {

		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_bar_menu', [ $this, 'add_toolbar_item' ] );

		if ( awpp_is_frontend() && 'off' != $this->options['loadcss'] ) {
			add_action( 'wp_head', [ $this, 'add_critical_css' ], 1 );
		}
	}

	/**
	 * Settings
	 */

	public function register_settings() {
		register_setting( awpp_get_instance()->Settings->settings_group, awpp_get_instance()->Settings->settings_option, [ $this, 'update_file' ] );
		add_settings_field( 'criticalcss', __( 'Critical CSS', 'awpp' ), [ $this, 'section_callback' ], awpp_get_instance()->Settings->settings_page, awpp_get_instance()->Settings->settings_section );
	}

	public function section_callback() {

		$path = apply_filters( 'awpp_critical_dir', $this->default_critical_path );
		if ( $path != $this->default_critical_path ) {
			echo '<p>' . __( 'Custom critical directory found:', 'awpp' ) . ' <code>' . $path . '</code></p>';
			if ( ! is_dir( $path ) ) {
				echo '<p class="error">' . __( 'Folder does not exist!', 'awpp' ) . '</p>';
			}
			if ( ! is_file( $path . 'index.css' ) ) {
				echo '<p class="error">' . __( 'index.css does not exist!', 'awpp' ) . '</p>';
			}

			return;
		}

		$file = $this->default_critical_path . 'index.css';
		if ( ! file_exists( $file ) ) {
			fopen( $file, 'w' );
		}

		$key      = 'criticalcss';
		$file_url = str_replace( $this->base_path, $this->base_url, $file );
		$val      = file_get_contents( $file );
		printf( '<textarea type="text" rows="10" cols="70" name="%1$s[%2$s]" id="%2$s">%3$s</textarea>', awpp_get_instance()->Settings->settings_option, $key, $val );
		echo "<p>File: <a target='_blank' href='$file_url'>$file_url</a></p>";
	}

	public function update_file( $input ) {

		if ( apply_filters( 'awpp_critical_dir', $this->default_critical_path ) != $this->default_critical_path ) {
			return $input;
		}

		$css = $input['criticalcss'];
		if ( ! isset( $input['criticalcss'] ) ) {
			return $input;
		}

		$path = plugin_dir_path( awpp_get_instance()->file ) . 'Classes/Libs';
		require_once $path . '/minify/autoload.php';
		require_once $path . '/path-converter/autoload.php';

		$minifier = new \MatthiasMullie\Minify\CSS( $css );
		$minifier->minify( $this->default_critical_path . 'index.css' );

		return $input;
	}

	/**
	 * Toolbar
	 */

	public function add_toolbar_item( $wp_admin_bar ) {

		$html = '';
		$html .= '<input type="checkbox" id="awpp-check-criticalcss" />';
		$html .= '<label for="awpp-check-criticalcss">';
		$html .= __( 'Test Critical CSS', 'awpp' );
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

		if ( ! is_admin() && 'off' != $this->options['loadcss'] ) {
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

		echo "<style type='text/css' id='criticalCSS' media='all'>$content</style>";
	}
}
