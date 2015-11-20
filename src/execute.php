<?php

namespace Saffyre {

    /**
     * The main execution method for the Saffyre framework. The method should only be invoked ONCE and there is no guarantee that
     * it will return execution (many methods and controllers will die or exit).
     *
     * @param string $controllerDirectory The path to a controller directory to register before executing.
     * @param boolean $return True to return the response, or false to output it.
     * @throws \Exception
     * @return string
     */
    function execute($controllerDirectory = null, $return = false, Controller $controller = null)
    {
        if ($controllerDirectory)
            Controller::registerDirectory($controllerDirectory);

        if (!$controller)
            $controller = Controller::fromRequest();

        $result = $controller->execute(true);

        foreach ($controller->responseHeaders as $header => $value)
            header("$header: $value");

        // Convert the output to a string:
        // - If the result is an object that implements __toString, convert it to a string
        // - If the result is an array, json_encode it
        // - If the response content-type header is json, json_encode it
        // - If the result is an object that implements JsonSerializable, json_encode it

        if (method_exists($result, '__toString'))
            $result = (string)$result;
        else if (
            is_array($result) ||
            (is_object($result) && array_filter(headers_list(), function($h) { return strpos(strtolower(preg_replace('/\\s/', '', $h)), 'content-type:application/json') === 0; })) ||
            $result instanceof \JsonSerializable
        )
            $result = json_encode($result);

        if ($controller->statusCode != null)
            Request::responseStatus($controller->statusCode, $controller->statusMessage);

        if ($return) return $result;
        else echo $result;
    }
}

// TODO: maybe this should move to another file...
namespace {
    if (!function_exists('getallheaders'))
    {
        function getallheaders()
        {
            $headers = [];
            foreach ($_SERVER as $name => $value)
                if (substr($name, 0, 5) == 'HTTP_')
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            if (isset($_SERVER['CONTENT_TYPE'])) $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
            if (isset($_SERVER['CONTENT_LENGTH'])) $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
            return $headers;
        }
    }
}