<?php
// @codingStandardsIgnoreFile

namespace Jnjxp\WebUi\Output;

use Jnjxp\HttpStatus\StatusCode;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use SplFileInfo;
use Zend\Diactoros\Stream;

trait FileResponderTrait
{

    /**
     * Respond with a file
     *
     * @param Request     $request  Request for file
     * @param Response    $response File Response
     * @param SplFileInfo $file     Requested File
     *
     * @return Response
     *
     * @access protected
     */
    protected function respondWithFile(
        Request $request,
        Response $response,
        SplFileInfo $file
    ) : Response {

        if (! $file->isFile()) {
            return $response->withStatus(StatusCode::NOT_FOUND);
        }

        $response = $this->responseWithFile($response, $file);

        if ($this->isRangeRequest($request)) {
            $response = $this->responseWithRange($request, $response, $file);
        }

        return $response;
    }


    /**
     * Add file to response
     *
     * @param Response    $response File Response
     * @param SplFileInfo $file     File to respond with
     *
     * @return Response
     *
     * @access protected
     */
    protected function responseWithFile(Response $response, SplFileInfo $file) : Response
    {
        $body = new Stream((string) $file, 'rb');

        return $response
            ->withHeader('Accept-Ranges', 'bytes')
            ->withHeader('Content-Type', mime_content_type((string) $file))
            ->withHeader('Last-Modified', gmdate('D, d M Y H:i:s T', $file->getMTime()))
            ->withHeader('Content-Length', (string) $file->getSize())
            ->withBody($body);
    }

    /**
     * Is the request for a range of the file?
     *
     * @param Request $request Request for file
     *
     * @return bool
     *
     * @access protected
     */
    protected function isRangeRequest(Request $request) : bool
    {
        return $request->hasHeader('range');
    }

    /**
     * Add range to file response
     *
     * @param Request     $request  Range Request
     * @param Response    $response Range Response
     * @param SplFileInfo $file     File to respond with
     *
     * @return Response
     *
     * @access protected
     */
    protected function responseWithRange(Request $request, Response $response, SplFileInfo $file) : Response
    {
        $header = $request->getHeaderLine('range');
        $range  = $this->parseRange($header);
        $total  = $file->getSize();

        if (! $range['end']) {
            $range['end'] = $total - 1;
        }

        if ($range['start'] > $total) {
            return $response
                ->withStatus(StatusCode::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE)
                ->withHeader('Content-Range', 'bytes */' . $total);
        }

        $contentRange = sprintf(
            'bytes %s-%s/%s',
            $range['start'],
            $range['end'],
            $total
        );

        $contentLength = $range['end'] - $range['start'] + 1;

        return $response
            ->withStatus(StatusCode::HTTP_PARTIAL_CONTENT)
            ->withHeader('Content-Range', $contentRange)
            ->withHeader('Content-Length', $contentLength);
    }

    /**
     * Parse range header
     *
     * @param string $header Range header
     *
     * @return array
     * @throws Exception if invalid range header
     *
     * @access protected
     */
    protected function parseRange($header) : array
    {
        if (preg_match('/bytes=\s*(\d+)-(\d*)?/i', $header, $matches)) {
            return [
                'start' => intval($matches[1]),
                'end'   => $matches[2]
            ];
        }

        throw new \Exception('Invalid range');
    }
}
