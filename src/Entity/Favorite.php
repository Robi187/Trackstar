<?php

namespace App\Entity;

use App\Repository\FavoriteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FavoriteRepository::class)]
class Favorite
{
    
    #[ORM\Id]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $fk_user = null;

    #[ORM\Id]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Content $fk_content = null;

    public function getFkUser(): ?User
    {
        return $this->fk_user;
    }

    public function setFkUser(?User $fk_user): static
    {
        $this->fk_user = $fk_user;

        return $this;
    }

    public function getFkContent(): ?Content
    {
        return $this->fk_content;
    }

    public function setFkContent(?Content $fk_content): static
    {
        $this->fk_content = $fk_content;

        return $this;
    }
}
