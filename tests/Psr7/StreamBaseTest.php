<?php

use ByJG\Util\Psr7\StreamBase;
use PHPUnit\Framework\TestCase;

abstract class StreamBaseTest extends TestCase
{
    const TEXT1 = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam auctor augue justo, id condimentum tortor molestie et. Quisque at egestas dui. Vestibulum id lectus et mi interdum lobortis non sit.";

    /**
     * @param $data
     * @return StreamBase
     */
    abstract public function getResource($data);

    abstract public function tearDownResource();

    abstract public function isWriteable();

    abstract public function canOverwrite();

    /**
     * @var StreamBase
     */
    protected $stream;

    public function setUp(): void
    {
        $this->stream = $this->getResource(self::TEXT1);
    }

    public function tearDown(): void
    {
        $this->tearDownResource();
        $this->stream = null;
    }

    public function testToString()
    {
        $this->assertEquals(self::TEXT1, (string)$this->stream);
    }

    public function TestToStringEmpty()
    {
        $this->stream = $this->getResource(null);
        $this->assertEquals("", (string)$this->stream);
    }

    public function testGetSize()
    {
        $this->assertSame(strlen(self::TEXT1), $this->stream->getSize());
    }

    public function testTell()
    {
        $this->assertSame(0, $this->stream->tell());
        $this->stream->seek(10);
        $this->assertSame(10, $this->stream->tell());
    }

    public function testEof()
    {
        $this->assertFalse($this->stream->eof());
        $this->stream->seek(0, SEEK_END);
        $this->stream->read(1);        // It is necessary read after the end of file to get eof true.
        $this->assertTrue($this->stream->eof());
    }

    public function testIsSeekable()
    {
        $this->assertTrue($this->stream->isSeekable());
    }

    public function testSeek()
    {
        $this->assertSame(0, $this->stream->tell());
        $this->stream->seek(10);
        $this->assertSame(10, $this->stream->tell());
        $this->stream->seek(10, SEEK_CUR);
        $this->assertSame(20, $this->stream->tell());
        $this->stream->seek(-10, SEEK_END);
        $this->assertSame(strlen(self::TEXT1)-10, $this->stream->tell());
    }

    public function testRewind()
    {
        $this->assertSame(0, $this->stream->tell());
        $this->stream->seek(0, SEEK_END);
        $this->assertSame(strlen(self::TEXT1), $this->stream->tell());
        $this->stream->rewind();
        $this->assertSame(0, $this->stream->tell());
    }

    public function testIsWritable()
    {
        if ($this->isWriteable()) {
            $this->assertTrue($this->stream->isWritable());
        } else {
            $this->assertFalse($this->stream->isWritable());
        }

    }

    public function testOverwrite()
    {
        if ($this->isWriteable()) {
            $this->stream->rewind();
            $result = $this->stream->write("test");
            if ($this->canOverwrite()) {
                $this->assertEquals("test" . substr(self::TEXT1, 4), (string)$this->stream);
                $this->assertSame(4, $result);
            } else {
                $this->assertNotEquals("test" . substr(self::TEXT1, 4), (string)$this->stream);
            }
        }
    }

    public function testAppend()
    {
        if ($this->isWriteable()) {
            $this->stream->seek(0, SEEK_END);
            $result = $this->stream->write("test");
            $this->assertEquals(self::TEXT1 . "test", (string)$this->stream);
            $this->assertSame(4, $result);
        }
    }

    public function testIsReadble()
    {
        $this->assertTrue($this->stream->isReadable());
    }

    public function testRead1()
    {
        $result = $this->stream->read(6);
        $this->assertEquals("Lorem ", $result);
        $result = $this->stream->read(6);
        $this->assertEquals("ipsum ", $result);
    }

    public function testGetContents1()
    {
        $result = $this->stream->read(6);
        $this->assertEquals("Lorem ", $result);
        $result = $this->stream->getContents();
        $this->assertEquals(substr(self::TEXT1, 6), $result);
    }

    public function testGetContents2()
    {
        $this->stream->rewind();
        $result = $this->stream->getContents();
        $this->assertEquals(self::TEXT1, $result);
    }
}
