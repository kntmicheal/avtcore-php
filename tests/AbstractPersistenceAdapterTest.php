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

require_once '../code/avorium/core/persistence/AbstractPersistenceAdapter.php';
require_once 'AbstractPersistenceAdapterTestPersistentObject.php';

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
        $po->longValue = -9223372036854775808;
        $po->stringValue = 'Hallo Welt!';
        // Store new record
        $this->getPersistenceAdapter()->save($po);
        // Get record back from database
        $result = $this->executeQuery('select * from potest where uuid=\''.$po->uuid.'\'');
        // Records must be unique
        $this->assertEquals($po->booleanValue, (bool)$result[0]['BOOLEAN_VALUE'], 'Boolean value from database differs from the boolean value of the persistent object.');
        $this->assertEquals($po->intValue, intval($result[0]['INT_VALUE']), 'Integer value from database differs from the int value of the persistent object.');
        $this->assertEquals($po->longValue, floatval($result[0]['LONG_VALUE']), 'Long value from database differs from the int value of the persistent object.');
        $this->assertEquals($po->stringValue, $result[0]['STRING_VALUE'], 'String value from database differs from the string value of the persistent object.');
    }

    /**
     * Tests creating a single record, updating its values and then retreiving
     * the updated values back from the database
     */
    public function testUpdateSingleRecord() {
        // Create record
        $po = new AbstractPersistenceAdapterTestPersistentObject();
        $po->booleanValue = true;
        $po->intValue = 2147483647;
        $po->longValue = -9223372036854775808;
        $po->stringValue = 'Hallo Welt!';
        // Store new record
        $this->getPersistenceAdapter()->save($po);
        // Remember uuid
        $uuid = $po->uuid;
        // Update values;
        $po->booleanValue = false;
        $po->intValue = -2147483646;
        $po->longValue = 9223372036854775807;
        $po->stringValue = 'Guten Morgen!';
        // Update record
        $this->getPersistenceAdapter()->save($po);
        // Get record back from database
        $result = $this->executeQuery('select * from potest where uuid=\''.$uuid.'\'');
        // Records must be unique
        $this->assertEquals($po->booleanValue, (bool)$result[0]['BOOLEAN_VALUE'], 'Boolean value from database differs from the boolean value of the persistent object.');
        $this->assertEquals($po->intValue, intval($result[0]['INT_VALUE']), 'Integer value from database differs from the int value of the persistent object.');
        $this->assertEquals($po->longValue, floatval($result[0]['LONG_VALUE']), 'Long value from database differs from the int value of the persistent object.');
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
        $long = -23456789;
        $string = 'avorium';
        // Write data to the database
        $this->executeQuery('insert into potest (uuid, BOOLEAN_VALUE, INT_VALUE, LONG_VALUE, STRING_VALUE) values (\''.$uuid.'\','.($bool ? 1:0).','.$int.', '.$long.', \''.$string.'\')');
        // Read data out and cast it to persistent object
        $po = $this->getPersistenceAdapter()->get('AbstractPersistenceAdapterTestPersistentObject', $uuid);
        var_dump($po);
        // Compare properties
        $this->assertEquals($uuid, $po->uuid, 'Uuid value from persistent object differs from the boolean value of the database.');
        $this->assertEquals($bool, $po->booleanValue, 'Boolean value from persistent object differs from the boolean value of the database.');
        $this->assertEquals($int, $po->intValue, 'Integer value from persistent object differs from the int value of the database.');
        $this->assertEquals($long, $po->longValue, 'Long value from persistent object differs from the int value of the database.');
        $this->assertEquals($string, $po->stringValue, 'String value from persistent object differs from the string value of the database.');
    }
    // Einzelnen Datensatz löschen
    // Mehrere Datensätze auslesen
    // Multi-Daten-SQL mit persistenten Objekten ausführen und dabei Casting prüfen
    // Single-Daten-SQL mit persistenten Objekten ausführen
    // Multi-Daten-SQL ohne persistente Objekten ausführen
    // Single-Daten-SQL ohne persistente Objekten ausführen
    // SQL ohne Rückgabe ausführen
    // Einfache Werte aus Datenbank lesen und casten
    // Tabellen anlegen
    // Tabellen erweitern

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
