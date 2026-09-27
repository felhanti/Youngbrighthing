<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Validator\Constraints\Image;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'attr' => [
                    'placeholder' => 'Nom du produit',
                    'autocomplete' => 'off'
                ],
                'label' => 'Nom',
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'Description détaillée du produit',
                    'rows' => 4
                ],
                'label' => 'Description',
            ])
            ->add('price', MoneyType::class, [
                'attr' => [
                    'placeholder' => '0.00',
                    'min' => 0,
                    'step' => '0.01'
                ],
                'currency' => false,
                'label' => 'Prix',
            ])
            ->add('available', CheckboxType::class, [
                'label' => 'Disponible',
                'required' => false,
                'attr' => [
                    'role' => 'switch'
                ],
            ])
            ->add('size', TextType::class, [
                'attr' => [
                    'placeholder' => 'Ex: XL, 42, M...'
                ],
                'label' => 'Taille',
            ])
            ->add('add_date', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'max' => (new \DateTime())->format('Y-m-d')  // Empêche les dates futures
                ],
                'label' => 'Date d\'ajout',
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
                'label' => 'Catégories',
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image du produit',
                'required' => false,
                'attr' => ['accept' => 'image/jpeg,image/png,image/webp'],
                // Le fichier atterrit dans public/ : n'accepter que de vraies images,
                // sinon un script .php envoyé ici serait exécutable par le serveur.
                'constraints' => [
                    new Image(
                        maxSize: '8M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Formats acceptés : JPEG, PNG ou WebP.',
                    ),
                ],
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
