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

require_once dirname(__FILE__).'/helper/Annotation.php';

/**
 * Base class for all persistent objects
 */
abstract class avorium_core_persistence_PersistentObject {

    /**
     * Unique identifier of the persistent object
     * @avtpersistable(name="UUID", type="string", size=40)
     */
    public $uuid;

    /**
     * @var string Name of the database table. Gets read out of the annotation
     * and is set in the constructor. Can be null, when no annotation is set.
     */
    public $tablename;

    /**
     * Initializing constructor, e.g.: new User(array("name" => "Ernst"))
     * @param array $properties Properties to set by default
     * @return avorium_core_persistence_PersistentObject
     */
    public function __construct(array $properties = NULL) {
        $this->uuid = uniqid('', true);
        $metaData = avorium_core_persistence_helper_Annotation::getPersistableMetaData($this);
		if (!isset($metaData["name"]) || strlen($metaData["name"]) < 1) {
			throw new Exception('The table name of the persistent object could not be determined.');
		}
        $this->tablename = $metaData["name"];
        if ($properties === NULL) {
            return;
        }
        foreach($properties as $property => $value) {
            if (property_exists($this, $property)) {
                    $this->$property = $value;
            }
        }
    }
	
}
