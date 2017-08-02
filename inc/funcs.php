<?php

function awpp_check_format( $string ) {

	$string = preg_replace( '/[^A-Za-z0-9\/\-\_\\\: ]/', '', $string );

	return $string;
}

function awpp_maybe_add_slash( $string ) {

	if ( substr( $string, - 1 ) != '/' ) {
		$string = $string . '/';
	}

	return $string;
}

function awpp_get_critical_keys() {

	$ids = [ 'index' ];

	/**
	 * Special Pages
	 */
	if ( is_front_page() || is_search() || is_404() ) {

		if ( is_front_page() ) {
			$ids[] = 'front-page';
		}

		if ( is_search() ) {
			$ids[] = 'search';
		}
		if ( is_404() ) {
			$ids[] = '404';
		}

		return $ids;
	}

	if ( is_singular() ) {
		$ids[] = 'singular';
		$ids[] = 'singular-' . get_post_type();
		$ids[] = 'singular-' . get_the_id();
	}

	if ( is_archive() || is_home() ) {

		$ids[] = 'archive';

		if ( is_post_type_archive() || is_home() ) {

			$pt = get_query_var( 'post_type', 1 );
			if ( is_home() ) {
				$pt = 'post';
			}
			$ids[] = "archive-$pt";

		} elseif ( is_author() ) {

			$ids[] = 'archive-author';
			$ids[] = 'archive-author-' . get_query_var( 'author_name' );

		} elseif ( is_date() ) {

			$ids[] = 'archive-date';
			$date  = 'year';

			if ( is_month() ) {
				$date = 'month';
			} elseif ( is_day() ) {
				$date = 'day';
			}

			$ids[] = 'archive-date-' . $date;
		} elseif ( is_tax() || is_category() || is_tag() ) {

			$ids[] = 'archive-taxonomy';
			$ids[] = 'archive-taxonomy-' . get_term( get_queried_object()->term_id )->term_id;
			$ids[] = 'archive-taxonomy-' . get_term( get_queried_object()->term_id )->taxonomy;

		}
	} // End if().

	return $ids;
}

function awpp_is_frontend() {
	if ( is_admin() || 'wp-login.php' == $GLOBALS['pagenow'] ) {
		return false;
	}
	return true;
}
