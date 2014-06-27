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

require_once dirname(__FILE__).'/../../code/avorium/core/persistence/AbstractPersistenceAdapter.php';
require_once dirname(__FILE__).'/AbstractPersistenceAdapterTestPersistentObject.php';
require_once dirname(__FILE__).'/AbstractPersistenceAdapterTestPersistentObjectWithIncompleteMetadata.php';
require_once dirname(__FILE__).'/AbstractPersistenceAdapterTestPersistentObjectWithoutMetadata.php';
require_once dirname(__FILE__).'/AbstractPersistenceAdapterTestNoPersistentObject.php';
require_once dirname(__FILE__).'/AbstractPersistenceAdapterTestPersistentObjectDifferentType.php';
require_once dirname(__FILE__).'/AbstractPersistenceAdapterTestPersistentObjectUnknownType.php';
require_once dirname(__FILE__).'/AbstractPersistenceAdapterTestPersistentObjectNoType.php';
require_once dirname(__FILE__).'/../../code/avorium/core/data/DataTable.php';

/**
 * Tests the functionality of the persistence adapters. The tests are run
 * one-directional. That means that in reading tests only reading functions
 * will be tested. The data these tests obtain are put into the database
 * manually before. The same is with writing tests where the writing functions
 * are tested and the database is checked manually by the test.
 */
abstract class test_persistence_AbstractPersistenceAdapterTest extends PHPUnit_Framework_TestCase {
	
	
	/**
	 * Derived test classes must do the following steps:
	 * - Initialize $this->persistenceAdapter with a valid database specific persistence adapter
	 * - Drop and recreate the database table POTEST with following columns
	 *   - UUID (string with 40 characters, primary key)
	 *   - BOOLEAN_VALUE (bit)
	 *   - INT_VALUE (32 bit integer)
	 *   - STRING_VALUE (string with 255 characters)
	 *   - DECIMAL_VALUE (decimal with 30 digits, 10 of them after the decimal point)
	 *   - DOUBLE_VALUE (64 bit double as in IEEE 754, 2.2251E-308 to 1.798E+308 positive and negative)
	 *   - TEXT_VALUE (string with 4000 characters, limited due to ORACLE limits)
	 *   - DATETIME_VALUE (column which can store date and time values between 1900-01-01 00:00:00 and 2999-12-31 23:59:59, prcision to seconds, 1900-01-01 00:00:00 is handled as "UNDEFINED")
	 */
	protected function setUp() {
        parent::setUp();
	}

    // Positive tests

    /**
     * Tests creating a single record. Constructs a persistent object, stores
     * it into the database and checks whether the database contains the values
     */
    public function testCreateSingleRecord() {
        // Create record
        $po = new test_persistence_AbstractPersistenceAdapterTestPersistentObject();
        $po->booleanValue = true;
        $po->intValue = 2147483647;
        $po->stringValue = 'Hallo Welt!';
        // Store new record
        $this->getPersistenceAdapter()->save($po);
        // Get record back from database
        $result = $this->executeQuery('select * from POTEST where UUID=\''.$po->uuid.'\'');
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
        $po = new test_persistence_AbstractPersistenceAdapterTestPersistentObject();
        $po->booleanValue = true;
        $po->intValue = 2147483647;
        $po->stringValue = 'Hallo Welt!';
        // Store new record
        $this->getPersistenceAdapter()->save($po);
        // Remember UUID
        $uuid = $po->uuid;
        // Update values;
        $po->booleanValue = false;
        $po->intValue = -2147483646;
        $po->stringValue = 'Guten Morgen!';
        // Update record
        $this->getPersistenceAdapter()->save($po);
        // Get record back from database
        $result = $this->executeQuery('select * from POTEST where UUID=\''.$uuid.'\'');
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
        $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object
        $po = $this->getPersistenceAdapter()->get('test_persistence_AbstractPersistenceAdapterTestPersistentObject', $uuid);
        // Compare properties
        $this->assertEquals($uuid, $po->uuid, 'Uuid value from persistent object differs from the UUID value of the database.');
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
        $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object
        $po = $this->getPersistenceAdapter()->get('test_persistence_AbstractPersistenceAdapterTestPersistentObject', $uuid);
        $this->assertNotNull($po, 'Persistent object was not stored in database.');
        // Delete persistent object and try to read it out again
        $this->getPersistenceAdapter()->delete($po);
        $podeleted = $this->getPersistenceAdapter()->get('test_persistence_AbstractPersistenceAdapterTestPersistentObject', $uuid);
        $this->assertNull($podeleted, 'Persistent object was not deleted.');
    }
    
    /**
     * Tests the reading of multiple records from the database with getAll().
     */
    public function testReadMultipleRecords() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testReadMultipleRecords 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testReadMultipleRecords 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testReadMultipleRecords 3']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        }
        // Read data out and cast it to persistent object
        $pos = $this->getPersistenceAdapter()->getAll('test_persistence_AbstractPersistenceAdapterTestPersistentObject');
        $this->assertEquals(count($records), count($pos), 'Wrong number of database records found.');
        for($i = 0; $i < count($pos); $i++)  {
            $this->assertEquals($records[$i]['UUID'], $pos[$i]->uuid, 'Uuid value from persistent object differs from the UUID value of the database.');
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
        $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object with incomplete metadata
        $po = $this->getPersistenceAdapter()->get('test_persistence_AbstractPersistenceAdapterTestPersistentObjectWithIncompleteMetadata', $uuid);
        // Compare properties
        // UUID must be there
        $this->assertEquals($uuid, $po->uuid, 'Uuid value from persistent object differs from the UUID value of the database.');
        // BOOLEAN_VALUE must not be set correctly because annotation is incomplete
        $this->assertNotEquals($bool, $po->BOOLEAN_VALUE, 'Boolean value from persistent object differs from the boolean value of the database.');
        // INT_VALUE must not be set correctly there because this property has no annotation
        $this->assertNotEquals($int, $po->INT_VALUE, 'Integer value from persistent object is the same as in database but must not be.');
        // stringValue must not be set, because there is no matching table column even with an annotation
        $this->assertNotEquals($string, $po->stringValue, 'String value from persistent object is the same as in database but must not be.');
    }
    
    /**
     * Tests the correct behaviour of the get() function with persistent objects 
     * with missing table name annotation.
     */
    public function testGetMissingMetadata() {
        $uuid = 'abcdefg';
        $bool = true;
        $int = 1234567;
        $string = 'avorium';
        // Write data to the database
        $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object with incomplete metadata
        $this->setExpectedException('Exception', 'The table name of the persistent object could not be determined.');
        // Here should come up an exception. It should not be possible to match to an class without knowing from which table to extract the date
        $this->getPersistenceAdapter()->get('test_persistence_AbstractPersistenceAdapterTestPersistentObjectWithoutMetadata', $uuid);
    }
    
    /**
     * Tests the correct behaviour of the getAll() function with persistent objects 
     * with missing table name annotation.
     */
    public function testGetAllMissingMetadata() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testReadMultipleRecords 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testReadMultipleRecords 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testReadMultipleRecords 3']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        }
        // Read data out and cast it to persistent object with incomplete metadata
        $this->setExpectedException('Exception', 'The table name of the persistent object could not be determined.');
        // Here should come up an exception. It should not be possible to match to an class without knowing from which table to extract the date
        $this->getPersistenceAdapter()->getAll('test_persistence_AbstractPersistenceAdapterTestPersistentObjectWithoutMetadata');
    }
    
    /**
     * Tests the correct behaviour of the executeSingleResultQuery() function 
     * with persistent objects with incomplete annotations.
     */
    public function testSingleResultQueryMissingMetadata() {
        $uuid = 'abcdefg';
        $bool = true;
        $int = 1234567;
        $string = 'avorium';
        // Write data to the database
        $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object with incomplete metadata
        // Here should come up an exception. It should not be possible to match to an class without knowing from which table to extract the date
        $query = 'select * from POTEST where UUID = \''.$uuid.'\'';
        $po = $this->getPersistenceAdapter()->executeSingleResultQuery($query, 'test_persistence_AbstractPersistenceAdapterTestNoPersistentObject');
        // Should return a result because when casting to an object the missing class metadata is irrelevant
        $this->assertNotNull($po, 'The result is null.');
    }
    
    /**
     * Tests the correct behaviour of the executeMultipleResultQuery() function
     * with persistent objects with incomplete annotations.
     */
    public function testMultipleResultMissingMetadata() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testReadMultipleRecords 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testReadMultipleRecords 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testReadMultipleRecords 3']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        }
        $query = 'select * from POTEST where UUID in (\'uuid1tRMR1\',\'uuid1tRMR2\',\'uuid1tRMR3\')';
        $pos = $this->getPersistenceAdapter()->executeMultipleResultQuery($query, 'test_persistence_AbstractPersistenceAdapterTestNoPersistentObject');
        // Should return results because when casting to an object the missing class metadata is irrelevant
        $this->assertEquals(count($records), count($pos), 'Wrong number of database records found.');
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
        $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object with incomplete metadata
        $this->setExpectedException('Exception', 'The given class is not derived from avorium_core_persistence_PersistentObject. But this is needed to extract the table name!');
        $this->getPersistenceAdapter()->get('test_persistence_AbstractPersistenceAdapterTestNoPersistentObject', $uuid);
    }
    
    /**
     * Tests the reading of data from a database vie getAll() when given class is
     * not a persistent object class.
     */
    public function testGetAllNoPersistentObject() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testReadMultipleRecords 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testReadMultipleRecords 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testReadMultipleRecords 3']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        }
        // Read data out and cast it to persistent object with incomplete metadata
        $this->setExpectedException('Exception', 'The given class is not derived from avorium_core_persistence_PersistentObject. But this is needed to extract the table name!');
        $this->getPersistenceAdapter()->getAll('test_persistence_AbstractPersistenceAdapterTestNoPersistentObject');
    }
    
    
    /**
     * Tests saving a single record which has null values in properties.
     */
    public function testSaveNullValues() {
        // Create record
        $po = new test_persistence_AbstractPersistenceAdapterTestPersistentObject();
        $po->booleanValue = null;
        $po->intValue = null;
        $po->stringValue = null;
        // Store new record
        $this->getPersistenceAdapter()->save($po);
        // Get record back from database
        $result = $this->executeQuery('select * from POTEST where UUID=\''.$po->uuid.'\'');
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
        $this->executeQuery('drop table POTEST');
        // Automatically create table
        $this->getPersistenceAdapter()->updateOrCreateTable('test_persistence_AbstractPersistenceAdapterTestPersistentObject');
        // Insert test data
        $uuid = 'abcdefg';
        $bool = true;
        $int = 1234567;
        $string = 'avorium';
        // Write data to the database
        $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object
        $po = $this->getPersistenceAdapter()->get('test_persistence_AbstractPersistenceAdapterTestPersistentObject', $uuid);
        // Compare properties
        $this->assertEquals($uuid, $po->uuid, 'Uuid value from persistent object differs from the UUID value of the database.');
        $this->assertEquals($bool, $po->booleanValue, 'Boolean value from persistent object differs from the boolean value of the database.');
        $this->assertEquals($int, $po->intValue, 'Integer value from persistent object differs from the int value of the database.');
        $this->assertEquals($string, $po->stringValue, 'String value from persistent object differs from the string value of the database.');
    }
	
	/**
	 * Derived classes must create a test table here.
	 */
	protected abstract function createTestTable();

    /**
     * Tests the update of a table for a persistent object
     */
    public function testUpdateTable() {
        // Drop table
        $this->executeQuery('drop table POTEST');
        // Manually create table with UUID column only
		$this->createTestTable();
        // Automatically update table
        $this->getPersistenceAdapter()->updateOrCreateTable('test_persistence_AbstractPersistenceAdapterTestPersistentObject');
        // Insert test data
        $uuid = 'abcdefg';
        $bool = true;
        $int = 1234567;
        $string = 'avorium';
        // Write data to the database
        $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object
        $po = $this->getPersistenceAdapter()->get('test_persistence_AbstractPersistenceAdapterTestPersistentObject', $uuid);
        // Compare properties
        $this->assertEquals($uuid, $po->uuid, 'Uuid value from persistent object differs from the UUID value of the database.');
        $this->assertEquals($bool, $po->booleanValue, 'Boolean value from persistent object differs from the boolean value of the database.');
        $this->assertEquals($int, $po->intValue, 'Integer value from persistent object differs from the int value of the database.');
        $this->assertEquals($string, $po->stringValue, 'String value from persistent object differs from the string value of the database.');
    }

    /**
     * Tests the executeMultipleResultQuery() function
     */
    public function testMultipleResultQuery() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testReadMultipleRecords 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testReadMultipleRecords 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testReadMultipleRecords 3']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        }
        // Read data out and cast it to persistent object
        $query = 'select * from POTEST where UUID in (\'uuid1tRMR1\',\'uuid1tRMR2\',\'uuid1tRMR3\')';
        $pos = $this->getPersistenceAdapter()->executeMultipleResultQuery($query, 'test_persistence_AbstractPersistenceAdapterTestPersistentObject');
        $this->assertEquals(count($records), count($pos), 'Wrong number of database records found.');
        for($i = 0; $i < count($pos); $i++)  {
            $this->assertEquals($records[$i]['UUID'], $pos[$i]->uuid, 'Uuid value from persistent object differs from the UUID value of the database.');
            $this->assertEquals($records[$i]['bool'], $pos[$i]->booleanValue, 'Boolean value from persistent object differs from the boolean value of the database.');
            $this->assertEquals($records[$i]['int'], $pos[$i]->intValue, 'Integer value from persistent object differs from the int value of the database.');
            $this->assertEquals($records[$i]['string'], $pos[$i]->stringValue, 'String value from persistent object differs from the string value of the database.');
        }
    }
    
    /**
     * Tests the executeSingleResultQuery() function
     */
    public function testSingleResultQuery() {
        $uuid = 'abcdefg';
        $bool = true;
        $int = 1234567;
        $string = 'avorium';
        // Write data to the database
        $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object
        $query = 'select * from POTEST where UUID = \'abcdefg\'';
        $po = $this->getPersistenceAdapter()->executeSingleResultQuery($query, 'test_persistence_AbstractPersistenceAdapterTestPersistentObject');
        // Compare properties
        $this->assertEquals($uuid, $po->uuid, 'Uuid value from persistent object differs from the UUID value of the database.');
        $this->assertEquals($bool, $po->booleanValue, 'Boolean value from persistent object differs from the boolean value of the database.');
        $this->assertEquals($int, $po->intValue, 'Integer value from persistent object differs from the int value of the database.');
        $this->assertEquals($string, $po->stringValue, 'String value from persistent object differs from the string value of the database.');
    }

    /**
     * Tests the executeMultipleResultQuery() function without using persistent objects
     */
    public function testMultipleResultQueryWithoutPersistentObject() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'BOOL' => 0, 'INT' => 10, 'STRING' => 'testReadMultipleRecords 1'],
            ['UUID' => 'uuid1tRMR2', 'BOOL' => 1, 'INT' => 20, 'STRING' => 'testReadMultipleRecords 2'],
            ['UUID' => 'uuid1tRMR3', 'BOOL' => 0, 'INT' => 30, 'STRING' => 'testReadMultipleRecords 3']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['BOOL'] ? 1:0).','.$record['INT'].', \''.$record['STRING'].'\')');
        }
        // Read data out
        $query = 'select * from POTEST where UUID in (\'uuid1tRMR1\',\'uuid1tRMR2\',\'uuid1tRMR3\')';
        $pos = $this->getPersistenceAdapter()->executeMultipleResultQuery($query);
        $this->assertEquals(count($records), count($pos), 'Wrong number of database records found.');
        for($i = 0; $i < count($pos); $i++)  {
            $this->assertEquals($records[$i]['UUID'], $pos[$i]->UUID, 'Uuid value from persistent object differs from the UUID value of the database.');
            $this->assertEquals($records[$i]['BOOL'], $pos[$i]->BOOLEAN_VALUE, 'Boolean value from persistent object differs from the boolean value of the database.');
            $this->assertEquals($records[$i]['INT'], $pos[$i]->INT_VALUE, 'Integer value from persistent object differs from the int value of the database.');
            $this->assertEquals($records[$i]['STRING'], $pos[$i]->STRING_VALUE, 'String value from persistent object differs from the string value of the database.');
        }
    }
    
    /**
     * Tests the executeSingleResultQuery() function without using persistent objects
     */
    public function testSingleResultQueryWithoutPersistentObject() {
        $uuid = 'abcdefg';
        $bool = true;
        $int = 1234567;
        $string = 'avorium';
        // Write data to the database
        $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object
        $query = 'select * from POTEST where UUID = \'abcdefg\'';
        $po = $this->getPersistenceAdapter()->executeSingleResultQuery($query);
        // Compare properties
        $this->assertEquals($uuid, $po->UUID, 'Uuid value from persistent object differs from the UUID value of the database.');
        $this->assertEquals($bool, $po->BOOLEAN_VALUE, 'Boolean value from persistent object differs from the boolean value of the database.');
        $this->assertEquals($int, $po->INT_VALUE, 'Integer value from persistent object differs from the int value of the database.');
        $this->assertEquals($string, $po->STRING_VALUE, 'String value from persistent object differs from the string value of the database.');
    }

    /**
     * Tests the executeNoResultQuery() function
     */
    public function testNoResultQuery() {
        $uuid = 'abcdefg';
        $bool = true;
        $int = 1234567;
        $string = 'avorium';
        // Store new record with executeNoResultQuery()
        $query = 'insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')';
        $this->getPersistenceAdapter()->executeNoResultQuery($query);
        // Get record back from database
        $result = $this->executeQuery('select * from POTEST where UUID=\''.$uuid.'\'');
        // Records must be unique
        $this->assertEquals($uuid, $result[0]['UUID'], 'Uuid value from persistent object differs from the UUID value of the database.');
        $this->assertEquals($bool, (bool)$result[0]['BOOLEAN_VALUE'], 'Boolean value from persistent object differs from the boolean value of the database.');
        $this->assertEquals($int, intval($result[0]['INT_VALUE']), 'Integer value from persistent object differs from the int value of the database.');
        $this->assertEquals($string, $result[0]['STRING_VALUE'], 'String value from persistent object differs from the string value of the database.');
    }

    // negative tests
    
    /**
     * Try to read a record via get() whose UUID is not in database
     */
    public function testGetSingleNonExistingRecord() {
        $uuid = 'abcdefg';
        $readuuid = 'hijklm';
        $bool = true;
        $int = 1234567;
        $string = 'avorium';
        // Write data to the database
        $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object
        $po = $this->getPersistenceAdapter()->get('test_persistence_AbstractPersistenceAdapterTestPersistentObject', $readuuid);
        // Compare properties
        $this->assertNull($po, 'Database should not return a result when requesting an unknown UUID.');
    }
    
    /**
     * Tests the update of table column definitions by changing the datatype.
     * In this case an exception should be thrown-
     */
    public function testChangeColumnDefinition() {
        // Automatically update table with persistent object with different type
        $this->setExpectedException('Exception', 'Changing the column type is not supported.');
        $this->getPersistenceAdapter()->updateOrCreateTable('test_persistence_AbstractPersistenceAdapterTestPersistentObjectDifferentType');
    }

    /**
     * Tests the use of missing property types in annotations when writing. The casting
	 * cannot handle this, so an exception should be thrown.
     */
    public function testCastNoTypeWhenWriting() {
        // Create record
        $po = new test_persistence_AbstractPersistenceAdapterTestPersistentObjectNoType();
        $po->booleanValue = true;
        $po->intValue = 2147483647;
        $po->stringValue = 'Hallo Welt!';
        // Store new record
        $this->setExpectedException('Exception', 'Type of persistent object property not set.');
        $this->getPersistenceAdapter()->save($po);
    }

    /**
     * Tests the use of missing property types in annotations when reading.
	 * The casting cannot cast automatically so an exception should be thrown.
     */
    public function testCastNoTypeWhenReading() {
       $uuid = 'abcdefg';
        $bool = true;
        $int = 1234567;
        $string = 'avorium';
        // Write data to the database
        $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object
        $this->setExpectedException('Exception', 'Type of persistent object property not set.');
        $this->getPersistenceAdapter()->get('test_persistence_AbstractPersistenceAdapterTestPersistentObjectNoType', $uuid);
    }

    /**
     * Tests the use of a type in annotations which is not known to the 
     * persistence adapter when writing to database
     */
    public function testCastTypeUnknownWhenWriting() {
        // Create record
        $po = new test_persistence_AbstractPersistenceAdapterTestPersistentObjectUnknownType();
        $po->booleanValue = true;
        $po->intValue = 2147483647;
        $po->stringValue = 'Hallo Welt!';
        // Store new record
        $this->setExpectedException('Exception', 'Database column type \'unknowntype\' is not known to the persistence adapter.');
        $this->getPersistenceAdapter()->save($po);
    }

    /**
     * Tests the use of a type in annotations which is not known to the 
     * persistence adapter when reading from database
     */
    public function testCastTypeUnknownWhenReading() {
       $uuid = 'abcdefg';
        $bool = true;
        $int = 1234567;
        $string = 'avorium';
        // Write data to the database
        $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object
        $this->setExpectedException('Exception', 'Database column type \'unknowntype\' is not known to the persistence adapter.');
        $this->getPersistenceAdapter()->get('test_persistence_AbstractPersistenceAdapterTestPersistentObjectUnknownType', $uuid);
    }
    
    /**
     * Tests the creation of a table when a column type is unknown to the 
     * persistence adapter.
     */
    public function testCreateTableUnknownType() {
        // Drop table
        $this->executeQuery('drop table POTEST');
        // Automatically create table
        $this->setExpectedException('Exception', 'Database column type \'unknowntype\' is not known to the persistence adapter.');
        $this->getPersistenceAdapter()->updateOrCreateTable('test_persistence_AbstractPersistenceAdapterTestPersistentObjectUnknownType');
    }
    
    /**
     * Tests the update of a table when a column type is unknown to the 
     * persistence adapter.
     */
    public function testUpdateTableUnknownType() {
        // Drop table
        $this->executeQuery('drop table POTEST');
        // Manually create table with UUID column only
        $this->createTestTable();
        // Automatically update table
        $this->setExpectedException('Exception', 'Database column type \'unknowntype\' is not known to the persistence adapter.');
        $this->getPersistenceAdapter()->updateOrCreateTable('test_persistence_AbstractPersistenceAdapterTestPersistentObjectUnknownType');
    }
    
    /**
     * Tests the use of special characters in string when writing to and
     * reading from database. This includes SQL injections which could be
     * performed, when the database does not handle the quotation marks
     * (', ", `, ’) correctly.
     */
    public function testSpecialCharacters() {
        // Create record
        $potowrite = new test_persistence_AbstractPersistenceAdapterTestPersistentObject();
        $potowrite->stringValue = '°!"§$%&/()=?`*\'>;:_+öä#<,.-²³¼¹½¬{[]}\\¸~’–…·|@\t\r\n';
        // Store new record
        $this->getPersistenceAdapter()->save($potowrite);
        // Get record back from database
        $result = $this->executeQuery('select * from POTEST where UUID=\''.$potowrite->uuid.'\'');
        // Records must be unique
        $this->assertEquals($potowrite->stringValue, $result[0]['STRING_VALUE'], 'String value from database differs from the string value of the persistent object.');
        // Now test tehe same in the other (read) direction
        $uuid = 'abcdefg';
        $string = '°!"§$%&/()=?`*\'>;:_+öä#<,.-²³¼¹½¬{[]}\\¸~’–…·|@\t\r\n';
        // Write data to the database
        $this->executeQuery('insert into POTEST (UUID, STRING_VALUE) values (\''.$uuid.'\', \''.$this->escape($string).'\')');
        // Read data out and cast it to persistent object
        $potoread = $this->getPersistenceAdapter()->get('test_persistence_AbstractPersistenceAdapterTestPersistentObject', $uuid);
        // Compare properties
        $this->assertEquals($string, $potoread->stringValue, 'String value from persistent object differs from the string value of the database.');
    }
    
    /**
     * Tests the behaviour of the adapters when no database connection is 
     * available. Only the getAll() function is tested because every function
     * uses the getDatabase() function of the adapter directly or indirectly.
     */
    public function testNoDatabaseConnection() {
        $persistenceadapter = $this->getErrornousPersistenceAdapter();
        $this->setExpectedException('Exception');
        $persistenceadapter->getAll('AbstractPersistenceAdapterTestPersistentObject');
    }
    
    /**
     * Tests the behaviour of the executeSingleResultQuery with errornous
     * SQL statement.
     */
    public function testErrornousSingleResultSqlQuery() {
        $this->setExpectedException('Exception', 'Error in query: errornous sql query');
        $this->getPersistenceAdapter()->executeSingleResultQuery('errornous sql query');
    }
    
    /**
     * Tests the behaviour of the executeSingleResultQuery with errornous
     * SQL statement
     */
    public function testErrornousMultipleResultSqlQuery() {
        $this->setExpectedException('Exception', 'Error in query: errornous sql query');
        $this->getPersistenceAdapter()->executeMultipleResultQuery('errornous sql query');
    }
    
    /**
     * Tests the behaviour of the executeSingleResultQuery with errornous
     * SQL statement
     */
    public function testErrornousNoResultSqlQuery() {
        $this->setExpectedException('Exception', 'Error in query: errornous sql query');
        $this->getPersistenceAdapter()->executeNoResultQuery('errornous sql query');
    }
    
    /**
     * Tests the behaviour of the executeSingleResultQuery() function when
     * a statement is given, which has multiple results. In this case
     * an exception should be thrown because there seems to be a semantic error.
     */
    public function testSingleResultQueryWithMultipleResultStatement() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testReadMultipleRecords 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testReadMultipleRecords 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testReadMultipleRecords 3']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        }
        $this->setExpectedException('Exception', 'Single result statement returned more than one result.');
        $this->getPersistenceAdapter()->executeSingleResultQuery('select * from POTEST');
    }
    
    /**
     * Tests the behaviour of the executeSingleResultQuery() function when
     * a statement is given, which has no results. In this case
     * an exception should be thrown.
     */
    public function testSingleResultQueryWithNoResultStatement() {
        $this->setExpectedException('Exception', 'Single result statement seems to be a no result statement.');
        $this->getPersistenceAdapter()->executeSingleResultQuery('delete from POTEST');
    }
    
    /**
     * Tests the behaviour of the executeSingleResultQuery() function when
     * a statement is given, which returns multiple columns with the same name.
	 * The result object should have only one property with the duplicate name
	 * containing the last requested content
     */
    public function testSingleResultQueryWithDuplicateColumns() {
        $record = ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testReadMultipleRecords 2'];
        // Write data to the database
		$this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        $query = 'select UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE, STRING_VALUE as UUID from POTEST where UUID = \'uuid1tRMR2\'';
        $result = $this->getPersistenceAdapter()->executeSingleResultQuery($query);
		$this->assertEquals($record['string'], $result->UUID, 'Uuid should contain the string value but it does not.');
		$this->assertEquals($record['bool'] ? 1 : 0, $result->BOOLEAN_VALUE, 'Boolean value from persistent object differs from the boolean value of the database.');
		$this->assertEquals($record['int'], $result->INT_VALUE, 'Integer value from persistent object differs from the int value of the database.');
		$this->assertEquals($record['string'], $result->STRING_VALUE, 'String value from persistent object differs from the string value of the database.');
    }
    
    /**
     * Tests the behaviour of the executeMultipleResultQuery() function when
     * a statement is given, which has only one results. In this case
     * an array with only one element should be returned.
     */
    public function testMultipleResultQueryWithSingleResultStatement() {
        $uuid = 'abcdefg';
        $bool = true;
        $int = 1234567;
        $string = 'avorium';
        // Write data to the database
        $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object
        $query = 'select * from POTEST where UUID = \'abcdefg\'';
        $pos = $this->getPersistenceAdapter()->executeMultipleResultQuery($query);
        $this->assertEquals(1, count($pos), 'Wrong number of database records found.');
    }
    
    /**
     * Tests the behaviour of the executeMultipleResultQuery() function when
     * a statement is given, which returns multiple columns with the same name.
	 * The result object should have only one property with the duplicate name
	 * containing the last requested content
     */
    public function testMultipleResultQueryWithDuplicateColumns() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testReadMultipleRecords 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testReadMultipleRecords 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testReadMultipleRecords 3']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        }
        $query = 'select UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE, STRING_VALUE as UUID from POTEST';
        $result = $this->getPersistenceAdapter()->executeMultipleResultQuery($query);
        $this->assertEquals(count($records), count($result), 'Wrong number of database records found.');
        for($i = 0; $i < count($result); $i++)  {
            $this->assertEquals($records[$i]['string'], $result[$i]->UUID, 'Uuid should contain the string value but it does not.');
            $this->assertEquals($records[$i]['bool'] ? 1 : 0, $result[$i]->BOOLEAN_VALUE, 'Boolean value from persistent object differs from the boolean value of the database.');
            $this->assertEquals($records[$i]['int'], $result[$i]->INT_VALUE, 'Integer value from persistent object differs from the int value of the database.');
            $this->assertEquals($records[$i]['string'], $result[$i]->STRING_VALUE, 'String value from persistent object differs from the string value of the database.');
        }
    }
    
    /**
     * Tests the behaviour of the executeMultipleResultQuery() function when
     * a statement is given, which has no result. In this case
     * an exception should be thrown.
     */
    public function testMultipleResultQueryWithNoResultStatement() {
        $this->setExpectedException('Exception', 'Multiple result statement seems to be a no result statement.');
        $this->getPersistenceAdapter()->executeMultipleResultQuery('delete from POTEST');
    }
    
    /**
     * Tests the behaviour of the executeNoResultQuery() function when
     * a statement is given, which has a single result. In this case
     * an exception should be thrown.
     */
    public function testNoResultQueryWithSingleResultStatement() {
        $uuid = 'abcdefg';
        $bool = true;
        $int = 1234567;
        $string = 'avorium';
        // Write data to the database
        $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        $this->setExpectedException('Exception', 'No result statement returned a result.');
        $query = 'select * from POTEST where UUID = \'abcdefg\'';
        $this->getPersistenceAdapter()->executeNoResultQuery($query);
    }
    
    /**
     * Tests the behaviour of the executeNoResultQuery() function when
     * a statement is given, which has multiple results. In this case
     * an exception should be thrown.
     */
    public function testNoResultQueryWithMultipleResultStatement() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testReadMultipleRecords 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testReadMultipleRecords 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testReadMultipleRecords 3']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        }
        $this->setExpectedException('Exception', 'No result statement returned a result.');
        $query = 'select * from POTEST';
        $this->getPersistenceAdapter()->executeNoResultQuery($query);
    }
    
    /**
     * Tests storing a long string in the database which exceeds the column
     * length. An exception should be thrown.
     */
    public function testSaveTooLongStrings() {
        // Create record
        $po = new test_persistence_AbstractPersistenceAdapterTestPersistentObject();
        $po->stringValue = str_repeat('X', 1000); // Only 255 are allowed
        // Store new record
        $this->setExpectedException('Exception', 'The string to be inserted is too long for the column.');
        $this->getPersistenceAdapter()->save($po);
    }
    
    /**
     * Tests getting less columns from the database via SQL statement than the
     * persistent object has properties. The unused properties should remain in
     * the default states.
     */
    public function testReadLessColumnsThanProperties() {
        $uuid = 'abcdefg';
        $bool = true;
        $int = 1234567;
        $string = 'avorium';
        // Write data to the database
        $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object
        $query = 'select UUID from POTEST where UUID = \'abcdefg\'';
        $po = $this->getPersistenceAdapter()->executeSingleResultQuery($query, 'test_persistence_AbstractPersistenceAdapterTestPersistentObject');
        // Compare properties
        $this->assertEquals($uuid, $po->uuid, 'Uuid value from persistent object differs from the UUID value of the database.');
        // These columns must not match because they were not read out with the query
        $this->assertNotEquals($bool, $po->booleanValue, 'Boolean value from persistent object equals the boolean value of the database.');
        $this->assertNotEquals($int, $po->intValue, 'Integer value from persistent object equals the int value of the database.');
        $this->assertNotEquals($string, $po->stringValue, 'String value from persistent object equals the string value of the database.');
    }
    
    /**
     * Tests getting other columns from database than the persistent object has
     * properties. The properties should remain in default state and the column
     * contents from the database should be discarded and not be put as dynamic
     * properties to the returned object.
     */
    public function testReadOtherColumnsThanProperties() {
        $uuid = 'abcdefg';
        $bool = true;
        $int = 1234567;
        $string = 'avorium';
        // Write data to the database
        $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object
        $query = 'select UUID OTHERUUID, BOOLEAN_VALUE OTHERBOOL, INT_VALUE OTHERINT, STRING_VALUE OTHERSTRING from POTEST where UUID = \'abcdefg\'';
        $po = $this->getPersistenceAdapter()->executeSingleResultQuery($query, 'test_persistence_AbstractPersistenceAdapterTestPersistentObject');
        // Compare properties
        // These columns must not match because they were not read out with the query
        $this->assertNotEquals($uuid, $po->uuid, 'Uuid value from persistent object equals the UUID value of the database.');
        $this->assertNotEquals($bool, $po->booleanValue, 'Boolean value from persistent object equals the boolean value of the database.');
        $this->assertNotEquals($int, $po->intValue, 'Integer value from persistent object equals the int value of the database.');
        $this->assertNotEquals($string, $po->stringValue, 'String value from persistent object equals the string value of the database.');
        // Make sure that the columns from the query are NOT stored as dynamic properties of the object
        $this->assertFalse(property_exists($po, 'OTHERUUID'), 'Dynamic property otheruuid exists.');
        $this->assertFalse(property_exists($po, 'OTHERBOOL'), 'Dynamic property otherbool exists.');
        $this->assertFalse(property_exists($po, 'OTHERINT'), 'Dynamic property otherint exists.');
        $this->assertFalse(property_exists($po, 'OTHERSTRING'), 'Dynamic property otherstring exists.');
    }
	
	/**
	 * Tests whether the getDataTable function returns the values requested
	 * by a query correctly. Positive test.
	 */
	public function testGetDataTable() {
		// Create values via SQL
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testGetDataTable 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testGetDataTable 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testGetDataTable 3']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        }
		// Get value data table
		$datatable = $this->getPersistenceAdapter()->getDataTable('select UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE from POTEST');
		// Compare contents
		$headers = $datatable->getHeaders();
		$this->assertEquals(4, count($headers), 'Wrong header names count');
		$this->assertEquals('UUID', $headers[0], 'Column 0 has wrong header name');
		$this->assertEquals('BOOLEAN_VALUE', $headers[1], 'Column 1 has wrong header name');
		$this->assertEquals('INT_VALUE', $headers[2], 'Column 2 has wrong header name');
		$this->assertEquals('STRING_VALUE', $headers[3], 'Column 3 has wrong header name');
		$datamatrix = $datatable->getDataMatrix();
		$this->assertEquals(3, count($datamatrix), 'Wrong row count');
		for ($i = 0; $i < 3; $i++) {
			$this->assertEquals(4, count($datamatrix[$i]), 'Wrong column count in row '.$i);
			// The cell values are all strings
			$this->assertEquals(''.$records[$i]['UUID'], $datamatrix[$i][0], 'Cell content does not match in row '.$i.' in column 0');
			$this->assertEquals(''.($records[$i]['bool']?1:0), $datamatrix[$i][1], 'Cell content does not match in row '.$i.' in column 1');
			$this->assertEquals(''.$records[$i]['int'], $datamatrix[$i][2], 'Cell content does not match in row '.$i.' in column 2');
			$this->assertEquals(''.$records[$i]['string'], $datamatrix[$i][3], 'Cell content does not match in row '.$i.' in column 3');
		}
	}
	
	/**
	 * Tests the default behaviour of the getDataTable function and checks that
	 * all elements are strings, even if they are stored as other datatypes
	 * in the database.
	 */
	public function testGetDataTableAllValuesStrings() {
		// Create values via SQL
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testGetDataTable 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testGetDataTable 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testGetDataTable 3']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        }
		// Get value data table
		$datatable = $this->getPersistenceAdapter()->getDataTable('select UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE from POTEST');
		// Compare contents
		$datamatrix = $datatable->getDataMatrix();
		$this->assertEquals(3, count($datamatrix), 'Wrong row count');
		for ($i = 0; $i < 3; $i++) {
			// The cell values must are be strings
			$this->assertTrue(is_string($datamatrix[$i][0]), 'Cell value in row '.$i.' in column 0 is not a string');
			$this->assertTrue(is_string($datamatrix[$i][1]), 'Cell value in row '.$i.' in column 1 is not a string');
			$this->assertTrue(is_string($datamatrix[$i][2]), 'Cell value in row '.$i.' in column 2 is not a string');
			$this->assertTrue(is_string($datamatrix[$i][3]), 'Cell value in row '.$i.' in column 3 is not a string');
		}
	}
	
	/**
	 * Checks that all values of the datatable have the same order as defined in
	 * the SQL statements. Therefor nearly same statements with different
	 * column and row orders are used.
	 */
	public function testGetDataTableRowAndColumnOrder() {
		// Create values via SQL
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testGetDataTable 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testGetDataTable 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testGetDataTable 3']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        }
		// Get value data table with columns in different order
		$datatablecolumnsordered = $this->getPersistenceAdapter()->getDataTable('select STRING_VALUE, BOOLEAN_VALUE, UUID, INT_VALUE from POTEST');
		$headerscolumnsordered = $datatablecolumnsordered->getHeaders();
		$this->assertEquals(4, count($headerscolumnsordered), 'Wrong header names count');
		$this->assertEquals('STRING_VALUE', $headerscolumnsordered[0], 'Column 0 has wrong header name');
		$this->assertEquals('BOOLEAN_VALUE', $headerscolumnsordered[1], 'Column 1 has wrong header name');
		$this->assertEquals('UUID', $headerscolumnsordered[2], 'Column 2 has wrong header name');
		$this->assertEquals('INT_VALUE', $headerscolumnsordered[3], 'Column 3 has wrong header name');
		$datamatrixcolumnsordered = $datatablecolumnsordered->getDataMatrix();
		$this->assertEquals(3, count($datamatrixcolumnsordered), 'Wrong row count');
		for ($i = 0; $i < 3; $i++) {
			$this->assertEquals(4, count($datamatrixcolumnsordered[$i]), 'Wrong column count in row '.$i);
			// The cell values are all strings
			$this->assertEquals(''.$records[$i]['string'], $datamatrixcolumnsordered[$i][0], 'Cell content does not match in row '.$i.' in column 0');
			$this->assertEquals(''.($records[$i]['bool']?1:0), $datamatrixcolumnsordered[$i][1], 'Cell content does not match in row '.$i.' in column 1');
			$this->assertEquals(''.$records[$i]['UUID'], $datamatrixcolumnsordered[$i][2], 'Cell content does not match in row '.$i.' in column 2');
			$this->assertEquals(''.$records[$i]['int'], $datamatrixcolumnsordered[$i][3], 'Cell content does not match in row '.$i.' in column 3');
		}
		// Get value data table with rows in different order
		$datatablerowsordered = $this->getPersistenceAdapter()->getDataTable('select UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE from POTEST order by INT_VALUE desc');
		$headersrowsordered = $datatablerowsordered->getHeaders();
		$this->assertEquals(4, count($headersrowsordered), 'Wrong header names count');
		$this->assertEquals('UUID', $headersrowsordered[0], 'Column 0 has wrong header name');
		$this->assertEquals('BOOLEAN_VALUE', $headersrowsordered[1], 'Column 1 has wrong header name');
		$this->assertEquals('INT_VALUE', $headersrowsordered[2], 'Column 2 has wrong header name');
		$this->assertEquals('STRING_VALUE', $headersrowsordered[3], 'Column 3 has wrong header name');
		$datamatrixrowsordered = $datatablerowsordered->getDataMatrix();
		$this->assertEquals(3, count($datamatrixrowsordered), 'Wrong row count');
		// Row 0
		$this->assertEquals(''.$records[2]['UUID'], $datamatrixrowsordered[0][0], 'Cell content does not match in row 0 in column 0');
		$this->assertEquals(''.($records[2]['bool']?1:0), $datamatrixrowsordered[0][1], 'Cell content does not match in row 0 in column 1');
		$this->assertEquals(''.$records[2]['int'], $datamatrixrowsordered[0][2], 'Cell content does not match in row 0 in column 2');
		$this->assertEquals(''.$records[2]['string'], $datamatrixrowsordered[0][3], 'Cell content does not match in row 0 in column 3');
		// Row 1
		$this->assertEquals(''.$records[1]['UUID'], $datamatrixrowsordered[1][0], 'Cell content does not match in row 0 in column 0');
		$this->assertEquals(''.($records[1]['bool']?1:0), $datamatrixrowsordered[1][1], 'Cell content does not match in row 0 in column 1');
		$this->assertEquals(''.$records[1]['int'], $datamatrixrowsordered[1][2], 'Cell content does not match in row 0 in column 2');
		$this->assertEquals(''.$records[1]['string'], $datamatrixrowsordered[1][3], 'Cell content does not match in row 0 in column 3');
		// Row 2
		$this->assertEquals(''.$records[0]['UUID'], $datamatrixrowsordered[2][0], 'Cell content does not match in row 0 in column 0');
		$this->assertEquals(''.($records[0]['bool']?1:0), $datamatrixrowsordered[2][1], 'Cell content does not match in row 0 in column 1');
		$this->assertEquals(''.$records[0]['int'], $datamatrixrowsordered[2][2], 'Cell content does not match in row 0 in column 2');
		$this->assertEquals(''.$records[0]['string'], $datamatrixrowsordered[2][3], 'Cell content does not match in row 0 in column 3');
	}
	
	/**
	 * When giving null as query the getDataTable function should throw an 
	 * exception.
	 */
	public function testGetDataTableQueryNull() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testReadMultipleRecords 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testReadMultipleRecords 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testReadMultipleRecords 3']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        }
        $this->setExpectedException('Exception', 'The query must not be null.');
		$this->getPersistenceAdapter()->getDataTable(null);
	}
	
	
	/**
	 * When the query is not a valid SQL statement the getDataTable function 
	 * should throw an exception.
	 */
	public function testGetDataTableInvalidQuery() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testReadMultipleRecords 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testReadMultipleRecords 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testReadMultipleRecords 3']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        }
		$query = 'invalid sql query';
        $this->setExpectedException('Exception', 'Error in query: '.$query);
		$this->getPersistenceAdapter()->getDataTable($query);
	}
	
	/**
	 * When the query has no result (e.g. DELETE) the result cannot be converted
	 * to a datatable and so the function should throw an exception.
	 */
	public function testGetDataTableNoResult() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testReadMultipleRecords 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testReadMultipleRecords 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testReadMultipleRecords 3']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        }
		$query = 'delete from POTEST';
        $this->setExpectedException('Exception', 'Multiple result statement seems to be a no result statement.');
		$this->getPersistenceAdapter()->getDataTable($query);
	}
	
	/**
	 * When the query has en empty result (no elements with the given filter
	 * found) the result must be an empty datatable with no rows but with
	 * header names.
	 */
	public function testGetDataTableEmptyResult() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testReadMultipleRecords 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testReadMultipleRecords 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testReadMultipleRecords 3']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        }
		$query = 'select UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE from POTEST where UUID=\'unknownuuid\'';
		$datatable = $this->getPersistenceAdapter()->getDataTable($query);
		// Check header names
		$headernames = $datatable->getHeaders();
		$this->assertEquals(4, count($headernames), 'Datatable has unexpected header names count.');
		$this->assertEquals('UUID', $headernames[0], 'Header of column 0 is not as expected.');
		$this->assertEquals('BOOLEAN_VALUE', $headernames[1], 'Header of column 1 is not as expected.');
		$this->assertEquals('INT_VALUE', $headernames[2], 'Header of column 2 is not as expected.');
		$this->assertEquals('STRING_VALUE', $headernames[3], 'Header of column 3 is not as expected.');
		// Check row count
		$this->assertEquals(0, count($datatable->getDataMatrix()), 'Datatable has unexpected row count.');
	}
	
	/**
	 * Single results should be handled the same way as multiple results. The
	 * returned datatable should contain only one row.
	 */
	public function testGetDataTableSingleResult() {
		// Create values via SQL
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testGetDataTable 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testGetDataTable 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testGetDataTable 3']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        }
		// Get value data table of single row (second record)
		$datatable = $this->getPersistenceAdapter()->getDataTable('select UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE from POTEST where UUID=\'uuid1tRMR2\'');
		// Compare contents
		$headers = $datatable->getHeaders();
		$this->assertEquals(4, count($headers), 'Wrong header names count');
		$this->assertEquals('UUID', $headers[0], 'Column 0 has wrong header name');
		$this->assertEquals('BOOLEAN_VALUE', $headers[1], 'Column 1 has wrong header name');
		$this->assertEquals('INT_VALUE', $headers[2], 'Column 2 has wrong header name');
		$this->assertEquals('STRING_VALUE', $headers[3], 'Column 3 has wrong header name');
		$datamatrix = $datatable->getDataMatrix();
		// Data matrix must contain only one row
		$this->assertEquals(1, count($datamatrix), 'Wrong row count');
		$this->assertEquals(4, count($datamatrix[0]), 'Wrong column count in row 0');
		// The query requested the second record
		$this->assertEquals(''.$records[1]['UUID'], $datamatrix[0][0], 'Cell content does not match in column 0');
		$this->assertEquals(''.($records[1]['bool']?1:0), $datamatrix[0][1], 'Cell content does not match in column 1');
		$this->assertEquals(''.$records[1]['int'], $datamatrix[0][2], 'Cell content does not match in column 2');
		$this->assertEquals(''.$records[1]['string'], $datamatrix[0][3], 'Cell content does not match in column 3');
	}

	/**
	 * When the query returns multiple columns with the same column name
	 * the datatable should return all requested columns (also the duplicated 
	 * ones).
	 */
	public function testGetDataTableDuplicateColumnNames() {
		// Create values via SQL
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testGetDataTable 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testGetDataTable 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testGetDataTable 3']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        }
		// Get value data table with STRING_VALUE named as UUID at the end. The UUID column must contain the content from STRING_VALUE
		// and STRING_VALUE must exist only once.
		$datatable = $this->getPersistenceAdapter()->getDataTable('select UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE, STRING_VALUE as UUID from POTEST');
		// Compare contents
		$headers = $datatable->getHeaders();
		$this->assertEquals(5, count($headers), 'Wrong header names count');
		$this->assertEquals('UUID', $headers[0], 'Column 0 has wrong header name');
		$this->assertEquals('BOOLEAN_VALUE', $headers[1], 'Column 1 has wrong header name');
		$this->assertEquals('INT_VALUE', $headers[2], 'Column 2 has wrong header name');
		$this->assertEquals('STRING_VALUE', $headers[3], 'Column 3 has wrong header name');
		$this->assertEquals('UUID', $headers[4], 'Column 4 has wrong header name');
		$datamatrix = $datatable->getDataMatrix();
		$this->assertEquals(3, count($datamatrix), 'Wrong row count');
		for ($i = 0; $i < 3; $i++) {
			$this->assertEquals(5, count($datamatrix[$i]), 'Wrong column count in row '.$i);
			// The UUID column was overwritten by aliasing the string column in the statement
			$this->assertEquals(''.$records[$i]['UUID'], $datamatrix[$i][0], 'Cell content does not match in row '.$i.' in column 0');
			$this->assertEquals(''.($records[$i]['bool']?1:0), $datamatrix[$i][1], 'Cell content does not match in row '.$i.' in column 1');
			$this->assertEquals(''.$records[$i]['int'], $datamatrix[$i][2], 'Cell content does not match in row '.$i.' in column 2');
			$this->assertEquals(''.$records[$i]['string'], $datamatrix[$i][3], 'Cell content does not match in row '.$i.' in column 3');
			$this->assertEquals(''.$records[$i]['string'], $datamatrix[$i][4], 'Cell content does not match in row '.$i.' in column 4');
		}
	}
	
	/**
	 * Positive test for normal use of saveDataTable. The given values should
	 * be stored correctly depending on their datatypes. Currently only boolean,
	 * integer and string are supported.
	 */
	public function testSaveDataTable() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testGetDataTable 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testGetDataTable 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testGetDataTable 3']
        ];
		// Create datatable and store it into the database
		$datatable = new avorium_core_data_DataTable(3, 4);
		$datatable->setHeader(0, 'UUID');
		$datatable->setHeader(1, 'BOOLEAN_VALUE');
		$datatable->setHeader(2, 'INT_VALUE');
		$datatable->setHeader(3, 'STRING_VALUE');
		for ($i = 0; $i < 3; $i++) {
			$datatable->setCellValue($i, 0, $records[$i]['UUID']);
			$datatable->setCellValue($i, 1, $records[$i]['bool']);
			$datatable->setCellValue($i, 2, $records[$i]['int']);
			$datatable->setCellValue($i, 3, $records[$i]['string']);
		}
		$this->getPersistenceAdapter()->saveDataTable('POTEST', $datatable);
		// Read the records via SQL and check their contents.
		// Let them return the results ordered. Mybe the persistence adapter 
		// uses bulk save methods which store the records in a different order
		$result = $this->executeQuery('select UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE from POTEST order by UUID');
		$this->assertEquals(3, count($result), 'Wrong row count');
		for ($i = 0; $i < 3; $i++) {
			$this->assertEquals($records[$i]['UUID'], $result[$i]['UUID'], 'UUID from database is not as expected.');
			$this->assertEquals($records[$i]['bool']?1:0, $result[$i]['BOOLEAN_VALUE'], 'Boolean value from database is not as expected.');
			$this->assertEquals($records[$i]['int'], $result[$i]['INT_VALUE'], 'Integer value from database is not as expected.');
			$this->assertEquals($records[$i]['string'], $result[$i]['STRING_VALUE'], 'String value from database is not as expected.');
		}
	}
	
	/**
	 * When no table name is given as parameter, the saveDataTable function
	 * should throw an exception.
	 */
	public function testSaveDataTableNoTableName() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testGetDataTable 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testGetDataTable 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testGetDataTable 3']
        ];
		$datatable = new avorium_core_data_DataTable(3, 4);
		$datatable->setHeader(0, 'UUID');
		$datatable->setHeader(1, 'BOOLEAN_VALUE');
		$datatable->setHeader(2, 'INT_VALUE');
		$datatable->setHeader(3, 'STRING_VALUE');
		for ($i = 0; $i < 3; $i++) {
			$datatable->setCellValue($i, 0, $records[$i]['UUID']);
			$datatable->setCellValue($i, 1, $records[$i]['bool']);
			$datatable->setCellValue($i, 2, $records[$i]['int']);
			$datatable->setCellValue($i, 3, $records[$i]['string']);
		}
		// Save without giving a table name
        $this->setExpectedException('Exception', 'No table name given.');
		$this->getPersistenceAdapter()->saveDataTable(null, $datatable);
	}
	
	/**
	 * When the table name is not given as string parameter, the saveDataTable
	 * function should throw an exception.
	 */
	public function testSaveDataTableTableNameNotString() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testGetDataTable 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testGetDataTable 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testGetDataTable 3']
        ];
		$datatable = new avorium_core_data_DataTable(3, 4);
		$datatable->setHeader(0, 'UUID');
		$datatable->setHeader(1, 'BOOLEAN_VALUE');
		$datatable->setHeader(2, 'INT_VALUE');
		$datatable->setHeader(3, 'STRING_VALUE');
		for ($i = 0; $i < 3; $i++) {
			$datatable->setCellValue($i, 0, $records[$i]['UUID']);
			$datatable->setCellValue($i, 1, $records[$i]['bool']);
			$datatable->setCellValue($i, 2, $records[$i]['int']);
			$datatable->setCellValue($i, 3, $records[$i]['string']);
		}
		// Save without giving a table name which is not a string
        $this->setExpectedException('Exception', 'Table name must be a string.');
		$this->getPersistenceAdapter()->saveDataTable(3, $datatable);
	}
	
	/**
	 * When an unknown table name is given as parameter, the saveDataTable 
	 * function should throw an exception.
	 */
	public function testSaveDataTableInvalidTableName() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testGetDataTable 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testGetDataTable 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testGetDataTable 3']
        ];
		$datatable = new avorium_core_data_DataTable(3, 4);
		$datatable->setHeader(0, 'UUID');
		$datatable->setHeader(1, 'BOOLEAN_VALUE');
		$datatable->setHeader(2, 'INT_VALUE');
		$datatable->setHeader(3, 'STRING_VALUE');
		for ($i = 0; $i < 3; $i++) {
			$datatable->setCellValue($i, 0, $records[$i]['UUID']);
			$datatable->setCellValue($i, 1, $records[$i]['bool']);
			$datatable->setCellValue($i, 2, $records[$i]['int']);
			$datatable->setCellValue($i, 3, $records[$i]['string']);
		}
		// Save giving an invalid table name
        $this->setExpectedException('Exception', 'Invalid table name given: INVALIDTABLENAME');
		$this->getPersistenceAdapter()->saveDataTable('INVALIDTABLENAME', $datatable);
	}
	
	/**
	 * When no data table is given as parameter, the saveDataTable function
	 * should throw an exception.
	 */
	public function testSaveDataTableNoDataTable() {
        $this->setExpectedException('Exception', 'No data table given.');
		$this->getPersistenceAdapter()->saveDataTable('POTEST', null);
	}
	
	/**
	 * When a datatable is given, which contains no rows, nothing should happen
	 * at all. No exception should be thrown.
	 * The save function should ignore the request. This can happen with
	 * automatisms across different systems.
	 */
	public function testSaveDataTableNoRows() {
		$datatable = new avorium_core_data_DataTable(0, 4);
		$datatable->setHeader(0, 'UUID');
		$datatable->setHeader(1, 'BOOLEAN_VALUE');
		$datatable->setHeader(2, 'INT_VALUE');
		$datatable->setHeader(3, 'STRING_VALUE');
		$this->getPersistenceAdapter()->saveDataTable('POTEST', $datatable);
	}
	
	/**
	 * When the datatable parameter is not of type avorium_core_data_DataTable,
	 * the function should throw an exception
	 */
	public function testSaveDataTableIncorrectDataType() {
        $this->setExpectedException('Exception', 'Data table is not of correct datatype.');
		$this->getPersistenceAdapter()->saveDataTable('POTEST', 'Incorrect data type');
	}
	
	/**
	 * When the header names of the data table do not match to the colmn names
	 * of the database table, the function should throw an exception.
	 */
	public function testSaveDataTableColumnNamesNotMatching() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testGetDataTable 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testGetDataTable 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testGetDataTable 3']
        ];
		$datatable = new avorium_core_data_DataTable(3, 4);
		$datatable->setHeader(0, 'UUID');
		$datatable->setHeader(1, 'INVALID_COLUMN_NAME');
		$datatable->setHeader(2, 'INT_VALUE');
		$datatable->setHeader(3, 'STRING_VALUE');
		for ($i = 0; $i < 3; $i++) {
			$datatable->setCellValue($i, 0, $records[$i]['UUID']);
			$datatable->setCellValue($i, 1, $records[$i]['bool']);
			$datatable->setCellValue($i, 2, $records[$i]['int']);
			$datatable->setCellValue($i, 3, $records[$i]['string']);
		}
		// Save giving an invalid column name
        $this->setExpectedException('Exception');
		$this->getPersistenceAdapter()->saveDataTable('POTEST', $datatable);
	}
	
	/**
	 * When a column name (header name) in a datatable is null, the save
	 * function should throw an exception because it does not know,
	 * in which column the data has to be stored.
	 */
	public function testSaveDataTableColumnNameNull() {
		$datatable = new avorium_core_data_DataTable(1, 1);
		$datatable->setHeader(0, null);
		$datatable->setCellValue(0, 0, '0');
        $this->setExpectedException('Exception', 'The header name is null but must not be.');
		$this->getPersistenceAdapter()->saveDataTable('POTEST', $datatable);
	}
	
	/**
	 * When a column name (header name) in a datatable is empty, the save
	 * function should throw an exception because it does not know,
	 * in which column the data has to be stored.
	 */
	public function testSaveDataTableColumnNameEmpty() {
		$datatable = new avorium_core_data_DataTable(1, 1);
		$datatable->setHeader(0, '');
		$datatable->setCellValue(0, 0, '0');
        $this->setExpectedException('Exception', 'The header name is empty but must not be.');
		$this->getPersistenceAdapter()->saveDataTable('POTEST', $datatable);
	}
	
	/**
	 * Currently only boolean, integer and string are supported as PHP value
	 * types for the saveDataTable function. So this function should throw
	 * an exception when in one of the cells is datatype which the
	 * persistence adapter cannot handle (e.g. objects or arrays)
	 */
	public function testSaveDataTableUnknownValueDatatype() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testGetDataTable 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => array(), 'string' => 'testGetDataTable 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testGetDataTable 3']
        ];
		$datatable = new avorium_core_data_DataTable(3, 4);
		$datatable->setHeader(0, 'UUID');
		$datatable->setHeader(1, 'BOOLEAN_VALUE');
		$datatable->setHeader(2, 'INT_VALUE');
		$datatable->setHeader(3, 'STRING_VALUE');
		for ($i = 0; $i < 3; $i++) {
			$datatable->setCellValue($i, 0, $records[$i]['UUID']);
			$datatable->setCellValue($i, 1, $records[$i]['bool']);
			$datatable->setCellValue($i, 2, $records[$i]['int']);
			$datatable->setCellValue($i, 3, $records[$i]['string']);
		}
		// Save giving an unknown data type (array). The persistence adapter
		// checks this before it constructs the SQL query because it must
		// know whether to escape the value in the stamenent or not.
        $this->setExpectedException('Exception', 'Unknown datatype: array');
		$this->getPersistenceAdapter()->saveDataTable('POTEST', $datatable);
	}
	
	/**
	 * Teste the behaviour of the saveDataTable function when column values
	 * are given as strings and stored into columns of different datatypes.
	 * Curretnly all database connectors should convert strings to integer
	 * numbers automatically.
	 */
	public function testSaveDataTableParseStringsToOtherDatatypes() {
        $record = ['UUID' => 'uuid1tRMR2', 'bool' => '1', 'int' => '12345', 'string' => 'testReadMultipleRecords 2'];
		$datatable = new avorium_core_data_DataTable(1, 4);
		$datatable->setHeader(0, 'UUID');
		$datatable->setHeader(1, 'BOOLEAN_VALUE');
		$datatable->setHeader(2, 'INT_VALUE');
		$datatable->setHeader(3, 'STRING_VALUE');
		$datatable->setCellValue(0, 0, $record['UUID']);
		$datatable->setCellValue(0, 1, $record['bool']);
		$datatable->setCellValue(0, 2, $record['int']);
		$datatable->setCellValue(0, 3, $record['string']);
		$this->getPersistenceAdapter()->saveDataTable('POTEST', $datatable);
		$result = $this->executeQuery('select UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE from POTEST where UUID=\''.$record['UUID'].'\'');
		$this->assertEquals(1, count($result), 'Wrong row count');
		$this->assertEquals($record['UUID'], $result[0]['UUID'], 'UUID from database is not as expected.');
		$this->assertEquals(1, $result[0]['BOOLEAN_VALUE'], 'Boolean value from database is not as expected.');
		$this->assertEquals($record['int'], $result[0]['INT_VALUE'], 'Integer value from database is not as expected.');
		$this->assertEquals($record['string'], $result[0]['STRING_VALUE'], 'String value from database is not as expected.');
	}
	
	/**
	 * Tests the behaviour of the save function when a column contains a string
	 * which is to be stored into a number column but cannot be parsed into
	 * a number. In this case the function should throw an exception.
	 */
	public function testSaveDataTableStringsNotParseableToDataTypes() {
        $record = ['UUID' => 'uuid1tRMR2', 'bool' => 'not parsable', 'int' => 'not parsable', 'string' => 'testReadMultipleRecords 2'];
		$datatable = new avorium_core_data_DataTable(1, 4);
		$datatable->setHeader(0, 'UUID');
		$datatable->setHeader(1, 'BOOLEAN_VALUE');
		$datatable->setHeader(2, 'INT_VALUE');
		$datatable->setHeader(3, 'STRING_VALUE');
		$datatable->setCellValue(0, 0, $record['UUID']);
		$datatable->setCellValue(0, 1, $record['bool']);
		$datatable->setCellValue(0, 2, $record['int']);
		$datatable->setCellValue(0, 3, $record['string']);
        $this->setExpectedException('Exception');
		$this->getPersistenceAdapter()->saveDataTable('POTEST', $datatable);
	}

	/**
	 * Tests the behaviour of the saveDataTable function when null values are 
	 * given.
	 */
	public function testSaveDataTableNullValues() {
       $recordswithnullvalues = [
            ['UUID' => 'uuid1tRMR1', 'bool' => true, 'int' => 100, 'string' => 'testGetDataTable 10'],
            ['UUID' => 'uuid1tRMR2', 'bool' => null, 'int' => null, 'string' => null],
            ['UUID' => 'uuid1tRMR3', 'bool' => true, 'int' => 300, 'string' => 'testGetDataTable 30']
        ];
		$datatablewithnullvalues = new avorium_core_data_DataTable(3, 4);
		$datatablewithnullvalues->setHeader(0, 'UUID');
		$datatablewithnullvalues->setHeader(1, 'BOOLEAN_VALUE');
		$datatablewithnullvalues->setHeader(2, 'INT_VALUE');
		$datatablewithnullvalues->setHeader(3, 'STRING_VALUE');
		for ($i = 0; $i < 3; $i++) {
			$datatablewithnullvalues->setCellValue($i, 0, $recordswithnullvalues[$i]['UUID']);
			$datatablewithnullvalues->setCellValue($i, 1, $recordswithnullvalues[$i]['bool']);
			$datatablewithnullvalues->setCellValue($i, 2, $recordswithnullvalues[$i]['int']);
			$datatablewithnullvalues->setCellValue($i, 3, $recordswithnullvalues[$i]['string']);
		}
		// Store the null values
		$this->getPersistenceAdapter()->saveDataTable('POTEST', $datatablewithnullvalues);
		// Read the database records, the values now must contain the null values
		$resultwithnullvalues = $this->executeQuery('select UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE from POTEST order by UUID');
		$this->assertEquals(3, count($resultwithnullvalues), 'Wrong row count');
		for ($i = 0; $i < 3; $i++) {
			$this->assertEquals($recordswithnullvalues[$i]['UUID'], $resultwithnullvalues[$i]['UUID'], 'UUID from database is not as expected in row '.$i.'.');
			$this->assertEquals($recordswithnullvalues[$i]['bool'], $resultwithnullvalues[$i]['BOOLEAN_VALUE'], 'Boolean value from database is not as expected in row '.$i.'.');
			$this->assertEquals($recordswithnullvalues[$i]['int'], $resultwithnullvalues[$i]['INT_VALUE'], 'Integer value from database is not as expected in row '.$i.'.');
			$this->assertEquals($recordswithnullvalues[$i]['string'], $resultwithnullvalues[$i]['STRING_VALUE'], 'String value from database is not as expected in row '.$i.'.');
		}
	}

	/**
	 * Tests the insertion of rows with new primary keys into an existing table.
	 * These rows should be inserted as new rows independent on the
	 * ignorenullvalues parameter (null value columns are left out in every
	 * case). The size of the database table should increase after the
	 * insertion.
	 */
	public function testSaveDataTableInsertNewRows() {
		// Prepare some database contents via SQL
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testReadMultipleRecords 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testReadMultipleRecords 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testReadMultipleRecords 3']
        ];
		$nonullvaluerecord = ['UUID' => 'uuid2nvr1', 'bool' => false, 'int' => 30, 'string' => 'testReadMultipleRecords 3'];
		$nullvaluerecord = ['UUID' => 'uuid2nvr2', 'bool' => null, 'int' => null, 'string' => null];
        foreach ($records as $record) {
            $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        }
		// Prepare datatable
		$datatable = new avorium_core_data_DataTable(1, 4);
		$datatable->setHeader(0, 'UUID');
		$datatable->setHeader(1, 'BOOLEAN_VALUE');
		$datatable->setHeader(2, 'INT_VALUE');
		$datatable->setHeader(3, 'STRING_VALUE');
		// Store a new record without any null values
		$datatable->setCellValue(0, 0, $nonullvaluerecord['UUID']);
		$datatable->setCellValue(0, 1, $nonullvaluerecord['bool']);
		$datatable->setCellValue(0, 2, $nonullvaluerecord['int']);
		$datatable->setCellValue(0, 3, $nonullvaluerecord['string']);
		$this->getPersistenceAdapter()->saveDataTable('POTEST', $datatable);
		// Check for the new record
		$resultwithoutnullvalues = $this->executeQuery('select UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE from POTEST order by UUID');
		$this->assertEquals(4, count($resultwithoutnullvalues), 'Wrong row count');
		$this->assertEquals($nonullvaluerecord['UUID'], $resultwithoutnullvalues[3]['UUID'], 'UUID from database is not as expected.');
		$this->assertEquals($nonullvaluerecord['bool']?1:0, $resultwithoutnullvalues[3]['BOOLEAN_VALUE'], 'Boolean value from database is not as expected.');
		$this->assertEquals($nonullvaluerecord['int'], $resultwithoutnullvalues[3]['INT_VALUE'], 'Integer value from database is not as expected.');
		$this->assertEquals($nonullvaluerecord['string'], $resultwithoutnullvalues[3]['STRING_VALUE'], 'String value from database is not as expected.');
		// Store a new record with null values
		$datatable->setCellValue(0, 0, $nullvaluerecord['UUID']);
		$datatable->setCellValue(0, 1, $nullvaluerecord['bool']);
		$datatable->setCellValue(0, 2, $nullvaluerecord['int']);
		$datatable->setCellValue(0, 3, $nullvaluerecord['string']);
		$this->getPersistenceAdapter()->saveDataTable('POTEST', $datatable);
		// Check for the new record, must contain null values, because when inserting the parameter is irrelevant
		$resultwithnullvaluesignoring = $this->executeQuery('select UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE from POTEST order by UUID');
		$this->assertEquals(5, count($resultwithnullvaluesignoring), 'Wrong row count');
		$this->assertEquals($nullvaluerecord['UUID'], $resultwithnullvaluesignoring[4]['UUID'], 'UUID from database is not as expected.');
		$this->assertEquals($nullvaluerecord['bool']?1:0, $resultwithnullvaluesignoring[4]['BOOLEAN_VALUE'], 'Boolean value from database is not as expected.');
		$this->assertEquals($nullvaluerecord['int'], $resultwithnullvaluesignoring[4]['INT_VALUE'], 'Integer value from database is not as expected.');
		$this->assertEquals($nullvaluerecord['string'], $resultwithnullvaluesignoring[4]['STRING_VALUE'], 'String value from database is not as expected.');
	}

	/**
	 * When givin a datatable which has rows and the primary keys of the rows
	 * already exist in the database, these database records should be
	 * overwritten. But only these columns are overwritten, which are contained
	 * in the datatable. All other columns of the record should keep the old
	 * values.
	 */
	public function testSaveDataTableUpdateRows() {
		// Prepare the database table via SQL
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testReadMultipleRecords 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testReadMultipleRecords 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testReadMultipleRecords 3']
        ];
        foreach ($records as $record) {
            $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        }
		// Create a datatable where only one column should be updated
		$datatable = new avorium_core_data_DataTable(3, 2);
		$datatable->setHeader(0, 'UUID');
		$datatable->setHeader(1, 'STRING_VALUE');
		// Store a new record without any null values
		for ($i = 0; $i < 3; $i++) {
			$datatable->setCellValue($i, 0, $records[$i]['UUID']);
			$datatable->setCellValue($i, 1, 'New value '.$i);
		}
		$this->getPersistenceAdapter()->saveDataTable('POTEST', $datatable);
		// Read out the database, all other columns must have the old values
		$result = $this->executeQuery('select UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE from POTEST order by UUID');
		$this->assertEquals(3, count($result), 'Wrong row count');
		for ($i = 0; $i < 3; $i++) {
			$this->assertEquals($records[$i]['UUID'], $result[$i]['UUID'], 'UUID from database is not as expected in row '.$i.'.');
			$this->assertEquals($records[$i]['bool']?1:0, $result[$i]['BOOLEAN_VALUE'], 'Boolean value from database is not as expected in row '.$i.'.');
			$this->assertEquals($records[$i]['int'], $result[$i]['INT_VALUE'], 'Integer value from database is not as expected in row '.$i.'.');
			$this->assertEquals('New value '.$i, $result[$i]['STRING_VALUE'], 'String value from database is not as expected in row '.$i.'.');
		}
	}

	/**
	 * When the datatable contains no column which matches to the primary key
	 * column of the database, the saveDataTable function should throw an
	 * exception and it should not try to create new records with an
	 * automatically created primary key (even not when there is an auto
	 * increment column).
	 */
	public function testSaveDataTableNoPrimaryKeyColumn() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testGetDataTable 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testGetDataTable 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testGetDataTable 3']
        ];
		// Create datatable and store it into the database
		$datatable = new avorium_core_data_DataTable(3, 3);
		$datatable->setHeader(0, 'BOOLEAN_VALUE');
		$datatable->setHeader(1, 'INT_VALUE');
		$datatable->setHeader(2, 'STRING_VALUE');
		for ($i = 0; $i < 3; $i++) {
			$datatable->setCellValue($i, 0, $records[$i]['bool']);
			$datatable->setCellValue($i, 1, $records[$i]['int']);
			$datatable->setCellValue($i, 2, $records[$i]['string']);
		}
        $this->setExpectedException('Exception', 'Expected primary key column UUID not found.');
		$this->getPersistenceAdapter()->saveDataTable('POTEST', $datatable);
	}
	
	/**
	 * Tests the behaviour of saving a datatable when the given table name
	 * contains special characters. It is expected that an exception is thrown
	 * that the table cannot be found because the databases do not support
	 * special characters in table names. But the statement must be escaped
	 * correctly to prevent SQL injections.
	 */
	public function testSaveDataTableSpecialCharsInTableName() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testGetDataTable 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testGetDataTable 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testGetDataTable 3']
        ];
		// Create datatable and store it into the database
		$datatable = new avorium_core_data_DataTable(3, 4);
		$datatable->setHeader(0, 'UUID');
		$datatable->setHeader(1, 'BOOLEAN_VALUE');
		$datatable->setHeader(2, 'INT_VALUE');
		$datatable->setHeader(3, 'STRING_VALUE');
		for ($i = 0; $i < 3; $i++) {
			$datatable->setCellValue($i, 0, $records[$i]['UUID']);
			$datatable->setCellValue($i, 1, $records[$i]['bool']);
			$datatable->setCellValue($i, 2, $records[$i]['int']);
			$datatable->setCellValue($i, 3, $records[$i]['string']);
		}
        $this->setExpectedException('Exception', 'Invalid table name given: °!"§$%&/()=?`*\'>; :_+öä#<,.-²³¼¹½¬{[]}\\¸~’–…·|@\t\r\n');
		$this->getPersistenceAdapter()->saveDataTable('°!"§$%&/()=?`*\'>; :_+öä#<,.-²³¼¹½¬{[]}\\¸~’–…·|@\t\r\n', $datatable);
	}
	
	/**
	 * When special characters are given as column names, they should be
	 * escaped correctly to prevent SQL injections. But the function
	 * should throw an exception that says that the column cannot be found.
	 */
	public function testSaveDataTableSpecialCharsInColumnName() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testGetDataTable 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => 'testGetDataTable 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testGetDataTable 3']
        ];
		// Create datatable and store it into the database
		$datatable = new avorium_core_data_DataTable(3, 4);
		$datatable->setHeader(0, 'UUID');
		$datatable->setHeader(1, '°!"§$%&/()=?`*\'>; :_+öä#<,.-²³¼¹½¬{[]}\\¸~’–…·|@\t\r\n');
		$datatable->setHeader(2, 'INT_VALUE');
		$datatable->setHeader(3, 'STRING_VALUE');
		for ($i = 0; $i < 3; $i++) {
			$datatable->setCellValue($i, 0, $records[$i]['UUID']);
			$datatable->setCellValue($i, 1, $records[$i]['bool']);
			$datatable->setCellValue($i, 2, $records[$i]['int']);
			$datatable->setCellValue($i, 3, $records[$i]['string']);
		}
		// The database query itself throws an exception
        $this->setExpectedException('Exception');
		$this->getPersistenceAdapter()->saveDataTable('POTEST', $datatable);
	}
	
	/**
	 * Values with special characters should be escaped and stored correctly.
	 */
	public function testSaveDataTableSpecialCharsInValues() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => false, 'int' => 10, 'string' => 'testGetDataTable 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => true, 'int' => 20, 'string' => '°!"§$%&/()=?`*\'>; :_+öä#<,.-²³¼¹½¬{[]}\\¸~’–…·|@\t\r\n'],
            ['UUID' => 'uuid1tRMR3', 'bool' => false, 'int' => 30, 'string' => 'testGetDataTable 3']
        ];
		// Create datatable and store it into the database
		$datatable = new avorium_core_data_DataTable(3, 4);
		$datatable->setHeader(0, 'UUID');
		$datatable->setHeader(1, 'BOOLEAN_VALUE');
		$datatable->setHeader(2, 'INT_VALUE');
		$datatable->setHeader(3, 'STRING_VALUE');
		for ($i = 0; $i < 3; $i++) {
			$datatable->setCellValue($i, 0, $records[$i]['UUID']);
			$datatable->setCellValue($i, 1, $records[$i]['bool']);
			$datatable->setCellValue($i, 2, $records[$i]['int']);
			$datatable->setCellValue($i, 3, $records[$i]['string']);
		}
		$this->getPersistenceAdapter()->saveDataTable('POTEST', $datatable);
		// Read the records via SQL and check their contents.
		// Let them return the results ordered. Mybe the persistence adapter 
		// uses bulk save methods which store the records in a different order
		$result = $this->executeQuery('select UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE from POTEST order by UUID');
		$this->assertEquals(3, count($result), 'Wrong row count');
		for ($i = 0; $i < 3; $i++) {
			$this->assertEquals($records[$i]['UUID'], $result[$i]['UUID'], 'UUID from database is not as expected.');
			$this->assertEquals($records[$i]['bool']?1:0, $result[$i]['BOOLEAN_VALUE'], 'Boolean value from database is not as expected.');
			$this->assertEquals($records[$i]['int'], $result[$i]['INT_VALUE'], 'Boolean value from database is not as expected.');
			$this->assertEquals($records[$i]['string'], $result[$i]['STRING_VALUE'], 'Boolean value from database is not as expected.');
		}
	}

	/**
	 * Tests the correct conversion of boolean datatypes into strings when
	 * reading from database and putting the data into a datatable.
	 * Allowed results: either "1" for true or "0" for false.
	 */
	public function testGetDataTableBooleanDataType() {
		// Insert border values into database
		$this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE) values (\'testuuid0\',0)');
		$this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE) values (\'testuuid1\',1)');
		// Extract datatable
		$datatable = $this->getPersistenceAdapter()->getDataTable('select BOOLEAN_VALUE from POTEST order by UUID');
		$datamatrix = $datatable->getDataMatrix();
		// Check strings for correct conversion
		$this->assertEquals('0', $datamatrix[0][0], 'Minimum boolean value is not converted to string as expected.');
		$this->assertEquals('1', $datamatrix[1][0], 'Maximum boolean value is not converted to string as expected.');
	}

	/**
	 * Tests the correct conversion of integer datatypes into strings when
	 * reading from database and putting the data into a datatable.
	 * Results must be between "-2147483648" and "2147483647".
	 */
	public function testGetDataTableIntegerDataType() {
		// Insert border values into database
		$minvalue = '-2147483648';
		$maxvalue = '2147483647';
		$this->executeQuery('insert into POTEST (UUID, INT_VALUE) values (\'testuuid0\','.$minvalue.')');
		$this->executeQuery('insert into POTEST (UUID, INT_VALUE) values (\'testuuid1\','.$maxvalue.')');
		// Extract datatable
		$datatable = $this->getPersistenceAdapter()->getDataTable('select INT_VALUE from POTEST order by UUID');
		$datamatrix = $datatable->getDataMatrix();
		// Check strings for correct conversion
		$this->assertEquals($minvalue, $datamatrix[0][0], 'Minimum integer value is not converted to string as expected.');
		$this->assertEquals($maxvalue, $datamatrix[1][0], 'Maximum integer value is not converted to string as expected.');
	}

	/**
	 * Tests the correct reading of strings of a
	 * maximum length of 255 characters when
	 * reading from database and putting the data into a datatable.
	 * Results must contain all special characters and must be UTF8 encoded.
	 */
	public function testGetDataTableStringDataType() {
		// Insert values into database
		$value = str_pad('', 255, '°!"§$%&/()=?`*\'>; :_+öä#<,.-²³¼¹½¬{[]}\\¸~’–…·|@\t\r\n');
		$escapedvalue = $this->escape($value);
		$this->executeQuery('insert into POTEST (UUID, STRING_VALUE) values (\'testuuid0\',\''.$escapedvalue.'\')');
		// Extract datatable
		$datatable = $this->getPersistenceAdapter()->getDataTable('select STRING_VALUE from POTEST order by UUID');
		$datamatrix = $datatable->getDataMatrix();
		// Check strings for correct conversion
		$resultstring = $datamatrix[0][0]; 
		$this->assertEquals(255, strlen($resultstring), 'Result string has not the correct length.');
		$this->assertEquals($value, $resultstring, 'Result string is not the same as the given one.');
		$this->assertEquals('UTF-8', mb_detect_encoding($resultstring), 'The string encoding is not UTF8.');
	}

	/**
	 * Tests the correct conversion of decimal datatypes into strings when
	 * reading from database and putting the data into a datatable.
	 * The results must be strings with optional negative signs and a dot as 
	 * decimal point character, no thousand-separators, e.g. "-1234.567".
	 */
	public function testGetDataTableDecimalDataType() {
		// Insert border values into database
		$minvalue = '-9999999999999999999.9999999999';
		$maxvalue = '9999999999999999999.9999999999';
		$this->executeQuery('insert into POTEST (UUID, DECIMAL_VALUE) values (\'testuuid0\','.$minvalue.')');
		$this->executeQuery('insert into POTEST (UUID, DECIMAL_VALUE) values (\'testuuid1\','.$maxvalue.')');
		// Extract datatable
		$datatable = $this->getPersistenceAdapter()->getDataTable('select DECIMAL_VALUE from POTEST order by UUID');
		$datamatrix = $datatable->getDataMatrix();
		// Check strings for correct conversion
		$this->assertEquals($minvalue, $datamatrix[0][0], 'Minimum decimal value is not converted to string as expected.');
		$this->assertEquals($maxvalue, $datamatrix[1][0], 'Maximum decimal value is not converted to string as expected.');
	}

	/**
	 * Tests the correct conversion of double datatypes into strings when
	 * reading from database and putting the data into a datatable.
	 * Results must be in the format (uppercase "E") "-2.22507485850719E-30" 
	 * to "1.79769313486230E+308"
	 */
	public function testGetDataTableDoubleDataType() {
		// Insert border values into database
		$minposvalue = '2.22507485850719E-308';
		$maxposvalue = '1.79769313486230E308';
		$minnegvalue = '-2.22507485850719E-308';
		$maxnegvalue = '-1.79769313486230E308';
		$this->executeQuery('insert into POTEST (UUID, DOUBLE_VALUE) values (\'testuuid0\','.$minposvalue.')');
		$this->executeQuery('insert into POTEST (UUID, DOUBLE_VALUE) values (\'testuuid1\','.$maxposvalue.')');
		$this->executeQuery('insert into POTEST (UUID, DOUBLE_VALUE) values (\'testuuid2\','.$minnegvalue.')');
		$this->executeQuery('insert into POTEST (UUID, DOUBLE_VALUE) values (\'testuuid3\','.$maxnegvalue.')');
		// Extract datatable
		$datatable = $this->getPersistenceAdapter()->getDataTable('select DOUBLE_VALUE from POTEST order by UUID');
		$datamatrix = $datatable->getDataMatrix();
		// Check strings for correct conversion
		$this->assertEquals($minposvalue, $datamatrix[0][0], 'Minimum positive double value is not converted to string as expected.');
		$this->assertEquals($maxposvalue, $datamatrix[1][0], 'Maximum positive double value is not converted to string as expected.');
		$this->assertEquals($minnegvalue, $datamatrix[2][0], 'Minimum negative double value is not converted to string as expected.');
		$this->assertEquals($maxnegvalue, $datamatrix[3][0], 'Maximum negative double value is not converted to string as expected.');
	}

	/**
	 * Tests the correct conversion of text datatypes into strings of a
	 * maximum length of 4000 (ORACLE limits) characters when reading from 
	 * database and putting the data into a datatable.
	 * Results must contain all special characters and must be UTF8 encoded.
	 */
	public function testGetDataTableTextDataType() {
		// Insert values into database
		$value = str_pad('', 4000, '         °!"§$%&/()=?`*\'>; :_+öä#<,.-²³¼¹½¬{[]}\\¸~’–…·|@\t\r\n'); // Must be 80 chars to pad correctly to 4000 characters
		$escapedvalue = $this->escape($value);
		$this->executeQuery('insert into POTEST (UUID, TEXT_VALUE) values (\'testuuid0\',\''.$escapedvalue.'\')');
		// Extract datatable
		$datatable = $this->getPersistenceAdapter()->getDataTable('select TEXT_VALUE from POTEST order by UUID');
		$datamatrix = $datatable->getDataMatrix();
		// Check strings for correct conversion
		$resultstring = $datamatrix[0][0]; 
		$this->assertEquals(4000, strlen($resultstring), 'Result string has not the correct length.');
		$this->assertEquals($value, $resultstring, 'Result string is not the same as the given one.');
		$this->assertEquals('UTF-8', mb_detect_encoding($resultstring), 'The string encoding is not UTF8.');
	}

	/**
	 * Tests the correct conversion of datetime datatypes into strings when
	 * reading from database and putting the data into a datatable.
	 * The result must be a string in the format yyyy-mm-dd hh:ii:ss between 
	 * 1970-01-01 00:00:00 and 3999-12-31 23:59:59.
	 */
	public function testGetDataTableDateTimeDataType() {
		// Insert border values into database
		$minvalue = '1900-01-01 00:00:00';
		$maxvalue = '3999-12-31 23:59:59';
		$this->executeQuery('insert into POTEST (UUID, DATETIME_VALUE) values (\'testuuid0\',\''.$minvalue.'\')');
		$this->executeQuery('insert into POTEST (UUID, DATETIME_VALUE) values (\'testuuid1\',\''.$maxvalue.'\')');
		// Extract datatable
		$datatable = $this->getPersistenceAdapter()->getDataTable('select DATETIME_VALUE from POTEST order by UUID');
		$datamatrix = $datatable->getDataMatrix();
		// Check strings for correct conversion
		$this->assertEquals($minvalue, $datamatrix[0][0], 'Minimum datetime value is not converted to string as expected.');
		$this->assertEquals($maxvalue, $datamatrix[1][0], 'Maximum datetime value is not converted to string as expected.');
	}

	/**
	 * Tests the correct conversion of strings into boolean datatypes when
	 * writing to database.
	 * Input: either "1" for true or "0" for false.
	 */
	public function testSaveDataTableBooleanDataType() {
		$minvalue = '0';
		$maxvalue = '1';
		// Store datatable
		$datatable = new avorium_core_data_DataTable(2, 2);
		$datatable->setHeader(0, 'UUID');
		$datatable->setHeader(1, 'BOOLEAN_VALUE');
		$datatable->setCellValue(0, 0, 'testuuid0');
		$datatable->setCellValue(0, 1, $minvalue);
		$datatable->setCellValue(1, 0, 'testuuid1');
		$datatable->setCellValue(1, 1, $maxvalue);
		$this->getPersistenceAdapter()->saveDataTable('POTEST', $datatable);
		// Read database via SQL
		$results = $this->executeQuery('select BOOLEAN_VALUE from POTEST order by UUID');
		// Compare values
		$this->assertEquals($minvalue, $results[0][0], 'Minimum boolean value is not converted from string as expected.');
		$this->assertEquals($maxvalue, $results[1][0], 'Maximum boolean value is not converted from string as expected.');
	}

	/**
	 * Tests the correct conversion of strings into integer datatypes when
	 * writing to database.
	 * Input: between "-2147483648" and "2147483647".
	 */
	public function testSaveDataTableIntegerDataType() {
		$minvalue = '-2147483648';
		$maxvalue = '2147483647';
		// Store datatable
		$datatable = new avorium_core_data_DataTable(2, 2);
		$datatable->setHeader(0, 'UUID');
		$datatable->setHeader(1, 'INT_VALUE');
		$datatable->setCellValue(0, 0, 'testuuid0');
		$datatable->setCellValue(0, 1, $minvalue);
		$datatable->setCellValue(1, 0, 'testuuid1');
		$datatable->setCellValue(1, 1, $maxvalue);
		$this->getPersistenceAdapter()->saveDataTable('POTEST', $datatable);
		// Read database via SQL
		$results = $this->executeQuery('select INT_VALUE from POTEST order by UUID');
		// Compare values
		$this->assertEquals($minvalue, $results[0][0], 'Minimum integer value is not converted from string as expected.');
		$this->assertEquals($maxvalue, $results[1][0], 'Maximum integer value is not converted from string as expected.');
	}

	/**
	 * Tests the correct storage of strings with a maximum length of 255
	 * characters when writing into the database.
	 * Input can contain all special characters and is UTF8 encoded.
	 */
	public function testSaveDataTableStringDataType() {
		$value = str_pad('', 255, '°!"§$%&/()=?`*\'>; :_+öä#<,.-²³¼¹½¬{[]}\\¸~’–…·|@\t\r\n');
		// Store datatable
		$datatable = new avorium_core_data_DataTable(1, 2);
		$datatable->setHeader(0, 'UUID');
		$datatable->setHeader(1, 'STRING_VALUE');
		$datatable->setCellValue(0, 0, 'testuuid0');
		$datatable->setCellValue(0, 1, $value);
		$this->getPersistenceAdapter()->saveDataTable('POTEST', $datatable);
		// Read database via SQL
		$results = $this->executeQuery('select STRING_VALUE from POTEST order by UUID');
		// Compare values
		$resultstring = $results[0][0];
		$this->assertEquals(255, strlen($resultstring), 'Result string has not the correct length.');
		$this->assertEquals($value, $resultstring, 'Result string is not the same as the given one.');
		$this->assertEquals('UTF-8', mb_detect_encoding($resultstring), 'The string encoding is not UTF8.');
	}

	/**
	 * Tests the correct conversion of strings into decimal datatypes when
	 * writing to database.
	 * Input can be strings with optional negative signs and a dot as 
	 * decimal point character, no thousand-separators, e.g. "-1234.567".
	 */
	public function testSaveDataTableDecimalDataType() {
		$minvalue = '-9999999999999999999.9999999999';
		$maxvalue = '9999999999999999999.9999999999';
		// Store datatable
		$datatable = new avorium_core_data_DataTable(2, 2);
		$datatable->setHeader(0, 'UUID');
		$datatable->setHeader(1, 'DECIMAL_VALUE');
		$datatable->setCellValue(0, 0, 'testuuid0');
		$datatable->setCellValue(0, 1, $minvalue);
		$datatable->setCellValue(1, 0, 'testuuid1');
		$datatable->setCellValue(1, 1, $maxvalue);
		$this->getPersistenceAdapter()->saveDataTable('POTEST', $datatable);
		// Read database via SQL
		$results = $this->executeQuery('select DECIMAL_VALUE from POTEST order by UUID');
		// Compare values
		$this->assertEquals($minvalue, $results[0][0], 'Minimum decimal value is not converted from string as expected.');
		$this->assertEquals($maxvalue, $results[1][0], 'Maximum decimal value is not converted from string as expected.');
	}

	/**
	 * Tests the correct conversion of strings into double datatypes when
	 * writing to database.
	 * Input is in the format (uppercase "E") "-2.22507485850719E-30" 
	 * to "1.79769313486230E+308"
	 */
	public function testSaveDataTableDoubleDataType() {
		$minposvalue = '2.22507485850719E-308';
		$maxposvalue = '1.79769313486230E308';
		$minnegvalue = '-2.22507485850719E-308';
		$maxnegvalue = '-1.79769313486230E308';
		// Store datatable
		$datatable = new avorium_core_data_DataTable(4, 2);
		$datatable->setHeader(0, 'UUID');
		$datatable->setHeader(1, 'DOUBLE_VALUE');
		$datatable->setCellValue(0, 0, 'testuuid0');
		$datatable->setCellValue(0, 1, $minposvalue);
		$datatable->setCellValue(1, 0, 'testuuid1');
		$datatable->setCellValue(1, 1, $maxposvalue);
		$datatable->setCellValue(2, 0, 'testuuid2');
		$datatable->setCellValue(2, 1, $minnegvalue);
		$datatable->setCellValue(3, 0, 'testuuid3');
		$datatable->setCellValue(3, 1, $maxnegvalue);
		$this->getPersistenceAdapter()->saveDataTable('POTEST', $datatable);
		// Read database via SQL
		$results = $this->executeQuery('select DOUBLE_VALUE from POTEST order by UUID');
		// Compare values
		$this->assertEquals($minposvalue, $results[0][0], 'Minimum positive double value is not converted from string as expected.');
		$this->assertEquals($maxposvalue, $results[1][0], 'Maximum positive double value is not converted from string as expected.');
		$this->assertEquals($minnegvalue, $results[2][0], 'Minimum negative double value is not converted from string as expected.');
		$this->assertEquals($maxnegvalue, $results[3][0], 'Maximum negative double value is not converted from string as expected.');
	}

	/**
	 * Tests the correct storage of strings with a maximum length of 65535
	 * characters when writing into the database.
	 * Input can contain all special characters and is UTF8 encoded.
	 */
	public function testSaveDataTableTextDataType() {
		$value = str_pad('', 4000, '         °!"§$%&/()=?`*\'>; :_+öä#<,.-²³¼¹½¬{[]}\\¸~’–…·|@\t\r\n'); // Must be 80 chars to pad correctly to 4000 characters
		// Store datatable
		$datatable = new avorium_core_data_DataTable(1, 2);
		$datatable->setHeader(0, 'UUID');
		$datatable->setHeader(1, 'TEXT_VALUE');
		$datatable->setCellValue(0, 0, 'testuuid0');
		$datatable->setCellValue(0, 1, $value);
		$this->getPersistenceAdapter()->saveDataTable('POTEST', $datatable);
		// Read database via SQL
		$results = $this->executeQuery('select TEXT_VALUE from POTEST order by UUID');
		// Compare values
		$resultstring = $results[0][0];
		$this->assertEquals(4000, strlen($resultstring), 'Result string has not the correct length.');
		$this->assertEquals($value, $resultstring, 'Result string is not the same as the given one.');
		$this->assertEquals('UTF-8', mb_detect_encoding($resultstring), 'The string encoding is not UTF8.');
	}

	/**
	 * Tests the correct conversion of strings into datetime datatypes when
	 * writing to database.
	 * The input is a string in the format yyyy-mm-dd hh:ii:ss between 
	 * 1970-01-01 00:00:00 and 3999-12-31 23:59:59.
	 */
	public function testSaveDataTableDateTimeDataType() {
		$minvalue = '1900-01-01 00:00:00';
		$maxvalue = '3999-12-31 23:59:59';
		// Store datatable
		$datatable = new avorium_core_data_DataTable(2, 2);
		$datatable->setHeader(0, 'UUID');
		$datatable->setHeader(1, 'DATETIME_VALUE');
		$datatable->setCellValue(0, 0, 'testuuid0');
		$datatable->setCellValue(0, 1, $minvalue);
		$datatable->setCellValue(1, 0, 'testuuid1');
		$datatable->setCellValue(1, 1, $maxvalue);
		$this->getPersistenceAdapter()->saveDataTable('POTEST', $datatable);
		// Read database via SQL
		$results = $this->executeQuery('select DATETIME_VALUE from POTEST order by UUID');
		// Compare values
		$this->assertEquals($minvalue, $results[0][0], 'Minimum datetime value is not converted from string as expected.');
		$this->assertEquals($maxvalue, $results[1][0], 'Maximum datetime value is not converted from string as expected.');
	}

    // helper functions

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
    
    /**
     * Escapes the given string for using it in SQL queries.
     */
    protected abstract function escape($string);
    
    /**
     * The derived class should return a persistence adapter which has an
     * errornous database connection and which cannot make queries correctly.
     */
    protected abstract function getErrornousPersistenceAdapter();
}
