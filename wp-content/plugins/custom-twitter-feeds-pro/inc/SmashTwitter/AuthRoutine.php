<?php
/**
 * Class Request
 *
 * Performs a request to the Smash Balloon Twitter API.
 *
 * @since 2.1
 */
namespace TwitterFeed\SmashTwitter;

class AuthRoutine
{

	public function __construct()
	{
	}

	public function run_register( $license_key = false )
	{
		if ( empty( $license_key ) ) {
			$license_key = get_option( 'ctf_license_key', '' );
		}

		$auth_required = false;
		$data = array(
			'url' => get_home_url(),
			'license_key' => $license_key,
		);

		$args = array(
			'body' => json_encode( $data ),
			'timeout' => 60
		);

		$request = new Request( 'register', '', $args, $auth_required );

		$return = $request->fetch();

		if ( is_wp_error( $return ) ) {
			return false;
		}

		$ctf_options = get_option( 'ctf_options', array() );

		$existing_token = ! empty( $ctf_options['site_access_token'] ) ? $ctf_options['site_access_token'] : false;
		$new_token = false;
		if ( ! empty( $return['token'] ) ) {
			$new_token = true;
			$ctf_options['site_access_token'] = $return['token'];
		}

		// Failsafe if user is already registered.
		if ( ! empty( $return['data']['token'] ) ) {
			$new_token = true;
			$ctf_options['site_access_token'] = $return['data']['token'];
		}

		if ( ! $new_token ) {
			$ctf_options['site_access_token'] = $existing_token;
		}

		update_option( 'ctf_options', $ctf_options );

		return $ctf_options['site_access_token'];
	}

	public function run_license_activation( $auth_token )
	{
		$ctf_license_key = get_option( 'ctf_license_key', '' );

		$data = array(
			'url' => get_home_url(),
			'license_key' => $ctf_license_key,
			'action' => 'activate'
		);

		$args = array(
			'body' => json_encode($data),
			'timeout' => 60
		);

		$request = new Request( 'license', '', $args, $auth_token );

		$return = $request->fetch();

		if ( is_wp_error( $return ) ) {
			return false;
		}

		return $return;
	}

	public function run_license_deactivation()
	{
		$ctf_options = get_option( 'ctf_options', array() );

		if ( empty( $ctf_options['site_access_token'] ) ) {
			return false;
		}
		$auth_token = $ctf_options['site_access_token'];

		$ctf_license_key = get_option( 'ctf_license_key', '' );

		$data = array(
			'url' => get_home_url(),
			'license_key' => $ctf_license_key,
			'action' => 'deactivate'
		);

		$args = array(
			'body' => json_encode($data),
			'timeout' => 60
		);

		$request = new Request( 'license', '', $args, $auth_token );

		$return = $request->fetch();

		if ( is_wp_error( $return ) ) {
			return false;
		}

		return $return;
	}
}