<?php

//abstract class BaseTestCase extends \PHPUnit\Framework\TestCase
abstract class BaseTestCase extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->caseSetUp();
    }

    public function expectException($exception)
    {
        self::setExpectedException($exception);
    }

    abstract public function caseSetUp();
}