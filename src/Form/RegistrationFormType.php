<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Profile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class RolesToArrayTransformer implements DataTransformerInterface
{
    public function transform(mixed $roles): mixed
    {
        // Transforms the array of roles into a string to display in the form
        if (empty($roles)) {
            return null;
        }

        if (in_array('ROLE_SUPERADMIN', $roles)) {
            return 'SuperAdmin';
        } elseif (in_array('ROLE_ADMIN', $roles)) {
            return 'Admin';
        }

        return 'Gebruiker';
    }

    public function reverseTransform(mixed $roleLabel): mixed
    {
        // Transforms the form choice back to an array of roles
        switch ($roleLabel) {
            case 'SuperAdmin':
                return ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_SUPERADMIN'];
            case 'Admin':
                return ['ROLE_USER', 'ROLE_ADMIN'];
            case 'Gebruiker':
            default:
                return ['ROLE_USER'];
        }
    }
}




class RegistrationFormType extends AbstractType
{
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $roleChoices = [
            'Gebruiker' => 'Gebruiker',
        ];

        // Check if the current user can assign the "Admin" role
        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $roleChoices['Admin'] = 'Admin';
        }

        // Check if the current user can assign the "SuperAdmin" role
        if ($this->authorizationChecker->isGranted('ROLE_SUPERADMIN')) {
            $roleChoices['SuperAdmin'] = 'SuperAdmin';
        }

        $builder
            ->add('username', TextType::class, [
                'label' => 'Inlognaam gebruiker'
            ])
            ->add('plainPassword', TextType::class, [
                'label' => 'Tijdelijk wachtwoord',
                'data' => '#K@tt3nkwaad!',
                'disabled' => true,
            ])
            ->add('location', TextType::class, [
                'label' => 'Locatie',
                'data' => 'Alkmaar',
                'disabled' => true,
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rol',
                'choices' => $roleChoices,
                'multiple' => false,
                'expanded' => false,
            ])
            ->add('profile', ProfileRegFormType::class, [
                'label' => 'Profiel',
            ]);
         $builder->get('roles')->addModelTransformer(new RolesToArrayTransformer());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'constraints' => [
                new UniqueEntity(fields: ['username'])
            ],
        ]);
    }
}
