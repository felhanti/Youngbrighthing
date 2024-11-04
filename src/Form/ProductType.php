<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;


class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nom du produit',
                    'autocomplete' => 'off'
                ],
                'label' => 'Nom',
                'label_attr' => ['class' => 'form-label fw-semibold'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Description détaillée du produit',
                    'rows' => 4
                ],
                'label' => 'Description',
                'label_attr' => ['class' => 'form-label fw-semibold'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('price', MoneyType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0.00',
                    'min' => 0,
                    'step' => '0.01'
                ],
                'currency' => 'None',
                'label' => 'Prix',
                'label_attr' => ['class' => 'form-label fw-semibold'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('is_sold', CheckboxType::class, [
                'label' => 'Disponible',
                'required' => false,
                'label_attr' => [
                    'class' => 'form-check-label fw-semibold d-block'
                ],
                'row_attr' => [
                    'class' => 'form-check form-switch mb-5 p-0'
                ],
                'attr' => [
                    'class' => 'form-check-input ms-1',
                    'role' => 'switch'
                ],
            ])
            ->add('size', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: XL, 42, M...'
                ],
                'label' => 'Taille',
                'label_attr' => ['class' => 'form-label fw-semibold'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('add_date', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'max' => (new \DateTime())->format('Y-m-d')  // Empêche les dates futures
                ],
                'label' => 'Date d\'ajout',
                'label_attr' => ['class' => 'form-label fw-semibold'],
                'row_attr' => ['class' => 'mb-3']
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'label' => 'Catégories',
                'attr' => ['class' => 'd-flex flex-wrap gap-3 mb-3'],
                'label_attr' => ['class' => 'fw-bold'],

            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image du produit',
                'label_attr' => ['class' => 'form-label fw-semibold'],
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
