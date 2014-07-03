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

require_once dirname(__FILE__).'/../../code/avorium/core/io/CsvWebService.php';

/**
 * Tests the functionality of the CsvWebService class. Derived classes
 * must provide a persistence adapter.
 */
abstract class test_io_AbstractCsvWebServiceTest extends PHPUnit_Framework_TestCase {

	
	/**
	 * Derived test classes must do the following steps:
	 * - Initialize $this->persistenceAdapter with a valid database specific persistence adapter
	 * - Drop and recreate the database table POTEST with following columns
	 *   - UUID (string with 40 characters, primary key)
	 *   - STRING_VALUE_1 (string with 255 characters)
	 *   - STRING_VALUE_2 (string with 255 characters)
	 */
	protected function setUp() {
        parent::setUp();
	}

	/**
	 * Positive test for setting the persistence adapter
	 */
	public function testSetPersistenceAdapter() {
		$cws = new avorium_core_io_CsvWebService();
		$cws->setPersistenceAdapter($this->getPersistenceAdapter());
	}
	
	/**
	 * When the given persistence adapter is null, an exception should be 
	 * thrown.
	 */
	public function testSetPersistenceAdapterNull() {
		$cws = new avorium_core_io_CsvWebService();
		$this->setExpectedException('Exception', 'Persistence adapter must not be null.');
		$cws->setPersistenceAdapter(null);
	}

	/**
	 * When the given persistence adapter is not of the correct type, an 
	 * exception should be thrown.
	 */
	public function testSetPersistenceAdapterWrongType() {
		$cws = new avorium_core_io_CsvWebService();
		$this->setExpectedException('Exception', 'Persistence adapter must be of type avorium_core_persistence_AbstractPersistenceAdapter or derived.');
		$cws->setPersistenceAdapter(1234);
	}
	
	/**
	 * Calling parseRequest with null content should result in an exception.
	 */
	public function testParseRequestContentNull() {
		$cws = new avorium_core_io_CsvWebService();
		$cws->setPersistenceAdapter($this->getPersistenceAdapter());
		$this->setExpectedException('Exception', 'The request content must not be null.');
		$cws->parseRequest(null);
	}
	
	/**
	 * Calling parseRequest with no or an unknown action in the first line
	 * should result in an exception.
	 */
	public function testParseRequestFirstLineUnknownAction() {
		$cws = new avorium_core_io_CsvWebService();
		$cws->setPersistenceAdapter($this->getPersistenceAdapter());
		$this->setExpectedException('Exception', 'Unknown action: "unknownaction"');
		$cws->parseRequest("unknownaction\nadditionalcontent");
	}
	
	/**
	 * Calling parseRequest with "execute" as action expects an SQL statement
	 * in the second line. When this is not given an exception should be thrown.
	 */
	public function testParseRequestExecuteNoSecondLine() {
		$cws = new avorium_core_io_CsvWebService();
		$cws->setPersistenceAdapter($this->getPersistenceAdapter());
		$this->setExpectedException('Exception', 'There is no second line.');
		$cws->parseRequest("execute");
	}
	
	/**
	 * Calling parseRequest with "save" as action expects a table name
	 * in the second line. When this is not given an exception should be thrown.
	 */
	public function testParseRequestSaveNoSecondLine() {
		$cws = new avorium_core_io_CsvWebService();
		$cws->setPersistenceAdapter($this->getPersistenceAdapter());
		$this->setExpectedException('Exception', 'There is no second line.');
		$cws->parseRequest("save");
	}
	
	/**
	 * Calling parseRequest with "save" as action requires a CSDV structure
	 * starting in the third line. Without this line an exception should be
	 * thrown.
	 */
	public function testParseRequestSaveNoThirdLine() {
		$cws = new avorium_core_io_CsvWebService();
		$cws->setPersistenceAdapter($this->getPersistenceAdapter());
		$this->setExpectedException('Exception', 'There is no third line.');
		$cws->parseRequest("save\nPOTEST");
	}
	
	/**
	 * Positive test for the "execute" action with checking the behaviour for
	 * special characters.
	 */
	public function testExecute() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'string1' => 'testReadMultipleRecords 1', 'string2' => 'testReadMultipleRecords 1 2'],
            ['UUID' => 'uuid1tRMR2', 'string1' => 'testReadMultipleRecords 2', 'string2' => 'testReadMultipleRecords 2 2'],
            ['UUID' => 'uuid1tRMR3', 'string1' => 'testReadMultipleRecords 3', 'string2' => 'testReadMultipleRecords 3 2']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->getPersistenceAdapter()->executeNoResultQuery('insert into POTEST (UUID, STRING_VALUE_1, STRING_VALUE_2) values (\''.$record['UUID'].'\', \''.$record['string1'].'\', \''.$record['string2'].'\')');
        }
		// Read back by converting it to CSV content
		$cws = new avorium_core_io_CsvWebService();
		$cws->setPersistenceAdapter($this->getPersistenceAdapter());
		$result = $cws->parseRequest('execute
select UUID, STRING_VALUE_1, STRING_VALUE_2
from POTEST 
order by UUID');
		$this->assertNotNull($result, 'The result is null but should be a CSV string.');
		$expectedstring = "\"UUID\",\"STRING_VALUE_1\",\"STRING_VALUE_2\"\n\"uuid1tRMR1\",\"testReadMultipleRecords 1\",\"testReadMultipleRecords 1 2\"\n\"uuid1tRMR2\",\"testReadMultipleRecords 2\",\"testReadMultipleRecords 2 2\"\n\"uuid1tRMR3\",\"testReadMultipleRecords 3\",\"testReadMultipleRecords 3 2\"\n";
		$this->assertEquals($expectedstring, $result, 'The result CSV string is not as expected.');
	}
	
	/**
	 * When the "execute" action is performed without previously setting a
	 * persistence adapter, an exception should be thrown.
	 */
	public function testExecutePersistenceAdapterNull() {
		$cws = new avorium_core_io_CsvWebService();
		$this->setExpectedException('Exception', 'Persistence adapter is null.');
		$cws->parseRequest('execute
select UUID, STRING_VALUE_1, STRING_VALUE_2
from POTEST 
order by UUID');
	}
	
	/**
	 * The "execute" action requires a SQL statement. Whithout this an
	 * exception should be thrown.
	 */
	public function testExecuteQueryEmpty() {
		$cws = new avorium_core_io_CsvWebService();
		$cws->setPersistenceAdapter($this->getPersistenceAdapter());
		$this->setExpectedException('Exception'); // Here a database specific exception comes up
		$cws->parseRequest("execute\n");
	}
	
	/**
	 * Errornous SQL statements in the "execute" action should result in
	 * an exception.
	 */
	public function testExecuteWrongQuery() {
		$cws = new avorium_core_io_CsvWebService();
		$cws->setPersistenceAdapter($this->getPersistenceAdapter());
		$this->setExpectedException('Exception'); // Here a database specific exception comes up
		$cws->parseRequest("execute\nThis query is not executable");
	}
	
	/**
	 * When calling the "execute" action with a statement which does not return
	 * any result (insert, delete, etc.) the return value should be null.
	 */
	public function testExecuteNoResultQuery() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'string1' => 'testReadMultipleRecords 1', 'string2' => 'testReadMultipleRecords 1 2'],
            ['UUID' => 'uuid1tRMR2', 'string1' => 'testReadMultipleRecords 2', 'string2' => 'testReadMultipleRecords 2 2'],
            ['UUID' => 'uuid1tRMR3', 'string1' => 'testReadMultipleRecords 3', 'string2' => 'testReadMultipleRecords 3 2']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->getPersistenceAdapter()->executeNoResultQuery('insert into POTEST (UUID, STRING_VALUE_1, STRING_VALUE_2) values (\''.$record['UUID'].'\', \''.$record['string1'].'\', \''.$record['string2'].'\')');
        }
		// Delete content
		$cws = new avorium_core_io_CsvWebService();
		$cws->setPersistenceAdapter($this->getPersistenceAdapter());
		$result = $cws->parseRequest("execute\ndelete from POTEST");
		$this->assertNull($result, 'The result is not null but should be.');
		// Check that the table content is deleted (no result query is performed)
		$dbcontent = $this->getPersistenceAdapter()->executeMultipleResultQuery('select * from POTEST');
		$this->assertEquals(0, count($dbcontent), 'The table content was not deleted / the no result query seems not to be executed.');
	}
	
	/**
	 * Positive test for the "save" action with checking the behaviour for
	 * special characters.
	 */
	public function testSave() {
		$cws = new avorium_core_io_CsvWebService();
		$cws->setPersistenceAdapter($this->getPersistenceAdapter());
		$result = $cws->parseRequest('save
POTEST
"UUID","STRING_VALUE_1","STRING_VALUE_2"
"1","Record One","Record Two"
"2","°!""§$%&/()=?`"   ,    "Ü*\'ÄÖ_:;L,.-.lö\\\\ä\""+üp#"
"3","„¡“¶¢[]|{}≠¿ """"""    ","“¬”#£ﬁ^˜·¯˙˚„¡“¶¢≠„„¡¡““¶¶¢¢[]|{}≠≠7/"


"4","“¬”#£ﬁ^˜·¯˙\˙¯·˜\^ﬁ£#”¬","∞…–œæ‘±•πø⁄¨Ω†®€∑«å‚∂ƒ©ªº∆@œ…∞µ~∫√ç≈¥"
"5","Trallala
Hoppsassa","Fidel ""Holly,

Hase,"",
bumms"
"6","∞µºª∫©√çƒ∂≈‚∑€®†Ω¨⁄∆øπ≠ø⁄¨Ω†®¢€¶»„‰¸","˝ˇÁÛØ∏Œﬂ÷˛˘›‹◊ÇÙ‡ÅÍ„‰™¸˝ˇÁÛØ∏Œ÷ŒÆ°’Æ—÷"');
		$this->assertNull($result, 'The result should be null on save actions but is not.');
		// Check the database content
		$dbcontent = $this->getPersistenceAdapter()->executeMultipleResultQuery('select UUID, STRING_VALUE_1, STRING_VALUE_2 from POTEST order by UUID');
        $this->assertEquals(6, count($dbcontent), 'Row count not as expected.');
        // Compare the cells
        $this->assertEquals('1', $dbcontent[0]->UUID, 'Content of cell 0/0 is not as expected.');
        $this->assertEquals('Record One', $dbcontent[0]->STRING_VALUE_1, 'Content of cell 0/1 is not as expected.');
        $this->assertEquals('Record Two', $dbcontent[0]->STRING_VALUE_2, 'Content of cell 0/2 is not as expected.');
        $this->assertEquals('2', $dbcontent[1]->UUID, 'Content of cell 1/0 is not as expected.');
        $this->assertEquals('°!"§$%&/()=?`', $dbcontent[1]->STRING_VALUE_1, 'Content of cell 1/1 is not as expected.');
        $this->assertEquals('Ü*'."'".'ÄÖ_:;L,.-.lö\\\\ä\"+üp#', $dbcontent[1]->STRING_VALUE_2, 'Content of cell 1/2 is not as expected.');
        $this->assertEquals('3', $dbcontent[2]->UUID, 'Content of cell 2/0 is not as expected.');
        $this->assertEquals('„¡“¶¢[]|{}≠¿ """    ', $dbcontent[2]->STRING_VALUE_1, 'Content of cell 2/1 is not as expected.');
        $this->assertEquals('“¬”#£ﬁ^˜·¯˙˚„¡“¶¢≠„„¡¡““¶¶¢¢[]|{}≠≠7/', $dbcontent[2]->STRING_VALUE_2, 'Content of cell 2/2 is not as expected.');
        $this->assertEquals('4', $dbcontent[3]->UUID, 'Content of cell 3/0 is not as expected.');
        $this->assertEquals('“¬”#£ﬁ^˜·¯˙\˙¯·˜\^ﬁ£#”¬', $dbcontent[3]->STRING_VALUE_1, 'Content of cell 3/1 is not as expected.');
        $this->assertEquals('∞…–œæ‘±•πø⁄¨Ω†®€∑«å‚∂ƒ©ªº∆@œ…∞µ~∫√ç≈¥', $dbcontent[3]->STRING_VALUE_2, 'Content of cell 3/2 is not as expected.');
        $this->assertEquals('5', $dbcontent[4]->UUID, 'Content of cell 4/0 is not as expected.');
        $this->assertEquals("Trallala\r\nHoppsassa", $dbcontent[4]->STRING_VALUE_1, 'Content of cell 4/1 is not as expected.');
        $this->assertEquals("Fidel \"Holly,\r\n\r\nHase,\",\r\nbumms", $dbcontent[4]->STRING_VALUE_2, 'Content of cell 4/2 is not as expected.');
        $this->assertEquals('6', $dbcontent[5]->UUID, 'Content of cell 5/0 is not as expected.');
        $this->assertEquals('∞µºª∫©√çƒ∂≈‚∑€®†Ω¨⁄∆øπ≠ø⁄¨Ω†®¢€¶»„‰¸', $dbcontent[5]->STRING_VALUE_1, 'Content of cell 5/1 is not as expected.');
        $this->assertEquals('˝ˇÁÛØ∏Œﬂ÷˛˘›‹◊ÇÙ‡ÅÍ„‰™¸˝ˇÁÛØ∏Œ÷ŒÆ°’Æ—÷', $dbcontent[5]->STRING_VALUE_2, 'Content of cell 5/2 is not as expected.');
	}
	
	/**
	 * Tests the "save" function for inserting new records with an unknown UUID.
	 */
	public function testSaveInsert() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'string1' => 'testReadMultipleRecords 1', 'string2' => 'testReadMultipleRecords 1 2'],
            ['UUID' => 'uuid1tRMR2', 'string1' => 'testReadMultipleRecords 2', 'string2' => 'testReadMultipleRecords 2 2'],
            ['UUID' => 'uuid1tRMR3', 'string1' => 'testReadMultipleRecords 3', 'string2' => 'testReadMultipleRecords 3 2']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->getPersistenceAdapter()->executeNoResultQuery('insert into POTEST (UUID, STRING_VALUE_1, STRING_VALUE_2) values (\''.$record['UUID'].'\', \''.$record['string1'].'\', \''.$record['string2'].'\')');
        }
		// Insert new record
		$cws = new avorium_core_io_CsvWebService();
		$cws->setPersistenceAdapter($this->getPersistenceAdapter());
		$result = $cws->parseRequest('save
POTEST
"UUID","STRING_VALUE_1","STRING_VALUE_2"
"uuid1tRMR4","Record One","Record Two"');
		$this->assertNull($result, 'The result should be null on save actions but is not.');
		// Check the database content
		$dbcontent = $this->getPersistenceAdapter()->executeMultipleResultQuery('select UUID, STRING_VALUE_1, STRING_VALUE_2 from POTEST order by UUID');
        $this->assertEquals(4, count($dbcontent), 'Row count not as expected.');
        // Compare the cells of the last entry
        $this->assertEquals('uuid1tRMR4', $dbcontent[3]->UUID, 'Uuid is not as expected.');
        $this->assertEquals('Record One', $dbcontent[3]->STRING_VALUE_1, 'String value 1 is not as expected.');
        $this->assertEquals('Record Two', $dbcontent[3]->STRING_VALUE_2, 'String value 2 is not as expected.');
	}
	
	/**
	 * Tests the "save" function for updating existing records with known UUIDs.
	 */
	public function testSaveUpdate() {
        $records = [
            ['UUID' => 'uuid1tRMR1', 'string1' => 'testReadMultipleRecords 1', 'string2' => 'testReadMultipleRecords 1 2'],
            ['UUID' => 'uuid1tRMR2', 'string1' => 'testReadMultipleRecords 2', 'string2' => 'testReadMultipleRecords 2 2'],
            ['UUID' => 'uuid1tRMR3', 'string1' => 'testReadMultipleRecords 3', 'string2' => 'testReadMultipleRecords 3 2']
        ];
        // Write data to the database
        foreach ($records as $record) {
            $this->getPersistenceAdapter()->executeNoResultQuery('insert into POTEST (UUID, STRING_VALUE_1, STRING_VALUE_2) values (\''.$record['UUID'].'\', \''.$record['string1'].'\', \''.$record['string2'].'\')');
        }
		// Insert new record
		$cws = new avorium_core_io_CsvWebService();
		$cws->setPersistenceAdapter($this->getPersistenceAdapter());
		$result = $cws->parseRequest('save
POTEST
"UUID","STRING_VALUE_1","STRING_VALUE_2"
"uuid1tRMR2","Record One","Record Two"');
		$this->assertNull($result, 'The result should be null on save actions but is not.');
		// Check the database content
		$dbcontent = $this->getPersistenceAdapter()->executeMultipleResultQuery('select UUID, STRING_VALUE_1, STRING_VALUE_2 from POTEST order by UUID');
        $this->assertEquals(3, count($dbcontent), 'Row count not as expected.');
        // Compare the cells of the second entry
        $this->assertEquals('uuid1tRMR2', $dbcontent[1]->UUID, 'Uuid is not as expected.');
        $this->assertEquals('Record One', $dbcontent[1]->STRING_VALUE_1, 'String value 1 is not as expected.');
        $this->assertEquals('Record Two', $dbcontent[1]->STRING_VALUE_2, 'String value 2 is not as expected.');
	}
	
	/**
	 * When the "save" action is performed without previously setting a
	 * persistence adapter, an exception should be thrown.
	 */
	public function testSavePersistenceAdapterNull() {
		$cws = new avorium_core_io_CsvWebService();
		$this->setExpectedException('Exception', 'Persistence adapter is null.');
		$cws->parseRequest('save
POTEST
"UUID","STRING_VALUE_1","STRING_VALUE_2"
"1","Record One","Record Two"');
	}
	
	/**
	 * The "save" action requires a valid table name. When it it empty (e.g.
	 * by having an empty second line in the request) the function should
	 * throw an exception.
	 */
	public function testSaveTableNameEmpty() {
		$cws = new avorium_core_io_CsvWebService();
		$cws->setPersistenceAdapter($this->getPersistenceAdapter());
		$this->setExpectedException('Exception', 'Invalid table name given: ');
		$cws->parseRequest('save

"UUID","STRING_VALUE_1","STRING_VALUE_2"
"1","Record One","Record Two"');
	}
	
	/**
	 * When the table name given with the "save" action is not known to the
	 * database, an exception should be thrown.
	 */
	public function testSaveTableNameUnknown() {
		$cws = new avorium_core_io_CsvWebService();
		$cws->setPersistenceAdapter($this->getPersistenceAdapter());
		$this->setExpectedException('Exception', 'Invalid table name given: UNKNOWNTABLENAME');
		$cws->parseRequest('save
UNKNOWNTABLENAME
"UUID","STRING_VALUE_1","STRING_VALUE_2"
"1","Record One","Record Two"');
	}
	
	/**
	 * The "save" action requires a CSV string which contains at least a column
	 * name line with at least one column. Without that name an exception
	 * should be thrown.
	 */
	public function testSaveNoColumnName() {
		$cws = new avorium_core_io_CsvWebService();
		$cws->setPersistenceAdapter($this->getPersistenceAdapter());
		$this->setExpectedException('Exception', 'Empty column names line.');
		$cws->parseRequest('save
POTEST

"1","Record One","Record Two"');
	}
	
	/**
	 * Giving unknown column names to the "save" action cannot be resolved by
	 * the persistence layer an should result in an exception.
	 */
	public function testSaveColumnNameUnknown() {
		$cws = new avorium_core_io_CsvWebService();
		$cws->setPersistenceAdapter($this->getPersistenceAdapter());
		$this->setExpectedException('Exception'); // Here a database specific exception comes up
		$cws->parseRequest('save
POTEST
"UUID","UNKNOWNCOLUMNNAME","STRING_VALUE_2"
"1","Record One","Record Two"');
	}
	
	/**
	 * When the given CSV string cannot be parsed and converted into an
	 * intermediate datatable, an exception should be thrown.
	 */
	public function testSaveWrongCsv() {
		$cws = new avorium_core_io_CsvWebService();
		$cws->setPersistenceAdapter($this->getPersistenceAdapter());
		$this->setExpectedException('Exception'); // Depending on the type of parser error different exceptions can come up.
		$cws->parseRequest('save
POTEST
Unparseable CSV content');
	}
	
	/**
	 * Updating a table with the "save" action requires the definition of a
	 * column which represents a primary key in the database. Without such a
	 * column the request cannot be stored and an exception should be thrown.
	 */
	public function testSaveNoPrimaryKeyColumn() {
		$cws = new avorium_core_io_CsvWebService();
		$cws->setPersistenceAdapter($this->getPersistenceAdapter());
		$this->setExpectedException('Exception', 'Expected primary key column UUID not found.');
		$cws->parseRequest('save
POTEST
"STRING_VALUE_1","STRING_VALUE_2"
"Record One","Record Two"');
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
}
