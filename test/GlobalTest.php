<?php

class GlobalTest extends PHPUnit_Framework_TestCase
{
    static $controllers = __DIR__ . '/controllers/global';

    public function setUp()
    {
        parent::setUp();
        \Saffyre\Controller::resetRegisteredDirectories();
    }

    public function testGlobalController()
    {
        \Saffyre\Controller::registerDirectory(self::$controllers, 'http://test.com/');


        $controller = \Saffyre\Controller::create('get', 'http://test.com/');
        $controller->execute();

        $this->assertEquals(self::$controllers, $controller->dir());
        $this->assertEquals('_default.php', $controller->file());
        $this->assertEquals(1, count($controller->globalControllers));
        $this->assertNull($controller->globalControllers[0]->response);
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);


        $controller = \Saffyre\Controller::create('get', 'http://test.com/test-a');
        $controller->execute();

        $this->assertEquals(self::$controllers, $controller->dir());
        $this->assertEquals('test-a/_default.php', $controller->file());
        $this->assertEquals(2, count($controller->globalControllers));
        $this->assertEquals('_global.php', $controller->globalControllers[0]->file());
        $this->assertEquals('test-a/_global.php', $controller->globalControllers[1]->file());
        $this->assertNull($controller->globalControllers[0]->response);
        $this->assertNull($controller->globalControllers[1]->response);
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);
    }

    public function testGlobalCanceledController()
    {
        \Saffyre\Controller::registerDirectory(self::$controllers, 'http://test.com/');
        $controller = \Saffyre\Controller::create('get', 'http://test.com/test-b/test-i');
        $controller->execute();

        $this->assertEquals('', $controller->response);
        $this->assertEquals(2, count($controller->globalControllers));
        $this->assertEquals('_global.php', $controller->globalControllers[0]->file());
        $this->assertEquals('test-b/_global.php', $controller->globalControllers[1]->file());
        $this->assertNull($controller->globalControllers[0]->response);
        $this->assertFalse($controller->globalControllers[1]->response);
        $this->assertTrue($controller->canceled);
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);
    }

    public function testGlobalErrorStatusController()
    {
        \Saffyre\Controller::registerDirectory(self::$controllers, 'http://test.com/');
        $controller = \Saffyre\Controller::create('get', 'http://test.com/test-c');
        $controller->execute();

        $this->assertEquals('', $controller->response);
        $this->assertEquals(2, count($controller->globalControllers));
        $this->assertEquals('_global.php', $controller->globalControllers[0]->file());
        $this->assertEquals('test-c/_global.php', $controller->globalControllers[1]->file());
        $this->assertNull($controller->globalControllers[0]->response);
        $this->assertNull($controller->globalControllers[1]->response);
        $this->assertTrue($controller->canceled);
        $this->assertEquals(400, $controller->statusCode);
        $this->assertEquals('Bad Request', $controller->statusMessage);
    }

    public function testGlobalOutputController()
    {
        \Saffyre\Controller::registerDirectory(self::$controllers, 'http://test.com/');
        $controller = \Saffyre\Controller::create('get', 'http://test.com/test-d');
        $controller->execute();

        $this->assertEquals('Error', $controller->response);
        $this->assertEquals(2, count($controller->globalControllers));
        $this->assertEquals('_global.php', $controller->globalControllers[0]->file());
        $this->assertEquals('test-d/_global.php', $controller->globalControllers[1]->file());
        $this->assertNull($controller->globalControllers[0]->response);
        $this->assertEquals('Error', $controller->globalControllers[1]->response);
        $this->assertTrue($controller->canceled);
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);
    }
}
