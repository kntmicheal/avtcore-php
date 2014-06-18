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
 * Concrete adapter for MySQL databases
 */
class avorium_core_persistence_MySqlPersistenceAdapter extends avorium_core_persistence_AbstractPersistenceAdapter {
    
    /**
     * Creates a MySQL database adapter using the given database credentials.
     * Currently only connecting to MySQL on default port (3306) is supported.
     * @param string $host Hostname or IP address of thy MySQL database server
     * @param string $database Name of the database
     * @param string $username Username for the database
     * @param string $password Password as clear text
     */
    public function __construct($host, $database, $username, $password) {
		$this->host = $host;
        $this->database = $database;
        $this->username = $username;
        $this->password = $password;
    }

	/**
	 * @var object Database connection used in all functions.
	 */
	private $db = null;
	
	/**
	 * Returns the current database connection resource. Creates a connection
	 * when none exists.
	 * 
	 * @return object MySQL database resource
	 */
    private function getDatabase() {
		if ($this->db === null) {
			$this->db = mysqli_connect($this->host, $this->username, $this->password, $this->database);
		}
		return $this->db;
    }

	/**
	 * Escapes the given string for using it in an SQL statement.
	 * 
	 * @param string $string String to escape
	 * @return string Escaped string
	 */
    private function escape($string) {
        return mysqli_real_escape_string($this->getDatabase(), $string);
    }

    public function save(avorium_core_persistence_PersistentObject $persistentObject) {
        $metaData = avorium_core_persistence_helper_Annotation::getPersistableMetaData($persistentObject);
        $tableName = $this->escape($persistentObject->tablename);
        $inserts = array();
        $values = array();
        $updates = array();
        foreach ($metaData['properties'] as $key => $definition) {
            $name = $this->escape($definition['name']);
            if ($persistentObject->$key === null) { // Null-values are transferred to database as they are
                $inserts[] = $name;
                $values[] = 'NULL';
            } else {
                $value = $this->escape($persistentObject->$key);
                $inserts[] = $name;
                switch($definition['type']) {
                    case 'bool':
                        $values[] = $value ? 1 : 0;
                        break;
                    case 'int':
                        $values[] = $value;
                        break;
                    case 'string':
                        // Prevent storing overlong strings into database when MySQL server is not in strict mode
                        if (isset($definition['size']) && (int)$definition['size'] < strlen($value)) {
                            throw new Exception('The string to be inserted is too long for the column.');
                        }
                        $values[] = '\''.$value.'\'';
                        break;
                    default: // Unknown data types cannot be handled correctly
                        throw new Exception('Database column type \''.$definition['type'].'\' is not known to the persistence adapter.');
                }
            }
            if ($name !== 'UUID') {
                $updates[] = $name.'=VALUES('.$name.')';
            }
        }
        $query = 'INSERT INTO '.$tableName.' ('.implode(',', $inserts).') VALUES ('.implode(',', $values).') ON DUPLICATE KEY UPDATE '.implode(',', $updates);
        $this->executeNoResultQuery($query);
    }

    private function createTable($propertiesMetaData, $tableName) {
        // Table does not exist, create it
        $columns = array();
        $columns[] = 'UUID VARCHAR(40) NOT NULL';
        foreach ($propertiesMetaData as $definition) {
            if ($definition['name'] !== 'UUID') {
                $columntype = $this->getDatabaseType($definition['type']);
                $columnsize = isset($definition['size']) ? '('.$definition['size'].')' : '';
                $columns[] = $this->escape($definition['name']). ' '.$columntype.$columnsize;
            }
        }
        $columns[] = 'PRIMARY KEY (UUID)';
        $query = 'CREATE TABLE '.$tableName.' ('.implode(',', $columns).')';
        $this->executeNoResultQuery($query);
    }

    private function updateTable($propertiesMetaData, $tableName) {
        $existingcolumns = $this->executeMultipleResultQuery('SHOW COLUMNS FROM '.$tableName);
        $newcolumns = array();
        foreach ($propertiesMetaData as $definition) {
            $columnfound = false;
            $columntype = $this->getDatabaseType($definition['type']);
            $columnsize = isset($definition['size']) ? '('.$definition['size'].')' : '';
            foreach ($existingcolumns as $existingcolumn) {
                if ($existingcolumn->Field === $definition['name']) {
                    $columnfound = true;
                    // Check whether to try to change the type. This may be a data consistency risk
                    $existingcolumntype = substr($existingcolumn->Type, 0, strpos($existingcolumn->Type, '('));
                    if (strtolower($existingcolumntype) !== strtolower($columntype)) {
                        throw new Exception('Changing the column type is not supported.');
                    }
                    break;
                }
            }
            if (!$columnfound) {
                $newcolumns[] = ' ADD COLUMN '.$definition['name'].' '.$columntype.$columnsize;
            }
        }
        if (count($newcolumns) > 0) {
            $query = 'ALTER TABLE '.$tableName.implode(',', $newcolumns);
            $this->executeNoResultQuery($query);
        }
    }

    public function updateOrCreateTable($persistentobjectclass) {
        $metaData = avorium_core_persistence_helper_Annotation::getPersistableMetaData($persistentobjectclass);
        $tableName = $this->escape($metaData['name']);
        // Erst mal gucken, ob die Tabelle existiert
        if (count($this->executeMultipleResultQuery('show tables like \''.$tableName.'\'')) < 1) {
            // Tabelle existiert noch nicht, also anlegen
            $this->createTable($metaData['properties'], $tableName);
        } else {
            // Tabelle existiert, Spalten auf Vorhandensein prüfen
            $this->updateTable($metaData['properties'], $tableName);
        }
    }

    /**
     * Maps the given persistent object type (given via annotation) to a
     * MySQL database type.
     * 
     * @param string $type Type set in the annotation
     * @return string Corresponsing MySQL database column type
     */
    private function getDatabaseType($type) {
        switch($type) {
            case 'bool':
                return 'tinyint';
            case 'int':
                return 'int';
            case 'string':
                return 'varchar';
            default: // Unknown data types cannot be handled correctly
                throw new Exception('Database column type \''.$type.'\' is not known to the persistence adapter.');
        }
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

    public function executeMultipleResultQuery($query, $persistentobjectclass = null) {
        $resultset = $this->getDatabase()->query($query);
        if ($resultset === true) { // Query did not return any result because it was a no result query
            throw new Exception('Multiple result statement seems to be a no result statement.');
        }
        if (!is_object($resultset)) {
            throw new Exception('Error in query: '.$query);
        }
        $result = array();
        while ($row = $resultset->fetch_object()) {
            $result[] = $persistentobjectclass !== null ? $this->cast($row, $persistentobjectclass) : $row;
        }
        return $result;
    }

	public function executeSingleResultQuery($query, $persistentobjectclass = null) {
        $resultset = $this->getDatabase()->query($query);
        if ($resultset === true) { // Query did not return any result because it was a no result query
            throw new Exception('Single result statement seems to be a no result statement.');
        }
        if (!is_object($resultset)) { // Error in SQL query
            throw new Exception('Error in query: '.$query);
        }
        $row = $resultset->fetch_object();
        if (is_null($row)) {
            return null;
        }
        // Check for further results, this seems to be a semantic error
        if ($resultset->fetch_object()) {
            throw new Exception('Single result statement returned more than one result.');
        }
        $result = $persistentobjectclass !== null ? $this->cast($row, $persistentobjectclass) : $row;
        return $result;
	}

	public function executeNoResultQuery($query) {
        $resultset = $this->getDatabase()->query($query);
        if (!$resultset) { // When false is returned, the query was not successful
            throw new Exception('Error in query: '.$query);
        }
        if ($resultset !== true) { // When not true (but an object is returned, the query was of wrong type
            throw new Exception('No result statement returned a result.');
        }
	}

	public function getAll($persistentobjectclass) {
        if (!is_subclass_of($persistentobjectclass, 'avorium_core_persistence_PersistentObject')) {
            throw new Exception('The given class is not derived from avorium_core_persistence_PersistentObject. But this is needed to extract the table name!');
        }
        $tablename = (new $persistentobjectclass())->tablename;
        $escapedtablename = $this->escape($tablename);
        if (strlen($escapedtablename) < 1) {
            throw new Exception('Could not determine table name from persistent object annotations.');
        }
        return $this->executeMultipleResultQuery('select * from '.$escapedtablename, $persistentobjectclass);
	}

	public function get($persistentobjectclass, $uuid) {
        if (!is_subclass_of($persistentobjectclass, 'avorium_core_persistence_PersistentObject')) {
            throw new Exception('The given class is not derived from avorium_core_persistence_PersistentObject. But this is needed to extract the table name!');
        }
        $tablename = (new $persistentobjectclass())->tablename;
        $escapedtablename = $this->escape($tablename);
        if (strlen($escapedtablename) < 1) {
            throw new Exception('Could not determine table name from persistent object annotations.');
        }
        return $this->executeSingleResultQuery('select * from '.$escapedtablename.' where uuid=\''.$this->escape($uuid).'\'', $persistentobjectclass);
	}

	public function delete(\avorium_core_persistence_PersistentObject $persistentObject) {
        $tableName = $this->escape($persistentObject->tablename);
        $uuid = $this->escape($persistentObject->uuid);
        $this->executeNoResultQuery('delete from '.$tableName.' where uuid=\''.$uuid.'\'');
	}

}
