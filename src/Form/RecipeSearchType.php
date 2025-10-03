<?php

namespace App\Form;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipeSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('query', TextType::class, [
                'label' => 'Search recipes',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Enter recipe name, ingredient, or description...',
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500'
                ]
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'All categories',
                'required' => false,
                'query_builder' => function (CategoryRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.name', 'ASC');
                },
                'attr' => [
                    'class' => 'w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500'
                ]
            ])
            ->add('difficulty', ChoiceType::class, [
                'label' => 'Difficulty',
                'choices' => [
                    'Any difficulty' => null,
                    'Very Easy' => 1,
                    'Easy' => 2,
                    'Medium' => 3,
                    'Hard' => 4,
                    'Very Hard' => 5,
                ],
                'required' => false,
                'placeholder' => false,
            ])
            ->add('maxPrepTime', IntegerType::class, [
                'label' => 'Max prep time (minutes)',
                'required' => false,
            ])
            ->add('minRating', NumberType::class, [
                'label' => 'Minimum rating',
                'required' => false,
                'scale' => 1,
            ])
            ->add('dietaryRestrictions', ChoiceType::class, [
                'label' => 'Dietary preferences',
                'choices' => [
                    'Vegetarian' => 'vegetarian',
                    'Vegan' => 'vegan',
                    'Gluten-free' => 'gluten_free',
                    'Dairy-free' => 'dairy_free',
                    'Low-carb' => 'low_carb',
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Search Recipes',
                'attr' => [
                    'class' => 'w-full bg-orange-500 hover:bg-orange-600 text-white font-medium py-2 px-4 rounded-lg transition-colors'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}