<?php


namespace ByJG\Util\Psr7;


use ByJG\Util\Helper\ExtendedStreamInterface;
use Psr\Http\Message\StreamInterface;
use \RuntimeException;

abstract class StreamBase implements StreamInterface, ExtendedStreamInterface
{

    protected $resource;

    public function __construct()
    {
        if ($this->isDetached() || $this->resource === false) {
            throw new RuntimeException("Resource is invalid");
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        if ($this->isDetached()) {
            return "";
        }

        if ($this->getSize() === 0) {
            return "";
        }

        if ($this->isSeekable()) {
            $this->rewind();
        }

        return $this->getContents();
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        if (!$this->isDetached()) {
            return;
        }
        fclose($this->resource);
        unset($this->resource);
    }

    /**
     * @inheritDoc
     */
    public function detach()
    {
        if ($this->isDetached()) {
            return null;
        }
        $resource = $this->resource;
        unset($this->resource);
        return $resource;
    }

    /**
     * @inheritDoc
     */
    public function getSize(): ?int
    {
        if (!$this->isDetached()) {
            return (int)fstat($this->resource)['size'];
        }
        return null;
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    public function tell(): int
    {
        if ($this->isDetached()) {
            throw new RuntimeException("Stream is detached");
        }
        $result = ftell($this->resource);

        if ($result === false) {
            throw new RuntimeException("Cannot return stream position");
        }

        return $result;
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    public function eof(): bool
    {
        if ($this->isDetached()) {
            throw new RuntimeException("Stream is detached");
        }
        return feof($this->resource);
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    public function isSeekable(): bool
    {
        if ($this->isDetached()) {
            throw new RuntimeException("Stream is detached");
        }
        $seekable = $this->getMetadata('seekable');
        return is_null($seekable) ? false : $seekable;
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        if (!$this->isSeekable()) {
            throw new RuntimeException("Stream is not seekable");
        }
        fseek($this->resource, $offset, $whence);
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    public function rewind(): void
    {
        if (!$this->isSeekable()) {
            throw new RuntimeException("Stream is not seekable");
        }
        rewind($this->resource);
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    public function isWritable(): bool
    {
        if ($this->isDetached()) {
            throw new RuntimeException("Stream is detached");
        }
        $mode = $this->getMetadata('mode');
        if (is_null($mode)) {
            return false;
        }
        return stristr($mode, 'w') !== false
            || stristr($mode, 'a') !== false;
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    public function write($string): int
    {
        if (!$this->isWritable()) {
            throw new RuntimeException("Stream is not writable");
        }

        $result = fwrite($this->resource, $string);

        if ($result === false) {
            throw new RuntimeException("Could not write to stream");
        }

        fflush($this->resource);

        return $result;
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    public function isReadable(): bool
    {
        if ($this->isDetached()) {
            throw new RuntimeException("Stream is detached");
        }
        $mode = $this->getMetadata('mode');
        if (is_null($mode)) {
            return false;
        }
        return stristr($mode, 'w+') !== false
            || stristr($mode, 'a+') !== false
            || stristr($mode, 'r') !== false;
    }

    /**
     * @inheritDoc
     */
    public function read($length): string
    {
        if ($this->isDetached()) {
            throw new RuntimeException("Stream is detached");
        }
        if (feof($this->resource)) {
            return "";
        }
        return fread($this->resource, $length);
    }

    /**
     * @inheritDoc
     * @throws RuntimeException
     */
    public function getContents(): string
    {
        return $this->read($this->getSize() - $this->tell());
    }

    /**
     * @inheritDoc
     */
    public function getMetadata($key = null)
    {
        if ($this->isDetached()) {
            throw new RuntimeException("Stream is detached");
        }

        $metaData = stream_get_meta_data($this->resource);
        if (is_null($key)) {
            return $metaData;
        } else if (isset($metaData[$key])) {
            return $metaData[$key];
        }

        return null;
    }

    protected function isDetached(): bool
    {
        return (!isset($this->resource) || !is_resource($this->resource));
    }

    /**
     * @param StreamInterface $stream
     */
    public function appendStream($stream): void
    {
        if (!($stream instanceof StreamInterface)) {
            throw new RuntimeException("You need to pass a stream");
        }
        $this->seek(0, SEEK_END);
        $stream->rewind();
        $this->write($stream->getContents());
    }

    /**
     * @param string $filter
     * @param string $mode (r)ead or (w)rite
     */
    public function addFilter($filter, string $mode = "r")
    {
        stream_filter_append($this->resource, $filter, $mode == "r" ? STREAM_FILTER_READ : STREAM_FILTER_WRITE);
    }
}
