<?php

namespace Ludelix\Session;

use SessionHandlerInterface;

class FileSessionHandler implements SessionHandlerInterface
{
    /**
     * The path where sessions should be stored.
     *
     * @var string
     */
    protected $path;

    /**
     * The number of minutes the session should be valid.
     *
     * @var int
     */
    protected $minutes;

    /**
     * Create a new file session handler instance.
     *
     * @param  string  $path
     * @param  int  $minutes
     * @return void
     */
    public function __construct($path, $minutes)
    {
        $this->path = $path;
        $this->minutes = $minutes;
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName): bool
    {
        // Ensure the session directory exists
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
        
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function read($sessionId)
    {
        if (file_exists($this->path.'/'.$sessionId)) {
            if (filemtime($this->path.'/'.$sessionId) >= (time() - $this->minutes * 60)) {
                return file_get_contents($this->path.'/'.$sessionId) ?: '';
            }
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function write($sessionId, $data)
    {
        // Ensure the session directory exists
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
        
        $logPath = dirname($this->path) . '/logs/session_debug.log';
        $logMessage = sprintf("Session Write: Path=[%s], SessionID=[%s], Data=[%s]".PHP_EOL, $this->path, $sessionId, $data);
        file_put_contents($logPath, $logMessage, FILE_APPEND);

        $result = file_put_contents($this->path.'/'.$sessionId, $data, LOCK_EX);
        
        return $result !== false;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function destroy($sessionId)
    {
        if (file_exists($this->path.'/'.$sessionId)) {
            unlink($this->path.'/'.$sessionId);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    #[\ReturnTypeWillChange]
    public function gc($lifetime)
    {
        foreach (glob($this->path.'/*') as $file) {
            if (filemtime($file) < time() - $lifetime) {
                unlink($file);
            }
        }

        return true;
    }
}