<?php

namespace nicomartin\AdvancedWPPerformance;

class CriticalCSS {

	public $ccss_folder = '';
	public $base_path = '';
	public $base_url = '';
	public $options = '';
	public $default_critical_path = '';

	public function __construct() {
		$this->ccss_folder           = ''; // default folder is set in check_empty()
		$this->base_path             = ABSPATH;
		$this->base_url              = get_home_url() . '/';
		$this->options               = get_option( awpp_get_instance()->Settings->settings_option );
		$this->default_critical_path = $this->base_path . 'wp-content/awpp/critical/';
		if ( ! file_exists( $this->default_critical_path ) ) {
			mkdir( $this->default_critical_path, 0777, true );
		}
	}

	public function run() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		/*add_filter( 'awpp_critical_dir', function ( $path ) {
			return get_template_directory() . '/assets/criticale/';
		} );*/

		if ( 'off' == $this->options['loadcss'] ) {
			return;
		}

		add_action( 'wp_head', [ $this, 'add_critical_css' ], 1 );
	}

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

		$key      = 'criticalcss';
		$file     = $this->default_critical_path . 'index.css';
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

	public function add_critical_css() {
		if ( true ) {
			echo apply_filters( 'awpp_critical_dir', $this->default_critical_path );
			echo '<pre>';
			print_r( awpp_get_critical_keys() );
			echo '</pre>';
		}
	}
}
