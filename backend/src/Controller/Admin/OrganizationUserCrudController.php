<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Organization;
use App\Repository\OrganizationRepository;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Routing\Annotation\Route;

class OrganizationUserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly OrganizationRepository $organizationRepository,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('User')
            ->setEntityLabelInPlural('Organization Users')
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
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $backAction)
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->displayAsLink();
            })
            ->update(Crud::PAGE_DETAIL, Action::EDIT, function (Action $action) {
                return $action->setLabel('Edit');
            })
            ->update(Crud::PAGE_DETAIL, Action::DELETE, function (Action $action) {
                return $action->setLabel('Delete');
            })
            ->update(Crud::PAGE_DETAIL, Action::INDEX, function (Action $action) {
                return $action->setLabel('Back');
            })
            ->add(Crud::PAGE_DETAIL, $backAction);
    }

    public function configureFields(string $pageName): iterable
    {
        if ($pageName === Crud::PAGE_INDEX) {
            return [
                IdField::new('id')
                    ->onlyOnIndex(),
                
                EmailField::new('email')
                    ->setLabel('Email Address'),
                
                TextField::new('firstname')
                    ->setLabel('First Name'),
                
                TextField::new('lastname')
                    ->setLabel('Last Name'),
                
                BooleanField::new('isActive')
                    ->setLabel('Active')
                    ->renderAsSwitch(),
            ];
        }

        // Create/Edit form
        return [
            EmailField::new('email')
                ->setLabel('Email Address')
                ->setRequired(true),
            
            TextField::new('username')
                ->setLabel('Username')
                ->setRequired(false),
            
            TextField::new('firstname')
                ->setLabel('First Name')
                ->setRequired(true),
            
            TextField::new('lastname')
                ->setLabel('Last Name')
                ->setRequired(true),
            
            TextField::new('password')
                ->setLabel('Password')
                ->setRequired(true)
                ->setFormType(PasswordType::class)
                ->onlyOnForms(),
            
            BooleanField::new('isActive')
                ->setLabel('Active')
                ->renderAsSwitch(),
            
            DateTimeField::new('lastLoginAt')
                ->setLabel('Last Login')
                ->onlyOnDetail(),
            
            DateTimeField::new('createdAt')
                ->onlyOnIndex(),
        ];
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        
        if ($organizationId = $this->getOrganizationId()) {
            $qb->andWhere('entity.organization = :organizationId')
                ->setParameter('organizationId', $organizationId);
        }

        return $qb;
    }

    public function createEntity(string $entityFqcn)
    {
        $entity = parent::createEntity($entityFqcn);
        
        if ($entity instanceof User && $organizationId = $this->getOrganizationId()) {
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
            if (isset($postData['User']['organization']) && is_numeric($postData['User']['organization'])) {
                $orgId = (int) $postData['User']['organization'];
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
        if ($entity instanceof User) {
            // Hash the password before persisting
            if ($entity->getPassword()) {
                $hashedPassword = $this->passwordHasher->hashPassword($entity, $entity->getPassword());
                $entity->setPassword($hashedPassword);
            }
            
            // If organization is not set, try to set it
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
                throw new \InvalidArgumentException('Cannot create user without an organization. Please access this form from an organization page.');
            }
        }

        parent::persistEntity($entityManager, $entity);
    }

    public function updateEntity($entityManager, mixed $entity): void
    {
        if ($entity instanceof User) {
            // Only hash password if it was changed
            if ($entity->getPassword()) {
                $hashedPassword = $this->passwordHasher->hashPassword($entity, $entity->getPassword());
                $entity->setPassword($hashedPassword);
            }
        }

        parent::updateEntity($entityManager, $entity);
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

    #[Route('/admin/organization/{organizationId}/user/{userId}/delete', name: 'delete_organization_user', methods: ['POST'])]
    public function deleteUser(Request $request, int $organizationId, int $userId): RedirectResponse
    {
        // Verify CSRF token
        if (!$this->csrfTokenManager->isTokenValid(
            new \Symfony\Component\Security\Csrf\CsrfToken('delete_user_' . $userId, $request->request->get('_token'))
        )) {
            throw new \Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException('Invalid CSRF token');
        }
        
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        
        if ($user) {
            $this->entityManager->remove($user);
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
