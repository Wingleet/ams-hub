<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use ReflectionClass;

/**
 * UserSoftDeleteListener - Handles soft delete for User entities
 * 
 * When a User entity is deleted:
 * 1. Prevents physical deletion from database
 * 2. Sets deletedAt timestamp
 * 3. Sets isAmsUser to false (prevents login)
 * 4. Keeps isActive as is (for potential restoration)
 */
#[AsDoctrineListener(event: Events::onFlush, priority: 500)]
class UserSoftDeleteListener
{
    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        // Get entities scheduled for deletion
        $scheduledDeletions = $unitOfWork->getScheduledEntityDeletions();
        
        foreach ($scheduledDeletions as $entity) {
            // Only handle User entities
            if (!$entity instanceof User) {
                continue;
            }

            // Set soft delete fields
            $entity->setDeletedAt(new \DateTime());
            $entity->setIsAmsUser(false);

            // Use reflection to remove entity from deletion queue
            $reflectionClass = new ReflectionClass($unitOfWork);
            $reflectionProperty = $reflectionClass->getProperty('entityDeletions');
            $reflectionProperty->setAccessible(true);
            
            $entityDeletions = $reflectionProperty->getValue($unitOfWork);
            $key = spl_object_id($entity);
            unset($entityDeletions[$key]);
            $reflectionProperty->setValue($unitOfWork, $entityDeletions);

            // Schedule for update instead
            $entityManager->persist($entity);
            $classMetadata = $entityManager->getClassMetadata(User::class);
            $unitOfWork->computeChangeSet($classMetadata, $entity);
        }
    }
}
