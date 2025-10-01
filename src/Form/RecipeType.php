<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Ingredient;
use App\Entity\Recipe;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Component\Validator\Constraints\Image as ImageConstraint;

class RecipeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'attr' => ['placeholder' => 'Chocolate Cake'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('instructions', TextareaType::class, [
                'label' => 'Instructions',
                'attr' => ['rows' => 6],
            ])
            ->add('prepTime', IntegerType::class, [
                'label' => 'Preparation time (min)',
                'required' => false,
            ])
            ->add('difficulty', ChoiceType::class, [
                'label' => 'Difficulty',
                'required' => false,
                'choices' => [
                    '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5,
                ],
                'expanded' => true,   // radios
                'multiple' => false,
                'placeholder' => false,
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Category',
                'placeholder' => '— Select —',
            ])
            ->add('ingredients', EntityType::class, [
                'class' => Ingredient::class,
                'choice_label' => 'name',
                'label' => 'Ingredients',
                'multiple' => true,
                'expanded' => true,
                'by_reference' => false,
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Image',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => false,
                'image_uri' => true,
                'asset_helper' => true,
                'attr' => [
                    'accept' => 'image/*',
                    'class'  => 'block w-full text-sm text-zinc-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100',
                ],
                'constraints' => [
                    new ImageConstraint([
                        'maxSize' => '5M',
                        'mimeTypesMessage' => 'Please upload a valid image.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recipe::class,
        ]);
    }
}
