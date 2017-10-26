<?php

namespace nicomartin\AdvancedWPPerformance;

class Monitoring {

	public $option_psikey = '';
	public $ajax_set_psikey = '';
	public $action_remove_psikey = '';

	public function __construct() {
		$this->option_psikey        = 'awpp_monitoring_psikey';
		$this->ajax_set_psikey      = 'awpp_monitoring_set_psikey';
		$this->action_remove_psikey = 'awpp_monitoring_remove_psikey';
	}

	public function run() {
		add_action( 'awpp_basics_section', [ $this, 'speed_test_monitoring' ] );
		add_action( 'wp_ajax_' . $this->ajax_set_psikey, [ $this, 'set_psikey' ] );
		add_action( 'admin_action_' . $this->action_remove_psikey, [ $this, 'remove_psikey' ] );
	}

	public function speed_test_monitoring() {

		$urls = get_option( 'awpp_monitoring_urls' );
		if ( ! is_array( $urls ) ) {
			$urls = [ get_home_url() ];
		}

		$values = [
			get_home_url() => [ 10, 100, 100, 85 ],
		];
		$colors = [ '#ff0000', '#00ff00', '#0000ff' ];

		$psi_apikey = get_option( $this->option_psikey );
		echo $psi_apikey;
		$psi_apikey_set = ( '' != $psi_apikey );

		add_thickbox();

		/**
		 * Settings
		 */

		echo '<div id="awpp-monitoring-settings" style="display: none;">';
		echo '</div>';

		/**
		 * Page
		 */

		echo '<div class="awpp-wrap__section">';
		echo '<h2>' . __( 'Monitoring', 'awpp' ) . '<a href="#TB_inline?width=600&height=550&inlineId=awpp-monitoring-settings" class="thickbox monitoring-options-btn"><span class="dashicons dashicons-admin-generic"></span></a></h2>';
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
				$color_index = $index % count( $colors );
				$color       = $colors[ $color_index ];

				$max       = max( $values[ $url ] );
				$max_times = [];
				$min       = min( $values[ $url ] );
				$min_times = [];
				$av        = 0;
				foreach ( $values[ $url ] as $timestamp => $score ) {

					if ( $score == $max ) {
						$max_times[] = awpp_convert_date( $timestamp );
					}
					if ( $score == $min ) {
						$min_times[] = awpp_convert_date( $timestamp );
					}

					$av = $av + $score;
				}
				$average = round( $av / count( $values[ $url ] ), 2 );

				echo '<tr class="monitoring-table">';
				echo "<td class='monitoring-table_link'><span class='monitoring-table_color' style='background-color: $color'></span>{$url}</td>";
				echo "<td class='monitoring-table_lowest'><span title='" . implode( ', ', $min_times ) . "'></span>$min</td>";
				echo "<td class='monitoring-table_highest'><span title='" . implode( ', ', $max_times ) . "'></span>$max</td>";
				echo "<td class='monitoring-table_average'><b>$average</b></td>";
				echo "<td class='monitoring-table_remove'></td>";
				echo '</tr>';
			}
			echo '</tbody>';
			echo '</table>';
		} // End if().

		echo '<p><b>' . __( 'Google Pagespeed Insights API Key', 'awpp' ) . '</b></p>';
		if ( $psi_apikey_set ) {
			$val = str_repeat( '*', strlen( $psi_apikey ) - 4 ) . substr( $psi_apikey, - 4 );
			echo '<input type="text" value="' . $val . '" disabled />';
			echo '<p class="awpp-smaller"><a href="admin.php?action=' . $this->action_remove_psikey . '&site=' . get_current_blog_id() . '">' . __( 'remove API Key', 'awpp' ) . '</a></p>';
		} else {
			echo '<div class="" id="monitoring-set-psikey">';
			echo '<p><a href="https://console.developers.google.com/apis/library/pagespeedonline.googleapis.com/" target="_blank">' . __( 'Get an API Key', 'awpp' ) . '</a></p>';
			echo '<input type="text" name="apikey" />';
			echo 'AIzaSyD1DEAkkZIGqitAhOTn1BbqctWP6f_tAoI';
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

		if ( 'error' == $return['type'] ) {
			//update_option( $this->option_psikey, $_POST['apikey'] );
			//awpp_exit_ajax( 'success', 'test' );
		}

		awpp_exit_ajax( 'error', 'er', $return );
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

		return json_decode( $content );
	}
}
