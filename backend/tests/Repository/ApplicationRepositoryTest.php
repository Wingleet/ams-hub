<?php

namespace App\Tests\Repository;

use App\Entity\Application;
use App\Repository\ApplicationRepository;
use App\Tests\DatabaseRefreshTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class ApplicationRepositoryTest extends KernelTestCase
{
    use DatabaseRefreshTrait;

    private EntityManagerInterface $entityManager;
    private ApplicationRepository $applicationRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->initializeDatabase($this->entityManager);
        $this->applicationRepository = $this->entityManager->getRepository(Application::class);
    }

    public function testFindAll(): void
    {
        $applications = $this->applicationRepository->findAll();
        $this->assertIsArray($applications);
    }

    public function testFindOneBy(): void
    {
        // Create a test application
        $app = new Application();
        $app->setName('Test Application');
        $app->setUrl('https://test-app.example.com');
        $app->setIsActive(true);
        $this->entityManager->persist($app);
        $this->entityManager->flush();

        // Find the application
        $foundApp = $this->applicationRepository->findOneBy(['name' => 'Test Application']);
        
        $this->assertNotNull($foundApp);
        $this->assertEquals('Test Application', $foundApp->getName());
        $this->assertEquals('https://test-app.example.com', $foundApp->getUrl());
        $this->assertTrue($foundApp->isActive());

        // Cleanup
        $this->entityManager->remove($foundApp);
        $this->entityManager->flush();
    }

    public function testFindByActive(): void
    {
        $activeApps = $this->applicationRepository->findBy(['isActive' => true]);
        $this->assertIsArray($activeApps);
        
        foreach ($activeApps as $app) {
            $this->assertTrue($app->isActive());
        }
    }

    public function testFindByInactive(): void
    {
        $inactiveApps = $this->applicationRepository->findBy(['isActive' => false]);
        $this->assertIsArray($inactiveApps);
        
        foreach ($inactiveApps as $app) {
            $this->assertFalse($app->isActive());
        }
    }

    public function testCountAll(): void
    {
        $count = $this->applicationRepository->count([]);
        $this->assertGreaterThanOrEqual(0, $count);
    }
}
