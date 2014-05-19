<?
include('authenticateheader.inc.php');
require_once($GLOBALS['AvtRolePersistenceHandler']);
require_once($GLOBALS['AvtUserPersistenceHandler']);

// Validiert die Formulareingaben und liefert true, wenn alles stimmt
function validate() {
	$valid = true;
	// Validierung
	$uuid = filter_input(INPUT_POST, 'uuid');
	$username = filter_input(INPUT_POST, 'username');
	if (strlen($username) < 1) {
		shownotification('error', __('Bitte geben Sie einen Benutzernamen ein.'));
		$GLOBALS['userusernameerror'] = true;
		$valid = false;
	}
	$email = filter_input(INPUT_POST, 'email');
	if (strlen($email) < 1) {
		$existinguser = AvtUserPersistenceHandler::getUserByEmail($email);
		if ($existinguser && $existinguser->uuid !== $uuid) {
			shownotification('error', __('Die E-Mail - Adresse wird bereits verwendet. Bitte geben Sie eine andere ein.'));
			$GLOBALS['useremailerror'] = true;
			$valid = false;
		}
	}
	return $valid;
}

$columns = array(
	'username' => array('title' => 'Benutzername', 'urlformat' => 'Benutzerliste.php?uuid={0}', 'urlfield' => 'uuid'),
	'email' => array('title' => 'E-Mail - Adresse'),
	'role' => array('title' => 'Rolle', 'urlformat' => 'Rollenliste.php?uuid={0}', 'urlfield' => 'roleuuid', 'accessurl' => '/Administration/Rollenliste.php'),
	'lastlogin' => array('title' => 'Letze Anmeldung', 'type' => 'datetime', 'align' => 'right')
);
$listid = 'benutzerliste'; // ID der Tabelle, wird auch als ID für Sortierung in Sitzung verwendet
if (!isset($_SESSION[$listid.'_sort'])) {
	$_SESSION[$listid.'_sort'] = 'username'; // Initialie Sortierung
}

// Postbacks behandeln
if (filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'POST') {
	$uuid = filter_input(INPUT_POST, 'uuid');
	// Löschen?
	if (filter_input(INPUT_POST, 'deletebutton') !== null) {
		AvtUserPersistenceHandler::deleteUser(AvtUserPersistenceHandler::getUser($uuid));
		$user = null; // Details werden hiermit ausgeblendet
		shownotification('success', __('Der Benutzer wurde gel&ouml;scht.'));
	} else {
		$user = AvtUserPersistenceHandler::getUser($uuid) ?: new avorium_core_persistence_User(array('password' => '', 'lastlogin' => 0)); // Neu behandeln
		$user->username = filter_input(INPUT_POST, 'username');
		$user->email = filter_input(INPUT_POST, 'email');
		$user->roleuuid = filter_input(INPUT_POST, 'roleuuid');
		// Passwort zusenden ?
		if (filter_input(INPUT_POST, 'newpasswordbutton') !== null) {
			if (!AvtUserPersistenceHandler::sendNewPasswordToUser($uuid)) {
				shownotification('error', __('Es konnte kein Passwort versendet werden. Stimmt die eingegebene E-Mail - Adresse?'));
				$GLOBALS['useremailerror'] = true;
			} else {
				shownotification('success', __('Es wurde ein neues Passwort versandt.'));
			}
		} else {
			if (validate()) {
				AvtUserPersistenceHandler::saveUser($user);
				shownotification('success', __('Der Benutzer wurde gespeichert.'));
				header('Location: Benutzerliste.php?uuid='.$user->uuid);
				exit;
			}
		}
	}
} else {
	// Wenn uuid übergeben wurde, Benutzer laden bzw. neu anlegen
	$uuid = filter_input(INPUT_GET, 'uuid');
	$user = strlen($uuid) > 0 ? AvtUserPersistenceHandler::getUser($uuid) : new avorium_core_persistence_User();
}

// Listen laden
$persistentobjects  = AvtUserPersistenceHandler::getUsersForAdministrationList();
$roles = AvtRolePersistenceHandler::getAllRoles(); // Rollen für Selectbox
usort($roles, function($a, $b) {
	return strcmp($a->name, $b->name);
});


include('header.inc.php');
?>
<div class="buttonarea">
<? if(AvtRolePersistenceHandler::canCurrentRoleWriteCurrentPage()) { ?>
	<a href="?uuid="><?= __('Neuer Benutzer') ?></a>
<? } ?>
</div>
<div class="list userlist"><?= avorium_core_ui_DataTable::render($persistentobjects, $columns, $listid) ?></div>
<? if (isset($user)) { ?>
<form class="details" method="post">
	<input type="hidden" name="uuid" value="<?= $user->uuid ?>" />
	<h1><?= __('Allgemeine Angaben') ?></h1>
	<table border="0" cellpadding="0" cellspacing="0" class="paragraph paragraph-2-col">
		<tbody>
			<tr>
				<td><label for="username"><?= __('Benutzername *') ?></label></td>
				<td><input type="text" name="username" value="<?= $user->username ?>" <?= isset($GLOBALS['userusernameerror']) ? ' class="error"' : '' ?> /></td>
			</tr>
			<? if(AvtRolePersistenceHandler::canCurrentRoleWriteCurrentPage()) { ?>
			<tr>
				<td><label><?= __('Passwort') ?></label></td>
				<td><button name="newpasswordbutton" type="submit"><?= __('Neues Passwort zusenden') ?></button></td>
			</tr>
			<? } ?>
			<tr>
				<td><label for="email"><?= __('E-Mail - Adresse') ?></label></td>
				<td><input type="text" name="email" value="<?= $user->email ?>" <?= isset($GLOBALS['useremailerror']) ? ' class="error"' : '' ?> /></td>
			</tr>
			<tr>
				<td><label for="roleuuid"><?= __('Rolle') ?></label></td>
				<td>
					<select name="roleuuid">
					<? foreach ($roles as $role) { ?>
						<option value="<?= $role->uuid ?>"<?= $user->roleuuid === $role->uuid ? ' selected="selected"' : '' ?>><?= $role->name ?></option>
					<? } ?>
					</select>
				</td>
			</tr>
		</tbody>
	</table>
	<div class="buttonarea">
	<? if(AvtRolePersistenceHandler::canCurrentRoleWriteCurrentPage()) { ?>
		<button name="savebutton" type="submit"><?= __('Speichern') ?></button>
		<? if (strlen(filter_input(INPUT_GET, 'uuid')) > 0) { ?>
		<button name="deletebutton" type="submit" onclick="return confirm('<?= __('Wollen Sie den Benutzer wirklich l&ouml;schen?') ?>')"><?= __('L&ouml;schen') ?></button>
		<? } ?>
	<? } ?>
	</div>
</form>
<? } ?>
<? include('footer.inc.php') ?>
