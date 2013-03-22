<?php defined('EF5_SYSTEM') || exit;
/*********************************************************
| eXtreme-Fusion 5
| Content Management System
|
| Copyright (c) 2005-2013 eXtreme-Fusion Crew
| http://extreme-fusion.org/
|
| This program is released as free software under the
| Affero GPL license. You can redistribute it and/or
| modify it under the terms of this license which you
| can read by viewing the included agpl.txt or online
| at www.gnu.org/licenses/agpl.html. Removal of this
| copyright header is strictly prohibited without
| written permission from the original author(s).
| 
**********************************************************
 	Some open-source code comes from
---------------------------------------------------------+
| PHP-Fusion Content Management System
| Copyright (C) 2002 - 2011 Nick Jones
| http://www.php-fusion.co.uk/
+--------------------------------------------------------+
| Author: Nick Jones (Digitanium)
+--------------------------------------------------------+
| This program is released as free software under the
| Affero GPL license. You can redistribute it and/or
| modify it under the terms of this license which you
| can read by viewing the included agpl.txt or online
| at www.gnu.org/licenses/agpl.html. Removal of this
| copyright header is strictly prohibited without
| written permission from the original author(s).
+--------------------------------------------------------*/
try
{
	require_once DIR_CLASS.'Exception.php';
	require_once DIR_CLASS.'Locales.php';

	if( ! extension_loaded('pdo')) 
	{
		throw new systemException('PDO "pdo" extension is required! Please turn it on in your php.ini.');
	}
	
	if( ! extension_loaded('pdo_mysql')) 
	{
		throw new systemException('PDO "pdo_mysql" extension is required! Please turn it on in your php.ini');
	}
	
	if ( ! isset($_GET['NoOPT']))
	{
		require_once OPT_DIR.'opt.class.php';
		require_once DIR_CLASS.'Parser.php';
	}

	require_once DIR_SYSTEM.'helpers/main.php';
	
    ob_start();

	# System configuration
    $_system = new System;

    # PHP Data Object
    $_pdo = new Data('mysql:host='.$_dbconfig['host'].';dbname='.$_dbconfig['database'].';port='.$_dbconfig['port'], $_dbconfig['user'], $_dbconfig['password'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '.$_dbconfig['charset']));
    
	// http://stackoverflow.com/a/4348744/1794927
	// MYSQL_ATTR_INIT_COMMAND is available for PHP >= 5.3.1
	$_pdo->query('SET NAMES '.$_dbconfig['charset'], NULL, FALSE);
	
	$_pdo->config($_dbconfig['prefix']);
    unset($_dbconfig);
	require_once DIR_SYSTEM.'table_list.php';
	
	# Settings
    $_sett = new Sett($_system, $_pdo);

	# Members
    $_user = new User($_sett, $_pdo);

	# Admin action logger
    $_log = new Logger($_user, $_pdo, $_sett->get('logger_active'));

	# Admin login session
    if (isset($_SESSION['admin']))
    {
        $_user->adminLoggedIn($_SESSION['admin']['id'], $_SESSION['admin']['hash']);
    }
	
	# Language files
    $_locale = new Locales($_user->getLang(), DIR_LOCALE);
	$_locale->setSubDir('admin');
	
	$_locale->load('admin');
	
	# Requests
	$_request = new Request;
	
	# Files class
	$_files = new Files;

	$_url = new Url($_sett->getUns('routing', 'url_ext'), $_sett->getUns('routing', 'main_sep'), $_sett->getUns('routing', 'param_sep'), $_system->rewriteAvailable(), $_system->pathInfoExists());
		
	HELP::init($_pdo, $_sett, $_user, $_url);

	define('URL_REQUEST', isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] != '' ? HELP::cleanurl($_SERVER['REQUEST_URI']) : $_SERVER['SCRIPT_NAME']);
	define('URL_QUERY', isset($_SERVER['QUERY_STRING']) ? HELP::cleanurl($_SERVER['QUERY_STRING']) : '');
	
    # Timezone settings
    date_default_timezone_set($_sett->get('timezone'));

	#Parser
	Parser::config($_pdo, $_sett, $_user, $_request, $_log);

	define('iGUEST', $_user->iGUEST());
    define('iUSER', $_user->iUSER());
    define('iADMIN', $_user->iADMIN());
}
catch(systemException $exception)
{
    die(systemErrorHandler($exception));
}
catch(userException $exception)
{
    die(userErrorHandler($exception));
}
catch(PDOException $exception)
{
	die(PDOErrorHandler($exception));
}
