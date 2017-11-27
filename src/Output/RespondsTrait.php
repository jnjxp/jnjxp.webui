<?php
/**
 * Web UI
 *
 * PHP version 5
 *
 * Copyright (C) 2017 Jake Johns
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 *
 * @category  Output
 * @package   Jnjxp\WebUi
 * @author    Jake Johns <jake@jakejohns.net>
 * @copyright 2017 Jake Johns
 * @license   http://jnj.mit-license.org/2017 MIT License
 * @link      http://jakejohns.net
 */


namespace Jnjxp\WebUi\Output;

use Aura\Payload_Interface\PayloadInterface as Payload;
use Jnjxp\HttpStatus\StatusCode as HttpStatus;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * RespondsTrait
 *
 * @category Output
 * @package  Jnjxp\WebUi
 * @author   Jake Johns <jake@jakejohns.net>
 * @license  https://jnj.mit-license.org/ MIT License
 * @link     https://github.com/jnjxp
 */
trait RespondsTrait
{

    /**
     * Request to respond to
     *
     * @var Request
     *
     * @access protected
     */
    protected $request;

    /**
     * Response
     *
     * @var Response
     *
     * @access protected
     */
    protected $response;

    /**
     * Domain Payload
     *
     * @var PayloadInterface | null
     *
     * @access protected
     */
    protected $payload;

    /**
     * Debug
     *
     * @var bool
     *
     * @access protected
     */
    protected $debug = false;

    /**
     * __invoke
     *
     * @param Request  $request  Request to respond to
     * @param Response $response Response
     * @param Payload  $payload  Domain payload
     *
     * @return Response
     *
     * @access public
     */
    public function __invoke(
        Request $request,
        Response $response,
        Payload $payload = null
    ) : Response {

        $this->request  = $request;
        $this->response = $response;
        $this->payload  = $payload;

        $this->respond();

        return $this->response;
    }

    /**
     * Enable Debug
     *
     * @return void
     *
     * @access public
     */
    public function enableDebugging() : void
    {
        $this->debug = true;
    }

    /**
     * Get Current Response
     *
     * @return Response
     *
     * @access protected
     */
    protected function getResponse() : Response
    {
        return $this->response;
    }

    /**
     * Set Response
     *
     * @param Response $response DESCRIPTION
     *
     * @return void
     *
     * @access protected
     */
    protected function setResponse(Response $response) : void
    {
        $this->response = $response;
    }

    /**
     * Get Request
     *
     * @return Request
     *
     * @access protected
     */
    protected function getRequest() : Request
    {
        return $this->request;
    }

    /**
     * Get Payload
     *
     * @return Payload
     *
     * @access protected
     */
    protected function getPayload()
    {
        return $this->payload;
    }

    /**
     * Respond
     *
     * @return void
     *
     * @access protected
     */
    protected function respond() : void
    {
        $method = $this->getMethodForPayload();
        $this->$method();
    }

    /**
     * Get method based on Payload Status
     *
     * @return string
     *
     * @access protected
     */
    protected function getMethodForPayload() : string
    {
        if (! $this->payload) {
            return 'noContent';
        }
        $method = str_replace('_', '', strtolower($this->payload->getStatus()));
        return method_exists($this, $method) ? $method : 'unknown';
    }

    /**
     * No content from domain
     *
     * @return void
     *
     * @access protected
     */
    protected function noContent() : void
    {
        $response = $this->getResponse()
            ->withStatus(HttpStatus::HTTP_NO_CONTENT);
        $this->setResponse($response);
    }

    /**
     * Unknown Domain Status
     *
     * @return void
     *
     * @access protected
     */
    protected function unknown() : void
    {
        $payload  = $this->getPayload();
        $status   = $payload->getStatus();
        $response = $this->getResponse();

        $message = sprintf(
            'Invalid Status: "%s"',
            $status ? $status : 'null'
        );

        $response = $response->withStatus(HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
        $body     = $response->getBody();

        $body->write($message);
        $this->setResponse($response);
    }

    /**
     * Domain produced an error
     *
     * @return void
     *
     * @access protected
     */
    protected function error() : void
    {
        if ($this->debug) {
            $payload   = $this->getPayload();
            $exception = $payload->getOutput();
            throw $exception;
        }

        $response = $this->getResponse();

        $response = $response->withStatus(HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
        $body     = $response->getBody();

        $body->write('Internal server error');
        $this->setResponse($response);
    }
}
