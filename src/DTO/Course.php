<?php


namespace App\DTO;

use JMS\Serializer\Annotation as Serializer;

class Course
{
    /**
     * @Serializer\Type("string")
     */
    private $code;

    /**
     * @Serializer\Type("string")
     */
    private $type;

    /**
     * @Serializer\Type("float")
     */
    private $price;

    public function __construct(string $code, string $type, float $price)
    {
        $this->code = $code;
        $this->type = $type;
        $this->price = $price;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getType(): string
    {
        return $this->code;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getPrice(): ?float
    {
        return $this->code;
    }

    public function setPrice(?float $price): void
    {
        $this->price = $price;
    }
}