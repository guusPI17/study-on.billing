<?php

namespace App\DTO;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

class User
{
    /**
     * @Serializer\Type("string")
     * @Assert\Email(message="Данный адрес {{ value }} написан неверно")
     * @Assert\NotNull(
     *     message="Почта не может отсутствовать"
     * )
     */
    private $username;

    /**
     * @Serializer\Type("string")
     * @Assert\Length(
     *     min="6",
     *     minMessage="Длина пароля должна быть минимум {{ limit }} символов"
     * )
     * @Assert\NotNull(
     *     message="Пароль не может отсутствовать"
     * )
     */
    private $password;

    /**
     * @Serializer\Type("float")
     */
    private $balance;

    /**
     * @Serializer\Type("array")
     */
    private $roles;

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function setBalance(float $balance): void
    {
        $this->balance = $balance;
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
