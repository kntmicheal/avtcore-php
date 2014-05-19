<?

// Basisklasse für alle persistenten Objekte
abstract class avorium_core_persistence_PersistentObject {

	/**
	 * Eindeutiger Identifizierer
	 * @avtpersistable(name="uuid", type="string", size=40)
	 */
	public $uuid;
	
	// Name der Tabelle. Wird aus Annotation ausgelesen und im Konstruktor gesetzt
	public $tablename;

	// Initialisierungskonstruktor, Beispiel: new User(array("name" => "Ernst"))
	public function __construct(array $properties = NULL) {
		$this->uuid = uniqid('', true);
		$metaData = avorium_core_persistence_helper_Annotation::getPersistableMetaData($this);
		$this->tablename = $metaData["name"];
		if ($properties === NULL) return;
		foreach($properties as $property => $value) {
			if (property_exists($this, $property)) {
				$this->$property = $value;
			}
		}
	}
	
}

?>