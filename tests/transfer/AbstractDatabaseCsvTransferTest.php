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

require_once dirname(__FILE__).'/../../code/avorium/core/transfer/DatabaseCsvTransfer.php';

/**
 * Tests the transfer of CSV data from and to a server. Uses the csvwebservice
 * script on the server which uses the CsvWebservice, CsvParser, DataTable and
 * persistence adapter classes to talk to the underlying database.
 */
abstract class test_remote_AbstractDatabaseCsvTransferTest extends PHPUnit_Framework_TestCase {
	
	protected $serverhandle; // CURL handle
	protected $serverpipes; // CURL pipes
	protected $serverpid; // Process id of the webserver
	protected $testendpoint; // URL of the tes endpoint on the webserver
	
	/**
	 * Derived classes must create a localconfig.php file in the directory
	 * where the csvwebservice.php file resides. This config file must
	 * initialize a persistence adapter and must store it in the global
	 * variable $GLOBALS['PersistenceAdapter'] where the webservice can
	 * read it from and use it.
	 */
	protected abstract function prepareLocalConfigFile($filename);
	
	/**
	 * Starts an internal webserver at port 8888 for the webservice
	 * calls.
	 * Derived test classes must do the following steps:
	 * - Initialize $this->persistenceAdapter with a valid database specific persistence adapter
	 * - Drop and recreate the database table POTEST with following columns
	 *   - UUID (string with 40 characters, primary key)
	 *   - STRING_VALUE (string with 255 characters)
	 */
	protected function setUp() {
		parent::setUp();
		$basedir = dirname(dirname(dirname(__FILE__)));
		$this->testendpoint = 'http://'.$GLOBALS['TEST_WEBSERVER'].'/csvwebservice.php';
		$this->prepareLocalConfigFile($basedir.'/localconfig.php');
		$this->serverhandle = proc_open('php -S '.$GLOBALS['TEST_WEBSERVER'].' -t '.$basedir, array(), $this->serverpipes);
		$this->assertTrue(is_resource($this->serverhandle), 'Could not start internal webserver');
		$this->serverpid = proc_get_status($this->serverhandle)['pid'];
	}
	
	/**
	 * Stops the internal webserver
	 */
	protected function tearDown() {
		// See http://php.net/manual/en/function.proc-terminate.php#113918
		stripos(php_uname('s'), 'win')>-1  ? exec('taskkill /F /T /PID '.$this->serverpid) : exec('kill -9 '.$this->serverpid);
		parent::tearDown();
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
	 * 
     * @return avorium_core_persistence_IPersistenceAdapter Used persistence adapter
     */
    protected function getPersistenceAdapter() {
        if ($this->persistenceAdapter !== null) {
            return $this->persistenceAdapter;
        }
        throw new Exception('No persistence adapter set. This must be done in the setUp() function of a test class');
    }
	
	// Test functions

	/**
	 * Positive test. Requests a datatable via select statement using special 
	 * characters in the database.
	 */
	public function testPullDataTableFromRemoteServer() {
		// Prepare database
        $records = [
            ['UUID' => 'uuid1tRMR1', 'string' => 'testGetDataTable 1'],
            ['UUID' => 'uuid1tRMR2', 'string' => '°!"§$%&/()=?`*\'>; :_+öä#<,.-²³¼¹½¬{[]}\\¸~’–…·|@\t\r\n'],
            ['UUID' => 'uuid1tRMR3', 'string' => 'testGetDataTable 3']
        ];
		$datatabletostore = new avorium_core_data_DataTable(3, 2);
		$datatabletostore->setHeader(0, 'UUID');
		$datatabletostore->setHeader(1, 'STRING_VALUE');
		for ($i = 0; $i < count($records); $i++) {
			$datatabletostore->setCellValue($i, 0, $records[$i]['UUID']);
			$datatabletostore->setCellValue($i, 1, $records[$i]['string']);
		}
		$this->getPersistenceAdapter()->saveDataTable('POTEST', $datatabletostore);
		$datamatrixtostore = $datatabletostore->getDataMatrix();
		$headerstostore = $datatabletostore->getHeaders();
		// Pull from remote server
		$query = "select UUID, STRING_VALUE from POTEST order by UUID";
		$datatablefromserver = avorium_core_transfer_DatabaseCsvTransfer::pullDataTableFromRemoteServer($this->testendpoint, $query);
		$datamatrixfromserver = $datatablefromserver->getDataMatrix();
		$headersfromserver = $datatablefromserver->getHeaders();
		// Compare contents
		$this->assertEquals(count($headerstostore), count($headersfromserver), 'Column count do not match.');
		$this->assertEquals(count($datamatrixtostore), count($datamatrixfromserver), 'Row count do not match.');
		$this->assertEquals($headerstostore[0], $headersfromserver[0], 'Headers UUID do not match.');
		$this->assertEquals($headerstostore[1], $headersfromserver[1], 'Headers STRING_VALUE do not match.');
		for ($i = 0; $i < count($records); $i++) {
			$this->assertEquals($datamatrixtostore[$i][0], $datamatrixfromserver[$i][0], 'UUID do not match in row '.$i);
			$this->assertEquals($datamatrixtostore[$i][1], $datamatrixfromserver[$i][1], 'STRING_VALUE do not match in row '.$i);
		}
	}

	/**
	 * Test the transfer of bigger datatables (10000 record) over the network
	 */
	public function testPullDataTableFromRemoteServerBigDataTable() {
		// Prepare database
		$records = array();
		$recordcount = 10 * 1000;
		for ($i = 0; $i < $recordcount; $i++) {
			$records[] = ['UUID' => 'uuid1tRMR'.str_pad($i, 10, '0', STR_PAD_LEFT), 'string' => '°!"§$%&/()=?`*\'>; :_+öä#<,.-²³¼¹½¬{[]}\\¸~’–…·|@\t\r\n'.$i];
		}
		$datatabletostore = new avorium_core_data_DataTable($recordcount, 2);
		$datatabletostore->setHeader(0, 'UUID');
		$datatabletostore->setHeader(1, 'STRING_VALUE');
		for ($i = 0; $i < $recordcount; $i++) {
			$datatabletostore->setCellValue($i, 0, $records[$i]['UUID']);
			$datatabletostore->setCellValue($i, 1, $records[$i]['string']);
		}
		$this->getPersistenceAdapter()->saveDataTable('POTEST', $datatabletostore);
		$datamatrixtostore = $datatabletostore->getDataMatrix();
		$headerstostore = $datatabletostore->getHeaders();
		// Pull from remote server
		$query = "select UUID, STRING_VALUE from POTEST order by UUID";
		$datatablefromserver = avorium_core_transfer_DatabaseCsvTransfer::pullDataTableFromRemoteServer($this->testendpoint, $query);
		$datamatrixfromserver = $datatablefromserver->getDataMatrix();
		$headersfromserver = $datatablefromserver->getHeaders();
		// Compare contents
		$this->assertEquals(count($headerstostore), count($headersfromserver), 'Column count do not match.');
		$this->assertEquals(count($datamatrixtostore), count($datamatrixfromserver), 'Row count do not match.');
		$this->assertEquals($headerstostore[0], $headersfromserver[0], 'Headers UUID do not match.');
		$this->assertEquals($headerstostore[1], $headersfromserver[1], 'Headers STRING_VALUE do not match.');
		for ($i = 0; $i < $recordcount; $i++) {
			$this->assertEquals($datamatrixtostore[$i][0], $datamatrixfromserver[$i][0], 'UUID do not match in row '.$i);
			$this->assertEquals($datamatrixtostore[$i][1], $datamatrixfromserver[$i][1], 'STRING_VALUE do not match in row '.$i);
		}
	}

	/**
	 * When the endpoint is null, the function should throw an exception.
	 */
	public function testPullDataTableFromRemoteServerEndpointNull() {
		$query = "select UUID, STRING_VALUE from POTEST order by UUID";
		$this->setExpectedException('Exception', 'The remote endpoint URL must not be null.');
		avorium_core_transfer_DatabaseCsvTransfer::pullDataTableFromRemoteServer(null, $query);
	}

	/**
	 * When the endpoint is wrong, the function should throw an exception.
	 */
	public function testPullDataTableFromRemoteServerWrongEndpoint() {
		$query = "select UUID, STRING_VALUE from POTEST order by UUID";
		$this->setExpectedException('Exception', 'Cannot connect to the remote endpoint.');
		avorium_core_transfer_DatabaseCsvTransfer::pullDataTableFromRemoteServer('http://i.hope.this.url.will.never.be/valid.php', $query);
	}

	/**
	 * When the query is null, the function should throw an exception.
	 */
	public function testPullDataTableFromRemoteServerQueryNull() {
		$this->setExpectedException('Exception', 'The query must not be null.');
		avorium_core_transfer_DatabaseCsvTransfer::pullDataTableFromRemoteServer($this->testendpoint, null);
	}

	/**
	 * When the query is wrong, the function should throw an exception.
	 */
	public function testPullDataTableFromRemoteServerWrongQuery() {
		$query = "invalid sql query";
		$this->setExpectedException('Exception', 'An error occured on the remote host.');
		avorium_core_transfer_DatabaseCsvTransfer::pullDataTableFromRemoteServer($this->testendpoint, $query);
	}

}
