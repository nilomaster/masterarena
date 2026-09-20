<?php
// Master Arena SaaS - Test Mode Early Exit Exception
// Comments strictly in ASCII only.

namespace App\Core;

use RuntimeException;

class EarlyExitException extends RuntimeException
{
    private int $statusCode;
    private array $data;

    public function __construct(int $statusCode, array $data)
    {
        parent::__construct("Early exit with HTTP {$statusCode}");
        $this->statusCode = $statusCode;
        $this->data = $data;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
