<?
// Datei wird von allen PHP-Dateien eingebunden und stellt Autoloader und Konfiguration bereit

// Session ist 2 Wochen gültig, so lange bleibt man eingeloggt
ini_set('session.gc_maxlifetime', 1209600); 
ini_set('session.cookie_lifetime', 1209600);
session_start();

spl_autoload_register(function ($classname) {
    $file = 'code/core/'.str_replace('\\', '/', str_replace('_', '/', $classname)).'.php';
	require_once($file);
});

require_once("code/utils.php");
require_once("config/config.php");
require_once("config/localconfig.php");
