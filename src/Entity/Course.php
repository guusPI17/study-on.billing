<?php

namespace App\Entity;

use App\DTO\Course as CourseDto;
use App\Repository\CourseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CourseRepository::class)
 */
class Course
{
    private const TYPES_COURSE = [
        1 => 'rent',
        2 => 'free',
        3 => 'buy',
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $code;

    /**
     * @ORM\Column(type="smallint")
     */
    private $type;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $price;

    /**
     * @ORM\OneToMany(targetEntity=Transaction::class, mappedBy="course")
     */
    private $transactions;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function getStringType(): string
    {
        return self::TYPES_COURSE[$this->type];
    }

    public function getNumberType(?string $stringType): ?int
    {
        return array_search($stringType, self::TYPES_COURSE, true);
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public static function fromDtoNew(CourseDto $courseDto): self
    {
        $course = new self();
        $course->setCode($courseDto->getCode());
        $numberType = $course->getNumberType($courseDto->getType());
        $course->setType($numberType);
        $course->setPrice($courseDto->getPrice());
        $course->setTitle($courseDto->getTitle());

        return $course;
    }

    public function fromDtoEdit(CourseDto $courseDto): self
    {
        $this->price = $courseDto->getPrice();
        $this->title = $courseDto->getTitle();
        $this->code = $courseDto->getCode();
        $numberType = $this->getNumberType($courseDto->getType());
        $this->type = $numberType;

        return $this;
    }

    /**
     * @return Collection|Transaction[]
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransactions(Transaction $transactions): self
    {
        if (!$this->transactions->contains($transactions)) {
            $this->transactions[] = $transactions;
            $transactions->setCourse($this);
        }

        return $this;
    }

    public function removeTransactions(Transaction $transactions): self
    {
        if ($this->transactions->removeElement($transactions)) {
            // set the owning side to null (unless already changed)
            if ($transactions->getCourse() === $this) {
                $transactions->setCourse(null);
            }
        }

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }
}
