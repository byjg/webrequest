<?php

use ByJG\Util\Psr7\Message;
use ByJG\Util\Psr7\MemoryStream;
use ByJG\Util\Psr7\MessageException;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    private $message;

    public function setUp(): void
    {
        $this->message = new Message();
    }

    public function tearDown(): void
    {
        $this->message = null;
    }

    public function headerDataProvider()
    {
        return [
            [
                ["mime-type", "text/plain"],
                ["Mime-Type" => ["text/plain"]]
            ],
            [
                ["MIME-TYPE", "text/plain"],
                ["Mime-Type" => ["text/plain"]]
            ],
            [
                ["accept-encoding", ["gzip", "deflate", "br"]],
                ["Accept-Encoding" => ["gzip", "deflate", "br"]]
            ]
        ];
    }

    /**
     * @dataProvider headerDataProvider
     * @param $header
     * @param $expected
     * @throws MessageException
     */
    public function testWithHeader($header, $expected)
    {
        $message = $this->message->withHeader($header[0], $header[1]);
        $this->assertEquals($expected, $message->getHeaders());
    }

    /**
     * @dataProvider headerDataProvider
     * @param $header
     * @param $expected
     * @throws MessageException
     */
    public function testHasHeader($header, $expected)
    {
        $message = $this->message->withHeader($header[0], $header[1]);
        $this->assertTrue($message->hasHeader($header[0]));
        $this->assertFalse($message->hasHeader($header[0] . "no"));
    }

    /**
     * @dataProvider headerDataProvider
     * @param $header
     * @param $expected
     * @throws MessageException
     */
    public function testGetHeader($header, $expected)
    {
        $message = $this->message->withHeader($header[0], $header[1]);
        $expected = array_values($expected);
        $this->assertEquals($expected[0], $message->getHeader($header[0]));
    }

    public function testGetProtocolVersion()
    {
        $this->assertEquals("1.1", $this->message->getProtocolVersion());
    }

    /**
     * @dataProvider headerDataProvider
     * @param $header
     * @param $expected
     * @throws MessageException
     */
    public function testGetHeaderLine($header, $expected)
    {
        $message = $this->message->withHeader($header[0], $header[1]);
        $expected = array_values($expected);
        $this->assertEquals(implode(",", $expected[0]), $message->getHeaderLine($header[0]));
    }

    /**
     * @dataProvider headerDataProvider
     * @param $header
     * @param $expected
     * @throws MessageException
     */
    public function testWithoutHeader($header, $expected)
    {
        $message = $this->message
            ->withHeader("accept-language", "en-US,en")
            ->withHeader("cache-control", "max-age=0")
            ->withHeader($header[0], $header[1]);

        $message = $message->withoutHeader("accept-language");

        $expected = array_merge(["Cache-Control" => ["max-age=0"]], $expected);
        $this->assertEquals($expected, $message->getHeaders());
    }

    /**
     * @dataProvider headerDataProvider
     * @param $header
     * @param $expected
     * @throws MessageException
     */
    public function testWithAddedHeader($header, $expected)
    {
        $message = $this->message->withHeader($header[0], $header[1])
            ->withAddedHeader($header[0], "added")
            ->withAddedHeader($header[0], ["more added", "extra"]);

        $expected = array_values($expected);
        $expected = array_merge($expected[0], ["added", "more added", "extra"]);
        $this->assertEquals($expected, $message->getHeader($header[0]));
    }

    public function testWithBody()
    {
        $stream = new MemoryStream("<html>ok</html>");
        $message = $this->message->withBody($stream);
        $this->assertEquals("<html>ok</html>", $message->getBody());
    }

    public function testWithProtocolVersion()
    {
        $message = $this->message->withProtocolVersion("1.0");
        $this->assertEquals("1.0", $message->getProtocolVersion());

        $message = $message->withProtocolVersion("1.1");
        $this->assertEquals("1.1", $message->getProtocolVersion());
    }

    /**
     * @throws MessageException
     */
    public function testWithProtocolVersionInvalid()
    {
        $this->expectException(MessageException::class);
        $this->expectExceptionMessage('Invalid Protocol Version');
        $this->message->withProtocolVersion("3.0");
    }
}
