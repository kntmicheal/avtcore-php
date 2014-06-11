<?php

require_once($GLOBALS["AvtPersistenceAdapter"]);

// Standard-Handler für Rollen.
class AvtRolePersistenceHandler {
	
	// Speichert die gegebene Rolle oder legt diese an.
	public static function saveRole(avorium_core_persistence_Role $role) {
		AvtPersistenceAdapter::save($role);
	}
	
	// Löscht die gegebene Rolle
	public static function deleteRole(avorium_core_persistence_Role $role) {
		AvtPersistenceAdapter::delete($role);
	}
	
	// Liefert die Rolle mit der gegebenen UUID oder false, falls keine existiert
	public static function getRole($uuid) {
		return AvtPersistenceAdapter::get('avorium_core_persistence_Role', $uuid);
	}
	
	// Liefert alle Rollen oder ein leeres Feld
	public static function getAllRoles() {
		return AvtPersistenceAdapter::getAll('avorium_core_persistence_Role');
	}
	
	// Liefert alle Rollenrechte einer bestimmten Rolle
	public static function getRoleRightsByRoleUuid($roleuuid) {
		return AvtPersistenceAdapter::executeMultipleResultQuery("select * from avtroleright where roleuuid='".AvtPersistenceAdapter::escape($roleuuid)."'", 'avorium_core_persistence_RoleRight');
	}
	
	// Prüft, ob die Rolle die gegebene URL lesen kann.
	// SystemAdmins können immer lesen.
	public static function canRoleReadPage($roleuuid, $pageurl) {
		if (static::getRole($roleuuid)->issysadmin) {
			return true;
		}
		$rolerights = static::getRoleRightsByRoleUuid($roleuuid);
		foreach ($rolerights as $roleright) {
			if ($GLOBALS["base_url"].$roleright->url === $pageurl && $roleright->canread) {
				return true;
			}
		}
		return false;
	}
	
	// Prüft, ob der angemeldete Benutzer die gerade angeforderte Seite sehen darf
	public static function canCurrentRoleReadCurrentPage() {
		$url = strrpos($_SERVER["REQUEST_URI"], "?") ? substr($_SERVER["REQUEST_URI"], 0, strrpos($_SERVER["REQUEST_URI"], "?")) : $_SERVER["REQUEST_URI"];
		return static::canRoleReadPage($_SESSION["roleuuid"], $url);
	}
	
	// Prüft, ob die Rolle die gegebene URL schreiben kann.
	// SystemAdmins können immer schreiben.
	public static function canRoleWritePage($roleuuid, $pageurl) {
		if (static::getRole($roleuuid)->issysadmin) {
			return true;
		}
		$rolerights = static::getRoleRightsByRoleUuid($roleuuid);
		foreach ($rolerights as $roleright) {
			if ($GLOBALS["base_url"].$roleright->url === $pageurl && $roleright->canwrite) {
				return true;
			}
		}
		return false;
	}
	
	// Prüft, ob der angemeldete Benutzer die gerade angeforderte Seite schreiben darf
	public static function canCurrentRoleWriteCurrentPage() {
		$url = strrpos($_SERVER["REQUEST_URI"], "?") ? substr($_SERVER["REQUEST_URI"], 0, strrpos($_SERVER["REQUEST_URI"], "?")) : $_SERVER["REQUEST_URI"];
		return static::canRoleWritePage($_SESSION["roleuuid"], $url);
	}
	
	// Liefert alle Rollenrechte für alle Seiten einer bestimmten Rolle,
	// auch wenn in der Datenbank keine Rechte gespeichert sind. Wird
	// Für Rollenadministration verwendet.
	public static function getCompleteRoleRightsByRoleUuid($roleuuid) {
		$existingRights = AvtPersistenceAdapter::executeMultipleResultQuery("select * from avtroleright where roleuuid='".AvtPersistenceAdapter::escape($roleuuid)."'", 'avorium_core_persistence_RoleRight');
		$completeRights = array();
		foreach (static::getPagesFromMenuItems(avorium_core_ui_MenuFactory::getRootMenuItem()) as $page) {
			$roleright = new avorium_core_persistence_RoleRight(array("url" => $page));
			foreach ($existingRights as $existingRight) {
				if ($existingRight->url === $page) {
					$roleright = $existingRight;
					break;
				}
			}
			$completeRights[] = $roleright;
		}
		return $completeRights;
	}
	
	private static function getPagesFromMenuItems(avorium_core_ui_MenuItem $menuItem) {
		$pages = array();
		foreach ($menuItem->items as $subMenuItem) {
			$pages[] = $subMenuItem->page;
			foreach (static::getPagesFromMenuItems($subMenuItem) as $subPage) {
				if (!in_array($subPage, $pages)) {
					$pages[] = $subPage;
				}
			}
		}
		return $pages;
	}
	
	// Speichert die gegebenen Rollenrechte und löscht alle anderen der Rolle.
	public static function saveRoleRights(array $rolerights, $roleuuid) {
		// Alte Rechte löschen
		AvtPersistenceAdapter::executeNoResultQuery("delete from avtroleright where roleuuid='".AvtPersistenceAdapter::escape($roleuuid)."'");
		// Rolle zuweisen
		foreach ($rolerights as $roleright) {
			$roleright->roleuuid = $roleuuid;
			// Neues Recht speichern
			AvtPersistenceAdapter::save($roleright);
		}
	}
	
}

?>