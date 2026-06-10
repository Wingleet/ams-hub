<?php

namespace App\Tests\Entity;

use App\Entity\Application;
use App\Entity\Organization;
use App\Entity\Subscription;
use PHPUnit\Framework\TestCase;

class SubscriptionTest extends TestCase
{
    private Subscription $subscription;

    protected function setUp(): void
    {
        $this->subscription = new Subscription();
    }

    public function testSubscriptionCreation(): void
    {
        $this->assertNull($this->subscription->getId());
        $this->assertNull($this->subscription->getCreatedAt());
        // createdAt is set by Doctrine PrePersist event during persistence
    }

    public function testSubscriptionCreatedAtIsSetBeforePersist(): void
    {
        $this->subscription->setCreatedAtValue();
        $this->assertInstanceOf(\DateTimeInterface::class, $this->subscription->getCreatedAt());
    }

    public function testOrganizationGetterAndSetter(): void
    {
        $this->assertNull($this->subscription->getOrganization());
        
        $organization = new Organization();
        $organization->setName('Test Org');
        $this->subscription->setOrganization($organization);
        
        $this->assertInstanceOf(Organization::class, $this->subscription->getOrganization());
        $this->assertEquals('Test Org', $this->subscription->getOrganization()->getName());
    }

    public function testApplicationGetterAndSetter(): void
    {
        $this->assertNull($this->subscription->getApplication());
        
        $application = new Application();
        $application->setName('Test App');
        $this->subscription->setApplication($application);
        
        $this->assertInstanceOf(Application::class, $this->subscription->getApplication());
        $this->assertEquals('Test App', $this->subscription->getApplication()->getName());
    }

    public function testIsActiveGetterAndSetter(): void
    {
        $this->assertTrue($this->subscription->isActive());
        
        $this->subscription->setIsActive(false);
        
        $this->assertFalse($this->subscription->isActive());
        
        $this->subscription->setIsActive(true);
        
        $this->assertTrue($this->subscription->isActive());
    }

    public function testEndsAtGetterAndSetter(): void
    {
        $this->assertNull($this->subscription->getEndsAt());
        
        $date = new \DateTime('+1 year');
        $this->subscription->setEndsAt($date);
        
        $this->assertEquals($date, $this->subscription->getEndsAt());
    }

    public function testCreatedAtGetterAndSetter(): void
    {
        $date = new \DateTime('2025-01-01 12:00:00');
        $this->subscription->setCreatedAt($date);
        
        $this->assertEquals($date, $this->subscription->getCreatedAt());
    }

    public function testUpdatedAtGetterAndSetter(): void
    {
        $this->assertNull($this->subscription->getUpdatedAt());
        
        $date = new \DateTime();
        $this->subscription->setUpdatedAt($date);
        
        $this->assertEquals($date, $this->subscription->getUpdatedAt());
    }
}
