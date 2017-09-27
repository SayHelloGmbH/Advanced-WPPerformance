<?php

namespace nicomartin\CriticalAPI;

class Settings extends Init {

	public function __construct() {

	}

	public function run() {
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_action_awpp_remove_apikey', [ $this, 'remove_apikey' ] );

	}

	public function remove_apikey() {
		if ( false === current_user_can( awpp_get_instance()->Settings->capability ) ) {
			wp_die( esc_html__( 'Access denied.', 'awpp' ) );
		}

		update_option( self::$settings_key, '' );

		$sendback = wp_get_referer();
		wp_redirect( esc_url_raw( $sendback ) );
		exit;
	}

	/**
	 * Menu Page
	 */

	public function add_menu_page() {
		add_menu_page( self::$name, self::$name, awpp_get_instance()->Settings->capability, self::$menu_page, [ $this, 'register_menu_page' ], 'dashicons-editor-code', 90 );
		add_submenu_page( self::$menu_page, self::$name . ': ' . __( 'Settings', 'awpp' ), __( 'Settings', 'awpp' ), awpp_get_instance()->Settings->capability, self::$settings_page, [ $this, 'register_settings_page' ] );
	}

	public function register_menu_page() {

	}

	public function register_settings_page() {
		?>
		<div class="wrap awpp-criticalapi-settings-wrap">
			<h1><?php echo self::$name; ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_errors( self::$settings . '-errors' );
				settings_fields( self::$settings . '-group' );
				do_settings_sections( self::$settings_page );

				submit_button();
				?>
				<div class="about-text">
					<p>
						<?php
						// translators: This Plugin was created by ...
						printf( __( 'This Plugin was created by %s.', 'awpp' ), '<a href="https://sayhello.ch" target="_blank">Say Hello GmbH</a>' );
						?>
					</p>
				</div>
			</form>
		</div>
		<?php
	}

	public function register_settings() {
		add_settings_section( self::$settings_key, __( 'API Settings', 'awpp' ), [ $this, 'print_section_info_key' ], self::$settings_page );
		add_settings_field( 'apikey', __( 'API Key', 'awpp' ), [ $this, 'apikey_callback' ], self::$settings_page, self::$settings_key );
		register_setting( self::$settings . '-group', self::$settings_key, [ $this, 'sanitize_key' ] );

		add_settings_section( self::$settings, __( 'Settings', 'awpp' ), [ $this, 'print_section_info' ], self::$settings_page );
		add_settings_field( 'test', __( 'Test', 'awpp' ), [ $this, 'test_callback' ], self::$settings_page, self::$settings );

		register_setting( self::$settings . '-group', self::$settings, [ $this, 'sanitize' ] );
	}

	/**
	 * API Settings
	 */

	public function sanitize_key( $input ) {

		if ( ! isset( $input['apikey'] ) ) {
			return $input;
		}

		$data = [
			'apiKey' => $input['apikey'],
		];

		$request = self::do_request( 'https://api.critical-css.io/key/isValid', $data );

		if ( 200 != $request['status'] ) {
			unset( $input['apikey'] );

			if ( 'error' == $request['status'] ) {
				$message = $request['message'];
			} else {
				// translators: Error 404: Message
				$message = sprintf( __( 'Error %1$1s: %2$2s' ), $request['status'], $request['message'] );
			}

			add_settings_error( self::$settings . '-errors', 'apikey-not-found', $message, 'error' );
		}

		return $input;
	}

	public function print_section_info_key() {
		echo '';
	}

	public function apikey_callback() {
		if ( self::apikey_set() ) {
			$options = get_option( self::$settings_key );
			$key     = $options['apikey'];
			printf( '<input type="text" name="apikey_hidden" value="%s" disabled />', str_repeat( '*', strlen( $key ) - 4 ) . substr( $key, - 4 ) );
			echo '<p style="text-align: right"><small><a href="admin.php?action=awpp_remove_apikey&site=' . get_current_blog_id() . '">' . __( 'remove API Key', 'sht' ) . '</a></small></p>';
		} else {
			echo '<input type="text" name="' . self::$settings_key . '[apikey]" value="" />';
		}
	}

	/**
	 * Settings
	 */

	public function sanitize( $input ) {
		return $input;
	}

	public function print_section_info() {
		echo '';
	}

	public function test_callback() {
		echo '<input type="text" name="' . self::$settings . '[test]" ' . ( self::apikey_set() ? '' : 'disabled' ) . ' />';
	}
}
