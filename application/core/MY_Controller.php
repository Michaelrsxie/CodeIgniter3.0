<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller {
	/**
	 * Class constructor
	 * @return	void
	 */
	public function __construct() {
		parent::__construct();
		defined('REQUEST_METHOD') or define('REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);
		defined('IS_GET') or define('IS_GET', REQUEST_METHOD == 'GET' ? true : false);
		defined('IS_POST') or define('IS_POST', REQUEST_METHOD == 'POST' ? true : false);
		defined('IS_AJAX') or define('IS_AJAX',((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) ? true : false);
	}
}