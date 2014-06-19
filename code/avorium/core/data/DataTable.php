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
 * The datatable represents a matrix of data. It is used by the persistence
 * framework to retreive structured data from databases. This can then be used
 * in the CSV Parser to create CSV-files from the data and then transfer it
 * to other systems. In other projects this workflow is used to synchronize
 * database tables between different systems.
 * 
 * The data structure is limited to 2^32 rows and columns but you have to make
 * sure that there is enough memory when using huge data structures! Otherwise
 * you can get OutOfMemoryExceptions from PHP.
 */
class avorium_core_data_DataTable {

	/**
	 * @var array Array containing arrays of cell values
	 */
	private $rows;

	/**
	 * @var array Array containing header names
	 */
	private $headers;
	
	/**
	 * Creates a new datatable with the given row and column count. After that
	 * the number of columns and rows are fixed. A datatable must contain at
	 * least one cell (one row and one column). When one of the parameters
	 * have wrong values (smaller that 1 or larger than max int), exceptions
	 * are thrown and no datatable is created.
	 * 
	 * @param integer $rowCount Number of rows. Must be an integer between 0 and
	 * 2147483648.
	 * @param integer $columnCount Number of columns. Must be integer between 0 
	 * and 2147483648.
	 */
	public function __construct($rowCount, $columnCount) {
		// Check parameters
		if (!is_integer($rowCount)) {
			throw new Exception('The row count must be an integer.');
		}
		if ($rowCount < 1) {
			throw new Exception('The row count must be greater than zero.');
		}
		if (!is_integer($columnCount)) {
			throw new Exception('The column count must be an integer.');
		}
		if ($columnCount < 1) {
			throw new Exception('The column count must be greater than zero.');
		}
		// Prepare internal arrays
		$this->headers = array();
		for ($i = 0; $i < $columnCount; $i++) {
			$this->headers[] = null;
		}
		$this->rows = array();
		for ($i = 0; $i < $rowCount; $i++) {
			$row = array();
			for ($j = 0; $j < $columnCount; $j++) {
				$row[] = null;
			}
			$this->rows[] = $row;
		}
	}
	
	/**
	 * Retreives a copy of the header names of the datatable. The result is not
	 * a reference to the header names so changing the returned array has no
	 * effects to the header names of the datatable.
	 * 
	 * @return array Array containing the header names of the datatable.
	 */
	public function getHeaders() {
		return $this->headers;
	}

	/**
	 * Retreives a copy of the cell values of the datatable. The result is not
	 * a reference to the cell values so changing the returned array has no
	 * effects to the cell values of the datatable.
	 * 
	 * @return array Array containing arrays of cell values of the datatable.
	 */
	public function getDataMatrix() {
		return $this->rows;
	}
	
	/**
	 * Sets the name of the header with the given index to the given string.
	 * A header name can be null or a string. Other datatypes are not allowed.
	 * 
	 * @param integer $columnIndex Index (zero-based) of the column to set the
	 * header name for. Must be greater or equal to zero and smaller than the
	 * column count.
	 * @param string $headerName Name of the header to set. Can be null or any
	 * string (also HTML is possible).
	 */
	public function setHeader($columnIndex, $headerName) {
		// Check parameters
		if (!is_integer($columnIndex)) {
			throw new Exception('The column index must be an integer.');
		}
		if ($columnIndex < 0) {
			throw new Exception('The column index must be larger than zero.');
		}
		if ($columnIndex >= count($this->headers)) {
			throw new Exception('The column index must be smaller than the column count.');
		}
		if (!is_null($headerName) && !is_string($headerName)) {
			throw new Exception('The header name must be null or a string.');
		}
		// All checks done, set the header name
		$this->headers[$columnIndex] = $headerName;
	}
	
	/**
	 * Sets the value of a cell. Cells can have any datatype as values.
	 * 
	 * @param integer $rowIndex Index (zero-based) of the row to set the
	 * cell value for. Must be greater or equal to zero and smaller than the
	 * row count.
	 * @param integer $columnIndex Index (zero-based) of the column to set the
	 * cell value for. Must be greater or equal to zero and smaller than the
	 * column count.
	 * @param object $value Value to set for the cell.
	 */
	public function setCellValue($rowIndex, $columnIndex, $value) {
		// Check parameters
		if (!is_integer($rowIndex)) {
			throw new Exception('The row index must be an integer.');
		}
		if ($rowIndex < 0) {
			throw new Exception('The row index must be larger than zero.');
		}
		if ($rowIndex >= count($this->rows)) {
			throw new Exception('The row index must be smaller than the row count.');
		}
		if (!is_integer($columnIndex)) {
			throw new Exception('The column index must be an integer.');
		}
		if ($columnIndex < 0) {
			throw new Exception('The column index must be larger than zero.');
		}
		if ($columnIndex >= count($this->headers)) {
			throw new Exception('The column index must be smaller than the column count.');
		}
		// All checks done, set the cell value
		$this->rows[$rowIndex][$columnIndex] = $value;
	}
	
}
