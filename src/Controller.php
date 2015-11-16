<?php

namespace Saffyre;

use Underscore\Types\Arrays;

final class Controller
{

    // REGISTERED DIRECTORIES

    /**
     * The array of registered directories where Saffyre looks for controller files. Each entry has the following format:
     * array(
     *     'dir' => The absolute path name to the folder containing controller files
     *     'prefix' => The prefix, as a string or regular expression, that the request must match in order for this directory to serve a response
     *     'priority' => The priority that this folder takes over other valid directories. Higher numbers have higher priorities.
     * )
     * @var array
     */
    private static $dirs = [];

    /**
     * @param $dir string The absolute path of the directory containing controller files
     * @param $prefix string The prefix that the request must match in order to be handled by the directory
     * @param null $priority The priority that the directory takes in handling requests. Higher numbers indicate higher priorities
     * @throws \Exception
     */
    public static function registerDirectory($dir, $prefix = "", $priority = null) {
        if ($priority === null)
            $priority = max(array_map(function($d) { return $d['priority']; }, self::$dirs) ?: [0]) + 1;
        $dir = rtrim($dir, DIRECTORY_SEPARATOR);
        $dir = $dir[0] == DIRECTORY_SEPARATOR ? $dir : getcwd() . $dir;
        if (!is_dir($dir))
            throw new \Exception("Could not register controllers directory: '$dir' is not a directory!");
        self::$dirs[] = ['dir' => $dir, 'prefix' => $prefix, 'priority' => $priority];
        usort(self::$dirs, function($a, $b) { return $a['priority'] - $b['priority']; });
    }

    public static function resetRegisteredDirectories()
    {
        self::$dirs = [];
    }




    // CONTROLLER STACK

    /**
     * The stack of executing controllers. The 0-th controller is the first one to start executing, and each additional
     * index is an internal request. Controllers are pushed and popped from this array as they are created and executed.
     * @var Controller[]
     */
    private static $stack = [];

    public function isMainRequest()
    {
        return self::$stack[0] === $this;
    }

    public function isInternalRequest()
    {
        return !self::isMainRequest();
    }

    /**
     * Returns the currently executing Controller.
     * @return Controller
     */
    public static function current()
    {
        return count(self::$stack) > 0 ? self::$stack[count(self::$stack)-1] : null;
    }



    // HTTP REQUEST PROPERTIES

    public $method;

    public $scheme;
    public $host;

    public $path;

    /**
     * Get an item from the path of this request, or an array containing the path segments.
     * @param int $index The index to return, or null to return the entire array.
     * @return string|array
     */
    public function path($index = null)
    {
        return $index === null ? $this->path : (isset($this->path[$index]) ? $this->path[$index] : '');
    }

    public $query;

    /**
     * @var BaseClass
     */
    public $get;
    public function get($values) {
        $this->get = new BaseClass($values);
        return $this;
    }

    /**
     * @var BaseClass
     */
    public $post;
    public function post($values) {
        $this->post = new BaseClass($values);
        return $this;
    }

    /**
     * @var HttpHeaders
     */
    public $headers;
    public function headers($values) {
        $this->headers = new HttpHeaders($values);
        return $this;
    }

    /**
     * @var BaseClass
     */
    public $cookies;
    public function cookies($values) {
        $this->cookies = new BaseClass($values);
        return $this;
    }

    private $body;
    public function body($value = null) {
        if ($value) {
            $this->body = $value;
            return $this;
        }

        if ($this->body)
            return $this->body;
        $this->body = file_get_contents('php://input');
        if (strpos($this->headers->{'Content-Type'}, 'application/json') === 0)
            $this->body = json_parse($this->body);
        return $this->body;
    }


    /**
     * Get an item from the arguments of this request (i.e. The path segments that did not match the controller file name).
     * @param null $index
     * @return array|string
     */
    public function args($index = null)
    {
        if(is_numeric($index))
            return(isset($this->args[$index]) ? $this->args[$index] : '');
        else
            return $this->args;
    }

    /**
     * Gets the entire URI of this Request.
     * @param bool $includeHost Whether to include the scheme and host.
     * @param bool $includeQuery Whether to include the query string.
     * @return string
     */
    public function request($includeHost = true, $includeQuery = true)
    {
        return ($includeHost ? "$this->scheme://$this->host" : "")
        . "/$this->path"
        . ($includeQuery ? "?$this->query" : "");
    }





    // HTTP RESPONSE PROPERTIES

    /**
     * The response data of this Controller.
     * @var
     */
    public $response;

    /**
     * The HTTP status code response.
     * @var int
     */
    public $statusCode;

    /**
     * The HTTP status message.
     * @var string
     */
    public $statusMessage;

    public function setStatusCode($code, $message = null) {
        $this->statusCode = $code;
        $this->statusMessage = $message ?: Request::getDefaultStatusMessage($code);
    }

    /**
     * @var HttpHeaders
     */
    public $responseHeaders;

    public function setHeader($name, $value) {
        $this->responseHeaders->$name = $value;
    }

    public function addHeader($name, $value) {
        if (!is_array($this->responseHeaders->$name))
            $this->responseHeaders->$name = [ $this->responseHeaders->$name ];
        array_push($this->responseHeaders->$name, $value);
    }




    // CONTROLLER FILE VALUES

    private $dir;

    /**
     * The absolute path to the registered directory that the controller file is contained in.
     * @return string
     */
    public function dir() {
        return $this->dir;
    }

    private $file = [];

    /**
     * The path of the controller file. If $absolute is false, it will be relative to $this->dir().
     * @param bool $absolute
     * @return string
     */
    public function file($absolute = false) {
        return ($absolute ? $this->dir : '') . implode(DIRECTORY_SEPARATOR, $this->file) . '.php';
    }

    private $segments = [];
    private $args = [];







    // CONSTRUCTOR & STATIC CREATION METHODS

    public function __construct($method, $url) {

        if(!count(self::$dirs))
            throw new \Exception('No controller directories have been registered! (Use Controller::registerDirectory(...))');

        $this->method = strtoupper($method);

        $url = parse_url($url);
        $this->scheme = !empty($url['scheme']) ? $url['scheme'] : ($_SERVER['HTTPS'] == 'on' ? 'https' : 'http');
        $this->host = !empty($url['host']) ? $url['host'] : $_SERVER['HTTP_HOST'];
        $this->path = !empty($url['path']) ? $url['path'] : '';
        $this->query = !empty($url['query']) ? $url['query'] : '';

        $sep = DIRECTORY_SEPARATOR;

        $this->segments = Controller::cleanPath($this->path);

        $max = null;

        foreach (self::$dirs as $dir)
        {
            $info = [
                'dir' => $dir['dir'],
                'args' => [],
                'file' => $this->segments
            ];

            do {
                $file = implode($sep, $info['file']);
                if (is_file("{$info['dir']}{$sep}{$file}{$sep}_default.php") && $info['file'][] = '_default') break;
                if (is_file("{$info['dir']}{$sep}{$file}.php")) break;
                array_unshift($info['args'], $slug = array_pop($info['file']));
            } while($slug);

            if (!$max || count(Arrays::without($info['file'], '_default')) > count(Arrays::without($max['file'], '_default')))
                $max = $info;
        }

        if(!$max || !$max['file']) {
            throw new \Exception('Invalid controller path. Maybe you don\'t have a _default.php file.');
        }

        $this->dir = $max['dir'];
        $this->args = $max['args'];
        $this->file = $max['file'];

        $this->responseHeaders = new HttpHeaders();
    }

    public static function create($method, $url) {
        return new Controller($method, $url);
    }

    public static function fromRequest()
    {
        return Controller::create($_SERVER['REQUEST_METHOD'], $url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]")
            ->get($_GET)
            ->post($_POST)
            ->cookies($_COOKIE)
            ->headers(getallheaders());
    }



    // EXECUTION METHODS

    /*
     * Indicates that this Controller's execution was canceled by a global controller.
     * @var bool
     */
    public $canceled = false;

    /**
     * Will contain the global Controllers that were executed.
     * @var Controller[]
     */
    public $globalControllers = [];

    private function executeGlobal()
    {
        $file = array();
        $args = $this->segments;
        while(true)
        {
            if(is_file(rtrim($this->dir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $file), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '_global.php'))
            {
                $controller = clone $this;
                $this->globalControllers[] = $controller;
                $controller->file = array_merge($file, array('_global'));
                $controller->executeFile();
                if ($controller->statusCode !== null)
                    $this->setStatusCode($controller->statusCode, $controller->statusMessage);
                if ($controller->response !== null || ($controller->statusCode >= 400 && $controller->statusCode < 600)) {
                    $this->canceled = true;
                    $this->response = $controller->response;
                    return;
                }
            }
            if(!$args)
                return;
            array_push($file, array_shift($args));
        }
    }

    private function executeFile()
    {
        array_push(self::$stack, $this);
        chdir(dirname($this->dir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $this->file) . '.php'));

        ob_start();
        $response = include $this->dir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $this->file) . '.php';
        $ob = ob_get_clean() ?: null;
        if ($response === 1) $response = $ob ?: null;

        array_pop(self::$stack);

        if (is_int($response) && $response >= 100 && $response < 600)
        {
            $this->setStatusCode($response);
            $response = $ob;
        }

        if ($this->statusCode === null)
            $this->setStatusCode(200);

        return $this->response = $response;
    }

    public function execute($withGlobal = true)
    {
        $this->get = $this->get ?: new BaseClass();
        $this->post = $this->post ?: new BaseClass();
        $this->cookies = $this->cookies ?: new BaseClass();
        $this->headers = $this->headers ?: new HttpHeaders();

        if ($withGlobal)
        {
            $this->executeGlobal();
            if ($this->canceled)
                return null;
        }

        return $this->executeFile();
    }








    // UTILITY METHODS

    /**
     * Cleans $path (an array of path components or a string path) by removing empty values and url-decoding the values.
     *
     * @param string|array $path The array of path components or string path to clean.
     * @param boolean $string True to return a string path, false to return an array of components
     * @return string|array The cleaned path, as a string or array
     */
    public static function cleanPath($path, $string = false)
    {
        if(!$path) return ($string ? '' : array());
        if(!is_array($path)) $path = explode('/', $path);
        $path = array_filter($path);
        foreach($path as $key => $item)
            $path[$key] = urldecode($item);
        return ($string ? implode('/', $path) : array_values($path));
    }
}