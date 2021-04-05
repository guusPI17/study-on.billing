<?php

namespace App\DTO;

use JMS\Serializer\Annotation as Serializer;

class Transaction
{
    /**
     * @Serializer\Type("int")
     */
    private $id;

    /**
     * @Serializer\Type("string")
     */
    private $createdAt;

    /**
     * @Serializer\Type("string")
     */
    private $type;

    /**
     * @Serializer\Type("string")
     */
    private $courseCode;

    /**
     * @Serializer\Type("float")
     */
    private $amount;

    public function __construct(int $id, string $createdAt, string $type, float $amount, ?string $courseCode)
    {
        $this->id = $id;
        $this->createdAt = $createdAt;
        $this->type = $type;
        $this->amount = $amount;
        $this->courseCode = $courseCode;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getCourseCode(): ?string
    {
        return $this->courseCode;
    }

    public function setCourseCode(?string $courseCode): void
    {
        $this->courseCode = $courseCode;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }
}
