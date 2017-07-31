<?php

function awpp_check_format( $string ) {

	$string = preg_replace( '/[^A-Za-z0-9\/\-\_ ]/', '', $string );

	return $string;
}

function awpp_maybe_add_slash( $string ) {

	if ( substr( $string, - 1 ) != '/' ) {
		$string = $string . '/';
	}

	if ( substr( $string, 0, 1 ) == '/' ) {
		$string = substr( $string, 1 );
	}

	return $string;
}

function awpp_get_critical_keys() {

	$ids = [ 'index' ];
	if ( is_front_page() ) {
		$ids[] = 'front-page';
	}
	if ( is_singular() ) {
		$ids[] = 'singular';
	}

	return $ids;
}
