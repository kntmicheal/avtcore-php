<?
// Führt Lokalisierungen durch. Wird von utils verwendet
// Dazu wird aus dem Browser die erste Locale ausgesucht (de-DE)
// und im locale-Verzeichnis nach einerjson-Datei mit diesem Namen
// gesucht. In dieser müssen dann die Übersetzungen HTML-kodiert stehen.
// Werden keine Übersetzungen gefunden, wird der String einfach zurück gegeben.
class avorium_core_localization_Helper {

	private static $mappings = null;
	private static $currentLanguage = null;
	private static $langFile = null;
	
	// TODO: Caching mit apc_store

	// Die Übersetzungen kommen aus den locale/*.json - Dateien. Zuerst wird geprüft, ob eine
	// 5-stellige Angabe im Browser existiert (z.B. de-CH). Dann wird nach einer Übersetzungsdatei
	// gesucht. Wird diese nicht gefunden, wird der zweistellige Code (de) probiert.
	// Somit kann man allgemeine Übersetzungen für deutsch machen und spezifische Phrasen in
	// Schweizerdeutch (de-CH) übersetzen.
	public static function translate($str) {
		// Gucken, ob Mappings für Request bereits geladen wurden
		if (static::$mappings === null) {
			static::$mappings = array();
			$language = locale_accept_from_http(filter_input(INPUT_SERVER, 'HTTP_ACCEPT_LANGUAGE'));
			if ($language === null) {
				return $str; // Browser hat keine Sprache angegeben, also auch nicht übersetzen
			}
			static::$currentLanguage = strtolower($language);
			// Mappings laden, falls möglich
			static::$langFile = $GLOBALS['app_path'].'/locale/'.static::$currentLanguage.'.json';
			if (file_exists(static::$langFile)) {
				static::$mappings = static::loadMappings(static::$currentLanguage);
			}
		}
		// Wenn Laden nicht geklappt hat, ist das Feld leer
		if (!isset(static::$mappings[$str])) {
			// Neue Übersetzung mit Standardwert erzeugen und in Mappings-Datei speichern
			// Dadurch kann die Übersetzung später manuell erfolgen
			static::$mappings[$str] = $str;
			static::saveMappings(static::$mappings, static::$currentLanguage);
		}
		return static::$mappings[$str];
	}
	
	/**
	 * Speichert die gegebenen Mappings für die gegebene Sprache und überschreibt dabei die
	 * alten Werte. Wird sowohl hier als auch in der Übersetzungsseite verwendet.
	 */
	public static function saveMappings(array $mappings, $language) {
		ksort($mappings);
		$json = json_encode($mappings, JSON_PRETTY_PRINT);
		$langFile = $GLOBALS['app_path'].'/locale/'.$language.'.json';
		file_put_contents($langFile, $json);
		chmod($langFile, 0660);
	}
	
	/**
	 * Lädt die Mappings der gegebenen Sprache und gibt sie als Feld zurück.
	 * Wird sowohl in dieser Klasse als auch in der Übersetzungsmaske der
	 * Administration verwendet.
	 */
	public static function loadMappings($language) {
		return json_decode(file_get_contents($GLOBALS['app_path'].'/locale/'.$language.'.json'), true);
	}
	
	/**
	 * Liefert eine Liste aller derzeit vorhandenen Sprachen. Dazu wird das locale-Verzeichnis
	 * nach json-Dateien durchsucht und deren Namen vor dem Punkt als SPrachkennung interpretiert.
	 */
	public static function getLanguagesList() {
		$localedir = $GLOBALS['app_path'].'/locale/';
		$languages = array();
		if (is_dir($localedir)) {
			$files = array_diff(scandir($localedir), array('..', '.'));
			foreach ($files as $file) {
				$languages[] = substr($file, 0, strrpos($file, '.'));
			}
		}
		sort($languages);
		return $languages;
	}
}
