<?php

namespace App\DTO;

use JMS\Serializer\Annotation as Serializer;

class Token
{
    /**
     * @Serializer\Type("string")
     */
    private $token;

    /**
     * @Serializer\Type("string")
     */
    private $refreshToken;

    /**
     * @Serializer\Type("array")
     */
    private $roles;

    /**
     * @Serializer\Type("int")
     */
    private $code;

    /**
     * @Serializer\Type("string")
     */
    private $message;

    public function __construct(string $token, string $refreshToken, array $roles)
    {
        $this->token = $token;
        $this->refreshToken = $refreshToken;
        $this->roles = $roles;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken): void
    {
        $this->refreshToken = $refreshToken;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function setCode(int $code): void
    {
        $this->code = $code;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }
}
