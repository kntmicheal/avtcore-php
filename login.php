<?
require_once($GLOBALS['AvtUserPersistenceHandler']);

function validatelogin($username, $password) {
	$valid = true;
	if ($username === '') {
		shownotification('error', __('Bitte geben Sie einen Benutzernamen ein.'));
		$GLOBALS['loginusernameerror'] = true;
		$valid = false;
	}
	if ($password === '') {
		shownotification('error', __('Bitte geben Sie ein Passwort ein.'));
		$GLOBALS['loginpassworderror'] = true;
		$valid = false;
	}
	if ($valid) {
		// Jetzt erst authentifizieren
		$user = AvtUserPersistenceHandler::authenticateUser($username, $password);
		if ($user) {
			// Bei Erfolg Sitzungsvariablen speichern und letzte Login-Zeit aktualisieren
			refreshUserSession($user);
		} else {
			shownotification('error', __('Der Benutzername wurde nicht gefunden oder das Passwort stimmt nicht.'));
			$GLOBALS['loginusernameerror'] = true;
			$GLOBALS['loginpassworderror'] = true;
			$valid = false;
		}
	}
	return $valid;
}

function validateregister($username, $password, $password2, $email) {
	$valid = true;
	if ($username === '') {
		shownotification('error', __('Bitte geben Sie einen Benutzernamen ein.'));
		$GLOBALS['registerusernameerror'] = true;
		$valid = false;
	}
	if ($password === '') {
		shownotification('error', __('Bitte geben Sie ein Passwort ein.'));
		$GLOBALS['registerpassworderror'] = true;
		$valid = false;
	}
	if ($password2 === '') {
		shownotification('error', __('Bitte geben Sie das Passwort erneut ein.'));
		$GLOBALS['registerpassword2error'] = true;
		$valid = false;
	}
	if ($password !== $password2) {
		shownotification('error', __('Die eingegebenen Passw&ouml;rter stimmen nicht &uuml;berein.'));
		$GLOBALS['registerpassworderror'] = true;
		$GLOBALS['registerpassword2error'] = true;
		$valid = false;
	}
	if ($email !== '') {
		$existinguser = AvtUserPersistenceHandler::getUserByEmail($email);
		if ($existinguser) {
			shownotification('error', __('Die E-Mail - Adresse wird bereits verwendet. Bitte geben Sie eine andere ein.'));
			$GLOBALS['registeremailerror'] = true;
			$valid = false;
		}
	}
	if ($valid) {
		// Jetzt erst Benutzernamen auf Vorhandensein pruefen
		if (AvtUserPersistenceHandler::getUserByUsername($username)) {
			shownotification('error', __('Der Benutzername ist bereits vergeben. Bitte geben Sie einen anderen ein.'));
			$GLOBALS['registerusernameerror'] = true;
			$valid = false;
		} else {
			$user = new avorium_core_persistence_User(array(
				'username' => $username, 
				'password' => password_hash($password, PASSWORD_DEFAULT), 
				'email' => $email, 
				'roleuuid' => 'userrole'
			));
			// Bei Erfolg Sitzungsvariablen speichern und letzte Login-Zeit aktualisieren
			// Benutzer wird dabei automatisch gespeichert
			refreshUserSession($user);
		}
	}
	return $valid;
}

$pagetitle = __('Anmelden');

// Postback
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if ($_POST['type'] === 'login') {
		$username = $_POST['username'];
		$password = $_POST['password'];
		if (validatelogin($username, $password)) {
			header('Location: '.(isset($_GET['returnurl']) ? urldecode($_GET['returnurl']) : 'index.php'));
			exit;
		}
	} else {
		$username = $_POST['registerusername'];
		$password = $_POST['registerpassword'];
		$password2 = $_POST['registerpassword2'];
		$email = $_POST['registeremail'];
		if (validateregister($username, $password, $password2, $email)) {
			header('Location: '.(isset($_GET['returnurl']) ? urldecode($_GET['returnurl']) : 'index.php'));
			exit;
		}
	}
}

// Nachdem Passwort-E-Mail geschickt wurde, soll der Benutzer nach der Anmeldung gleich auf sein Konto umgeleitet werden.
if (isset($_GET['gotgeneratedpassword'])) {
	shownotification('info', __('Bitte geben Sie das Passwort ein, dass Ihnen zugesandt wurde und &auml;ndern Sie dieses sofort nach der Anmeldung.'));
	header('Location: ?returnurl=Kontoverwaltung.php%3Fgotgeneratedpassword=true');
	exit;
}

include('header.inc.php');
?>
<table class="login" border="0" cellpadding="0" cellspacing="0">
	<tr class="top">
		<td>
			<form class="loginform" method="post">
				<input type="hidden" name="type" value="login" />
				<h1><?= __('Anmelden') ?></h1>
				<p><?= __('Bitte geben Sie Ihre Anmeldedaten ein.') ?></p>
				<label for="username"><?= __('Benutzername *') ?></label>
				<input name="username" <?= isset($GLOBALS['loginusernameerror']) ? ' class="error"' : '' ?>type="text" value="<?= isset($_POST['username']) ? $_POST['username'] : '' ?>" />
				<label for="password"><?= __('Passwort *') ?></label>
				<input name="password" <?= isset($GLOBALS['loginpassworderror']) ? ' class="error"' : '' ?> type="password" value="<?= isset($_POST['password']) ? $_POST['password'] : '' ?>" />
				<button type="submit"><?= __('Anmelden') ?></button>
				<p><a href="forgotpassword.php"><?= __('Ich habe mein Passwort vergessen.') ?></a></p>
			</form>
		</td><td>
			<form class="loginform" method="post">
				<input type="hidden" name="type" value="register" />
				<h1><?= __('Registrieren') ?></h1>
				<p><?= __('Sie haben noch kein Konto?') ?></p>
				<label for="registerusername"><?= __('Benutzername *') ?></label>
				<input name="registerusername" <?= isset($GLOBALS['registerusernameerror']) ? ' class="error"' : '' ?> type="text" value="<?= isset($_POST['registerusername']) ? $_POST['registerusername'] : '' ?>" />
				<label for="registerpassword"><?= __('Passwort *') ?></label>
				<input name="registerpassword" <?= isset($GLOBALS['registerpassworderror']) ? ' class="error"' : '' ?> type="password" value="<?= isset($_POST['registerpassword']) ? $_POST['registerpassword'] : '' ?>" />
				<label for="registerpassword2"><?= __('Passwort wiederholen *') ?></label>
				<input name="registerpassword2" <?= isset($GLOBALS['registerpassword2error']) ? ' class="error"' : '' ?> type="password" value="<?= isset($_POST['registerpassword2']) ? $_POST['registerpassword2'] : '' ?>" />
				<label for="registeremail"><?= __('E-Mail - Adresse') ?></label>
				<input name="registeremail" <?= isset($GLOBALS['registeremailerror']) ? ' class="error"' : '' ?> type="email" value="<?= isset($_POST['registeremail']) ? $_POST['registeremail'] : '' ?>" />
				<button type="submit"><?= __('Registrieren') ?></button>
			</form>
		</td>
	</tr>
</table>
<p class="hint center"><?= __('* Pflichtfelder') ?></p>
<? include('footer.inc.php') ?>