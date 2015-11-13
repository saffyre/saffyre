<?php


class BasicRoutingTest extends PHPUnit_Framework_TestCase {

    public function setUp()
    {
        parent::setUp();
        \Saffyre\Controller::resetRegisteredDirectories();
    }

    public function testDefaultController()
    {
        \Saffyre\Controller::registerDirectory(__DIR__ . '/controllers/test-1', 'http://test.com/');
        $controller = \Saffyre\Controller::create('get', 'http://test.com/');
        $controller->execute();

        $this->assertEquals(__DIR__ . '/controllers/test-1', $controller->dir());
        $this->assertEquals('_default.php', $controller->file());
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);
    }

    public function testNamedController()
    {
        \Saffyre\Controller::registerDirectory(__DIR__ . '/controllers/test-1', 'http://test.com/');
        $controller = \Saffyre\Controller::create('get', 'http://test.com/test-a');
        $controller->execute();

        $this->assertEquals(__DIR__ . '/controllers/test-1', $controller->dir());
        $this->assertEquals('test-a.php', $controller->file());
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);
    }

    public function testDeepDefaultController()
    {
        \Saffyre\Controller::registerDirectory(__DIR__ . '/controllers/test-1', 'http://test.com/');
        $controller = \Saffyre\Controller::create('get', 'http://test.com/test-b');
        $controller->execute();

        $this->assertEquals(__DIR__ . '/controllers/test-1', $controller->dir());
        $this->assertEquals('test-b/_default.php', $controller->file());
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);
    }

    public function testDeepNamedController()
    {
        \Saffyre\Controller::registerDirectory(__DIR__ . '/controllers/test-1', 'http://test.com/');
        $controller = \Saffyre\Controller::create('get', 'http://test.com/test-b/test-i');
        $controller->execute();

        $this->assertEquals(__DIR__ . '/controllers/test-1', $controller->dir());
        $this->assertEquals('test-b/test-i.php', $controller->file());
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);
    }

    public function testGlobalController()
    {
        \Saffyre\Controller::registerDirectory(__DIR__ . '/controllers/test-2', 'http://test.com/');


        $controller = \Saffyre\Controller::create('get', 'http://test.com/');
        $controller->execute();

        $this->assertEquals(__DIR__ . '/controllers/test-2', $controller->dir());
        $this->assertEquals('_default.php', $controller->file());
        $this->assertEquals(1, count($controller->globalControllers));
        $this->assertNull($controller->globalControllers[0]->response);
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);


        $controller = \Saffyre\Controller::create('get', 'http://test.com/test-a');
        $controller->execute();

        $this->assertEquals(__DIR__ . '/controllers/test-2', $controller->dir());
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
        \Saffyre\Controller::registerDirectory(__DIR__ . '/controllers/test-2', 'http://test.com/');
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
}