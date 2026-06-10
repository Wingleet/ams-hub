<?php

namespace App\Command;

use App\Entity\Application;
use App\Repository\ApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-applications',
    description: 'Load default applications into the database'
)]
class LoadApplicationsCommand extends Command
{
    public function __construct(
        private ApplicationRepository $applicationRepository,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Loading Applications');

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

        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($applicationsData as $data) {
            $app = $this->applicationRepository->findOneBy(['name' => $data['name']]);
            
            if (!$app) {
                $app = new Application();
                $app->setName($data['name']);
                $this->entityManager->persist($app);
                $createdCount++;
                $io->text(sprintf('  • Creating application: <info>%s</info>', $data['name']));
            } else {
                // Check if update is needed
                if ($app->getDescription() === $data['description'] &&
                    $app->getUrl() === $data['url'] &&
                    $app->getDatabaseName() === $data['databaseName'] &&
                    $app->isActive() === $data['isActive']) {
                    $skippedCount++;
                    $io->text(sprintf('  • Skipping (already up-to-date): <comment>%s</comment>', $data['name']));
                    continue;
                }
                $updatedCount++;
                $io->text(sprintf('  • Updating application: <info>%s</info>', $data['name']));
            }
            
            // Set/update common properties
            $app->setDescription($data['description']);
            $app->setUrl($data['url']);
            $app->setDatabaseName($data['databaseName']);
            $app->setIsActive($data['isActive']);
        }

        $this->entityManager->flush();

        $io->newLine();
        $io->success('Applications loaded successfully!');
        $io->table(
            ['Action', 'Count'],
            [
                ['Created', $createdCount],
                ['Updated', $updatedCount],
                ['Skipped', $skippedCount],
                ['Total', count($applicationsData)],
            ]
        );

        return Command::SUCCESS;
    }
}
