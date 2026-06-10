<?php

namespace App\Controller\Admin;

use App\Entity\Organization;
use App\Entity\User;
use App\Entity\Application;
use App\Entity\Subscription;
use App\Repository\OrganizationRepository;
use App\Repository\UserRepository;
use App\Repository\ApplicationRepository;
use App\Repository\SubscriptionRepository;
use App\Service\StatsService;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractDashboardController
{

    public function __construct(
        private readonly AdminUrlGenerator $AdminUrlGenerator,
        private readonly UserRepository $userRepository,
        private readonly OrganizationRepository $organizationRepository,
        private readonly ApplicationRepository $applicationRepository,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly RequestStack $requestStack,
        private readonly StatsService $statsService,
    )
    {
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $request = $this->requestStack->getCurrentRequest();
        $itemsPerPage = 10;
        
        // Get pagination parameters
        $usersPage = (int) $request->query->get('users_page', 1);
        $organizationsPage = (int) $request->query->get('organizations_page', 1);
        $applicationsPage = (int) $request->query->get('applications_page', 1);
        $subscriptionsPage = (int) $request->query->get('subscriptions_page', 1);
        $activitiesPage = (int) $request->query->get('activities_page', 1);
        
        // General statistics (DB-level counts, no findAll)
        $stats = $this->statsService->getDashboardStats();

        // Paginate data using SQL LIMIT/OFFSET via findBy
        $users = $this->userRepository->findBy([], null, $itemsPerPage, ($usersPage - 1) * $itemsPerPage);
        $organizations = $this->organizationRepository->findBy([], null, $itemsPerPage, ($organizationsPage - 1) * $itemsPerPage);
        $applications = $this->applicationRepository->findBy([], null, $itemsPerPage, ($applicationsPage - 1) * $itemsPerPage);
        $subscriptions = $this->subscriptionRepository->findBy([], null, $itemsPerPage, ($subscriptionsPage - 1) * $itemsPerPage);

        // Get recent activities
        $allActivities = $this->statsService->getRecentActivities(50);
        $activities = array_slice($allActivities, ($activitiesPage - 1) * $itemsPerPage, $itemsPerPage);

        // Generate URLs for organizations
        $organizationUrls = [];
        foreach ($organizations as $org) {
            $organizationUrls[$org->getId()] = $this->AdminUrlGenerator
                ->setController(OrganizationCrudController::class)
                ->setAction('detail')
                ->setEntityId($org->getId())
                ->generateUrl();
        }

        return $this->render('admin/dashboard/index.html.twig', [
            'stats' => $stats,
            'users' => $users,
            'organizations' => $organizations,
            'applications' => $applications,
            'subscriptions' => $subscriptions,
            'activities' => $activities,
            'organizationUrls' => $organizationUrls,
            'usersPage' => $usersPage,
            'organizationsPage' => $organizationsPage,
            'applicationsPage' => $applicationsPage,
            'subscriptionsPage' => $subscriptionsPage,
            'activitiesPage' => $activitiesPage,
            'itemsPerPage' => $itemsPerPage,
            'totalUsers' => $stats['totalUsers'],
            'totalOrganizations' => $stats['totalOrganizations'],
            'totalApplications' => $stats['totalApplications'],
            'totalSubscriptions' => $stats['totalSubscriptions'],
            'totalActivities' => count($allActivities),
        ]);
    }

    #[Route('/admin/dashboard/section/{section}', name: 'admin_dashboard_section')]
    public function getSection(string $section): Response
    {
        $request = $this->requestStack->getCurrentRequest();
        $itemsPerPage = 10;
        $page = (int) $request->query->get('page', 1);

        $data = [];

        match ($section) {
            'users' => $data = $this->getSectionData('users', $page, $itemsPerPage),
            'organizations' => $data = $this->getSectionData('organizations', $page, $itemsPerPage),
            'applications' => $data = $this->getSectionData('applications', $page, $itemsPerPage),
            'subscriptions' => $data = $this->getSectionData('subscriptions', $page, $itemsPerPage),
            'activities' => $data = $this->getSectionData('activities', $page, $itemsPerPage),
            default => throw new \InvalidArgumentException("Unknown section: $section"),
        };

        return $this->render("admin/dashboard/sections/{$section}.html.twig", $data);
    }

    private function getSectionData(string $section, int $page, int $itemsPerPage): array
    {
        return match ($section) {
            'users' => $this->getUsersData($page, $itemsPerPage),
            'organizations' => $this->getOrganizationsData($page, $itemsPerPage),
            'applications' => $this->getApplicationsData($page, $itemsPerPage),
            'subscriptions' => $this->getSubscriptionsData($page, $itemsPerPage),
            'activities' => $this->getActivitiesData($page, $itemsPerPage),
            default => [],
        };
    }

    private function getUsersData(int $page, int $itemsPerPage): array
    {
        $total = $this->userRepository->count([]);
        $users = $this->userRepository->findBy([], null, $itemsPerPage, ($page - 1) * $itemsPerPage);
        $totalPages = (int) ceil($total / $itemsPerPage);

        return [
            'users' => $users,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $total,
            'itemsPerPage' => $itemsPerPage,
        ];
    }

    private function getOrganizationsData(int $page, int $itemsPerPage): array
    {
        $total = $this->organizationRepository->count([]);
        $organizations = $this->organizationRepository->findBy([], null, $itemsPerPage, ($page - 1) * $itemsPerPage);
        $totalPages = (int) ceil($total / $itemsPerPage);

        // Generate URLs for organizations
        $organizationUrls = [];
        foreach ($organizations as $org) {
            $organizationUrls[$org->getId()] = $this->AdminUrlGenerator
                ->setController(OrganizationCrudController::class)
                ->setAction('detail')
                ->setEntityId($org->getId())
                ->generateUrl();
        }

        return [
            'organizations' => $organizations,
            'organizationUrls' => $organizationUrls,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $total,
            'itemsPerPage' => $itemsPerPage,
        ];
    }

    private function getApplicationsData(int $page, int $itemsPerPage): array
    {
        $total = $this->applicationRepository->count([]);
        $applications = $this->applicationRepository->findBy([], null, $itemsPerPage, ($page - 1) * $itemsPerPage);
        $totalPages = (int) ceil($total / $itemsPerPage);

        return [
            'applications' => $applications,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $total,
            'itemsPerPage' => $itemsPerPage,
        ];
    }

    private function getSubscriptionsData(int $page, int $itemsPerPage): array
    {
        $total = $this->subscriptionRepository->count([]);
        $subscriptions = $this->subscriptionRepository->findBy([], null, $itemsPerPage, ($page - 1) * $itemsPerPage);
        $totalPages = (int) ceil($total / $itemsPerPage);

        return [
            'subscriptions' => $subscriptions,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $total,
            'itemsPerPage' => $itemsPerPage,
        ];
    }

    private function getActivitiesData(int $page, int $itemsPerPage): array
    {
        $allActivities = $this->statsService->getRecentActivities(50);
        $activities = array_slice($allActivities, ($page - 1) * $itemsPerPage, $itemsPerPage);
        $totalPages = (int) ceil(count($allActivities) / $itemsPerPage);

        return [
            'activities' => $activities,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => count($allActivities),
            'itemsPerPage' => $itemsPerPage,
        ];
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('AMS APPS HUB')
            ->setLocales(['fr', 'en']);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Organization', 'fa fa-building', Organization::class);
        yield MenuItem::linkToCrud('User', 'fa fa-user', User::class);
        yield MenuItem::linkToCrud('Application', 'fa fa-th-large', Application::class);
        yield MenuItem::linkToCrud('Subscription', 'fa fa-credit-card', Subscription::class);
        
        yield MenuItem::section('Account');
        yield MenuItem::linkToLogout('Logout', 'fa fa-sign-out');
    }
}
