<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'auth.nom',
                'attr' => ['placeholder' => 'Dupont'],
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir votre nom.'),
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'auth.prenom',
                'attr' => ['placeholder' => 'Jean'],
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir votre prénom.'),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'auth.email',
                'attr' => ['placeholder' => 'jean.dupont@example.com'],
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir votre adresse e-mail.'),
                    new Email(message: 'Veuillez entrer une adresse e-mail valide.'),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'auth.password',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'auth.password_confirm',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'Les deux mots de passe doivent être identiques.',
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir un mot de passe.'),
                    new Length(
                        min: 6,
                        max: 4096,
                        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.'
                    ),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label' => 'J’accepte les conditions d’utilisation.',
                'constraints' => [
                    new IsTrue(message: 'Vous devez accepter les conditions.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}