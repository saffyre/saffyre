<?php

class ContentTypeExtensionsTest extends PHPUnit_Framework_TestCase {

    static $controllers = __DIR__ . '/controllers/content-type-extensions';

    public function setUp()
    {
        parent::setUp();
        \Saffyre\Controller::resetRegisteredDirectories();
    }

    public function testNoExtensions()
    {
        \Saffyre\Controller::registerDirectory(self::$controllers);
        $controller = \Saffyre\Controller::create('get', 'test.html');
        $controller->execute();

        $this->assertEquals(self::$controllers, $controller->dir['path']);
        $this->assertEquals('_default.php', $controller->file());
        $this->assertEquals('test.html', $controller->args(0));
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);
    }

    public function testAllExtensionsAllowed()
    {
        \Saffyre\Controller::registerDirectory([
            'path' => self::$controllers,
            'extensions' => true
        ]);


        $controller = \Saffyre\Controller::create('get', 'other.html');
        $controller->execute();

        $this->assertEquals(self::$controllers, $controller->dir['path']);
        $this->assertEquals('_default.php', $controller->file());
        $this->assertEquals('other', $controller->args(0));
        $this->assertEquals('html', $controller->extension);
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);


        $controller = \Saffyre\Controller::create('get', 'test-a.json');
        $controller->execute();

        $this->assertEquals(self::$controllers, $controller->dir['path']);
        $this->assertEquals('test-a.php', $controller->file());
        $this->assertEquals(0, count($controller->args()));
        $this->assertEquals('json', $controller->extension);
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);


        $controller = \Saffyre\Controller::create('get', 'test-a/other.txt');
        $controller->execute();

        $this->assertEquals(self::$controllers, $controller->dir['path']);
        $this->assertEquals('test-a.php', $controller->file());
        $this->assertEquals('other', $controller->args(0));
        $this->assertEquals('txt', $controller->extension);
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);


        $controller = \Saffyre\Controller::create('get', 'test-b/test-i.xml');
        $controller->execute();

        $this->assertEquals(self::$controllers, $controller->dir['path']);
        $this->assertEquals('test-b/test-i.php', $controller->file());
        $this->assertEquals(0, count($controller->args()));
        $this->assertEquals('xml', $controller->extension);
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);


        $controller = \Saffyre\Controller::create('get', 'test-b/test-i/other.anything');
        $controller->execute();

        $this->assertEquals(self::$controllers, $controller->dir['path']);
        $this->assertEquals('test-b/test-i.php', $controller->file());
        $this->assertEquals('other', $controller->args(0));
        $this->assertEquals('anything', $controller->extension);
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);
    }


    public function testSinglePathPrefixExtensionsAllowed()
    {
        \Saffyre\Controller::registerDirectory([
            'path' => self::$controllers,
            'extensions' => '/test-a'
        ]);


        // Type extensions on / are not allowed
        $controller = \Saffyre\Controller::create('get', 'other.json');
        $controller->execute();
        $this->assertEquals(self::$controllers, $controller->dir['path']);
        $this->assertEquals('_default.php', $controller->file());
        $this->assertEquals('other.json', $controller->args(0));
        $this->assertEquals('', $controller->extension);
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);


        // Type extensions on /test-a are allowed
        $controller = \Saffyre\Controller::create('get', 'test-a.xml');
        $controller->execute();
        $this->assertEquals(self::$controllers, $controller->dir['path']);
        $this->assertEquals('test-a.php', $controller->file());
        $this->assertEquals(0, count($controller->args()));
        $this->assertEquals('xml', $controller->extension);
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);

        // Type extensions on /test-a with additional segments are allowed
        $controller = \Saffyre\Controller::create('get', 'test-a/other.txt');
        $controller->execute();
        $this->assertEquals(self::$controllers, $controller->dir['path']);
        $this->assertEquals('test-a.php', $controller->file());
        $this->assertEquals('other', $controller->args(0));
        $this->assertEquals('txt', $controller->extension);
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);

        // Type extensions on /test-b are not allowed, so /_default.php handles request because /test-b/test-i.html.php
        // is not a file that exists, and /test-b/_default.php does not exist either
        $controller = \Saffyre\Controller::create('get', 'test-b/test-i.html');
        $controller->execute();
        $this->assertEquals(self::$controllers, $controller->dir['path']);
        $this->assertEquals('_default.php', $controller->file());
        $this->assertEquals('test-b', $controller->args(0));
        $this->assertEquals('test-i.html', $controller->args(1));
        $this->assertEquals('', $controller->extension);
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);
    }


    public function testMultiplePathPrefixesExtensions()
    {
        \Saffyre\Controller::registerDirectory([
            'path' => self::$controllers,
            'extensions' => [
                '/test-a',
                '/test-b/test-i'
            ]
        ]);


        // Type extensions on / are not allowed
        $controller = \Saffyre\Controller::create('get', 'other.json');
        $controller->execute();
        $this->assertEquals(self::$controllers, $controller->dir['path']);
        $this->assertEquals('_default.php', $controller->file());
        $this->assertEquals('other.json', $controller->args(0));
        $this->assertEquals('', $controller->extension);
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);


        // Type extensions on /test-a are allowed
        $controller = \Saffyre\Controller::create('get', 'test-a.xml');
        $controller->execute();
        $this->assertEquals(self::$controllers, $controller->dir['path']);
        $this->assertEquals('test-a.php', $controller->file());
        $this->assertEquals(0, count($controller->args()));
        $this->assertEquals('xml', $controller->extension);
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);


        // Type extensions on /test-a with additional segments are allowed
        $controller = \Saffyre\Controller::create('get', 'test-a/other.txt');
        $controller->execute();
        $this->assertEquals(self::$controllers, $controller->dir['path']);
        $this->assertEquals('test-a.php', $controller->file());
        $this->assertEquals('other', $controller->args(0));
        $this->assertEquals('txt', $controller->extension);
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);

        // Type extensions on /test-b/test-i are allowed
        $controller = \Saffyre\Controller::create('get', 'test-b/test-i.html');
        $controller->execute();
        $this->assertEquals(self::$controllers, $controller->dir['path']);
        $this->assertEquals('test-b/test-i.php', $controller->file());
        $this->assertEquals('', $controller->args(0));
        $this->assertEquals('html', $controller->extension);
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);

        // Type extensions on /test-b/test-ii are not allowed
        $controller = \Saffyre\Controller::create('get', 'test-b/test-ii.html');
        $controller->execute();
        $this->assertEquals(self::$controllers, $controller->dir['path']);
        $this->assertEquals('_default.php', $controller->file());
        $this->assertEquals('test-b', $controller->args(0));
        $this->assertEquals('test-ii.html', $controller->args(1));
        $this->assertEquals('', $controller->extension);
        $this->assertEquals(200, $controller->statusCode);
        $this->assertEquals('OK', $controller->statusMessage);
    }
}