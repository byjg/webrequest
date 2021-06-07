<?php


namespace ByJG\Util\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

class Response extends Message implements ResponseInterface
{
    protected static $codes = [
        "100" => "Continue",
        "101" => "Switching Protocols",
        "102" => "Processing ",
        "103" => "Early Hints",
        "200" => "OK",
        "201" => "Created",
        "202" => "Accepted",
        "203" => "Non-Authoritative Information",
        "204" => "No Content",
        "205" => "Reset Content",
        "206" => "Partial Content",
        "207" => "Multi-Status",
        "208" => "Already Reported",
        "218" => "This is fine",
        "226" => "IM Used",
        "300" => "Multiple Choices",
        "301" => "Moved Permanently",
        "302" => "Found",
        "303" => "See Other",
        "304" => "Not Modified",
        "305" => "Use Proxy",
        "306" => "Switch Proxy",
        "307" => "Temporary Redirect",
        "308" => "Permanent Redirect",
        "400" => "Bad Request",
        "401" => "Unauthorized",
        "402" => "Payment Required",
        "403" => "Forbidden",
        "404" => "Not Found",
        "405" => "Method Not Allowed",
        "406" => "Not Acceptable",
        "407" => "Proxy Authentication Required",
        "408" => "Request Timeout",
        "409" => "Conflict",
        "410" => "Gone",
        "411" => "Length Required",
        "412" => "Precondition Failed",
        "413" => "Payload Too Large",
        "414" => "URI Too Long",
        "415" => "Unsupported Media Type",
        "416" => "Range Not Satisfiable",
        "417" => "Expectation Failed",
        "418" => "I'm a teapot",
        "419" => "Page Expired",
        "420" => "Method Failure",
        "421" => "Misdirected Request",
        "422" => "Unprocessable Entity",
        "423" => "Locked",
        "424" => "Failed Dependency",
        "425" => "Too Early",
        "426" => "Upgrade Required",
        "428" => "Precondition Required",
        "429" => "Too Many Requests",
        "430" => "Request Header Fields Too Large",
        "431" => "Request Header Fields Too Large",
        "440" => "Login Time-out",
        "444" => "No Response",
        "449" => "Retry With",
        "450" => "Blocked by Windows Parental Controls",
        "451" => "Redirect",
        "460" => "AWS ELB Client close connection",
        "463" => "AWS ELB Too much X-Forwarded-For",
        "494" => "Request header too large",
        "495" => "SSL Certificate Error",
        "496" => "SSL Certificate Required",
        "497" => "HTTP Request Sent to HTTPS Port",
        "498" => "Invalid Token (Esri)",
        "499" => "Token Required (Esri)",
        "500" => "Internal Server Error",
        "501" => "Not Implemented",
        "502" => "Bad Gateway",
        "503" => "Service Unavailable",
        "504" => "Gateway Timeout",
        "505" => "HTTP Version Not Supported",
        "506" => "Variant Also Negotiates",
        "507" => "Insufficient Storage",
        "508" => "Loop Detected",
        "509" => "Bandwidth Limit Exceeded",
        "510" => "Not Extended",
        "511" => "Network Authentication Required",
        "520" => "Web Server Returned an Unknown Error",
        "521" => "Web Server Is Down",
        "522" => "Connection Timed Out",
        "523" => "Origin Is Unreachable",
        "524" => "A Timeout Occurred",
        "525" => "SSL Handshake Failed",
        "526" => "Invalid SSL Certificate",
        "527" => "Railgun Error",
        "529" => "Site is overloaded",
        "530" => "Site is frozen",
        "598" => "Network read timeout error",
    ];

    protected $statusCode = ["", ""];

    public function __construct($code = 200)
    {
        $this->setStatus($code);
    }

    public static function getInstance($code = 200): Response
    {
        return new Response($code);
    }

    /**
     * @inheritDoc
     */
    public function getStatusCode(): int
    {
        return $this->statusCode[0];
    }

    /**
     * @inheritDoc
     */
    public function withStatus($code, $reasonPhrase = ''): Response
    {
        $clone = clone $this;
        $clone->setStatus($code, $reasonPhrase);
        return $clone;
    }

    protected function setStatus($code, $reasonPhrase = ""): void
    {
        $code = intval($code);
        if ($code < 100 || $code > 599) {
            throw new InvalidArgumentException('Status code has to be an integer between 100 and 599');
        }

        $this->statusCode[0] = $code;
        $this->statusCode[1] = (empty($reasonPhrase) && isset(self::$codes[$code])) ? self::$codes[$code] : $reasonPhrase;
    }

    /**
     * @inheritDoc
     */
    public function getReasonPhrase(): string
    {
        return $this->statusCode[1];
    }
}

