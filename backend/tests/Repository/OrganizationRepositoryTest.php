<?php

namespace App\Tests\Repository;

use App\Entity\Organization;
use App\Repository\OrganizationRepository;
use App\Tests\DatabaseRefreshTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class OrganizationRepositoryTest extends KernelTestCase
{
    use DatabaseRefreshTrait;

    private EntityManagerInterface $entityManager;
    private OrganizationRepository $organizationRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->initializeDatabase($this->entityManager);
        $this->organizationRepository = $this->entityManager->getRepository(Organization::class);
    }

    public function testFindAll(): void
    {
        $organizations = $this->organizationRepository->findAll();
        $this->assertIsArray($organizations);
    }

    public function testFindOneBy(): void
    {
        // Create a test organization
        $org = new Organization();
        $org->setName('Test Organization');
        $org->setIsActive(true);
        $this->entityManager->persist($org);
        $this->entityManager->flush();

        // Find the organization
        $foundOrg = $this->organizationRepository->findOneBy(['name' => 'Test Organization']);
        
        $this->assertNotNull($foundOrg);
        $this->assertEquals('Test Organization', $foundOrg->getName());
        $this->assertTrue($foundOrg->isActive());

        // Cleanup
        $this->entityManager->remove($foundOrg);
        $this->entityManager->flush();
    }

    public function testFindByActive(): void
    {
        $activeOrgs = $this->organizationRepository->findBy(['isActive' => true]);
        $this->assertIsArray($activeOrgs);
        
        foreach ($activeOrgs as $org) {
            $this->assertTrue($org->isActive());
        }
    }

    public function testFindByInactive(): void
    {
        $inactiveOrgs = $this->organizationRepository->findBy(['isActive' => false]);
        $this->assertIsArray($inactiveOrgs);
        
        foreach ($inactiveOrgs as $org) {
            $this->assertFalse($org->isActive());
        }
    }

    public function testCountAll(): void
    {
        $count = $this->organizationRepository->count([]);
        $this->assertGreaterThanOrEqual(0, $count);
    }
}
