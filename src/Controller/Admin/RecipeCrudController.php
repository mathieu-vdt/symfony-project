<?php

namespace App\Controller\Admin;

use App\Entity\Recipe;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;

class RecipeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Recipe::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Recipe')
            ->setEntityLabelInPlural('Recipes')
            ->setSearchFields(['title', 'description', 'author.username', 'category.name'])
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->showEntityActionsInlined();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('category'))
            ->add(EntityFilter::new('author'))
            ->add(NumericFilter::new('difficulty'))
            ->add(NumericFilter::new('prepTime'));
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            
            TextField::new('title')
                ->setColumns('col-sm-8')
                ->setHelp('Enter a descriptive title for your recipe'),
                
            TextareaField::new('description')
                ->setColumns('col-sm-12')
                ->setNumOfRows(3)
                ->setHelp('Brief description of the recipe'),
                
            TextEditorField::new('instructions')
                ->setColumns('col-sm-12')
                ->hideOnIndex()
                ->setHelp('Step-by-step cooking instructions'),
                
            AssociationField::new('category')
                ->setColumns('col-sm-6')
                ->setCrudController(CategoryCrudController::class),
                
            AssociationField::new('author')
                ->setColumns('col-sm-6')
                ->setCrudController(UserCrudController::class)
                ->hideOnForm(),
                
            IntegerField::new('prepTime')
                ->setColumns('col-sm-4')
                ->setHelp('Preparation time in minutes'),
                
            ChoiceField::new('difficulty')
                ->setColumns('col-sm-4')
                ->setChoices([
                    '⭐ Easy' => 1,
                    '⭐⭐ Medium' => 2,
                    '⭐⭐⭐ Hard' => 3,
                    '⭐⭐⭐⭐ Expert' => 4,
                    '⭐⭐⭐⭐⭐ Master' => 5,
                ])
                ->renderAsBadges([
                    1 => 'success',
                    2 => 'warning', 
                    3 => 'info',
                    4 => 'primary',
                    5 => 'danger',
                ]),
                
            ImageField::new('imageName')
                ->setBasePath('/uploads/recipes')
                ->setUploadDir('public/uploads/recipes')
                ->setColumns('col-sm-4')
                ->hideOnForm(),
                
            AssociationField::new('ingredients')
                ->setColumns('col-sm-12')
                ->hideOnIndex()
                ->setCrudController(IngredientCrudController::class),
                
            DateTimeField::new('createdAt')
                ->hideOnForm()
                ->setFormat('MMM d, yyyy HH:mm'),
        ];
    }
}
