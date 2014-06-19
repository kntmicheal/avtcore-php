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

require_once dirname(__FILE__).'/../../code/avorium/core/data/DataTable.php';

/**
 * Tests the functionality of the DataTable class
 */
class test_data_DataTableTest extends PHPUnit_Framework_TestCase {

	/**
	 * Positive test for checking that all functions of the datatable class
	 * work properly in a normal use case with correct data.
	 * This test creates a table with 3 rows and columns
	 */
	public function testCreateAndUseCorrectDataTable() {
		$expectedheadernames = [ 'First column', 'Second column', 'Third column' ];
		$expectedcellvalues = [
			[1, false, 'Hello world'],
			[null, true, array()],
			[12345678901234567890, new stdClass(), '']
		];
		// Create datatable
		$datatable = new avorium_core_data_DataTable(3, 3);
		// Fill header names
		for ($i = 0; $i < count($expectedheadernames); $i++) {
			$datatable->setHeader($i, $expectedheadernames[$i]);
		}
		// Fill cells
		for ($i = 0; $i < count($expectedcellvalues); $i++) {
			for ($j = 0; $j < count($expectedcellvalues[$i]); $j++) {
				$datatable->setCellValue($i, $j, $expectedcellvalues[$i][$j]);
			}
		}
		// Check header names
		$headernames = $datatable->getHeaders();
		$this->assertEquals(count($expectedheadernames), count($headernames), 'The number of returned header names is not as expected.');
		for ($i = 0; $i < count($expectedheadernames); $i++) {
			$this->assertEquals($expectedheadernames[$i], $headernames[$i], 'The header name with index '.$i.' is not as expected.');
		}
		// Check cells
		$cellvalues = $datatable->getDataMatrix();
		$this->assertEquals(count($expectedcellvalues), count($cellvalues), 'The number of returned rows is not as expected.');
		for ($i = 0; $i < count($expectedcellvalues); $i++) {
			$this->assertEquals(count($expectedcellvalues[$i]), count($cellvalues[$i]), 'The number of returned cells of row '.$i.' is not as expected.');
			for ($j = 0; $j < count($expectedcellvalues[$i]); $j++) {
				$this->assertEquals($expectedcellvalues[$i][$j], $cellvalues[$i][$j], 'The cell with index '.$j.' in row '.$i.' has not the expected value.');
			}
		}
	}
	
	/**
	 * Checks the behaviour of the constructor when a non integer variable is
	 * given as row count. In this case the constructor must throw an exception
	 * because this would be an error which cannot be handled by the datatable.
	 */
	public function testConstructorRowCountNoInt() {
		$this->setExpectedException('Exception', 'The row count must be an integer.');
		new avorium_core_data_DataTable('no int', 5);
	}
	
	/**
	 * Checks the behaviour of the constructor when a row count less than one
	 * is given. In this case the datatable would be useless so an exception
	 * is expected.
	 */
	public function testConstructorRowCountLessThanOne() {
		$this->setExpectedException('Exception', 'The row count must be greater than zero.');
		new avorium_core_data_DataTable(0, 5);
	}
	
	/**
	 * Checks the behaviour of the constructor when a non integer variable is
	 * given as column count. In this case the constructor must throw an 
	 * exception because this would be an error which cannot be handled by the 
	 * datatable.
	 */
	public function testConstructorColumnCountNoInt() {
		$this->setExpectedException('Exception', 'The column count must be an integer.');
		new avorium_core_data_DataTable(5, 'no int');
	}
	
	/**
	 * Checks the behaviour of the constructor when a column count less than one
	 * is given. In this case the datatable would be useless so an exception
	 * is expected.
	 */
	public function testConstructorColumnCountLessThanOne() {
		$this->setExpectedException('Exception', 'The column count must be greater than zero.');
		new avorium_core_data_DataTable(5, 0);
	}
	
	/**
	 * Creates a datatable and checks the header names and the cells.
	 * The header name array must have column count elements and the data matrix
	 * must be of size row count x column count. All header names and cells
	 * must be null.
	 */
	public function testEmptyTable() {
		// Create emty table
		$datatable = new avorium_core_data_DataTable(3, 3);
		// Check header names
		$headernames = $datatable->getHeaders();
		for ($i = 0; $i < count($headernames); $i++) {
			$this->assertNull($headernames[$i], 'The header name in column '.$i.' is not null.');
		}
		// Check cell values
		$cellvalues = $datatable->getDataMatrix();
		for ($i = 0; $i < count($cellvalues); $i++) {
			for ($j = 0; $j < count($cellvalues[$i]); $j++) {
				$this->assertNull($cellvalues[$i][$j], 'The cell value in row '.$i.' in column '.$j.' is not null.');
			}
		}
	}
	
	/**
	 * When giving a column index variable to setHeader, which is no int,
	 * the function must throw an exception.
	 */
	public function testSetHeaderColumnIndexNoInt() {
		$datatable = new avorium_core_data_DataTable(1, 1);
		$this->setExpectedException('Exception', 'The column index must be an integer.');
		$datatable->setHeader('no int', 'Header name');
	}
	
	/**
	 * When giving a column index variable less than zero to setHeader,
	 * an exception must be thrown because only values between zero
	 * (including) and column count (excluding) are allowed.
	 */
	public function testSetHeaderColumnIndexLessThanZero() {
		$datatable = new avorium_core_data_DataTable(1, 1);
		$this->setExpectedException('Exception', 'The column index must be larger than zero.');
		$datatable->setHeader(-1, 'Header name');
	}
	
	/**
	 * When giving a column index variable larger than the maximun column
	 * count minus one to setHeader,
	 * an exception must be thrown because only values between zero
	 * (including) and column count (excluding) are allowed.
	 */
	public function testSetHeaderColumnIndexLargerThanColumnCountMinusOne() {
		$datatable = new avorium_core_data_DataTable(1, 1);
		$this->setExpectedException('Exception', 'The column index must be smaller than the column count.');
		$datatable->setHeader(1, 'Header name');
	}
	
	/**
	 * Headers can have no names, so givin null as name parameter to setHeader
	 * is valid. But we must check whether null is set correctly to the 
	 * header name and that null is neither ignored nor converted to an 
	 * empty string.
	 */
	public function testSetHeaderNameNull() {
		$datatable = new avorium_core_data_DataTable(1, 1);
		// First set header to a correct value
		$datatable->setHeader(0, 'Header name');
		// Check the returned header
		$this->assertEquals('Header name', $datatable->getHeaders()[0], 'The header name was not set to the wanted value.');
		// Now set the header name to null and check it
		$datatable->setHeader(0, null);
		$this->assertNull($datatable->getHeaders()[0], 'The header name was not set to null.');
	}
	
	/**
	 * Header names can either be null or a string. All other datatypes
	 * are not allowed, so an exception must be thrown here.
	 */
	public function testSetHeaderNameNoString() {
		$datatable = new avorium_core_data_DataTable(1, 1);
		$this->setExpectedException('Exception', 'The header name must be null or a string.');
		$datatable->setHeader(0, false);
	}
	
	/**
	 * When giving a row index variable to setCellValue, which is no int,
	 * the function must throw an exception.
	 */
	public function testSetCellValueRowIndexNoInt() {
		$datatable = new avorium_core_data_DataTable(1, 1);
		$this->setExpectedException('Exception', 'The row index must be an integer.');
		$datatable->setCellValue('no int', 0, 'value');
	}
	
	/**
	 * When giving a row index variable less than zero to setCellValue,
	 * an exception must be thrown because only values between zero
	 * (including) and row count (excluding) are allowed.
	 */
	public function testSetCellValueRowIndexLessThanZero() {
		$datatable = new avorium_core_data_DataTable(1, 1);
		$this->setExpectedException('Exception', 'The row index must be larger than zero.');
		$datatable->setCellValue(-1, 0, 'value');
	}
	
	/**
	 * When giving a row index variable larger than the maximun row
	 * count minus one to setCellValue,
	 * an exception must be thrown because only values between zero
	 * (including) and row count (excluding) are allowed.
	 */
	public function testSetCellValueRowIndexLargerThanRowCountMinusOne() {
		$datatable = new avorium_core_data_DataTable(1, 1);
		$this->setExpectedException('Exception', 'The row index must be smaller than the row count.');
		$datatable->setCellValue(1, 0, 'value');
	}
	
	/**
	 * When giving a column index variable to setCellValue, which is no int,
	 * the function must throw an exception.
	 */
	public function testSetCellValueColumnIndexNoInt() {
		$datatable = new avorium_core_data_DataTable(1, 1);
		$this->setExpectedException('Exception', 'The column index must be an integer.');
		$datatable->setCellValue(0, 'no int', 'value');
	}
	
	/**
	 * When giving a column index variable less than zero to setCellValue,
	 * an exception must be thrown because only values between zero
	 * (including) and column count (excluding) are allowed.
	 */
	public function testSetCellValueColumnIndexLessThanZero() {
		$datatable = new avorium_core_data_DataTable(1, 1);
		$this->setExpectedException('Exception', 'The column index must be larger than zero.');
		$datatable->setCellValue(0, -1, 'value');
	}
	
	/**
	 * When giving a column index variable larger than the maximun column
	 * count minus one to setCellValue,
	 * an exception must be thrown because only values between zero
	 * (including) and column count (excluding) are allowed.
	 */
	public function testSetCellValueColumnIndexLargerThanColumnCountMinusOne() {
		$datatable = new avorium_core_data_DataTable(1, 1);
		$this->setExpectedException('Exception', 'The column index must be smaller than the column count.');
		$datatable->setCellValue(0, 1, 'value');
	}
	
	/**
	 * Setting cell values to null is allowed. But we must check whether
	 * null is set correctly to the cell and that null is neither ignored
	 * nor converted to an empty string.
	 */
	public function testSetCellValueNull() {
		$datatable = new avorium_core_data_DataTable(1, 1);
		// First set cell to a correct value
		$datatable->setCellValue(0, 0, 'value');
		// Check the returned cell value
		$this->assertEquals('value', $datatable->getDataMatrix()[0][0], 'The cell value was not set to the wanted value.');
		// Now set the header name to null and check it
		$datatable->setCellValue(0, 0, null);
		$this->assertNull($datatable->getDataMatrix()[0][0], 'The cell value was not set to null.');
	}
	
	/**
	 * When obtaining the header names with getHeaders, the manipulation of the
	 * returned array must not have any influence on the header names in the
	 * datatable. It is expected that the returned array is a copy of the
	 * header names and not a reference to the internal array.
	 */
	public function testGetHeadersModifyReturnedData() {
		// Prepare datatable
		$expectedheadernames = [ 'First column', 'Second column', 'Third column' ];
		$datatable = new avorium_core_data_DataTable(3, 3);
		for ($i = 0; $i < count($expectedheadernames); $i++) {
			$datatable->setHeader($i, $expectedheadernames[$i]);
		}
		// Add header name
		$headernamesaddname = $datatable->getHeaders();
		$headernamesaddname[] = 'Fourth column';
		$this->assertEquals(4, count($headernamesaddname), 'The header name was not added.');
		$this->assertNotEquals(count($datatable->getHeaders()), count($headernamesaddname), 'The added header name seems to be stored in the datatable but this must not be.');
		// Remove header name
		$headernamesremovename = $datatable->getHeaders();
		array_splice($headernamesremovename, 0, 1);
		$this->assertEquals(2, count($headernamesremovename), 'The header name was not removed.');
		$this->assertNotEquals(count($datatable->getHeaders()), count($headernamesremovename), 'The removed header name seems to be removed from the datatable but this must not be.');
		// Change content of header name
		$headernamesreplacename = $datatable->getHeaders();
		$headernamesreplacename[0] = 'New name';
		$this->assertEquals('New name', $headernamesreplacename[0], 'The header name was not replaced correctly.');
		$this->assertNotEquals($datatable->getHeaders()[0], $headernamesreplacename[0], 'The header name seems to be updated in the datatable but this must not be.');
	}
	
	/**
	 * When obtaining the data matrix with getMatrix, the manipulation of the
	 * returned array and its sub arrays must not have any influence on the
	 * cells and internal array structure in the datatable. It is expected that
	 * the returned array structure is a copy of the data matrix and not a 
	 * reference to the internal array structure.
	 */
	public function testGetMatrixModifyReturnedData() {
		// Prepare datatable
		$expectedcellvalues = [
			[1, false, 'Hello world'],
			[null, true, array()],
			[12345678901234567890, new stdClass(), '']
		];
		$datatable = new avorium_core_data_DataTable(3, 3);
		for ($i = 0; $i < count($expectedcellvalues); $i++) {
			for ($j = 0; $j < count($expectedcellvalues[$i]); $j++) {
				$datatable->setCellValue($i, $j, $expectedcellvalues[$i][$j]);
			}
		}
		// Add row
		$cellvaluesaddrow = $datatable->getDataMatrix();
		$cellvaluesaddrow[] = ['one', 'two', 'three'];
		$this->assertEquals(4, count($cellvaluesaddrow), 'The row was not added.');
		$this->assertNotEquals(count($datatable->getDataMatrix()), count($cellvaluesaddrow), 'The added row seems to be stored in the datatable but this must not be.');
		// Remove row
		$cellvaluesremoverow = $datatable->getDataMatrix();
		array_splice($cellvaluesremoverow, 0, 1);
		$this->assertEquals(2, count($cellvaluesremoverow), 'The row was not removed.');
		$this->assertNotEquals(count($datatable->getDataMatrix()), count($cellvaluesremoverow), 'The removed row seems to be removed from the datatable but this must not be.');
		// Replace row with another column array
		$cellvaluesreplacerow = $datatable->getDataMatrix();
		array_splice($cellvaluesreplacerow, 0, 1, array(array('four','five','six')));
		$this->assertEquals(array('four','five','six'), $cellvaluesreplacerow[0], 'The row was not replaced correctly.');
		$this->assertNotEquals($datatable->getDataMatrix()[0], $cellvaluesreplacerow[0], 'The replaced row seems to be updated in the datatable but this must not be.');
		// Add column
		$cellvaluesaddcolumn = $datatable->getDataMatrix();
		$cellvaluesaddcolumn[0][] = 'four';
		$this->assertEquals(4, count($cellvaluesaddcolumn[0]), 'The row was not added.');
		$this->assertNotEquals(count($datatable->getDataMatrix()[0]), count($cellvaluesaddcolumn[0]), 'The added column seems to be stored in the datatable but this must not be.');
		// Remove column
		$cellvaluesremovecolumn = $datatable->getDataMatrix();
		array_splice($cellvaluesremovecolumn[0], 0, 1);
		$this->assertEquals(2, count($cellvaluesremovecolumn[0]), 'The column was not removed.');
		$this->assertNotEquals(count($datatable->getDataMatrix()[0]), count($cellvaluesremovecolumn[0]), 'The removed column seems to be removed from the datatable but this must not be.');
		// Change cell content
		$cellvaluesreplacecell = $datatable->getDataMatrix();
		$cellvaluesreplacecell[0][0] = 'newvalue';
		$this->assertEquals('newvalue', $cellvaluesreplacecell[0][0], 'The cell value was not replaced correctly.');
		$this->assertNotEquals($datatable->getDataMatrix()[0][0], $cellvaluesreplacecell[0][0], 'The cell value seems to be updated in the datatable but this must not be.');
	}
}
