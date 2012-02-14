<?php

class Route {

	const REGEX_SEGMENT                 = '[^/.,;?\n]++';
	const REGEX_ESCAPE                  = '[.\\+*?[^\\]${}=!|]';

	public static $default_action       = 'index';

	public static $localhosts           = array(FALSE, '', 'local', 'localhost');

	protected static $_routes           = array();

	public static function process_uri($uri, $routes = NULL)
	{
		$routes = (empty($routes)) ? Route::all() : $routes;
		$params = NULL;
		foreach ( $routes as $name => $route )
		{
			if ( $params = $route -> matches($uri) )
			{
				return array(
					'params'    => $params,
					'route'     => $route,
				);
			}
		}
		return NULL;
	}

	public static function run()
	{
		if( !empty($_SERVER['REQUEST_URI']) )
		{
			$uri = trim($_SERVER['REQUEST_URI'], '/');
		}

		if( !empty($_SERVER['PATH_INFO']) )
		{
			$uri = trim($_SERVER['PATH_INFO'], '/');
		}

		if( !empty($_SERVER['QUERY_STRING']) )
		{
			$uri = trim($_SERVER['QUERY_STRING'], '/');
		}

		if ( !isset($uri) )
		{
			throw new Exception('Oops!!! URI not detected!');
		}

		$processed_uri = self::process_uri($uri);
		if ( empty($processed_uri) )
		{
			throw new Exception('File not found', 404);
		}

		$params = $processed_uri['params'];
		$prefix = 'controller_' . (
			isset($params['directory'])
				? str_replace(array('\\', DIRECTORY_SEPARATOR), '_', trim($params['directory'], DIRECTORY_SEPARATOR)) . '_'
				: ''
			);
		$controller = $params['controller'];
		$action = isset($params['action']) ? $params['action'] : self::$default_action;
		unset($params['controller'], $params['action'], $params['directory']);

		$file = Base::find_file('classes', $prefix . $controller);
		if ( empty($file) )
		{
			throw new Exception('Controller ' . $controller . ' not found', 404);
		}

		require $file;

		if ( !class_exists($prefix . $controller) )
		{
			throw new Exception('Controller ' . $controller . ' not found', 404);
		}

		$class = new ReflectionClass($prefix . $controller);
		if ( $class -> isAbstract() )
		{
			throw new Exception('Cannot create instances of abstract ' . $controller, 403);
		}

		$controller = $class -> newInstance();
		$class -> getMethod('before') -> invoke($controller);

		if ( !$class -> hasMethod('action_' . $action) )
		{
			throw new Exception('The requested URL ' . $uri . ' was not found on this server.', 404);
		}

		$method = $class -> getMethod('action_' . $action);
		$method -> invokeArgs($controller, $params);

		$class -> getMethod('after') -> invoke($controller);
	}

	public static function set($name, $uri_callback = NULL, $regex = NULL)
	{
		return Route::$_routes[$name] = new Route($uri_callback, $regex);
	}

	public static function get($name)
	{
		if (!isset(Route::$_routes[$name]))
		{
			throw new Exception('The requested route does not exist: ' . $name);
		}

		return Route::$_routes[$name];
	}

	public static function all()
	{
		return Route::$_routes;
	}

	public static function name(Route $route)
	{
		return array_search($route, Route::$_routes);
	}

	public static function compile($uri, array $regex = NULL)
	{
		if ( !is_string($uri) )
		{
			return FALSE;
		}

		$expression = preg_replace('~' . Route::REGEX_ESCAPE . '~', '\\\\$0', $uri);

		if ( strpos($expression, '(') !== FALSE )
		{
			$expression = str_replace(array('(', ')'), array('(?:', ')?'), $expression);
		}

		$expression = str_replace(array('<', '>'), array('(?P<', '>' . Route::REGEX_SEGMENT . ')'), $expression);
		if ($regex)
		{
			$search = $replace = array();
			foreach ($regex as $key => $value)
			{
				$search[]  = "<$key>" . Route::REGEX_SEGMENT;
				$replace[] = "<$key>$value";
			}
			$expression = str_replace($search, $replace, $expression);
		}
		return '~^' . $expression . '$~uD';
	}

	protected $_callback;
	protected $_uri             = '';
	protected $_regex           = array();
	protected $_defaults        = array('action' => 'index', 'host' => FALSE);
	protected $_route_regex;

	public function __construct($uri = NULL, $regex = NULL)
	{
		if ($uri === NULL)
		{
			return;
		}

		if ( !is_string($uri) && is_callable($uri) )
		{
			$this -> _callback = $uri;
			$this -> _uri = $regex;
			$regex = NULL;
		}
		elseif (!empty($uri))
		{
			$this -> _uri = $uri;
		}

		if (!empty($regex))
		{
			$this -> _regex = $regex;
		}

		$this -> _route_regex = Route::compile($uri, $regex);
	}

	public function defaults(array $defaults = NULL)
	{
		$this -> _defaults = $defaults;
		return $this;
	}

	public function matches($uri)
	{
		if ($this -> _callback)
		{
			$closure = $this -> _callback;
			$params = call_user_func($closure, $uri);
			if ( !is_array($params) )
			{
				return FALSE;
			}
		}
		else
		{
			if ( !preg_match($this -> _route_regex, $uri, $matches) )
			{
				return FALSE;
			}

			$params = array();
			foreach ($matches as $key => $value)
			{
				if ( is_int($key) )
				{
					continue;
				}

				$params[$key] = $value;
			}
		}

		foreach ($this -> _defaults as $key => $value)
		{
			if ( !isset($params[$key]) || '' === $params[$key])
			{
				$params[$key] = $value;
			}
		}

		return $params;
	}

	public function is_external()
	{
		$host = isset($this -> _defaults['host']) ? $this -> _defaults['host'] : FALSE;
		return !in_array($host, Route::$localhosts);
	}

}