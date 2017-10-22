<?php

namespace nicomartin\CriticalAPI;

class Settings extends Init {

	public function __construct() {
	}

	public function run() {

		add_action( 'awpp_settings', [ $this, 'register_apikey_settings' ] );
		add_action( 'awpp_on_sanitize_' . self::$apikey_key, [ $this, 'set_index_css' ] );
		add_filter( 'awpp_sanitize_' . self::$apikey_key, [ $this, 'check_apikey' ] );
		add_action( 'admin_action_awpp_remove_apikey', [ $this, 'remove_apikey' ] );
	}

	/**
	 * ApiKey
	 */
	public function register_apikey_settings() {
		$section = awpp_settings()->add_section( awpp_settings_page_assets(), 'ccss', __( 'Above the fold CSS', 'awpp' ) . ' - API' );
		if ( ! $this->apikey_set() ) {

			$args = [
				// translators: This is an early stage beta feature. Please contact us to get further information: {email}
				'after_field' => '<p class="awpp-smaller">' . sprintf( __( 'This is an early stage beta feature. Please contact us to get further information: %s', 'awpp' ), '<a href="' . self::$support_email . '">' . self::$support_email . '</a>' ) . '</p>',
			];
			awpp_settings()->add_input( $section, self::$apikey_key, __( 'API Key', 'awpp' ), '', $args );

		} else {

			$args = [
				// translators: This is an early stage beta feature. Please contact us to get further information: {email}
				'after_field' => '<p style="text-align: right" class="awpp-smaller"><a href="admin.php?action=awpp_remove_apikey&site=' . get_current_blog_id() . '">' . __( 'remove API Key', 'awpp' ) . '</a></p>',
			];

			$val = awpp_get_setting( self::$apikey_key );
			$val = str_repeat( '*', strlen( $val ) - 4 ) . substr( $val, - 4 );

			$key     = self::$apikey_key . '-placeholder';
			$content = "<input type='text' name='$key' value='$val' disabled/>";
			awpp_settings()->add_message( $section, self::$apikey_key . '-placeholder', __( 'API Key', 'awpp' ), $content, $args );

			// translators: The devices and screensizes can be modified with a filter {filter}, which passes the sizes as an array (max two devices).
			$content = sprintf( __( 'The devices and screensizes can be modified with a filter %1$s, which passes the sizes as an array (max two devices).', 'awpp' ), '<code>awpp_criticalapi_dimensions</code>' );
			$content .= '<pre><code class="block">' . print_r( self::get_dimensions(), true ) . '</code></pre>';
			awpp_settings()->add_message( $section, 'screensizes', __( 'Screen Sizes', 'awpp' ), $content );

		}
	}

	public function set_index_css( $val ) {

		if ( '' == $val ) {
			return;
		}

		$dir = self::get_critical_dir();

		if ( ! is_dir( $dir ) ) {
			mkdir( $dir );
		}

		$index = $dir . 'index.css';
		if ( file_exists( $index ) ) {
			return;
		}

		$css = self::fetch_css( get_home_url(), $val );
		if ( 201 != $css['status'] ) {
			return;
		}

		$css_file = fopen( $index, 'w' );
		fwrite( $css_file, $css['message'] );
		fclose( $css_file );
	}

	public function check_apikey( $key ) {

		if ( '' == $key ) {
			return '';
		}

		$data    = [
			'apiKey'   => $key,
			'hostname' => get_home_url(),
		];
		$request = self::do_request( 'https://api.critical-css.io/key/isValid', $data );

		if ( 200 != $request['status'] ) {
			$key = '';
			if ( 'error' == $request['status'] ) {
				$message = $request['message'];
			} else {
				$message = sprintf( '%1$s (%2$s)', $request['message'], $request['status'] );
			}
			// translators: API Key Error: {error}
			add_settings_error( 'awpp-errors', 'apikey-not-found', sprintf( __( 'API Key Error: %s', 'awpp' ), $message ), 'error' );
		}

		return $key;
	}

	public function remove_apikey() {

		if ( false === current_user_can( awpp_settings()->capability ) ) {
			wp_die( esc_html__( 'Access denied.', 'awpp' ) );
		}

		$options                      = get_option( self::$awpp_settings_key );
		$options[ self::$apikey_key ] = '';

		update_option( self::$awpp_settings_key, $options );
		$sendback = wp_get_referer();
		wp_redirect( esc_url_raw( $sendback ) );
		exit;
	}
}
