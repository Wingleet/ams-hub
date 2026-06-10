<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Organization;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use App\Repository\OrganizationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Create a new user with optional admin role and organization assignment'
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private OrganizationRepository $organizationRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email address of the user')
            ->addArgument('firstName', InputArgument::REQUIRED, 'First name of the user')
            ->addArgument('lastName', InputArgument::REQUIRED, 'Last name of the user')
            ->addArgument('password', InputArgument::REQUIRED, 'Password for the user')
            ->addOption('admin', 'a', InputOption::VALUE_NONE, 'Grant admin role to the user')
            ->addOption('organization', 'o', InputOption::VALUE_OPTIONAL, 'Organization ID to assign to the user')
            ->addOption('inactive', 'i', InputOption::VALUE_NONE, 'Create user as inactive');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $email = $input->getArgument('email');
        $firstName = $input->getArgument('firstName');
        $lastName = $input->getArgument('lastName');
        $password = $input->getArgument('password');
        $isAdmin = $input->getOption('admin');
        $organizationId = $input->getOption('organization');
        $isInactive = $input->getOption('inactive');

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('Invalid email format');
            return Command::FAILURE;
        }

        // Check if user already exists
        $existingUser = $this->userRepository->findOneBy(['email' => $email]);
        if ($existingUser) {
            $io->error(sprintf('User with email "%s" already exists', $email));
            return Command::FAILURE;
        }

        // Validate password strength
        if (strlen($password) < 8) {
            $io->error('Password must be at least 8 characters long');
            return Command::FAILURE;
        }

        // Create user
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setIsActive(!$isInactive);

        // Add admin role if requested
        if ($isAdmin) {
            $user->addRole(UserRole::ADMIN);
            $io->info('Admin role added');
        }

        // Assign to organization if provided
        if ($organizationId) {
            $organization = $this->organizationRepository->find($organizationId);
            if (!$organization) {
                $io->error(sprintf('Organization with ID "%s" not found', $organizationId));
                return Command::FAILURE;
            }
            $user->setOrganization($organization);
            $io->info(sprintf('User assigned to organization "%s"', $organization->getName()));
        }

        // Save user
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf(
            'User "%s" (%s) created successfully with ID: %d',
            $user->getFullName(),
            $email,
            $user->getId()
        ));

        return Command::SUCCESS;
    }
}
