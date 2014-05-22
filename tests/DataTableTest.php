<?php

require_once '../code/avorium/core/persistence/DataTable.php';

/**
 * Testet die Funktionalität von DataTable
 */
class DataTableTest extends PHPUnit_Framework_TestCase {
    
    /**
     * Prüft, ob der Konstruktor von DataTable die Felder columns
	 * und rows korrekt initialisiert.
     */
    public function testDataTableConstructor() {
		$dataTable = new avorium_core_persistence_DataTable();
		$this->assertTrue(is_array($dataTable->getColumns()));
		$this->assertTrue(is_array($dataTable->getRows()));
	}
	
	/**
	 * Positivtest. Es wird eine korrekte Datentabelle erzeugt und befüllt
	 * und danach isValid() aufgerufen, um die Struktur zu testen.
	 */
	public function testDataTablePositive() {
		$dataTable = new avorium_core_persistence_DataTable();
		$columns = &$dataTable->getColumns();
		$columns[] = 'Column 1';
		$columns[] = 'Column 2';
		$columns[] = 'Column 3';
		$rows = &$dataTable->getRows();
		$rows[] = array('Cell 1 1', 'Cell 1 2', 'Cell 1 3');
		$rows[] = array('Cell 2 1', 'Cell 2 2', 'Cell 2 3');
		$rows[] = array('Cell 3 1', 'Cell 3 2', 'Cell 3 3');
		$this->assertTrue($dataTable->isValid());
		$this->assertTrue(sizeof($dataTable->getColumns()) === 3);
		$this->assertTrue(sizeof($dataTable->getRows()) === 3);
		$this->assertTrue(sizeof($dataTable->getRows()[0]) === 3);
		$this->assertTrue(sizeof($dataTable->getRows()[1]) === 3);
		$this->assertTrue(sizeof($dataTable->getRows()[2]) === 3);
	}
	
	/**
	 * Negativtest für Nullzeilen. Es wird eine Tabelle erzeugt, die eine Zeile
	 * enthält, welche null ist. isValid() muss daraufhin false zurück geben.
	 */
	public function testDataTableNullRow() {
		$dataTable = new avorium_core_persistence_DataTable();
		$columns = &$dataTable->getColumns();
		$columns[] = 'Column 1';
		$columns[] = 'Column 2';
		$columns[] = 'Column 3';
		$rows = &$dataTable->getRows();
		$rows[] = array('Cell 1 1', 'Cell 1 2', 'Cell 1 3');
		$rows[] = null;
		$rows[] = array('Cell 3 1', 'Cell 3 2', 'Cell 3 3');
		$this->assertFalse($dataTable->isValid());
	}
	
	/**
	 * Negativtest für Zeilen, die keine Felder enthalten.
	 * Es wird eine Tabelle erzeugt, die eine Zeile
	 * enthält, welche kein Feld ist. 
	 * isValid() muss daraufhin false zurück geben.
	 */
	public function testDataTableRowIsNotAnArray() {
		$dataTable = new avorium_core_persistence_DataTable();
		$columns = &$dataTable->getColumns();
		$columns[] = 'Column 1';
		$columns[] = 'Column 2';
		$columns[] = 'Column 3';
		$rows = &$dataTable->getRows();
		$rows[] = array('Cell 1 1', 'Cell 1 2', 'Cell 1 3');
		$rows[] = 'This is not an array';
		$rows[] = array('Cell 3 1', 'Cell 3 2', 'Cell 3 3');
		$this->assertFalse($dataTable->isValid());
	}
	
	/**
	 * Negativtest für Falsche Zellenanzahl. Es wird eine Tabelle erzeugt,
	 * bei der eine Zeile nicht dieselbe Anzahl von Zellen hat, wie Spaltennamen
	 * angegeben sind. isValid() muss daraufhin false zurück geben.
	 */
	public function testDataTableWrongCellNumber() {
		$dataTable = new avorium_core_persistence_DataTable();
		$columns = &$dataTable->getColumns();
		$columns[] = 'Column 1';
		$columns[] = 'Column 2';
		$columns[] = 'Column 3';
		$rows = &$dataTable->getRows();
		$rows[] = array('Cell 1 1', 'Cell 1 2', 'Cell 1 3');
		$rows[] = array('Cell 2 1', 'Cell 2 2');
		$rows[] = array('Cell 3 1', 'Cell 3 2', 'Cell 3 3');
		$this->assertFalse($dataTable->isValid());
	}

}
