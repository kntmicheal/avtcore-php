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
     * @param string $nlslang Langauge to use when connecting to the server. Must
     * match the database language (e.g. 'AL32UTF8')
     */
    public function __construct($host, $username, $password, $nlslang) {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->nlslang = $nlslang;
    }
	
	/**
	 * @var object Database connection used in all functions.
	 */
	private $db = null;
	
	/**
	 * Returns the current database connection resource. Creates a connection
	 * when none exists.
	 * 
	 * @return object Oracle database resource
	 */
    public function getDatabase() {
		if ($this->db === null) {
			$this->db = oci_connect($this->username, $this->password, $this->host, $this->nlslang);
		}
		return $this->db;
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
        $this->executeNoResultQuery('delete from '.$this->escapeTableOrColumnName($persistentObject->tablename).' where uuid=\''.$this->escape($persistentObject->uuid).'\'');
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
        $tablename = $this->escapeTableOrColumnName((new $persistentobjectclass())->tablename); // table name is set here because it was tested in persistent object constructor
        return $this->executeSingleResultQuery('select * from '.$tablename.' where uuid=\''.$this->escape($uuid).'\'', $persistentobjectclass);
	}

	public function getAll($persistentobjectclass) {
        if (!is_subclass_of($persistentobjectclass, 'avorium_core_persistence_PersistentObject')) {
            throw new Exception('The given class is not derived from avorium_core_persistence_PersistentObject. But this is needed to extract the table name!');
        }
        $tablename = $this->escapeTableOrColumnName((new $persistentobjectclass())->tablename); // table name is set here because it was tested in persistent object constructor
        return $this->executeMultipleResultQuery('select * from '.$tablename, $persistentobjectclass);
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
        $tableName = $this->escapeTableOrColumnName(strtoupper($metaData['name']));
        // Check whether the table exists
		$query = 'SELECT * FROM USER_TABLES WHERE UPPER(TABLE_NAME)=\''.$tableName.'\'';
		$result = $this->executeMultipleResultQuery($query);
        if (count($result) < 1) {
            // Table does not exist, create it from scratch
            $this->createTable($metaData['properties'], $tableName);
        } else {
            // Table exists, append new columns
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
                $columns[] = $this->escapeTableOrColumnName($definition['name']). ' '.$columntype.$columnsize;
            }
        }
        $columns[] = 'PRIMARY KEY (UUID)';
        $query = 'CREATE TABLE '.$this->escapeTableOrColumnName($tableName).' ('.implode(',', $columns).')';
        $this->executeNoResultQuery($query);
    }

    private function updateTable($propertiesMetaData, $tableName) {
        $existingcolumns = $this->executeMultipleResultQuery('SELECT * FROM USER_TAB_COLUMNS WHERE TABLE_NAME=\''.$this->escapeTableOrColumnName($tableName).'\'');
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
                $newcolumns[] = $this->escapeTableOrColumnName($definition['name']).' '.$columntype.$columnsize;
            }
        }
        if (count($newcolumns) > 0) {
            $query = 'ALTER TABLE '.$this->escapeTableOrColumnName($tableName).' ADD ('.implode(',', $newcolumns).')';
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
		// Temporarily set the decimal characters to english ones to have correct number formats in resulting strings
		oci_execute(oci_parse($this->getDatabase(), 'begin EXECUTE IMMEDIATE \'ALTER SESSION set nls_numeric_characters=".,"\';end;'));
		// Temporarily set the date format to yyyy-mm-dd hh:ii:ss for the case that we request dates from the database
		oci_execute(oci_parse($this->getDatabase(), 'begin EXECUTE IMMEDIATE \'ALTER SESSION set nls_date_format="yyyy-mm-dd hh24:mi:ss"\';end;'));
		// Execute the given statement
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
		// Extract rows and columns to have the row count
		$rows = [];
        while ($row = oci_fetch_row($statement)) {
			$rows[] = $row;
        }
		// Prepare datatable
		$columncount = oci_num_fields($statement);
		$rowcount = count($rows);
		$datatable = new avorium_core_data_DataTable($rowcount, $columncount);
		// Extract header names even if the resultset is empty
		for ($i = 0; $i < $columncount; $i++) {
			$datatable->setHeader($i, oci_field_name($statement, $i + 1));
		}
		// Fill datatable cells
		for ($i = 0; $i < $rowcount; $i++) {
			for ($j = 0; $j < $columncount; $j++) {
				$datatable->setCellValue($i, $j, $rows[$i][$j]);
			}
        }
		oci_free_statement($statement);
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
		// Temporarily set the decimal characters to english ones to have correct number formats in resulting strings
		oci_execute(oci_parse($this->getDatabase(), 'begin EXECUTE IMMEDIATE \'ALTER SESSION set nls_numeric_characters=".,"\';end;'));
		// Temporarily set the date format to yyyy-mm-dd hh:ii:ss for the case that we request dates from the database
		oci_execute(oci_parse($this->getDatabase(), 'begin EXECUTE IMMEDIATE \'ALTER SESSION set nls_date_format="yyyy-mm-dd hh24:mi:ss"\';end;'));
		// Obtain primary key from database
		$escapedtablename = $this->escapeTableOrColumnName($tablename);
		$primarykeys = $this->executeMultipleResultQuery('select user_cons_columns.table_name, user_cons_columns.column_name from user_cons_columns join user_constraints on user_constraints.constraint_name = user_cons_columns.constraint_name where user_constraints.constraint_type=\'P\' and user_cons_columns.table_name=\''.$escapedtablename.'\'');
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
			$insertcolumns[] = 'T.'.$escapedheadername;
			$insertvalues[] = 'S.'.$escapedheadername;
			if ($escapedheadername !== $primarykeycolumnname) {
				$updates[] = 'T.'.$escapedheadername.'=S.'.$escapedheadername;
			} else {
				$primarykeycolumnfound = true;
			}
		}
		if (!$primarykeycolumnfound) {
			throw new Exception('Expected primary key column '.$primarykeycolumnname.' not found.');
		}
		// Wee need the column schema from the database to eventually parse
		// the given values into database data types
		$columnsarray = $this->executeMultipleResultQuery('select COLUMN_NAME, DATA_TYPE from USER_TAB_COLS where TABLE_NAME = \''.$escapedtablename.'\'');
		$columns = [];
		foreach ($columnsarray as $column) {
			$columns[$column->COLUMN_NAME] = $column->DATA_TYPE;
		}
		foreach ($datamatrix as $row) {
			$rowselects = array();
			for ($i = 0; $i < $columncount; $i++) {
				// Distinguish between data types
				if (is_null($row[$i])) {
					$rowselects[] = 'NULL '.$escapedheadernames[$i];
				} else if (is_bool($row[$i])) {
					$rowselects[] = ($row[$i] ? 1 : 0).' '.$escapedheadernames[$i];
				} else if (is_numeric($row[$i])) {
					if ($columns[$escapedheadernames[$i]] === 'BINARY_DOUBLE') { // Double must be handled separately in ORACLE
						$rowselects[] = 'to_binary_double(\''.$row[$i].'\') '.$escapedheadernames[$i];
					} else {
						$rowselects[] = $row[$i].' '.$escapedheadernames[$i];
					}
				} else if (is_string($row[$i])) {
					$rowselects[] = '\''.$this->escape($row[$i]).'\' '.$escapedheadernames[$i];
				} else {
					throw new Exception('Unknown datatype: '.gettype($row[$i]));
				}
			}
			$selects[] = 'SELECT '.implode(',', $rowselects).' FROM DUAL';
		}
		$query = 'MERGE INTO '.$escapedtablename.' T USING ('.implode(' UNION ALL ', $selects).') S ON (T.'.$primarykeycolumnname.' = S.'.$primarykeycolumnname.') WHEN MATCHED THEN UPDATE SET '.implode(',', $updates).' WHEN NOT MATCHED THEN INSERT ('.implode(',', $insertcolumns).') VALUES ('.implode(',', $insertvalues).')';
        $this->executeNoResultQuery($query);
	}

}
