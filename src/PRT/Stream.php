<?php

namespace Ludelix\PRT;

/**
 * Stream Handler
 * 
 * Handles streaming responses and file operations
 */
class Stream
{
    protected $resource;
    protected bool $readable = false;
    protected bool $writable = false;
    protected bool $seekable = false;

    public function __construct($resource = null)
    {
        if ($resource !== null) {
            $this->attach($resource);
        }
    }

    /**
     * Attach resource to stream
     */
    public function attach($resource): void
    {
        $this->resource = $resource;
        
        if (is_resource($resource)) {
            $meta = stream_get_meta_data($resource);
            $mode = $meta['mode'] ?? '';
            
            $this->readable = str_contains($mode, 'r') || str_contains($mode, '+');
            $this->writable = str_contains($mode, 'w') || str_contains($mode, 'a') || str_contains($mode, '+');
            $this->seekable = $meta['seekable'] ?? false;
        }
    }

    /**
     * Create stream from string
     */
    public static function fromString(string $content): self
    {
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, $content);
        rewind($resource);
        
        return new self($resource);
    }

    /**
     * Create stream from file
     */
    public static function fromFile(string $path, string $mode = 'r'): self
    {
        $resource = fopen($path, $mode);
        return new self($resource);
    }

    /**
     * Read from stream
     */
    public function read(int $length): string
    {
        if (!$this->readable) {
            throw new \RuntimeException('Stream is not readable');
        }
        
        return fread($this->resource, $length);
    }

    /**
     * Write to stream
     */
    public function write(string $data): int
    {
        if (!$this->writable) {
            throw new \RuntimeException('Stream is not writable');
        }
        
        return fwrite($this->resource, $data);
    }

    /**
     * Get stream contents
     */
    public function getContents(): string
    {
        if (!$this->readable) {
            throw new \RuntimeException('Stream is not readable');
        }
        
        return stream_get_contents($this->resource);
    }

    /**
     * Get entire stream as string
     */
    public function toString(): string
    {
        if ($this->seekable) {
            $this->rewind();
        }
        
        return $this->getContents();
    }

    /**
     * Seek to position
     */
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!$this->seekable) {
            throw new \RuntimeException('Stream is not seekable');
        }
        
        fseek($this->resource, $offset, $whence);
    }

    /**
     * Rewind stream
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * Get current position
     */
    public function tell(): int
    {
        return ftell($this->resource);
    }

    /**
     * Check if at end of stream
     */
    public function eof(): bool
    {
        return feof($this->resource);
    }

    /**
     * Get stream size
     */
    public function getSize(): ?int
    {
        if (!is_resource($this->resource)) {
            return null;
        }
        
        $stats = fstat($this->resource);
        return $stats['size'] ?? null;
    }

    /**
     * Check if readable
     */
    public function isReadable(): bool
    {
        return $this->readable;
    }

    /**
     * Check if writable
     */
    public function isWritable(): bool
    {
        return $this->writable;
    }

    /**
     * Check if seekable
     */
    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    /**
     * Close stream
     */
    public function close(): void
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
        
        $this->resource = null;
        $this->readable = false;
        $this->writable = false;
        $this->seekable = false;
    }

    /**
     * Detach resource
     */
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;
        $this->readable = false;
        $this->writable = false;
        $this->seekable = false;
        
        return $resource;
    }

    /**
     * Stream file to output
     */
    public static function output(string $filePath, int $chunkSize = 8192): void
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }
        
        $handle = fopen($filePath, 'rb');
        
        while (!feof($handle)) {
            echo fread($handle, $chunkSize);
            flush();
        }
        
        fclose($handle);
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        try {
            return $this->toString();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->close();
    }
}