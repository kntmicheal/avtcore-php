<!DOCTYPE html>
<html>
	<head>
		<title><?= (isset($pagetitle) ? $pagetitle." - " : "").__("Fahrtenbuch") ?></title>
		<link type="text/css" media="all" rel="stylesheet" href="<?= $GLOBALS['base_url'] ?>/static/default.css"></link>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no" />
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<meta name="apple-mobile-web-app-status-bar-style" content="black" />
		<meta name="format-detection" content="telephone=no">
	</head>
	<body>
		<div class="logo"><img src="<?= $GLOBALS['base_url'] ?>/static/logo.png" alt="<?= __("avorium Fahrtenbuch") ?>" /></div>
		<div class="secondarymenu">
			<? if (isset($_SESSION["useruuid"])) { ?>
			<a href="<?= $GLOBALS['base_url'] ?>/logout.php"><?= __("Abmelden") ?></a>
			<? } else { ?>
			<a href="<?= $GLOBALS['base_url'] ?>/login.php"><?= __("Anmelden") ?></a>
			<? } ?>
		</div>
		<? if (isset($_SESSION["useruuid"])) { ?>
		<div class="loggedinas">
			<span class="loggedinaslabel"><?= __("Angemeldet als: ") ?></span>
			<span class="loggedinasvalue"><?= $_SESSION["username"] ?></span>
		</div>
		<? } ?>
		<?= avorium_core_ui_MainMenu::render() ?>
		<div class="content">
			<?= avorium_core_ui_Breadcrumbs::render() ?>
			<?= avorium_core_ui_Notification::render() ?>
