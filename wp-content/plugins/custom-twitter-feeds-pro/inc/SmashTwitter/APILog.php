<?php
/**
 * Class APILog
 *
 * Tracks API events related to retrieving new tweets and creates messages.
 *
 * @since 2.2.2
 */
namespace TwitterFeed\SmashTwitter;

class APILog {

	private $log;

	public function __construct() {
		$ctf_api_call_log = get_option( 'ctf_api_call_log', array() );
		$this->log = array();
		if ( ! empty( $ctf_api_call_log ) ) {
			$this->log = $ctf_api_call_log;
		}
	}

	public function get() {
		return $this->log;
	}

	public function update( $type, $term, $background = false ) {
		if ( is_array( $this->log ) && count( $this->log ) > 50 ) {
			reset( $this->log );
			unset( $this->log[ key( $this->log ) ] );
		}

		$this->log[] = array(
			'time' => time(),
			'type' => $type,
			'term' => $term,
			'background' => $background
		);

		update_option( 'ctf_api_call_log', $this->log, false );
	}

}