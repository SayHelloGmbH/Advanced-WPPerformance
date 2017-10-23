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

		if ( ! file_exists( $this->default_critical_path ) ) {
			mkdir( $this->default_critical_path, 0777, true );
		}
	}

	public function run() {

		if ( ! apply_filters( 'awpp_use_critical_api', false ) ) {
			add_action( 'awpp_settings', [ $this, 'register_settings' ] );
		}
		add_filter( 'awpp_sanitize_criticalcss', [ $this, 'minify_criticalcss' ] );
		add_action( 'admin_bar_menu', [ $this, 'add_toolbar_item' ] );

		if ( awpp_is_frontend() ) {
			add_action( 'wp_head', [ $this, 'add_critical_css' ], 1 );
		}
	}

	public function register_settings() {

		$section = awpp_settings()->add_section( awpp_settings_page_assets(), 'ccss', __( 'Above the fold CSS', 'awpp' ) );

		if ( ! apply_filters( 'awpp_use_critical_api', false ) ) {
			if ( ! awpp_get_setting( 'deliverycss' ) ) {
				$content = '<p>' . __( 'Please enable "Optimize CSS Delivery". With a normal css delivery, this option is not necessary.', 'awpp' ) . '</p>';
				awpp_settings()->add_message( $section, 'ccssmessage', __( 'Critical CSS', 'awpp' ), $content );
			} else {
				$path = apply_filters( 'awpp_critical_dir', $this->default_critical_path );
				if ( $this->default_critical_path != $path ) {

					$content = '';
					// translators: Custom critical directory found: code
					$content .= '<p>' . sprintf( __( 'Custom critical directory found: %s', 'awpp' ), '<code>' . $path . '</code>' ) . '</p>';
					if ( ! is_dir( $path ) ) {
						$content .= '<p class="error">' . __( 'Folder does not exist!', 'awpp' ) . '</p>';
					}
					if ( ! is_file( $path . 'index.css' ) ) {
						$content .= '<p class="error">' . __( 'index.css does not exist!', 'awpp' ) . '</p>';
					}
					awpp_settings()->add_message( $section, 'ccssmessage', __( 'Critical CSS', 'awpp' ), $content );

				} else {

					$file = $this->default_critical_path . 'index.css';
					if ( ! file_exists( $file ) ) {
						fopen( $file, 'w' );
					}

					$file_url = str_replace( $this->base_path, $this->base_url, $file );
					$val      = file_get_contents( $file );

					$args['after_field'] = "<p class='awpp-smaller'>File: <a target='_blank' href='$file_url'>$file_url</a></p>";

					awpp_settings()->add_textarea( $section, 'criticalcss', __( 'Critical CSS', 'awpp' ), $val, $args );

				}
			} // End if().
		} // End if().
	}

	public function minify_criticalcss( $css ) {

		$path = plugin_dir_path( awpp_get_instance()->file ) . 'Classes/Libs';
		require_once $path . '/minify/autoload.php';
		require_once $path . '/path-converter/autoload.php';

		$minifier = new \MatthiasMullie\Minify\CSS( $css );
		$minifier->minify( $this->default_critical_path . 'index.css' );

		return $minifier->minify();
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
			'id'     => awpp_get_instance()->Init->admin_bar_id . '-criticalcss',
			'parent' => awpp_get_instance()->Init->admin_bar_id,
			'title'  => __( 'Critical CSS', 'awpp' ),
			'href'   => '',
			'meta'   => [
				'class' => awpp_get_instance()->prefix . '-adminbar-criticalcss ' . ( awpp_get_setting( 'deliverycss' ) ? '' : 'disabled' ),
			],
		];

		if ( awpp_is_frontend() && awpp_get_setting( 'deliverycss' ) ) {
			$args['meta']['html'] = '<div class="ab-item ab-empty-item">' . $html . '</div>';
		}
		$wp_admin_bar->add_node( $args );
	}

	/**
	 * Header Output
	 */

	public function add_critical_css() {

		if ( ! awpp_get_setting( 'deliverycss' ) ) {
			return;
		}

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

		$ccss_path = $path . $critical_id . '.css';
		$ccss_url  = str_replace( ABSPATH, get_home_url() . '/', $ccss_path );

		if ( is_user_logged_in() ) {
			$content .= "\nDebug Information (for logged in users):\n";
			$content .= "- Critical ID: $critical_id\n";
			$content .= "- File: $ccss_url\n";
			if ( ! file_exists( $ccss_path ) ) {
				$content .= "\nError: File does not exist!\n";
			}
		}
		if ( '' == $critical_id ) {
			$content .= "\nError: Critical CSS File not found!\n";
			$content .= "*/\n";
		} else {
			$content .= "*/\n";
			if ( file_exists( $ccss_path ) ) {
				$content .= file_get_contents( $ccss_path );
			}
		}

		echo "<style type='text/css' id='criticalCSS' media='all'>\n{$content}\n</style>\n";
	}
}
