<?
/**
 * Stellt eine Rolle dar
 * @avtpersistable(name="avtrole")
 */
class avorium_core_persistence_Role extends avorium_core_persistence_PersistentObject {

	/**
	 * Rollenname
	 * @avtpersistable(name="name", type="string", size=255)
	 */
	public $name;
	
	/**
	 * Rolle ist Systemadministrator
	 * @avtpersistable(name="issysadmin", type="bool")
	 */
	public $issysadmin;
	
}
?>