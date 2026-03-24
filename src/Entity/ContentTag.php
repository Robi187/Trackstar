<?php

namespace App\Entity;

use App\Repository\ContentTagRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContentTagRepository::class)]
class ContentTag
{    

    #[ORM\Id]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Content $fk_content = null;

    #[ORM\Id]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tag $fk_tag = null;

    public function getFkContent(): ?Content
    {
        return $this->fk_content;
    }

    public function setFkContent(?Content $fk_content): static
    {
        $this->fk_content = $fk_content;

        return $this;
    }

    public function getFkTag(): ?Tag
    {
        return $this->fk_tag;
    }

    public function setFkTag(?Tag $fk_tag): static
    {
        $this->fk_tag = $fk_tag;

        return $this;
    }
}
