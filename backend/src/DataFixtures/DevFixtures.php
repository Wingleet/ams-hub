<?php

namespace App\DataFixtures;

use App\Entity\Application;
use App\Entity\Organization;
use App\Entity\Subscription;
use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


/**
 * Minimal fixtures for development
 * Contains basic data to test the application
 */
class DevFixtures extends Fixture implements FixtureGroupInterface
{
    public const DEFAULT_PASSWORD = 'Password123!';

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public static function getGroups(): array
    {
        return ['dev'];
    }

    public function load(ObjectManager $manager): void
    {
        // Create applications
        $applications = $this->createApplications($manager);

        // Create organizations
        $organizations = $this->createOrganizations($manager);

        // Create users
        $users = $this->createUsers($manager, $organizations);

        // Create subscriptions
        $this->createSubscriptions($manager, $organizations, $applications);

        $manager->flush();

        // Create SSO Code
        $ssocode = $this->createSsoCode($manager);
    }

    /**
     * @return Application[]
     */
    private function createApplications(ObjectManager $manager): array
    {
        $applicationsData = [
            [
                'name' => 'iSDR',
                'description' => 'Aircraft Maintenance & SDR Management Platform',
                'url' => 'https://isdr.amc.local',
                'databaseName' => 'app_isdr_prod',
                'isActive' => true,
            ],
            [
                'name' => 'iDismantling',
                'description' => 'Dismantling Management Platform',
                'url' => 'https://idismantling.amc.local',
                'databaseName' => 'app_idismantling_prod',
                'isActive' => true,
            ],
            [
                'name' => 'iKanban',
                'description' => 'Visual Task Tracking and Workflow Management Platform',
                'url' => 'https://ikanban.amc.local',
                'databaseName' => 'app_ikanban_prod',
                'isActive' => true,
            ],
            [
                'name' => 'iARC',
                'description' => 'ARC Compliance & Certification Platform',
                'url' => 'https://iarc.amc.local',
                'databaseName' => 'app_iarc_prod',
                'isActive' => false,
            ],
            [
                'name' => 'iInventory',
                'description' => 'Parts & Inventory Management System',
                'url' => 'https://iinventory.amc.local',
                'databaseName' => 'app_iinventory_prod',
                'isActive' => true,
            ],
            [
                'name' => 'iPlanning',
                'description' => 'Project & Resource Planning Tool',
                'url' => 'https://iplanning.amc.local',
                'databaseName' => 'app_iplanning_prod',
                'isActive' => true,
            ],
            [
                'name' => 'iReporting',
                'description' => 'Advanced Analytics & Reporting Suite',
                'url' => 'https://ireporting.amc.local',
                'databaseName' => 'app_ireporting_prod',
                'isActive' => true,
            ],
            [
                'name' => 'iTraining',
                'description' => 'Training & Certification Management',
                'url' => 'https://itraining.amc.local',
                'databaseName' => 'app_itraining_prod',
                'isActive' => true,
            ],
            [
                'name' => 'iQuality',
                'description' => 'Quality Control & Compliance Platform',
                'url' => 'https://iquality.amc.local',
                'databaseName' => 'app_iquality_prod',
                'isActive' => true,
            ],
            [
                'name' => 'iDocumentation',
                'description' => 'Document Management & Control System',
                'url' => 'https://idocumentation.amc.local',
                'databaseName' => 'app_idocumentation_prod',
                'isActive' => true,
            ],
            [
                'name' => 'SSO_App',
                'description' => 'Central Single Sign-On Authentication Application',
                'url' => 'http://localhost:3000',
                'databaseName' => null,
                'isActive' => true,
            ],
        ];

        $applications = [];
        foreach ($applicationsData as $data) {
            $app = new Application();
            $app->setName($data['name']);
            $app->setDescription($data['description']);
            $app->setUrl($data['url']);
            $app->setDatabaseName($data['databaseName']);
            $app->setIsActive($data['isActive']);
            
            $manager->persist($app);
            $applications[$data['name']] = $app;
        }

        return $applications;
    }

    /**
     * @return Organization[]
     */
    private function createOrganizations(ObjectManager $manager): array
    {
        $organizations = [];
        for ($i = 1; $i <= 15; $i++) {
            $org = new Organization();
            $org->setName("Organization " . $i);
            $org->setIsActive(true);
            
            $manager->persist($org);
            $organizations["Organization " . $i] = $org;
        }

        return $organizations;
    }

    /**
     * @param Organization[] $organizations
     * @return User[]
     */
    private function createUsers(ObjectManager $manager, array $organizations): array
    {
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

        $users = [];
        $userIndex = 0;
        $organizationList = array_values($organizations);
        $organizationCount = count($organizationList);

        // Create 30 total users distributed across organizations
        for ($i = 0; $i < 30; $i++) {
            $firstName = $firstNames[$userIndex % count($firstNames)];
            $lastName = $lastNames[$userIndex % count($lastNames)];
            
            $user = new User();
            $user->setEmail("user" . ($userIndex + 1) . "@example.com");
            $user->setFirstname($firstName);
            $user->setLastname($lastName);
            $user->setPassword($this->passwordHasher->hashPassword($user, self::DEFAULT_PASSWORD));
            $user->setOrganization($organizationList[$i % $organizationCount]);
            $user->setIsActive(true);
            
            // Add only USER role
            $user->addRole(UserRole::USER);
            
            $manager->persist($user);
            $users[$user->getEmail()] = $user;
            $userIndex++;
        }

        return $users;
    }

    /**
     * @param Organization[] $organizations
     * @param Application[] $applications
     */
    private function createSubscriptions(
        ObjectManager $manager,
        array $organizations,
        array $applications
    ): void {
        // Each organization gets subscribed to a few random applications
        $applicationList = array_values($applications);
        $appCount = count($applicationList);

        foreach ($organizations as $organization) {
            // Subscribe to 2-4 random applications per organization
            $numApps = rand(2, min(4, $appCount));
            $selectedApps = array_rand($applicationList, $numApps);
            
            // Ensure selectedApps is always an array
            if (!is_array($selectedApps)) {
                $selectedApps = [$selectedApps];
            }

            foreach ($selectedApps as $appIndex) {
                $app = $applicationList[$appIndex] ?? null;
                if(!$app) continue;
                $subscription = new Subscription();
                $subscription->setOrganization($organization);
                $subscription->setApplication($app);
                $subscription->setIsActive(true);
                $subscription->setEndsAt(new \DateTime('+1 year'));
                $manager->persist($subscription);
            }
        }
    }

    public function createSsoCode(ObjectManager $manager): void
    {
        // SSO configuration for SSO_App application
        // Make sure to update the ID and values according to your configuration

        $applications = $manager->getRepository(Application::class)->findAll();

        foreach ($applications as $application) {
            if ($application->getName() === 'SSO_App') {

                // Must match SSO_SECRET in docker-compose.yml
                $ssoSecret = 'a0d2564005e60a4775f4ad5713b360afde573eeb6f659b33799f6621e14caea2';
                
                $application->setSsoSecret($ssoSecret);
                $application->setSsoCallbackUrl('http://localhost:3000/auth/callback');
                
                $manager->persist($application);
            }
        }

        $manager->flush();
    }
}
