<?php

namespace App\Controller\Admin;

use App\Entity\Organization;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class OrganizationCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator
    ) {}

    public static function getEntityFqcn(): string
    {
        return Organization::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPaginatorPageSize(15)
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            TextField::new('name'),
            
            ImageField::new('iconUrl')
                ->setRequired(false)
                ->setUploadDir('public/uploads/organizations')
                ->setBasePath('uploads/organizations'),
            
            BooleanField::new('isActive'),
            
            DateTimeField::new('deletedAt')
                ->setRequired(false)
                ->hideOnIndex(),
            
            DateTimeField::new('createdAt')
                ->hideOnIndex(),
            
            DateTimeField::new('updatedAt')
                ->hideOnIndex(),
        ];

        // Add Users and Subscriptions sections on detail page
        if ($pageName === Crud::PAGE_DETAIL) {
            $fields[] = FormField::addPanel('Users')->setIcon('fa fa-users');
            $fields[] = AssociationField::new('users')
                ->setLabel('')
                ->setTemplatePath('admin/organization_crud/organization_users.html.twig')
                ->formatValue(function ($value, $entity) {
                    return $entity; // Pass the entire organization entity
                })
                ->onlyOnDetail();

            $fields[] = FormField::addPanel('Subscriptions')->setIcon('fa fa-credit-card');
            $fields[] = AssociationField::new('subscriptions')
                ->setLabel('')
                ->setTemplatePath('admin/organization_crud/organization_subscriptions.html.twig')
                ->formatValue(function ($value, $entity) {
                    return $entity; // Pass the entire organization entity
                })
                ->onlyOnDetail();
        }

        return $fields;
    }

    public function configureResponseParameters(KeyValueStore $responseParameters): KeyValueStore
    {
        if (Crud::PAGE_DETAIL === $responseParameters->get('pageName')) {
            $entity = $responseParameters->get('entity');
            
            if ($entity) {
                $instance = $entity->getInstance();
                if ($instance instanceof Organization) {
                    // Pass the organization ID to templates
                    $responseParameters->set('organizationId', $instance->getId());
                }
            }
        }

        return $responseParameters;
    }
}
