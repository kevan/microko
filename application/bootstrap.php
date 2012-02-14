<?php

define('BASEURL', 'http://' . $_SERVER['HTTP_HOST'] . '/');

spl_autoload_register(array('base', 'auto_load'));
ini_set('unserialize_callback_func', 'spl_autoload_call');

date_default_timezone_set('Europe/Moscow');
setlocale(LC_ALL, 'ru_RU.utf-8');

Base::init();

Route::set('default', '(<controller>(/<action>))')
	-> defaults(array(
	'controller'    => 'main',
	'action'        => 'index',
));

Route::run();