<?php
/**
 * Im Gegensatz zu Listen von persistenten Objekten werden DataTables
 * speziell für heterogene Daten aus Datenbanken verwendet. Also speziell
 * für den Fall, dass man mit JOINS Daten aus mehreren Tabellen einer Datenbank
 * tabellarisch darzustellen, bietet es sich an, DataTables anstelle von
 * persistenten Objekten zu verwenden. Somit kann man auch schon per SQL die
 * auszugebenden Werte formatieren (z.B. Spalten zusammenfassen, HTML für
 * Links generieren, etc.).
 * Ferner bieten sich DataTables für die Synchronisierung von Systemen über
 * CSV-Schnittstellen an.
 */
class avorium_core_persistence_DataTable {
	
	private $columns = array();
	private $rows = array();
	
	/**
	 * Liefert ein Feld, welches die Spaltennamen der Datentabelle enthält.
	 * Die Anzahl der Spaltennamen sollte der Anzahl der Zellen in jeder
	 * Zeile entsprechen, was jedoch nicht garantiert wird. Um zu prüfen,
	 * ob die Tabellenstruktur korrekt ist, kann isValid() benutzt werden.
	 * Um die Inhalte des Feldes zu ändern, muss dem Funktionsaufruf ein
	 * Kaufmanns-Und vorangestellt werden.
	 * 
	 * @return array Feld mit Spaltennamen. Kann leer sein, ist aber nie NULL
	 */
	public function &getColumns() {
		return $this->columns;
	}
	
	/**
	 * Liefert ein Feld von Zeilen der Datentabelle. Jede Zeile stellt dabei
	 * selbst ein Feld von Zellen dar. Um zu prüfen,
	 * ob die Tabellenstruktur korrekt ist, kann isValid() benutzt werden.
	 * Um die Inhalte des Feldes zu ändern, muss dem Funktionsaufruf ein
	 * Kaufmanns-Und vorangestellt werden.
	 * 
	 * @return array Feld mit Zeilen. Kann leer, aber nie NULL sein. Jede Zeile
	 *               ist selbst ein Feld, kann aber ggf. NULL sein.
	 */
	public function &getRows() {
		return $this->rows;
	}
	
	/**
	 * Prüft, ob die Tabellenstruktur valide ist. Dabei wird über jede Zeile
	 * iteriert und Folgendes geprüft: 1) Ist die Zeile ein Feld (array)
	 * oder nicht (Jede Zeile muss ein Feld sein, auch wenn dieses leer ist)?
	 * 2) Hat die Zeile genauso viele Zellen, wie es Spaltennamen gibt?
	 * Nur, wenn alle Bedingungen erfüllt sind, wird true zurück gegeben.
	 * 
	 * @return boolean True, wenn Tabellenstruktur valide ist - False, 
	 *                 wenn nicht
	 */
	public function isValid() {
		$columncount = sizeof($this->columns);
		foreach ($this->rows as $row) {
			// Zeile darf nicht null sein und muss ein Feld sein
			// is_array() ist langsamer als das Casten!
			if ( (array)$row !== $row ) return false;
			// Anzahl Spalten muss stimmen
			if (sizeof($row) !== $columncount) return false;
		}
		return true;
	}
}
