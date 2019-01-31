<?php

/*
Plugin Name: Advanced WPPerformance
Plugin URI: https://github.com/nico-martin/Advanced-WPPerformance
Description: This plugin adds several performance improvements to your WordPress site
Author: Nico Martin
Version: 1.6.2
Author URI: https://nicomartin.ch
Text Domain: awpp
Domain Path: /languages
 */

global $wp_version;
if ( version_compare( $wp_version, '4.7', '<' ) || version_compare( PHP_VERSION, '5.4', '<' ) ) {
	function awpp_compatability_warning() {
		echo '<div class="error"><p>';
		// translators: Dependency waring
		echo sprintf( __( '“%1$s” requires PHP %2$s (or newer) and WordPress %3$s (or newer) to function properly. Your site is using PHP %4$s and WordPress %5$s. Please upgrade. The plugin has been automatically deactivated.', 'awpp' ), 'Advanced WPPerformance', '5.3', '4.7', PHP_VERSION, $GLOBALS['wp_version'] );
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

	define( 'AWPP_SETTINGS_PARENT', 'advanced-wpperformance' );
	define( 'AWPP_SETTINGS_OPTION', 'awpp-option' );

	require_once 'inc/funcs.php';
	require_once 'Classes/Libs/class-htaccess.php';

	/**
	 * Init Plugin
	 */
	require_once 'Classes/class-plugin.php';
	function awpp_get_instance() {
		return nicomartin\AdvancedWPPerformance\Plugin::get_instance( __FILE__ );
	}

	awpp_get_instance();

	require_once 'Classes/class-init.php';
	awpp_get_instance()->Init = new nicomartin\AdvancedWPPerformance\Init();
	awpp_get_instance()->Init->run();

	/**
	 * Init Settings
	 */

	require_once 'Classes/Libs/class-settings.php';
	function awpp_settings() {
		return nicomartin\Settings::get_instance( 'awpp' );
	}

	awpp_settings()->set_parent_page( AWPP_SETTINGS_PARENT );
	//awpp_settings()->set_debug( true );

	/**
	 * Features
	 */
	require_once 'Classes/class-upgrade.php';
	new nicomartin\AdvancedWPPerformance\Upgrade();

	require_once 'Classes/class-handleenqueue.php';
	awpp_get_instance()->HandleEnqueue = new nicomartin\AdvancedWPPerformance\HandleEnqueue();
	awpp_get_instance()->HandleEnqueue->run();

	require_once 'Classes/class-minify.php';
	awpp_get_instance()->Minify = new nicomartin\AdvancedWPPerformance\Minify();
	awpp_get_instance()->Minify->run();

	require_once 'Classes/class-criticalcss.php';
	awpp_get_instance()->CriticalCSS = new nicomartin\AdvancedWPPerformance\CriticalCSS();
	awpp_get_instance()->CriticalCSS->run();

	require_once 'Classes/class-server.php';
	awpp_get_instance()->Server = new nicomartin\AdvancedWPPerformance\Server();
	awpp_get_instance()->Server->run();

	require_once 'Classes/class-http2push.php';
	awpp_get_instance()->Http2Push = new nicomartin\AdvancedWPPerformance\Http2Push();
	awpp_get_instance()->Http2Push->run();

	/*
	require_once 'Classes/class-monitoring.php';
	awpp_get_instance()->Monitoring = new nicomartin\AdvancedWPPerformance\Monitoring();
	awpp_get_instance()->Monitoring->run();
	*/
	/**
	 * Critical API
	 */

	if ( defined( 'AWPP_CRITICALAPI' ) && AWPP_CRITICALAPI ) {
		require_once 'Classes/critical-api/class-init.php';
		awpp_get_instance()->CriticalAPI = new nicomartin\CriticalAPI\Init();
		awpp_get_instance()->CriticalAPI->run();

		require_once 'Classes/critical-api/class-settings.php';
		awpp_get_instance()->CriticalAPI->Settings = new nicomartin\CriticalAPI\Settings();
		awpp_get_instance()->CriticalAPI->Settings->run();

		require_once 'Classes/critical-api/class-adminpage.php';
		awpp_get_instance()->CriticalAPI->AdminPage = new nicomartin\CriticalAPI\AdminPage();
		awpp_get_instance()->CriticalAPI->AdminPage->run();
	}
} // End if().
