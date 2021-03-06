<?php

namespace App\Entity;

use App\Repository\ProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProfileRepository::class)
 */
class Profile {
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
     * @ORM\OneToMany(targetEntity=Item::class, mappedBy="profile")
     */
    private $items;

    public function __construct() {
        $this->items = new ArrayCollection();
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

    /**
     * @return Collection|Item[]
     */
    public function getItems(): Collection {
        return $this->items;
    }

    public function addItem(Item $item): self {
        if (!$this->items->contains($item)) {
            $this->items[] = $item;
            $item->setProfile($this);
        }

        return $this;
    }

    public function removeItem(Item $item): self {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getProfile() === $this) {
                $item->setProfile(null);
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

        return $json;
    }
}
