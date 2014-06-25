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

require_once dirname(__FILE__).'/DataTable.php';

/**
 * This class provides static functions for converting datatables into
 * CSV strings and vice versa.
 * 
 * The standard CSV format is expected and outputted.
 * Cell delimiters must be commas (,).
 * Each cell must be quoted in double quotes ("), the same is for header names.
 * A double quote (") within a cell must be escaped by using double-double
 * quotes ("").
 * A cell can be empty (two commas following each other), this is handled as
 * null as value for the datatable.
 * Rows must be separated by newline-chars (\n).
 * There must be at least one line in the CSV data.
 * The first line must contain the column names.
 * A column must consist of at least one character (must not be empty (null)).
 * All rows must have the same number of cells.
 * The nummer of row cells must match the number of header names.
 * Header names can be contained more than once.
 * 
 * Please notice that the CSV parser allows special characters in header names.
 * But when you use the resulting datatable in conjunction with persistence
 * adapters, the special characters may not be allowed as database table names
 * and so exceptions can come up.
 */
class avorium_core_data_CsvParser {
	
	private static $delimiter = ',';
	private static $quotation = '"';
	private static $newline = "\n";

	/**
	 * Converts a CSV string into a datatable. The given CSV string is validated
	 * before a conversion is done.
	 * 
	 * @param string $csv CSV string to convert
	 * @return avorium_core_data_DataTable Data table containing the contents 
	 * from the CSV string
	 */
	public static function convertCsvToDataTable($csv) {
		// Check parameter
		if (is_null($csv)) {
			throw new Exception('The CSV parameter must not be null.');
		}
		if (!is_string($csv)) {
			throw new Exception('The CSV parameter must be a string.');
		}
		if (empty($csv)) {
			throw new Exception('The CSV parameter must not be empty.');
		}
		// Check the number of quotation marks. Must be an even number.
		if (substr_count($csv, '"') % 2 !== 0)
			throw new Exception('Wrong number of escape characters (") in CSV content. Must be even.');
		// Merge the rows of the table on demand (if there is a line break
		// within a cell content). Only \n are checked, this contains \r\n.
		// When after that a line ends with \r, it will be removed
		// automatically when trimming. \r within cell contents keep existing.
		$rawlines = explode("\n", $csv);
		$filteredlines = array();
		$currentfilteredline = false;
		for ($i = 0; $i < count($rawlines); $i++) {
			// If there is an odd number of quotation marks in a line,
			// the next line must be appended because the line break is
			// within a cell content.
			$currentfilteredline = $currentfilteredline ? $currentfilteredline."\n".$rawlines[$i] : $rawlines[$i];
			if (substr_count($currentfilteredline, '"') % 2 === 0) {
				// On an even number of quotation marks the row is closed
				// correctly and can be appended to the list of filtered rows
				// (if the row is not an empty one).
				$filteredlines[] = trim($currentfilteredline);
				$currentfilteredline = false;
			}
		}
		// Process column names
		$columnnamesline = $filteredlines[0];
		if (empty($columnnamesline))
			throw new Exception('Empty column names line.');
		$columnnames = static::extractCellsFromLine($columnnamesline);
		$columncount = count($columnnames);
		// Process rows
		$rows = array();
		for ($i = 1; $i < count($filteredlines); $i++) {
			if (empty($filteredlines[$i])) // Ignore empty lines
				continue;
			$cells = static::extractCellsFromLine($filteredlines[$i]);
			if (count($cells) !== $columncount)
				throw new Exception('Content row '.($i-1).' has wrong cell count, found: '.count($cells).', expected: '.$columncount);
			$rows[] = $cells;
		}
		$rowcount = count($rows);
		// Create datatable
		$datatable = new avorium_core_data_DataTable($rowcount, $columncount);
		for ($i = 0; $i < $columncount; $i++) {
			$datatable->setHeader($i, $columnnames[$i]);
		}
		for ($i = 0; $i < $rowcount; $i++) {
			for ($j = 0; $j < $columncount; $j++) {
				$datatable->setCellValue($i, $j, $rows[$i][$j]);
			}
		}
		return $datatable;
	}

	/**
	 * Extracts cells out of lines. With that commas within cells
	 * are handled correctly.
	 */
	private static function extractCellsFromLine($line) {
		$rawcells = explode(',', $line);
		$filteredcells = array();
		$currentfilteredcell = false;
		for ($i = 0; $i < count($rawcells); $i++) {
			if (!$currentfilteredcell)
				$currentfilteredcell = $rawcells[$i];
			else
				// On an odd number of quotation marks the next line
				// must be appended because the comma was part of the 
				// cell content.
				$currentfilteredcell .= ','.$rawcells[$i];
			if (substr_count($currentfilteredcell, '"') % 2 === 0) {
				// On an even number of quotation marks the cell is correctly
				// closed and can be appended to the list of filtered cells.
				$filteredcells[] = $currentfilteredcell;
				$currentfilteredcell = false;
			}
		}
		// Trim the cells when there are spaces between a comma and an opening
		// quotation mark or between a closing quotation mark and a comma.
		$result = array();
		foreach ($filteredcells as $filteredcell) {
			$result[] = static::trimCellContent($filteredcell);
		}
		return $result;
	}

	/**
	 * Removes all spaces around a cell definition and also removes the
	 * quotation marks around the cell to get the content only.
	 */
	private static function trimCellContent($rawcell) {
		$trimmedcell = trim($rawcell);
		if (empty($trimmedcell)) {
			return null; // Handle emtpy cells as null values
		}
		if (substr($trimmedcell, 0, 1) !== '"' || substr($trimmedcell, -1, 1) !== '"') {
			throw new Exception('Cell does not start and end with escape character (").');
		}
		$cellcontent = substr($trimmedcell, 1, strlen($trimmedcell) - 2);
		// Unescape double double-quotation marks
		return str_replace('""', '"', $cellcontent);
	}

	/**
	 * Converts the given datatable into a CSV string.
	 * 
	 * @param avorium_core_data_DataTable $datatable Data table to convert
	 * @return string CSV string with the data table content
	 */
	public static function convertDataTableToCsv($datatable) {
		return null;
	}
}
