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

    private $db = false;

    /**
     * Derived classes must open and return a database connection here.
     * Used in getDatabase() when no database connection is open.
     * 
     * @return object Handle to a database connection.
     */
    protected abstract function openDatabase();

    /**
     * Derived classes must escape and return the given string in that way,
     * that it can be used in SQL queries without the risk of SQL
     * injections.
     * 
     * @param string $string String to escape
     * @return string Escaped input string.
     */
    public abstract function escape($string);

    /**
     * Returns the current database connection. Opens a new connection with
     * openDatabase() whe no connection is open.
     * 
     * @return object Database reference. Type differs from database to
     * database (mysqli, oci, etc.)
     */
    protected function getDatabase() {
        if (!$this->db) {
            $this->db = $this->openDatabase();
        }
        return $this->db;
    }

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
    public function getAll($persistentobjectclass) {
        $tablename = $this->escape((new $persistentobjectclass())->tablename);
        $objs = $this->executeMultipleResultQuery('select * from '.$tablename);
        $result = array();
        foreach ($objs as $obj) {
            $result[] = $this->cast($obj, $persistentobjectclass);
        }
        return $result;
    }

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
    public function get($persistentobjectclass, $uuid) {
        $tablename = $this->escape((new $persistentobjectclass())->tablename);
        $obj = $this->executeSingleResultQuery('select * from '.$tablename.' where uuid=\''.$this->escape($uuid).'\'');
        return $this->cast($obj, $persistentobjectclass);
    }

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
    public static function delete(avorium_core_persistence_PersistentObject $persistentObject) {
        $tableName = $this->escape($persistentObject->tablename);
        $uuid = $this->escape($persistentObject->uuid);
        $this->executeNoResultQuery('delete from '.$tableName.' where uuid=\''.$uuid.'\'');
    }

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
    public function executeMultipleResultQuery($query, $persistentobjectclass = null) {
        $resultset = $this->getDatabase()->query($query);
        if (!is_a($resultset, 'mysqli_result')) {
            return array();
        }
        $result = array();
        while ($row = $resultset->fetch_object()) {
            $result[] = $persistentobjectclass !== null ? $this->cast($row, $persistentobjectclass) : $row;
        }
        return $result;
    }

    /**
     * Returns a single object from the given query or false, when no 
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
    public function executeSingleResultQuery($query, $persistentobjectclass = null) {
        $resultset = $this->getDatabase()->query($query);
        if (!$resultset || $resultset->num_rows < 1) {
            return false;
        }
        $result = $persistentobjectclass !== null ? $this->cast($resultset->fetch_object(), $persistentobjectclass) : $resultset->fetch_object();
        return $result;
    }

    /**
     * Executes the given query without returning a value.
     * 
     * @param string $query Query to execute. There is no check for
     * SQL injections. Caller has to make sure, that the query is correct.
     */
    public function executeNoResultQuery($query) {
        $this->getDatabase()->query($query);
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
     * as comparing with the source object!
     */
    public function cast($obj, $classname) {
            if (!$obj) {
                return false;
            }
            $metadata = avorium_core_persistence_helper_Annotation::getPersistableMetaData($classname);
            $result = new $classname();
            foreach ($obj as $key => $value) {
                if (!property_exists($result, $key)) {
                    continue; // Skip non existing properties
                }
                if ($metadata === null) {
                    $result->$key = $value;
                } else {
                    $result->$key = $this->castDatabaseValue($value, $metadata['properties'][$key]['type']);
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
	
}
