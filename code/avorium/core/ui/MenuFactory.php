<?php

/* 
 * The MIT License
 *
 * Copyright 2014 Ronny Hildebrandt <ronny.hildebrandt@avorium.de>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

require_once dirname(__FILE__).'/../persistence/handler/RolePersistenceHandler.php';
require_once dirname(__FILE__).'/../persistence/handler/UserPersistenceHandler.php';
require_once dirname(__FILE__).'/MenuItem.php';

/**
 * Central management factory for the application menu structure.
 * In the config.php all menu items and their page URLs are registered by using
 * this factory. Used by main menu rendering and by role rights handling.
 */
class avorium_core_ui_MenuFactory {

    private static $rootMenuItem = null;

    /**
     * Returns the root menu item and instanciates it on demand. Used in this
     * class and by role persistence handler.
     * 
     * @return type avorium_core_ui_MenuItem Root menu item
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
    /**
     * Returns a filtered menu structure for the given role depending on their
     * read rights to the pages of the menu structure. Selects the first 
     * available page url in the path when the given page url is not available.
     * 
     * @param string $roleuuid UUID of the role
     * @param string $pageurltoselect URL of the page to select
     * @return avorium_core_ui_MenuItem Root menu item containing the menu structure for the given role
     * @throws Exception When no role for the given UUID can be found
     */
    public static function getFilteredRootMenuItemForRole($roleuuid, $pageurltoselect)
    {
        $role = AvtRolePersistenceHandler::getRole($roleuuid);
        if (!is_a($role, 'avorium_core_persistence_Role')) {
            throw new Exception('The given role does not exist!');
        }
        $roleRights = AvtRolePersistenceHandler::getRoleRightsByRoleUuid($roleuuid);
        $roleRootMenuItem = new avorium_core_ui_MenuItem();
        $rootMenuItem = static::getRootMenuItem();
        foreach ($rootMenuItem->items as $menuItem) {
            $filteredMenuItem = static::getFilteredMenuItems($menuItem, $roleRights, $pageurltoselect, (bool)$role->issysadmin);
            if ($filteredMenuItem != null) {
                $roleRootMenuItem->addMenuItem($filteredMenuItem);
            }
        }
        return $roleRootMenuItem;
    }

    /**
     * Recursive function to obtain a menu structure for a su  menu item
     * filtered by the given role rights.
     * 
     * @param avorium_core_ui_MenuItem $menuItem Input menu item to filter
     * @param array $roleRights Array of rights used for filtering the input menu item
     * @param type $pageurl URL of tha page which needs to be selected
     * @param type $isAdmin Defines whether the role is admin and has all rights. Simplifies the filtering-
     * @return avorium_core_ui_MenuItem Returns the filtered menu item matching the role rights or null, when the menu itam cannot be accessed by the role.
     */
    private static function getFilteredMenuItems(avorium_core_ui_MenuItem $menuItem, array $roleRights, $pageurl, $isAdmin)
    {
        $matchingRoleRight = null;
        if (!$isAdmin) { // Check rights only when role is not admin
            foreach ($roleRights as $roleRight) {
                if ($roleRight->canread && $menuItem->page ===  $roleRight->url) {
                    $matchingRoleRight = $roleRight;
                    break;
                }
            }
        }
        $filteredMenuItem = new avorium_core_ui_MenuItem(array(
            "page" => $menuItem->page, "title" => $menuItem->title, "visible" => $menuItem->visible,
            "selected" => $GLOBALS['base_url'].$menuItem->page === $pageurl // Select page when URL matches
        ));
        // Recursion
        foreach ($menuItem->items as $subMmenuItem) {
            $filteredSubMenuItem = static::getFilteredMenuItems($subMmenuItem, $roleRights, $pageurl, $isAdmin);
            if ($filteredSubMenuItem != null) {
                $filteredMenuItem->addMenuItem($filteredSubMenuItem);
                $filteredMenuItem->selected |= $filteredSubMenuItem->selected;
            }
        }
        if ($isAdmin || $matchingRoleRight != null) {
            // The role can access the menu item, return it
            return $filteredMenuItem;
        }
        if (count($filteredMenuItem->items) < 1) {
            // This case happens when no direct access to the menu item is allowed an no accessible sub menu items exists
            return null;
        }
        // An accessible sub menu item exists, so redirect the page for the current menu item to the sub menu item
        $filteredMenuItem->page = $filteredMenuItem->items[0]->page;
        return $filteredMenuItem;
    }

    /**
     * Adds a child menu item to a parent menu item and sorst the child menu
     * items depending on their priorities.
     * 
     * @param type $parentMenuItem Parent menu item to add the child to
     * @param type $childMenuItem Child menu item
     */
    private static function insertMenuItem($parentMenuItem, $childMenuItem) {
        // Remember parent for child
        $childMenuItem->parentMenuItem = $parentMenuItem;
        // Append child to parent
        $parentMenuItem->items[] = $childMenuItem;
        // Sort child by their priorities
        usort($parentMenuItem->items, function($a, $b) {
            return $a->priority > $b->priority;
        });
    }

    /**
     * Recursively analyzes the given parent menu item for a sub menu item with
     * a given ID and returns it. Can be null, when no menu item with
     * the given ID was found in the structure.
     * 
     * @param string $id ID of the menu item to search for
     * @param avorium_core_ui_MenuItem $parentMenuItem Menu item which provides the structure to analyze
     * @return avorium_core_ui_MenuItem Menu item with the given ID or null.
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
    /**
     * Appends a menu item which is described by the array to the parent menu
     * item with the given ID, e.g.:
     * avorium_core_ui_MenuFactory::addMenuItem(array('id' => 'Administration/Benutzer', 'title' => 'Benutzer', 'page' => '/Administration/Benutzerliste.php'), 'Administration');
     * 
     * @param array $menuItemArray Array describing the menu item to add
     * @param string $parentId ID of the parent menu item to add the new one to
     * @throws Exception When no parent menu item with the given ID can be found.
     */
    public static function addMenuItem(array $menuItemArray, $parentId = null)
    {
        $menuItem = new avorium_core_ui_MenuItem($menuItemArray);
        // Priorität prüfen und ggf setzen
        if ($menuItem->priority === null) {
            $menuItem->priority = 100;
        }
        $rootMenuItem = static::getRootMenuItem();
        $parentMenuItem = $parentId === null ? $rootMenuItem : static::findMenuItemWithId($parentId, $rootMenuItem);
        if ($parentMenuItem === null) {
            throw new Exception('Menu item '.$menuItem->page.' could not be appended to the parent '.$parentId.' because the parent does not exist.');
        }
        static::insertMenuItem($parentMenuItem, $menuItem);
    }
}