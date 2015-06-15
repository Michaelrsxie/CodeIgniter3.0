<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Service {
	/**
	 * Class constructor
	 * @return	void
	 */
	public function __construct() {
		log_message('debug', 'Service Class Initialized');
	}

	/**
	 * Get the CI singleton
	 * @return	object
	 */
	public function __get($key) {
		$CI = & get_instance();
		return $CI->$key;
	}
}