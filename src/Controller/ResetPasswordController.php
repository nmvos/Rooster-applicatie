<?php
namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ResetPasswordController extends AbstractController
{
    private UserPasswordHasherInterface $passwordHasher;
    private EntityManagerInterface $entityManager;

    public function __construct(UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager)
    {
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;
    }

    #[Route('/password-reset', name: 'password_reset', methods: ['GET', 'POST'])]
    public function resetPassword(Request $request, LoggerInterface $logger): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Je moet ingelogd zijn om je wachtwoord te veranderen.');
        }

        if (!$user->isPasswordReset()) {
            throw $this->createNotFoundException('Wachtwoord veranderen is niet nodig.');
        }

        if ($request->isMethod('POST')) {
            $password = $request->request->get('password');
            $passwordConfirm = $request->request->get('password_confirm');

            // Define the password policy regex
            $passwordPolicy = '/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}$/';

            // Check if passwords match and validate against the policy
            if ($password === $passwordConfirm) {
                if (preg_match($passwordPolicy, $password)) {
                    // Hash and set the password if it meets the policy
                    $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
                    $user->setPassword($hashedPassword);
                    $user->setPasswordReset(false);

                    $this->entityManager->persist($user);
                    $this->entityManager->flush();

                    $this->addFlash('success', 'Je wachtwoord is succesvol veranderd.');

                    $logger->info('{userName} heeft zijn wachtwoord succesvol veranderd', ['userName' => $this->getUser()->getProfile()->getName()]);

                    return $this->redirectToRoute('login');
                } else {
                    $this->addFlash('error', 'Wachtwoord moet de volgende punten bevatten:
                    <ul style="margin: 0; padding-left: 20px;">
                        <li>Minimaal 8 tekens</li>
                        <li>Minimaal 1 hoofdletter</li>
                        <li>Minimaal 1 kleine letter</li>
                        <li>Minimaal 1 cijfer</li>
                        <li>Minimaal 1 speciaal teken</li>
                    </ul>');
                }
            } else {
                $this->addFlash('error', 'Wachtwoorden komen niet overeen.');
            }
        }

        return $this->render('security/reset_password.html.twig', [
            
                'exclude_sidebar' => true,
      
        ]);
    }
}
