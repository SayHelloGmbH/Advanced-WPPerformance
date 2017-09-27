<?php

namespace nicomartin\CriticalAPI;

class Init {

	public static $name = 'Critical API';
	public static $menu_page = 'awpp_criticalapi';
	public static $settings_page = 'awpp_criticalapi_settings';
	public static $settings = 'awpp_criticalapi_settings';
	public static $settings_key = 'awpp_criticalapi_key';

	public function __construct() {

	}

	public function run() {

	}

	/**
	 * Helpers
	 */

	protected function apikey_set() {
		$options = get_option( self::$settings_key );
		if ( isset( $options['apikey'] ) && '' != $options['apikey'] ) {
			return true;
		}

		return false;
	}

	protected function do_request( $url, $data = [] ) {

		if ( empty( $data ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Invalid data', 'awpp' ),
			];
		}

		if ( ! function_exists( 'curl_version' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'curl is not enabled on your server', 'awpp' ),
			];
		}

		$data_string = json_encode( $data );
		$ch          = curl_init( $url );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Content-Length: ' . strlen( $data_string ),
		] );
		$content   = curl_exec( $ch );
		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		return [
			'status'  => $http_code,
			'message' => $content,
		];
	}
}
