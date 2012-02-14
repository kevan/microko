<?php

$time = microtime( TRUE );
$memory = memory_get_usage();

error_reporting( E_ALL | E_STRICT ) ;
ini_set( 'display_errors', 'On' );

define('DOCROOT', realpath( dirname(__FILE__) ) . DIRECTORY_SEPARATOR);

define('APPPATH', DOCROOT . 'application' . DIRECTORY_SEPARATOR);
define('SYSTEM', DOCROOT . 'system' . DIRECTORY_SEPARATOR);

require SYSTEM . 'base.php';
require APPPATH . 'bootstrap.php';

//echo '<!-- time: ' . ( microtime( TRUE ) - $time ) . '; memory: ' . ( memory_get_usage() - $memory ) . ' -->';