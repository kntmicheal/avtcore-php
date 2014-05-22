<?php

require_once '../code/avorium/core/persistence/MySqlPersistenceAdapter.php';

/**
 * Testet die Funktionalität des MySQL-PersistenceAdapter. Die Tests werden
 * einseitig ausgeführt. D.h. bei Schreibtests werden die Schreibfunktionen
 * geprüft und danach direkt in der Datenbank geguckt, ob alles stimmt.
 * Bei Lesetests werden die Daten manuell in die Datenbank gebracht und
 * mit den Lesefunktionen ausgelesen.
 */
class MySqlPersistenceAdapterTest extends AbstractPersistenceAdapterTest {
	
	/**
	 * Legt den MySql-Persistenzadapter für den Test fest
	 */
	protected function setUp() {
		parent::setUp();
	}
	
	
	// TODO: Das hier sollte abstrakt gehalten werden, um nicht für jeden PA eine eigene Testklasse vollständig haben zu müssen
	// Vielmehr sollten mehrere PAs festgelegt werden können, welche dann mit mehreren Aufrufen dieser Klasse
	// getestet werden.
	// Allerdings muss dann überlegt werden, wie die abstrakte Testklasse die Datenbank direkt ansprechen kann, um die Ergebnisse zu vergleichen
	// Evtl. mit abstrakten Funktionen in der Testklasse wie "executeSqlAndReturnArray"
	// Weil mysqli::query gibt es bei OCI nicht. Dort muss anders abgefragt werden.
	
	
	
	// Positivtests
	
	// Einzelnen Datensatz anlegen
	// Einzelnen Datensatz überschreiben
	// Einzelnen Datensatz auslesen
	// Einzelnen Datensatz löschen
	// Mehrere Datensätze auslesen
	// Multi-Daten-SQL mit persistenten Objekten ausführen und dabei Casting prüfen
	// Single-Daten-SQL mit persistenten Objekten ausführen
	// Multi-Daten-SQL ohne persistente Objekten ausführen
	// Single-Daten-SQL ohne persistente Objekten ausführen
	// SQL ohne Rückgabe ausführen
	// Einfache Werte aus Datenbank lesen und casten
	// Tabellen anlegen
	// Tabellen erweitern
	
	// Negativtests
	
	// Auslesen von einzelnen Datensätzen, deren UUID nicht in Datenbank ist
	// Tabellen erweitern, indem Datentypen von Spalten verändert werden, soll nicht möglich sein
	// Casten von Datentypen, die der Datenbank unbekannt sind
	// Casten von Datentypen, die dem Code unbekannt sind
	// Verwendung von Sonderzeichen
	// SQL-Injection
	// Fehlerhafte Datenbankverbindung
	// Fehlerhafte SQL-Statements
	// SQL-Statements, die nicht zur Abfrageart passen (Multi vs. Single vs. Einfacher Wert)
	
}
