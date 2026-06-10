<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Organization;
use App\Entity\Application;
use App\Repository\UserRepository;
use App\Repository\OrganizationRepository;
use App\Repository\ApplicationRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Fixtures for automated tests
 */
class TestFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private UserRepository $userRepository,
        private OrganizationRepository $organizationRepository,
        private ApplicationRepository $applicationRepository
    ) {
    }

    public static function getGroups(): array
    {
        return ['test'];
    }

    public function load(ObjectManager $manager): void
    {
        // Check if fixtures already exist to avoid duplicates
        $existingOrganizations = $this->organizationRepository->findBy(['name' => 'Organization 1']);
        if (!empty($existingOrganizations)) {
            return; // Fixtures already loaded
        }

        // Create 20 organizations
        $organizations = [];
        for ($i = 1; $i <= 20; $i++) {
            $organization = new Organization();
            $organization->setName("Organization " . $i);
            $organization->setIsActive(true);
            
            $manager->persist($organization);
            $organizations[] = $organization;
        }

        // Create 20 applications
        $applications = [];
        for ($i = 1; $i <= 20; $i++) {
            $application = new Application();
            $application->setName("Application " . $i);
            $application->setDescription("Description for application " . $i);
            $application->setUrl("https://app" . $i . ".example.com");
            $application->setDatabaseName("app_test_" . str_pad($i, 2, '0', STR_PAD_LEFT) . "_db");
            $application->setIsActive(true);
            
            $manager->persist($application);
            $applications[] = $application;
        }

        // Flush organizations and applications first
        $manager->flush();

        // Create 60 users distributed across organizations (3 users per organization)
        $firstNames = [
            'Jean', 'Marie', 'Pierre', 'Sophie', 'Luc', 'Anne', 'Marc', 'Céline',
            'Philippe', 'Julie', 'Olivier', 'Isabelle', 'Christophe', 'Nathalie',
            'Alain', 'Florence', 'Nicolas', 'Catherine', 'Laurent', 'Sylvie'
        ];

        $lastNames = [
            'Dupont', 'Martin', 'Bernard', 'Dubois', 'Moreau', 'Laurent', 'Simon',
            'Michel', 'Garnier', 'Garcia', 'David', 'Bertrand', 'Roux', 'Vincent',
            'Fournier', 'Morel', 'Lefebvre', 'Leroy', 'Henry', 'Chevalier'
        ];

        $userIndex = 0;
        for ($i = 0; $i < 20; $i++) {
            // Add 3 users per organization
            for ($j = 0; $j < 3; $j++) {
                $firstName = $firstNames[$userIndex % count($firstNames)];
                $lastName = $lastNames[$userIndex % count($lastNames)];
                
                $user = new User();
                $user->setEmail("user" . ($userIndex + 1) . "@example.com");
                $user->setFirstName($firstName);
                $user->setLastName($lastName);
                $user->setPassword($this->passwordHasher->hashPassword($user, 'Password123!'));
                $user->setOrganization($organizations[$i]);
                $user->setIsActive(true);
                
                $manager->persist($user);
                $userIndex++;
            }
        }

        // Create a test user for authentication tests
        $testUser = new User();
        $testUser->setEmail('test@example.com');
        $testUser->setFirstName('Test');
        $testUser->setLastName('User');
        $testUser->setPassword($this->passwordHasher->hashPassword($testUser, 'TestPassword123!'));
        $testUser->setOrganization($organizations[0]);
        $testUser->setIsActive(true);
        
        $manager->persist($testUser);
        
        $manager->flush();
    }
}
