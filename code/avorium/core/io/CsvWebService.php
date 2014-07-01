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
 * The CSV Webservice is for requesting database content from a server
 * via REST and returning it as CSV file. Also this class can be used to
 * store content given as CSV in a POST request into a database.
 * 
 * The main function is parseRequest() which expects the POST request as
 * string, please the the documentation of the function for more details on
 * the structure of the request and response.
 * 
 * Before using this service, a persistence adapter must be set via
 * setPersistenceAdapter().
 */
class avorium_core_io_CsvWebService {
	
	/**
	 * Persistence adapter to be used by the execute() and save() functions
	 */
	private $persistenceadapter = null;
	
	/**
	 * Sets the persistence adapter to be used by the execute() and save()
	 * functions. Must not be null and must be of type
	 * avorium_core_persistence_AbstractPersistenceAdapter or a derived class.
	 * This function must be called before any request parsing is done.
	 * 
	 * @param avorium_core_persistence_AbstractPersistenceAdapter $persistenceadapter Persistence adapter to be used.
	 * @throws Exception When the given persistence adapter is null or not of type avorium_core_persistence_AbstractPersistenceAdapter or derived
	 */
	public function setPersistenceAdapter($persistenceadapter) {
		if (is_null($persistenceadapter)) {
			throw new Exception('Persistence adapter must not be null.');
		}
		if (!is_a($persistenceadapter, 'avorium_core_persistence_AbstractPersistenceAdapter')) {
			throw new Exception('Persistence adapter must be of type avorium_core_persistence_AbstractPersistenceAdapter or derived.');
		}
		$this->persistenceadapter = $persistenceadapter;
	}
	
	/**
	 * Returns the currently used persistence adapter.
	 * 
	 * @return avorium_core_persistence_AbstractPersistenceAdapter Currently 
	 * used persistence adapter. Can be null, when setPersistenceAdapter was
	 * never set before.
	 * @throws Exception When the currently used persistence adapter is null.
	 * So the calling functions do not need to check the returned object for
	 * null.
	 */
	private function getPersistenceAdapter() {
		if (is_null($this->persistenceadapter)) {
			throw new Exception('Persistence adapter is null.');
		}
		return $this->persistenceadapter;
	}
	
	/**
	 * Analyzes the given POST request content and performs the relevant
	 * actions and returns their responses. Internally the CsvParser class
	 * is used to convert the request content into a datatable. After that
	 * the datatable is given to the attached persistence adapter (when
	 * "save" is used) which stores it into the database.
	 * On "execute" functions the given SQL query is executed by the
	 * persistence adapter which creates a datatable out of the result (if 
	 * the query returns a result). Next this datatable is converted to
	 * a CSV string and gets returned by this function.
	 * 
	 * The content must consist of at least two lines, separated by "\n".
	 * The first line must contain either "execute" or "save". When "execute"
	 * is given, all further content is interpreted as SQL query which is
	 * forwarded to the attached persistence adapter. The result of the
	 * statement (if there is any) is returned as CSV string where the first
	 * line contains the column headers returned by the statement.
	 * 
	 * When "save" is given, the second line must contain the name of the
	 * database table, where to store the content into. All further lines
	 * must contain a valid CSV file content. The third line must contain
	 * the database column names where to put the values into. The fourth and
	 * all following  lines must contain the CSV content to store. The required
	 * CSV structure for the "save" function is documented in 
	 * avorium_core_data_CsvParser::convertCsvToDataTable.
	 * 
	 * Any exceptions which occur in subfunctions are not handled here but are
	 * forwarded to the calling function where they should be handled.
	 * 
	 * @param string $content Request content to be parsed and processed.
	 * @return string The CSV content of the result of an "execute" SQL query
	 * or null, when the "Execute" SQL query returns no result. Is also
	 * null when the "save" function is used and performed properly.
	 * Any error will not return a result but throw an exception.
	 * @throws Exception When the given content is null or has less than two
	 * lines or has neither "execute" nor "save" in the first line or has
	 * an invalid SQL query when "execute" is given or has no table name in the
	 * second line when "save" is given or has an invalid CSV content when
	 * "save" is given.
	 * 
	 */
	public function parseRequest($content) {
		// Check for null
		if (is_null($content)) {
			throw new Exception('The request content must not be null.');
		}
		// Split lines by \n and trim them
		$lines = split("\n", $content);
		// Check for second line
		if (count($lines) < 2) {
			throw new Exception('There is no second line.');
		}
		// Check action
		$action = trim($lines[0]);
		switch ($action) {
			case 'execute':
				// Merge all lines starting with the second one into a statement string
				return $this->execute(implode("\n", array_slice($lines, 1)));
			case 'save':
				// Check for a third line
				if (count($lines) < 3) {
					throw new Exception('There is no third line.');
				}
				// Merge the CSV content for the parser
				return $this->save(trim($lines[1]), implode("\n", array_slice($lines, 2)));
			default:
				throw new Exception('Unknown action: "'.$action.'"');
		}
		throw new Exception('Not implemented');
	}
	
	/**
	 * Executes the given SQL query and returns a CSV string of the result if
	 * there is any. Before calling this function a persistence adapter must
	 * be set.
	 * 
	 * @param string $query SQL query to be executed.
	 * @return string CSV content of the SQL result or null when the query does
	 * not return any result.
	 */
	private function execute($query) {
		if (strlen(trim($query)) < 1) {
			throw new Exception("The query is empty.");
		}
		try {
			$datatable = $this->getPersistenceAdapter()->getDataTable($query);
			return avorium_core_data_CsvParser::convertDataTableToCsv($datatable);
		} catch (Exception $exc) {
			if ($exc->getMessage() === 'Multiple result statement seems to be a no result statement.') {
				// This is okay here, simply return null
				return null;
			}
			// Rethrow any unhandled exceptions
			throw $exc;
		}
	}

	/**
	 * Stores the given CSV content into the database table given by its name.
	 * Before calling this function a persistence adapter must be set.
	 * 
	 * @param string $tablename Name of the table where to put the data into.
	 * @param string $csv CSV content to be stored in the database table. The
	 * first line must contain the column names.
	 */
	private function save($tablename, $csv) {
		$datatable = avorium_core_data_CsvParser::convertCsvToDataTable($csv);
		$this->getPersistenceAdapter()->saveDataTable($tablename, $datatable);
	}
}
