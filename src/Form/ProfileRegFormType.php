<?php

namespace App\Form;

use App\Entity\Profile;
use App\Entity\GlobalSettings;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ProfileRegFormType extends AbstractType
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Fetch the GlobalSettings entity (assuming there's only one)
        $globalSettings = $this->entityManager->getRepository(GlobalSettings::class)->findOneBy([]);
        
        // Get the departments array from the GlobalSettings entity
        $departments = $globalSettings ? $globalSettings->getDepartments() : [];

        sort($departments); // Sort the departments alphabetically

        // Build the form
        $builder
            ->add('name', TextType::class, [
                'label' => 'Volledige naam',
            ])
            ->add('department', ChoiceType::class, [
                'label' => 'Afdeling',
                'choices' => array_combine($departments, $departments), // use department names as both keys and values
                'expanded' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Profile::class,
        ]);
    }
}
