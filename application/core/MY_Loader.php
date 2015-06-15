<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Loader extends CI_Loader {
	/**
	 * List of paths to load services from
	 *
	 * @var array
	 * @access protected
	 */
	protected $_ci_service_paths = array(APPPATH, BASEPATH);

	/**
	 * Constructor
	 *
	 * Set the path to the Service files
	 */
	public function __construct() {

		parent::__construct();
		load_class('Service', 'core');
	}

	/**
	 * Service Loader
	 *
	 * Loads and instantiates services.
	 * Designed to be called from application controllers.
	 *
	 * @param	string	$service	Service name
	 * @param	array	$params		Optional parameters to pass to the service class constructor
	 * @param	string	$object_name	An optional object name to assign to
	 * @return	object
	 */
	public function service($service, $params = NULL, $object_name = NULL)
	{
		if (empty($service))
		{
			return $this;
		}
		elseif (is_array($service))
		{
			foreach ($service as $key => $value)
			{
				if (is_int($key))
				{
					$this->service($value, $params);
				}
				else
				{
					$this->service($key, $params, $value);
				}
			}

			return $this;
		}

		if ($params !== NULL && ! is_array($params))
		{
			$params = NULL;
		}

		$this->_ci_load_service($service, $params, $object_name);
		return $this;
	}

	/**
	 * Internal MY Service Loader
	 *
	 * @used-by	MY_Loader::service()
	 * @uses	MY_Loader::_ci_init_service()
	 *
	 * @param	string	$class		Class name to load
	 * @param	mixed	$params		Optional parameters to pass to the class constructor
	 * @param	string	$object_name	Optional object name to assign to
	 * @return	void
	 */
	protected function _ci_load_service($class, $params = NULL, $object_name = NULL)
	{
		// Get the class name, and while we're at it trim any slashes.
		// The directory path can be included as part of the class name,
		// but we don't want a leading slash
		$class = str_replace('.php', '', trim($class, '/'));

		// Was the path included with the class name?
		// We look for a slash to determine this
		if (($last_slash = strrpos($class, '/')) !== FALSE)
		{
			// Extract the path
			$subdir = substr($class, 0, ++$last_slash);

			// Get the filename from the path
			$class = substr($class, $last_slash);
		}
		else
		{
			$subdir = '';
		}

		$class = ucfirst($class);

		// Is this a stock sercice? There are a few special conditions if so ...
		if (file_exists(BASEPATH.'services/'.$subdir.$class.'.php'))
		{
			return $this->_ci_load_stock_service($class, $subdir, $params, $object_name);
		}
		// Let's search for the requested service file and load it.
		foreach ($this->_ci_service_paths as $path)
		{
			// BASEPATH has already been checked for
			if ($path === BASEPATH)
			{
				continue;
			}

			$filepath = $path.'services/'.$subdir.$class.'.php';

			// Safety: Was the class already loaded by a previous call?
			if (class_exists($class, FALSE))
			{
				// Before we deem this to be a duplicate request, let's see
				// if a custom object name is being supplied. If so, we'll
				// return a new instance of the object
				if ($object_name !== NULL)
				{
					$CI =& get_instance();
					if ( ! isset($CI->$object_name))
					{
						return $this->_ci_init_service($class, '', $params, $object_name);
					}
				}

				log_message('debug', $class.' class already loaded. Second attempt ignored.');
				return;
			}
			// Does the file exist? No? Bummer...
			elseif ( ! file_exists($filepath))
			{
				continue;
			}

			include_once($filepath);
			return $this->_ci_init_service($class, '', $params, $object_name);
		}

		// One last attempt. Maybe the service is in a subdirectory, but it wasn't specified?
		if ($subdir === '')
		{
			return $this->_ci_load_service($class.'/'.$class, $params, $object_name);
		}

		// If we got this far we were unable to find the requested class.
		log_message('error', 'Unable to load the requested class: '.$class);
		show_error('Unable to load the requested class: '.$class);
	}

	/**
	 * Internal MY Service Instantiator
	 *
	 * @used-by	MY_Loader::_ci_load_stock_service()
	 * @used-by	MY_Loader::_ci_load_service()
	 *
	 * @param	string		$class		Class name
	 * @param	string		$prefix		Class name prefix
	 * @param	array|null|bool	$config		Optional configuration to pass to the class constructor:
	 *						FALSE to skip;
	 *						NULL to search in config paths;
	 *						array containing configuration data
	 * @param	string		$object_name	Optional object name to assign to
	 * @return	void
	 */
	protected function _ci_init_service($class, $prefix, $config = FALSE, $object_name = NULL)
	{
		// Is there an associated config file for this class? Note: these should always be lowercase
		if ($config === NULL)
		{
			// Fetch the config paths containing any package paths
			$config_component = $this->_ci_get_component('config');

			if (is_array($config_component->_config_paths))
			{
				$found = FALSE;
				foreach ($config_component->_config_paths as $path)
				{
					// We test for both uppercase and lowercase, for servers that
					// are case-sensitive with regard to file names. Load global first,
					// override with environment next
					if (file_exists($path.'config/'.strtolower($class).'.php'))
					{
						include($path.'config/'.strtolower($class).'.php');
						$found = TRUE;
					}
					elseif (file_exists($path.'config/'.ucfirst(strtolower($class)).'.php'))
					{
						include($path.'config/'.ucfirst(strtolower($class)).'.php');
						$found = TRUE;
					}

					if (file_exists($path.'config/'.ENVIRONMENT.'/'.strtolower($class).'.php'))
					{
						include($path.'config/'.ENVIRONMENT.'/'.strtolower($class).'.php');
						$found = TRUE;
					}
					elseif (file_exists($path.'config/'.ENVIRONMENT.'/'.ucfirst(strtolower($class)).'.php'))
					{
						include($path.'config/'.ENVIRONMENT.'/'.ucfirst(strtolower($class)).'.php');
						$found = TRUE;
					}

					// Break on the first found configuration, thus package
					// files are not overridden by default paths
					if ($found === TRUE)
					{
						break;
					}
				}
			}
		}

		$class_name = $prefix.$class;

		// Is the class name valid?
		if ( ! class_exists($class_name, FALSE))
		{
			log_message('error', 'Non-existent class: '.$class_name);
			show_error('Non-existent class: '.$class_name);
		}

		// Set the variable name we will assign the class to
		// Was a custom class name supplied? If so we'll use it
		if (empty($object_name))
		{
			$object_name = strtolower($class);
			if (isset($this->_ci_varmap[$object_name]))
			{
				$object_name = $this->_ci_varmap[$object_name];
			}
		}

		// Don't overwrite existing properties
		$CI =& get_instance();
		if (isset($CI->$object_name))
		{
			if ($CI->$object_name instanceof $class_name)
			{
				log_message('debug', $class_name." has already been instantiated as '".$object_name."'. Second attempt aborted.");
				return;
			}

			show_error("Resource '".$object_name."' already exists and is not a ".$class_name." instance.");
		}

		// Save the class name and object name
		$this->_ci_classes[$object_name] = $class;

		// Instantiate the class
		$CI->$object_name = isset($config)
			? new $class_name($config)
			: new $class_name();
	}

	/**
	 * Internal MY Stock Service Loader
	 *
	 * @used-by	MY_Loader::_ci_load_service()
	 * @uses	MY_Loader::_ci_init_service()
	 *
	 * @param	string	$service	Service name to load
	 * @param	string	$file_path	Path to the service filename, relative to services/
	 * @param	mixed	$params		Optional parameters to pass to the class constructor
	 * @param	string	$object_name	Optional object name to assign to
	 * @return	void
	 */
	protected function _ci_load_stock_service($service_name, $file_path, $params, $object_name)
	{
		$prefix = 'CI_';

		if (class_exists($prefix.$service_name, FALSE))
		{
			if (class_exists(config_item('subclass_prefix').$service_name, FALSE))
			{
				$prefix = config_item('subclass_prefix');
			}

			// Before we deem this to be a duplicate request, let's see
			// if a custom object name is being supplied. If so, we'll
			// return a new instance of the object
			if ($object_name !== NULL)
			{
				$CI =& get_instance();
				if ( ! isset($CI->$object_name))
				{
					return $this->_ci_init_service($service_name, $prefix, $params, $object_name);
				}
			}

			log_message('debug', $service_name.' class already loaded. Second attempt ignored.');
			return;
		}

		$paths = $this->_ci_service_paths;
		array_pop($paths); // BASEPATH
		array_pop($paths); // APPPATH (needs to be the first path checked)
		array_unshift($paths, APPPATH);

		foreach ($paths as $path)
		{
			if (file_exists($path = $path.'services/'.$file_path.$service_name.'.php'))
			{
				// Override
				include_once($path);
				if (class_exists($prefix.$service_name, FALSE))
				{
					return $this->_ci_init_service($service_name, $prefix, $params, $object_name);
				}
				else
				{
					log_message('debug', $path.' exists, but does not declare '.$prefix.$service_name);
				}
			}
		}

		include_once(BASEPATH.'services/'.$file_path.$service_name.'.php');

		// Check for extensions
		$subclass = config_item('subclass_prefix').$service_name;
		foreach ($paths as $path)
		{
			if (file_exists($path = $path.'services/'.$file_path.$subclass.'.php'))
			{
				include_once($path);
				if (class_exists($subclass, FALSE))
				{
					$prefix = config_item('subclass_prefix');
					break;
				}
				else
				{
					log_message('debug', APPPATH.'services/'.$file_path.$subclass.'.php exists, but does not declare '.$subclass);
				}
			}
		}

		return $this->_ci_init_service($service_name, $prefix, $params, $object_name);
	}
}