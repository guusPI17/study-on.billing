<?php

namespace App\DTO;

use JMS\Serializer\Annotation as Serializer;

class Response
{
    /**
     * @Serializer\Type("array")
     */
    private $error;

    /**
     * @Serializer\Type("int")
     */
    private $code;

    public function __construct(array $error, int $code)
    {
        $this->error = $error;
        $this->code = $code;
    }

    public function getError(): array
    {
        return $this->error;
    }

    public function setError($error): void
    {
        $this->error = $error;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function setCode($code): void
    {
        $this->code = $code;
    }
}
