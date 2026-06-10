<?php

namespace App\Tests\Entity;

use App\Entity\Organization;
use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class OrganizationTest extends TestCase
{
    private Organization $organization;

    protected function setUp(): void
    {
        $this->organization = new Organization();
    }

    public function testOrganizationCreation(): void
    {
        $this->assertNull($this->organization->getId());
        $this->assertNull($this->organization->getCreatedAt());
        // createdAt is set by Doctrine PrePersist event during persistence
        $this->assertTrue($this->organization->isActive());
        $this->assertInstanceOf(ArrayCollection::class, $this->organization->getUsers());
        $this->assertInstanceOf(ArrayCollection::class, $this->organization->getSubscriptions());
        $this->assertCount(0, $this->organization->getUsers());
        $this->assertCount(0, $this->organization->getSubscriptions());
    }

    public function testOrganizationCreatedAtIsSetBeforePersist(): void
    {
        $this->organization->setCreatedAtValue();
        $this->assertInstanceOf(\DateTimeInterface::class, $this->organization->getCreatedAt());
    }

    public function testNameGetterAndSetter(): void
    {
        $name = 'Test Organization';
        $this->organization->setName($name);
        
        $this->assertEquals($name, $this->organization->getName());
    }

    public function testCreatedAtGetterAndSetter(): void
    {
        $date = new \DateTime('2025-01-01 12:00:00');
        $this->organization->setCreatedAt($date);
        
        $this->assertEquals($date, $this->organization->getCreatedAt());
    }

    public function testUpdatedAtGetterAndSetter(): void
    {
        $this->assertNull($this->organization->getUpdatedAt());
        
        $date = new \DateTime();
        $this->organization->setUpdatedAt($date);
        
        $this->assertEquals($date, $this->organization->getUpdatedAt());
    }

    public function testDeletedAtGetterAndSetter(): void
    {
        $this->assertNull($this->organization->getDeletedAt());
        
        $date = new \DateTime();
        $this->organization->setDeletedAt($date);
        
        $this->assertEquals($date, $this->organization->getDeletedAt());
    }

    public function testIconUrlGetterAndSetter(): void
    {
        $this->assertNull($this->organization->getIconUrl());
        
        $iconUrl = 'https://example.com/icon.png';
        $this->organization->setIconUrl($iconUrl);
        
        $this->assertEquals($iconUrl, $this->organization->getIconUrl());
    }

    public function testIsActiveGetterAndSetter(): void
    {
        $this->assertTrue($this->organization->isActive());
        
        $this->organization->setIsActive(false);
        $this->assertFalse($this->organization->isActive());
        
        $this->organization->setIsActive(true);
        $this->assertTrue($this->organization->isActive());
    }

    public function testAddUser(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        
        $this->organization->addUser($user);
        
        $this->assertCount(1, $this->organization->getUsers());
        $this->assertTrue($this->organization->getUsers()->contains($user));
        $this->assertEquals($this->organization, $user->getOrganization());
    }

    public function testAddUserOnlyOnce(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        
        $this->organization->addUser($user);
        $this->organization->addUser($user);
        
        $this->assertCount(1, $this->organization->getUsers());
    }

    public function testRemoveUser(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        
        $this->organization->addUser($user);
        $this->assertCount(1, $this->organization->getUsers());
        
        $this->organization->removeUser($user);
        
        $this->assertCount(0, $this->organization->getUsers());
        $this->assertNull($user->getOrganization());
    }

    public function testRemoveUserNotInCollection(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        
        // Should not throw exception
        $this->organization->removeUser($user);
        $this->assertCount(0, $this->organization->getUsers());
    }

    public function testAddSubscription(): void
    {
        $subscription = new Subscription();
        
        $this->organization->addSubscription($subscription);
        
        $this->assertCount(1, $this->organization->getSubscriptions());
        $this->assertTrue($this->organization->getSubscriptions()->contains($subscription));
        $this->assertEquals($this->organization, $subscription->getOrganization());
    }

    public function testAddSubscriptionOnlyOnce(): void
    {
        $subscription = new Subscription();
        
        $this->organization->addSubscription($subscription);
        $this->organization->addSubscription($subscription);
        
        $this->assertCount(1, $this->organization->getSubscriptions());
    }

    public function testRemoveSubscription(): void
    {
        $subscription = new Subscription();
        
        $this->organization->addSubscription($subscription);
        $this->assertCount(1, $this->organization->getSubscriptions());
        
        $this->organization->removeSubscription($subscription);
        
        $this->assertCount(0, $this->organization->getSubscriptions());
        $this->assertNull($subscription->getOrganization());
    }
}
