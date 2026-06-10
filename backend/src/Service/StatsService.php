<?php

namespace App\Service;

use App\Repository\ApplicationRepository;
use App\Repository\OrganizationRepository;
use App\Repository\SubscriptionRepository;
use App\Repository\UserRepository;

/**
 * StatsService - Unified statistics service
 * 
 * Consolidates DashboardStatsService, EntityStatsCalculatorService, and 
 * ActivityAggregatorService into a single, focused service following DRY principle.
 * 
 * Responsibilities:
 * - Calculate entity statistics (organizations, applications, users)
 * - Aggregate activities from all entities
 * - Provide formatted dashboard data
 */
class StatsService
{
    public function __construct(
        private UserRepository $userRepository,
        private OrganizationRepository $organizationRepository,
        private ApplicationRepository $applicationRepository,
        private SubscriptionRepository $subscriptionRepository,
    ) {
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): array
    {
        return [
            'totalOrganizations' => $this->organizationRepository->count([]),
            'activeOrganizations' => $this->organizationRepository->count(['isActive' => true]),
            'totalApplications' => $this->applicationRepository->count([]),
            'activeApplications' => $this->applicationRepository->count(['isActive' => true]),
            'totalUsers' => $this->userRepository->count([]),
            'activeUsers' => $this->userRepository->count(['isActive' => true]),
            'totalSubscriptions' => $this->subscriptionRepository->count([]),
        ];
    }

    /**
     * Calculate stats for an entity type
     */
    public function calculateEntityStats(string $entityType): array
    {
        $repository = $this->getRepository($entityType);
        
        $total = $repository->count([]);
        $active = $repository->count(['isActive' => true]);

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'activationRate' => $total > 0 ? round(($active / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get recent activities from all entities
     */
    public function getRecentActivities(int $limit = 10): array
    {
        $activities = [];

        $activities = array_merge($activities, $this->getUserActivities($limit));
        $activities = array_merge($activities, $this->getOrganizationActivities($limit));
        $activities = array_merge($activities, $this->getApplicationActivities($limit));
        $activities = array_merge($activities, $this->getSubscriptionActivities($limit));

        // Sort by timestamp descending
        usort($activities, fn($a, $b) => $b['timestamp'] - $a['timestamp']);

        // Remove timestamp field and limit results
        $activities = array_slice($activities, 0, $limit);
        foreach ($activities as &$activity) {
            unset($activity['timestamp']);
        }

        return $activities;
    }

    /**
     * Get user creation activities
     */
    private function getUserActivities(int $limit): array
    {
        $activities = [];

        // Created users
        $users = $this->userRepository->createQueryBuilder('u')
            ->where('u.deletedAt IS NULL')
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        foreach ($users as $user) {
            $activities[] = $this->formatActivity(
                'user_created_' . $user->getId(),
                'user_created',
                "New user created: {$user->getEmail()}",
                $user->getCreatedAt(),
                $user->getId(),
                $user->getEmail()
            );
        }

        // Deleted users
        $deletedUsers = $this->userRepository->createQueryBuilder('u')
            ->where('u.deletedAt IS NOT NULL')
            ->orderBy('u.deletedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        foreach ($deletedUsers as $user) {
            $activities[] = $this->formatActivity(
                'user_deleted_' . $user->getId(),
                'user_deleted',
                "User deleted: {$user->getEmail()}",
                $user->getDeletedAt(),
                $user->getId(),
                $user->getEmail()
            );
        }

        // Updated users (activation/deactivation)
        $updatedUsers = $this->userRepository->createQueryBuilder('u')
            ->where('u.updatedAt IS NOT NULL')
            ->andWhere('u.deletedAt IS NULL')
            ->orderBy('u.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        foreach ($updatedUsers as $user) {
            $type = $user->isActive() ? 'user_activated' : 'user_deactivated';
            $message = $user->isActive()
                ? "User activated: {$user->getEmail()}"
                : "User deactivated: {$user->getEmail()}";
            
            $activities[] = $this->formatActivity(
                'user_updated_' . $user->getId(),
                $type,
                $message,
                $user->getUpdatedAt(),
                $user->getId(),
                $user->getEmail()
            );
        }

        return $activities;
    }

    /**
     * Get organization activities
     */
    private function getOrganizationActivities(int $limit): array
    {
        $activities = [];

        // Created organizations
        $organizations = $this->organizationRepository->createQueryBuilder('o')
            ->where('o.deletedAt IS NULL')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        foreach ($organizations as $org) {
            $activities[] = $this->formatActivity(
                'org_created_' . $org->getId(),
                'organization_created',
                "New organization created: {$org->getName()}",
                $org->getCreatedAt(),
                $org->getId(),
                $org->getName()
            );
        }

        // Deleted organizations
        $deletedOrgs = $this->organizationRepository->createQueryBuilder('o')
            ->where('o.deletedAt IS NOT NULL')
            ->orderBy('o.deletedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        foreach ($deletedOrgs as $org) {
            $activities[] = $this->formatActivity(
                'org_deleted_' . $org->getId(),
                'organization_deleted',
                "Organization deleted: {$org->getName()}",
                $org->getDeletedAt(),
                $org->getId(),
                $org->getName()
            );
        }

        // Updated organizations
        $updatedOrgs = $this->organizationRepository->createQueryBuilder('o')
            ->where('o.updatedAt IS NOT NULL')
            ->andWhere('o.deletedAt IS NULL')
            ->orderBy('o.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        foreach ($updatedOrgs as $org) {
            $type = $org->isActive() ? 'organization_activated' : 'organization_deactivated';
            $message = $org->isActive() 
                ? "Organization activated: {$org->getName()}"
                : "Organization deactivated: {$org->getName()}";
            
            $activities[] = $this->formatActivity(
                'org_updated_' . $org->getId(),
                $type,
                $message,
                $org->getUpdatedAt(),
                $org->getId(),
                $org->getName()
            );
        }

        return $activities;
    }

    /**
     * Get application activities
     */
    private function getApplicationActivities(int $limit): array
    {
        $activities = [];

        // Created applications
        $applications = $this->applicationRepository->createQueryBuilder('a')
            ->where('a.deletedAt IS NULL')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        foreach ($applications as $app) {
            $activities[] = $this->formatActivity(
                'app_created_' . $app->getId(),
                'application_created',
                "New application created: {$app->getName()}",
                $app->getCreatedAt(),
                $app->getId(),
                $app->getName()
            );
        }

        // Deleted applications
        $deletedApps = $this->applicationRepository->createQueryBuilder('a')
            ->where('a.deletedAt IS NOT NULL')
            ->orderBy('a.deletedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        foreach ($deletedApps as $app) {
            $activities[] = $this->formatActivity(
                'app_deleted_' . $app->getId(),
                'application_deleted',
                "Application deleted: {$app->getName()}",
                $app->getDeletedAt(),
                $app->getId(),
                $app->getName()
            );
        }

        // Updated applications
        $updatedApps = $this->applicationRepository->createQueryBuilder('a')
            ->where('a.updatedAt IS NOT NULL')
            ->andWhere('a.deletedAt IS NULL')
            ->orderBy('a.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        foreach ($updatedApps as $app) {
            $type = $app->isActive() ? 'application_activated' : 'application_deactivated';
            $message = $app->isActive()
                ? "Application activated: {$app->getName()}"
                : "Application deactivated: {$app->getName()}";
            
            $activities[] = $this->formatActivity(
                'app_updated_' . $app->getId(),
                $type,
                $message,
                $app->getUpdatedAt(),
                $app->getId(),
                $app->getName()
            );
        }

        return $activities;
    }

    /**
     * Get subscription activities
     */
    private function getSubscriptionActivities(int $limit): array
    {
        $activities = [];

        // Active subscriptions
        $subscriptions = $this->subscriptionRepository->createQueryBuilder('s')
            ->where('s.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('s.createdAt', 'DESC')
            ->setMaxResults($limit * 2)
            ->getQuery()
            ->getResult();

        foreach ($subscriptions as $subscription) {
            $app = $subscription->getApplication();
            $org = $subscription->getOrganization();
            $dateToUse = $subscription->getUpdatedAt() ?? $subscription->getCreatedAt();
            
            $activities[] = $this->formatActivity(
                'subscription_' . $subscription->getId(),
                'user_subscribed',
                "{$org->getName()} subscribed to {$app->getName()}",
                $dateToUse,
                $app->getId(),
                $app->getName()
            );
        }

        // Inactive subscriptions (unsubscriptions)
        $unsubscriptions = $this->subscriptionRepository->createQueryBuilder('s')
            ->where('s.isActive = :isActive')
            ->andWhere('s.updatedAt IS NOT NULL')
            ->setParameter('isActive', false)
            ->orderBy('s.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        foreach ($unsubscriptions as $subscription) {
            $app = $subscription->getApplication();
            $org = $subscription->getOrganization();
            
            $activities[] = $this->formatActivity(
                'unsubscription_' . $subscription->getId(),
                'user_unsubscribed',
                "{$org->getName()} unsubscribed from {$app->getName()}",
                $subscription->getUpdatedAt(),
                $app->getId(),
                $app->getName()
            );
        }

        return $activities;
    }

    /**
     * Format activity record
     */
    private function formatActivity(
        string $id,
        string $type,
        string $message,
        \DateTimeInterface $date,
        int $entityId,
        string $entityName
    ): array {
        return [
            'id' => $id,
            'type' => $type,
            'message' => $message,
            'createdAt' => $date->format('Y-m-d\TH:i:s\Z'),
            'entityId' => $entityId,
            'entityName' => $entityName,
            'timestamp' => $date->getTimestamp(),
        ];
    }

    /**
     * Get repository for entity type
     */
    private function getRepository(string $entityType)
    {
        return match($entityType) {
            'organization' => $this->organizationRepository,
            'application' => $this->applicationRepository,
            'user' => $this->userRepository,
            default => throw new \InvalidArgumentException("Unknown entity type: {$entityType}")
        };
    }
}
