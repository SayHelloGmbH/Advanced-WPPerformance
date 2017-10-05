<?php

namespace nicomartin\AdvancedWPPerformance;

class Upgrade {

	public function __construct() {
		add_action( 'awpp_on_update', [ $this, 'update_to_oop_settings' ], 10, 2 );
	}

	public function update_to_oop_settings( $new_version, $old_version ) {

		if ( ! get_option( 'awpp-option' ) || '' == get_option( 'awpp-option' ) ) {
			return;
		}

		$old_settings = get_option( 'awpp-option' );

		/**
		 * awpp-settings
		 */
		$awpp_settings = [];
		if ( 'on' == $old_settings['scripts_to_footer'] ) {
			$awpp_settings['deliveryjs'] = true;
		}
		if ( 'on' == $old_settings['minify'] ) {
			$awpp_settings['minify'] = true;
		}
		if ( 'disabled' != $old_settings['loadcss'] ) {
			$awpp_settings['deliverycss'] = true;
		}
		$awpp_settings['serverpush'] = $old_settings['serverpush'];
		update_option( 'awpp_settings', $awpp_settings );

		$awpp_serverpush_files = $old_settings['serverpush_files'];
		update_option( 'awpp_serverpush_files', $awpp_serverpush_files );

		delete_option( 'awpp-option' );

	}
}
