<?
require_once($GLOBALS['AvtPersistenceAdapter']);

// Standard-Handler für Benutzer. Um eine Authentifizierung gegen ein
// Fremdsystem zu machen, muss diese Klasse abgeleitet werden und in der 
// config.php die Variable $GLOBALS['AvtUserPersistenceHandler'] auf die abgeleitete
// Klasse zeigen. Die abgeleitete Klasse muss dann in ihren Funktionen
// mit dem Fremdsystem kommunizieren
class AvtUserPersistenceHandler extends avorium_core_persistence_handler_AbstractPersistenceHandler {
	
	// Speichert den gegebenen Benutzer oder legt diesen an.
	public static function saveUser(avorium_core_persistence_User $user) {
		AvtPersistenceAdapter::save($user);
		// Events verschicken, Fahrtenbuch z.B. legt für neue Benutzer ein Profil an und setzt die Trial-Time
		static::sendEvent('saveUser', $user);
	}
	
	/**
	 * Löscht den übergebenen Benutzer.
	 * Verschickt das Event "deleteUser" mit dem Benutzer als Argument
	 */
	public static function deleteUser(avorium_core_persistence_User $user) {
		AvtPersistenceAdapter::delete($user);
		// Events verschicken, falls sich irgendwer dafür interessiert, z.B. das Fahrtenbuch, um Profile zu löschen
		static::sendEvent('deleteUser', $user);
	}
	
	// Liefert den Benutzer mit der gegebenen UUID oder false, falls keiner existiert
	public static function getUser($uuid) {
		return AvtPersistenceAdapter::get('avorium_core_persistence_User', $uuid);
	}
	
	// Liefert den Benutzer mit der gegebenen E-Mail - Adresse oder false, falls keiner existiert
	public static function getUserByEmail($email) {
		return AvtPersistenceAdapter::executeSingleResultQuery('select * from avtuser where email=\''.AvtPersistenceAdapter::escape($email).'\'', 'avorium_core_persistence_User');
	}
	
	// Liefert alle Benutzer oder ein leeres Feld
	public static function getAllUsers() {
		return AvtPersistenceAdapter::getAll('avorium_core_persistence_User');
	}

	// Liefert einen Benutzer anhand seines Benutzernamens oder false.
	public static function getUserByUsername($username) {
		return AvtPersistenceAdapter::executeSingleResultQuery('select * from avtuser where username=\''.AvtPersistenceAdapter::escape($username).'\'', 'avorium_core_persistence_User');
	}
	
	// Prüft, ob die Benutzername/Passwort-Kombination stimmt und gibt im Erfolgsfall den Benutzer und ansonsten false zurück
	public static function authenticateUser($username, $password) {
		$user = static::getUserByUsername($username);
		return ($user && password_verify($password, $user->password)) ? $user : false;
	}
	
	// Liefert alle Benutzer als Tabelle für die Administration
	// Enthält die Spalte 'role', welche den Namnen der Benutzerrolle darstellt (JOIN)
	public static function getUsersForAdministrationList() {
		return AvtPersistenceAdapter::executeMultipleResultQuery('select avtuser.uuid, avtuser.username, avtuser.email, avtuser.roleuuid, avtuser.lastlogin, avtrole.name role from avtuser join avtrole on avtrole.uuid = avtuser.roleuuid');
	}

	// Siehe http://stackoverflow.com/questions/6101956/generating-a-random-password-in-php/6101969#6101969
	private static function randomPassword() {
		$alphabet = 'abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789';
		$pass = array(); //remember to declare $pass as an array
		$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
		for ($i = 0; $i < 8; $i++) {
			$n = rand(0, $alphaLength);
			$pass[] = $alphabet[$n];
		}
		return implode($pass); //turn the array into a string
	}
	
	// Generiert ein neues Passwort für den Benutzer mit der uuid und schickt es ihm per E-Mail.
	// Liefert false, wenn Versand fehl schlug oder Benutzer nicht existiert oder E-Mail - Adresse falsch ist.
	public static function sendNewPasswordToUser($uuid) {
		$user = static::getUser($uuid);
		if ($user === null) {
			return false;
		}
		$subject = __('Avorium Fahrtenbuch - Passwort vergessen');
		$password = static::randomPassword();
		$bodytemplate = __('Hallo {0},').'\n\n'.
						__('F&uuml;r Sie wurde ein neues Passwort generiert:').'\n\n{1}\n\n'.
						__('Bitte gehen Sie zu folgender Adresse, melden sich mit Ihrem Benutzernamen und oben stehendem Passwort an, und &auml;ndern das Passwort sofort darauf.').'\n\nhttps://fahrtenbuch.avorium.de/login.php?gotgeneratedpassword=true\n\n'.
						__('Ihr Avorium Fahrtenbuch Team\nhttps://fahrtenbuch.avorium.de');
		$body = str_replace('{1}', $password, str_replace('{0}', $user->username, $bodytemplate));
		$headers = array(
			'MIME-Version: 1.0',
			'Content-type: text/plain; charset=UTF-8',
			'From: noreply@avorium.de',
			'Subject: {'.$subject.'}',
			'X-Mailer: PHP/'.phpversion()
		);
		if (mail($user->email, $subject, $body, implode('\r\n', $headers))) {
			// Erst nach erfolgreichem Mailversand Passwort speichern
			$user->password = password_hash($password, PASSWORD_DEFAULT);
			static::saveUser($user);
			return true;
		}
		return false;
	}
	
	// Sendet ein neues Passwort an den Benutzer mit der angegebenen E-Mail - Adresse.
	// Dabei darf es nur einen einzigen Benutzer  mit der E-Mail - Adresse geben.
	// Ansonsten
	public static function sendNewPasswordToUserEmail($email) {
		$user = static::getUserByEmail($email);
		if ($user) {
			static::sendNewPasswordToUser($user->uuid);
		}
	}

}
