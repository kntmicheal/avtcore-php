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

require_once dirname(__FILE__).'/../../code/avorium/core/persistence/PersistentObject.php';

/**
 * Test class is representing a persistent object which has different database types. Used in persistence adapter 
 * tests.
 * @avtpersistable(name="POTEST")
 */
class test_persistence_AbstractPersistenceAdapterTestPersistentObjectDifferentType
extends avorium_core_persistence_PersistentObject {

	/**
	 * Represents a boolean value
	 * @avtpersistable(name="BOOLEAN_VALUE", type="bool")
	 */
	public $booleanValue;
	
	/**
	 * Represents an integer value
	 * @avtpersistable(name="INT_VALUE", type="int")
	 */
	public $intValue;
	
	/**
	 * Type differs from the column type in database. Used to test
         * updateOrCreateTable().
	 * @avtpersistable(name="STRING_VALUE", type="int")
	 */
	public $stringValue;
	
}
