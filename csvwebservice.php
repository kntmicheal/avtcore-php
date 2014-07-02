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

require_once dirname(__FILE__).'/localconfig.php';
require_once dirname(__FILE__).'/code/avorium/core/io/CsvWebService.php';

$method = strtolower(filter_input(INPUT_SERVER, 'REQUEST_METHOD'));
$postdata = file_get_contents('php://input');

// React to POST only!
if ($method !== 'post') {
    http_response_code(400);
    die;
}

try {
	// Process request
	$cws = new avorium_core_io_CsvWebService();
	$cws->setPersistenceAdapter($GLOBALS['PersistenceAdapter']);
	$response = $cws->parseRequest($postdata);
	// Send header data
	header('Content-Type: text/csv');
	echo $response;
	exit;
} catch (Exception $exc) {
	// Exception occured, log it and return simple error code
	error_log($exc->getMessage().' in '.$exc->getTraceAsString().' POSTDATA: '.$postdata);
    http_response_code(400);
    die;
}
