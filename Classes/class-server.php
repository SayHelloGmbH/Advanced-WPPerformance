<?php

namespace nicomartin\AdvancedWPPerformance;
class Server {
	private $htaccess = '';

	public function __construct() {
		$this->htaccess = new \nicomartin\Htaccess( 'Advanced WPPerformance' );
	}

	public function run() {
		add_action( 'awpp_settings', [ $this, 'register_system_recs' ] );
		add_action( 'awpp_settings', [ $this, 'register_settings' ] );
		add_action( 'awpp_sanitize', [ $this, 'do_htaccess' ] );
		register_uninstall_hook( awpp_get_instance()->file, [ 'clean_up' ] );
	}

	public function register_system_recs() {

		$section = awpp_settings()->add_section( awpp_settings_page_server(), 'systemcheck', __( 'System Recommendations', 'awpp' ) );

		/**
		 * PHP Version
		 */

		$content = '';
		if ( version_compare( PHP_VERSION, '7.0.0', '>=' ) ) {
			$content .= '<p class="awpp-check awpp-check--good">' . __( 'Great!', 'awpp' ) . '</p>';
			$content .= '<p class="awpp-smaller">' . __( 'Your are using PHP 7 or higher.', 'awpp' ) . '</p>';
		} else {
			$content .= '<p class="awpp-check awpp-check--bad">' . __( 'Needs work!', 'awpp' ) . '</p>';
			// translators: Currently you are using PHP Version {PHP_VERSION}. Version 7.0.0 brought some enormous performance improvements. We highly recommend to contact you hosting provider to upgrade to min PHP Version 7.0.0
			$content .= '<p class="awpp-smaller">' . sprintf( __( 'Currently you are using PHP Version %1$s. Version 7.0.0 brought some enormous performance improvements. We highly recommend to contact you hosting provider to upgrade to min PHP Version 7.0.0.', 'awpp' ), '<b>' . PHP_VERSION . '</b>' ) . '</p>';
		}
		awpp_settings()->add_message( $section, 'php7', __( 'PHP Version', 'awpp' ), $content );

		/**
		 * HTTP Version
		 */

		$env          = getenv( 'X_SPDY' );
		$http         = $_SERVER['SERVER_PROTOCOL'];
		$http_version = explode( '/', $http )[1];
		if ( version_compare( $http_version, '2', '>=' ) || ( '' != $env && false != $env ) ) {
			$content = '<p class="awpp-check awpp-check--good">' . __( 'Great!', 'awpp' ) . '</p>';
			$content .= '<p class="awpp-smaller">' . __( 'Your are using min. HTTP/2.', 'awpp' ) . '</p>';
		} else {
			$content = '<p class="awpp-check awpp-check--bad">' . __( 'Needs work!', 'awpp' ) . '</p>';
			// translators: This Plugin uses the advantages of {HTTP_VERSION}. Currently your server supports %1$s. We highly recommend to contact you hosting provider to upgrade to HTTP/2
			$content .= '<p class="awpp-smaller">' . sprintf( __( 'This Plugin uses the advantages of HTTP/2. Currently your server supports %1$s. We highly recommend to contact you hosting provider to upgrade to HTTP/2', 'awpp' ), $http ) . '</p>';
			//$content .= $env . '-' . $http;
		}
		awpp_settings()->add_message( $section, 'http2', __( 'HTTP Version', 'awpp' ), $content );
	}

	public function register_settings() {

		$section = awpp_settings()->add_section( awpp_settings_page_server(), 'server', __( 'Server Settings', 'awpp' ) );
		$args    = [
			'after_field' => '<p class="awpp-smaller">' . __( 'If enabled, Advanced WPPerformance will add compression using "AddOutputFilterByType DEFLATE" inside your .htaccess for common file types.', 'awpp' ) . '</p>',
		];
		awpp_settings()->add_checkbox( $section, 'compression', __( 'Enable Compression', 'awpp' ), '', $args );
		$args = [
			'after_field' => '<p class="awpp-smaller">' . __( 'If enabled, Advanced WPPerformance will set Cache-Control Headers to your .htaccess. One year for images and one month for CSS and JS files.', 'awpp' ) . '</p>',
		];
		awpp_settings()->add_checkbox( $section, 'cachingheaders', __( 'Set caching headers', 'awpp' ), '', $args );
	}

	public function do_htaccess( $data ) {
		if ( isset( $data['compression'] ) || isset( $data['cachingheaders'] ) ) {

			$add = '';

			if ( $data['compression'] ) {

				$deflate_types_pre = [
					'text'        => [ 'plain', 'html', 'xml', 'shtml', 'css', 'x-component', 'javascript' ],
					'image'       => [ 'svg+xml', 'image/x-icon' ],
					'font'        => [ 'opentype' ],
					'application' => [ 'xml', 'xhtml+xml', 'rss+xml', 'javascript', 'x-javascript', 'json', 'vnd.ms-fontobject', 'x-font-ttf', 'x-web-app-manifest+json' ],
				];

				$deflate_types = [];
				foreach ( $deflate_types_pre as $main => $types ) {
					foreach ( $types as $type ) {
						$deflate_types[] = $main . '/' . $type;
					}
				}

				$deflate_types = apply_filters( 'awpp_deflate_types', $deflate_types );

				$add .= "<IfModule mod_deflate.c>\n";
				foreach ( $deflate_types as $deflate_type ) {
					$add .= "AddOutputFilterByType DEFLATE $deflate_type\n";
				}
				$add .= "</IfModule>\n";
			}

			if ( $data['cachingheaders'] ) {

				$caching_types_pre = [
					'text'        => [
						'html'       => '2 seconds',
						'css'        => '1 months',
						'js'         => '1 months',
						'javascript' => '1 months',
					],
					'image'       => [
						'gif'  => '1 years',
						'jpg'  => '1 years',
						'jpeg' => '1 years',
						'png'  => '1 years',
						'ico'  => '1 years',
					],
					'application' => [
						'javascript' => '1 months',
					],
				];

				$caching_types = [];

				foreach ( $caching_types_pre as $main => $types ) {
					foreach ( $types as $type => $time ) {
						$caching_types[ $main . '/' . $type ] = $time;
					}
				}

				$caching_types = apply_filters( 'awpp_caching_types', $caching_types );

				$add .= "<IfModule mod_expires.c>\n";
				$add .= "ExpiresActive on\n";
				$add .= "ExpiresDefault \"access plus 30 seconds\"\n";
				foreach ( $caching_types as $type => $time ) {
					$add .= "ExpiresByType $type \"access plus $time\"\n";
				}
				$add .= "</IfModule>\n";
			} // End if().
			$this->htaccess->set( $add );
		}// End if().
	}

	public function clean_up() {
		$this->htaccess->delete();
	}
}
