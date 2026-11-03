<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'account.nom',
                'constraints' => [
                    new NotBlank(message: 'Le nom est obligatoire.'),
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'account.prenom',
                'constraints' => [
                    new NotBlank(message: 'Le prénom est obligatoire.'),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'account.email',
                'constraints' => [
                    new NotBlank(message: 'L\'adresse email est obligatoire.'),
                    new Email(message: 'Veuillez saisir une adresse email valide.'),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => false,
                'first_options'  => [
                    'label' => 'account.password',
                    'help' => 'account.password_help',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'auth.password_confirm',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'Les deux mots de passe doivent correspondre.',
                'constraints' => [
                    new Length(
                        min: 6,
                        max: 4096,
                        minMessage: 'Le nouveau mot de passe doit comporter au moins {{ limit }} caractères.'
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}
