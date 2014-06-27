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

require_once dirname(__FILE__).'/../../code/avorium/core/persistence/OraclePersistenceAdapter.php';
require_once dirname(__FILE__).'/AbstractPersistenceAdapterTest.php';

/**
 * Persistence adapter tests especially for ORACLE databases.
 */
class test_persistence_OraclePersistenceAdapterTest 
extends test_persistence_AbstractPersistenceAdapterTest {
	
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
        $this->executeQuery('CREATE TABLE POTEST ('
				. 'UUID NVARCHAR2(40) NOT NULL, '
				. 'BOOLEAN_VALUE NUMBER(1), '
				. 'INT_VALUE NUMBER(38), '
				. 'STRING_VALUE NVARCHAR2(255), '
				. 'DECIMAL_VALUE NUMBER(30,10), '
				. 'DOUBLE_VALUE BINARY_DOUBLE, '
				. 'TEXT_VALUE VARCHAR2(4000), '
				. 'DATETIME_VALUE DATE, '
				. 'PRIMARY KEY (UUID))');
    }
	
	/**
	 * Closes opened database connections.
	 */
	protected function tearDown() {
		oci_close($this->oci);
		parent::tearDown();
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

	// Must be overwritten, because the double values must be casted before putting them into the database
	public function testGetDataTableDoubleDataType() {
		// Temporarily set the decimal characters to english ones to have correct number formats in resulting strings
		oci_execute(oci_parse($this->oci, 'begin EXECUTE IMMEDIATE \'ALTER SESSION set nls_numeric_characters=".,"\';end;'));
		// Insert border values into database
		$minposvalue = '2.22507485850719E-308';
		$maxposvalue = '1.79769313486230E308';
		$minnegvalue = '-2.22507485850719E-308';
		$maxnegvalue = '-1.79769313486230E308';
		$this->executeQuery('insert into POTEST (UUID, DOUBLE_VALUE) values (\'testuuid0\',to_binary_double(\''.$minposvalue.'\'))');
		$this->executeQuery('insert into POTEST (UUID, DOUBLE_VALUE) values (\'testuuid1\',to_binary_double(\''.$maxposvalue.'\'))');
		$this->executeQuery('insert into POTEST (UUID, DOUBLE_VALUE) values (\'testuuid2\',to_binary_double(\''.$minnegvalue.'\'))');
		$this->executeQuery('insert into POTEST (UUID, DOUBLE_VALUE) values (\'testuuid3\',to_binary_double(\''.$maxnegvalue.'\'))');
		// Extract datatable
		$datatable = $this->getPersistenceAdapter()->getDataTable('select DOUBLE_VALUE from POTEST');
		$datamatrix = $datatable->getDataMatrix();
		// Check strings for correct conversion
		$this->assertEquals($minposvalue, $datamatrix[0][0], 'Minimum positive double value is not converted to string as expected.');
		$this->assertEquals($maxposvalue, $datamatrix[1][0], 'Maximum positive double value is not converted to string as expected.');
		$this->assertEquals($minnegvalue, $datamatrix[2][0], 'Minimum negative double value is not converted to string as expected.');
		$this->assertEquals($maxnegvalue, $datamatrix[3][0], 'Maximum negative double value is not converted to string as expected.');
	}

	// Overwritten to correctly put datetime values into the database
	public function testGetDataTableDateTimeDataType() {
		// Insert border values into database
		$minvalue = '1900-01-01 00:00:00';
		$maxvalue = '3999-12-31 23:59:59';
		$this->executeQuery('insert into POTEST (UUID, DATETIME_VALUE) values (\'testuuid0\',to_date(\''.$minvalue.'\',\'yyyy-mm-dd hh24:mi:ss\'))');
		$this->executeQuery('insert into POTEST (UUID, DATETIME_VALUE) values (\'testuuid1\',to_date(\''.$maxvalue.'\',\'yyyy-mm-dd hh24:mi:ss\'))');
		// Extract datatable
		$datatable = $this->getPersistenceAdapter()->getDataTable('select DATETIME_VALUE from POTEST');
		$datamatrix = $datatable->getDataMatrix();
		// Check strings for correct conversion
		$this->assertEquals($minvalue, $datamatrix[0][0], 'Minimum datetime value is not converted to string as expected.');
		$this->assertEquals($maxvalue, $datamatrix[1][0], 'Maximum datetime value is not converted to string as expected.');
	}

	// All other test methods are defined in the parent abstract class.
}
