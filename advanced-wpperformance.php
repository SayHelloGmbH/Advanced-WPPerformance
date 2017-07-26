<?php
/*
Plugin Name: Advanced WPPerformance
Plugin URI: https://github.com/nico-martin/Advanced-WPPerformance
Description: This plugin add several performance improvements to your WordPress site
Author: Nico Martin (mail@nicomartin.ch)
Version: 0.0.1
Author URI: https://nicomartin.ch
Text Domain: awpp
Domain Path: /languages
 */

if ( version_compare( $wp_version, '4.7', '<' ) || version_compare( PHP_VERSION, '5.3', '<' ) ) {
	function awpp_compatability_warning() {
		echo '<div class="error"><p>';
		// translators: Dependency waring
		echo sprintf( __( '“%1$s” requires PHP %2$s (or newer) and WordPress %3$s (or newer) to function properly. Your site is using PHP %4$s and WordPress %5$s. Please upgrade. The plugin has been automatically deactivated.', 'awpp' ), 'PLUGIN NAME', '5.3', '4.7', PHP_VERSION, $GLOBALS['wp_version'] );
		echo '</p></div>';
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}

	add_action( 'admin_notices', 'awpp_compatability_warning' );

	function awpp_deactivate_self() {
		deactivate_plugins( plugin_basename( __FILE__ ) );
	}

	add_action( 'admin_init', 'awpp_deactivate_self' );

	return;

} else {

	require_once 'Classes/class-plugin.php';

	function awpp_get_instance() {
		return nicomartin\AdvancedWPPerformance\Plugin::get_instance( __FILE__ );
	}

	awpp_get_instance();

	require_once 'Classes/class-init.php';
	awpp_get_instance()->Init = new nicomartin\AdvancedWPPerformance\Init();
	awpp_get_instance()->Init->run();
}
