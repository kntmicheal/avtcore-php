<?
// Normalerweise gleich abbrechen, ausser, man kommentiert die folgende Zeile aus
//exit;

$query = filter_input(INPUT_POST, 'query');
$result = $query ? AvtPersistenceAdapter::executeMultipleResultQuery($query) : array();

?><!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	</head>
<body>
	<form method="post">
		<textarea name="query" style="width:100%;display:block;" rows="10"><?= $query ?></textarea>
		<input type="submit" />
	</form>
	<? if (count($result) > 0) : ?>
	<table border="1">
		<tr>
			<? foreach ($result[0] as $key => $value) : ?>
			<th><?= $key ?></th>
			<? endforeach ?>
		</tr>
		<? foreach ($result as $row) : ?>
		<tr>
			<? foreach ($row as $key => $value) : ?>
			<td><?= $value ?></td>
			<? endforeach ?>
		</tr>
		<? endforeach ?>
	</table>
	<? endif ?>
</body>
</html>