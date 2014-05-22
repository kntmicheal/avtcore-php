<?

/// <summary>
/// Erzeugt alle Menüs
/// </summary>
class avorium_core_ui_MainMenu {

	private static function renderSubMenu(avorium_core_ui_MenuItem $menuItem, $level)
	{
		$currentLevelHtml = null;
		$selectedSubMenuItem = null;
		foreach ($menuItem->items as $subMenuItem) {
			if ($subMenuItem->visible) {
				$cls = $subMenuItem->selected ? " class=\"selected\"" : "";
				$currentLevelHtml .= "<a".$cls." href=\"".$GLOBALS["base_url"].$subMenuItem->page."\">".__($subMenuItem->title)."</a>";
			}
			if ($subMenuItem->selected) {
				$selectedSubMenuItem = $subMenuItem;
			}
		}
		if ($selectedSubMenuItem == null) return null;
		$subLevelHtml = static::renderSubMenu($selectedSubMenuItem, $level + 1);
		return $currentLevelHtml !== null ? "<div class=\"avt_menu avt_menu_".$level."\">".$currentLevelHtml."</div>".$subLevelHtml : null;
	}

	// Rendert das Menü und gibt es als HTML-Struktur zurück
	public static function render() {
		$rootMenuItem = avorium_core_ui_MenuFactory::getFilteredRootMenuItemForRole(isset($_SESSION["roleuuid"]) ? $_SESSION["roleuuid"] : "guestrole", strrpos($_SERVER["REQUEST_URI"], "?") ? substr($_SERVER["REQUEST_URI"], 0, strrpos($_SERVER["REQUEST_URI"], "?")) : $_SERVER["REQUEST_URI"]);
		return static::renderSubMenu($rootMenuItem, 0);
	}
	
}

?>