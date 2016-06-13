<?php

namespace Saffyre;


/**
 * Utility methods for working with the current HTTP request.
 * Note that they do not affect or apply to internal requests.
 * @package Saffyre
 */
class Request {

    /**
     * Redirects the current request using an HTTP 301 redirect, then terminates execution. Optionally sets extra headers.
     *
     * @param string $url The url to redirect to.
     * @param array $headers Additional headers to set (each array value should be an entire header line).
     */
    public static function redirect($url, $temporary = false, $headers = array())
    {
        $headers = $headers ?: [];
        array_unshift($headers, 'HTTP/1.1 ' . ($temporary ? '302 Moved Temporarily' : '301 Moved Permanently'));
        if(preg_match('/^https?:\/\//', $url))
            header("Location: $url");
        else
            header('Location: ' . rtrim(Config::$baseUrl, '/') . '/' . ltrim($url, '/'));
        if(is_array($headers)) foreach($headers as $header) header($header);
        exit;
    }

    /**
     * Sends an HTTP error code header. This method DOES NOT terminate execution.
     *
     * @param $code int The status code.
     * @param $message string The status message. If null, the default corresponding message for the status code is used.
     */
    public static function responseStatus($code, $message)
    {
        if (headers_sent())
            return;

        if ($message === null)
            $message = self::getDefaultStatusMessage($code);

        header("HTTP/1.1 $code $message");
    }

    public static function getDefaultStatusMessage($code)
    {
        $messages = [
            200 => 'OK',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
        ];
        return isset($messages[$code]) ? $messages[$code] : "";
    }

    /**
     * Indicates whether the request was an ajax call (based on the X-Requested-With header).
     *
     * @return boolean Whether this request was an ajax call.
     */
    public static function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
    }

    /**
     * Indicates whether the request was secure.
     * @return boolean
     */
    public static function isSSL()
    {
        return !empty($_SERVER['HTTPS']);
    }
}
