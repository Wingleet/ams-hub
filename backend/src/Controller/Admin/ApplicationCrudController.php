<?php

namespace App\Controller\Admin;

use App\Entity\Application;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;

class ApplicationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Application::class;
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
    if (Crud::PAGE_INDEX === $pageName) {
        // Index/List view
        return [
            TextField::new('name'),
            TextField::new('databaseName'),
            BooleanField::new('isActive'),
        ];
    }

    // Detail, Create, Edit views
    $fields = [
        TextField::new('name'),
        TextField::new('description'),
        TextField::new('url'),
        TextField::new('iconUrl')
            ->setHelp('Enter the full URL of the icon image (e.g., https://example.com/icon.png)')
            ->setLabel('Icon URL'),
    ];

    if (Crud::PAGE_DETAIL === $pageName) {
        $fields[] = ImageField::new('iconUrl')
            ->setBasePath('/')
            ->setLabel('Icon Preview');
    }

    $fields[] = TextField::new('databaseName');
    $fields[] = BooleanField::new('isActive');

    return $fields;
}
}
