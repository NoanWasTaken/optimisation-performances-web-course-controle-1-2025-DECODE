<?php

namespace App\Entity;

use App\Repository\GalaxyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GalaxyRepository::class)]
class Galaxy
{
    #[ORM\Id]
    #[ORM\Column]
    private ?string $id = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(nullable: true)]
    private ?int $sort = null;

    #[ORM\Column(length: 255)]
    private ?string $user_created = null;

    #[ORM\Column(length: 255)]
    private ?string $date_created = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $user_updated = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $date_updated = null;

    #[ORM\ManyToOne(targetEntity: Modeles::class)]
    #[ORM\JoinColumn(name: 'modele', referencedColumnName: 'id')]
    private ?Modeles $modele = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 65535)]
    private ?string $description = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSort(): ?int
    {
        return $this->sort;
    }

    public function setSort(?int $sort): static
    {
        $this->sort = $sort;

        return $this;
    }

    public function getUserCreated(): ?string
    {
        return $this->user_created;
    }

    public function setUserCreated(string $user_created): static
    {
        $this->user_created = $user_created;

        return $this;
    }

    public function getDateCreated(): ?string
    {
        return $this->date_created;
    }

    public function setDateCreated(string $date_created): static
    {
        $this->date_created = $date_created;

        return $this;
    }

    public function getUserUpdated(): ?string
    {
        return $this->user_updated;
    }

    public function setUserUpdated(string $user_updated): static
    {
        $this->user_updated = $user_updated;

        return $this;
    }

    public function getDateUpdated(): ?string
    {
        return $this->date_updated;
    }

    public function setDateUpdated(string $date_updated): static
    {
        $this->date_updated = $date_updated;

        return $this;
    }

    public function getModele(): ?Modeles
    {
        return $this->modele;
    }

    public function setModele(?Modeles $modele): static
    {
        $this->modele = $modele;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
