<?php

namespace nicomartin\AdvancedWPPerformance;

class Init {

	public function __construct() {
	}

	public function run() {
		add_action( 'wp_enqueue_scripts', [ $this, 'add_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'add_admin_assets' ] );
	}

	public function add_assets() {

		$script_version = awpp_get_instance()->version;

		$min = true;
		if ( awpp_get_instance()->debug && is_user_logged_in() ) {
			$min = false;
		}

		$dir_uri = plugin_dir_url( awpp_get_instance()->file );

		//wp_enqueue_style( awpp_get_instance()->prefix . '-style', $dir_uri . 'assets/styles/ui' . ( $min ? '.min' : '' ) . '.css', [], $script_version );
		wp_enqueue_script( awpp_get_instance()->prefix . '-script', $dir_uri . 'assets/scripts/ui' . ( $min ? '.min' : '' ) . '.js', [ 'jquery' ], $script_version, true );

		if ( is_user_logged_in() ) {
			wp_enqueue_style( awpp_get_instance()->prefix . '-admin-bar-style', $dir_uri . 'assets/styles/admin-bar' . ( $min ? '.min' : '' ) . '.css', [], $script_version );
			wp_enqueue_script( awpp_get_instance()->prefix . '-admin-bar-script', $dir_uri . 'assets/scripts/admin-bar' . ( $min ? '.min' : '' ) . '.js', [ 'jquery' ], $script_version, true );
		}
	}

	public function add_admin_assets() {

		$script_version = awpp_get_instance()->version;

		$min = true;
		if ( awpp_get_instance()->debug && is_user_logged_in() ) {
			$min = false;
		}

		$dir_uri = plugin_dir_url( awpp_get_instance()->file );

		wp_enqueue_style( awpp_get_instance()->prefix . '-admin-style', $dir_uri . '/assets/styles/admin' . ( $min ? '.min' : '' ) . '.css', [], $script_version );
		wp_enqueue_script( awpp_get_instance()->prefix . '-admin-script', $dir_uri . '/assets/scripts/admin' . ( $min ? '.min' : '' ) . '.js', [ 'jquery' ], $script_version, true );

		wp_enqueue_style( awpp_get_instance()->prefix . '-admin-bar-style', $dir_uri . 'assets/styles/admin-bar' . ( $min ? '.min' : '' ) . '.css', [], $script_version );
		wp_enqueue_script( awpp_get_instance()->prefix . '-admin-bar-script', $dir_uri . 'assets/scripts/admin-bar' . ( $min ? '.min' : '' ) . '.js', [ 'jquery' ], $script_version, true );
	}
}
