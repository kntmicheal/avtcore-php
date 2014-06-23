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

/**
 * Base class for all database adapters.
 */
abstract class avorium_core_persistence_AbstractPersistenceAdapter {

    /**
     * Returns all entries of a database table as array of objects where
     * the object fields have the name of the columns.
     * Can be an empty array, when no results were found.
     * resultset->fetch_object is used.
     * 
     * @param string $persistentobjectclass Class name of the persistent
     * object class. In this class the return values are casted into.
     * @return array Array of persistent objects casted into the given 
     * class.
     */
    public abstract function getAll($persistentobjectclass);

    /**
     * Returns a data object from the given table with the given uuid.
     * Can be false when no object is found. Uses fetch_object.
     * 
     * @param string $persistentobjectclass Class name of the persistent
     * object class. In this class the return value is casted into.
     * @param string $uuid Unique identifier of the wanted persistent 
     * object
     * @return object Persistent object casted into the given class.
     */
    public abstract function get($persistentobjectclass, $uuid);

    /**
     * Stores the given object with its fields into the database.
     * 
     * @param avorium_core_persistence_PersistentObject $persistentObject
     * Persistent object to store (insert or update).
     */
    public abstract function save(avorium_core_persistence_PersistentObject $persistentObject);

    /**
     * Deletes the given persistent object from the database by using
     * its uuid.
     * 
     * @param avorium_core_persistence_PersistentObject $persistentObject
     */
    public abstract function delete(avorium_core_persistence_PersistentObject $persistentObject);
    
    /**
     * Returns an array of objects from the given query. When a persistent
     * object class is given, the array elements are casted into it.
     * 
     * @param string $query SQL query to execute. There is no check for
     * SQL injections. Caller has to make sure, that the query is correct.
     * @param string $persistentobjectclass Name of the persistent object 
     * class the results should be casted into.
     * @return array Array of persistent objects, either as simple objects
     * (fetch_object()) or casted into the given class.
     */
    public abstract function executeMultipleResultQuery($query, $persistentobjectclass = null);
	
    /**
     * Returns a single object from the given query or null, when no 
     * result was found. When a persistent object class is given, the 
     * result is casted into it.
     * 
     * @param string $query SQL query to execute. There is no check for
     * SQL injections. Caller has to make sure, that the query is correct.
     * @param string $persistentobjectclass Name of the persistent object 
     * class the result should be casted into.
     * @return object Persistent object, either as simple object
     * (fetch_object()) or casted into the given class.
     */
    public abstract function executeSingleResultQuery($query, $persistentobjectclass = null);
	
    /**
     * Executes the given query without returning a value.
     * 
     * @param string $query Query to execute. There is no check for
     * SQL injections. Caller has to make sure, that the query is correct.
     */
    public abstract function executeNoResultQuery($query);    
    /**
     * Analyzes the given metadata structure and tries to find the property
     * name for a given table column name. Returns null when any problem
     * occurs.
     * 
     * @param array $metadata Metadata to analyze
     * @param string $metaname Table column name to find a property name for
     * @return string Found property name or content of $metaname
     */
    private function findPropertyNameForMetaName($metadata, $metaname) {
        foreach ($metadata['properties'] as $key => $value) {
			// ORACLE normally returns all column namens in uppercase when column names are not case sensitive 
            if (!is_null($value) && isset($value['name']) && strtoupper($value['name']) === strtoupper($metaname)) {
                return $key;
            }
        }
        return null;
    }

    /**
     * Casts the given object from the database into the given class by copying
     * the property contents from the object to a new object of the target
     * type. With this the properties get their correct datatypes depending
     * on the peristent object class.
     * 
     * @param object $obj Object to cast.
     * @param string $classname Name of the class to cast the object into.
     * @return object Object casted into the given class name. The returned
     * object is a new one, so comparing with == will not be the same
     * as comparing with the source object! Returns null, 
     * when the given object is not an object
     */
    protected function cast($obj, $classname) {
        $metadata = avorium_core_persistence_helper_Annotation::getPersistableMetaData($classname);
        $result = new $classname();
        foreach ($obj as $key => $value) {
            $propertyName = $this->findPropertyNameForMetaName($metadata, $key);
            if (!is_null($propertyName)) {
                if (!isset($metadata['properties'][$propertyName]['type'])) {
                    throw new Exception('Type of persistent object property not set.');
                }
				$result->$propertyName = $this->castDatabaseValue($value, $metadata['properties'][$propertyName]['type']);
            }
        }
        return $result;
    }

    /**
     * Derived classes must cast the given value coming from the database
     * into the given type and return it.
     * 
     * @param object $value Value to cast.
     * @param string $metatype Name of the type to cast into. Can be 'bool',
     * 'int' or any other (which should be casted into a string).
     * @return object Correct casted value.
     */
    protected abstract function castDatabaseValue($value, $metatype);

    /**
     * Derived classes should analyze the given persistent object class by
     * its annotations and should cretae a database table or update its schema
     * to match the given persistent object class structure. Normally used
     * in update scripts when the structure of persistent objects have changed.
     * 
     * @param name $persistentobjectclass Name of the persistent object class
     * to create or update a table for.
     */
    public abstract function updateOrCreateTable($persistentobjectclass);
	
	/**
	 * Executes the query which should be a multiple results query and creates
	 * a datatable from the result. When the query is invalid or the query does
	 * not return a result with at least one row, an exception is thrown.
	 * When the query returns multiple columns with the same name, the datatable
	 * will only contain one column with that name (the last occurence).
	 * The order of rows and columns in the datatable is exactly the same as
	 * the order of the query result.
	 * The header names of the datatable are set to the column namens the query
	 * returns.
	 * The values are all strings, independent on the database datatypes.
	 * 
	 * @param string $query Multiple result query to execute.
	 * @return avorium_core_data_DataTable Datatable with at least one row and 
	 * column containing the results from the query.
	 */
	public function getDataTable($query) {
		// Check query parameter
		if (is_null($query)) {
			throw new Exception('The query must not be null.');
		}
		$records = $this->executeMultipleResultQuery($query);
		$rowcount = count($records);
		// Extract headers
		$headernames = array_keys(get_object_vars($records[0]));
		$columncount = count($headernames);
		$datatable = new avorium_core_data_DataTable($rowcount, $columncount);
		for ($i = 0; $i < $columncount; $i++) {
			$datatable->setHeader($i, $headernames[$i]);
		}
		for ($i = 0; $i < $rowcount; $i++) {
			$row = get_object_vars($records[$i]);
			for ($j = 0; $j < $columncount; $j++) {
				$datatable->setCellValue($i, $j, $row[$headernames[$j]]);
			}
		}
		return $datatable;
	}
	
	/**
	 * Derived classes have to analyze the datatable, validate its content and
	 * store it into the database in the way "all or nothing". That means, if
	 * there is a problem with a single value of the datatable, no data has to
	 * be stored to the database. So the adapter should use bulk queries and 
	 * should not put the values row by row into the database.
	 * Rows which have no primary key in the database (new ones) should be
	 * inserted as new rows and rows for which a dataset with a primary key
	 * already exists should update this dataset, but only the columns defined
	 * in the datatable.
	 * When the datatable contains no primary key column, the datatable cannot
	 * be stored in the database because there is no mechanism for automatically
	 * creating primary keys (not all tables have an auto increment column).
	 * 
	 * @param string $tablename Name of the database table where to store the
	 * values into.
	 * @param avorium_core_data_DataTable $datatable Datatable to store into the
	 * database.
	 */
	public abstract function saveDataTable($tablename, $datatable);
	
}
