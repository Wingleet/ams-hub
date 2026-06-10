<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Repository\ApplicationRepository;
use App\Trait\TimestampableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ApplicationRepository::class)]
#[ORM\Table(name: 'application')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['application:read']],
            security: "is_granted('IS_AUTHENTICATED_FULLY')"
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['application:read']],
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            paginationEnabled: false
        ),
        new Post(
            denormalizationContext: ['groups' => ['application:write']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Put(
            denormalizationContext: ['groups' => ['application:write']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        ),
    ],
    normalizationContext: ['groups' => ['application:read']],
    denormalizationContext: ['groups' => ['application:write']]
)]
class Application
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['application:read', 'subscription:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['application:read', 'application:write', 'subscription:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['application:read', 'application:write', 'subscription:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['application:read', 'application:write', 'subscription:read'])]
    private ?string $url = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(
    message: 'The icon URL must be a valid URL.'
    )]
    #[Groups(['application:read', 'application:write', 'subscription:read'])]
    private ?string $iconUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: 'The database name cannot be longer than {{ limit }} characters.'
    )]
    #[Groups(['application:read', 'application:write'])]
    private ?string $databaseName = null;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => true])]
    #[Groups(['application:read', 'application:write'])]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['application:read'])]
    private ?\DateTimeInterface $deletedAt = null;

    #[ORM\Column(length: 64, nullable: true)]
    #[Groups(['application:write'])]
    private ?string $ssoSecret = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['application:read', 'application:write'])]
    private ?string $ssoCallbackUrl = null;

    #[ORM\OneToMany(targetEntity: Subscription::class, mappedBy: 'application', cascade: ['persist', 'remove'])]
    private Collection $subscriptions;

    public function __construct()
    {
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

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

    public function getDatabaseName(): ?string
    {
        return $this->databaseName;
    }

    public function setDatabaseName(?string $databaseName): static
    {
        $this->databaseName = $databaseName;

        return $this;
    }

    #[Groups(['application:read', 'application:write'])]
    #[SerializedName('isActive')]
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
            $subscription->setApplication($this);
        }

        return $this;
    }

    public function removeSubscription(Subscription $subscription): static
    {
        if ($this->subscriptions->removeElement($subscription)) {
            // set the owning side to null (unless already changed)
            if ($subscription->getApplication() === $this) {
                $subscription->setApplication(null);
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

    public function getSsoSecret(): ?string
    {
        return $this->ssoSecret;
    }

    public function setSsoSecret(?string $ssoSecret): static
    {
        $this->ssoSecret = $ssoSecret;
        return $this;
    }

    public function getSsoCallbackUrl(): ?string
    {
        return $this->ssoCallbackUrl;
    }

    public function setSsoCallbackUrl(?string $ssoCallbackUrl): static
    {
        $this->ssoCallbackUrl = $ssoCallbackUrl;
        return $this;
    }
}
