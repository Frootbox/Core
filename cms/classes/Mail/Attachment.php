<?php
/**
 *
 */

namespace Frootbox\Mail;

class Attachment
{
    protected $name;
    protected $path;

    /**
     *
     */
    public function __construct(string $path, string $name = null)
    {
        $this->path = $path;

        if ($name === null) {
            $this->name = basename($this->path);
        }
        else {
            $this->name = $name;
        }
    }

    /**
     *
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     *
     */
    public function getPath(): string
    {
        return $this->path;
    }
}
