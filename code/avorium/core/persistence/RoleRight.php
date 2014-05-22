<?
/**
 * Stellt eine Rollenberechtigung dar
 * @avtpersistable(name="avtroleright")
 */
class avorium_core_persistence_RoleRight extends avorium_core_persistence_PersistentObject {

	/**
	 * Identifizierer der zugeordneten Rolle
	 * @avtpersistable(name="roleuuid", type="string", size=40)
	 */
	public $roleuuid;
	
	/**
	 * URL der Seite
	 * @avtpersistable(name="url", type="string", size=255)
	 */
	public $url;
	
	/**
	 * Seite kann gelesen werden
	 * @avtpersistable(name="canread", type="bool")
	 */
	public $canread;
	
	/**
	 * Seite kan beschrieben werden
	 * @avtpersistable(name="canwrite", type="bool")
	 */
	public $canwrite;
	
}
?>