<?php

namespace nicomartin\CriticalAPI;
class Init {
	public static $name = 'Critical API';
	public static $awpp_settings_key = 'awpp-settings';
	public static $apikey_key = 'criticalapi_key';
	public static $support_email = 'hello@sayhello.ch';

	public static $ajax_action = 'criticalapi_ajax_generate';

	public function __construct() {
	}

	public function run() {
		if ( self::apikey_set() ) {
			add_filter( 'awpp_critical_dir', [ $this, 'change_critical_dir' ] );
		}
		add_action( 'wp_ajax_' . self::$ajax_action, [ $this, 'ajax_generate' ] );
	}

	public function change_critical_dir( $dir ) {
		return self::get_critical_dir();
	}

	public function ajax_generate() {

		$url = esc_url( $_POST['url'] );
		if ( strpos( untrailingslashit( get_home_url() ), $url ) != 0 ) {
			// translators: The requested URL is not a subpage of {url}
			awpp_exit_ajax( 'error', sprintf( __( 'The requested URL is not a subpage of %s', 'awpp' ), untrailingslashit( get_home_url() ) ) );
		}

		$key  = sanitize_title( $_POST['critical_key'] );
		$dir  = self::get_critical_dir();
		$file = $dir . $key . '.css';

		$css = self::fetch_css( $url );
		if ( 201 != $css['status'] ) {
			// translators: Critical CSS could not be fetched: {message} ({status})
			awpp_exit_ajax( 'error', sprintf( __( 'Critical CSS could not be fetched: %1$1s (%2$2s)', 'awpp' ), $css['message'], $css['status'] ) );
		}

		$css_file = fopen( $file, 'w' );
		fwrite( $css_file, $css['message'] );
		fclose( $css_file );

		$data = [
			'datetime' => self::convert_date(),
		];
		// translators: Critical CSS for "{key}" ({url}) generated
		awpp_exit_ajax( 'success', sprintf( __( 'Critical CSS for "%1$s" (%2$s) generated.', 'awpp' ), $key, $url ), $data );
	}

	/**
	 * Helpers
	 */
	protected function apikey_set() {
		$options = get_option( self::$awpp_settings_key );
		if ( isset( $options[ self::$apikey_key ] ) && '' != $options[ self::$apikey_key ] ) {
			return true;
		}

		return false;
	}

	protected function do_request( $url, $data = [] ) {
		if ( empty( $data ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Invalid data', 'awpp' ),
			];
		}
		if ( ! function_exists( 'curl_version' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'curl is not enabled on your server', 'awpp' ),
			];
		}

		$data_string = json_encode( $data );
		$data_string = htmlspecialchars_decode( $data_string );
		$ch          = curl_init( $url );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data_string );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Content-Length: ' . strlen( $data_string ),
		] );
		$content   = curl_exec( $ch );
		$http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		return [
			'status'  => $http_code,
			'message' => $content,
			//'message' => $content . ' data: ' . $data_string, // debugging
		];
	}

	protected function get_dimensions() {
		$dimensions = [
			'desktop' => [
				'width'  => 1200,
				'height' => 800,
			],
			'mobile'  => [
				'width'  => 700,
				'height' => 300,
			],
		];

		$dimensions = apply_filters( 'awpp_criticalapi_dimensions', $dimensions );

		$i      = 0;
		$return = [];
		foreach ( $dimensions as $device => $vals ) {
			if ( ! array_key_exists( 'width', $vals ) || ! array_key_exists( 'height', $vals ) ) {
				continue;
			}
			if ( 0 == intval( $vals['width'] ) || 0 == intval( $vals['height'] ) ) {
				continue;
			}
			if ( $i >= 2 ) {
				continue;
			}
			$i ++;
			$return[ $device ] = [
				'width'  => intval( $vals['width'] ),
				'height' => intval( $vals['height'] ),
			];
		}

		return $return;
	}

	protected function get_critical_dir() {
		return ABSPATH . 'wp-content/cache/awpp/criticalapi/';
	}

	protected function fetch_css( $url, $api_key = '' ) {

		if ( '' == $api_key ) {
			$api_key = awpp_get_setting( self::$apikey_key );
		}

		$atts = [
			'apiKey'     => $api_key,
			'url'        => $url,
			'dimensions' => self::get_dimensions(),
		];

		return self::do_request( 'https://api.critical-css.io', $atts );
	}

	protected function get_all_critical_elements() {
		$elements = [];

		$elements['front-page'] = [
			'name' => __( 'Front Page', 'awpp' ),
			'url'  => get_home_url(),
		];

		/**
		 * All Singular
		 */
		foreach ( self::get_post_types() as $key => $name ) {
			$posts = get_posts( [
				'posts_per_page' => - 1,
				'post_type'      => $key,
			] );

			$elements[ 'singular-' . $key ] = [
				'name'     => $name,
				'elements' => [],
			];

			foreach ( $posts as $post ) {
				$elements[ 'singular-' . $key ]['elements'][ 'singular-' . $post->ID ] = [
					'name' => get_the_title( $post ),
					'url'  => get_permalink( $post->ID ),
				];
			}
		}

		/**
		 * All Taxonomies
		 */
		foreach ( self::get_taxonomies() as $key => $name ) {
			$terms = get_terms( $key, [
				'hide_empty' => true,
			] );

			$elements[ 'archive-taxonomy-' . $key ] = [
				'name'     => $name,
				'elements' => [],
			];

			foreach ( $terms as $term ) {
				$elements[ 'archive-taxonomy-' . $key ]['elements'][ 'archive-taxonomy-' . $term->term_id ] = [
					'name' => apply_filters( 'the_title', $term->name ),
					'url'  => get_term_link( $term ),
				];
			}
		}

		/**
		 * All Users
		 */
		$elements['archive-author'] = [
			'name'     => __( 'Author Pages', 'awpp' ),
			'elements' => [],
		];
		foreach ( get_users() as $user ) {
			$elements['archive-author']['elements'][ 'archive-author-' . $user->user_nicename ] = [
				'name' => $user->display_name,
				'url'  => get_author_posts_url( $user->ID ),
			];
		}

		return $elements;
	}

	protected function get_post_types() {

		$post_types = [];

		$post_types_objects = get_post_types( [
			'public' => true,
		], 'objects' );

		foreach ( $post_types_objects as $pt => $object ) {
			$post_types[ $pt ] = $object->labels->name;
		}

		return $post_types;
	}

	protected function get_taxonomies() {

		$taxonomies = [];
		foreach ( self::get_post_types() as $pt => $name ) {
			$post_taxonomies_objects = get_object_taxonomies( $pt, 'objects' );
			foreach ( $post_taxonomies_objects as $tax => $tax_object ) {
				if ( ! $tax_object->show_ui ) {
					continue;
				}
				$taxonomies[ $tax ] = $tax_object->labels->name;
			}
		}

		return $taxonomies;
	}

	protected function convert_date( $timestamp = '', $type = 'datetime' ) {
		if ( '' == $timestamp ) {
			$timestamp = time();
		}
		switch ( $type ) {
			case 'date':
				return date( get_option( 'date_format' ), $timestamp );
				break;
			case 'time':
				return date( get_option( 'time_format' ), $timestamp );
				break;
			default:
				return date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
				break;
		}
	}

	/**
	 * Render
	 */
	protected function render_criticalapi_generate_list( $critical_key, $title, $urls ) {

		$file = self::get_critical_dir() . $critical_key . '.css';

		$return = '<tr class="criticalapi-generate" id="' . $critical_key . '">';
		$return .= '<td>';
		$return .= '<input name="action" type="hidden" value="' . self::$ajax_action . '"/>';
		$return .= '<input name="critical_key" type="hidden" value="' . $critical_key . '"/>';
		$return .= '<p><b>' . $title . '</b></p>';

		if ( is_array( $urls ) ) {
			$return .= '<select name="url" class="criticalapi-generate__input">';
			foreach ( $urls as $key => $val ) {
				if ( array_key_exists( 'elements', $val ) && is_array( $val['elements'] ) ) {
					if ( empty( $val['elements'] ) ) {
						continue;
					}
					$return .= "<optgroup label='{$val['name']}'>";
					foreach ( $val['elements'] as $element_key => $element ) {
						$return .= "<option data-key='{$element_key}' value='{$element['url']}'>{$element['name']}</option>";
					}
					$return .= '</optgroup>';
				} else {
					$return .= "<option data-key='{$key}' value='{$val['url']}'>{$val['name']}</option>";
				}
			}
			$return .= '</select>';
			$return .= '<input name="savepage" type="hidden" value="yes"/>';
		} elseif ( '' == $urls ) {
			$return .= '<input name="url" type="text" class="criticalapi-generate__input" placeholder="' . trailingslashit( get_home_url() ) . '..."/>';
			$return .= '<input name="savepage" type="hidden" value="no"/>';
		} else {
			$return .= '<input name="url" type="text" value="' . $urls . '" disabled class="criticalapi-generate__input"/>';
			$return .= '<input name="savepage" type="hidden" value="yes"/>';
		}
		$return .= '</td>';

		// generated
		$has_file = file_exists( $file );
		$return   .= '<td class="criticalapi-generate__generated criticalapi-generate__generated--' . ( $has_file ? 'file' : 'nofile' ) . '">';

		$filedate = '';
		if ( $has_file ) {
			$filedate = $this->convert_date( filemtime( $file ) );
		}
		$return .= '<span class="is_generated">' . $filedate . '</span>';
		$return .= '<span class="not_generated">' . __( 'not yet generated', 'awpp' ) . '</span>';
		$return .= '</td>';

		// controls
		$return .= '<td class="criticalapi-generate__controls">';
		$return .= '<button id="regenerate-criticalcss" class="button criticalapi-generate__regenerate">' . __( 'regenerate', 'awpp' ) . '</button>';
		$return .= '<br><button style="display: ' . ( $has_file ? 'inline-block' : 'none' ) . '" class="criticalapi-generate__delete">' . __( 'delete', 'awpp' ) . '</button>';
		$return .= '</td>';
		$return .= '</tr>';

		return $return;
	}
}
