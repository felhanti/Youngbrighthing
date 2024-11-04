<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;


class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('name', TextType::class, [
            'attr' => ['class' => 'form-control', 'placeholder' => 'Nom du produit'],
        ])
        ->add('last', ChoiceType::class,[
            'choices' => [
                'Oui' => true,
                'Non' => false
        ],
             'label' => 'Last drop',
             'required' => true,
             'attr' => ['class' => 'form-select'],
         ]); 
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
