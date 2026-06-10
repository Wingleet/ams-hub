<?php

namespace App\Controller\Admin;

use App\Entity\Subscription;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

class SubscriptionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Subscription::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Subscription')
            ->setEntityLabelInPlural('Subscriptions')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->showEntityActionsInlined()
            ->setPaginatorPageSize(15);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('Add Subscription');
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
        if (Crud::PAGE_INDEX === $pageName) {
            // List view - simple and relevant fields
            return [
                AssociationField::new('organization')
                    ->setLabel('Organization'),
                
                AssociationField::new('application')
                    ->setLabel('Application'),
                
                DateTimeField::new('endsAt')
                    ->setLabel('Expires at')
                    ->setFormat('dd/MM/yyyy HH:mm'),
            ];
        }

        if (Crud::PAGE_DETAIL === $pageName) {
            // Detail view - all fields
            return [
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
        return [
            AssociationField::new('organization')
                ->setLabel('Organization')
                ->setRequired(true),
            
            AssociationField::new('application')
                ->setLabel('Application')
                ->setRequired(true),
            
            BooleanField::new('isActive')
                ->setLabel('Active'),
            
            DateTimeField::new('endsAt')
                ->setLabel('Expiration Date')
                ->setRequired(false),
        ];
    }
}
