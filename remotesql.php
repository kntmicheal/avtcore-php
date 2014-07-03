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

require_once dirname(__FILE__).'/code/avorium/core/transfer/DatabaseCsvTransfer.php';

/**
 * This file is for performing SQL requests on a remote machine which has the
 * csvwebservice installed.
 */

// Put in the URL of the webservice, e.g. http://www.example.com/csvwebservice.php
$webserviceurl = 'http://localhost/csvwebservice.php';

$sql = ''.filter_input(INPUT_POST, 'sql');
$html = '';
if ($sql !== '') {
	// Process SQL and send it to the server
	$datatable = avorium_core_transfer_DatabaseCsvTransfer::pullDataTableFromRemoteServer($webserviceurl, $sql);
	$html = renderDataTable($datatable);
}

function renderDataTable($datatable) {
	if ($datatable === null || !is_a($datatable, 'avorium_core_data_DataTable')) {
		return;
	}
	$html = '<table border="1"><thead><tr>';
	foreach ($datatable->getHeaders() as $headername) {
		$html .= '<th>'.$headername.'</th>';
	}
	$html .= '</tr></thead><tbody>';
	foreach ($datatable->getDataMatrix() as $row) {
		$html .= '<tr>';
		foreach ($row as $cell) {
			$html .= '<td>'.$cell.'</td>';
		}
		$html .= '</tr>';
	}
	return $html;
}

?><!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
	</head>
	<body>
		<form method="post">
			<textarea name="sql" rows="10" style="width:100%"><?php echo $sql ?></textarea>
			<input type="submit" />
		</form>
		<div><?php echo $html ?></div>
	</body>
</html>