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
 * Tests the functionality of the CsvParser class
 */
class test_data_CsvParserTest extends PHPUnit_Framework_TestCase {
	
	/**
	 * Positive test for converting CSV to Datatable with special characters
	 */
	public function testConvertCsvToDataTable() {
        $goodcsvstring = '"UUID","FIRSTCOL","SECONDCOL"
"1","Record One","Record Two"
"2","°!""§$%&/()=?`"   ,    "Ü*\'ÄÖ_:;L,.-.lö\\\\ä\""+üp#Hier mal mit Leerzeichen um die Trennzeichen"
"3","„¡“¶¢[]|{}≠¿ Hier kommen drei Anführungszeichen in Folge:"""""" und Leerzeichen am Ende    ","“¬”#£ﬁ^˜·¯˙˚„¡“¶¢≠„„¡¡““¶¶¢¢[]|{}≠≠7/"


"4","“¬”#£ﬁ^˜·¯˙\˙¯·˜\^ﬁ£#”¬","∞…–œæ‘±•πø⁄¨Ω†®€∑«å‚∂ƒ©ªº∆@œ…∞µ~∫√ç≈¥"
"5","Trallala
Hoppsassa","Fidel ""Holly,
Der 5. Eintrag hier ist syntaktisch korrekt, SECONDCOL geht von Fidel bis bumms
und enthält Zeilenumbrüche, Anführungszeichen und Kommas

Hase,"",
bumms"
"6","∞µºª∫©√çƒ∂≈‚∑€®†Ω¨⁄∆øπ≠ø⁄¨Ω†®¢€¶»„‰¸","˝ˇÁÛØ∏Œﬂ÷˛˘›‹◊ÇÙ‡ÅÍ„‰™¸˝ˇÁÛØ∏Œ÷ŒÆ°’Æ—÷"';
        $datatable = avorium_core_data_CsvParser::convertCsvToDataTable($goodcsvstring);
		// Check header names
		$headernames = $datatable->getHeaders();
        $this->assertEquals(3, count($headernames), 'Column count not as expected.');
        $this->assertEquals('UUID', $headernames[0], 'Wrong header name on column 0.');
        $this->assertEquals('FIRSTCOL', $headernames[1], 'Wrong header name on column 1.');
        $this->assertEquals('SECONDCOL', $headernames[2], 'Wrong header name on column 2.');
		// Check values
		$datamatrix = $datatable->getDataMatrix();
        $this->assertEquals(6, count($datamatrix), 'Row count not as expected.');
        for ($i = 0; $i < 6; $i++) {
            $this->assertEquals(3, count($datamatrix[$i]), 'Column count in row '.$i.' not as expected.');
        }
        // Compare the cells
        $this->assertEquals('1', $datamatrix[0][0], 'Content of cell 0/0 is not as expected.');
        $this->assertEquals('Record One', $datamatrix[0][1], 'Content of cell 0/1 is not as expected.');
        $this->assertEquals('Record Two', $datamatrix[0][2], 'Content of cell 0/2 is not as expected.');
        $this->assertEquals('2', $datamatrix[1][0], 'Content of cell 1/0 is not as expected.');
        $this->assertEquals('°!"§$%&/()=?`', $datamatrix[1][1], 'Content of cell 1/1 is not as expected.');
        $this->assertEquals('Ü*'."'".'ÄÖ_:;L,.-.lö\\\\ä\"+üp#Hier mal mit Leerzeichen um die Trennzeichen', $datamatrix[1][2], 'Content of cell 1/2 is not as expected.');
        $this->assertEquals('3', $datamatrix[2][0], 'Content of cell 2/0 is not as expected.');
        $this->assertEquals('„¡“¶¢[]|{}≠¿ Hier kommen drei Anführungszeichen in Folge:""" und Leerzeichen am Ende    ', $datamatrix[2][1], 'Content of cell 2/1 is not as expected.');
        $this->assertEquals('“¬”#£ﬁ^˜·¯˙˚„¡“¶¢≠„„¡¡““¶¶¢¢[]|{}≠≠7/', $datamatrix[2][2], 'Content of cell 2/2 is not as expected.');
        $this->assertEquals('4', $datamatrix[3][0], 'Content of cell 3/0 is not as expected.');
        $this->assertEquals('“¬”#£ﬁ^˜·¯˙\˙¯·˜\^ﬁ£#”¬', $datamatrix[3][1], 'Content of cell 3/1 is not as expected.');
        $this->assertEquals('∞…–œæ‘±•πø⁄¨Ω†®€∑«å‚∂ƒ©ªº∆@œ…∞µ~∫√ç≈¥', $datamatrix[3][2], 'Content of cell 3/2 is not as expected.');
        $this->assertEquals('5', $datamatrix[4][0], 'Content of cell 4/0 is not as expected.');
        $this->assertEquals("Trallala\r\nHoppsassa", $datamatrix[4][1], 'Content of cell 4/1 is not as expected.');
        $this->assertEquals("Fidel \"Holly,\r\nDer 5. Eintrag hier ist syntaktisch korrekt, SECONDCOL geht von Fidel bis bumms\r\nund enthält Zeilenumbrüche, Anführungszeichen und Kommas\r\n\r\nHase,\",\r\nbumms", $datamatrix[4][2], 'Content of cell 4/2 is not as expected.');
        $this->assertEquals('6', $datamatrix[5][0], 'Content of cell 5/0 is not as expected.');
        $this->assertEquals('∞µºª∫©√çƒ∂≈‚∑€®†Ω¨⁄∆øπ≠ø⁄¨Ω†®¢€¶»„‰¸', $datamatrix[5][1], 'Content of cell 5/1 is not as expected.');
        $this->assertEquals('˝ˇÁÛØ∏Œﬂ÷˛˘›‹◊ÇÙ‡ÅÍ„‰™¸˝ˇÁÛØ∏Œ÷ŒÆ°’Æ—÷', $datamatrix[5][2], 'Content of cell 5/2 is not as expected.');
	}
	
	/**
	 * When a cell is empty and has no quotes, the content is handled as null.
	 */
	public function testConvertCsvToDataTableEmptyCell() {
        $datatable = avorium_core_data_CsvParser::convertCsvToDataTable("\"ONE\",\"TWO\",\"THREE\"\n\"first\",,\"third\"");
		$this->assertNull($datatable->getDataMatrix()[0][1], 'The cell should be null.');
	}
	
	/**
	 * The first line must contain the header names and must not be empty.
	 */
	public function testConvertCsvToDataTableEmptyColumnNamesLine() {
        $this->setExpectedException('Exception', 'Empty column names line.');
        avorium_core_data_CsvParser::convertCsvToDataTable("\n\"Column 21\",\"Column 22\"");
	}
	
	/**
	 * Each row of the CSV file must have the same number of cells. The number
	 * of row cells must be the same as the number of column names.
	 */
	public function testConvertCsvToDataTableWrongCellCountInContent() {
        $this->setExpectedException('Exception', 'Content row 1 has wrong cell count, found: 3, expected: 2');
        avorium_core_data_CsvParser::convertCsvToDataTable("\"Column 11\",\"Column 12\"\n\"Column 21\",\"Column 22\"\r\n\"Column 31\",\"Column 32\",\"Column 33\"");
	}
	
	/**
	 * Escape characters (quotation marks) must not be within cells (only if
	 * they are escaped too).
	 */
	public function testConvertCsvToDataTableWrongEscapeCharactersInCell() {
        $this->setExpectedException('Exception', 'Cell does not start and end with escape character (").');
        avorium_core_data_CsvParser::convertCsvToDataTable("\"Column 11\",\"Column 12\"\nColumn\"12\",\"Column 22\"");
	}
	
	/**
	 * The entire CSV string must have an equal number of quotation marks
	 * (One closing for an opening).
	 */
	public function testConvertCsvToDataTableWrongNumberOfEscapeCharacters() {
        $this->setExpectedException('Exception', 'Wrong number of escape characters (") in CSV content. Must be even.');
        avorium_core_data_CsvParser::convertCsvToDataTable("\"Column\" 11\",\"Column 12\"\r\n\"Column 21\",\"Column 22\"");
	}
	
	/**
	 * The CSV parameter string must not be null.
	 */
	public function testConvertCsvToDataTableParameterNull() {
        $this->setExpectedException('Exception', 'The CSV parameter must not be null.');
        avorium_core_data_CsvParser::convertCsvToDataTable(null);
	}
	
	/**
	 * The CSV parameter string must be a string.
	 */
	public function testConvertCsvToDataTableParameterNotString() {
        $this->setExpectedException('Exception', 'The CSV parameter must be a string.');
        avorium_core_data_CsvParser::convertCsvToDataTable(123);
	}
	
	/**
	 * When a column name is contained twice in the CSV, it also should
	 * be contained twice in the datatable, even when the affected columns
	 * have different cell values.
	 */
	public function testConvertCsvToDataTableDuplicateColumnNames() {
		$csv = "\"H1\",\"H2\",\"H1\"\n\"C1\",\"C2\",\"C3\"";
		$datatable = avorium_core_data_CsvParser::convertCsvToDataTable($csv);
		// Check header names
		$headernames = $datatable->getHeaders();
        $this->assertEquals(3, count($headernames), 'Column count not as expected.');
        $this->assertEquals('H1', $headernames[0], 'Wrong header name on column 0.');
        $this->assertEquals('H2', $headernames[1], 'Wrong header name on column 1.');
        $this->assertEquals('H1', $headernames[2], 'Wrong header name on column 2.');
		// Check values
		$datamatrix = $datatable->getDataMatrix();
        $this->assertEquals(1, count($datamatrix), 'Row count not as expected.');
		$this->assertEquals(3, count($datamatrix[0]), 'Column count in row 0 not as expected.');
        // Compare the cells
        $this->assertEquals('C1', $datamatrix[0][0], 'Content of cell 0/0 is not as expected.');
        $this->assertEquals('C2', $datamatrix[0][1], 'Content of cell 0/1 is not as expected.');
        $this->assertEquals('C3', $datamatrix[0][2], 'Content of cell 0/2 is not as expected.');
	}
	
	/**
	 * The CSV parameter string must not be empty.
	 */
	public function testConvertCsvToDataTableParameterEmptyString() {
        $this->setExpectedException('Exception', 'The CSV parameter must not be empty.');
        avorium_core_data_CsvParser::convertCsvToDataTable("");
	}

	/**
	 * Positive test for converting a datatable into CSV with special
	 * characters.
	 */
	public function testConvertDataTableToCsv() {
        $expectedcsv = "\"UUID\",\"FIRSTCOL\",\"SECONDCOL\"\n".
"\"1\",\"Record One\",\"Record Two\"\n".
"\"2\",\"°!\"\"§$%&/()=?`\",\"Ü*'ÄÖ_:;L,.-.lö\\\\ä\\\"\"+üp#Hier mal mit Leerzeichen um die Trennzeichen\"\n".
"\"3\",\"„¡“¶¢[]|{}≠¿ Hier kommen drei Anführungszeichen in Folge:\"\"\"\"\"\" und Leerzeichen am Ende    \",\"“¬”#£ﬁ^˜·¯˙˚„¡“¶¢≠„„¡¡““¶¶¢¢[]|{}≠≠7/\"\n".
"\"4\",\"“¬”#£ﬁ^˜·¯˙\˙¯·˜\^ﬁ£#”¬\",\"∞…–œæ‘±•πø⁄¨Ω†®€∑«å‚∂ƒ©ªº∆@œ…∞µ~∫√ç≈¥\"\n".
"\"5\",\"Trallala\r\n".
"Hoppsassa\",\"Fidel \"\"Holly,\r\n".
"Der 5. Eintrag hier ist syntaktisch korrekt, SECONDCOL geht von Fidel bis bumms\r\n".
"und enthält Zeilenumbrüche, Anführungszeichen und Kommas\r\n".
"\r\n".
"Hase,\"\",\r\n".
"bumms\"\n".
"\"6\",\"∞µºª∫©√çƒ∂≈‚∑€®†Ω¨⁄∆øπ≠ø⁄¨Ω†®¢€¶»„‰¸\",\"˝ˇÁÛØ∏Œﬂ÷˛˘›‹◊ÇÙ‡ÅÍ„‰™¸˝ˇÁÛØ∏Œ÷ŒÆ°’Æ—÷\"\n";
        $datatable = new avorium_core_data_DataTable(6, 3);
		$datatable->setHeader(0, 'UUID');
		$datatable->setHeader(1, 'FIRSTCOL');
		$datatable->setHeader(2, 'SECONDCOL');
		$datatable->setCellValue(0, 0, '1');
		$datatable->setCellValue(0, 1, 'Record One');
		$datatable->setCellValue(0, 2, 'Record Two');
		$datatable->setCellValue(1, 0, '2');
		$datatable->setCellValue(1, 1, '°!"§$%&/()=?`');
		$datatable->setCellValue(1, 2, 'Ü*'."'".'ÄÖ_:;L,.-.lö\\\\ä\"+üp#Hier mal mit Leerzeichen um die Trennzeichen');
		$datatable->setCellValue(2, 0, '3');
		$datatable->setCellValue(2, 1, '„¡“¶¢[]|{}≠¿ Hier kommen drei Anführungszeichen in Folge:""" und Leerzeichen am Ende    ');
		$datatable->setCellValue(2, 2, '“¬”#£ﬁ^˜·¯˙˚„¡“¶¢≠„„¡¡““¶¶¢¢[]|{}≠≠7/');
		$datatable->setCellValue(3, 0, '4');
		$datatable->setCellValue(3, 1, '“¬”#£ﬁ^˜·¯˙\˙¯·˜\^ﬁ£#”¬');
		$datatable->setCellValue(3, 2, '∞…–œæ‘±•πø⁄¨Ω†®€∑«å‚∂ƒ©ªº∆@œ…∞µ~∫√ç≈¥');
		$datatable->setCellValue(4, 0, '5');
		$datatable->setCellValue(4, 1, "Trallala\r\nHoppsassa");
		$datatable->setCellValue(4, 2, "Fidel \"Holly,\r\nDer 5. Eintrag hier ist syntaktisch korrekt, SECONDCOL geht von Fidel bis bumms\r\nund enthält Zeilenumbrüche, Anführungszeichen und Kommas\r\n\r\nHase,\",\r\nbumms");
		$datatable->setCellValue(5, 0, '6');
		$datatable->setCellValue(5, 1, '∞µºª∫©√çƒ∂≈‚∑€®†Ω¨⁄∆øπ≠ø⁄¨Ω†®¢€¶»„‰¸');
		$datatable->setCellValue(5, 2, '˝ˇÁÛØ∏Œﬂ÷˛˘›‹◊ÇÙ‡ÅÍ„‰™¸˝ˇÁÛØ∏Œ÷ŒÆ°’Æ—÷');
		// Get CSV from datatable
		$csv = avorium_core_data_CsvParser::convertDataTableToCsv($datatable);
		// Check whether the CSV matches the expected CSV
		$this->assertEquals($expectedcsv, $csv, 'The CSV generated by the datatable does not equal the expected CSV string.');
	}

	/**
	 * When the datatable contains no rows, the resulting CSV consists of the
	 * column name line only.
	 */
	public function testConvertDataTableToCsvNoRows() {
        $datatable = new avorium_core_data_DataTable(0, 3);
		$datatable->setHeader(0, 'UUID');
		$datatable->setHeader(1, 'FIRSTCOL');
		$datatable->setHeader(2, 'SECONDCOL');
		// Get CSV from datatable
		$csv = avorium_core_data_CsvParser::convertDataTableToCsv($datatable);
		// Check whether the CSV matches the expected CSV
		$this->assertEquals("\"UUID\",\"FIRSTCOL\",\"SECONDCOL\"\n", $csv, 'The CSV generated by the datatable does not equal the expected CSV string.');
	}

	/**
	 * When the datatable has multiple columns with the same header name, the
	 * CSV result should also contain these columns with the same name.
	 */
	public function testConvertDataTableToCsvDuplicateHeaderNames() {
        $datatable = new avorium_core_data_DataTable(1, 3);
		$datatable->setHeader(0, 'H1');
		$datatable->setHeader(1, 'H2');
		$datatable->setHeader(2, 'H1');
		$datatable->setCellValue(0, 0, 'C1');
		$datatable->setCellValue(0, 1, 'C2');
		$datatable->setCellValue(0, 2, 'C3');
		// Get CSV from datatable
		$csv = avorium_core_data_CsvParser::convertDataTableToCsv($datatable);
		// Check whether the CSV matches the expected CSV
		$this->assertEquals("\"H1\",\"H2\",\"H1\"\n\"C1\",\"C2\",\"C3\"\n", $csv, 'The CSV generated by the datatable does not equal the expected CSV string.');
	}

	/**
	 * Null values in datatable cells should be outputted as empty cells
	 * (two successive commas)
	 */
	public function testConvertDataTableToCsvNullValuesInCells() {
        $datatable = new avorium_core_data_DataTable(1, 3);
		$datatable->setHeader(0, 'H1');
		$datatable->setHeader(1, 'H2');
		$datatable->setHeader(2, 'H3');
		$datatable->setCellValue(0, 0, 'C1');
		$datatable->setCellValue(0, 1, null);
		$datatable->setCellValue(0, 2, 'C3');
		// Get CSV from datatable
		$csv = avorium_core_data_CsvParser::convertDataTableToCsv($datatable);
		// Check whether the CSV matches the expected CSV
		$this->assertEquals("\"H1\",\"H2\",\"H3\"\n\"C1\",,\"C3\"\n", $csv, 'The CSV generated by the datatable does not equal the expected CSV string.');
	}

	/**
	 * Cells with values which are not strings cannot be converted
	 * automatically, so an exception must be thrown.
	 */
	public function testConvertDataTableToCsvCellValuesNotStrings() {
        $datatable = new avorium_core_data_DataTable(1, 3);
		$datatable->setHeader(0, 'H1');
		$datatable->setHeader(1, 'H2');
		$datatable->setHeader(2, 'H3');
		$datatable->setCellValue(0, 0, 'C1');
		$datatable->setCellValue(0, 1, 12345);
		$datatable->setCellValue(0, 2, 'C3');
		// Get CSV from datatable
		$this->setExpectedException('Exception', 'A cell value in the datatable is not of type string and cannot be converted.');
		avorium_core_data_CsvParser::convertDataTableToCsv($datatable);
	}

	/**
	 * Header names must not be null.
	 */
	public function testConvertDataTableToCsvHeaderNameNull() {
        $datatable = new avorium_core_data_DataTable(1, 3);
		$datatable->setHeader(0, 'H1');
		$datatable->setHeader(1, null);
		$datatable->setHeader(2, 'H3');
		$datatable->setCellValue(0, 0, 'C1');
		$datatable->setCellValue(0, 1, 'C2');
		$datatable->setCellValue(0, 2, 'C3');
		// Get CSV from datatable
		$this->setExpectedException('Exception', 'A header name is null but must not be.');
		avorium_core_data_CsvParser::convertDataTableToCsv($datatable);
	}

	/**
	 * Header names must consist of at least consist one character.
	 */
	public function testConvertDataTableToCsvHeaderNameEmpty() {
        $datatable = new avorium_core_data_DataTable(1, 3);
		$datatable->setHeader(0, 'H1');
		$datatable->setHeader(1, '');
		$datatable->setHeader(2, 'H3');
		$datatable->setCellValue(0, 0, 'C1');
		$datatable->setCellValue(0, 1, 'C2');
		$datatable->setCellValue(0, 2, 'C3');
		// Get CSV from datatable
		$this->setExpectedException('Exception', 'A header name is empty but must not be.');
		avorium_core_data_CsvParser::convertDataTableToCsv($datatable);
	}

	/**
	 * The datatable parameter must not be null.
	 */
	public function testConvertDataTableToCsvParameterNull() {
		$this->setExpectedException('Exception', 'The datatable parameter must not be null.');
		avorium_core_data_CsvParser::convertDataTableToCsv(null);
	}

	/**
	 * The datatable parameter must be of the datatable data type.
	 */
	public function testConvertDataTableToCsvParameterNotDataTable() {
		$this->setExpectedException('Exception', 'The datatable parameter must be of type avorium_core_data_DataTable.');
		avorium_core_data_CsvParser::convertDataTableToCsv(1234);
	}

}
