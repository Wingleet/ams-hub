<?php

namespace App\Controller\Admin;

use App\Entity\Subscription;
use App\Entity\Organization;
use App\Entity\Application;
use App\Repository\OrganizationRepository;
use App\Repository\SubscriptionRepository;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Routing\Annotation\Route;

class OrganizationSubscriptionCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly OrganizationRepository $organizationRepository,
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public static function getEntityFqcn(): string
    {
        return Subscription::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Subscription')
            ->setEntityLabelInPlural('Organization Subscriptions')
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(15);
    }

    public function configureActions(Actions $actions): Actions
    {
        $backAction = Action::new('back', 'Back to Organization')
            ->linkToUrl(fn () => $this->getBackUrl())
            ->setCssClass('btn btn-secondary')
            ->setIcon('fa fa-arrow-left')
            ->createAsGlobalAction();

        return $actions
            ->add(Crud::PAGE_INDEX, $backAction)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('Add Subscription')->displayAsLink();
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setLabel('Edit');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setLabel('Delete');
            })
            ->update(Crud::PAGE_DETAIL, Action::EDIT, function (Action $action) {
                return $action->setLabel('Edit');
            })
            ->update(Crud::PAGE_DETAIL, Action::DELETE, function (Action $action) {
                return $action->setLabel('Delete');
            })
            ->update(Crud::PAGE_DETAIL, Action::INDEX, function (Action $action) {
                return $action->setLabel('Back');
            });
    }

    public function configureFields(string $pageName): iterable
    {
        if ($pageName === Crud::PAGE_INDEX) {
            // List view - simple and relevant fields
            return [
                IdField::new('id')
                    ->setLabel('ID'),
                
                AssociationField::new('application')
                    ->setLabel('Application'),
                
                BooleanField::new('isActive')
                    ->setLabel('Status')
                    ->renderAsSwitch(),
                
                DateTimeField::new('endsAt')
                    ->setLabel('Expires at')
                    ->setFormat('dd/MM/yyyy HH:mm'),
            ];
        }

        if ($pageName === Crud::PAGE_DETAIL) {
            // Detail view - all fields
            return [
                IdField::new('id')
                    ->setLabel('ID')
                    ->onlyOnIndex(),
                
                AssociationField::new('organization')
                    ->setLabel('Organization'),
                
                AssociationField::new('application')
                    ->setLabel('Application'),
                
                BooleanField::new('isActive')
                    ->setLabel('Active'),
                
                DateTimeField::new('endsAt')
                    ->setLabel('Expiration Date'),
                
                DateTimeField::new('createdAt')
                    ->setLabel('Created at')
                    ->setFormat('dd/MM/yyyy HH:mm:ss')
                    ->onlyOnIndex(),
                
                DateTimeField::new('updatedAt')
                    ->setLabel('Updated at')
                    ->setFormat('dd/MM/yyyy HH:mm:ss')
                    ->onlyOnIndex(),
            ];
        }

        // Create/Edit form
        $applicationField = AssociationField::new('application')
            ->setLabel('Application')
            ->setRequired(true);
        
        // Filter out already subscribed applications
        if ($organizationId = $this->getOrganizationId()) {
            $applicationField->setQueryBuilder(function (QueryBuilder $qb) use ($organizationId) {
                // Get all applications that are NOT already subscribed in this organization
                $subQuery = $this->subscriptionRepository->createQueryBuilder('sub')
                    ->select('IDENTITY(sub.application)')
                    ->where('sub.organization = :orgId')
                    ->setParameter('orgId', $organizationId);
                
                $qb->andWhere('entity.id NOT IN (' . $subQuery->getDQL() . ')')
                   ->setParameter('orgId', $organizationId);
                
                return $qb;
            });
        }
        
        return [
            AssociationField::new('organization')
                ->setLabel('Organization')
                ->onlyOnForms(),
            
            $applicationField,
            
            BooleanField::new('isActive')
                ->setLabel('Active'),
            
            DateTimeField::new('endsAt')
                ->setLabel('Expiration Date')
                ->setRequired(false),
        ];
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            throw new \InvalidArgumentException('Organization ID is required to view subscriptions. Please access this page from an organization.');
        }
        
        // Reset all order by clauses to avoid EasyAdmin's default sorting issues
        $qb->resetDQLPart('orderBy');
        
        $qb->andWhere('entity.organization = :organizationId')
            ->setParameter('organizationId', $organizationId)
            ->orderBy('entity.id', 'DESC');

        return $qb;
    }

    public function createEntity(string $entityFqcn)
    {
        $entity = parent::createEntity($entityFqcn);
        
        if ($entity instanceof Subscription && $organizationId = $this->getOrganizationId()) {
            try {
                // Use getReference to create a lazy-loaded proxy without hitting the DB
                $organization = $this->entityManager->getReference(Organization::class, $organizationId);
                $entity->setOrganization($organization);
            } catch (\Exception $e) {
                // If reference fails, try to find the organization
                $organization = $this->organizationRepository->find($organizationId);
                if ($organization) {
                    $entity->setOrganization($organization);
                }
            }
        }

        return $entity;
    }

    private function getOrganizationId(): ?int
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        
        // First try to get from query parameter (safe parameter name)
        if ($request && $request->query->get('orgId')) {
            $orgId = (int) $request->query->get('orgId');
            // Store in session for future requests
            if ($request->hasSession()) {
                $request->getSession()->set('current_organization_id', $orgId);
            }
            return $orgId;
        }
        
        // Check if it's stored in session
        if ($request && $request->hasSession()) {
            $sessionOrgId = $request->getSession()->get('current_organization_id');
            if ($sessionOrgId) {
                return (int) $sessionOrgId;
            }
        }
        
        // If still not found, try to extract from HTTP_REFERER
        if ($request && $request->server->get('HTTP_REFERER')) {
            if (preg_match('/entityId=(\d+)/', $request->server->get('HTTP_REFERER'), $matches)) {
                $orgId = (int) $matches[1];
                // Verify this is actually an organization by checking referer contains OrganizationCrudController
                if (strpos($request->server->get('HTTP_REFERER'), 'OrganizationCrudController') !== false) {
                    // Store in session
                    if ($request->hasSession()) {
                        $request->getSession()->set('current_organization_id', $orgId);
                    }
                    return $orgId;
                }
            }
        }
        
        // If still not found, try to extract from the request POST data or form data
        if ($request) {
            // Check if there's an organization ID in POST data
            $postData = $request->request->all();
            if (isset($postData['Subscription']['organization']) && is_numeric($postData['Subscription']['organization'])) {
                $orgId = (int) $postData['Subscription']['organization'];
                if ($request->hasSession()) {
                    $request->getSession()->set('current_organization_id', $orgId);
                }
                return $orgId;
            }
        }
        
        return null;
    }

    private function getBackUrl(): string
    {
        $organizationId = $this->getOrganizationId();
        if (!$organizationId) {
            return $this->adminUrlGenerator
                ->setController(OrganizationCrudController::class)
                ->setAction('index')
                ->generateUrl();
        }

        return $this->adminUrlGenerator
            ->setController(OrganizationCrudController::class)
            ->setAction('detail')
            ->setEntityId($organizationId)
            ->generateUrl() . '?orgId=' . $organizationId;
    }

    public function persistEntity($entityManager, mixed $entity): void
    {
        if ($entity instanceof Subscription) {
            // Always try to ensure organization is set
            if (!$entity->getOrganization()) {
                $organizationId = $this->getOrganizationId();
                
                if ($organizationId) {
                    try {
                        // Use getReference to create a lazy-loaded proxy
                        $organization = $entityManager->getReference(Organization::class, $organizationId);
                        $entity->setOrganization($organization);
                    } catch (\Exception $e) {
                        // If reference fails, try to find the organization
                        $organization = $this->organizationRepository->find($organizationId);
                        if ($organization) {
                            $entity->setOrganization($organization);
                        }
                    }
                }
            }
            
            // Strict validation - organization MUST be set
            if (!$entity->getOrganization()) {
                throw new \InvalidArgumentException('Cannot create subscription without an organization. Please access this form from an organization page.');
            }
        }

        parent::persistEntity($entityManager, $entity);
    }

    public function getRedirectResponseAfterSave(AdminContext $context, string $action): RedirectResponse
    {
        $organizationId = $this->getOrganizationId();
        
        if ($organizationId) {
            $url = $this->adminUrlGenerator
                ->setController(OrganizationCrudController::class)
                ->setAction('detail')
                ->setEntityId($organizationId)
                ->generateUrl();
            
            return new RedirectResponse($url);
        }
        
        return parent::getRedirectResponseAfterSave($context, $action);
    }

    public function getRedirectResponseAfterDelete(AdminContext $context): RedirectResponse
    {
        $organizationId = $this->getOrganizationId();
        
        if ($organizationId) {
            $url = $this->adminUrlGenerator
                ->setController(OrganizationCrudController::class)
                ->setAction('detail')
                ->setEntityId($organizationId)
                ->generateUrl();
            
            return new RedirectResponse($url);
        }
        
        return parent::getRedirectResponseAfterSave($context, Action::INDEX);
    }

    #[Route('/admin/organization/{organizationId}/subscription/{subscriptionId}/delete', name: 'delete_organization_subscription', methods: ['POST'])]
    public function deleteSubscription(Request $request, int $organizationId, int $subscriptionId): RedirectResponse
    {
        // Verify CSRF token
        if (!$this->csrfTokenManager->isTokenValid(
            new \Symfony\Component\Security\Csrf\CsrfToken('delete_subscription_' . $subscriptionId, $request->request->get('_token'))
        )) {
            throw new \Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException('Invalid CSRF token');
        }
        
        $subscription = $this->entityManager->getRepository(Subscription::class)->find($subscriptionId);
        
        if ($subscription) {
            $this->entityManager->remove($subscription);
            $this->entityManager->flush();
        }
        
        // Redirect back to organization detail
        $url = $this->adminUrlGenerator
            ->setController(OrganizationCrudController::class)
            ->setAction('detail')
            ->setEntityId($organizationId)
            ->generateUrl();
        
        return new RedirectResponse($url);
    }
}
