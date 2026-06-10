<?php

namespace App\Command;

use App\Entity\Organization;
use App\Entity\User;
use App\Enum\UserRole;
use App\Service\AmsApiService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-ams',
    description: 'Synchronize organizations and users from AMS API'
)]
class SyncAmsDataCommand extends Command
{
    public function __construct(
        private AmsApiService $amsApiService,
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
    ) {
        parent::__construct();
    }

    /**
     * Get a fresh EntityManager (handles closed EM scenario)
     */
    private function getEntityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManager();
        
        if (!$em->isOpen()) {
            $this->doctrine->resetManager();
            /** @var EntityManagerInterface $em */
            $em = $this->doctrine->getManager();
            $this->logger->info('EntityManager was closed and has been reset');
        }
        
        return $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $io->info('Starting AMS synchronization...');
            $this->logger->info('AMS synchronization started');

            // Step 1: Authenticate with AMS API
            $io->section('Step 1: Authenticating with AMS API');
            if (!$this->amsApiService->authenticate()) {
                $io->error('Failed to authenticate with AMS API');
                $this->logger->error('AMS authentication failed');
                return Command::FAILURE;
            }
            $io->success('Successfully authenticated with AMS API');

            // Step 2: Sync companies
            $io->section('Step 2: Synchronizing organizations');
            $companiesSynced = $this->syncCompanies($io);
            $io->success(sprintf('Organizations synchronized: %d created/updated', $companiesSynced));

            // Step 3: Sync users
            $io->section('Step 3: Synchronizing users');
            $usersSynced = $this->syncUsers($io);
            $io->success(sprintf('Users synchronized: %d created/updated', $usersSynced));

            $io->success('AMS synchronization completed successfully');
            $this->logger->info('AMS synchronization completed', [
                'organizations' => $companiesSynced,
                'users' => $usersSynced,
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('An error occurred during synchronization: ' . $e->getMessage());
            $this->logger->error('AMS synchronization error', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Synchronize organizations from AMS API
     */
    private function syncCompanies(SymfonyStyle $io): int
    {
        $companies = $this->amsApiService->getCompanies();

        if (empty($companies)) {
            $io->warning('No companies received from AMS API');
            return 0;
        }

        $synced = 0;

        foreach ($companies as $company) {
            try {
                // Get fresh EntityManager for each iteration
                $em = $this->getEntityManager();
                
                // Validate required fields
                if (!isset($company['compid']) || !isset($company['compfullname'])) {
                    $this->logger->warning('Company missing required fields', ['company' => $company]);
                    continue;
                }

                $companyId = (int) $company['compid'];
                $companyName = $company['compfullname'];

                // Primary lookup: check if company exists by amsCompanyId
                $organizationRepo = $em->getRepository(Organization::class);
                $organization = $organizationRepo->findOneBy(['amsCompanyId' => $companyId]);
                
                // Double check by name
                if (!$organization) {
                    $byName = $organizationRepo->findOneBy(['name' => $companyName]);
                    // Only use the name match if it doesn't already have a different amsCompanyId assigned
                    if ($byName && !$byName->getAmsCompanyId()) {
                        $organization = $byName;
                    }
                }
                
                $isNew = !$organization;
                if ($isNew) {
                    $organization = new Organization();
                }
                
                $this->populateOrganizationFields($organization, $companyName, $companyId);
                
                if ($isNew) {
                    $em->persist($organization);
                    $this->logger->info('Creating new organization', ['name' => $companyName]);
                } else {
                    $this->logger->info('Updating existing organization', [
                        'id' => $organization->getId(),
                        'name' => $companyName,
                        'amsCompanyId' => $companyId,
                    ]);
                }

                $em->flush();
                $em->clear();
                $synced++;
                $io->writeln(sprintf('<info>✓</info> Organization: %s (ID: %s)%s', $companyName, $companyId, $isNew ? ' (new)' : ''));
            } catch (\Exception $e) {
                $this->logger->error('Error syncing company', [
                    'company' => $company,
                    'exception' => $e->getMessage(),
                ]);
                $io->writeln(sprintf('<error>✗</error> Failed to sync company: %s - %s', $company['compfullname'] ?? 'Unknown', $e->getMessage()));
                // Force reset for next iteration
                $this->doctrine->resetManager();
            }
        }

        return $synced;
    }

    /**
     * Synchronize users from AMS API
     */
    private function syncUsers(SymfonyStyle $io): int
    {
        $users = $this->amsApiService->getUsers();

        if (empty($users)) {
            $io->warning('No users received from AMS API');
            return 0;
        }

        $synced = 0;
        $emailsProcessed = []; // Track processed emails to avoid duplicates

        foreach ($users as $userData) {
            try {
                // Get fresh EntityManager for each iteration
                $em = $this->getEntityManager();
                
                // Validate required fields
                if (!isset($userData['uemail']) || empty($userData['uemail'])) {
                    $this->logger->warning('User missing email', ['userId' => $userData['userid'] ?? 'unknown']);
                    continue;
                }

                $email = trim($userData['uemail']);
                
                // Skip invalid emails
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->logger->warning('User has invalid email', ['email' => $email, 'userId' => $userData['userid'] ?? 'unknown']);
                    continue;
                }
                
                // Skip if we've already processed this email (AMS has duplicates)
                if (isset($emailsProcessed[$email])) {
                    $this->logger->info('Skipping duplicate email', ['email' => $email, 'userId' => $userData['userid'] ?? 'unknown']);
                    continue;
                }
                $emailsProcessed[$email] = true;

                $username = $userData['name'] ?? null;
                $fullName = $userData['namefull'] ?? $username ?? $email;
                $companyId = $userData['compid'] ?? null;

                // Parse full name into first and last name
                [$firstName, $lastName] = $this->parseFullName($fullName);

                // Check if user already exists (by email or username)
                $userRepo = $em->getRepository(User::class);
                $user = $userRepo->findOneBy(['email' => $email]);
                if (!$user && $username) {
                    $user = $userRepo->findOneBy(['username' => $username]);
                }

                $isNew = false;
                if ($user) {
                    // Update existing user
                    $this->populateUserFields($em, $user, $email, $firstName, $lastName, $username, $companyId);
                } else {
                    // Create new user
                    $isNew = true;
                    $user = new User();
                    $this->populateUserFields($em, $user, $email, $firstName, $lastName, $username, $companyId);

                    // Set minimal role
                    $user->addRole(UserRole::USER);

                    // Set a default password
                    $user->setPassword(bin2hex(random_bytes(16)));

                    $em->persist($user);

                    $this->logger->info('Creating new user', [
                        'email' => $email,
                        'firstname' => $firstName,
                        'lastname' => $lastName,
                    ]);
                }

                $em->flush();
                $em->clear();
                
                $synced++;
                $io->writeln(sprintf('<info>✓</info> User: %s <%s>%s', $fullName, $email, $isNew ? ' (new)' : ''));
                
            } catch (\Exception $e) {
                $this->logger->error('Error syncing user', [
                    'user' => $userData,
                    'exception' => $e->getMessage(),
                ]);
                $io->writeln(sprintf('<error>✗</error> Failed to sync user: %s - %s', $userData['uemail'] ?? 'Unknown', $e->getMessage()));
                // Force reset for next iteration
                $this->doctrine->resetManager();
            }
        }

        return $synced;
    }

    /**
     * Populate user fields
     */
    private function populateUserFields(EntityManagerInterface $em, User $user, string $email, string $firstName, string $lastName, ?string $username = null, $companyId = null): void
    {
        $user->setEmail($email);
        $user->setFirstname($firstName);
        $user->setLastname($lastName);
        $user->setIsActive(true);
        $user->setIsAmsUser(true);

        if ($username) {
            $user->setUsername($username);
        }

        if ($companyId) {
            $organizationRepo = $em->getRepository(Organization::class);
            $organization = $organizationRepo->findOneBy(['amsCompanyId' => (int) $companyId]);
            if ($organization) {
                $user->setOrganization($organization);
            }
        }
    }

    /**
     * Parse full name into first and last name
     */
    private function parseFullName(string $fullName): array
    {
        $parts = explode(' ', trim($fullName), 2);
        $firstName = $parts[0] ?? 'Unknown';
        $lastName = $parts[1] ?? '';

        return [$firstName, $lastName];
    }

    /**
     * Populate organization fields
     */
    private function populateOrganizationFields(Organization $organization, string $companyName, int $companyId): void
    {
        $organization->setName($companyName);
        $organization->setAmsCompanyId($companyId);
        $organization->setIsActive(true);
    }
}