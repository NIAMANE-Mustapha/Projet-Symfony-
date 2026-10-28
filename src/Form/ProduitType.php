<?php

namespace App\Form; 

use App\Entity\Produit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du produit',
                'constraints' => [
                    new NotBlank(message: 'Le nom du produit est obligatoire.'),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('prix', NumberType::class, [
                'label' => 'Prix (€)',
                'scale' => 2,
                'constraints' => [
                    new NotBlank(message: 'Le prix est obligatoire.'),
                    new PositiveOrZero(message: 'Le prix ne peut pas être négatif.'),
                ],
            ])
            ->add('stock', IntegerType::class, [
                'label' => 'Stock disponible',
                'constraints' => [
                    new NotBlank(message: 'Le stock est obligatoire.'),
                    new PositiveOrZero(message: 'Le stock ne peut pas être négatif.'),
                ],
            ])
            ->add('photoFile', FileType::class, [
                'label' => 'Photo du produit',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '5M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Choisis une image JPG, PNG ou WEBP.',
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
        ]);
    }
}