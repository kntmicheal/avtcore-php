<?

// Konkreter Adapter für MySQL-Datenbanken. Verwendet $GLOBALS-Variablen
// $GLOBALS['db_host'], $GLOBALS['db_database'], $GLOBALS['db_username']
// sowie $GLOBALS['db_password'] für Datenbankeinstellungen
class AvtPersistenceAdapter extends avorium_core_persistence_AbstractPersistenceAdapter {
	
	protected static function openDatabase() {
		return mysqli_connect($GLOBALS['db_host'], $GLOBALS['db_username'], $GLOBALS['db_password'], $GLOBALS['db_database']);
	}

	public static function escape($string) {
		return mysqli_real_escape_string(static::getDatabase(), $string);
	}

	// Stores the given object with its fields into the database
	public static function save(avorium_core_persistence_PersistentObject $persistentObject) {
		$metaData = avorium_core_persistence_helper_Annotation::getPersistableMetaData($persistentObject);
		$tableName = static::escape($persistentObject->tablename);
		$inserts = array();
		$values = array();
		$updates = array();
		foreach ($metaData['properties'] as $key => $definition) {
			$name = static::escape($definition['name']);
			if ($persistentObject->$key === null) {
				continue;
			}
			$value = static::escape($persistentObject->$key);
			$inserts[] = $name;
			switch($definition['type']) {
				case 'bool':
					$values[] = $value ? 1 : 0;
					break;
				case 'int':
					$values[] = $value;
					break;
				case 'long':
					$values[] = $value;
					break;
				default:
					$values[] = '\''.$value.'\'';
					break;
			}
			if ($name !== 'uuid') {
				$updates[] = $name.'=VALUES('.$name.')';
			}
		}
		$query = 'INSERT INTO '.$tableName.' ('.implode(',', $inserts).') VALUES ('.implode(',', $values).') ON DUPLICATE KEY UPDATE '.implode(',', $updates);
		static::executeNoResultQuery($query);
	}
	
	private static function createTable($propertiesMetaData, $tableName) {
		// Tabelle existiert noch nicht, also anlegen
		$columns = array();
		$columns[] = 'uuid VARCHAR(40) NOT NULL';
		foreach ($propertiesMetaData as $definition) {
			if ($definition['name'] !== 'uuid') {
				$columntype = static::getDatabaseType($definition['type']);
				$columnsize = isset($definition['size']) ? '('.$definition['size'].')' : '';
				$columns[] = static::escape($definition['name']). ' '.$columntype.$columnsize;
			}
		}
		$columns[] = 'PRIMARY KEY (uuid)';
		$query = 'CREATE TABLE '.$tableName.' ('.implode(',', $columns).')';
		echo $query.'<br/>';
		static::executeNoResultQuery($query);
	}
	
	private static function updateTable($propertiesMetaData, $tableName) {
		$existingcolumns = static::executeMultipleResultQuery('SHOW COLUMNS FROM '.$tableName);
		$newcolumns = array();
		foreach ($propertiesMetaData as $definition) {
			$columnfound = false;
			foreach ($existingcolumns as $existingcolumn) {
				if ($existingcolumn->Field === $definition['name']) {
					$columnfound = true;
					break;
				}
			}
			if (!$columnfound) {
				$columntype = static::getDatabaseType($definition['type']);
				$columnsize = isset($definition['size']) ? '('.$definition['size'].')' : '';
				$newcolumns[] = ' ADD COLUMN '.$definition['name'].' '.$columntype.$columnsize;
			}
		}
		if (count($newcolumns) > 0) {
			$query = 'ALTER TABLE '.$tableName.implode(',', $newcolumns);
			echo $query.'<br/>';
			static::executeNoResultQuery($query);
		}
	}
	
	public static function updateOrCreateTable($persistentobjectclass) {
		$metaData = avorium_core_persistence_helper_Annotation::getPersistableMetaData($persistentobjectclass);
		$tableName = static::escape($metaData['name']);
		// Erst mal gucken, ob die Tabelle existiert
		if (count(static::executeMultipleResultQuery('show tables like \''.$tableName.'\'')) < 1) {
			// Tabelle existiert noch nicht, also anlegen
			static::createTable($metaData['properties'], $tableName);
		} else {
			// Tabelle existiert, Spalten auf Vorhandensein prüfen
			static::updateTable($metaData['properties'], $tableName);
		}
	}
	
	private static function getDatabaseType($type) {
		switch($type) {
			case 'bool':
				return 'TINYINT(1)';
			case 'int':
				return 'INT';
			case 'long':
				return 'BIGINT';
			default:
				return 'VARCHAR';
		}
	}
	
	protected static function castDatabaseValue($value, $metatype) {
		switch($metatype) {
			case 'bool':
				return (bool)$value;
			case 'int':
				return (int)$value;
			case 'long':
				return (int)$value;
			default:
				return $value;
		}
	}
}
