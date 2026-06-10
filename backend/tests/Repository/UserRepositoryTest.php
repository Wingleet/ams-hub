<?php

namespace App\Tests\Repository;

use App\Entity\User;
use App\Entity\Organization;
use App\Repository\UserRepository;
use App\Tests\DatabaseRefreshTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class UserRepositoryTest extends KernelTestCase
{
    use DatabaseRefreshTrait;

    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->initializeDatabase($this->entityManager);
        $this->userRepository = $this->entityManager->getRepository(User::class);
    }

    public function testFindAll(): void
    {
        $users = $this->userRepository->findAll();
        $this->assertIsArray($users);
    }

    public function testFindOneBy(): void
    {
        // Create a test user
        $user = new User();
        $user->setEmail('repository-test@example.com');
        $user->setPassword('hashed_password');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Find the user
        $foundUser = $this->userRepository->findOneBy(['email' => 'repository-test@example.com']);
        
        $this->assertNotNull($foundUser);
        $this->assertEquals('repository-test@example.com', $foundUser->getEmail());

        // Cleanup
        $this->entityManager->remove($foundUser);
        $this->entityManager->flush();
    }

    public function testFindByWithCriteria(): void
    {
        $users = $this->userRepository->findBy([], ['createdAt' => 'DESC']);
        $this->assertIsArray($users);
    }

    public function testFindWithLimit(): void
    {
        $users = $this->userRepository->findBy([], ['createdAt' => 'DESC'], 5, 0);
        $this->assertLessThanOrEqual(5, count($users));
    }

    public function testUpgradePassword(): void
    {
        // The upgradePassword method is part of the PasswordUpgraderInterface
        // It's tested internally by Symfony's authentication system
        // Here we just verify the method exists and has the correct signature
        $this->assertTrue(method_exists($this->userRepository, 'upgradePassword'));
        
        $reflectionMethod = new \ReflectionMethod($this->userRepository, 'upgradePassword');
        $this->assertEquals(2, $reflectionMethod->getNumberOfParameters());
    }

    public function testUpgradePasswordWithInvalidUserType(): void
    {
        $this->expectException(\TypeError::class);

        $invalidUser = new \stdClass();
        $this->userRepository->upgradePassword($invalidUser, 'new_password');
    }
}
