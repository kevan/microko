<?php

class View {

	protected static $_global_data      = array();

	public static function factory($file = NULL, array $data = NULL)
	{
		return new View($file, $data);
	}

	protected static function capture($view_filename, array $view_data)
	{
		extract($view_data, EXTR_SKIP);

		if ( View::$_global_data )
		{
			extract(View::$_global_data, EXTR_REFS);
		}

		ob_start();
		try
		{
			include $view_filename;
		}
		catch (Exception $e)
		{
			ob_end_clean();
			throw $e;
		}

		return ob_get_clean();
	}

	public static function set_global($key, $value = NULL)
	{
		if ( is_array($key) )
		{
			foreach ($key as $key2 => $value)
			{
				View::$_global_data[$key2] = $value;
			}
		}
		else
		{
			View::$_global_data[$key] = $value;
		}
	}

	public static function bind_global($key, & $value)
	{
		View::$_global_data[$key] =& $value;
	}

	protected $_file;

	protected $_data            = array();

	public function __construct($file = NULL, array $data = NULL, $template = NULL)
	{
		if ( NULL !== $file )
		{
			$this -> set_filename($file);
		}

		if ( NULL !== $data )
		{
			$this -> _data = array_merge($data, $this -> _data);
		}

		if ( NULL !== $template )
		{
			self::$template = $template;
		}
	}

	public function __get($key)
	{
		if ( array_key_exists($key, $this -> _data) )
		{
			return $this -> _data[$key];
		}
		elseif ( array_key_exists($key, View::$_global_data) )
		{
			return View::$_global_data[$key];
		}
		else
		{
			throw new Exception("View variable is not set: $key");
		}
	}

	public function __set($key, $value)
	{
		$this -> set($key, $value);
	}

	public function __isset($key)
	{
		return ( isset($this -> _data[$key]) || isset(View::$_global_data[$key]) );
	}

	public function __unset($key)
	{
		unset($this -> _data[$key], View::$_global_data[$key]);
	}

	public function __toString()
	{
		return $this -> render();
	}

	public function set_filename($file)
	{
		if ( !($filename = Base::find_file('views', $file)) )
		{
			throw new Exception('The requested view ' . $file . ' could not be found');
		}

		$this -> _file = $filename;
		return $this;
	}

	public function set($key, $value = NULL)
	{
		if ( is_array($key) )
		{
			foreach ($key as $name => $value)
			{
				$this -> _data[$name] = $value;
			}
		}
		else
		{
			$this -> _data[$key] = $value;
		}

		return $this;
	}

	public function bind($key, &$value)
	{
		$this -> _data[$key] =& $value;
		return $this;
	}

	public function render($file = NULL)
	{
		if (NULL !== $file)
		{
			$this -> set_filename($file);
		}

		if (empty($this -> _file))
		{
			throw new Exception('You must set the file to use within your view before rendering');
		}

		return View::capture($this -> _file, $this -> _data);
	}

}