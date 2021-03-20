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
     * @Serializer\Type("array")
     */
    private $roles;

    public function __construct(string $token, array $roles)
    {
        $this->token = $token;
        $this->roles = $roles;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }
}
