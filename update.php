<?
// Update für avtcore.
// Sollte in projektspezifischer update.php so aufgerufen werden:
// require_once('/usr/lib/avorium/avtcore/php/update.php');

require_once($GLOBALS["AvtUserPersistenceHandler"]);
require_once($GLOBALS["AvtRolePersistenceHandler"]);
require_once($GLOBALS["AvtPersistenceAdapter"]);

AvtPersistenceAdapter::updateOrCreateTable("avorium_core_persistence_User");
AvtPersistenceAdapter::updateOrCreateTable("avorium_core_persistence_Role");
AvtPersistenceAdapter::updateOrCreateTable("avorium_core_persistence_RoleRight");

// Standardrollen anlegen, falls diese nicht schon existieren
if (!AvtRolePersistenceHandler::getRole("sysadminrole")) {
	$sysadminrole = new avorium_core_persistence_Role(array("uuid" => "sysadminrole", "name" => "System Administrator", "issysadmin" => true));
	AvtRolePersistenceHandler::saveRole($sysadminrole);
}
if (!AvtRolePersistenceHandler::getRole("portaladminrole")) {
	$portaladminrole = new avorium_core_persistence_Role(array("uuid" => "portaladminrole", "name" => "Portal Administrator"));
	AvtRolePersistenceHandler::saveRole($portaladminrole);
}
if (!AvtRolePersistenceHandler::getRole("userrole")) {
	$authenticateduserrole = new avorium_core_persistence_Role(array("uuid" => "userrole", "name" => "Angemeldeter Benutzer"));
	AvtRolePersistenceHandler::saveRole($authenticateduserrole);
}
if (!AvtRolePersistenceHandler::getRole("guestrole")) {
	$guestrole = new avorium_core_persistence_Role(array("uuid" => "guestrole", "name" => "Gast"));
	AvtRolePersistenceHandler::saveRole($guestrole);
}
// Systemadministrator-Konto anlegen, falls es dieses nicht schon gibt, BN, PW = "sysadmin"
if (!AvtUserPersistenceHandler::getUser("sysadminuser")) {
	$sysadminuser = new avorium_core_persistence_User(array("uuid" => "sysadminuser", "username" => "sysadmin", "password" => password_hash("sysadmin", PASSWORD_DEFAULT), "roleuuid" => "sysadminrole"));
	AvtUserPersistenceHandler::saveUser($sysadminuser);
}
