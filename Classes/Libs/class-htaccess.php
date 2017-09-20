<?php

namespace nicomartin;

class Htaccess {

	public $key = '';
	public $start_file = '';
	public $end_file = '';
	public $file = '';
	public $regex = '';

	public function __construct( $key = '' ) {

		if ( '' == $key ) {
			wp_die( 'Please add a key to the htaccess class' );
		}

		$this->key        = $key;
		$this->start_file = "# BEGIN $key";
		$this->end_file   = "# END $key";
		$this->file       = ABSPATH . '.htaccess';
		$this->regex      = '/(' . $this->start_file . '\n)(.*?)\n*(' . $this->end_file . ')/ms';

		if ( ! is_writable( $this->file ) ) {
			add_action( 'admin_notices', function () {
				$class   = 'notice notice-error';
				$message = 'Irks! Your .htacces is not writable';
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
			} );
		}
	}

	/**
	 * Public Methods
	 */

	public function set( $msg ) {
		$this->delete();
		$this->prepend( $msg );
	}

	public function append( $msg ) {
		$content = str_replace( $this->end_file, "$msg\n$this->end_file", $this->get_contents() );
		$this->save( $content );
	}

	public function prepend( $msg ) {
		$content = str_replace( $this->start_file, "$this->start_file\n$msg", $this->get_contents() );
		$this->save( $content );
	}

	public function delete() {
		$content = $this->get_contents();
		preg_match_all( $this->regex, $content, $matches, PREG_SET_ORDER, 0 );
		if ( empty( $matches ) ) {
			return;
		} else {
			$content = str_replace( $matches[0][0], '', $content );
			$this->save( $content );
		}
	}

	/**
	 * Helpers
	 */

	private function get_contents() {
		$content = file_get_contents( $this->file );
		preg_match_all( $this->regex, $content, $matches, PREG_SET_ORDER, 0 );
		if ( empty( $matches ) ) {
			return "$this->start_file\n$this->end_file\n$content";
		} elseif ( count( $matches ) == 1 ) {
			return $content;
		} else {
			$i = 0;
			foreach ( $matches as $match ) {
				$i ++;
				if ( 1 == $i ) {
					continue;
				}
				$content = str_replace( $match[0], '', $content );
			}

			return $content;
		}
	}

	private function save( $content ) {

		// remove linebreaks if three or more in a line
		$content = preg_replace( "/\n{3,}+/", "\n\n", $content );
		file_put_contents( $this->file, $content );
	}
}
