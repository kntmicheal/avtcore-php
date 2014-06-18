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
require_once dirname(__FILE__).'/AbstractPersistenceAdapterTestPersistentObjectDifferentType.php';
require_once dirname(__FILE__).'/AbstractPersistenceAdapterTestPersistentObjectUnknownType.php';

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
        $po = new AbstractPersistenceAdapterTestPersistentObject();
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
        $po = $this->getPersistenceAdapter()->get('AbstractPersistenceAdapterTestPersistentObject', $uuid);
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
            ['UUID' => 'uuid1tRMR1', 'bool' => 0, 'int' => 10, 'string' => 'testReadMultipleRecords 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => 1, 'int' => 20, 'string' => 'testReadMultipleRecords 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => 0, 'int' => 30, 'string' => 'testReadMultipleRecords 3']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        }
        // Read data out and cast it to persistent object
        $pos = $this->getPersistenceAdapter()->getAll('AbstractPersistenceAdapterTestPersistentObject');
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
        $po = $this->getPersistenceAdapter()->get('AbstractPersistenceAdapterTestPersistentObjectWithIncompleteMetadata', $uuid);
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
     * with incomplete annotations.
     */
    public function testGetMissingMetadata() {
        $uuid = 'abcdefg';
        $bool = true;
        $int = 1234567;
        $string = 'avorium';
        // Write data to the database
        $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object with incomplete metadata
        $this->setExpectedException('Exception', 'Could not determine table name from persistent object annotations.');
        // Here should come up an exception. It should not be possible to match to an class without knowing from which table to extract the date
        $this->getPersistenceAdapter()->get('AbstractPersistenceAdapterTestPersistentObjectWithoutMetadata', $uuid);
    }
    
    /**
     * Tests the correct behaviour of the getAll() function with persistent objects 
     * with incomplete annotations.
     */
    public function testGetAllMissingMetadata() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => 0, 'int' => 10, 'string' => 'testReadMultipleRecords 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => 1, 'int' => 20, 'string' => 'testReadMultipleRecords 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => 0, 'int' => 30, 'string' => 'testReadMultipleRecords 3']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        }
        // Read data out and cast it to persistent object with incomplete metadata
        $this->setExpectedException('Exception', 'Could not determine table name from persistent object annotations.');
        // Here should come up an exception. It should not be possible to match to an class without knowing from which table to extract the date
        $this->getPersistenceAdapter()->getAll('AbstractPersistenceAdapterTestPersistentObjectWithoutMetadata');
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
        $po = $this->getPersistenceAdapter()->executeSingleResultQuery($query, 'AbstractPersistenceAdapterTestPersistentObjectWithoutMetadata');
        // Should return a result because when casting to an object the missing class metadata is irrelevant
        $this->assertNotNull($po, 'The result is null.');
    }
    
    /**
     * Tests the correct behaviour of the executeMultipleResultQuery() function
     * with persistent objects with incomplete annotations.
     */
    public function testMultipleResultMissingMetadata() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => 0, 'int' => 10, 'string' => 'testReadMultipleRecords 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => 1, 'int' => 20, 'string' => 'testReadMultipleRecords 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => 0, 'int' => 30, 'string' => 'testReadMultipleRecords 3']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        }
        $query = 'select * from POTEST where UUID in (\'uuid1tRMR1\',\'uuid1tRMR2\',\'uuid1tRMR3\')';
        $pos = $this->getPersistenceAdapter()->executeMultipleResultQuery($query, 'AbstractPersistenceAdapterTestPersistentObjectWithoutMetadata');
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
        $this->getPersistenceAdapter()->get('AbstractPersistenceAdapterTestNoPersistentObject', $uuid);
    }
    
    /**
     * Tests the reading of data from a database vie getAll() when given class is
     * not a persistent object class.
     */
    public function testGetAllNoPersistentObject() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'bool' => 0, 'int' => 10, 'string' => 'testReadMultipleRecords 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => 1, 'int' => 20, 'string' => 'testReadMultipleRecords 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => 0, 'int' => 30, 'string' => 'testReadMultipleRecords 3']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
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
        $this->getPersistenceAdapter()->updateOrCreateTable('AbstractPersistenceAdapterTestPersistentObject');
        // Insert test data
        $uuid = 'abcdefg';
        $bool = true;
        $int = 1234567;
        $string = 'avorium';
        // Write data to the database
        $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object
        $po = $this->getPersistenceAdapter()->get('AbstractPersistenceAdapterTestPersistentObject', $uuid);
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
        $this->getPersistenceAdapter()->updateOrCreateTable('AbstractPersistenceAdapterTestPersistentObject');
        // Insert test data
        $uuid = 'abcdefg';
        $bool = true;
        $int = 1234567;
        $string = 'avorium';
        // Write data to the database
        $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', \''.$string.'\')');
        // Read data out and cast it to persistent object
        $po = $this->getPersistenceAdapter()->get('AbstractPersistenceAdapterTestPersistentObject', $uuid);
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
            ['UUID' => 'uuid1tRMR1', 'bool' => 0, 'int' => 10, 'string' => 'testReadMultipleRecords 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => 1, 'int' => 20, 'string' => 'testReadMultipleRecords 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => 0, 'int' => 30, 'string' => 'testReadMultipleRecords 3']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->executeQuery('insert into POTEST (UUID, BOOLEAN_VALUE, INT_VALUE, STRING_VALUE) values (\''.$record['UUID'].'\','.($record['bool'] ? 1:0).','.$record['int'].', \''.$record['string'].'\')');
        }
        // Read data out and cast it to persistent object
        $query = 'select * from POTEST where UUID in (\'uuid1tRMR1\',\'uuid1tRMR2\',\'uuid1tRMR3\')';
        $pos = $this->getPersistenceAdapter()->executeMultipleResultQuery($query, 'AbstractPersistenceAdapterTestPersistentObject');
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
        $po = $this->getPersistenceAdapter()->executeSingleResultQuery($query, 'AbstractPersistenceAdapterTestPersistentObject');
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
        $po = $this->getPersistenceAdapter()->get('AbstractPersistenceAdapterTestPersistentObject', $readuuid);
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
        $this->getPersistenceAdapter()->updateOrCreateTable('AbstractPersistenceAdapterTestPersistentObjectDifferentType');
    }

    /**
     * Tests the use of a type in annotations which is not known to the 
     * persistence adapter when writing to database
     */
    public function testCastTypeUnknownWhenWriting() {
        // Create record
        $po = new AbstractPersistenceAdapterTestPersistentObjectUnknownType();
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
        $this->getPersistenceAdapter()->get('AbstractPersistenceAdapterTestPersistentObjectUnknownType', $uuid);
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
        $this->getPersistenceAdapter()->updateOrCreateTable('AbstractPersistenceAdapterTestPersistentObjectUnknownType');
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
        $this->getPersistenceAdapter()->updateOrCreateTable('AbstractPersistenceAdapterTestPersistentObjectUnknownType');
    }
    
    /**
     * Tests the use of special characters in string when writing to and
     * reading from database. This includes SQL injections which could be
     * performed, when the database does not handle the quotation marks
     * (', ", `, ’) correctly.
     */
    public function testSpecialCharacters() {
        // Create record
        $potowrite = new AbstractPersistenceAdapterTestPersistentObject();
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
        $potoread = $this->getPersistenceAdapter()->get('AbstractPersistenceAdapterTestPersistentObject', $uuid);
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
            ['UUID' => 'uuid1tRMR1', 'bool' => 0, 'int' => 10, 'string' => 'testReadMultipleRecords 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => 1, 'int' => 20, 'string' => 'testReadMultipleRecords 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => 0, 'int' => 30, 'string' => 'testReadMultipleRecords 3']
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
            ['UUID' => 'uuid1tRMR1', 'bool' => 0, 'int' => 10, 'string' => 'testReadMultipleRecords 1'],
            ['UUID' => 'uuid1tRMR2', 'bool' => 1, 'int' => 20, 'string' => 'testReadMultipleRecords 2'],
            ['UUID' => 'uuid1tRMR3', 'bool' => 0, 'int' => 30, 'string' => 'testReadMultipleRecords 3']
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
        $po = new AbstractPersistenceAdapterTestPersistentObject();
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
        $po = $this->getPersistenceAdapter()->executeSingleResultQuery($query, 'AbstractPersistenceAdapterTestPersistentObject');
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
        $po = $this->getPersistenceAdapter()->executeSingleResultQuery($query, 'AbstractPersistenceAdapterTestPersistentObject');
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
