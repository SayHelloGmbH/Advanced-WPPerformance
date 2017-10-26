<?php

namespace nicomartin\AdvancedWPPerformance;

class Init {
	public $capability = '';
	public $admin_bar_id = '';
	public $menu_title = '';

	public function __construct() {
		$this->capability   = 'administrator';
		$this->admin_bar_id = awpp_get_instance()->prefix . '-admin-bar';
		$this->menu_title   = __( 'WP Performance', 'awpp' );
	}

	public function run() {
		// Basics Page
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'awpp_basics_section', [ $this, 'intro_text' ], 1 );
		add_action( 'awpp_basics_section', [ $this, 'speed_test_link' ] );
		// Admin Bar
		add_action( 'admin_bar_menu', [ $this, 'add_toolbar' ], 90 );
		// Assets
		add_action( 'wp_enqueue_scripts', [ $this, 'add_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'add_admin_assets' ] );

		// Helper
		add_action( 'admin_footer', [ $this, 'admin_footer_js' ], 1 );
	}

	/**
	 * Basics Page
	 */
	public function add_menu_page() {
		$icon = 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( plugin_dir_path( awpp_get_instance()->file ) . '/assets/img/menu-icon.svg' ) );
		add_menu_page( awpp_get_instance()->name, $this->menu_title, $this->capability, AWPP_SETTINGS_PARENT, '', $icon, 100 );
		add_submenu_page( AWPP_SETTINGS_PARENT, __( 'Basics', 'awpp' ), __( 'Basics', 'awpp' ), $this->capability, AWPP_SETTINGS_PARENT, [ $this, 'basics_menu_page' ] );
	}

	public function basics_menu_page() {
		?>
		<div class="wrap awpp-wrap">
			<h1><?php echo awpp_get_instance()->name; ?></h1>
			<div class="awpp-wrap__content">
				<?php do_action( 'awpp_basics_section' ); ?>
			</div>
		</div>
		<?php
	}

	public function intro_text() {
		echo '<div class="awpp-wrap__section">';
		// translators: Thank you for using plugin_name
		echo '<p><b>' . sprintf( __( 'Thank you for using %s!', 'awpp' ), awpp_get_instance()->name ) . '</b></p>';
		echo '<p>';
		// translators: This Plugin was developed by Nico Martin to deliver the best pagespeed performance possible.
		echo sprintf( __( 'This Plugin was developed by %s to deliver the best pagespeed performance possible.', 'awpp' ), '<a href="https://niomartin.ch" target="_blank">Nico Martin</a> - <a href="https://sayhello.ch" target="_blank">Say Hello GmbH</a>' );
		echo '<br>' . __( 'In contrst to other performance Plugins, this one sets focus on HTTP/2 Standards (like Server Push and SPDY).', 'awpp' );
		echo '</p>';
		$buyabeer = '<a href="https://www.paypal.me/NicoMartin" target="_blank">' . __( 'buy me a beer', 'awpp' ) . '</a>';
		$github   = '<a href="https://github.com/nico-martin/Advanced-WPPerformance" target="_blank">GitHub</a>';
		// translators: If you like this Plugin feel free to buy me a beer or get involved in the development on GitHub
		echo '<p>' . sprintf( __( 'If you like this Plugin feel free to %1$s or get involved with the development on %2$s', 'awpp' ), $buyabeer, $github ) . '</p>';
		echo '</div>';
	}

	public function speed_test_link() {
		$links = [
			'pagespeed_insights' => [
				'title' => 'Pagespeed Insights',
				'url'   => 'https://developers.google.com/speed/pagespeed/insights/?url=' . urlencode( get_home_url() ),
			],
			'google_mobile_test' => [
				'title' => 'Google Mobile Test',
				'url'   => 'https://search.google.com/search-console/mobile-friendly?url=' . urlencode( get_home_url() ),
			],
			'webpagetest'        => [
				'title' => 'WebPagetest',
				'url'   => 'http://www.webpagetest.org/?url=' . urlencode( get_home_url() ),
			],
			'sucuri'             => [
				'title' => 'Sucuri Load Time Tester',
				'url'   => 'https://performance.sucuri.net/domain/' . str_replace( [ 'http://', 'https://' ], '', get_home_url() ),
			],
		];
		echo '<div class="awpp-wrap__section">';
		echo '<h2>' . __( 'Speed tests', 'awpp' ) . '</h2>';
		foreach ( $links as $key => $values ) {
			echo "<p><b>{$values['title']}</b><br><a href='{$values['url']}' target='_blank'>{$values['url']}</a></p>";
		}
		echo '</div>';
	}

	/**
	 * Admin Bar
	 */
	public function add_toolbar( $wp_admin_bar ) {
		$icon = file_get_contents( plugin_dir_path( awpp_get_instance()->file ) . '/assets/img/menu-icon.svg' );
		$args = [
			'id'    => $this->admin_bar_id,
			'title' => "<span class='icon'>{$icon}</span> {$this->menu_title}",
			'href'  => admin_url( 'admin.php?page=' . AWPP_SETTINGS_PARENT ),
			'meta'  => [
				'class' => awpp_get_instance()->prefix . '-adminbar',
			],
		];
		$wp_admin_bar->add_node( $args );
	}

	/**
	 * Assets
	 */
	public function add_assets() {
		$script_version = awpp_get_instance()->version;
		$min            = true;
		if ( awpp_get_instance()->debug && is_user_logged_in() ) {
			$min = false;
		}
		$dir_uri = plugin_dir_url( awpp_get_instance()->file );
		//wp_enqueue_style( awpp_get_instance()->prefix . '-style', $dir_uri . 'assets/styles/ui' . ( $min ? '.min' : '' ) . '.css', [], $script_version );
		//wp_enqueue_script( awpp_get_instance()->prefix . '-script', $dir_uri . 'assets/scripts/ui' . ( $min ? '.min' : '' ) . '.js', [ 'jquery' ], $script_version, true );
		if ( is_user_logged_in() ) {
			wp_enqueue_style( awpp_get_instance()->prefix . '-admin-bar-style', $dir_uri . 'assets/styles/admin-bar' . ( $min ? '.min' : '' ) . '.css', [], $script_version );
			wp_enqueue_script( awpp_get_instance()->prefix . '-admin-bar-script', $dir_uri . 'assets/scripts/admin-bar' . ( $min ? '.min' : '' ) . '.js', [ 'jquery' ], $script_version, true );
		}
	}

	public function add_admin_assets() {
		$script_version = awpp_get_instance()->version;
		$min            = true;
		if ( awpp_get_instance()->debug && is_user_logged_in() ) {
			$min = false;
		}
		$dir_uri = plugin_dir_url( awpp_get_instance()->file );
		wp_enqueue_style( awpp_get_instance()->prefix . '-admin-style', $dir_uri . '/assets/styles/admin' . ( $min ? '.min' : '' ) . '.css', [], $script_version );
		wp_enqueue_script( awpp_get_instance()->prefix . '-admin-script', $dir_uri . '/assets/scripts/admin' . ( $min ? '.min' : '' ) . '.js', [ 'jquery' ], $script_version, true );
		wp_enqueue_style( awpp_get_instance()->prefix . '-admin-bar-style', $dir_uri . 'assets/styles/admin-bar' . ( $min ? '.min' : '' ) . '.css', [], $script_version );
		wp_enqueue_script( awpp_get_instance()->prefix . '-admin-bar-script', $dir_uri . 'assets/scripts/admin-bar' . ( $min ? '.min' : '' ) . '.js', [ 'jquery' ], $script_version, true );
	}

	/**
	 * Helper
	 */
	public function admin_footer_js() {
		$defaults = [
			'AjaxURL' => admin_url( 'admin-ajax.php' ),
			'homeurl' => trailingslashit( get_home_url() ),
		];

		$vars = apply_filters( 'awpp_admin_footer_js', $defaults );

		echo "<script id='awpp-js-vars'>\r\n";
		echo 'var AwppJsVars = ' . json_encode( $vars );
		echo '</script>';
	}
}
