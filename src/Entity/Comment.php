<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $text = null;

    #[ORM\Column]
    private ?\DateTime $created_at = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $fk_user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Content $fk_content = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Comment $fk_parent_comment = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'fk_parent_comment', cascade: ['remove'])]
    #[ORM\OrderBy(['created_at' => 'ASC'])]
    private Collection $replies;

    public function __construct()
    {
        $this->replies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

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

    public function getFkParentComment(): ?Comment
    {
        return $this->fk_parent_comment;
    }

    public function setFkParentComment(?Comment $fk_parent_comment): static
    {
        $this->fk_parent_comment = $fk_parent_comment;

        return $this;
    }

    public function getReplies(): Collection
    {
        return $this->replies;
    }
}
