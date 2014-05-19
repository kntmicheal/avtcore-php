<?
include('authenticateheader.inc.php');
require_once($GLOBALS['AvtUserPersistenceHandler']);

// Validiert die Formulareingaben und liefert true, wenn alles stimmt
function validate() {
	$valid = true;
	// Validierung
	if (strlen(filter_input(INPUT_POST, 'username')) < 1) {
		shownotification('error', __('Bitte geben Sie einen Benutzernamen ein.'));
		$GLOBALS['accountusernameerror'] = true;
		$valid = false;
	}
	$password = filter_input(INPUT_POST, 'password');
	$password2 = filter_input(INPUT_POST, 'password2');
	if (strlen($password) > 0 && $password !== $password2) {
		shownotification('error', __('Die Passw&ouml;rter stimmen nicht &uuml;berein.'));
		$GLOBALS['accountpassworderror'] = true;
		$GLOBALS['accountpassword2error'] = true;
		$valid = false;
	}
	$email = filter_input(INPUT_POST, 'email');
	if (strlen($email) > 0) {
		$existinguser = AvtUserPersistenceHandler::getUserByEmail($email);
		if ($existinguser && $existinguser->uuid !== $_SESSION['useruuid']) {
			shownotification('error', __('Die E-Mail - Adresse wird bereits verwendet. Bitte geben Sie eine andere ein.'));
			$GLOBALS['accountemailerror'] = true;
			$valid = false;
		}
	}
	return $valid;
}

$user = AvtUserPersistenceHandler::getUser($_SESSION['useruuid']);

// Postbacks behandeln
if (filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'POST') {
	// Löschen?
	if (filter_input(INPUT_POST, 'deletebutton') !== null) {
		AvtUserPersistenceHandler::deleteUser($user);
		header('Location: '.$GLOBALS['base_url'].'/logout.php');
		exit;
	} else {
		$user->username = filter_input(INPUT_POST, 'username');
		$password = filter_input(INPUT_POST, 'password');
		if (strlen($password) > 0) {
			$user->password = password_hash($password, PASSWORD_DEFAULT);
		}
		$user->email = filter_input(INPUT_POST, 'email');
		if (validate()) {
			AvtUserPersistenceHandler::saveUser($user);
			shownotification('success', __('Ihre Daten wurden gespeichert.'));
			header('Location: Allgemein.php?uuid='.$user->uuid);
			exit;
		}
	}
}

// Nachdem man sich nach der Zusendung eines neuen Passwortes angemeldet hat, wird man darauf hingewiesen, das Passwort umgehend zu ändern.
if (filter_input(INPUT_GET, 'gotgeneratedpassword') !== null) {
	shownotification('error', __('Bitte geben Sie nun ein neues Passwort ein.'));
	$GLOBALS['accountpassworderror'] = true;
	$GLOBALS['accountpassword2error'] = true;
}

include('header.inc.php');
?>
<form method='post'>
	<h1><?= __('Allgemeine Angaben') ?></h1>
	<table border='0' cellpadding='0' cellspacing='0' class='paragraph paragraph-2-col'>
		<tbody>
			<tr>
				<td><label for='username'><?= __('Benutzername *') ?></label></td>
				<td><input type='text' name='username' value='<?= $user->username ?>' <?= isset($GLOBALS['accountusernameerror']) ? ' class="error"' : '' ?>/></td>
			</tr>
			<? if(AvtRolePersistenceHandler::canCurrentRoleWriteCurrentPage()) { ?>
			<tr>
				<td><label><?= __('Neues Passwort') ?></label></td>
				<td><input type='password' name='password' <?= isset($GLOBALS['accountpassworderror']) ? ' class="error"' : '' ?> /></td>
			</tr>
			<tr>
				<td><label><?= __('Neues Passwort wiederholen') ?></label></td>
				<td><input type='password' name='password2' <?= isset($GLOBALS['accountpassword2error']) ? ' class="error"' : '' ?> /></td>
			</tr>
			<? } ?>
			<tr>
				<td><label for='email'><?= __('E-Mail - Adresse') ?></label></td>
				<td><input type='text' name='email' value='<?= $user->email ?>' <?= isset($GLOBALS['accountemailerror']) ? ' class="error"' : '' ?> /></td>
			</tr>
		</tbody>
	</table>
	<div class='buttonarea'>
	<? if(AvtRolePersistenceHandler::canCurrentRoleWriteCurrentPage()) { ?>
		<button name='savebutton' type='submit'><?= __('Speichern') ?></button>
		<button name='deletebutton' type='submit' onclick="return confirm('<?= __("Wollen Sie Ihr Konto wirklich l&ouml;schen? Alle damit verkn&uuml;pften Daten (inkl. aller Fahrten) werden dadurch ebenfalls gel&ouml;scht.") ?>')"><?= __('Konto l&ouml;schen') ?></button>
	<? } ?>
	</div>
</form>
<? include('footer.inc.php') ?>
