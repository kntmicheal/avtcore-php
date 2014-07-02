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

require_once dirname(__FILE__).'/../data/CsvParser.php';

/**
 * Provides static functions for transferring database contents from one server
 * to another. Persistence adapters are used for retrieving and storing the 
 * data. The transfer over the web is done via CSV webservice calls.
 */
class avorium_core_transfer_DatabaseCsvTransfer {
	
	/**
	 * Performs a request to the webservice and returns the HTTP status code
	 * and sets the response.
	 * 
	 * @param string $remoteendpoint URL of the csvwebservice.php file on the 
	 * remote host. For example "http://www.example.com/csvwebservice.php"
	 * @param string $response Here the response from the webservice call will
	 * be written into.
	 * @param string $postcontent When null, a GET request is done, otherwise
	 * the given content is sent via POST to the webservice.
	 * @return int HTTP status code of the request.
	 */
	private static function doRequest($remoteendpoint, &$response, $postcontent) {
		$ch = curl_init($remoteendpoint);
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
	 * Performs a SQL query on the remote host, transfers obtains the request
	 * as CSV data and converts it into a datatable. The datatable can then
	 * be stored into the local database.
	 * 
	 * @param string $remoteendpoint URL of the csvwebservice.php file on the 
	 * remote host. For example "http://www.example.com/csvwebservice.php"
	 * @param string $query SQL query used to obtain the table data. Must be
	 * a statement which returns results (no DELETE, INSERT).
	 * @return avorium_core_data_DataTable Returns a datatable with the query
	 * result containing all values as strings.
	 * @throws Exception Exceptions are thrown when the parameters are invalid
	 * or when the remote endpoint reports errors (Only the fact that there is
	 * any error is reported, not the error reason itself. The reason can be
	 * found in the log files of the remote host.)
	 */
	public static function pullDataTableFromRemoteServer($remoteendpoint, $query) {
		// Check parameters
		if (is_null($remoteendpoint)) {
			throw new Exception('The remote endpoint URL must not be null.');
		}
		if (is_null($query)) {
			throw new Exception('The query must not be null.');
		}
		$postcontent = "execute\n".$query;
		$response = null;
		$code = self::doRequest($remoteendpoint, $response, $postcontent);
		if ($code === 400) {
			throw new Exception('An error occured on the remote host.');
		} else if ($code === 200) {
			$datatable = avorium_core_data_CsvParser::convertCsvToDataTable($response);
			return $datatable;
		}
		throw new Exception('Cannot connect to the remote endpoint.');
	}
}
