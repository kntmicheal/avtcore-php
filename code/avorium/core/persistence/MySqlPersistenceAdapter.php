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

    protected function openDatabase() {
        return mysqli_connect($this->host, $this->username, $this->password, $this->database);
    }

    public function escape($string) {
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
                    default:
                        $values[] = '\''.$value.'\'';
                        break;
                }
            }
            if ($name !== 'uuid') {
                $updates[] = $name.'=VALUES('.$name.')';
            }
        }
        $query = 'INSERT INTO '.$tableName.' ('.implode(',', $inserts).') VALUES ('.implode(',', $values).') ON DUPLICATE KEY UPDATE '.implode(',', $updates);
        $this->executeNoResultQuery($query);
    }

    private function createTable($propertiesMetaData, $tableName) {
        // Table does not exist, create it
        $columns = array();
        $columns[] = 'uuid NVARCHAR(40) NOT NULL';
        foreach ($propertiesMetaData as $definition) {
            if ($definition['name'] !== 'uuid') {
                $columntype = $this->getDatabaseType($definition['type']);
                $columnsize = isset($definition['size']) ? '('.$definition['size'].')' : '';
                $columns[] = $this->escape($definition['name']). ' '.$columntype.$columnsize;
            }
        }
        $columns[] = 'PRIMARY KEY (uuid)';
        $query = 'CREATE TABLE '.$tableName.' ('.implode(',', $columns).')';
        $this->executeNoResultQuery($query);
    }

    private function updateTable($propertiesMetaData, $tableName) {
        $existingcolumns = $this->executeMultipleResultQuery('SHOW COLUMNS FROM '.$tableName);
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
                $columntype = $this->getDatabaseType($definition['type']);
                $columnsize = isset($definition['size']) ? '('.$definition['size'].')' : '';
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

    private function getDatabaseType($type) {
        switch($type) {
            case 'bool':
                return 'TINYINT(1)';
            case 'int':
                return 'INT';
            default:
                return 'NVARCHAR';
        }
    }

    protected function castDatabaseValue($value, $metatype) {
        switch($metatype) {
            case 'bool':
                return (bool)$value;
            case 'int':
                return (int)$value;
            default:
                return $value;
        }
    }

    protected function extractRowFromResultset($resultset) {
        return $resultset->fetch_object();
    }

}
