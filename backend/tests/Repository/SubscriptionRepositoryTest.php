<?php

namespace App\Tests\Repository;

use App\Entity\Subscription;
use App\Entity\Organization;
use App\Entity\Application;
use App\Repository\SubscriptionRepository;
use App\Tests\DatabaseRefreshTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class SubscriptionRepositoryTest extends KernelTestCase
{
    use DatabaseRefreshTrait;

    private EntityManagerInterface $entityManager;
    private SubscriptionRepository $subscriptionRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->initializeDatabase($this->entityManager);
        $this->subscriptionRepository = $this->entityManager->getRepository(Subscription::class);
    }

    public function testFindAll(): void
    {
        $subscriptions = $this->subscriptionRepository->findAll();
        $this->assertIsArray($subscriptions);
    }

    public function testFindByActive(): void
    {
        $activeSubscriptions = $this->subscriptionRepository->findBy(['isActive' => true]);
        $this->assertIsArray($activeSubscriptions);
        
        foreach ($activeSubscriptions as $sub) {
            $this->assertTrue($sub->isActive());
        }
    }

    public function testFindByInactive(): void
    {
        $inactiveSubscriptions = $this->subscriptionRepository->findBy(['isActive' => false]);
        $this->assertIsArray($inactiveSubscriptions);
        
        foreach ($inactiveSubscriptions as $sub) {
            $this->assertFalse($sub->isActive());
        }
    }

    public function testFindOneBy(): void
    {
        // Get or create an organization and application for testing
        $org = $this->entityManager->getRepository(Organization::class)->findOneBy([]);
        $app = $this->entityManager->getRepository(Application::class)->findOneBy([]);

        if ($org && $app) {
            // Create a test subscription
            $subscription = new Subscription();
            $subscription->setOrganization($org);
            $subscription->setApplication($app);
            $subscription->setIsActive(true);
            $this->entityManager->persist($subscription);
            $this->entityManager->flush();

            // Find the subscription
            $foundSub = $this->subscriptionRepository->findOneBy([
                'organization' => $org,
                'application' => $app
            ]);
            
            $this->assertNotNull($foundSub);
            $this->assertTrue($foundSub->isActive());

            // Cleanup
            $this->entityManager->remove($foundSub);
            $this->entityManager->flush();
        } else {
            $this->markTestSkipped('No organization or application available for testing');
        }
    }

    public function testCountAll(): void
    {
        $count = $this->subscriptionRepository->count([]);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCountActive(): void
    {
        $count = $this->subscriptionRepository->count(['isActive' => true]);
        $this->assertGreaterThanOrEqual(0, $count);
    }
}
