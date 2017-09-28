<?php

namespace nicomartin\AdvancedWPPerformance;

class Settings {

	public $capability = '';
	public $icon = '';
	public $settings_page = '';
	public $settings_group = '';
	public $settings_key = '';
	public $adminbar_id = '';

	public $select_choices = '';

	private $options = '';
	public $beta = '';

	public function __construct() {

		$this->capability       = 'administrator';
		$this->settings_page    = awpp_get_instance()->prefix . '-settings';
		$this->settings_option  = awpp_get_instance()->prefix . '-option';
		$this->settings_group   = $this->settings_key . '-group';
		$this->settings_section = $this->settings_key . '-section';
		$this->adminbar_id      = awpp_get_instance()->prefix . '_adminbar';

		$this->select_choices['serverpush'] = [
			'disabled' => __( 'Disabled', 'awpp' ),
			'php'      => __( 'PHP', 'awpp' ),
			'htaccess' => __( '.htaccess', 'awpp' ),
		];

		$this->select_choices['loadcss'] = [
			'disabled' => __( 'Disabled', 'awpp' ),
			'classic'  => __( 'Classic', 'awpp' ),
		];

		$this->options = get_option( $this->settings_option );

		$this->beta = false;
		if ( get_option( 'awpp_beta' ) == 1 ) {
			$this->beta = true;
		}

		if ( $this->beta ) {
			$this->select_choices['loadcss']['api'] = __( 'API (beta)', 'awpp' );
		}
	}

	public function run() {
		add_action( 'admin_action_awpp_beta_mode', [ $this, 'awpp_beta_mode_option' ] );
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_bar_menu', [ $this, 'add_toolbar' ], 90 );
		add_action( 'admin_init', [ $this, 'http2_check' ] );
		add_action( 'awpp_on_activate', [ $this, 'set_default_settings' ] );
		add_action( 'awpp_on_update', [ $this, 'set_default_settings' ] );
	}

	public function awpp_beta_mode_option() {
		if ( false === current_user_can( $this->capability ) ) {
			wp_die( esc_html__( 'Access denied.', 'awpp' ) );
		}

		$val = false;
		if ( isset( $_GET['val'] ) && 'true' == $_GET['val'] ) {
			$val = true;
		}
		update_option( 'awpp_beta', $val );

		$sendback = wp_get_referer();
		wp_redirect( esc_url_raw( $sendback ) );
		exit;
	}

	public function add_menu_page() {
		add_submenu_page( 'options-general.php', awpp_get_instance()->name, awpp_get_instance()->name, $this->capability, $this->settings_page, [ $this, 'register_settings_page' ] );
	}

	public function register_settings_page() {
		?>
		<div class="wrap awpp-settings-wrap">
			<h1><?php echo awpp_get_instance()->name; ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_errors( $this->settings_option . 'errors' );
				settings_fields( $this->settings_group );
				do_settings_sections( $this->settings_page );
				submit_button();
				?>
				<div class="about-text">
					<p>
						<?php
						// translators: This Plugin was created by ...
						printf( __( 'This Plugin was created by %s.', 'awpp' ), '<a href="https://nicomartin.ch" target="_blank">Nico Martin</a> - <a href="https://sayhello.ch" target="_blank">Say Hello GmbH</a>' );
						?>
					</p>
				</div>
				<div class="beta">
					<p>
						<?php
						$link = '<a href="admin.php?action=awpp_beta_mode&val=true&site=' . get_current_blog_id() . '"">' . __( 'enable', 'awpp' ) . '</a>';
						if ( $this->beta ) {
							$link = '<a href="admin.php?action=awpp_beta_mode&val=false&site=' . get_current_blog_id() . '"">' . __( 'disable', 'awpp' ) . '</a>';
						}

						// translators: Beta Mode enabled (disable)
						printf( __( 'Latest beta features: %1$1s (%2$2s)', 'awpp' ), '<b>' . ( $this->beta ? 'enabled' : 'disabled' ) . '</b>', $link );
						?>
					</p>
				</div>
			</form>
		</div>
		<?php
	}

	public function register_settings() {
		$section = $this->settings_section;
		register_setting( $this->settings_group, $this->settings_option, [ $this, 'sanitize' ] );
		register_setting( $this->settings_group, $this->settings_option, [ $this, 'update_file' ] );

		add_settings_section( $section, __( 'Settings', 'awpp' ), [ $this, 'print_section_info' ], $this->settings_page );
		add_settings_field( 'scripts_to_footer', __( 'Move all scripts to footer', 'awpp' ), [ $this, 'scripts_to_footer_callback' ], $this->settings_page, $section );
		add_settings_field( 'minify', __( 'Minify CSS and JS Files', 'awpp' ), [ $this, 'minify_callback' ], $this->settings_page, $section );
		add_settings_field( 'loadcss', __( 'Load CSS async', 'awpp' ), [ $this, 'loadcss_callback' ], $this->settings_page, $section );
		add_settings_field( 'serverpush', __( 'HTTP/2 Server Push', 'awpp' ), [ $this, 'serverpush_callback' ], $this->settings_page, $section );
	}

	public function sanitize( $input ) {

		$new_input = [];

		$checkboxes = [ 'scripts_to_footer', 'minify' ];
		$select     = [ 'loadcss', 'serverpush' ];

		foreach ( $checkboxes as $key ) {
			if ( ! isset( $input[ $key ] ) ) {
				$new_input[ $key ] = 'disabled';
			} else {
				$new_input[ $key ] = $input[ $key ];
			}
		}

		foreach ( $select as $key ) {
			if ( ! array_key_exists( $input[ $key ], $this->select_choices[ $key ] ) ) {
				$new_input[ $key ] = array_keys( $this->select_choices[ $key ] )[0];
			} else {
				$new_input[ $key ] = $input[ $key ];
			}
		}

		if ( 'htaccess' == $new_input['serverpush'] && isset( $input['serverpush_files'] ) ) {
			$new_input['serverpush_files'] = $input['serverpush_files'];
		}

		if ( 'classic' == $new_input['loadcss'] && isset( $input['criticalcss'] ) ) {
			$new_input['criticalcss'] = $input['criticalcss'];
		}

		/*if ( 'api' == $new_input['loadcss'] ) {
			add_settings_error( $this->settings_option . 'errors', 'criticalapi-message', 'test', 'updated' );
		}*/

		return $new_input;
	}

	public function update_file( $input ) {

		if ( apply_filters( 'awpp_critical_dir', awpp_get_instance()->CriticalCSS->default_critical_path ) != awpp_get_instance()->CriticalCSS->default_critical_path ) {
			return $input;
		}

		if ( ! isset( $input['criticalcss'] ) ) {
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
		$minifier->minify( awpp_get_instance()->CriticalCSS->default_critical_path . 'index.css' );

		return $input;
	}

	public function print_section_info() {
		//echo '<pre>';
		//print_r( $this->options );
		//echo '</pre>';
	}

	public function scripts_to_footer_callback() {
		$key = 'scripts_to_footer';
		$val = $this->get_val( $key, 'on' );
		printf( '<input type="checkbox" name="%1$s[%2$s]" id="%2$s" %3$s />', $this->settings_option, $key, ( 'on' == $val ? 'checked' : '' ) );
	}

	public function minify_callback() {
		$key = 'minify';
		$val = $this->get_val( $key, 'on' );
		printf( '<input type="checkbox" name="%1$s[%2$s]" id="%2$s" %3$s />', $this->settings_option, $key, ( 'on' == $val ? 'checked' : '' ) );
	}

	public function loadcss_callback() {
		$key = 'loadcss';
		$val = $this->get_val( $key, 'disabled' );
		if ( 'on' == $val ) {
			$val = 'classic';
		}

		echo '<select name="' . $this->settings_option . '[' . $key . ']" id="' . $key . '">';
		foreach ( $this->select_choices[ $key ] as $choice => $name ) {
			echo '<option value="' . $choice . '" ' . ( $choice == $val ? 'selected' : '' ) . '>' . $name . '</option>';
		}
		echo '</select>';

		//return;

		//$val = $this->get_val( $key, 'on' );
		//printf( '<input type="checkbox" name="%1$s[%2$s]" id="%2$s" %3$s />', $this->settings_option, $key, ( 'on' == $val ? 'checked' : '' ) );
		?>
		<div class="settings-sub settings-sub-loadcss-api" style="display:<?php echo( 'api' == $val ? 'block' : 'none' ); ?>">
			<p><?php _e( 'This feature is still in development. After saving this option you will find a "Critical API" menu item in your navigation on the left, where you are able to configure your "above the fold" CSS.', 'awpp' ); ?></p>
		</div>

		<div class="settings-sub settings-sub-loadcss-classic" style="display:<?php echo( 'classic' == $val ? 'block' : 'none' ); ?>">
			<p><b><?php _e( 'Critical CSS', 'awpp' ); ?></b></p>
			<?php
			$path = apply_filters( 'awpp_critical_dir', awpp_get_instance()->CriticalCSS->default_critical_path );
			if ( awpp_get_instance()->CriticalCSS->default_critical_path != $path ) {
				echo '<p>' . __( 'Custom critical directory found:', 'awpp' ) . ' <code>' . $path . '</code></p>';
				if ( ! is_dir( $path ) ) {
					echo '<p class="error">' . __( 'Folder does not exist!', 'awpp' ) . '</p>';
				}
				if ( ! is_file( $path . 'index.css' ) ) {
					echo '<p class="error">' . __( 'index.css does not exist!', 'awpp' ) . '</p>';
				}
			} else {
				$file = awpp_get_instance()->CriticalCSS->default_critical_path . 'index.css';
				if ( ! file_exists( $file ) ) {
					fopen( $file, 'w' );
				}

				$key      = 'criticalcss';
				$file_url = str_replace( awpp_get_instance()->CriticalCSS->base_path, awpp_get_instance()->CriticalCSS->base_url, $file );
				$val      = file_get_contents( $file );
				printf( '<textarea type="text" rows="10" name="%1$s[%2$s]" id="%2$s">%3$s</textarea>', $this->settings_option, $key, $val );
				echo "<p>File: <a target='_blank' href='$file_url'>$file_url</a></p>";
			}
			?>
		</div>
		<?php
	}

	public function serverpush_callback() {
		$key = 'serverpush';
		$val = $this->get_val( $key, 'disabled' );

		echo '<select name="' . $this->settings_option . '[' . $key . ']" id="' . $key . '">';
		foreach ( $this->select_choices[ $key ] as $choice => $name ) {
			echo '<option value="' . $choice . '" ' . ( $choice == $val ? 'selected' : '' ) . '>' . $name . '</option>';
		}
		echo '</select>';
		?>
		<div class="settings-sub settings-sub-serverpush-htaccess" style="display:<?php echo( 'htaccess' == $val ? 'block' : 'none' ); ?>">
			<p class="info">
				<?php _e( 'This option will add server push rules directly to your .htaccess Please select all files that should be pushed on every pageload (Frontpage and all subpages).', 'awpp' ) ?>
			</p>
			<?php
			$scanned_files = get_option( awpp_get_instance()->Http2Push->serverpush_possfiles_option );
			if ( ! is_array( $scanned_files ) || empty( $scanned_files ) ) {
				$scanned_files = [
					'styles'  => [],
					'scripts' => [],
				];
			}
			foreach ( [ 'styles', 'scripts' ] as $type ) {
				echo '<p><b>' . ucfirst( $type ) . '</b></p>';
				echo '<ul id="' . $type . '" class="files-list">';
				if ( ! is_array( $scanned_files[ $type ] ) ) {
					$scanned_files[ $type ] = [];
				}
				foreach ( $scanned_files[ $type ] as $id => $url ) {
					$checked = '';
					if ( isset( $this->options['serverpush_files'][ $type ][ $id ] ) && 'on' == $this->options['serverpush_files'][ $type ][ $id ] ) {
						$checked = 'checked';
					}
					echo "<li id='$id'><label title='$url'><input type='checkbox' $checked name='awpp-option[serverpush_files][$type][$id]'/> $id</label></li>";
				}
				echo '<li class="no-items">' . __( 'No files aviable', 'awpp' ) . '</li>';
				echo '</ul>';
			}
			?>
			<p style="text-align: right;">
				<a id="scan-page" data-action="<?php echo awpp_get_instance()->Http2Push->serverpush_scan_action; ?>" data-ajaxurl="<?php echo admin_url( 'admin-ajax.php' ); ?>" class="button"><?php _e( 'Scan Frontpage', 'awpp' ); ?></a>
			</p>
			<div class="loader"></div>
		</div>
		<?php
	}

	public function add_toolbar( $wp_admin_bar ) {
		$args = [
			'id'    => $this->adminbar_id,
			'title' => str_replace( 'Advanced ', '', awpp_get_instance()->name ),
			'href'  => admin_url( 'options-general.php?page=' . $this->settings_page ),
			'meta'  => [
				'class' => awpp_get_instance()->prefix . '-adminbar',
			],
		];
		$wp_admin_bar->add_node( $args );
	}

	public function http2_check() {

		add_action( 'admin_notices', function () {
			if ( get_current_screen()->id != 'settings_page_' . $this->settings_page || getenv( 'X_SPDY' ) != '' ) {
				return;
			}
			// translators: To get the maximum out of Advanced WPPerformance you should upgrade to HTTP/2. Currenty your server supports HTTP/1
			$message = sprintf( __( 'To get the maximum out of %1$1s you should upgrade to HTTP/2. Currently your server supports %2$2s', 'awpp' ), '<b>' . awpp_get_instance()->name . '</b>', $_SERVER['SERVER_PROTOCOL'] );
			printf( '<div class="notice notice-warning"><p>%s</p></div>', $message );
		} );
	}

	public function set_default_settings() {
		$options = $this->options;

		$defaults = [
			'scripts_to_footer' => 'on',
			'minify'            => 'on',
			'loadcss'           => 'disabled',
			'serverpush'        => 'disabled',
		];

		foreach ( $defaults as $key => $default ) {
			if ( ! isset( $options[ $key ] ) ) {
				$options[ $key ] = $default;
			}
		}

		update_option( $this->settings_option, $options );
	}

	/**
	 * Helpers
	 */
	public function get_val( $key, $default = '' ) {
		if ( isset( $this->options[ $key ] ) ) {
			return $this->options[ $key ];
		} else {
			return $default;
		}
	}
}
