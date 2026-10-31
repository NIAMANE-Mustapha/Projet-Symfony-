<?php

namespace App\Form;

use App\Entity\Produit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'product.name_label',
                'constraints' => [
                    new NotBlank(message: 'Le nom du produit est obligatoire.'),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'product.description_label',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('prix', NumberType::class, [
                'label' => 'product.price_label',
                'scale' => 2,
                'constraints' => [
                    new NotBlank(message: 'Le prix est obligatoire.'),
                    new Positive(message: 'Le prix doit être supérieur à zéro.'),
                ],
            ])
            ->add('stock', IntegerType::class, [
                'label' => 'product.stock_label',
                'constraints' => [
                    new NotBlank(message: 'Le stock est obligatoire.'),
                    new PositiveOrZero(message: 'Le stock doit être un nombre positif ou nul.'),
                ],
            ])
            ->add('photoFile', FileType::class, [
                'label' => 'product.photo_label',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '5M',
                        mimeTypes: [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        mimeTypesMessage: 'Veuillez téléverser une image valide (JPG, PNG, WEBP).'
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
        ]);
    }
}
