<?
require_once($GLOBALS["AvtUserPersistenceHandler"]);
require_once($GLOBALS["AvtRolePersistenceHandler"]);

/// <summary>
/// Stellt zentrales Verwaltungspunkt für das Anwendungsmenü bereit.
/// In der config.php werden die einzelnen Menüpunkte der Anwendung samt deren
/// Seiten hier drin registriert.
/// </summary>
class avorium_core_ui_MenuFactory {

	/// <summary>
	/// Hier speichert die Global.asax das Wurzel-MenuItem. Die Menü-Tags lesen das dann aus.
	/// </summary>
	private static $rootMenuItem = null;
	
	/**
	 * Liefert das Root-MenuItem und instanziiert es bei Bedarf.
	 * Wird hier intern und vom RolePersistenceHandler verwendet.
	 * @return type avorium_core_ui_MenuItem
	 */
	public static function getRootMenuItem() {
		if (static::$rootMenuItem === null) {
			static::$rootMenuItem  = new avorium_core_ui_MenuItem();
		}
		return static::$rootMenuItem;
	}
	
	/// <summary>
	/// Liefert für die gegebene Rolle eine gefilterte Struktur von MenuItems. Die Filterung
	/// erfolgt auf Basis der CanRead-Eigenschaften der RoleRights.
	/// Außerdem wird der korrekte Pfad selektiert.
	/// </summary>
	public static function getFilteredRootMenuItemForRole($roleuuid, $pageurl)
	{
		$role = AvtRolePersistenceHandler::getRole($roleuuid);
		$roleRights = AvtRolePersistenceHandler::getRoleRightsByRoleUuid($roleuuid);
		$roleRootMenuItem = new avorium_core_ui_MenuItem();
		$rootMenuItem = static::getRootMenuItem();
		foreach ($rootMenuItem->items as $menuItem) {
			$filteredMenuItem = static::getFilteredMenuItems($menuItem, $roleRights, $pageurl, (bool)$role->issysadmin);
			if ($filteredMenuItem != null) {
				$roleRootMenuItem->addMenuItem($filteredMenuItem);
			}
		}
		return $roleRootMenuItem;
	}

	private static function getFilteredMenuItems(avorium_core_ui_MenuItem $menuItem, array $roleRights, $pageurl, $isAdmin)
	{
		$matchingRoleRight = null;
		if (!$isAdmin) { // Benutzerrechte nur prüfen, wenn nicht Admin
			foreach ($roleRights as $roleRight) {
				if ($roleRight->canread && $menuItem->page ===  $roleRight->url) {
					$matchingRoleRight = $roleRight;
					break;
				}
			}
		}
		$filteredMenuItem = new avorium_core_ui_MenuItem(array(
			"page" => $menuItem->page, "title" => $menuItem->title, "visible" => $menuItem->visible,
			"selected" => $GLOBALS['base_url'].$menuItem->page === $pageurl // Selektieren, wenn Pfad übereinstimmt
		));
		// Rekursion
		foreach ($menuItem->items as $subMmenuItem) {
			$filteredSubMenuItem = static::getFilteredMenuItems($subMmenuItem, $roleRights, $pageurl, $isAdmin);
			if ($filteredSubMenuItem != null) {
				$filteredMenuItem->addMenuItem($filteredSubMenuItem);
				$filteredMenuItem->selected |= $filteredSubMenuItem->selected;
			}
		}
		// Ziel des Haupteintrages auf den ersten Untereintrag umbiegen, falls es kein direktes Zugriffsrecht gibt
		if ($isAdmin || $matchingRoleRight != null) {
			return $filteredMenuItem;
		}
		if (count($filteredMenuItem->items) < 1) {
			return null;
			// Wenn kein direkter Zugriff erlaubt ist und auch kein Unterelement erlaubt ist, dann nichts zurück geben
		}
		$filteredMenuItem->page = $filteredMenuItem->items[0]->page;
		return $filteredMenuItem;
	}
	
	/**
	 * Fügt dem Elternelement das Kindelement an gegebener Stelle hinzu.
	 */
	private static function insertMenuItem($parentMenuItem, $childMenuItem) {
		// Parent merken
		$childMenuItem->parentMenuItem = $parentMenuItem;
		// Unterelement einfach anhängen
		$parentMenuItem->items[] = $childMenuItem;
		// Feld sortieren
		usort($parentMenuItem->items, function($a, $b) {
			return $a->priority > $b->priority;
		});
	}
	
	/**
	 * Durchsucht die Menüstruktur des gegenbenen ParentMenuItems nach einem MenuItem, welches die gegebene
	 * ID hat und gibt dieses MenuItem zurück. Wird kein passendes gefunden, wird null zurück gegeben.
	 * @param type $id ID des gesuchten MenuItems
	 * @param type $parentMenuItem MenuItem, welches nach dem Element mit der gegebenen Id durchsucht werden soll
	 */
	private static function findMenuItemWithId($id, avorium_core_ui_MenuItem $parentMenuItem) {
		foreach ($parentMenuItem->items as $item) {
			if ($item->id === $id) {
				return $item;
			}
			$subItem = static::findMenuItemWithId($id, $item);
			if ($subItem != null) {
				return $subItem;
			}
		}
		return null;
	}

	/**
	 * Fügt ein Menüelement dem Menü hinzu.
	 * @param array $menuItemArray Array, welches ein MenuItem beschreibt, das dem Menü hinzugefügt werden soll
	 * @param type $parentId ID des übergeordneten Elementes, an welches das MenuItem angehängt werden soll. Wenn null, wird es direkt ans rootMenuItem angehängt.
	 * wird das neue Element dahinter gehangen.
	 */
	public static function addMenuItem(array $menuItemArray, $parentId = null)
	{
		if ($menuItemArray === null) {
			throw new Exception('Das MenuItem-Feld ist null');
		}
		$menuItem = new avorium_core_ui_MenuItem($menuItemArray);
		// Priorität prüfen und ggf setzen
		if ($menuItem->priority === null) {
			$menuItem->priority = 100;
		}
		$rootMenuItem = static::getRootMenuItem();
		$parentMenuItem = $parentId === null ? $rootMenuItem : static::findMenuItemWithId($parentId, $rootMenuItem);
		if ($parentMenuItem === null) {
			throw new Exception('Das MenuItem '.$menuItem->page.' konnte nicht hinzugefuegt werden. Parent '.$parentId.' wurde nicht gefunden.');
		}
		static::insertMenuItem($parentMenuItem, $menuItem);
	}
}
