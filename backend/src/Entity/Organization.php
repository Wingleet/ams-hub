<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Repository\OrganizationRepository;
use App\Trait\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrganizationRepository::class)]
#[ORM\Table(name: 'organization')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['organization:read']],
            security: "is_granted('IS_AUTHENTICATED_FULLY')"
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['organization:read']],
            security: "is_granted('IS_AUTHENTICATED_FULLY')"
        ),
        new Post(
            denormalizationContext: ['groups' => ['organization:write']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Put(
            denormalizationContext: ['groups' => ['organization:write']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        ),
    ],
    normalizationContext: ['groups' => ['organization:read']],
    denormalizationContext: ['groups' => ['organization:write']]
)]
class Organization
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['organization:read', 'user:read', 'subscription:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['organization:read', 'organization:write', 'user:read', 'subscription:read'])]
    private ?string $name = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['organization:read'])]
    private ?\DateTimeInterface $deletedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['organization:read', 'organization:write'])]
    private ?string $iconUrl = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['organization:read', 'organization:write'])]
    private bool $isActive = true;

    #[ORM\Column(type: 'integer', nullable: true, unique: true)]
    private ?int $amsCompanyId = null;

    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'organization', cascade: ['persist', 'remove'])]
    private Collection $users;

    #[ORM\OneToMany(targetEntity: Subscription::class, mappedBy: 'organization', cascade: ['persist', 'remove'])]
    private Collection $subscriptions;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
    public function __toString(): string
    {
    return $this->name ?? '';
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getIconUrl(): ?string
    {
        return $this->iconUrl;
    }

    public function setIconUrl(?string $iconUrl): static
    {
        $this->iconUrl = $iconUrl;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setOrganization($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            if ($user->getOrganization() === $this) {
                $user->setOrganization(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addSubscription(Subscription $subscription): static
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions->add($subscription);
            $subscription->setOrganization($this);
        }

        return $this;
    }

    public function removeSubscription(Subscription $subscription): static
    {
        if ($this->subscriptions->removeElement($subscription)) {
            // set the owning side to null (unless already changed)
            if ($subscription->getOrganization() === $this) {
                $subscription->setOrganization(null);
            }
        }

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): static
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function getAmsCompanyId(): ?int
    {
        return $this->amsCompanyId;
    }

    public function setAmsCompanyId(?int $amsCompanyId): static
    {
        $this->amsCompanyId = $amsCompanyId;
        return $this;
    }
}
