<?php

/**
 * @copyright Copyright 2011 Andrew Brown. All rights reserved.
 * @license GNU/GPL, see 'help/LICENSE.html'.
 */
if (!class_exists('TestCase'))
    require '../Case.php';

class BasicValidationTest extends TestCase {

    public function testValidateObject() {
        $object = new stdClass();
        $object->a = 42;
        $object->b = '...';
        $object->c = new stdClass();
        $object->c->d = 1.0;
        // test
        $actual = (bool) BasicValidation::with($object)->isObject()->withProperty('a')
                        ->isInteger()->upOne()->withProperty('b')->isString()->upOne()
                        ->withProperty('c')->isObject()->withProperty('d')->isFloat();
        $this->assertTrue($actual);
    }

    public function testInvalidateObject() {
        $object = new stdClass();
        $object->a = 42;
        $object->b = '...';
        $object->c = new stdClass();
        $object->c->d = 1.0;
        // test: should throw Error exception
        $this->setExpectedException('Error');
        $actual = (bool) BasicValidation::with($object)->isArray();
    }

    public function testValidateArray() {
        $array = array(1, 2, 3, 'k' => 4);
        // test
        $actual = (bool) BasicValidation::with($array)->isArray()->hasKey(0)->
                        hasKey('k')->withKey('k')->isInteger();
        $this->assertTrue($actual);
    }

    public function testValidateOneOf() {
        $x = 'c';
        // test
        $actual = (bool) BasicValidation::with($x)->oneOf('a', 'b', 'c');
        $this->assertTrue($actual);
        // test invalid
        $this->setExpectedException('Error');
        BasicValidation::with($x)->oneOf(1, 2, 3);
    }

    public function testValidateIsNull() {
        $x = null;
        // test
        $actual = (bool) BasicValidation::with($x)->isNull();
        $this->assertTrue($actual);
        // test invalid
        $this->setExpectedException('Error');
        $x = 42;
        BasicValidation::with($x)->isNull();
    }

    public function testValidateBoolean() {
        $x = true;
        // test
        $actual = (bool) BasicValidation::with($x)->isBoolean();
        $this->assertTrue($actual);
        // test invalid
        $this->setExpectedException('Error');
        $x = 42;
        BasicValidation::with($x)->isBoolean();
    }

    public function testValidateInteger() {
        $x = 42;
        // test
        $actual = (bool) BasicValidation::with($x)->isInteger();
        $this->assertTrue($actual);
        // test invalid
        $this->setExpectedException('Error');
        $x = '...';
        BasicValidation::with($x)->isInteger();
    }

    public function testValidateFloat() {
        $x = 4.2;
        // test
        $actual = (bool) BasicValidation::with($x)->isFloat();
        $this->assertTrue($actual);
        // test invalid
        $this->setExpectedException('Error');
        $x = true;
        BasicValidation::with($x)->isFloat();
    }

    public function testValidateNumeric() {
        $x = '42';
        // test
        $actual = (bool) BasicValidation::with($x)->isNumeric();
        $this->assertTrue($actual);
        // test invalid
        $this->setExpectedException('Error');
        $x = true;
        BasicValidation::with($x)->isNumeric();
    }

    public function testValidateString() {
        $x = '42';
        // test
        $actual = (bool) BasicValidation::with($x)->isString();
        $this->assertTrue($actual);
        // test invalid
        $this->setExpectedException('Error');
        $x = true;
        BasicValidation::with($x)->isString();
    }

    public function testValidateScalar() {
        $x = '...';
        // test
        $actual = (bool) BasicValidation::with($x)->isScalar();
        $this->assertTrue($actual);
        // test invalid
        $this->setExpectedException('Error');
        $x = array('a');
        BasicValidation::with($x)->isScalar();
    }

    public function testValidateEmpty() {
        // test empty
        $x = '';
        $actual = (bool) BasicValidation::with($x)->isEmpty();
        $this->assertTrue($actual);
        $x = false;
        $actual = (bool) BasicValidation::with($x)->isEmpty();
        $this->assertTrue($actual);
        $x = 0;
        $actual = (bool) BasicValidation::with($x)->isEmpty();
        $this->assertTrue($actual);
        $x = array();
        $actual = (bool) BasicValidation::with($x)->isEmpty();
        $this->assertTrue($actual);
        // test not empty
        $x = '.';
        $actual = (bool) BasicValidation::with($x)->isNotEmpty();
        $this->assertTrue($actual);
        $x = array('a');
        $actual = (bool) BasicValidation::with($x)->isNotEmpty();
        $this->assertTrue($actual);
    }

    public function testValidateAlphanumeric() {
        $x = 'pocket-knife_v1';
        // test
        $actual = (bool) BasicValidation::with($x)->isAlphanumeric();
        $this->assertTrue($actual);
        // test invalid
        $this->setExpectedException('Error');
        $x = 'Some text...';
        BasicValidation::with($x)->isAlphanumeric();
    }

    public function testValidateEmail() {
        $x = 'email.address3@some-server.com';
        // test
        $actual = (bool) BasicValidation::with($x)->isEmail();
        $this->assertTrue($actual);
        // test invalid
        $this->setExpectedException('Error');
        $x = 'invalid email address / @ ';
        BasicValidation::with($x)->isEmail();
    }

    public function testValidateUrl() {
        $x = 'http://www.google.com';
        // test
        $actual = (bool) BasicValidation::with($x)->isUrl();
        $this->assertTrue($actual);
        // test invalid
        $this->setExpectedException('Error');
        $x = 'google.com';
        BasicValidation::with($x)->isUrl();
    }

    public function testValidatePath() {
        $x = __FILE__;
        // test
        $actual = (bool) BasicValidation::with($x)->isPath();
        $this->assertTrue($actual);
        // test invalid
        $this->setExpectedException('Error');
        $x = './some-nonexistent-file';
        BasicValidation::with($x)->isPath();
    }

    public function testValidateDate() {
        // test
        $x = 'tomorrow';
        $actual = (bool) BasicValidation::with($x)->isDate();
        $this->assertTrue($actual);
        $x = '21May2012';
        $actual = (bool) BasicValidation::with($x)->isDate();
        $this->assertTrue($actual);
        // test invalid
        $this->setExpectedException('Error');
        $x = 53;
        BasicValidation::with($x)->isDate();
    }

    public function testValidateHtml() {
        // test
        $x = '<p>some text with a <a href="...">link</a></p>';
        $actual = (bool) BasicValidation::with($x)->isHtml();
        $this->assertTrue($actual);
        // test invalid
        $this->setExpectedException('Error');
        $x = '<p>no end element...';
        BasicValidation::with($x)->isHtml();
    }

    public function testValidateSql() {
        // test invalid
        $this->setExpectedException('Error');
        $x = '...';
        BasicValidation::with($x)->isSql();
    }

}