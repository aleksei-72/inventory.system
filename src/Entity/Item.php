<?php

namespace App\Entity;

use App\Repository\ItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ItemRepository::class)
 */
class Item {
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=1024)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=1024, nullable=true)
     */
    private $comment;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $count;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="items")
     */
    private $category;

    /**
     * @ORM\ManyToMany(targetEntity=Room::class, inversedBy="items")
     */
    private $room;

    /**
     * @ORM\Column(type="string")
     */
    private $number;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\ManyToOne(targetEntity=Profile::class, inversedBy="items")
     */
    private $profile;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $price;


    public function __construct() {
        $this->room = new ArrayCollection();
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getTitle(): ?string {
        return $this->title;
    }

    public function setTitle(string $title): self {
        $this->title = $title;

        return $this;
    }

    public function getComment(): ?string {
        return $this->comment;
    }

    public function setComment(?string $comment): self {
        $this->comment = $comment;

        return $this;
    }

    public function getCount(): ?string {
        return $this->count;
    }

    public function setCount(string $count): self {
        $this->count = $count;

        return $this;
    }

    public function getCategory(): ?Category {
        return $this->category;
    }

    public function setCategory(?Category $category): self {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection|Room[]
     */
    public function getRoom(): Collection {
        return $this->room;
    }

    public function addRoom(Room $room): self {
        if (!$this->room->contains($room)) {
            $this->room[] = $room;
        }

        return $this;
    }

    public function removeRoom(Room $room): self {
        $this->room->removeElement($room);

        return $this;
    }

    public function removeAllRoom(): self {
        $rooms = $this->getRoom();

        foreach ($rooms as $room) {
            $this->room->removeElement($room);
        }

        return $this;
    }

    public function getNumber(): ?string {
        return $this->number;
    }

    public function setNumber(string $number): self {
        $this->number = $number;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): self {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getProfile(): ?Profile {
        return $this->profile;
    }

    public function setProfile(?Profile $profile): self {
        $this->profile = $profile;

        return $this;
    }

    public function getPrice(): ?float {
        return $this->price;
    }

    public function setPrice(?float $price): self {
        $this->price = $price;

        return $this;
    }


    /**
     * @return array
     */
    public function toJSON(): array {

        $json = array();
        $json['title'] = $this->getTitle();
        $json['comment'] = $this->getComment();
        $json['count'] = $this->getCount();
        $json['number'] = $this->getNumber();
        $json['id'] = $this->getId();
        $json['created_at'] = $this->getCreatedAt();
        $json['updated_at'] = $this->getUpdatedAt();
        $json['price'] = $this->getPrice();

        $itemCategory = $this->getCategory();

        if ($itemCategory) {
            $json['category'] = $itemCategory->toJSON();
        } else {
            $json['category'] = null;
        }

        $itemProfile = $this->getProfile();

        if ($itemProfile) {
            $json['profile'] = $itemProfile->toJSON();
        } else {
            $json['profile'] = null;
        }

        $itemRooms = $this->getRoom();

        $json['rooms'] = array();

        if (count($itemRooms) !== 0) {

            foreach ($itemRooms as $room) {
                array_push($json['rooms'], $room->toJSON());
            }

        }

        return $json;
    }

}