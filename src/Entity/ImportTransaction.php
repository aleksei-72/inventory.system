<?php

namespace App\Entity;

use App\Repository\ImportTransactionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ImportTransactionRepository::class)
 */
class ImportTransaction
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $fileName;

    /**
     * @ORM\Column(type="integer")
     */
    private $execTime;

    /**
     * @ORM\Column(type="boolean", options={"default": "false"})
     */
    private $status;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateTime;

    /**
     * @ORM\Column(type="integer", options={"default": "0"})
     */
    private $countItems;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="importTransactions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $targetUser;

    /**
     * @ORM\Column(type="string", length=1024, nullable=true)
     */
    private $description;

    public function getId(): ?int {
        return $this->id;
    }

    public function getFileName(): ?string {
        return $this->fileName;
    }

    public function setFileName(string $fileName): self {
        $this->fileName = $fileName;

        return $this;
    }

    public function getExecTime(): ?int {
        return $this->execTime;
    }

    public function setExecTime(int $execTime): self {
        $this->execTime = $execTime;

        return $this;
    }

    public function getStatus(): ?bool {
        return $this->status;
    }

    public function setStatus(bool $status): self {
        $this->status = $status;

        return $this;
    }

    public function getDateTime(): ?\DateTimeInterface {
        return $this->dateTime;
    }

    public function setDateTime(\DateTimeInterface $dateTime): self {
        $this->dateTime = $dateTime;

        return $this;
    }

    public function getCountItems(): ?int {
        return $this->countItems;
    }

    public function setCountItems(int $countItems): self {
        $this->countItems = $countItems;

        return $this;
    }

    public function getTargetUser(): ?User {
        return $this->targetUser;
    }

    public function setTargetUser(?User $targetUser): self {
        $this->targetUser = $targetUser;

        return $this;
    }

    public function getDescription(): ?string {
        return $this->description;
    }

    public function setDescription(?string $description): self {
        $this->description = $description;

        return $this;
    }

}
