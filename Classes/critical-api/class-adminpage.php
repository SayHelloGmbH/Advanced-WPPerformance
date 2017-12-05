<?php

namespace nicomartin\CriticalAPI;

class AdminPage extends Init {

	public $capability = '';
	public $prefix = '';
	public $title = '';
	public $key = '';

	public function __construct() {
		$this->capability = 'administrator';
		$this->prefix     = 'awpp';
		$this->title      = self::$name;
		$this->key        = $this->prefix . '-' . sanitize_title( self::$name );
	}

	public function run() {
		add_action( 'admin_menu', [ $this, 'register_subpage' ] );
		add_action( 'awpp_criticalapi_section', [ $this, 'section_about' ] );
		add_action( 'awpp_criticalapi_section', [ $this, 'section_recommended' ] );
		add_action( 'awpp_criticalapi_section', [ $this, 'section_posttypes' ] );
		add_action( 'awpp_criticalapi_section', [ $this, 'section_taxonomies' ] );
		add_action( 'awpp_criticalapi_section', [ $this, 'section_special' ] );
		add_action( 'awpp_criticalapi_section', [ $this, 'section_date' ] );

		// Singles
		add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );
		foreach ( self::get_taxonomies() as $key => $name ) {
			add_action( $key . '_edit_form_fields', [ $this, 'criticalapi_tax_field' ], 10, 2 );
		}
		add_action( 'show_user_profile', [ $this, 'criticalapi_user_field' ], 10, 1 );
		add_action( 'edit_user_profile', [ $this, 'criticalapi_user_field' ], 10, 1 );
	}

	public function register_subpage() {
		if ( '' == awpp_get_setting( self::$apikey_key ) || ! awpp_get_setting( self::$apikey_key ) ) {
			return;
		}
		add_submenu_page( AWPP_SETTINGS_PARENT, $this->title, $this->title, $this->capability, $this->key, [ $this, 'adminpage' ] );
	}

	public function adminpage() {
		?>
		<div class="wrap <?php echo $this->prefix; ?>-wrap <?php echo $this->key; ?>-wrap">
			<h1><?php echo $this->title; ?></h1>
			<div class="<?php echo $this->prefix; ?>-wrap__content">
				<?php do_action( 'awpp_criticalapi_section' ); ?>
			</div>
		</div>
		<?php
	}

	public function section_about() {
		echo '<div class="awpp-wrap__section">';
		echo '<h2>' . __( 'About critical-css.io', 'awpp' ) . '</h2>';
		// translators: critical-css.io is a project by {say hello}.
		$description = '<p>' . sprintf( __( 'critical-css.io is a project by %s.', 'awpp' ), '<a href="https://sayhello.ch" target="_blank">say hello</a>' ) . '</p>';
		$description .= '<p>' . __( 'The service is based on a powerfull API which returns the CSS required for the first screen of a webpage.', 'awpp' ) . '</p>';
		echo $description;
		echo '</div>';
	}

	public function section_recommended() {
		echo '<div class="awpp-wrap__section">';
		echo '<h2>' . __( 'Recommended Pages', 'awpp' ) . '</h2>';
		echo '<table class="awpp-table wp-list-table widefat striped">';
		echo '<thead><tr>';
		echo '<th>' . __( 'Element', 'awpp' ) . '</th>';
		echo '<th>' . __( 'generated', 'awpp' ) . '</th>';
		echo '<th></th>';
		echo '</tr></thead>';

		$select_array = array_merge( [
			'-' => [
				'name' => __( 'Select Page', 'awpp' ),
				'url'  => '',
			],
		], self::get_all_critical_elements() );

		echo self::render_criticalapi_generate_list( 'index', __( 'Fallback (index.css)', 'awpp' ), $select_array );
		echo self::render_criticalapi_generate_list( 'front-page', __( 'Front Page', 'awpp' ), get_home_url() );
		echo self::render_criticalapi_generate_list( 'singular', __( 'Singular', 'awpp' ), $select_array );
		echo self::render_criticalapi_generate_list( 'archive', __( 'Archive', 'awpp' ), '' );
		echo '</table>';
		echo '</div>';
	}

	public function section_posttypes() {
		echo '<div class="awpp-wrap__section">';
		echo '<h2>' . __( 'Post Types', 'awpp' ) . '</h2>';
		echo '<table class="awpp-table wp-list-table widefat striped">';
		echo '<thead><tr>';
		echo '<th>' . __( 'Element', 'awpp' ) . '</th>';
		echo '<th>' . __( 'generated', 'awpp' ) . '</th>';
		echo '<th></th>';
		echo '</tr></thead>';

		$select_array = array_merge( [
			'-' => [
				'name' => __( 'Select Page', 'awpp' ),
				'url'  => '',
			],
		], self::get_all_critical_elements() );

		foreach ( self::get_post_types() as $key => $name ) {
			echo self::render_criticalapi_generate_list( 'singular-' . $key, $name, $select_array[ 'singular-' . $key ] );
			if ( '' != get_post_type_archive_link( $key ) ) {
				echo self::render_criticalapi_generate_list( 'archive-' . $key, __( 'Archive', 'awpp' ) . ': ' . $name, get_post_type_archive_link( $key ) );
			}
		}

		echo '</table>';
		echo '</div>';
	}

	public function section_taxonomies() {
		echo '<div class="awpp-wrap__section">';
		echo '<h2>' . __( 'Taxonomies', 'awpp' ) . '</h2>';
		echo '<table class="awpp-table wp-list-table widefat striped">';
		echo '<thead><tr>';
		echo '<th>' . __( 'Element', 'awpp' ) . '</th>';
		echo '<th>' . __( 'generated', 'awpp' ) . '</th>';
		echo '<th></th>';
		echo '</tr></thead>';

		$select_array = array_merge( [
			'-' => [
				'name' => __( 'Select Page', 'awpp' ),
				'url'  => '',
			],
		], self::get_all_critical_elements() );

		foreach ( self::get_taxonomies() as $key => $name ) {
			echo self::render_criticalapi_generate_list( 'archvie-taxonomy-' . $key, $name, $select_array[ 'archive-taxonomy-' . $key ] );
		}

		echo '</table>';
		echo '</div>';
	}

	public function section_special() {
		echo '<div class="awpp-wrap__section">';
		echo '<h2>' . __( 'Special Pages', 'awpp' ) . '</h2>';
		echo '<table class="awpp-table wp-list-table widefat striped">';
		echo '<thead><tr>';
		echo '<th>' . __( 'Element', 'awpp' ) . '</th>';
		echo '<th>' . __( 'generated', 'awpp' ) . '</th>';
		echo '<th></th>';
		echo '</tr></thead>';

		$select_array = array_merge( [
			'-' => [
				'name' => __( 'Select Page', 'awpp' ),
				'url'  => '',
			],
		], self::get_all_critical_elements() );

		echo self::render_criticalapi_generate_list( 'archive-author', __( 'Archive Author', 'awpp' ), $select_array['archive-author'] );
		echo self::render_criticalapi_generate_list( '404', __( '404 Page', 'awpp' ), '' );
		echo self::render_criticalapi_generate_list( 'search', __( 'Search Page', 'awpp' ), '' );
		echo '</table>';
		echo '</div>';
	}

	public function section_date() {
		echo '<div class="awpp-wrap__section">';
		echo '<h2>' . __( 'Archive Date', 'awpp' ) . '</h2>';
		echo '<table class="awpp-table wp-list-table widefat striped">';
		echo '<thead><tr>';
		echo '<th>' . __( 'Element', 'awpp' ) . '</th>';
		echo '<th>' . __( 'generated', 'awpp' ) . '</th>';
		echo '<th></th>';
		echo '</tr></thead>';

		$select_array = array_merge( [
			'-' => [
				'name' => __( 'Select Page', 'awpp' ),
				'url'  => '',
			],
		], self::get_all_critical_elements() );

		echo self::render_criticalapi_generate_list( 'archive-date', __( 'Archive Date', 'awpp' ), '' );
		echo self::render_criticalapi_generate_list( 'archive-date-year', '- ' . __( 'Archive Date Year', 'awpp' ), '' );
		echo self::render_criticalapi_generate_list( 'archive-date-month', '- ' . __( 'Archive Date Month', 'awpp' ), '' );
		echo self::render_criticalapi_generate_list( 'archive-date-day', '- ' . __( 'Archive Date Day', 'awpp' ), '' );
		echo '</table>';
		echo '</div>';
	}

	/**
	 *Singles
	 */
	public function register_meta_box() {
		foreach ( self::get_post_types() as $key => $name ) {
			add_meta_box( 'criticalapi-meta-box', self::$name, [ $this, 'criticalapi_meta_box' ], $key, 'side', 'low' );
		}
	}

	public function criticalapi_meta_box( $post ) {
		if ( 'publish' != $post->post_status ) {
			echo '<p>' . __( 'Please publish the post before generating the Critical CSS.', 'awpp' ) . '</p>';
		} else {
			echo self::render_criticalapi_generate_single( "singular-{$post->ID}", get_permalink( $post->ID ) );
		}
	}

	public function criticalapi_tax_field( $term ) {
		echo '<tr class="form-field term-criticalapi-wrap">';
		echo '<th scope="row"><label for="description">' . self::$name . '</label></th>';
		echo '<td>' . self::render_criticalapi_generate_single( "archvie-taxonomy-{$term->term_id}", get_term_link( $term ) ) . '</td>';
		echo '</tr>';
	}

	public function criticalapi_user_field( $user ) {
		echo '<table class="form-table">';
		echo '<tr class="form-field term-criticalapi-wrap">';
		echo '<th scope="row"><label for="description">' . self::$name . '</label></th>';
		echo '<td>' . self::render_criticalapi_generate_single( "archive-author-{$user->user_nicename}", get_author_posts_url( $user->ID ) ) . '</td>';
		echo '</tr>';
		echo '</table>';
	}
}
