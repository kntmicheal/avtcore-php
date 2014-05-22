<?

// Hilfsklasse zum Auswerten der "avtpersistable"-Annotationen
class avorium_core_persistence_helper_Annotation {

	// Liefert die Annotationsstruktur für das gegebene Objekt oder null
	// Klassenannotation für Tabellennamen: @avtpersistable(name = "name")
	// Feldannotationen für Spalten: @avtpersistable(name = "name", type = "type", size = 9)
	public static function getPersistableMetaData($objectorclassname) {
		$reflectionClass = new ReflectionClass($objectorclassname);
		$classMetaData = static::parseDocComment($reflectionClass);
		if ($classMetaData === null) return null;
		$classMetaData["properties"] = array();
		foreach($reflectionClass->getProperties() as $property) {
			$propertyMetaData = static::parseDocComment($property);
			if ($propertyMetaData !== null) $classMetaData["properties"][$property->name] = $propertyMetaData;
		}
		return $classMetaData;
	}
	
	private static function parseDocComment($element) {
		$docComment = $element->getDocComment();
		foreach (explode("\n", $docComment) as $line) {
			if (($pos = strpos($line, "@avtpersistable")) !== false) {
				$annotationString = substr($line, $pos);
				break;
			}
		}
		if (!isset($annotationString)) return null;
		$parameterString = substr($annotationString, strpos($annotationString, "(") + 1); // Öffnende Klammer
		$parameterString = substr($parameterString, 0, strpos($parameterString, ")")); // Schließende Klammer
		$parameters = array();
		foreach(explode(",", trim($parameterString)) as $parameter) {
			$parameterparts = explode("=", trim($parameter));
			$parametername = trim($parameterparts[0]);
			$parametervalue = trim(str_replace("\"", "", str_replace("'", "", $parameterparts[1])));
			$parameters[$parametername] = $parametervalue;
		}
		return $parameters;
	}
}
?>