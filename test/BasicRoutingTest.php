<?php

class BasicRoutingTest extends PHPUnit_Framework_TestCase {

    static $controllers = __DIR__ . '/controllers/basic-routing';

    public function setUp()
    {
        parent::setUp();
        \Saffyre\Controller::resetRegisteredDirectories();
    }

    public function testDefaultController()
    {
        \Saffyre\Controller::registerDirectory(self::$controllers, 'http://test.com/');
        $controller = \Saffyre\Controller::create('get', 'http://test.com/');
        $controller->execute();

        $this->assertEquals(self::$controllers, $controller->dir());
        $this->assertEquals('_default.php', $controller->file());
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);
    }

    public function testNamedController()
    {
        \Saffyre\Controller::registerDirectory(self::$controllers, 'http://test.com/');
        $controller = \Saffyre\Controller::create('get', 'http://test.com/test-a');
        $controller->execute();

        $this->assertEquals(self::$controllers, $controller->dir());
        $this->assertEquals('test-a.php', $controller->file());
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);
    }

    public function testDeepDefaultController()
    {
        \Saffyre\Controller::registerDirectory(self::$controllers, 'http://test.com/');
        $controller = \Saffyre\Controller::create('get', 'http://test.com/test-b');
        $controller->execute();

        $this->assertEquals(self::$controllers, $controller->dir());
        $this->assertEquals('test-b/_default.php', $controller->file());
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);
    }

    public function testDeepNamedController()
    {
        \Saffyre\Controller::registerDirectory(self::$controllers, 'http://test.com/');
        $controller = \Saffyre\Controller::create('get', 'http://test.com/test-b/test-i');
        $controller->execute();

        $this->assertEquals(self::$controllers, $controller->dir());
        $this->assertEquals('test-b/test-i.php', $controller->file());
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);
    }

}