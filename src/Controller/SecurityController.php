<?php
// src/Controller/SecurityController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
{
    if ($this->getUser()) {
        $userRoles = $this->getUser()->getRoles();

        if (in_array('ROLE_SUPERADMIN', $userRoles)) {
            return $this->redirectToRoute('settings');
        } elseif (in_array('ROLE_ADMIN', $userRoles)) {
            return $this->redirectToRoute('weekrooster');
        } elseif (in_array('ROLE_USER', $userRoles)) {
            return $this->redirectToRoute('profile_view', ['id' => $this->getUser()->getId()]);
        }
    }
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/logout', name: 'logout')]
    public function logout(): void
    {
        throw new \LogicException('This method should not be called directly.');
    }

    #[Route(path: '/403', name: '403')]
    public function forbidden(Request $request): Response
    {
        return $this->render('security/403_page.html.twig');
    }

    #[Route(path: '/404', name: '404')]
    public function notfound(Request $request): Response
    {
        return $this->render('security/404_page.html.twig');
    }
}
