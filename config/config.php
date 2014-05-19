<?
// Zu verwendender Persistenzadapter
$GLOBALS["AvtPersistenceAdapter"] = "code/core/avorium/core/persistence/MySqlPersistenceAdapter.php";
// Zu verwendende PersistenzHandler
$GLOBALS["AvtUserPersistenceHandler"] = "code/core/avorium/core/persistence/handler/UserPersistenceHandler.php";
$GLOBALS["AvtRolePersistenceHandler"] = "code/core/avorium/core/persistence/handler/RolePersistenceHandler.php";

// Datenbanbkverbindung muss in localconfig überschrieben werden
$GLOBALS["db_host"] = null;
$GLOBALS["db_database"] = null;
$GLOBALS["db_username"] = null;
$GLOBALS["db_password"] = null;

// Allgemeine Menüstruktur, in localconfig avorium_core_ui_MenuFactory::$rootMenuItem überarbeiten
avorium_core_ui_MenuFactory::addMenuItem(array('id' => 'Konto', 'title' => 'Konto', 'page' => '/Konto/Allgemein.php'));
avorium_core_ui_MenuFactory::addMenuItem(array('id' => 'Konto/Allgemein', 'title' => 'Allgemein', 'page' => '/Konto/Allgemein.php'), 'Konto');
avorium_core_ui_MenuFactory::addMenuItem(array('id' => 'Administration', 'title' => 'Administration', 'page' => '/Administration/Benutzerliste.php'));
avorium_core_ui_MenuFactory::addMenuItem(array('id' => 'Administration/Benutzer', 'title' => 'Benutzer', 'page' => '/Administration/Benutzerliste.php'), 'Administration');
avorium_core_ui_MenuFactory::addMenuItem(array('id' => 'Administration/Rollen', 'title' => 'Rollen', 'page' => '/Administration/Rollenliste.php', 'priority' => 200), 'Administration');
avorium_core_ui_MenuFactory::addMenuItem(array('id' => 'Administration/Uebersetzungen', 'title' => '&Uuml;bersetzungen', 'page' => '/Administration/Uebersetzungen.php', 'priority' => 300), 'Administration');
