<?php

namespace Response;

class Response implements ResponseInterface {
        
    private $headers = [];
    private $body;
    private $statusCode;
    private $reasonPhrase;

    public function __construct(string $body, array $headers, int $statusCode, string $reasonPhrase) {
        $this->body = $body;
        $this->headers = $headers;
        $this->statusCode = $statusCode;
        $this->reasonPhrase = $reasonPhrase;
    }

    public function getHeaders() {
        return $this->headers;
    }

    public function emitBody()
    {
        echo $this->body;   
    }

    public function getStatusCode() {
        return $this->statusCode;
    }
    public function getReasonPhrase() {
        return $this->reasonPhrase;
    }

    public static function redirect($url) {
        return new Response("", [
            "Location" => $url
        ], 302, "Found");
    }

}