<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez l\'email'
                ]
            ])
            ->add('roles', ChoiceType::class, [
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Administrateur' => 'ROLE_ADMIN'
                ],
                'multiple' => true,
                'expanded' => true,
                'label' => 'Rôles'
            ])
            // On retire le champ password du formulaire d'édition
            // car il nécessite un traitement spécial avec le hachage
            /*->add('password', PasswordType::class, [
                'mapped' => false,
                'required' => false
            ])*/
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le nom'
                ]
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le prénom'
                ]
            ])
            ->add('birthDate', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'label' => 'Date de naissance',
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'attr' => [
                'class' => 'needs-validation'
            ]
        ]);
    }
    public function addPasswordField(FormBuilderInterface $builder)
{
    $builder->add('plainPassword', PasswordType::class, [
        'mapped' => false,
        'required' => false,
        'attr' => [
            'class' => 'form-control',
            'placeholder' => 'Laissez vide pour ne pas changer'
        ],
        'constraints' => [
            new Length([
                'min' => 6,
                'minMessage' => 'Le mot de passe doit faire au moins {{ limit }} caractères',
            ])
        ],
    ]);
}
}