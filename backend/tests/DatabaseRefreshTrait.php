<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

trait DatabaseRefreshTrait
{
    /**
     * Initialize the database schema for tests
     */
    protected function initializeDatabase(EntityManagerInterface $entityManager): void
    {
        $schemaTool = new SchemaTool($entityManager);
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        
        // Drop and recreate schema
        try {
            $schemaTool->dropSchema($metadata);
        } catch (\Exception) {
            // Schema doesn't exist yet, that's fine
        }
        
        $schemaTool->createSchema($metadata);
    }
}
