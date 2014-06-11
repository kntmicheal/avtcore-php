<?php

/// <summary>
/// Erzeugt eine Brotkrümelnavigation
/// </summary>
class avorium_core_ui_Breadcrumbs {

	// Rendert die Brotkrümelnavigation und gibt sie als HTML-Struktur zurück
	public static function render() {
		$rootMenuItem = avorium_core_ui_MenuFactory::getFilteredRootMenuItemForRole(isset($_SESSION["roleuuid"]) ? $_SESSION["roleuuid"] : "guestrole", strrpos($_SERVER["REQUEST_URI"], "?") ? substr($_SERVER["REQUEST_URI"], 0, strrpos($_SERVER["REQUEST_URI"], "?")) : $_SERVER["REQUEST_URI"]);
		$breadcrumbs = array();
		$menuItem = null;
		foreach ($rootMenuItem->items as $subMenuItem) {
			if ($subMenuItem->selected) {
				$menuItem = $subMenuItem;
				break;
			}
		}
		while ($menuItem !== null) {
			if ($menuItem->visible) {
				$breadcrumbs[] = "<a href=\"".$GLOBALS["base_url"].$menuItem->page."\">".__($menuItem->title)."</a>";
			}
			$foundSubMenuItem = null;
			foreach ($menuItem->items as $subMenuItem) {
				if ($subMenuItem->selected) {
					$foundSubMenuItem = $subMenuItem;
					break;
				}
			}
			$menuItem = $foundSubMenuItem;
		}
		return "<div class=\"avt_breadcrumbs\">".implode(" &gt; ", $breadcrumbs)."</div>";
	}

}
?>
