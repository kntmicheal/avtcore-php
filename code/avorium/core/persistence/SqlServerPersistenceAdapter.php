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
 * Concrete adapter for MS SQL server databases. The PHP driver can be found at
 * http://www.microsoft.com/en-us/download/details.aspx?id=20098 (up to PHP 5.4)
 * http://sqlsrvphp.codeplex.com/discussions/441706 or http://www.hmelihkara.com/files/php_sqlsrv_55.rar (PHP 5.5)
 */
class avorium_core_persistence_SqlServerPersistenceAdapter extends avorium_core_persistence_AbstractPersistenceAdapter {
    
    /**
     * Creates a MS SQL server database adapter using the given database 
	 * credentials.
     * @param string $host Hostname or IP address including the catalog
	 * of thy SQL server database server (e.g. "localhost/SQLEXPRESS")
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
	 * @return object SQL server database resource
	 */
    public function getDatabase() {
		if ($this->db === null) {
			$this->db = sqlsrv_connect($this->host, array('Database' => $this->database, 'UID' => $this->username, 'PWD' => $this->password));
		}
		return $this->db;
    }

	/**
	 * Escapes the given string that it can be used in MSSQL SQL statements
	 * by replacing the single quote character.
	 */
	private function escape($string) {
		return str_replace('\'', '\'\'', $string);
	}

    public function save(avorium_core_persistence_PersistentObject $persistentObject) {
        $metaData = avorium_core_persistence_helper_Annotation::getPersistableMetaData($persistentObject);
        $tableName = $this->escapeTableOrColumnName($persistentObject->tablename);
        $selects = array();
        $insertcolumns = array();
		$insertvalues = array();
        $updates = array();
        foreach ($metaData['properties'] as $key => $definition) {
            $name = $this->escapeTableOrColumnName($definition['name']);
            if ($persistentObject->$key === null) { // Null-values are transferred to database as they are
				$selects[] = 'NULL '.$name;
            } else {
                $value = $persistentObject->$key;
				if (!isset($definition['type'])) {
					throw new Exception('Type of persistent object property not set.');
				}
                switch($definition['type']) {
                    case 'bool':
                        $selects[] = ($value ? 1 : 0).' AS '.$name;
                        break;
                    case 'int':
                        $selects[] = ((int)$value).' AS '.$name;
                        break;
                    case 'string':
                        // Prevent storing overlong strings into database when MySQL server is not in strict mode
                        if (isset($definition['size']) && (int)$definition['size'] < strlen($value)) {
                            throw new Exception('The string to be inserted is too long for the column.');
                        }
                        $selects[] = '\''.$this->escape($value).'\' AS '.$name;
                        break;
                    default: // Unknown data types cannot be handled correctly
                        throw new Exception('Database column type \''.$definition['type'].'\' is not known to the persistence adapter.');
                }
            }
			$insertcolumns[] = $name;
			$insertvalues[] = 'S.'.$name;
			if ($name !== 'UUID') {
				$updates[] = $name.'=S.'.$name;
			}
        }
		// See http://www.sergeyv.com/blog/archive/2010/09/10/sql-server-upsert-equivalent.aspx
		$query = 'MERGE INTO '.$tableName.' USING (SELECT '.implode(',', $selects).') AS S ON ('.$tableName.'.UUID = S.UUID) WHEN MATCHED THEN UPDATE SET '.implode(',', $updates).' WHEN NOT MATCHED THEN INSERT ('.implode(',', $insertcolumns).') VALUES ('.implode(',', $insertvalues).');';
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
                $columns[] = $this->escapeTableOrColumnName($definition['name']). ' '.$columntype.$columnsize;
            }
        }
        $columns[] = 'PRIMARY KEY (UUID)';
        $query = 'CREATE TABLE '.$tableName.' ('.implode(',', $columns).')';
        $this->executeNoResultQuery($query);
    }

    private function updateTable($propertiesMetaData, $tableName) {
		// From http://stackoverflow.com/a/2418665
        $existingcolumns = $this->executeMultipleResultQuery('select COLUMN_NAME, DATA_TYPE from INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME=\''.$tableName.'\'');
        $newcolumns = array();
        foreach ($propertiesMetaData as $definition) {
            $columnfound = false;
            $columntype = $this->getDatabaseType($definition['type']);
            $columnsize = isset($definition['size']) ? '('.$definition['size'].')' : '';
            foreach ($existingcolumns as $existingcolumn) {
                if ($existingcolumn->COLUMN_NAME === $definition['name']) {
                    $columnfound = true;
                    // Check whether to try to change the type. This may be a data consistency risk
                    if (strtolower($existingcolumn->DATA_TYPE) !== strtolower($columntype)) {
                        throw new Exception('Changing the column type is not supported.');
                    }
                    break;
                }
            }
            if (!$columnfound) {
                $newcolumns[] = $definition['name'].' '.$columntype.$columnsize;
            }
        }
        if (count($newcolumns) > 0) {
            $query = 'ALTER TABLE '.$tableName.' ADD '.implode(',', $newcolumns);
            $this->executeNoResultQuery($query);
        }
    }

    public function updateOrCreateTable($persistentobjectclass) {
        $metaData = avorium_core_persistence_helper_Annotation::getPersistableMetaData($persistentobjectclass);
        $tableName = $this->escapeTableOrColumnName($metaData['name']);
        // Erst mal gucken, ob die Tabelle existiert
        if (count($this->executeMultipleResultQuery('SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = \''.$tableName.'\'')) < 1) {
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
        $resultset = sqlsrv_query($this->getDatabase(), $query);
        if ($resultset !== false && (sqlsrv_field_metadata($resultset) === false || sqlsrv_num_fields($resultset) < 1)) { // Query did not return any result because it was a no result query
            throw new Exception('Multiple result statement seems to be a no result statement.');
        }
        if ($resultset === false) { // Error in SQL query
            throw new Exception('Error in query: '.$query);
        }
        $result = array();
        while ($row = sqlsrv_fetch_object($resultset)) {
            $result[] = $persistentobjectclass !== null ? $this->cast($row, $persistentobjectclass) : $row;
        }
        return $result;
    }

	public function executeSingleResultQuery($query, $persistentobjectclass = null) {
        $resultset = sqlsrv_query($this->getDatabase(), $query);
        if ($resultset !== false && (sqlsrv_field_metadata($resultset) === false || sqlsrv_num_fields($resultset) < 1)) { // Query did not return any result because it was a no result query
            throw new Exception('Single result statement seems to be a no result statement.');
        }
        if ($resultset === false) { // Error in SQL query
            throw new Exception('Error in query: '.$query);
        }
        $row = sqlsrv_fetch_object($resultset);
        if (is_null($row)) {
            return null;
        }
        // Check for further results, this seems to be a semantic error
        if (sqlsrv_fetch_object($resultset)) {
            throw new Exception('Single result statement returned more than one result.');
        }
        $result = $persistentobjectclass !== null ? $this->cast($row, $persistentobjectclass) : $row;
        return $result;
	}

	public function executeNoResultQuery($query) {
        $resultset = sqlsrv_query($this->getDatabase(), $query);
        if ($resultset === false) { // When false is returned, the query was not successful
            throw new Exception('Error in query: '.$query);
        }
        if (sqlsrv_has_rows($resultset)) { // When not empty the query was of wrong type
            throw new Exception('No result statement returned a result.');
        }
	}

	public function getAll($persistentobjectclass) {
        if (!is_subclass_of($persistentobjectclass, 'avorium_core_persistence_PersistentObject')) {
            throw new Exception('The given class is not derived from avorium_core_persistence_PersistentObject. But this is needed to extract the table name!');
        }
        $tablename = (new $persistentobjectclass())->tablename;
        $escapedtablename = $this->escapeTableOrColumnName($tablename); // table name is set here because it was tested in persistent object constructor
        return $this->executeMultipleResultQuery('select * from '.$escapedtablename, $persistentobjectclass);
	}

	public function get($persistentobjectclass, $uuid) {
        if (!is_subclass_of($persistentobjectclass, 'avorium_core_persistence_PersistentObject')) {
            throw new Exception('The given class is not derived from avorium_core_persistence_PersistentObject. But this is needed to extract the table name!');
        }
        $tablename = (new $persistentobjectclass())->tablename;
        $escapedtablename = $this->escapeTableOrColumnName($tablename); // table name is set here because it was tested in persistent object constructor
        return $this->executeSingleResultQuery('select * from '.$escapedtablename.' where uuid=\''.$this->escape($uuid).'\'', $persistentobjectclass);
	}

	public function delete(avorium_core_persistence_PersistentObject $persistentObject) {
        $tableName = $this->escapeTableOrColumnName($persistentObject->tablename);
        $uuid = $this->escape($persistentObject->uuid);
        $this->executeNoResultQuery('delete from '.$tableName.' where uuid=\''.$uuid.'\'');
	}
	
	/**
	 * Replaces all spaces, quotes and semicolon in table or column names to 
	 * prevent SQL injections
	 */
	private function escapeTableOrColumnName($tablename) {
		return str_replace(' ', '', str_replace(';', '', str_replace('\'', '', $tablename)));
	}

	public function getDataTable($query) {
		// Check query parameter
		if (is_null($query)) {
			throw new Exception('The query must not be null.');
		}		
        $resultset = sqlsrv_query($this->getDatabase(), $query, array(), array( "Scrollable" => SQLSRV_CURSOR_KEYSET )); // Parameter needed to make sqlserv_num_rows work
        if ($resultset !== false && (sqlsrv_field_metadata($resultset) === false || sqlsrv_num_fields($resultset) < 1)) { // Query did not return any result because it was a no result query
            throw new Exception('Multiple result statement seems to be a no result statement.');
        }
        if ($resultset === false) {
            throw new Exception('Error in query: '.$query);
        }
		// Prepare datatable
		$columncount = sqlsrv_num_fields($resultset);
		$rowcount = sqlsrv_num_rows($resultset);
		$datatable = new avorium_core_data_DataTable($rowcount, $columncount);
		// Extract header names even if the resultset is empty
		$metadata = sqlsrv_field_metadata(sqlsrv_prepare($this->getDatabase(), $query)); // http://msdn.microsoft.com/en-us/library/cc296197.aspx
		for ($i = 0; $i < $columncount; $i++) {
			$datatable->setHeader($i, $metadata[$i]['Name']);
		}
		// Fill datatable cells
		$rownum = 0;
        while ($row = sqlsrv_fetch_array($resultset)) {
			for ($i = 0; $i < $columncount; $i++) {
				// Here we need datatype conversions because the database does not return strings
				$value = $row[$i];
				if (is_string($value)) {
					$datatable->setCellValue($rownum, $i, $value);
				} elseif (is_a($value, 'DateTime')) {
					$datatable->setCellValue($rownum, $i, $value->format('Y-m-d H:i:s'));
				} elseif (is_bool($value)) {
					$datatable->setCellValue($rownum, $i, $value ? '1' : '0');
				} else {
					// Numeric and so on
					$datatable->setCellValue($rownum, $i, ''.$value);
				}
			}
			$rownum++;
        }
        return $datatable;
	}

	public function saveDataTable($tablename, $datatable) {
		// Check parameters for incorrect values
		if (is_null($tablename)) {
			throw new Exception('No table name given.');
		}
		if (!is_string($tablename)) {
			throw new Exception('Table name must be a string.');
		}
		if (is_null($datatable)) {
			throw new Exception('No data table given.');
		}
		if (!is_a($datatable, 'avorium_core_data_DataTable')) {
			throw new Exception('Data table is not of correct datatype.');
		}
		// Obtain primary key from database
		$escapedtablename = $this->escapeTableOrColumnName($tablename);
		$primarykeys = $this->executeMultipleResultQuery('select COLUMN_NAME from INFORMATION_SCHEMA.KEY_COLUMN_USAGE where OBJECTPROPERTY(OBJECT_ID(constraint_name), \'IsPrimaryKey\') = 1 and TABLE_NAME=\''.$escapedtablename.'\'');
		if (count($primarykeys) < 1) {
			throw new Exception('Invalid table name given: '.$tablename);
		}
		// Currently only one primary key is supported, get its column name
		$primarykeycolumnname = $primarykeys[0]->COLUMN_NAME;
		$primarykeycolumnfound = false;
		// Process data table
		$headernames = $datatable->getHeaders();
		$escapedheadernames = array();
		$columncount = count($headernames);
		$datamatrix = $datatable->getDataMatrix();
		// Ignore empty datatables
		if (count($datamatrix) < 1) {
			return;
		}
        $selects = array();
        $insertcolumns = array();
		$insertvalues = array();
        $updates = array();
		foreach ($headernames as $headername) {
			if ($headername === null) {
				throw new Exception('The header name is null but must not be.');
			}
			$escapedheadername = $this->escapeTableOrColumnName($headername);
			if (strlen($escapedheadername) < 1) {
				throw new Exception('The header name is empty but must not be.');
			}
			$escapedheadernames[] = $escapedheadername;
			$insertcolumns[] = $escapedheadername;
			$insertvalues[] = 'S.'.$escapedheadername;
			if ($escapedheadername !== $primarykeycolumnname) {
				$updates[] = $escapedheadername.'=S.'.$escapedheadername;
			} else {
				$primarykeycolumnfound = true;
			}
		}
		if (!$primarykeycolumnfound) {
			throw new Exception('Expected primary key column '.$primarykeycolumnname.' not found.');
		}
		foreach ($datamatrix as $row) {
			$rowselects = array();
			for ($i = 0; $i < $columncount; $i++) {
				// Distinguish between data types
				if (is_null($row[$i])) {
					$rowselects[] = 'NULL AS '.$escapedheadernames[$i];
				} else if (is_bool($row[$i])) {
					$rowselects[] = ($row[$i] ? 1 : 0).' AS '.$escapedheadernames[$i];
				} else if (is_numeric($row[$i])) {
					$rowselects[] = $row[$i].' AS '.$escapedheadernames[$i];
				} else if (is_string($row[$i])) {
					$rowselects[] = '\''.$this->escape($row[$i]).'\' AS '.$escapedheadernames[$i];
				} else {
					throw new Exception('Unknown datatype: '.gettype($row[$i]));
				}
			}
			$selects[] = 'SELECT '.implode(',', $rowselects);
		}
		$query = 'MERGE INTO '.$escapedtablename.' AS T USING ('.implode(' UNION ALL ', $selects).') AS S ON (T.UUID = S.UUID) WHEN MATCHED THEN UPDATE SET '.implode(',', $updates).' WHEN NOT MATCHED THEN INSERT ('.implode(',', $insertcolumns).') VALUES ('.implode(',', $insertvalues).');';
        $this->executeNoResultQuery($query);
	}

}
