<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\PostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ApiResource(
    denormalizationContext: ['groups' => ['create_post']],
    normalizationContext: ['groups' => ['read_post'], 'skip_null_values' => false],
)]
class Post extends HasOwner
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['read_post', 'read_user'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['create_post', 'read_post', 'read_user'])]
    private ?string $title;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['create_post', 'read_post', 'read_user'])]
    private ?string $text;

    #[ORM\OneToMany(mappedBy: 'post', targetEntity: Comment::class, orphanRemoval: true)]
    #[Groups(['read_post'])]
    private Collection $comments;

    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['read_post'])]
    private ?User $owner = null;

    public function __construct(string $title, string $text)
    {
        $this->title = $title;
        $this->text = $text;
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $owner): self
    {
        if (!$this->comments->contains($owner)) {
            $this->comments->add($owner);
            $owner->setPost($this);
        }

        return $this;
    }

    public function removeComment(Comment $owner): self
    {
        if ($this->comments->removeElement($owner)) {
            if ($owner->getPost() === $this) {
                $owner->setPost(null);
            }
        }

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): void
    {
        $this->owner = $owner;
    }
}
