<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use App\Trait\TimestampableTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['user:read']],
            security: "is_granted('IS_AUTHENTICATED_FULLY')"
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['user:read']],
            security: "is_granted('IS_AUTHENTICATED_FULLY')"
        ),
        new Post(
            denormalizationContext: ['groups' => ['user:write']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Put(
            denormalizationContext: ['groups' => ['user:write']],
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        ),
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['user:read', 'user:write'])]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $username = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private array $role = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['user:read', 'user:write'])]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['user:read', 'user:write'])]
    private ?string $lastname = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['user:read'])]
    private ?\DateTimeInterface $lastLoginAt = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['user:read', 'user:write'])]
    private bool $isActive = true;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['user:read'])]
    private bool $isAmsUser = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['user:read'])]
    private ?\DateTimeInterface $deletedAt = null;

    #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Organization $organization = null;

    public function __construct()
    {
        $this->role = ['ROLE_USER'];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->role;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->role = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    public function __toString(): string
    {
        return $this->getFullName() . ' (' . $this->email . ')';
    }

    public function getLastLoginAt(): ?\DateTimeInterface
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeInterface $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;

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

    public function isAmsUser(): bool
    {
        return $this->isAmsUser;
    }

    public function setIsAmsUser(bool $isAmsUser): static
    {
        $this->isAmsUser = $isAmsUser;

        return $this;
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

    /**
     * Add a role to the user
     */
    public function addRole(UserRole|string $role): static
    {
        $roleValue = $role instanceof UserRole ? $role->value : $role;
        
        if (!in_array($roleValue, $this->role, true)) {
            $this->role[] = $roleValue;
        }

        return $this;
    }

    /**
     * Remove a role from the user
     */
    public function removeRole(UserRole|string $role): static
    {
        $roleValue = $role instanceof UserRole ? $role->value : $role;
        $this->role = array_values(array_diff($this->role, [$roleValue]));

        return $this;
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(UserRole|string $role): bool
    {
        $roleValue = $role instanceof UserRole ? $role->value : $role;
        return in_array($roleValue, $this->getRoles(), true);
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::ADMIN);
    }

    /**
     * Check if user is a regular user (not admin only)
     */
    public function isUser(): bool
    {
        return $this->hasRole(UserRole::USER);
    }

    /**
     * Set user as admin (adds ROLE_ADMIN)
     */
    public function setAsAdmin(bool $isAdmin = true): static
    {
        if ($isAdmin) {
            $this->addRole(UserRole::ADMIN);
        } else {
            $this->removeRole(UserRole::ADMIN);
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

    /**
     * Check if user is deleted (soft delete)
     */
    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }
}
