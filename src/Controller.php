<?php

namespace Saffyre;

use Underscore\Underscore as _;

final class Controller
{
    // REGISTERED DIRECTORIES

    /**
     * The array of registered directories where Saffyre looks for controller files.
     * @see Controller::registerDirectory
     * @var array
     */
    private static $directories = [];

    /**
     * @param $directory array|string The absolute path name to the folder containing controller files, or a settings array
     *     'path' => The absolute path name to the folder containing controller files
     *     'prefix' => string, the start of the request path that must match in order to be handled by the directory
     *     'priority' => integer, the priority that the directory takes in handling requests. Higher integers indicate that the directory will handle requests before other matching directories with lower priority.
     *     'extensions' => bool|string|array, whether to allow content-type extensions in the last path segment.
     *         'true' allows content-type extensions on all paths.
     *         a string or array of strings of request path prefixes that allow content-type extensions.
     * @throws \Exception
     */
    public static function registerDirectory($directory = []) {
        if (is_string($directory))
            $directory = [ 'path' => $directory ];
        $directory += [
            'prefix' => '',
            'priority' => max(array_map(function($d) { return $d['priority']; }, self::$directories) ?: [0]) + 1,
            'extensions' => false
        ];
        $directory['path'] = rtrim($directory['path'], DIRECTORY_SEPARATOR);
        $directory['path'] = $directory['path'][0] == DIRECTORY_SEPARATOR ? $directory['path'] : getcwd() . $directory['path'];
        if (!is_dir($directory['path']))
            throw new \Exception("Could not register controllers directory: '{$directory['path']}' is not a directory!");

        if (is_string($directory['extensions']))
            $directory['extensions'] = [ $directory['extensions'] ];
        if (is_array($directory['extensions']))
            $directory['extensions'] = array_map(function($prefix) { return trim($prefix, '/'); }, $directory['extensions']);

        self::$directories[] = $directory;
        usort(self::$directories, function($a, $b) { return $a['priority'] - $b['priority']; });
    }

    public static function resetRegisteredDirectories()
    {
        self::$directories = [];
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
    public $port;
    public $path;
    public $query;

    public $extension;

    /**
     * Get an item from the path of this request, or an array containing the path segments.
     * @param int $index The index to return, or null to return the entire array.
     * @return string|array
     */
    public function path($index = null)
    {
        $parts = $this->cleanPath($this->path);
        return $index === null ? $parts : (isset($parts[$index]) ? $$parts[$index] : '');
    }

    const URL_PART_SCHEME = 0b00001;
    const URL_PART_HOST   = 0b00010;
    const URL_PART_PORT   = 0b00100;
    const URL_PART_PATH   = 0b01000;
    const URL_PART_QUERY  = 0b10000;
    const URL_PART_BASE   = 0b00111;
    const URL_PART_ALL    = 0b11111;

    public function buildUrl($parts = Controller::URL_PART_ALL)
    {
        return ($parts & Controller::URL_PART_SCHEME ? "$this->scheme://" : '')
             . ($parts & Controller::URL_PART_HOST ? $this->host : '')
             . ($parts & Controller::URL_PART_PORT ? ":$this->port" : '')
             . ($parts & Controller::URL_PART_PATH ? "/$this->path" : '')
             . ($parts & Controller::URL_PART_QUERY ? "?$this->query" : '');
    }

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
            $this->body = new BaseClass($this->body);
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

    /**
     * The entry from Controller::$directories that this controller matches.
     * @see Controller::$directories
     * @var array
     */
    public $directory;

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

        if(!count(self::$directories))
            throw new \Exception('No controller directories have been registered! (Use Controller::registerDirectory(...))');

        $this->method = strtoupper($method);

        $url = parse_url($url);
        $this->scheme = !empty($url['scheme']) ? $url['scheme'] : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https' : 'http');
        $this->host = !empty($url['host']) ? $url['host'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
        $this->port = !empty($url['port']) ? $url['port'] : (isset($_SERVER['HTTP_PORT']) ? $_SERVER['HTTP_PORT'] : '');
        $this->path = !empty($url['path']) ? trim($url['path'], '/') : '';
        $this->query = !empty($url['query']) ? $url['query'] : '';

        $sep = DIRECTORY_SEPARATOR;

        $this->segments = Controller::cleanPath($this->path);

        $max = null;

        foreach (self::$directories as $dir)
        {
            $info = [
                'dir' => $dir,
                'args' => [],
                'file' => $this->segments,
                'extension' => ''
            ];

            if ($dir['prefix'])
            {
                $prefix = Controller::cleanPath($dir['prefix']);
                if ($prefix == (array)_::first($this->segments, count($prefix)))
                {
                    $info['file'] = (array)_::rest($this->segments, count($prefix));
                }
            }

            if ($dir['extensions'])
            {
                $lastIndex = count($info['file']) - 1;
                $fileParts = explode('.', $info['file'][$lastIndex], 2);
                if (count($fileParts) == 2)
                {
                    if (is_string($dir['extensions']))
                        $dir['extensions'] = [ $dir['extensions'] ];
                    if (!is_array($dir['extensions']) ||
                        (is_array($dir['extensions']) &&
                            count(array_filter($dir['extensions'], function($prefix) {
                                return strpos($this->path, $prefix . '/') === 0
                                    || strpos($this->path, $prefix . '.') === 0
                                    || trim($prefix, '/') === $this->path;
                            }))
                        )
                    )
                    {
                        $info['extension'] = $fileParts[1];
                        $info['file'][$lastIndex] = $fileParts[0];
                    }
                }
            }

            do {
                $file = implode($sep, $info['file']);
                if (is_file("{$info['dir']['path']}{$sep}{$file}{$sep}_default.php") && $info['file'][] = '_default') break;
                if (is_file("{$info['dir']['path']}{$sep}{$file}.php")) break;
                array_unshift($info['args'], $slug = array_pop($info['file']));
            } while($slug);

            if (!$max || count(_::reject($info['file'], function($f) { return $f == '_default'; })) > count(_::reject($max['file'], function($f) { return $f == '_default'; })))
                $max = $info;
        }

        if(!$max || !$max['file']) {
            throw new \Exception('Invalid controller path. Maybe you don\'t have a _default.php file.');
        }

        $this->dir = $max['dir'];
        $this->args = $max['args'];
        $this->file = $max['file'];
        $this->extension = $max['extension'];

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
            if(is_file(rtrim($this->dir['path'] . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $file), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '_global.php'))
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
        chdir(dirname($this->dir['path'] . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $this->file) . '.php'));

        ob_start();
        $response = include $this->dir['path'] . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $this->file) . '.php';
        $ob = ob_get_clean() ?: null;
        if ($response === 1 || ($ob && $response === null)) $response = $ob ?: null;

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
                return $this->response;
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