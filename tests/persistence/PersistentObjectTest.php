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
require_once dirname(__FILE__).'/PersistentObjectTestPersistentObject.php';
require_once dirname(__FILE__).'/PersistentObjectTestPersistentObjectNoTableName.php';

/**
 * Tests the functionality of the PersistentObject class, especially the
 * automatic property initialization in the constructor.
 */
class test_persistence_PersistentObjectTest extends PHPUnit_Framework_TestCase {

	/**
	 * Tests the creation of a persistent object with a default constructor#
	 * without parameters.
	 */
    public function testDefaultInitialization() {
        $ponormal = new test_persistence_PersistentObjectTestPersistentObject();
		$ponormal->uuid = 'abcdef';
		$ponormal->booleanValue = true;
		$ponormal->intValue= 1234567890;
		$ponormal->stringValue = 'Hello world!';
        $this->assertEquals('abcdef', $ponormal->uuid, 'Uuid of persistent object is not as expected.');
        $this->assertEquals(true, $ponormal->booleanValue, 'Uuid of persistent object is not as expected.');
        $this->assertEquals(1234567890, $ponormal->intValue, 'Uuid of persistent object is not as expected.');
        $this->assertEquals('Hello world!', $ponormal->stringValue, 'Uuid of persistent object is not as expected.');
    }
    
	/**
	 * Tests the creation of a persistent object with giving a parameter array
	 * to the constructor.
	 */
    public function testPropertyInitialization() {
        // Initialize persistent object with constructor array
        $ponormal = new test_persistence_PersistentObjectTestPersistentObject(array(
            'uuid' => 'abcdef',
            'booleanValue' => true,
            'intValue' => 1234567890,
            'stringValue' => 'Hello world!'
        ));
        $this->assertEquals('abcdef', $ponormal->uuid, 'Uuid of persistent object is not as expected.');
        $this->assertEquals(true, $ponormal->booleanValue, 'Uuid of persistent object is not as expected.');
        $this->assertEquals(1234567890, $ponormal->intValue, 'Uuid of persistent object is not as expected.');
        $this->assertEquals('Hello world!', $ponormal->stringValue, 'Uuid of persistent object is not as expected.');
    }

		
	/**
	 * Tries to save a persistent object without a table name annotation.
	 * This should not be possible and should throw an exception.
	 */
	public function testNoTableNameAnnotation() {
        $this->setExpectedException('Exception', 'The table name of the persistent object could not be determined.');
		new test_persistence_PersistentObjectTestPersistentObjectNoTableName();
	}

}
