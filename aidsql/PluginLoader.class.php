<?php

	require_once "class/aidsql/PluginLoader.class.php";
	require_once "interface/InjectionPlugin.interface.php";
	require_once "class/aidsql/InjectionPlugin.class.php";
	require_once "interface/Log.interface.php";
	require_once "class/log/Logger.class.php";

	$log		=	new Logger();
	$log->setEcho(TRUE);
	
	$plugins	=	array("sqli"=>"mysql5");	
	$pLoader	=	new aidSQL\PluginLoader("class/plugin/",$log);
	$pLoader->listPlugins($plugins);
	aidSQL\PluginLoader::Load($pLoader);
	$pLoader->getInstance(array("sqli"=>"mysql5"));

?>
