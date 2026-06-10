<?php

namespace App\Tests\Entity;

use App\Entity\Application;
use App\Entity\Subscription;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    private Application $application;

    protected function setUp(): void
    {
        $this->application = new Application();
    }

    public function testApplicationCreation(): void
    {
        $this->assertNull($this->application->getId());
        $this->assertNull($this->application->getCreatedAt());
        // createdAt is set by Doctrine PrePersist event during persistence
        $this->assertTrue($this->application->isActive());
        $this->assertInstanceOf(ArrayCollection::class, $this->application->getSubscriptions());
        $this->assertCount(0, $this->application->getSubscriptions());
    }

    public function testApplicationCreatedAtIsSetBeforePersist(): void
    {
        $this->application->setCreatedAtValue();
        $this->assertInstanceOf(\DateTimeInterface::class, $this->application->getCreatedAt());
    }

    public function testNameGetterAndSetter(): void
    {
        $name = 'Test Application';
        $this->application->setName($name);
        
        $this->assertEquals($name, $this->application->getName());
    }

    public function testDescriptionGetterAndSetter(): void
    {
        $this->assertNull($this->application->getDescription());
        
        $description = 'Test Description';
        $this->application->setDescription($description);
        
        $this->assertEquals($description, $this->application->getDescription());
    }

    public function testUrlGetterAndSetter(): void
    {
        $this->assertNull($this->application->getUrl());
        
        $url = 'https://example.com';
        $this->application->setUrl($url);
        
        $this->assertEquals($url, $this->application->getUrl());
    }

    public function testIconUrlGetterAndSetter(): void
    {
        $this->assertNull($this->application->getIconUrl());
        
        $iconUrl = 'https://example.com/icon.png';
        $this->application->setIconUrl($iconUrl);
        
        $this->assertEquals($iconUrl, $this->application->getIconUrl());
    }

    public function testDatabaseNameGetterAndSetter(): void
    {
        $this->assertNull($this->application->getDatabaseName());
        
        $databaseName = 'test_database';
        $this->application->setDatabaseName($databaseName);
        
        $this->assertEquals($databaseName, $this->application->getDatabaseName());
    }

    public function testDatabaseNameMaxLength(): void
    {
        $longDatabaseName = str_repeat('a', 256);
        $this->application->setDatabaseName($longDatabaseName);
        
        // The validation should be checked by Symfony Validator, 
        // but the setter should accept it
        $this->assertEquals($longDatabaseName, $this->application->getDatabaseName());
    }

    public function testIsActiveGetterAndSetter(): void
    {
        $this->assertTrue($this->application->isActive());
        
        $this->application->setIsActive(false);
        $this->assertFalse($this->application->isActive());
        
        $this->application->setIsActive(true);
        $this->assertTrue($this->application->isActive());
    }

    public function testCreatedAtGetterAndSetter(): void
    {
        $date = new \DateTime('2025-01-01 12:00:00');
        $this->application->setCreatedAt($date);
        
        $this->assertEquals($date, $this->application->getCreatedAt());
    }

    public function testUpdatedAtGetterAndSetter(): void
    {
        $this->assertNull($this->application->getUpdatedAt());
        
        $date = new \DateTime();
        $this->application->setUpdatedAt($date);
        
        $this->assertEquals($date, $this->application->getUpdatedAt());
    }

    public function testDeletedAtGetterAndSetter(): void
    {
        $this->assertNull($this->application->getDeletedAt());
        
        $date = new \DateTime();
        $this->application->setDeletedAt($date);
        
        $this->assertEquals($date, $this->application->getDeletedAt());
    }

    public function testAddSubscription(): void
    {
        $subscription = new Subscription();
        
        $this->application->addSubscription($subscription);
        
        $this->assertCount(1, $this->application->getSubscriptions());
        $this->assertTrue($this->application->getSubscriptions()->contains($subscription));
        $this->assertEquals($this->application, $subscription->getApplication());
    }

    public function testAddSubscriptionOnlyOnce(): void
    {
        $subscription = new Subscription();
        
        $this->application->addSubscription($subscription);
        $this->application->addSubscription($subscription);
        
        $this->assertCount(1, $this->application->getSubscriptions());
    }

    public function testRemoveSubscription(): void
    {
        $subscription = new Subscription();
        
        $this->application->addSubscription($subscription);
        $this->assertCount(1, $this->application->getSubscriptions());
        
        $this->application->removeSubscription($subscription);
        
        $this->assertCount(0, $this->application->getSubscriptions());
        $this->assertNull($subscription->getApplication());
    }
}
