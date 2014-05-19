<?

// Diese Datei wird von internen Seiten eingebunden, die eine Authentifizierung benötigen
require_once($GLOBALS["AvtRolePersistenceHandler"]);

// Anmeldung prüfen und ggf. auf Login-Seite umleiten
if (!isset($_SESSION["useruuid"])) {
	header("Location: ".$GLOBALS["base_url"]."/login.php?returnurl=".urlencode($_SERVER["REQUEST_URI"]));
	exit;
}

// Zugriff auf Seite prüfen und ggf. auf noaccess umleiten
if(!AvtRolePersistenceHandler::canCurrentRoleReadCurrentPage()) {
	header("Location: ".$GLOBALS["base_url"]."/noaccess.php");
	exit;
}

?>