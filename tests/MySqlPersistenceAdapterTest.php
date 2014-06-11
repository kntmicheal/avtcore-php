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

require_once '../code/avorium/core/persistence/MySqlPersistenceAdapter.php';
require_once 'AbstractPersistenceAdapterTest.php';

/**
 * Persistence adapter tests especially for MySQL databases.
 */
class MySqlPersistenceAdapterTest extends AbstractPersistenceAdapterTest {
	
	private $host = '192.168.1.3';
	private $database = 'avtcoretest';
	private $username = 'avtcoretest';
	private $password = 'avtcoretest';
	
	/**
	 * Defines the MySQL persistence adapter to be used and prepares the
	 * database (cleans tables).
	 */
	protected function setUp() {
		parent::setUp();
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
		// Clean database tables
		$this->mysqli->query('delete from potest'); // Table of AbstractPersistenceAdapterTestPersistentObject 
	}

	protected function executeQuery($query) {
		$resultset = $this->mysqli->query($query);
		$result = array();
		if ($resultset === true || $resultset === false) {
                    return $result;
                } // Can happen with statements which have no result (CREATE TABLE)
		while ($row = $resultset->fetch_array()) {
			$result[] = $row;
		}
		return $result;
	}

	// All test methods are defined in the parent abstract class.
}
