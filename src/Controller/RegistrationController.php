<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Profile;
use App\Form\RegistrationFormType;
use App\Form\MeeloperRegistrationFormType;
use App\Form\ProfileRegFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('create_employee', name: 'create_employee', methods: ['GET', 'POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
{
    $user = new User();
    $profile = new Profile(); 

    $user->setProfile($profile);
    $profile->setUser($user);

    $form = $this->createForm(RegistrationFormType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        
        // Encode the password
        $user->setPassword($passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()));

        // Set roles
        $user->setRoles($form->get('roles')->getData());
        $user->setLocation($form->get('location')->getData());
        $user->setPasswordReset('true');

        // Set department on profile
        $profile->setDepartment($form->get('profile')['department']->getData());
        
        // Persist both user and profile
        $entityManager->persist($user);
        $entityManager->flush();

        // Redirect or perform further actions
        // return $this->redirectToRoute('admin');
    }

    return $this->render('create_employee.html.twig', [
        'registrationForm' => $form->createView(),
    ]);
}

}
