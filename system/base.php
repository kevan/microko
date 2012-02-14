<?php

class Base {

	protected static $_init     = FALSE;

	protected static $_paths    = NULL;

	public static function init()
	{
		if (self::$_init)
			return;

		self::$_init = TRUE;
		self::$_paths = array(APPPATH, SYSTEM);
	}

	public static function auto_load($class)
	{
		try
		{
			$file = str_replace('_', DIRECTORY_SEPARATOR, mb_strtolower($class));
			if ( $path = Base::find_file('classes', $file) )
			{
				require $path;
				return TRUE;
			}
			return FALSE;
		}
		catch (Exception $e)
		{
			throw $e;
			die;
		}
	}

	public static function find_file($dir, $file, $ext = NULL, $array = FALSE)
	{
		if ($ext === NULL)
		{
			$ext = '.php';
		}
		elseif ($ext)
		{
			$ext = ".{$ext}";
		}
		else
		{
			$ext = '';
		}

		$file = str_replace('_', DIRECTORY_SEPARATOR, $file);
		$path = $dir . DIRECTORY_SEPARATOR . $file . $ext;

		if ($array || $dir == 'config' || $dir == 'messages')
		{
			$paths = array_reverse(Base::$_paths);
			$found = array();
			foreach ( $paths as $dir )
			{
				if ( is_readable($dir . $path) )
				{
					$found[] = $dir . $path;
				}
			}
		}
		else
		{
			$found = FALSE;
			foreach ( Base::$_paths as $dir )
			{
				if ( is_readable($dir . $path) )
				{
					$found = $dir . $path;
					break;
				}
			}
		}

		return $found;
	}

}