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

require_once dirname(__FILE__).'/../code/avorium/core/persistence/OraclePersistenceAdapter.php';
require_once dirname(__FILE__).'/AbstractPersistenceAdapterTest.php';

/**
 * Persistence adapter tests especially for ORACLE databases.
 */
class OraclePersistenceAdapterTest extends AbstractPersistenceAdapterTest {
	
    /**
     * Defines the ORACLE persistence adapter to be used and prepares the
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
        $this->oci = oci_connect(
            $this->username, 
            $this->password,
            $this->host,
            $this->nlslang
        );
        // Clean database tables by recreating them
        $this->executeQuery('BEGIN  EXECUTE IMMEDIATE \'DROP TABLE POTEST\'; EXCEPTION WHEN OTHERS THEN IF SQLCODE != -942 THEN RAISE; END IF; END;');
        $this->executeQuery('create table POTEST (UUID NVARCHAR2(40) NOT NULL, BOOLEAN_VALUE NUMBER(1), INT_VALUE NUMBER(38), STRING_VALUE NVARCHAR2(255), PRIMARY KEY (UUID))');
    }

    protected function executeQuery($query) {
        $result = array();
		$statement = oci_parse($this->oci, $query);
        oci_execute($statement);
		if (oci_statement_type($statement) === 'SELECT') {
			while ($row = oci_fetch_array($statement)) {
				$result[] = $row;
			}
		}
		oci_free_statement($statement);
        return $result;
    }

    protected function escape($string) {
		return str_replace('\'', '\'\'', $string);
    }

    protected function getErrornousPersistenceAdapter() {
        return new avorium_core_persistence_OraclePersistenceAdapter(
            'wronghost', 
            'wrongusername', 
            'wrongpassword',
            'wrongnlslang'
        );
    }

	protected function createTestTable() {
        $this->executeQuery('create table POTEST (UUID NVARCHAR2(40) NOT NULL, PRIMARY KEY (UUID))');
	}

	// All other test methods are defined in the parent abstract class.
}
