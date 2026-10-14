<?php
/**
 * Plugin Name: Workbox Debug
 * Description: Debugging messages
 * Version: 1.0
 */

require_once 'krumo/class.krumo.php';

if (!function_exists('__d')) {
	function __d($data, $padding = '') {
		if ($padding) {
			echo '<div style="padding-top: '.$padding.'px"></div>';
		}
		krumo($data);
	}
}

function __dd($data) {
	echo '<pre>';
	var_dump($data);
	echo '</pre>';
}