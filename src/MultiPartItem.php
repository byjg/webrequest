<?php

namespace ByJG\Util;

use InvalidArgumentException;
use PhpParser\Node\Expr\AssignOp\Mul;
use Psr\Http\Message\StreamInterface;

class MultiPartItem
{
    protected $field;

    protected $content;

    protected $filename;

    protected $contentType;

    protected $base64 = false;

    protected $contentDisposition = "form-data";

    /**
     * MultiPartItem constructor.
     *
     * @param string $field
     * @param string $content
     * @param string $filename
     * @param string $contentType
     */
    public function __construct(string $field, string $content = "", string $filename = "", string $contentType = "")
    {
        $this->field = $field;
        $this->content = $content;
        $this->filename = $filename;
        $this->contentType = $contentType;
    }

    /**
     * @param string $filename
     * @param string $contentType
     * @return MultiPartItem
     * @throws FileNotFoundException
     */
    public function loadFile(string $filename, string $contentType = ""): MultiPartItem
    {
        if (!file_exists($filename)) {
            throw new FileNotFoundException("File '$filename' does not found!");
        }

        $this->content = file_get_contents($filename);
        $this->filename = basename($filename);
        $this->contentType = $contentType;

        return $this;
    }

    public function withEncodedBase64(): MultiPartItem
    {
        $this->base64 = true;
        return $this;
    }

    /**
     * @param $type
     * @return $this
     */
    public function withContentDisposition($type): MultiPartItem
    {
        $validTypes = ["form-data", "inline", "attachment"];
        if (!in_array($type, $validTypes)) {
            throw new InvalidArgumentException("Only '" . implode("', '", $validTypes) . "' are accepted.");
        }
        $this->contentDisposition = $type;
        return $this;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getContent(): string
    {
        if ($this->isBase64()) {
            return base64_encode($this->content);
        }
        return $this->content;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function withField($field): MultiPartItem
    {
        $this->field = $field;
        return $this;
    }

    public function withContent($content): MultiPartItem
    {
        $this->content = $content;
        return $this;
    }

    public function withFilename($filename): MultiPartItem
    {
        $this->filename = $filename;
        return $this;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function withContentType($contentType): MultiPartItem
    {
        $this->contentType = $contentType;
        return $this;
    }

    /**
     * @return bool
     */
    public function isBase64(): bool
    {
        return $this->base64;
    }

    /**
     * @return string
     */
    public function getContentDisposition(): string
    {
        return $this->contentDisposition;
    }
    
    public function build(StreamInterface $stream, $boundary): void
    {
        $stream->write("--$boundary\nContent-Disposition: {$this->getContentDisposition()}; name=\"{$this->getField()}\";");
        $fileName = $this->getFileName();
        if (!empty($fileName)) {
            $stream->write(" filename=\"{$this->getFileName()}\";");
        }
        $contentType = $this->getContentType();
        if (!empty($contentType)) {
            $stream->write("\nContent-Type: {$this->getContentType()}");
        }
        if ($this->isBase64()) {
            $stream->write("\nContent-Transfer-Encoding: base64");
        }
        $stream->write("\n\n{$this->getContent()}\n");
    }
}
