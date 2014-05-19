<?
require_once($GLOBALS["AvtUserPersistenceHandler"]);

// Postbacks behandeln
if (count($_POST) > 0) {
	if ($_POST["email"] === "") {
		shownotification("error", __("Bitte geben Sie eine g&uuml;ltige E-Mail - Adresse ein."));
		$GLOBALS["forgotpasswordemailerror"] = true;
	} else {
		AvtUserPersistenceHandler::sendNewPasswordToUserEmail($_POST["email"]);
		shownotification("success", __("Ein neues Passwort wurde an die angegebene E-Mail - Adresse versandt, sofern diese dem System bekannt ist. Bitte befolgen Sie die darin enthaltenen Anweisungen."));
		header("Location: index.php");
		exit;
	}
}

include("header.inc.php");
?>
<p><?= __("Bitte geben Sie die E-Mail - Adresse ein, die Sie in Ihrem Konto hinterlegt haben. Wir senden Ihnen daraufhin umgehend ein neues Passwort zu.") ?></p>
<form method="post">
	<table border="0" cellspacing="0" cellpadding="0" class="paragraph paragraph-2-col">
		<tbody>
			<tr>
				<td><label for="email"><?= __("E-Mail - Adresse *") ?></label></td>
				<td><input type="email" name="email" <?= isset($GLOBALS["forgotpasswordemailerror"]) ? ' class="error"' : '' ?> /></td>
			</tr>
			<tr>
				<td></td>
				<td><button type="submit"><?= __("Absenden") ?></button></td>
			</tr>
		</tbody>
	</table>
</form>
<p><?= __("Sollten Sie keine E-Mail - Adresse hinterlegt haben, k&ouml;nnen Sie uns auch telefonisch kontaktieren.") ?></p>
<? include("footer.inc.php") ?>
