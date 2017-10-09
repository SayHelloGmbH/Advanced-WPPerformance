<?php

namespace nicomartin\CriticalAPI;

class Settings extends Init {

	public function __construct() {
	}

	public function run() {

		add_action( 'awpp_settings', [ $this, 'register_apikey_settings' ] );
		add_filter( 'awpp_sanitize_' . self::$apikey_key, [ $this, 'check_apikey' ] );
		add_action( 'admin_action_awpp_remove_apikey', [ $this, 'remove_apikey' ] );

		add_action( 'awpp_settings', [ $this, 'register_settings' ] );
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

			// todo: add screen sizes

		}
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

	/**
	 * Settings
	 */
	public function register_settings() {

		if ( '' == awpp_get_setting( self::$apikey_key ) || ! awpp_get_setting( self::$apikey_key ) ) {
			return;
		}

		$page = awpp_settings()->add_page( 'criticalapi', __( 'Critical API', 'awpp' ) );

		// translators: critical-css.io is a project by {name and link}.
		$description = '<p>' . sprintf( __( 'critical-css.io is a project by %s.', 'awpp' ), '<a href="https://sayhello.ch" target="_blank">say hello</a>' ) . '</p>';
		$description .= '<p>' . sprintf( __( 'The service is based on a powerfull API which returns the CSS required for the first screen of a webpage.', 'awpp' ), '<a href="https://sayhello.ch" target="_blank">say hello</a>' ) . '</p>';
		awpp_settings()->add_section( $page, 'ccss-api-about', __( 'About critical-css.io', 'awpp' ), $description );
		$section = awpp_settings()->add_section( $page, 'ccss-api-default', __( 'Default Pages', 'awpp' ) );

	}
}
