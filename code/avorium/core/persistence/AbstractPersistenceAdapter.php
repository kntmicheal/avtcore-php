<?

// Basisklasse für Persistenzadapter, konkrete Klasse wird in $GLOBALS['AvtPersistenceAdapter']
// festegelegt und per require_once($GLOBALS['AvtPersistenceAdapter']) inkludiert und dann per AvtPersistenceAdapter angesprochen.
abstract class avorium_core_persistence_AbstractPersistenceAdapter {

	/* TODO
	 * - Kommentare
	 * - Keine statische Klasse mehr, sondern konkreter Adapter wird mit DB-Parametern instanziiert, dadurch bessere Kontrolle über Sichtbarkeit der Funktionen
	 */
	
	protected static $db = false;
	
	// Abgeleitete Klassen müssen hier eine Datenbankverbindung aufbauen und zurück geben
	protected abstract static function openDatabase();
	
	// Abgeleitete Klasse muss hier einen String so escapen, dass dieser ohne Probleme
	// in die Datenbank geschrieben werden kann
	public abstract static function escape($string);
	
	// Liefert die offene Datenbankverbindung zurück oder öffnet eine
	protected static function getDatabase() {
		if (!static::$db) {
			static::$db = static::openDatabase();
		}
		return static::$db;
	}

	// Returns all entries of a database table as array of objects where
	// the object fields have the name of the columns
	// resultset->fetch_object is used
	public static function getAll($persistentobjectclass) {
		$tablename = static::escape((new $persistentobjectclass())->tablename);
		$objs = static::executeMultipleResultQuery('select * from '.$tablename);
		$result = array();
		foreach ($objs as $obj) {
			$result[] = static::cast($obj, $persistentobjectclass);
		}
		return $result;
	}

	// Returns a data object from the given table with the given uuid
	// Can be false when no object is found. Uses fetch_object.
	public static function get($persistentobjectclass, $uuid) {
		$tablename = static::escape((new $persistentobjectclass())->tablename);
		$obj = static::executeSingleResultQuery('select * from '.$tablename.' where uuid=\''.static::escape($uuid).'\'');
		return static::cast($obj, $persistentobjectclass);
	}

	// Stores the given object with its fields into the database
	public abstract static function save(avorium_core_persistence_PersistentObject $persistentObject);

	// Deletes the object with the given UUID from the given table
	public static function delete(avorium_core_persistence_PersistentObject $persistentObject) {
		$tableName = static::escape($persistentObject->tablename);
		$uuid = static::escape($persistentObject->uuid);
		static::executeNoResultQuery('delete from '.$tableName.' where uuid=\''.$uuid.'\'');
	}

	// Returns an array of objects from the given query
	public static function executeMultipleResultQuery($query, $persistentobjectclass = null) {
		$resultset = static::getDatabase()->query($query);
		if (!is_a($resultset, 'mysqli_result')) {
			return array();
		}
		$result = array();
		while ($row = $resultset->fetch_object()) {
			$result[] = $persistentobjectclass !== null ? static::cast($row, $persistentobjectclass) : $row;
		}
		return $result;
	}

	// Returns a single object from the given query or false, when no result was found
	public static function executeSingleResultQuery($query, $persistentobjectclass = null) {
		$resultset = static::getDatabase()->query($query);
		if (!$resultset || $resultset->num_rows < 1) {
			return false;
		}
		$result = $persistentobjectclass !== null ? static::cast($resultset->fetch_object(), $persistentobjectclass) : $resultset->fetch_object();
		return $result;
	}

	// Executes the given query without returning a value
	public static function executeNoResultQuery($query) {
		static::getDatabase()->query($query);
	}
	
	// Castet ein Datenbankobjekt auf eine Klasse, indem die Properties kopiert werden
	// Ausserdem werden die Properties Datentypabhängig gesetzt
	public static function cast($obj, $classname, $metadata = null) {
		if (!$obj) {
			return false;
		}
		if ($metadata === null) {
			$metadata = avorium_core_persistence_helper_Annotation::getPersistableMetaData($classname);
		}
		$result = new $classname();
		foreach ($obj as $key => $value) {
			if (!property_exists($result, $key)) {
				continue; // Nicht existierende Eigenschaften überspringen
			}
			if ($metadata === null) {
				$result->$key = $value;
			} else {
				$result->$key = static::castDatabaseValue($value, $metadata['properties'][$key]['type']);
			}
		}
		return $result;
	}
	
	// Castet den Wert aus der Datenbank DB-abhängig in den gegebenen Typ
	protected abstract static function castDatabaseValue($value, $metatype);

	// Erstellt eine Tabelle für eine persistente Klasse oder erweitert deren Spalten, wenn neue dazukommen
	// Benutzt die Annotationen der persistenten Klasse
	public abstract static function updateOrCreateTable($persistentobjectclass);
	
}
