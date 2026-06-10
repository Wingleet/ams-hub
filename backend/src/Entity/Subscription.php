<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Repository\SubscriptionRepository;
use App\Trait\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
#[ORM\Table(name: 'subscription')]
#[ORM\HasLifecycleCallbacks]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
#[ApiResource(
    shortName: 'Subscription',
    operations: [
        new Get(
            uriTemplate: '/subscriptions/{id}',
            requirements: ['id' => '\d+'],
            normalizationContext: ['groups' => ['subscription:read']]
        ),
        new GetCollection(
            uriTemplate: '/subscriptions',
            normalizationContext: ['groups' => ['subscription:read']]
        ),
        new Post(
            uriTemplate: '/subscriptions',
            denormalizationContext: ['groups' => ['subscription:write']]
        ),
        new Delete(
            uriTemplate: '/subscriptions/{id}',
            requirements: ['id' => '\d+']
        ),
    ],
    formats: ['json' => ['application/json']]
)]
#[ApiFilter(SearchFilter::class, properties: ['organization' => 'exact', 'application' => 'exact', 'isActive' => 'exact'])]
class Subscription
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['subscription:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[ApiProperty(push: true)]
    #[Groups(['subscription:read', 'subscription:write'])]
    private ?Organization $organization = null;

    #[ORM\ManyToOne(targetEntity: Application::class, inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(nullable: false)]
    #[ApiProperty(push: true)]
    #[Groups(['subscription:read', 'subscription:write'])]
    private ?Application $application = null;

    #[ORM\Column(type: 'boolean')]
    #[Assert\NotNull]
    #[Groups(['subscription:read', 'subscription:write'])]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['subscription:read'])]
    private ?\DateTimeInterface $endsAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): static
    {
        $this->organization = $organization;

        return $this;
    }

    public function getApplication(): ?Application
    {
        return $this->application;
    }

    public function setApplication(?Application $application): static
    {
        $this->application = $application;

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

    public function getEndsAt(): ?\DateTimeInterface
    {
        return $this->endsAt;
    }

    public function setEndsAt(?\DateTimeInterface $endsAt): static
    {
        $this->endsAt = $endsAt;

        return $this;
    }

    public function __toString(): string
    {
        $org = $this->organization?->getName() ?? 'Unknown';
        $app = $this->application?->getName() ?? 'Unknown';
        $status = $this->isActive ? 'Active' : 'Inactive';
        return "$org - $app ($status)";
    }
}
