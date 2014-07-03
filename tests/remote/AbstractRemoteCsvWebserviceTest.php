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

require_once dirname(__FILE__).'/../../code/avorium/core/data/CsvParser.php';

/**
 * Tests the transfer of CSV data from and to a server. Uses the csvwebservice
 * script on the server which uses the CsvWebservice, CsvParser, DataTable and
 * persistence adapter classes to talk to the underlying database.
 */
abstract class test_remote_AbstractRemoteCsvWebserviceTest extends PHPUnit_Framework_TestCase {
	
	protected $serverhandle;
	protected $serverpipes;
	protected $serverpid;
	
	/**
	 * Derived classes must create a localconfig.php file in the directory
	 * where the csvwebservice.php file resides. This config file must
	 * initialize a persistence adapter and must store it in the global
	 * variable $GLOBALS['PersistenceAdapter'] where the webservice can
	 * read it from and use it.
	 */
	protected abstract function prepareLocalConfigFile($filename);
	
	/**
	 * Performs a request to the webservice and returns the HTTP status code
	 * and sets the response.
	 * 
	 * @param string $response Here the response from the webservice call will
	 * be written into.
	 * @param string $postcontent When null, a GET request is done, otherwise
	 * the given content is sent via POST to the webservice.
	 * @return int HTTP status code of the request.
	 */
	private function doRequest(&$response, $postcontent = null) {
		$ch = curl_init('http://'.$GLOBALS['TEST_WEBSERVER'].'/csvwebservice.php');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if ($postcontent !== null) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postcontent); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain')); 
		}
		$response = curl_exec($ch);
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		return $code;
	}
	
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
	 * When calling the webservice via GET, it should return an error 400
	 * because only POST requests are supported.
	 */
	public function testWebserviceGetMethod() {
		$response = null;
		$code = $this->doRequest($response);
		$this->assertEquals(400, $code, 'Calling the webservice via GET should return a 400 error code.');
	}
	
	/**
	 * Tests the execute function of the webservice with special characters
	 */
	public function testWebserviceExecute() {
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
		// Request content via remote webservice
		$postcontent = "execute\nselect UUID, STRING_VALUE from POTEST order by UUID";
		$response = null;
		$code = $this->doRequest($response, $postcontent);
		$this->assertEquals(200, $code, 'Calling the webservices execute function should return a 200 code.');
		// Convert content to datatable
		$datatablefromserver = avorium_core_data_CsvParser::convertCsvToDataTable($response);
		// Compare contents
		$this->assertEquals(count($datatabletostore->getHeaders()), count($datatablefromserver->getHeaders()), 'Column count do not match.');
		$this->assertEquals(count($datatabletostore->getDataMatrix()), count($datatablefromserver->getDataMatrix()), 'Row count do not match.');
		$this->assertEquals($datatabletostore->getHeaders()[0], $datatablefromserver->getHeaders()[0], 'Headers UUID do not match.');
		$this->assertEquals($datatabletostore->getHeaders()[1], $datatablefromserver->getHeaders()[1], 'Headers STRING_VALUE do not match.');
		for ($i = 0; $i < count($records); $i++) {
			$this->assertEquals($datatabletostore->getDataMatrix()[$i][0], $datatablefromserver->getDataMatrix()[$i][0], 'UUID do not match in row '.$i);
			$this->assertEquals($datatabletostore->getDataMatrix()[$i][1], $datatablefromserver->getDataMatrix()[$i][1], 'STRING_VALUE do not match in row '.$i);
		}
	}

	/**
	 * Tests the execute function and provocates an exception by giving a
	 * wrong SQL statement. This should result in a 400 response code.
	 */
	public function testWebserviceExecuteException() {
		$postcontent = "execute\nincorrect SQL statement";
		$response = null;
		$code = $this->doRequest($response, $postcontent);
		$this->assertEquals(400, $code, 'Provocating an exception with the execute function on the server does not result in an 400 error.');
	}

	/**
	 * Tests the save function of the webservice with special characters
	 */
	public function testWebserviceSave() {
		// Prepare CSV content
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
		$csv = avorium_core_data_CsvParser::convertDataTableToCsv($datatabletostore);
		// Send CSV content to server
		$postcontent = "save\nPOTEST\n".$csv;
		$response = null;
		$code = $this->doRequest($response, $postcontent);
		$this->assertEquals(200, $code, 'Calling the webservices save function should return a 200 code.');
		// Read out database
		$datatablefromserver = $this->getPersistenceAdapter()->getDataTable('select UUID, STRING_VALUE from POTEST order by UUID');
		// Compare contents
		$this->assertEquals(count($datatabletostore->getHeaders()), count($datatablefromserver->getHeaders()), 'Column count do not match.');
		$this->assertEquals(count($datatabletostore->getDataMatrix()), count($datatablefromserver->getDataMatrix()), 'Row count do not match.');
		$this->assertEquals($datatabletostore->getHeaders()[0], $datatablefromserver->getHeaders()[0], 'Headers UUID do not match.');
		$this->assertEquals($datatabletostore->getHeaders()[1], $datatablefromserver->getHeaders()[1], 'Headers STRING_VALUE do not match.');
		for ($i = 0; $i < count($records); $i++) {
			$this->assertEquals($datatabletostore->getDataMatrix()[$i][0], $datatablefromserver->getDataMatrix()[$i][0], 'UUID do not match in row '.$i);
			$this->assertEquals($datatabletostore->getDataMatrix()[$i][1], $datatablefromserver->getDataMatrix()[$i][1], 'STRING_VALUE do not match in row '.$i);
		}
	}

	/**
	 * Tests the save function and provocates an exception by giving a wrong
	 * table name. This should result in a 400 response code.
	 */
	public function testWebserviceSaveException() {
		$postcontent = "save\nUNKNOWNTABLENAME\n\"UUID\",\"STRING_VALUE\"\n\"uuid1\",\"string1\"";
		$response = null;
		$code = $this->doRequest($response, $postcontent);
		$this->assertEquals(400, $code, 'Provocating an exception with the save function on the server does not result in an 400 error.');
	}

}
