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

require_once dirname(__FILE__).'/../../code/avorium/core/persistence/OraclePersistenceAdapter.php';
require_once dirname(__FILE__).'/AbstractCsvWebServiceTest.php';

/**
 * Tests the functionality of the CsvWebService class for Oracle databases.
 */
class test_io_OracleCsvWebServiceTest extends test_io_AbstractCsvWebServiceTest {
	
    /**
     * Defines the MySQL persistence adapter to be used and prepares the
     * database (cleans tables).
     */
    protected function setUp() {
        parent::setUp();
		$this->host = $GLOBALS['TEST_ORACLE_DB_HOST'];
		$this->username = $GLOBALS['TEST_ORACLE_DB_USERNAME']; 
		$this->password = $GLOBALS['TEST_ORACLE_DB_PASSWORD'];
                $this->nlslang= $GLOBALS['TEST_ORALCE_NLS_LANG'];
        $this->persistenceAdapter = 
            new avorium_core_persistence_OraclePersistenceAdapter(
                $this->host, 
                $this->username, 
                $this->password,
                $this->nlslang
            );
        // Clean database tables by recreating them
        $this->persistenceAdapter->executeNoResultQuery('BEGIN  EXECUTE IMMEDIATE \'DROP TABLE POTEST\'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;');
        $this->persistenceAdapter->executeNoResultQuery('CREATE TABLE POTEST ('
				. 'UUID NVARCHAR2(40) NOT NULL, '
				. 'STRING_VALUE_1 NVARCHAR2(255), '
				. 'STRING_VALUE_2 NVARCHAR2(255), '
				. 'PRIMARY KEY (UUID))');
    }

	/**
	 * Closes database connection of persistence adapter used in tests
	 */
	protected function tearDown() {
		oci_close($this->persistenceAdapter->getDatabase());
		parent::tearDown();
	}

}