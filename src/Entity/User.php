<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="`user`")
 */
class User {
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $userName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $password;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $role;

    /**
     * @ORM\Column(type="boolean", options={"default": "false"})
     */
    private $isBlocked = false;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastActiveAt;

    /**
     * @ORM\OneToMany(targetEntity=ImportTransaction::class, mappedBy="targetUser")
     */
    private $importTransactions;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $deletedAt;

    public function __construct() {
        $this->importTransactions = new ArrayCollection();
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function setName(string $name): self {
        $this->name = $name;

        return $this;
    }

    public function getUserName(): ?string {
        return $this->userName;
    }

    public function setUserName(string $userName): self {
        $this->userName = $userName;

        return $this;
    }

    public function getEmail(): ?string {
        return $this->email;
    }

    public function setEmail(?string $email): self {
        $this->email = $email;

        return $this;
    }

    public function verifyPassword(string $password): bool {
        return password_verify($password, $this->password);
    }

    public function setPassword(string $password): self {
        $this->password = password_hash($password, PASSWORD_BCRYPT);

        return $this;
    }

    public function getCreatedAt(): ?\DateTime {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getRole(): ?string {
        return $this->role;
    }

    public function setRole(string $role): self {
        $this->role = $role;

        return $this;
    }

    public function getIsBlocked(): ?bool {
        return $this->isBlocked;
    }

    public function setIsBlocked(bool $isBlocked): self {
        $this->isBlocked = $isBlocked;

        return $this;
    }

    public function getLastActiveAt(): ?\DateTimeInterface {
        return $this->lastActiveAt;
    }

    public function setLastActiveAt(?\DateTimeInterface $lastActiveAt): self {
        $this->lastActiveAt = $lastActiveAt;

        return $this;
    }


    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * @return Collection|ImportTransaction[]
     */
    public function getImportTransactions(): Collection {
        return $this->importTransactions;
    }

    public function addImportTransaction(ImportTransaction $importTransaction): self {
        if (!$this->importTransactions->contains($importTransaction)) {
            $this->importTransactions[] = $importTransaction;
            $importTransaction->setTargetUser($this);
        }

        return $this;
    }

    public function removeImportTransaction(ImportTransaction $importTransaction): self {
        if ($this->importTransactions->removeElement($importTransaction)) {
            // set the owning side to null (unless already changed)
            if ($importTransaction->getTargetUser() === $this) {
                $importTransaction->setTargetUser(null);
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toJSON(): array {
        $json = array();
        $json['id'] = $this->getId();
        $json['name'] = $this->getName();
        $json['username'] = $this->getUserName();
        $json['email'] = $this->getEmail();
        $json['created_at'] = $this->getCreatedAt();
        $json['role'] = $this->getRole();
        $json['blocked'] = $this->getIsBlocked();
        $json['last_active_at'] = $this->getLastActiveAt();
        $json['deteled_at'] = $this->getDeletedAt();

        return $json;
    }

}