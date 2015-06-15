<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Model extends CI_Model {
	/**
	 * Class constructor
	 * @return	void
	 */
	public function __construct() {
		parent::__construct();
		$this->load->database();
	}
}