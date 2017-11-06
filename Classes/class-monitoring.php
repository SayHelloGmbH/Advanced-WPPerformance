<?php

namespace nicomartin\AdvancedWPPerformance;

class Monitoring {

	public $option_monitoring = '';
	public $option_psikey = '';
	public $ajax_set_psikey = '';
	public $action_remove_psikey = '';
	public $ajax_save_settings = '';
	public $dir = '';

	public function __construct() {
		$this->option_monitoring    = 'awpp_monitoring';
		$this->option_psikey        = 'awpp_monitoring_psikey';
		$this->ajax_set_psikey      = 'awpp_monitoring_set_psikey';
		$this->action_remove_psikey = 'awpp_monitoring_remove_psikey';
		$this->ajax_save_settings   = 'awpp_monitoring_save_settings';
		$this->dir                  = trailingslashit( wp_upload_dir()['basedir'] ) . 'awpp-monitoring/';
		if ( ! is_dir( $this->dir ) ) {
			mkdir( $this->dir );
		}
	}

	public function run() {
		add_action( 'awpp_basics_section', [ $this, 'speed_test_monitoring' ] );
		add_action( 'wp_ajax_' . $this->ajax_set_psikey, [ $this, 'set_psikey' ] );
		add_action( 'admin_action_' . $this->action_remove_psikey, [ $this, 'remove_psikey' ] );

		add_filter( 'cron_schedules', [ $this, 'cron_schedules' ] );
		add_action( 'wp_ajax_' . $this->ajax_save_settings, [ $this, 'ajax_save_settings' ] );
	}

	public function speed_test_monitoring() {

		$urls = apply_filters( 'awpp_monitoring_urls', [ get_home_url() ] );
		if ( ! is_array( $urls ) ) {
			$urls = [ get_home_url() ];
		}

		//$colors = [ '#ff0000', '#00ff00', '#0000ff' ];

		$psi_apikey     = get_option( $this->option_psikey );
		$psi_apikey_set = ( '' != $psi_apikey );

		add_thickbox();

		/**
		 * Settings
		 */

		$settings = [
			'frequency' => [],
			'email'     => __( 'Email', 'awpp' ),

		];

		foreach ( wp_get_schedules() as $key => $shedule ) {
			$settings['frequency'][ $key ] = $shedule['display'];
		}

		echo '<div id="awpp-monitoring-settings" style="display: none;">';
		echo '<div class="awpp-monitoring-settings">';
		echo '<h3>' . __( 'Settings', 'awpp' ) . '</h3>';
		echo '<span class="awpp-monitoring_setting awpp-monitoring_setting--frequency"><label for="frequency">' . __( 'Frequency', 'awpp' ) . '</label>';
		echo '<select id="frequency" name="frequency">';
		echo '<option value="never">' . __( 'Never', 'awpp' ) . '</option>';
		foreach ( wp_get_schedules() as $key => $shedule ) {
			if ( strpos( $key, 'awpp_' ) === 0 ) {
				$selected = '';
				if ( $key == $this->get_setting( 'frequency' ) ) {
					$selected = 'selected';
				}
				echo "<option value='$key' $selected>{$shedule['display']}</option>";
			}
		}
		echo '</select>';
		echo '</span>';
		echo '<span class="awpp-monitoring_setting awpp-monitoring_setting--minindex"><label for="minindex">' . __( 'Inform me if result is lower than this index:', 'awpp' ) . '</label>';
		echo '<input id="minindex" name="minindex" type="number" min="1" max="100" value="' . $this->get_setting( 'minindex', 0 ) . '" />';
		echo '</span>';
		echo '<span class="awpp-monitoring_setting awpp-monitoring_setting--email"><label for="email">' . __( 'Email', 'awpp' ) . ':</label>';
		echo '<input id="email" name="email" type="email" value="' . $this->get_setting( 'email', get_option( 'admin_email' ) ) . '" />';
		echo '</span>';
		echo '<span class="awpp-monitoring_setting awpp-monitoring_setting--submit">';
		echo '<button id="save_settings" type="submit" class="button">' . __( 'Send', 'awpp' ) . '</button>';
		echo '<input type="hidden" name="action" value="' . $this->ajax_save_settings . '">';
		echo '</span>';

		echo '<div class="loader"></div>';
		echo '</div>';
		echo '</div>';

		/**
		 * Page
		 */

		echo '<div class="awpp-wrap__section">';
		echo '<h2>' . __( 'Monitoring', 'awpp' ) . '<a href="#TB_inline=true&width=300&height=330&inlineId=awpp-monitoring-settings" class="thickbox monitoring-options-btn"><span class="dashicons dashicons-admin-generic"></span></a></h2>';
		if ( $psi_apikey_set ) {
			echo '<table class="monitoring-links">';
			echo '<thead>';
			echo '<th>' . __( 'Link', 'awpp' ) . '</th>';
			echo '<th>' . __( 'lowest', 'awpp' ) . '</th>';
			echo '<th>' . __( 'highest', 'awpp' ) . '</th>';
			echo '<th>' . __( 'average', 'awpp' ) . '</th>';
			echo '<th></th>';
			echo '</thead>';
			echo '<tbody>';
			foreach ( $urls as $index => $url ) {
				$key  = $this->filter_monitoring_url_key( $url );
				$file = $this->dir . $key . '.json';
				if ( ! file_exists( $file ) ) {
					fopen( $file, 'w' );
				}
				$data = json_decode( file_get_contents( $file ), true );
				if ( is_null( $data ) ) {
					continue;
				}

				//echo '<pre>' . print_r( $data, true ) . '</pre>';
				//rsort( $data );
				//echo '<pre>' . print_r( $data, true ) . '</pre>';

				$scores = [];
				foreach ( $data as $timestamp => $d ) {
					$scores[ $timestamp ] = $d['ruleGroups']['SPEED']['score'];
				}

				//$color_index = $index % count( $colors );
				//$color       = $colors[ $color_index ];

				$max       = max( $scores );
				$min       = min( $scores );
				$max_times = [];
				$min_times = [];
				$av        = 0;

				foreach ( $scores as $timestamp => $score ) {

					if ( $score == $max ) {
						$max_times[] = awpp_convert_date( $timestamp );
					}
					if ( $score == $min ) {
						$min_times[] = awpp_convert_date( $timestamp );
					}

					$av = $av + $score;
				}
				$average = round( $av / count( $scores ), 2 );

				$max_times_sliced = array_slice( $max_times, 0, 5 );
				if ( count( $max_times ) != count( $max_times_sliced ) ) {
					$max_times_sliced[] = '...';
				}

				$min_times_sliced = array_slice( $min_times, 0, 5 );
				if ( count( $min_times ) != count( $min_times_sliced ) ) {
					$min_times_sliced[] = '...';
				}

				echo '<tr class="monitoring-table">';
				echo "<td class='monitoring-table_link'><span class='monitoring-table_color'></span>{$url}</td>";
				echo "<td class='monitoring-table_lowest'><span data-balloon='" . implode( '&#10;', $min_times_sliced ) . "' data-balloon-break>$min</span></td>";
				echo "<td class='monitoring-table_highest'><span data-balloon='" . implode( '&#10;', $max_times_sliced ) . "' data-balloon-break>$max</span></td>";
				echo "<td class='monitoring-table_average'><b>$average</b></td>";
				echo "<td class='monitoring-table_remove'></td>";
				echo '</tr>';

			} // End foreach().
			echo '</tbody>';
			echo '</table>';
		} // End if().

		if ( $psi_apikey_set ) {
			//$val = str_repeat( '*', strlen( $psi_apikey ) - 4 ) . substr( $psi_apikey, - 4 );
			//echo '<input type="text" value="' . $val . '" disabled />';
			echo '<p class="awpp-smaller monitoring-remove-psikey"><a href="admin.php?action=' . $this->action_remove_psikey . '&site=' . get_current_blog_id() . '">' . __( 'remove API Key', 'awpp' ) . '</a></p>';
		} else {
			echo '<p><b>' . __( 'Google Pagespeed Insights API Key', 'awpp' ) . '</b></p>';
			echo '<div class="" id="monitoring-set-psikey">';
			echo '<p><a href="https://console.developers.google.com/apis/library/pagespeedonline.googleapis.com/" target="_blank">' . __( 'Get an API Key', 'awpp' ) . '</a></p>';
			echo '<input type="text" name="apikey" />';
			//echo 'AIzaSyD1DEAkkZIGqitAhOTn1BbqctWP6f_tAoI';
			echo '<input name="action" value="' . $this->ajax_set_psikey . '" type="hidden" />';
			wp_nonce_field( $this->option_psikey . '_nonce', 'nonce' );
			echo '<br><br><button class="button">' . __( 'Save', 'awpp' ) . '</button>';
			echo '</div>';
		}

		echo '</div>';
	}

	public function set_psikey() {
		if ( ! wp_verify_nonce( $_POST['nonce'], $this->option_psikey . '_nonce' ) ) {
			awpp_exit_ajax( 'error', '<p>' . sht_error( 'nonce error' ) . '</p>' );
		}

		$return = $this->do_psi_request( get_home_url(), $_POST['apikey'] );

		if ( isset( $return['error'] ) && is_array( $return['error'] ) ) {
			$error = "Error {$return['error']['code']}: {$return['error']['message']}";
			awpp_exit_ajax( 'error', $error, $return );
		}

		update_option( $this->option_psikey, $_POST['apikey'] );
		awpp_exit_ajax( 'success', 'test', $return );

	}

	public function remove_psikey() {

		if ( false === current_user_can( awpp_settings()->capability ) ) {
			wp_die( esc_html__( 'Access denied.', 'awpp' ) );
		}

		update_option( $this->option_psikey, '' );
		$sendback = wp_get_referer();
		wp_redirect( esc_url_raw( $sendback ) );
		exit;
	}

	public function do_psi_request( $url, $key = '' ) {

		if ( '' == $key ) {
			$key = get_option( $this->option_psikey );
		}

		$url = "https://www.googleapis.com/pagespeedonline/v2/runPagespeed?url=$url&key=$key";

		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
		$content = curl_exec( $ch );
		curl_close( $ch );

		return json_decode( $content, true );
	}

	public function cron_schedules( $schedules ) {

		$schedules['awpp_hourly'] = [
			'interval' => ( 60 * 60 ),
			'display'  => __( 'Every Hour', 'awpp' ),
		];

		$schedules['awpp_twicedaily'] = [
			'interval' => ( 60 * 60 * 12 ),
			'display'  => __( 'Twice Daily', 'awpp' ),
		];

		$schedules['awpp_daily'] = [
			'interval' => ( 60 * 60 * 24 ),
			'display'  => __( 'Daily', 'awpp' ),
		];

		$schedules['awpp_twiceweekly'] = [
			'interval' => ( 60 * 60 * 24 * 3.5 ),
			'display'  => __( 'Twice Weekly', 'awpp' ),
		];

		$schedules['awpp_weekly'] = [
			'interval' => ( 60 * 60 * 24 * 7 ),
			'display'  => __( 'Weekly', 'awpp' ),
		];

		return $schedules;
	}

	public function ajax_save_settings() {

		$data = [
			'frequency' => 'never',
			'minindex'  => 1,
			'email'     => '',
		];

		foreach ( $data as $key => $value ) {
			if ( ! array_key_exists( $key, $_POST ) ) {
				continue;
			}
			if ( 'minindex' == $key ) {
				$data[ $key ] = intval( $_POST[ $key ] );
			} elseif ( 'email' == $key ) {
				$data[ $key ] = sanitize_email( $_POST[ $key ] );
			} else {
				$data[ $key ] = $_POST[ $key ];
			}
		}

		update_option( $this->option_monitoring, $data );

		wp_clear_scheduled_hook( 'awpp_monitoring_sheduled_psi_request' );
		if ( 'never' != $data['frequency'] ) {
			$this->sheduled_psi_request();
			wp_schedule_event( time(), $data['frequency'], 'awpp_monitoring_sheduled_psi_request' );
		}
		add_action( 'awpp_monitoring_sheduled_psi_request', [ $this, 'sheduled_psi_request' ] );

		awpp_exit_ajax( 'success', 'test' );
	}

	public function get_setting( $key, $default = '' ) {
		$option = get_option( $this->option_monitoring );
		if ( ! is_array( $option ) ) {
			return $default;
		}
		if ( array_key_exists( $key, $option ) ) {
			return $option[ $key ];
		}

		return $default;
	}

	public function sheduled_psi_request() {

		$urls     = apply_filters( 'awpp_monitoring_urls', [ get_home_url() ] );
		$settings = get_option( $this->option_monitoring );

		foreach ( $urls as $url ) {
			$key  = $this->filter_monitoring_url_key( $url );
			$file = $this->dir . $key . '.json';
			if ( ! file_exists( $file ) ) {
				fopen( $file, 'w' );
			}
			$old_data = json_decode( file_get_contents( $file ), true );
			if ( is_null( $old_data ) ) {
				$old_data = [];
			}

			$return_value = $this->do_psi_request( $url );
			if ( ! isset( $return['error'] ) ) {
				$old_data[ time() ] = $return_value;
				$parsed_value       = $this->parse_psi( $return_value );
				if ( intval( $settings['minindex'] ) > $parsed_value['score'] ) {
					// translators: Sheduled Pagespeed insights returned a score lower than {{score}}.
					$content = sprintf( __( 'Sheduled Pagespeed insights returned a score lower than %s.', 'awpp' ), $settings['minindex'] );
					// translators: Time {DateTime}
					$content .= "\n" . sprintf( __( 'Time: %s', 'awpp' ), awpp_convert_date() );
					$content .= "\n" . __( 'Answer:', 'awpp' );
					$content .= "\n<pre style='text-align: left;'>" . print_r( $parsed_value, true ) . '</pre>';
					wp_mail( $settings['email'], __( 'Advanced WPPerformance - Monitoring', 'awpp' ), $content );
				}
			}
			file_put_contents( $file, json_encode( $old_data ) );
		}
	}

	private function parse_psi( $data ) {
		$return          = [];
		$return['score'] = $data['ruleGroups']['SPEED']['score'];
		$return['rules'] = [];
		foreach ( $data['formattedResults']['ruleResults'] as $key => $val ) {
			$return['rules'][ $key ]['title']   = $val['localizedRuleName'];
			$return['rules'][ $key ]['content'] = $val['summary']['format'];
		}

		return $return;
	}

	private function filter_monitoring_url_key( $url ) {
		$key = str_replace( [ 'https://', 'http://', 'www.' ], '', $url );

		return sanitize_title( $key );
	}
}
