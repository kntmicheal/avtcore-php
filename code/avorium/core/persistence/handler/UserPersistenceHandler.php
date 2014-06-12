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

/**
 * Default handler for users.
 */
class avorium_core_persistence_handler_UserPersistenceHandler
extends avorium_core_persistence_handler_AbstractPersistenceHandler {
	
    /**
     * Stores the given user in the database and send the "saveUser" event with
     * the user as parameter.
     * 
     * @param avorium_core_persistence_User $user User to save
     */
    public static function saveUser(avorium_core_persistence_User $user) {
        static::getPersistenceAdapter()->save($user);
        // Send events about saving the user. E.g. The Fahrtenbuch creates
        // a profile after creating the user and sets its trial time.
        static::sendEvent('saveUser', $user);
    }

    /**
     * Deletes the given user from the database and send the "deleteUser" event 
     * with the deleted user as parameter.
     * 
     * @param avorium_core_persistence_User $user User to delete
     */
    public static function deleteUser(avorium_core_persistence_User $user) {
        static::getPersistenceAdapter()->delete($user);
        // Send event. The Fahrtenbuch deletes the corresponding profile
        // of the deleted user.
        static::sendEvent('deleteUser', $user);
    }

    /**
     * Returns the user with the given UUID or false, when there is no user with
     * that UUID.
     * 
     * @param string $uuid UUID of the requested user.
     * @return avorium_core_persistence_User User or null
     */
    public static function getUser($uuid) {
        return static::getPersistenceAdapter()->get('avorium_core_persistence_User', $uuid);
    }

    /**
     * Returns the first user in the database which has the given email address
     * or null, when no user has this email address. When there are more than
     * one user with that email address, the first one of the result set is
     * returned. The order of the result depends on the database implementation
     * but normally you get the user which was inserted into the database as first.
     * 
     * @param string $email Email address to search a user for.
     * @return avorium_core_persistence_User First user in database with the given email address.
     */
    public static function getFirstUserByEmail($email) {
        $persistenceAdapter = static::getPersistenceAdapter();
        return $persistenceAdapter->executeSingleResultQuery('select * from avtuser where email=\''.$persistenceAdapter->escape($email).'\'', 'avorium_core_persistence_User');
    }

    /**
     * Returns all users in the database. Used for user administration.
     * 
     * @return array Array of users. Can be empty when no users exist.
     */
    public static function getAllUsers() {
        return static::getPersistenceAdapter()->getAll('avorium_core_persistence_User');
    }

    /**
     * Returns the first user in the database which has the given username
     * or null, when no user has this username. When there are more than
     * one user with that username, the first one of the result set is
     * returned. The order of the result depends on the database implementation
     * but normally you get the user which was inserted into the database as first.
     * 
     * @param string $username Username to search a user for.
     * @return avorium_core_persistence_User First user in database with the given username.
     */
    public static function getFirstUserByUsername($username) {
        $persistenceAdapter = static::getPersistenceAdapter();
        return $persistenceAdapter->executeSingleResultQuery('select * from avtuser where username=\''.$persistenceAdapter->escape($username).'\'', 'avorium_core_persistence_User');
    }

    // Prüft, ob die Benutzername/Passwort-Kombination stimmt und gibt im Erfolgsfall den Benutzer und ansonsten false zurück
    public static function authenticateUser($username, $password) {
            $user = static::getFirstUserByUsername($username);
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
            $user = static::getFirstUserByEmail($email);
            if ($user) {
                    static::sendNewPasswordToUser($user->uuid);
            }
    }

}
