<?php
// @codingStandardsIgnoreFile

namespace Jnjxp\WebUi\Middleware;

use Zend\Diactoros\Response as Emitter;

class ResponseSender
{
    public function __invoke($request, $response, $next)
    {
        $response = $next($request, $response);

        $range = $response->hasHeader('Content-Range');

        $emitter = ($range)
            ? new Emitter\SapiStreamEmitter
            : new Emitter\SapiEmitter;

        $emitter->emit($response);
    }
}

