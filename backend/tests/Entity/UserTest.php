<?php

namespace App\Tests\Entity;

use App\Entity\Organization;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testUserCreation(): void
    {
        $this->assertNull($this->user->getId());
        $this->assertNull($this->user->getCreatedAt());
        // createdAt is set by Doctrine PrePersist event during persistence
        $this->assertEquals(['ROLE_USER'], $this->user->getRoles());
        $this->assertTrue($this->user->isActive());
    }

    public function testUserCreatedAtIsSetBeforePersist(): void
    {
        $this->user->setCreatedAtValue();
        $this->assertInstanceOf(\DateTimeInterface::class, $this->user->getCreatedAt());
    }

    public function testEmailGetterAndSetter(): void
    {
        $email = 'test@example.com';
        $this->user->setEmail($email);
        
        $this->assertEquals($email, $this->user->getEmail());
        $this->assertEquals($email, $this->user->getUserIdentifier());
    }

    public function testPasswordGetterAndSetter(): void
    {
        $password = 'hashedPassword123';
        $this->user->setPassword($password);
        
        $this->assertEquals($password, $this->user->getPassword());
    }

    public function testFirstnameGetterAndSetter(): void
    {
        $firstname = 'John';
        $this->user->setFirstname($firstname);
        
        $this->assertEquals($firstname, $this->user->getFirstname());
    }

    public function testLastnameGetterAndSetter(): void
    {
        $lastname = 'Doe';
        $this->user->setLastname($lastname);
        
        $this->assertEquals($lastname, $this->user->getLastname());
    }

    public function testFullNameGetter(): void
    {
        $this->user->setFirstname('John');
        $this->user->setLastname('Doe');
        
        $this->assertEquals('John Doe', $this->user->getFullName());
    }

    public function testRolesGetterAndSetter(): void
    {
        $roles = ['ROLE_ADMIN', 'ROLE_USER'];
        $this->user->setRoles($roles);
        
        $result = $this->user->getRoles();
        $this->assertContains('ROLE_ADMIN', $result);
        $this->assertContains('ROLE_USER', $result);
        $this->assertCount(2, array_unique($result));
    }

    public function testRolesAlwaysContainRoleUser(): void
    {
        $this->user->setRoles(['ROLE_ADMIN']);
        
        $roles = $this->user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
    }

    public function testIsActiveGetterAndSetter(): void
    {
        $this->assertTrue($this->user->isActive());
        
        $this->user->setIsActive(false);
        $this->assertFalse($this->user->isActive());
        
        $this->user->setIsActive(true);
        $this->assertTrue($this->user->isActive());
    }

    public function testCreatedAtGetterAndSetter(): void
    {
        $date = new \DateTime('2025-01-01 12:00:00');
        $this->user->setCreatedAt($date);
        
        $this->assertEquals($date, $this->user->getCreatedAt());
    }

    public function testUpdatedAtGetterAndSetter(): void
    {
        $this->assertNull($this->user->getUpdatedAt());
        
        $date = new \DateTime();
        $this->user->setUpdatedAt($date);
        
        $this->assertEquals($date, $this->user->getUpdatedAt());
    }

    public function testLastLoginAtGetterAndSetter(): void
    {
        $this->assertNull($this->user->getLastLoginAt());
        
        $date = new \DateTime();
        $this->user->setLastLoginAt($date);
        
        $this->assertEquals($date, $this->user->getLastLoginAt());
    }

    public function testOrganizationGetterAndSetter(): void
    {
        $this->assertNull($this->user->getOrganization());
        
        $organization = new Organization();
        $organization->setName('Test Org');
        $this->user->setOrganization($organization);
        
        $this->assertInstanceOf(Organization::class, $this->user->getOrganization());
        $this->assertEquals('Test Org', $this->user->getOrganization()->getName());
    }

    public function testEraseCredentials(): void
    {
        // This method should not throw any exception
        $this->user->eraseCredentials();
        $this->assertTrue(true);
    }
}
