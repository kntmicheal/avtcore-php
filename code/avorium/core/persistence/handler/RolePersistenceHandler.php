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

require_once dirname(__FILE__).'/AbstractPersistenceHandler.php';
require_once dirname(__FILE__).'/../AbstractPersistenceAdapter.php';
require_once dirname(__FILE__).'/../RoleRight.php';
require_once dirname(__FILE__).'/../../ui/MenuFactory.php';
require_once dirname(__FILE__).'/../../ui/MenuItem.php';

/**
 * Default handler for roles.
 */
class avorium_core_persistence_handler_RolePersistenceHandler
extends avorium_core_persistence_handler_AbstractPersistenceHandler{
	
    /**
     * Stores the given role in the database.
     * 
     * @param avorium_core_persistence_Role $role Role to save
     */
    public static function saveRole(avorium_core_persistence_Role $role) {
        static::getPersistenceAdapter()->save($role);
    }

    /**
     * Deletes the given role from the database when it exists.
     * 
     * @param avorium_core_persistence_Role $role Role to delete
     */
    public static function deleteRole(avorium_core_persistence_Role $role) {
        static::getPersistenceAdapter()->delete($role);
    }

    /**
     * Returns the role with the given uuid or null whon no such role exists.
     * 
     * @param string $uuid Unique identifier of the role to get
     * @return avorium_core_persistence_Role Role or null
     */
    public static function getRole($uuid) {
        return static::getPersistenceAdapter()->get('avorium_core_persistence_Role', $uuid);
    }

    /**
     * Retreive a list of all axisting roles. Can be empty when no role exists.
     * 
     * @return array Array of existing roles.
     */
    public static function getAllRoles() {
        return static::getPersistenceAdapter()->getAll('avorium_core_persistence_Role');
    }

    /**
     * Retrieve all role rights for a role with the given uuid. Result can be
     * an empty array when not rights were found or when there is no role with
     * the given uuid.
     * 
     * @param string $roleuuid Unique identifier of the role to get the rights for.
     * @return array Array of rights.
     */
    public static function getRoleRightsByRoleUuid($roleuuid) {
        $persistenceAdapter = static::getPersistenceAdapter();
        return $persistenceAdapter->executeMultipleResultQuery("select * from avtroleright where roleuuid='".$persistenceAdapter->escape($roleuuid)."'", 'avorium_core_persistence_RoleRight');
    }

    /**
     * Checks whether the role with the given UUID can read the given page url
     * or not. SysAdmins can read pages in every case independent on the rights.
     * 
     * @param string $roleuuid UUID of the role to check a page for
     * @param string $pageurl URL of the page to check
     * @return boolean True when the role can read the page, false otherwise.
     */
    public static function canRoleReadPage($roleuuid, $pageurl) {
        $role = static::getRole($roleuuid);
        if (!is_a($role, 'avorium_core_persistence_Role')) {
            return false;
        }
        if ($role->issysadmin) {
            return true;
        }
        $rolerights = static::getRoleRightsByRoleUuid($roleuuid);
        foreach ($rolerights as $roleright) {
            if ($roleright->url === $pageurl && $roleright->canread) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks whether the role with the given UUID can write the given page url
     * or not. SysAdmins can write pages in every case independent on the 
     * rights. Used for making save or delete buttons visible on pages.
     * 
     * @param string $roleuuid UUID of the role to check a page for
     * @param string $pageurl URL of the page to check
     * @return boolean True when the role can write the page, false otherwise.
     */
    public static function canRoleWritePage($roleuuid, $pageurl) {
        $role = static::getRole($roleuuid);
        if (!is_a($role, 'avorium_core_persistence_RoleRight')) {
            return false;
        }
        if ($role->issysadmin) {
            return true;
        }
        $rolerights = static::getRoleRightsByRoleUuid($roleuuid);
        foreach ($rolerights as $roleright) {
            if ($roleright->url === $pageurl && $roleright->canwrite) {
                return true;
            }
        }
        return false;
    }

    // Liefert alle Rollenrechte für alle Seiten einer bestimmten Rolle,
    // auch wenn in der Datenbank keine Rechte gespeichert sind. Wird
    // Für Rollenadministration verwendet.
    /**
     * Returns all role rights for all pages for a specific role, even when
     * there are no rights stored for the role in the database or even when
     * there is no role with the given uuid in the database (in this case the
     * result contains rights where each page has no read or write permission). 
     * Used for role rights administration.
     * 
     * @param string $roleuuid UUID of the role to obtain all rights for.
     * @return array Array of role rights for all pages for the given role
     */
    public static function getCompleteRoleRightsByRoleUuid($roleuuid) {
        $persistenceAdapter = static::getPersistenceAdapter();
        $existingRights = $persistenceAdapter->executeMultipleResultQuery("select * from avtroleright where roleuuid='".$persistenceAdapter->escape($roleuuid)."'", 'avorium_core_persistence_RoleRight');
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

    /**
     * Obtains a flat list of page URLs of the given menu item and all of its
     * sub menu items.
     * 
     * @param avorium_core_ui_MenuItem $menuItem Menu item to get a flat list of
     * page URLs for
     * @return array Array of page URLs. Can be empty.
     */
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
    /**
     * Saves the given role rights for the given role and deletes all existing
     * role rights  for the role before.
     * 
     * @param array $rolerights Role rights to save.
     * @param string $roleuuid UUID of the role to store the rights for.
     */
    public static function saveRoleRights(array $rolerights, $roleuuid) {
        if (is_null($roleuuid)) {
            return;
        }
        $persistenceAdapter = static::getPersistenceAdapter();
        // Delete old rights
        $persistenceAdapter->executeNoResultQuery("delete from avtroleright where roleuuid='".$persistenceAdapter->escape($roleuuid)."'");
        // Assing role to rights
        foreach ($rolerights as $roleright) {
            $roleright->roleuuid = $roleuuid;
            // Store new rights
            $persistenceAdapter->save($roleright);
        }
    }
	
}
