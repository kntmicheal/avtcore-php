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

require_once dirname(__FILE__).'/AbstractPersistenceAdapter.php';
require_once dirname(__FILE__).'/PersistentObject.php';

/**
 * Concrete adapter for ORACLE databases
 */
class avorium_core_persistence_OraclePersistenceAdapter extends avorium_core_persistence_AbstractPersistenceAdapter {
    
    /**
     * Creates a ORACLE database adapter using the given database credentials.
     * Currently only connecting to ORACLE on default port (1521) is supported.
     * @param string $host Hostname or IP address of thy MySQL database server
     * @param string $database Name of the database
     * @param string $username Username for the database
     * @param string $password Password as clear text
     */
    public function __construct($host, $username, $password) {
		$this->host = $host;
        $this->username = $username;
        $this->password = $password;
    }

	/**
	 * Opens a connection to the database and returns it.
	 * 
	 * @return object connection to database
	 */
    private function getDatabase() {
        return oci_connect($this->username, $this->password, $this->host);
    }
	
	/**
	 * Escapes the given string that it can be used in ORACLE SQL statements
	 * by replacing the single quote character.
	 */
	private function escape($string) {
		return str_replace('\'', '\'\'', $string);
	}

	protected function castDatabaseValue($value, $metatype) {
        switch($metatype) {
            case 'bool':
                return (bool)$value;
            case 'int':
                return (int)$value;
            case 'string':
                return (string)$value;
            default: // Unknown data types cannot be handled correctly
                throw new Exception('Database column type \''.$metatype.'\' is not known to the persistence adapter.');
        }
	}

	public function delete(\avorium_core_persistence_PersistentObject $persistentObject) {
        $this->executeNoResultQuery('delete from '.$this->escape($persistentObject->tablename).' where uuid=\''.$this->escape($persistentObject->uuid).'\'');
	}

	public function executeMultipleResultQuery($query, $persistentobjectclass = null) {
		$statement = oci_parse($this->getDatabase(), $query);
		$type = oci_statement_type($statement);
		try {
			oci_execute($statement);
		}catch (Exception $ex) {
			throw new Exception('Error in query: '.$query, null, $ex);
		}
		if ($type !== 'SELECT') {
            throw new Exception('Multiple result statement seems to be a no result statement.');
		}
		$result = array();
        while ($row = oci_fetch_object($statement)) {
            $result[] = $persistentobjectclass !== null ? $this->cast($row, $persistentobjectclass) : $row;
        }
		oci_free_statement($statement);
        return $result;
	}

	public function executeNoResultQuery($query) {
		$statement = oci_parse($this->getDatabase(), $query);
        // Check for possible results, this seems to be a semantic error
        if (oci_statement_type($statement) === 'SELECT') {
            throw new Exception('No result statement returned a result.');
        }
		try {
			oci_execute($statement);
		}catch (Exception $ex) {
			throw new Exception('Error in query: '.$query, null, $ex);
		}
		oci_free_statement($statement);
	}

	public function executeSingleResultQuery($query, $persistentobjectclass = null) {
		$statement = oci_parse($this->getDatabase(), $query);
		try {
			oci_execute($statement);
		}catch (Exception $ex) {
			throw new Exception('Error in query: '.$query, null, $ex);
		}
		$type = oci_statement_type($statement);
		if ($type !== 'SELECT') {
            throw new Exception('Single result statement seems to be a no result statement.');
		}
		$row = oci_fetch_object($statement);
        if (!$row) {
            return null;
        }
        // Check for further results, this seems to be a semantic error
        if (oci_fetch_object($statement)) {
            throw new Exception('Single result statement returned more than one result.');
        }
		oci_free_statement($statement);
        $result = $persistentobjectclass !== null ? $this->cast($row, $persistentobjectclass) : $row;
        return $result;
	}

	public function get($persistentobjectclass, $uuid) {
        if (!is_subclass_of($persistentobjectclass, 'avorium_core_persistence_PersistentObject')) {
            throw new Exception('The given class is not derived from avorium_core_persistence_PersistentObject. But this is needed to extract the table name!');
        }
        $tablename = $this->escape((new $persistentobjectclass())->tablename);
        if (strlen($tablename) < 1) {
            throw new Exception('Could not determine table name from persistent object annotations.');
        }
        return $this->executeSingleResultQuery('select * from '.$tablename.' where uuid=\''.$this->escape($uuid).'\'', $persistentobjectclass);
	}

	public function getAll($persistentobjectclass) {
        if (!is_subclass_of($persistentobjectclass, 'avorium_core_persistence_PersistentObject')) {
            throw new Exception('The given class is not derived from avorium_core_persistence_PersistentObject. But this is needed to extract the table name!');
        }
        $tablename = $this->escape((new $persistentobjectclass())->tablename);
        if (strlen($tablename) < 1) {
            throw new Exception('Could not determine table name from persistent object annotations.');
        }
        return $this->executeMultipleResultQuery('select * from '.$tablename, $persistentobjectclass);
	}

	public function save(\avorium_core_persistence_PersistentObject $persistentObject) {
        $metaData = avorium_core_persistence_helper_Annotation::getPersistableMetaData($persistentObject);
        $tableName = $this->escape($persistentObject->tablename);
        $selects = array();
        $insertcolumns = array();
		$insertvalues = array();
        $updates = array();
        foreach ($metaData['properties'] as $key => $definition) {
            $name = $this->escape($definition['name']);
            if ($persistentObject->$key === null) { // Null-values are transferred to database as they are
				$selects[] = 'NULL '.$name;
            } else {
                $value = $persistentObject->$key;
                switch($definition['type']) {
                    case 'bool':
                        $selects[] = ($value ? 1 : 0).' '.$name;
                        break;
                    case 'int':
                        $selects[] = ((int)$value).' '.$name;
                        break;
                    case 'string':
                        // Prevent storing overlong strings into database when MySQL server is not in strict mode
                        if (isset($definition['size']) && (int)$definition['size'] < strlen($value)) {
                            throw new Exception('The string to be inserted is too long for the column.');
                        }
                        $selects[] = '\''.$this->escape($value).'\' '.$name;
                        break;
                    default: // Unknown data types cannot be handled correctly
                        throw new Exception('Database column type \''.$definition['type'].'\' is not known to the persistence adapter.');
                }
            }
			$insertcolumns[] = 'T.'.$name;
			$insertvalues[] = 'S.'.$name;
			if ($name !== 'UUID') {
				$updates[] = 'T.'.$name.'=S.'.$name;
			}
        }
		$query = 'MERGE INTO '.$tableName.' T USING (SELECT '.implode(',', $selects).' FROM DUAL) S ON (T.UUID = S.UUID) WHEN MATCHED THEN UPDATE SET '.implode(',', $updates).' WHEN NOT MATCHED THEN INSERT ('.implode(',', $insertcolumns).') VALUES ('.implode(',', $insertvalues).')';
        $this->executeNoResultQuery($query);
	}

	public function updateOrCreateTable($persistentobjectclass) {
        $metaData = avorium_core_persistence_helper_Annotation::getPersistableMetaData($persistentobjectclass);
        $tableName = $this->escape(strtoupper($metaData['name']));
        // Erst mal gucken, ob die Tabelle existiert
		$query = 'SELECT * FROM USER_TABLES WHERE UPPER(TABLE_NAME)=\''.$tableName.'\'';
		$result = $this->executeMultipleResultQuery($query);
        if (count($result) < 1) {
            // Tabelle existiert noch nicht, also anlegen
            $this->createTable($metaData['properties'], $tableName);
        } else {
            // Tabelle existiert, Spalten auf Vorhandensein prüfen
            $this->updateTable($metaData['properties'], $tableName);
        }
	}
	
	private function createTable($propertiesMetaData, $tableName) {
        // Table does not exist, create it
        $columns = array();
        $columns[] = 'UUID NVARCHAR2(40) NOT NULL';
        foreach ($propertiesMetaData as $definition) {
            if ($definition['name'] !== 'UUID') {
                $columntype = $this->getDatabaseType($definition['type']);
                $columnsize = isset($definition['size']) ? '('.$this->escape($definition['size']).')' : '';
                $columns[] = $this->escape($definition['name']). ' '.$columntype.$columnsize;
            }
        }
        $columns[] = 'PRIMARY KEY (UUID)';
        $query = 'CREATE TABLE '.$this->escape($tableName).' ('.implode(',', $columns).')';
        $this->executeNoResultQuery($query);
    }

    private function updateTable($propertiesMetaData, $tableName) {
        $existingcolumns = $this->executeMultipleResultQuery('SELECT * FROM USER_TAB_COLUMNS WHERE TABLE_NAME=\''.$this->escape($tableName).'\'');
        $newcolumns = array();
        foreach ($propertiesMetaData as $definition) {
            $columnfound = false;
            $columntype = $this->getDatabaseType($definition['type']);
            $columnsize = isset($definition['size']) ? '('.$this->escape($definition['size']).')' : '';
            foreach ($existingcolumns as $existingcolumn) {
                if (strtoupper($existingcolumn->COLUMN_NAME) === strtoupper($definition['name'])) {
                    $columnfound = true;
                    // Check whether to try to change the type. This may be a data consistency risk
                    $existingcolumntype = $existingcolumn->DATA_TYPE;
                    if (strtoupper($existingcolumntype) !== strtoupper($columntype)) {
                        throw new Exception('Changing the column type is not supported.');
                    }
                    break;
                }
            }
            if (!$columnfound) {
                $newcolumns[] = $this->escape($definition['name']).' '.$columntype.$columnsize;
            }
        }
        if (count($newcolumns) > 0) {
            $query = 'ALTER TABLE '.$this->escape($tableName).' ADD ('.implode(',', $newcolumns).')';
			$this->executeNoResultQuery($query);
        }
    }

    /**
     * Maps the given persistent object type (given via annotation) to an
     * ORACLE database type.
     * 
     * @param string $type Type set in the annotation
     * @return string Corresponsing ORACLE database column type
     */
    private function getDatabaseType($type) {
        switch($type) {
            case 'bool':
                return 'NUMBER';
            case 'int':
                return 'NUMBER';
            case 'string':
                return 'NVARCHAR2';
            default: // Unknown data types cannot be handled correctly
                throw new Exception('Database column type \''.$type.'\' is not known to the persistence adapter.');
        }
    }


}
