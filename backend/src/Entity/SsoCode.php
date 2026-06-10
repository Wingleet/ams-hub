<?php

namespace App\Entity;

use App\Repository\SsoCodeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SsoCodeRepository::class)]
#[ORM\Table(name: 'sso_code')]
#[ORM\Index(columns: ['code'], name: 'idx_sso_code')]
#[ORM\Index(columns: ['expires_at'], name: 'idx_sso_expires_at')]
class SsoCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $code = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Application::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Application $application = null;

    #[ORM\Column(type: 'text')]
    private ?string $jwtToken = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $expiresAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $usedAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
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

    public function getJwtToken(): ?string
    {
        return $this->jwtToken;
    }

    public function setJwtToken(string $jwtToken): static
    {
        $this->jwtToken = $jwtToken;
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeInterface $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getUsedAt(): ?\DateTimeInterface
    {
        return $this->usedAt;
    }

    public function setUsedAt(?\DateTimeInterface $usedAt): static
    {
        $this->usedAt = $usedAt;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTime();
    }

    public function isUsed(): bool
    {
        return $this->usedAt !== null;
    }
}
