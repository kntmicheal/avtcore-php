<?php

/**
 * Stellt einen Benutzer dar
 * @avtpersistable(name="avtuser")
 */
class avorium_core_persistence_User extends avorium_core_persistence_PersistentObject {
	
	/**
	 * Benutzername
	 * @avtpersistable(name="username", type="string", size=255)
	 */
	public $username;
	
	/**
	 * Verschlüsseltes Passwort
	 * @avtpersistable(name="password", type="string", size=255)
	 */
	public $password;
	
	/**
	 * E-Mail - Adresse
	 * @avtpersistable(name="email", type="string", size=255)
	 */
	public $email;
	
	/**
	 * Identifizierer der zugeordneten Rolle
	 * @avtpersistable(name="roleuuid", type="string", size=40)
	 */
	public $roleuuid;
	
	/**
	 * Letzter Login-Zeitpunkt als UNIX-Timestamp
	 * @avtpersistable(name="lastlogin", type="long")
	 */
	public $lastlogin;
}
