<?php

/* 
public function The MIT License
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

require_once dirname(__FILE__).'/../../code/avorium/core/persistence/MySqlPersistenceAdapter.php';
require_once dirname(__FILE__).'/AbstractCsvWebServiceTest.php';

/**
 * Tests the functionality of the CsvWebService class for MySQL databases.
 */
class test_io_MySqlCsvWebServiceTest extends test_io_AbstractCsvWebServiceTest {
	
    /**
     * Defines the MySQL persistence adapter to be used and prepares the
     * database (cleans tables).
     */
    protected function setUp() {
        parent::setUp();
		$this->host = $GLOBALS['TEST_MYSQL_DB_HOST'];
		$this->database = $GLOBALS['TEST_MYSQL_DB_DATABASE'];
		$this->username = $GLOBALS['TEST_MYSQL_DB_USERNAME']; 
		$this->password = $GLOBALS['TEST_MYSQL_DB_PASSWORD'];
        $this->persistenceAdapter = 
            new avorium_core_persistence_MySqlPersistenceAdapter(
                $this->host, 
                $this->database, 
                $this->username, 
                $this->password
            );
        $this->mysqli = mysqli_connect(
            $this->host, 
            $this->username, 
            $this->password, 
            $this->database
        );
        // Clean database tables by recreating them
        $this->mysqli->query('drop table POTEST');
        $this->mysqli->query('CREATE TABLE POTEST ('
				. 'UUID VARCHAR(40) NOT NULL, '
				. 'STRING_VALUE_1 varchar(255), '
				. 'STRING_VALUE_2 varchar(255), '
				. 'PRIMARY KEY (UUID))');
    }
	
	/**
	 * Closes opened database connections.
	 */
	protected function tearDown() {
		$this->mysqli->close();
		parent::tearDown();
	}
}