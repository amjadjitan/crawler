<?php

namespace App\Http\Client;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\RetryMiddleware;

class Http
{
    public const MAX_RETRIES = 5;

    public static function client(array $options = []): Client
    {
        $stack = HandlerStack::create();
        $stack->push(Middleware::retry(self::decider(), self::delay()));
        return new Client(["handler" => $stack] + $options);
    }

    private static function decider(): callable
    {
        return function (
            $retries,
            $request,
            $response = null,
            $exception = null
        ) {
            if ($retries >= self::MAX_RETRIES) {
                return false;
            }

            // Retry connection exceptions
            if ($exception) {
                /* Logger::logWithTempProcessor("NETWORK_ERROR_RETRYING", fn(array $x): array => $x + [
                   "source" => "guzzle",
                   "error" => $exception instanceof \Exception ? $exception->getMessage() : "N/A"
                 ], Logger::WARNING);*/
                return true;
            }

            return false;
        };
    }

    private static function delay(): callable
    {
        return function (int $retry) {
            return RetryMiddleware::exponentialDelay($retry);
        };
    }
}
