<?php

declare(strict_types=1);

namespace Ludelix\PRT;

/**
 * UploadedFile - Represents a file uploaded through HTTP.
 */
class UploadedFile
{
    private string $path;
    private string $originalName;
    private string $mimeType;
    private int $size;
    private int $error;

    /**
     * Create a new UploadedFile instance.
     *
     * @param string $path
     * @param string $originalName
     * @param string $mimeType
     * @param int $size
     * @param int $error
     */
    public function __construct(string $path, string $originalName, string $mimeType, int $size, int $error = 0)
    {
        $this->path = $path;
        $this->originalName = $originalName;
        $this->mimeType = $mimeType;
        $this->size = $size;
        $this->error = $error;
    }

    /**
     * Get the absolute path to the uploaded file.
     *
     * @return string
     */
    public function getPathname(): string
    {
        return $this->path;
    }

    /**
     * Get the original file name as provided by the client.
     *
     * @return string
     */
    public function getClientOriginalName(): string
    {
        return $this->originalName;
    }

    /**
     * Get the original file extension.
     *
     * @return string
     */
    public function getClientOriginalExtension(): string
    {
        return pathinfo($this->originalName, PATHINFO_EXTENSION);
    }

    /**
     * Get the file MIME type.
     *
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Get the file size in bytes.
     *
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Determine if the file was uploaded successfully.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK && is_uploaded_file($this->path);
    }

    /**
     * Get the upload error code.
     *
     * @return int
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * Move the uploaded file to a new location.
     *
     * @param string $directory
     * @param string $name
     * @return bool
     */
    public function move(string $directory, string $name): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        $target = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;

        if (move_uploaded_file($this->path, $target)) {
            $this->path = $target;
            return true;
        }

        return false;
    }
}
