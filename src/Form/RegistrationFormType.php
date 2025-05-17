<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Email;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez l\'email'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'L\'adresse email est obligatoire',
                    ]),
                    new Email([
                        'message' => 'Veuillez saisir une adresse email valide',
                    ]),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter les conditions générales d\'utilisation en cochant cette case',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-check-input'
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'new-password', 
                    'class' => 'form-control password-field', 
                    'placeholder' => 'Entrez un mot de passe'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le mot de passe est obligatoire',
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Votre mot de passe doit contenir au minimum {{ limit }} caractères',
                        'max' => 4096,
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).*$/',
                        'message' => 'Votre mot de passe doit contenir au moins une lettre minuscule, une lettre majuscule et un chiffre'
                    ]),
                ],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le nom'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le nom est obligatoire',
                    ]),
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez le prénom'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le prénom est obligatoire',
                    ]),
                ],
            ])
            ->add('birthDate', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'required' => true,
                'label' => 'Date de naissance',
                'attr' => [
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'La date de naissance est obligatoire',
                    ]),
                ],
            ])
            ->add('adress', TextType::class, [
                'label' => 'Adresse',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez votre adresse'
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'L\'adresse est obligatoire',
                    ]),
                ],
            ])
            ->add('cp', TextType::class, [
                'label' => 'Code Postal',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez votre code postal',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le code postal est obligatoire',
                    ]),
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez votre ville',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'La ville est obligatoire',
                    ]),
                ],
            ])
            ->add('country', CountryType::class, [
                'label' => 'Pays',
                'attr' => [
                    'class' => 'form-control',
                ],
                'placeholder' => 'Choisissez un pays',
                'constraints' => [
                    new NotBlank([
                        'message' => 'Le pays est obligatoire',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}