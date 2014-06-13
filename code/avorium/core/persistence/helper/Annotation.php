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

/**
 * Helper class to analyze the avtpersistable annotations.
 */
class avorium_core_persistence_helper_Annotation {

    /**
     * Returns the annotation structure for the given object or an empty 
     * metadata structure, when no
     * metadata can be read (when the properties are not commented or have no
     * annotations)
     * Class annotations for table names: @avtpersistable(name = "name")
     * Property annotations for columns: @avtpersistable(name = "name", type = "type", size = 9)
     * 
     * @param string $objectorclassname Object or class name to analyze
     * @return array Array of metadata describing the object:
     * [ "name" => E.G. name of the database table of the class or null, when name is not set in annotation,
     *   "properties" => array [ // Properties of the class
     *      "propertyname" => array [ // Array describing metadata of property
     *          "name" => // e.g. name of the database columns of the property
     *          "type" => // e.g. type of the database column
     *      ]
     *   ]
     * ]
     */
    public static function getPersistableMetaData($objectorclassname) {
        $reflectionClass = new ReflectionClass($objectorclassname);
        $classMetaData = static::parseDocComment($reflectionClass);
        if ($classMetaData === null) {
            $classMetaData = array("name" => null);
        }
        $classMetaData["properties"] = array();
        foreach($reflectionClass->getProperties() as $property) {
            $propertyMetaData = static::parseDocComment($property);
            if ($propertyMetaData !== null) {
                $classMetaData["properties"][$property->name] = $propertyMetaData;
            }
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
        if (!isset($annotationString)) {
            return null;
        }
        $startingAtOpeningBracket = substr($annotationString, strpos($annotationString, "(") + 1); // Opening bracket
        $betweenBrackets = substr($startingAtOpeningBracket, 0, strpos($startingAtOpeningBracket, ")")); // Closing bracket
        $parameters = array();
        foreach(explode(",", trim($betweenBrackets)) as $parameter) {
            $parameterparts = explode("=", trim($parameter));
            if (count($parameterparts) < 2) { // When no annotation parameters were given
                continue;
            }
            $parametername = trim($parameterparts[0]);
            $parametervalue = trim(str_replace("\"", "", str_replace("'", "", $parameterparts[1])));
            $parameters[$parametername] = $parametervalue;
        }
        return $parameters;
    }
}
