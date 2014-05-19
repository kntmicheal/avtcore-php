<?
include("authenticateheader.inc.php");

// Alle verfügbaren Sprachen laden
$languages = avorium_core_localization_Helper::getLanguagesList();
// Aktuelle Sprache festlegen
$currentLanguage = filter_input(INPUT_POST, 'language') ?: (isset($_SESSION['selectedlanguageforediting']) ? $_SESSION['selectedlanguageforediting'] : $languages[0]);
$_SESSION['selectedlanguageforediting'] = $currentLanguage;
// Mappings der aktuellen Sprache laden
$currentMappings = avorium_core_localization_Helper::loadMappings($currentLanguage);
// Assoziatives Mapping-Feld in normales Feld mit Objekten umwandeln
$currentMappingList = array();
$index = 0;
foreach ($currentMappings as $key => $value) {
	$currentMappingList[] = (object)array('index' => $index, 'key' => $key);
	$index++;
}

// Spalten für Sprachliste definieren
$columns = array(
	'key' => array('title' => 'Schl&uuml;ssel', 'urlformat' => 'Uebersetzungen.php?keyIndex={0}', 'urlfield' => 'index')
);

$currentKey = null;

// Postbacks behandeln
if (filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'POST') {
	$currentKey = filter_input(INPUT_POST, 'key');
	// Nur beim Speichern und Löschen irgendwas tun, andernfalls haben wir nur eine Sprachauswahl
	if (filter_input(INPUT_POST, 'deletebutton') !== null) {
		// Übersetzungsschlüssel löschen, muss für jede Sprache separat gemacht werden.
		unset($currentMappings[$currentKey]);
		avorium_core_localization_Helper::saveMappings($currentMappings, $currentLanguage);
		$currentKey = null; // Details werden ausgeblendet.
		shownotification('success', __('Die &Uuml;bersetzung wurde gel&ouml;scht.'));
	} elseif (filter_input(INPUT_POST, 'savebutton') !== null) {
		// Übersetzung speichern
		$value = filter_input(INPUT_POST, 'value');
		$currentMappings[$currentKey] = $value;
		avorium_core_localization_Helper::saveMappings($currentMappings, $currentLanguage);
		shownotification('success', __('Die &Uuml;bersetzung wurde gespeichert.'));
	} else {
		// Sprachwechsel. Index entfernen, es kann ja sein, das es den gerade gewählten in der Sprache noch nicht gibt.
		$currentKey = null; // Details werden ausgeblendet.
	}
} else {
	$currentKeyIndex = filter_input(INPUT_GET, 'keyIndex');
	if ($currentKeyIndex !== null) {
		$currentKey = $currentMappingList[$currentKeyIndex]->key;
	}
}
		
include("header.inc.php");

?>
<form method="post">
	<input type="hidden" name="key" value="<?= $currentKey ?>" />
	<table border="0" cellpadding="0" cellspacing="0" class="paragraph paragraph-3-col">
		<tbody>
			<tr>
				<td><label for="language"><?= __("Sprache") ?></label></td>
				<td>
					<select name="language">
					<? foreach ($languages as $language) { ?>
						<option value="<?= $language ?>"<?= ($language === $currentLanguage) ? ' selected="selected"' : '' ?>><?= $language ?></option>
					<? } ?>
					</select>
				</td>
				<td><button name="chooselanguagebutton" type="submit"><?= __("Sprache anzeigen") ?></button></td>
			</tr>
		</tbody>
	</table>
</form>
<div class="list languagelist"><?= avorium_core_ui_DataTable::render($currentMappingList, $columns, 'languagemappingslist') ?></div>
<? if (strlen($currentKey) > 0) { ?>
<form class="details" method="post">
	<input type="hidden" name="key" value="<?= $currentKey ?>" />
	<input type="hidden" name="langauge" value="<?= $currentLanguage ?>" />
	<table border="0" cellpadding="0" cellspacing="0" class="paragraph paragraph-1-col">
		<tbody>
			<tr>
				<td><label><?= __('HTML-Vorschau') ?></label>
				<td><?= $currentMappings[$currentKey] ?></td>
			</tr>
			<tr>
				<td><label><?= __('&Uuml;bersetzung') ?></label>
				<td><textarea name="value"><?= htmlentities($currentMappings[$currentKey]) ?></textarea></td>
			</tr>
		</tbody>
	</table>
	<div class="buttonarea">
	<? if(AvtRolePersistenceHandler::canCurrentRoleWriteCurrentPage()) { ?>
		<button name="savebutton" type="submit"><?= __("Speichern") ?></button>
		<button name="deletebutton" type="submit" onclick="return confirm('<?= __('Wollen Sie die &Uumlbersetzung wirklich l&ouml;schen?') ?>')"><?= __('L&ouml;schen') ?></button>
	<? } ?>
	</div>
</form>
<? } ?>
<? include("footer.inc.php") ?>
