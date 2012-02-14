<?php

class Config {

	protected static $_instances = NULL;

	public static function instance($name)
	{
		if (!isset(self::$_instances[$name]))
		{
			self::$_instances[$name] = new Config($name);
		}
		return self::$_instances[$name];
	}

	protected $_config;

	public function __construct($name)
	{
		if ( !( $file = Base::find_file('config', $name) ) )
		{
			throw new Exception('Config file ' . $name . ' not found');
		}

		if (is_array($file))
		{
			$this -> _config = array();
			foreach ($file as $f)
			{
				$this -> _config += include $f;
			}
		}
		else
		{
			$this -> _config = include $file;
		}
	}

	public function __get($name)
	{
		return $this -> get($name);
	}

	public function __set($name, $value)
	{
		$this -> _config[$name] = $value;
	}

	public function __toString()
	{
		return serialize($this -> _config);
	}

	public function as_array()
	{
		return $this -> _config;
	}

	public function get($path = NULL, $default = NULL)
	{
		if (NULL === $path)
		{
			return $this -> _config;
		}

		$arr = explode('.', $path);
		$result = $this -> _config;
		foreach ($arr as $item)
		{
			if ( !isset($result[$item]) )
			{
				unset($result);
				break;
			}
			$result = $result[$item];
		}

		return isset($result) ? $result : $default;
	}

}