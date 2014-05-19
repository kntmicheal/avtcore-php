<?
// http://www.php.net/manual/de/function.session-destroy.php

// Löschen aller Session-Variablen.
unset($_SESSION['useruuid']);
unset($_SESSION['username']);
unset($_SESSION['roleuuid']);
unset($_SESSION['userapikey']);
$_SESSION = array();

// Falls die Session gelöscht werden soll, löschen Sie auch das
// Session-Cookie.
// Achtung: Damit wird die Session gelöscht, nicht nur die Session-Daten!
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'],
        $params['domain'], $params['secure'], $params['httponly']
    );
}

// Zum Schluß, löschen der Session.
session_destroy();

// Auf Anmeldeseite umleiten, evtl. mit ReturnUrl
$returnurl = filter_input(INPUT_GET, 'returnurl');
header('Location: '.($returnurl !== null ? urldecode($returnurl) : 'login.php'));
exit;
