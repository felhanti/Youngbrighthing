<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
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
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'required' => $options['require_password'],
                'label' => 'Mot de passe',
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => $options['require_password']
                        ? 'Mot de passe du compte'
                        : 'Laissez vide pour garder le mot de passe actuel',
                ],
                'constraints' => array_filter([
                    $options['require_password'] ? new NotBlank(message: 'Un mot de passe est obligatoire.') : null,
                    new Length(min: 8, max: 4096, minMessage: 'Le mot de passe doit faire au moins {{ limit }} caractères'),
                ]),
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'placeholder' => 'Entrez le nom'
                ]
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'placeholder' => 'Entrez le prénom'
                ]
            ])
            ->add('birthDate', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'label' => 'Date de naissance',
            ])
            ->add('adress', TextType::class, [
                'label' => 'Adresse',
                'attr' => [
                    'placeholder' => 'Entrez votre adresse'
                ]
            ])
            ->add('cp', TextType::class, [
                'label' => 'Code Postal',
                'attr' => [
                    'placeholder' => 'Entrez votre code postal',
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'attr' => [
                    'placeholder' => 'Entrez votre ville',
                ],
            ])
            ->add('country', CountryType::class, [
                'label' => 'Pays',
                'placeholder' => 'Choisissez un pays', // Affiche une option vide
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            // true à la création d'un compte, false en édition (vide = inchangé).
            'require_password' => false,
        ]);
        $resolver->setAllowedTypes('require_password', 'bool');
    }
}