<?php

namespace App\Form;

use App\Entity\GlobalSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class FavIconFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('favIcon', FileType::class, [
                'label' => ' ',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new Image([
                        'maxHeight' => '32',
                        'maxHeightMessage' => 'height is te groot',
                        'maxWidth' => '32',
                        'maxWidthMessage' => 'Width is te groot',
                    ])
                ],

            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GlobalSettings::class,
        ]);
    }
}
