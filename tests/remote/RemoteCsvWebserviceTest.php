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

/**
 * Tests the transfer of CSV data from and to a server. Uses the csvwebservice
 * script on the server which uses the CsvWebservice, CsvParser, DataTable and
 * persistence adapter classes to talk to the underlying database.
 */
class test_remote_RemoteCsvWebserviceTest extends PHPUnit_Framework_TestCase {
	
	protected $serverhandle;
	protected $serverpipes;
	protected $serverpid;
	
	private function prepareLocalConfigFile($filename) {
		$config = "<?php\n"
				."require_once dirname(__FILE__).'/code/avorium/core/persistence/MySqlPersistenceAdapter.php';\n"
				.'$GLOBALS[\'PersistenceAdapter\'] = new avorium_core_persistence_MySqlPersistenceAdapter(\''.$GLOBALS['TEST_MYSQL_DB_HOST'].'\', \''.$GLOBALS['TEST_MYSQL_DB_DATABASE'].'\', \''.$GLOBALS['TEST_MYSQL_DB_USERNAME'].'\', \''.$GLOBALS['TEST_MYSQL_DB_PASSWORD'].'\');';
		file_put_contents($filename, $config);
	}
	
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
	
	protected function setUp() {
		parent::setUp();
		$basedir = dirname(dirname(dirname(__FILE__)));
		$this->prepareLocalConfigFile($basedir.'/localconfig.php');
		$this->serverhandle = proc_open('php -S '.$GLOBALS['TEST_WEBSERVER'].' -t '.$basedir, array(), $this->serverpipes);
		$this->assertTrue(is_resource($this->serverhandle), 'Could not start internal webserver');
		$this->serverpid = proc_get_status($this->serverhandle)['pid'];
	}
	
	protected function tearDown() {
		// See http://php.net/manual/en/function.proc-terminate.php#113918
		stripos(php_uname('s'), 'win')>-1  ? exec('taskkill /F /T /PID '.$this->serverpid) : exec('kill -9 '.$this->serverpid);
		parent::tearDown();
	}
	
	public function testGet() {
		$response = null;
		$code = $this->doRequest($response);
		$this->assertEquals(400, $code, 'Calling the webservice via GET should return a 400 error code.');
	}
	
	public function testIrgendwas() {
		$response = null;
		$code = $this->doRequest($response, 'Trallala');
		var_dump($code);
		var_dump($response);
	}
}
