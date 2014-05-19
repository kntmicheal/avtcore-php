<?
include('authenticateheader.inc.php');
require_once($GLOBALS['AvtRolePersistenceHandler']);

// Validiert die Formulareingaben und liefert true, wenn alles stimmt
function validate() {
	$valid = true;
	// Validierung
	$name = filter_input(INPUT_POST, 'name');
	if (strlen($name) < 1) {
		shownotification('error', __('Bitte geben Sie einen Namen ein.'));
		$GLOBALS['rolenameerror'] = true;
		$valid = false;
	}
	return $valid;
}

$columns = array(
	'name' => array('title' => 'Name', 'urlformat' => 'Rollenliste.php?uuid={0}', 'urlfield' => 'uuid'),
	'issysadmin' => array('title' => 'Sys-Admin', 'type' => 'bool', 'align' => 'center')
);
$listid = 'rollenliste'; // ID der Tabelle, wird auch als ID für Sortierung in Sitzung verwendet
if (!isset($_SESSION[$listid.'_sort'])) {
	$_SESSION[$listid.'_sort'] = 'name'; // Initialie Sortierung
}

// Postbacks behandeln
if (filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'POST') {
	$uuid = filter_input(INPUT_POST, 'uuid');
	// Löschen?
	if (filter_input(INPUT_POST, 'deletebutton') !== null) {
		AvtRolePersistenceHandler::deleteRole(AvtRolePersistenceHandler::getRole($uuid));
		$role = null; // Details werden hiermit ausgeblendet
		shownotification('success', __('Die Rolle wurde gel&ouml;scht.'));
	} else {
		$role = AvtRolePersistenceHandler::getRole($uuid) ?: new avorium_core_persistence_Role(); // Neu behandeln
		$role->name = filter_input(INPUT_POST, 'name') ?: '';
		$role->issysadmin = filter_input(INPUT_POST, 'issysadmin') !== null;
		if (validate()) {
			AvtRolePersistenceHandler::saveRole($role);
			shownotification('success', __('Die Rolle wurde gespeichert.'));
		}
		// Berechtigungen
		$rolerights = array();
		$canread = filter_input(INPUT_POST, 'canread', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
		if ($canread != null) {
			foreach ($canread as $url => $value) {
				$rolerights[] = new avorium_core_persistence_RoleRight(array('url' => $url, 'canread' => true));
			}
		}
		$canwrite = filter_input(INPUT_POST, 'canwrite', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
		if ($canwrite != null) {
			foreach ($canwrite as $url => $value) {
				$rightfound = false;
				foreach ($rolerights as $roleright) {
					if ($roleright->url === $url) {
						$roleright->canwrite = true;
						$rightfound = true;
						break;
					}
				}
				if (!$rightfound) {
					$rolerights[] = new avorium_core_persistence_RoleRight(array('url' => $url, 'canwrite' => true));
				}
			}
		}
		AvtRolePersistenceHandler::saveRoleRights($rolerights, $role->uuid);
	}
} else {
	// Wenn uuid übergeben wurde, Rolle laden bzw. neu anlegen
	$uuid = filter_input(INPUT_GET, 'uuid');
	$role = strlen($uuid) > 0 ? AvtRolePersistenceHandler::getRole($uuid) : new avorium_core_persistence_Role();
}
// Liste laden
$persistentobjects  = AvtRolePersistenceHandler::getAllRoles();

include('header.inc.php');
?>
<div class="buttonarea">
<? if(AvtRolePersistenceHandler::canCurrentRoleWriteCurrentPage()) { ?>
	<a href="?uuid="><?= __('Neue Rolle') ?></a>
<? } ?>
</div>
<div class="list rolelist"><?= avorium_core_ui_DataTable::render($persistentobjects, $columns, $listid) ?></div>
<? if ($uuid !== null) { ?>
<form class="details" method="post">
	<input type="hidden" name="uuid" value="<?= $role->uuid ?>" />
	<h1><?= __('Allgemeine Angaben') ?></h1>
	<table border="0" cellpadding="0" cellspacing="0" class="paragraph paragraph-2-col">
		<tbody>
			<tr>
				<td><label for="name"><?= __('Rollenname *') ?></label></td>
				<td><input type="text" name="name" value="<?= $role->name ?>" <?= isset($GLOBALS['rolenameerror']) ? ' class="error"' : '' ?>/></td>
			</tr>
			<tr>
				<td><label for="issysadmin"><?= __('Systemadministrator') ?></label></td>
				<td><input type="checkbox" name="issysadmin"<?= $role->issysadmin ? ' checked="checked"' : '' ?> /></td>
			</tr>
		</tbody>
	</table>
	<h1><?= __('Zugriffsberechtigungen') ?></h1>
	<table border="0" cellpadding="0" cellspacing="0" class="list roleaccessrightslist">
		<thead>
			<tr>
				<th><?= __('Pfad') ?></th>
				<th><?= __('Lesen') ?></th>
				<th><?= __('Schreiben') ?></th>
			</tr>
		<tbody>
		<? foreach (AvtRolePersistenceHandler::getCompleteRoleRightsByRoleUuid($role->uuid) as $roleright) { ?>
			<tr>
				<td><?= $roleright->url ?></td>
				<td><input type="checkbox" name="canread[<?= $roleright->url ?>]"<?= $roleright->canread ? ' checked="checked"' : '' ?> /></td>
				<td><input type="checkbox" name="canwrite[<?= $roleright->url ?>]"<?= $roleright->canwrite ? ' checked="checked"' : '' ?> /></td>
			</tr>
		<? } ?>
		</tbody>
	</table>
	<div class="buttonarea">
	<? if(AvtRolePersistenceHandler::canCurrentRoleWriteCurrentPage()) { ?>
		<button name="savebutton" type="submit"><?= __('Speichern') ?></button>
		<? if (strlen(filter_input(INPUT_GET, 'uuid')) > 0) { ?>
		<button name="deletebutton" type="submit" onclick="return confirm('<?= __('Wollen Sie die Rolle wirklich l&ouml;schen?') ?>')"><?= __('L&ouml;schen') ?></button>
		<? } ?>
	<? } ?>
	</div>
</form>
<? } ?>
<? include('footer.inc.php') ?>
