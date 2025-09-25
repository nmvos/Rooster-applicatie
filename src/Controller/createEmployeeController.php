<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Profile;
use App\Form\RegistrationFormType;
use App\Form\MeeloperRegistrationFormType;  // Assuming this is the correct form type for meeloper
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Psr\Log\LoggerInterface;

class createEmployeeController extends AbstractController
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/dashboard.html.twig');
    }

    #[Route('/admin/create_employee', name: 'create_employee', methods: ['GET', 'POST'])]
    public function createUser(Request $request, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $user = new User();
        $profile = new Profile();

        $user->setProfile($profile);
        $profile->setUser($user);

        // Create both forms
        $registrationForm = $this->createForm(RegistrationFormType::class, $user);
        $meeloperForm = $this->createForm(MeeloperRegistrationFormType::class, $profile);

        $registrationForm->handleRequest($request);
        $meeloperForm->handleRequest($request);

        $logger->info('{userName} laad Medewerker aanmaken', ['userName' => $this->getUser()->getProfile()->getName()]);

        // Handle Registration Form Submission
        if ($registrationForm->isSubmitted() && $registrationForm->isValid()) {
            $username = $registrationForm->get('username')->getData();
            $usernameLower = strtolower($username);

            $user->setUsername($usernameLower);
            $user->setPassword($this->passwordHasher->hashPassword($user, $registrationForm->get('plainPassword')->getData()));
            $user->setRoles($registrationForm->get('roles')->getData());
            $user->setLocation($registrationForm->get('location')->getData());
            $user->setPasswordReset(true);

            // Set department on profile
            $profile->setDepartment($registrationForm->get('profile')['department']->getData());

            $entityManager->persist($user);
            $entityManager->flush();

            $userId = $user->getId();

            $logger->info('{userName} maakt een nieuw medewerker aan genaamd {nieuw}', ['userName' => $this->getUser()->getProfile()->getName(), 'nieuw' => $user->getProfile()->getName()]);

            return $this->redirectToRoute('profile_view',['id' => $userId]);
        }

        // Handle Meeloper Form Submission
        if ($meeloperForm->isSubmitted() && $meeloperForm->isValid()) {

            $user->setUsername("geen");
            
            // Hardcoded password for meeloper form
            $user->setPassword($this->passwordHasher->hashPassword($user, 'd!TW@chtw00rd!$We!rd'));
            $user->setRoles(['ROLE_USER']);
            $user->setLocation('Alkmaar');
            $user->setPasswordReset(false);
        
            // Set department and remove date for meeloper
            $profile->setDepartment('Meedraaien');
            $profile->setRemoveDate($meeloperForm->get('removeDate')->getData());
        
            $entityManager->persist($user);
            $entityManager->flush();

            $logger->info('{userName} maakt een nieuw meeloper aan genaamd {nieuw}', ['userName' => $this->getUser()->getProfile()->getName(), 'nieuw' => $user->getProfile()->getName()]);
        
            return $this->redirectToRoute('schedule_employee');
        }
        
        return $this->render('admin/create_employee.html.twig', [
            'registrationForm' => $registrationForm->createView(),
            'meeloperForm' => $meeloperForm->createView(),
        ]);
    }
}
