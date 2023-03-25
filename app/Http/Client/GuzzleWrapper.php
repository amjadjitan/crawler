<?php

namespace App\Http\Client;

use Exception;

class GuzzleWrapper
{
    /**
     * @param string $url
     * @param array $payload
     * @param array $headers
     * @param array $options
     * @return \Psr\Http\Message\StreamInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function get(string $url, array $payload = [], array $headers = [], array $options = [])
    {
        if(!isset($headers["Host"])){
            $headers["Host"] = $_SERVER['HTTP_HOST'];
        }
    /**/
        $headers["Cache-Control"] = 'no-cache';

        //$headers["User-Agent"] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.5060.53 Safari/537.36';
        $headers["User-Agent"] = 'PostmanRuntime/7.31.3';
    /**/
        $start_time = microtime(true);
        try {
            $response = Http::client(["base_uri" => $url])->request("GET", "", [
                "headers" => $headers,
                "allow_redirects" => true,
                "query" => $payload,
            ]);
        } catch(\Throwable $e){
            $execution_time = microtime(true) - $start_time;
            return ['errorBody' => $e->getMessage(), 'response' => $e->getCode(), 'execTime' => $execution_time];
        }
        $execution_time = microtime(true) - $start_time;
        return ['body' => $response->getBody(), 'response' => $response->getStatusCode(), 'execTime' => $execution_time];
    }

    /**
     * @param string $url
     * @param array $headers
     * @param array $payload
     * @param array $options
     * @return \Psr\Http\Message\StreamInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function post(string $url, array $headers = [], array $payload = [], array $options = [])
    {
        if (!isset($headers["Content-Type"])) {
            $headers["Content-Type"] = "application/json";
        }

        $options = [
            "headers" => $headers,
            "allow_redirects" => true,
        ];

        if (self::instr($headers["Content-Type"], "application/json")) {
            $options["body"] = json_encode($payload, true);
        } else if (self::instr($headers["Content-Type"], "application/x-www-form-urlencoded")) {
            $options["form_params"] = $payload;
        } else {
            throw new Exception("Content-Type not implemented");
        }
        $response = Http::client(["base_uri" => $url])->request("POST", "", $options);
        return ['body' => $response->getBody(), 'response' => $response->getStatusCode()];
    }

    /**
     * @param string $url
     * @param array $headers
     * @param array $payload
     * @param array $options
     * @return \Psr\Http\Message\StreamInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function put(string $url, array $headers = [], array $payload = [], array $options = [])
    {
        if (!isset($headers["Content-Type"])) {
            $headers["Content-Type"] = "application/json";
        }

        $options = [
            "headers" => $headers,
            "allow_redirects" => true,
        ];
        if (self::instr($headers["Content-Type"], "application/json")) {
            $options["body"] = json_encode($payload, true);
        } else if (self::instr($headers["Content-Type"], "application/x-www-form-urlencoded")) {
            $options["form_params"] = $payload;
        } else {
            throw new Exception("Content-Type not implemented");
        }
        $response = Http::client(["base_uri" => $url])->request("PUT", "", $options);
        return ['body' => $response->getBody(), 'response' => $response->getStatusCode()];
    }

    /**
     * @param string $s
     * @param string $search
     * @return bool
     */
    private static function instr(string $s, string $search): bool
    {
        return strpos($s, $search) !== false;
    }
}
