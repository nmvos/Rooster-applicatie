<?php
// src/Form/MeeloperRegistrationFormType.php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Profile;

class MeeloperRegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('name', TextType::class, [
            'label' => 'Naam',
            'required' => true,
        ])

        // Add the 'removeDate' field with 7, 14, and 21 days options
        ->add('removeDate', ChoiceType::class, [
            'label' => 'Verwijder',
            'choices' => [
                'over 7 dagen' => (new \DateTime())->modify('+7 days'),
                'over 14 dagen' => (new \DateTime())->modify('+14 days'),
                'over 21 dagen' => (new \DateTime())->modify('+21 days'),
            ],
            'expanded' => true, // This will display the choices as checkboxes
            'multiple' => false, // Ensure only one option can be selected
        ]);
            
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Define the data class if needed or set to array if it's not mapped to an entity
            'data_class' => Profile::class,
        ]);
    }
}
