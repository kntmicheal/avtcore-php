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

require_once dirname(__FILE__).'/../code/avorium/core/persistence/AbstractPersistenceAdapter.php';
require_once dirname(__FILE__).'/AbstractPersistenceAdapterTestPersistentObject.php';
require_once dirname(__FILE__).'/AbstractPersistenceAdapterTestPersistentObjectWithIncompleteMetadata.php';
require_once dirname(__FILE__).'/AbstractPersistenceAdapterTestPersistentObjectWithoutMetadata.php';
require_once dirname(__FILE__).'/AbstractPersistenceAdapterTestNoPersistentObject.php';

/**
 * Tests the functionality of the persistence adapters. The tests are run
 * one-directional. That means that in reading tests only reading functions
 * will be tested. The data these tests obtain are put into the database
 * manually before. The same is with writing tests where the writing functions
 * are tested and the database is checked manually by the test.
 */
abstract class AbstractPersistenceAdapterTest extends PHPUnit_Framework_TestCase {
	
    // Positive tests

    /**
     * Tests creating a single record. Constructs a persistent object, stores
     * it into the database and checks whether the database contains the values
     */
    public function testCreateSingleRecord() {
        // Create record
        $po = new AbstractPersistenceAdapterTestPersistentObject();
        $po->booleanValue = true;
        $po->intValue = 2147483647;
        $po->stringValue = 'Hallo Welt!';
        // Store new record
        $this->getPersistenceAdapter()->save($po);
        // Get record back from database
        $result = $this->executeQuery('select * from potest where uuid=\''.$po->uuid.'\'');
        // Records must be unique
        $this->assertEquals($po->booleanValue, (bool)$result[0]['BOOLEAN_VALUE'], 'Boolean value from database differs from the boolean value of the persistent object.');
        $this->assertEquals($po->intValue, intval($result[0]['INT_VALUE']), 'Integer value from database differs from the int value of the persistent object.');
        $this->assertEquals($po->stringValue, $result[0]['STRING_VALUE'], 'String value from database differs from the string value of the persistent object.');
    }

    /**
     * Tests creating a single record, updating its values and then retreiving
     * the updated values back from the database.
     */
    public function testUpdateSingleRecord() {
        // Create record
        $po = new AbstractPersistenceAdapterTestPersistentObject();
        $po->booleanValue = true;
        $po->intValue = 2147483647;
        $po->stringValue = 'Hallo Welt!';
        // Store new record
        $this->getPersistenceAdapter()->save($po);
        // Remember uuid
        $uuid = $po->uuid;
        // Update values;
        $po->booleanValue = false;
        $po->intValue = -2147483646;
        $po->stringValue = 'Guten Morgen!';
        // Update record
        $this->getPersistenceAdapter()->save($po);
        // Get record back from database
        $result = $this->executeQuery('select * from potest where uuid=\''.$uuid.'\'');
        // Records must be unique
        $this->assertEquals($po->booleanValue, (bool)$result[0]['BOOLEAN_VALUE'], 'Boolean value from database differs from the boolean value of the persistent object.');
        $this->assertEquals($po->intValue, intval($result[0]['INT_VALUE']), 'Integer value from database differs from the int value of the persistent object.');
        $this->assertEquals($po->stringValue, $result[0]['STRING_VALUE'], 'String value from database differs from the string value of the persistent object.');
    }

    /**
     * Tests the reading and correct casting of a single persistent object
     * from the database
     */
    public function testReadSingleRecord() {
        $uuid = 'abcdefg';
        $bool = true;
        $int = 1234567;
        $string = 'avorium';
        // Write data to the database
        $this->executeQuery('insert into potest (uuid, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object
        $po = $this->getPersistenceAdapter()->get('AbstractPersistenceAdapterTestPersistentObject', $uuid);
        // Compare properties
        $this->assertEquals($uuid, $po->uuid, 'Uuid value from persistent object differs from the uuid value of the database.');
        $this->assertEquals($bool, $po->booleanValue, 'Boolean value from persistent object differs from the boolean value of the database.');
        $this->assertEquals($int, $po->intValue, 'Integer value from persistent object differs from the int value of the database.');
        $this->assertEquals($string, $po->stringValue, 'String value from persistent object differs from the string value of the database.');
    }

    /**
     * Tests the deletion of a single record by inserting it manually, checking 
     * whether it was stored into the database and then deleting it and trying
     * to get it again.
     */
    public function testDeleteSingleRecord() {
        $uuid = 'abcdefg';
        $bool = true;
        $int = 1234567;
        $string = 'avorium';
        // Write data to the database
        $this->executeQuery('insert into potest (uuid, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object
        $po = $this->getPersistenceAdapter()->get('AbstractPersistenceAdapterTestPersistentObject', $uuid);
        $this->assertNotNull($po, 'Persistent object was not stored in database.');
        // Delete persistent object and try to read it out again
        $this->getPersistenceAdapter()->delete($po);
        $podeleted = $this->getPersistenceAdapter()->get('AbstractPersistenceAdapterTestPersistentObject', $uuid);
        $this->assertNull($podeleted, 'Persistent object was not deleted.');
    }
    
    /**
     * Tests the reading of multiple records from the database with getAll().
     */
    public function testReadMultipleRecords() {
        $records = [
            ['uuid' => 'uuid1tRMR1', 'bool' => 0, 'int' => 10, 'string' => 'testReadMultipleRecords 1'],
            ['uuid' => 'uuid1tRMR2', 'bool' => 1, 'int' => 20, 'string' => 'testReadMultipleRecords 2'],
            ['uuid' => 'uuid1tRMR3', 'bool' => 0, 'int' => 30, 'string' => 'testReadMultipleRecords 3']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->executeQuery('insert into potest (uuid, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['uuid'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        }
        // Read data out and cast it to persistent object
        $pos = $this->getPersistenceAdapter()->getAll('AbstractPersistenceAdapterTestPersistentObject');
        $this->assertEquals(count($records), count($pos), 'Wrong number of database records found.');
        for($i = 0; $i < count($pos); $i++)  {
            $this->assertEquals($records[$i]['uuid'], $pos[$i]->uuid, 'Uuid value from persistent object differs from the uuid value of the database.');
            $this->assertEquals($records[$i]['bool'], $pos[$i]->booleanValue, 'Boolean value from persistent object differs from the boolean value of the database.');
            $this->assertEquals($records[$i]['int'], $pos[$i]->intValue, 'Integer value from persistent object differs from the int value of the database.');
            $this->assertEquals($records[$i]['string'], $pos[$i]->stringValue, 'String value from persistent object differs from the string value of the database.');
        }
    }
    
    /**
     * Tests the correct behaviour of persistent objects with 
     * incomplete annotations.
     */
    public function testIncompleteMetadata() {
        $uuid = 'abcdefg';
        $bool = true;
        $int = 1234567;
        $string = 'avorium';
        // Write data to the database
        $this->executeQuery('insert into potest (uuid, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object with incomplete metadata
        $po = $this->getPersistenceAdapter()->get('AbstractPersistenceAdapterTestPersistentObjectWithIncompleteMetadata', $uuid);
        // Compare properties
        // UUID must be there
        $this->assertEquals($uuid, $po->uuid, 'Uuid value from persistent object differs from the uuid value of the database.');
        // BOOLEAN_VALUE must not be set correctly because annotation is incomplete
        $this->assertNotEquals($bool, $po->BOOLEAN_VALUE, 'Boolean value from persistent object differs from the boolean value of the database.');
        // INT_VALUE must not be set correctly there because this property has no annotation
        $this->assertNotEquals($int, $po->INT_VALUE, 'Integer value from persistent object is the same as in database but must not be.');
        // stringValue must not be set, because there is no matching table column even with an annotation
        $this->assertNotEquals($string, $po->stringValue, 'String value from persistent object is the same as in database but must not be.');
    }
    
    /**
     * Tests the correct behaviour of persistent objects with 
     * incomplete annotations.
     */
    public function testMissingMetadata() {
        $uuid = 'abcdefg';
        $bool = true;
        $int = 1234567;
        $string = 'avorium';
        // Write data to the database
        $this->executeQuery('insert into potest (uuid, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object with incomplete metadata
        $po = $this->getPersistenceAdapter()->get('AbstractPersistenceAdapterTestPersistentObjectWithoutMetadata', $uuid);
        // Result must be null here. It should not be possible to match to an class without knowing from which table to extract the date
        $this->assertNull($po, 'Result is not null. Where does the system know which table I want to read out?');
    }
    
    /**
     * Tests the reading of data from a database vie get() when given class is
     * not a persistent object class.
     */
    public function testGetNoPersistentObject() {
        $uuid = 'abcdefg';
        $bool = true;
        $int = 1234567;
        $string = 'avorium';
        // Write data to the database
        $this->executeQuery('insert into potest (uuid, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object with incomplete metadata
        $this->setExpectedException('Exception', 'The given class is not derived from avorium_core_persistence_PersistentObject. But this is needed to extract the table name!');
        $this->getPersistenceAdapter()->get('AbstractPersistenceAdapterTestNoPersistentObject', $uuid);
    }
    
    /**
     * Tests the reading of data from a database vie getAll() when given class is
     * not a persistent object class.
     */
    public function testGetAllNoPersistentObject() {
        $records = [
            ['uuid' => 'uuid1tRMR1', 'bool' => 0, 'int' => 10, 'string' => 'testReadMultipleRecords 1'],
            ['uuid' => 'uuid1tRMR2', 'bool' => 1, 'int' => 20, 'string' => 'testReadMultipleRecords 2'],
            ['uuid' => 'uuid1tRMR3', 'bool' => 0, 'int' => 30, 'string' => 'testReadMultipleRecords 3']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->executeQuery('insert into potest (uuid, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['uuid'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        }
        // Read data out and cast it to persistent object with incomplete metadata
        $this->setExpectedException('Exception', 'The given class is not derived from avorium_core_persistence_PersistentObject. But this is needed to extract the table name!');
        $this->getPersistenceAdapter()->getAll('AbstractPersistenceAdapterTestNoPersistentObject');
    }
    
    
    /**
     * Tests saving a single record which has null values in properties.
     */
    public function testSaveNullValues() {
        // Create record
        $po = new AbstractPersistenceAdapterTestPersistentObject();
        $po->booleanValue = null;
        $po->intValue = null;
        $po->stringValue = null;
        // Store new record
        $this->getPersistenceAdapter()->save($po);
        // Get record back from database
        $result = $this->executeQuery('select * from potest where uuid=\''.$po->uuid.'\'');
        // Records must be unique
        $this->assertEquals($po->booleanValue, (bool)$result[0]['BOOLEAN_VALUE'], 'Boolean value from database differs from the boolean value of the persistent object.');
        $this->assertEquals($po->intValue, intval($result[0]['INT_VALUE']), 'Integer value from database differs from the int value of the persistent object.');
        $this->assertEquals($po->stringValue, $result[0]['STRING_VALUE'], 'String value from database differs from the string value of the persistent object.');
    }

    /**
     * Tests the creation of a table for a persistent object
     */
    public function testCreateTable() {
        // Drop table
        $this->executeQuery('drop table potest');
        // Automatically create table
        $this->getPersistenceAdapter()->updateOrCreateTable('AbstractPersistenceAdapterTestPersistentObject');
        // Insert test data
        $uuid = 'abcdefg';
        $bool = true;
        $int = 1234567;
        $string = 'avorium';
        // Write data to the database
        $this->executeQuery('insert into potest (uuid, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object
        $po = $this->getPersistenceAdapter()->get('AbstractPersistenceAdapterTestPersistentObject', $uuid);
        // Compare properties
        $this->assertEquals($uuid, $po->uuid, 'Uuid value from persistent object differs from the uuid value of the database.');
        $this->assertEquals($bool, $po->booleanValue, 'Boolean value from persistent object differs from the boolean value of the database.');
        $this->assertEquals($int, $po->intValue, 'Integer value from persistent object differs from the int value of the database.');
        $this->assertEquals($string, $po->stringValue, 'String value from persistent object differs from the string value of the database.');
    }

    /**
     * Tests the update of a table for a persistent object
     */
    public function testUpdateTable() {
        // Drop table
        $this->executeQuery('drop table potest');
        // Manually create table with UUID column only
        $this->executeQuery('create table potest (uuid NVARCHAR(40) NOT NULL, PRIMARY KEY (uuid))');
        // Automatically update table
        $this->getPersistenceAdapter()->updateOrCreateTable('AbstractPersistenceAdapterTestPersistentObject');
        // Insert test data
        $uuid = 'abcdefg';
        $bool = true;
        $int = 1234567;
        $string = 'avorium';
        // Write data to the database
        $this->executeQuery('insert into potest (uuid, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object
        $po = $this->getPersistenceAdapter()->get('AbstractPersistenceAdapterTestPersistentObject', $uuid);
        // Compare properties
        $this->assertEquals($uuid, $po->uuid, 'Uuid value from persistent object differs from the uuid value of the database.');
        $this->assertEquals($bool, $po->booleanValue, 'Boolean value from persistent object differs from the boolean value of the database.');
        $this->assertEquals($int, $po->intValue, 'Integer value from persistent object differs from the int value of the database.');
        $this->assertEquals($string, $po->stringValue, 'String value from persistent object differs from the string value of the database.');
    }
    
    // Tabellen anlegen
    // Tabellen erweitern
    
    // 
    // Multi-Daten-SQL mit persistenten Objekten ausführen und dabei Casting prüfen
    // Single-Daten-SQL mit persistenten Objekten ausführen
    // Multi-Daten-SQL ohne persistente Objekten ausführen
    // Single-Daten-SQL ohne persistente Objekten ausführen
    // SQL ohne Rückgabe ausführen
    // Einfache Werte aus Datenbank lesen und casten

    // Negativtests

    // Auslesen von einzelnen Datensätzen, deren UUID nicht in Datenbank ist
    // Tabellen erweitern, indem Datentypen von Spalten verändert werden, soll nicht möglich sein
    // Casten von Datentypen, die der Datenbank unbekannt sind
    // Casten von Datentypen, die dem Code unbekannt sind
    // Verwendung von Sonderzeichen
    // SQL-Injection
    // Fehlerhafte Datenbankverbindung
    // Fehlerhafte SQL-Statements
    // SQL-Statements, die nicht zur Abfrageart passen (Multi vs. Single vs. Einfacher Wert)
    // Strings mit unterschiedlicher Länge in Datenbank (40, 255, 65535 Zeichen)


    /**
     * Persistence adapter to use in test. Must be set in the setUp()-
     * Method of the database specific test class (e.g. 
     * MySqlPersistenceAdapterTest)
     */
    protected $persistenceAdapter = null;

    /**
     * Returns the current persistence adapter or throws an exception,
     * when no adapter was set.
     * @return avorium_core_persistence_IPersistenceAdapter Used persistence adapter
     */
    protected function getPersistenceAdapter() {
        if ($this->persistenceAdapter !== null) {
            return $this->persistenceAdapter;
        }
        throw new Exception('No persistence adapter set. This must be done in the setUp() function of a test class');
    }

    /**
     * Executes the given query and returns the result. Called by this abstract
     * class within tests to directly obtain database results without the use
     * of persistence adapters because the adapters are to be tested. Derived
     * classes must return a two dimensional array (fetch_array()) which can 
     * be empty.
     */
    protected abstract function executeQuery($query);	
}
