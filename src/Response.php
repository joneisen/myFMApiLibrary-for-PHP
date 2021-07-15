<?php

namespace RCConsulting\FileMakerApi;

use RCConsulting\FileMakerApi\Exception\Exception;

/**
 * Class Response
 * @package RCConsulting\FileMakerApi
 */
final class Response
{
    /**
     * @var array
     */
    private $headers = [];
    /**
     * @var string
     */
    private $body;
    /**
     * @var int
     */
    private $responseCodeHTTP = null;

    /**
     * Response constructor.
     *
     * @param array $headers
     * @param array $body
     *
     * @throws Exception
     */
    public function __construct(array $headers, array $body)
    {
        $this->headers = $headers;
        $this->body = $body;
        // parses "HTTP/1.1 200 OK" and returns the 200
        $this->responseCodeHTTP = (int)explode(" ", $this->getHeader("Status"))[1];
    }

    /**
     * @param string $headers
     * @param string $body
     *
     * @return self
     * @throws Exception
     */
    public static function parse(string $headers, string $body)
    {
        return new self(self::parseHeaders($headers), self::parseBody($body));
    }

    /**
     * @param $header
     *
     * @return mixed
     * @throws Exception
     */
    public function getHeader(string $header)
    {
        if (isset($this->headers[$header])) {
            return $this->headers[$header];
        } else {
            throw new Exception("Header not found");
        }
    }

    /**
     * @return int
     */
    public function getHttpCode()
    {
        return $this->responseCodeHTTP;
    }

    /**
     * @return array
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $headers
     *
     * @return array
     * @throws Exception
     */
    private static function parseHeaders(string $headers)
    {
        // We convert the raw header string into an array
        $headers = explode("\n", $headers);
        $headers = array_map(function ($header) {
            $exploded = explode(":", $header, 2);

            return array_map(function ($value) {
                return trim($value);
            }, $exploded);
        }, $headers);

        // We remove empty lines in array
        $headers = array_filter($headers, function ($value) {
            return (is_array($value) ? $value[0] : $value) !== '';
        });

        // Lastly, we clean the array format to be a key => value array
        // The response code is special as there is no key. We handle it differently
        $statusHeader = [];
        foreach ($headers as $index => $header) {
            if (isset($header[1])) {
                break;
            }

            $statusHeader = [
                'Status' => $header[0],
            ];
            unset($headers[$index]);
        }
        $processedHeaders = $statusHeader;

        foreach ($headers as $header) {
            if (!isset($header[1])) {
                continue;
            }

            $processedHeaders[$header[0]] = $header[1];
        }

        return $processedHeaders;
    }

    /**
     * @param string $body
     *
     * @return array
     * @throws Exception
     */
    private static function parseBody(string $body)
    {
        return json_decode($body, true, JSON_THROW_ON_ERROR);
    }

}
