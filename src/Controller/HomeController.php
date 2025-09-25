<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;

use App\Entity\Notifications;
use Doctrine\ORM\EntityManagerInterface;

class HomeController extends AbstractController
{
    #[Route('/keep-alive', name: 'keep_alive', methods: ['POST'])]
    public function keepAlive(Request $request): Response
    {
        // Get the current session
        $session = $request->getSession();

        // Start the session if it's not already started
        if (!$session->isStarted()) {
            $session->start();
        }

        // Define the new expiration time (e.g., 5 minutes from now)
        $lifetime = 300; // 5 minutes in seconds
        $cookieParams = session_get_cookie_params(); // Get current cookie parameters

        // Create a response object to return
        $response = new Response('Session kept alive', Response::HTTP_OK);

        // Set the updated session cookie using Symfony's Cookie class
        $response->headers->setCookie(
            new Cookie(
                session_name(), 
                session_id(), 
                time() + $lifetime, // Updated expiration time
                $cookieParams['path'],
                $cookieParams['domain'],
                $cookieParams['secure'],
                $cookieParams['httponly']
            )
        );

        return $response;
    }
    
    #[Route('/', name: 'home')]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        // Handle login logic here
        if ($this->getUser()) {
            // Redirect to different paths based on roles
            if ($this->isGranted('ROLE_SUPERADMIN')) {
                return $this->redirectToRoute('admin');
            } elseif ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('weekrooster');
            } else {
                // Assuming 'profile_view' is your profile view route name with an 'id' parameter
                return $this->redirectToRoute('profile_view', ['id' => $this->getUser()->getProfile()->getId()]);
            }
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    public function loadChangelog(): Response
    {
         return $this->render('changelog/_changelog.html.twig');
    }

    public function loadNotifications(EntityManagerInterface $em): Response
    {
        $userId = $this->getUser()->getProfile()->getId();
        $allNotifications = $em->getRepository(Notifications::class)->findBy(['user' => $userId],['id'=>'DESC']);

        return $this-> render('notifications/_notifications.html.twig',[
            'allNotifications' => $allNotifications,
        ]);
    }

    #[Route('/NotificationSeen', name: 'NotificationSeen')]
    public function NotificationSeen(Request $request, EntityManagerInterface $em):JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $userId = $data['userId'];

        $allNotifications = $em->getRepository(Notifications::class)->findBy(['user' => $userId]);

        foreach($allNotifications as $notification){
            $notification->setSeen(true);
            $em->persist($notification);
            $em->flush();
        }

        return new JsonResponse(['status' => 'success']);
    }

    #[Route('/NotificationDelete', name: 'NotificationDelete')]
    public function NotificationDelete(Request $request, EntityManagerInterface $em):JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $notificationId = $data['notificationId'];
        
        $notification =  $em->getRepository(Notifications::class)->findOneBy(['id' => $notificationId]);

        $em->remove($notification);
        $em->flush();

        return new JsonResponse(['status' => 'success']);
    }

#[Route('/NotificationDeleteAll', name: 'NotificationDeleteAll')]
public function NotificationDeleteAll(EntityManagerInterface $em): JsonResponse
{
    $user = $this->getUser();
    if (!$user) {
        return new JsonResponse(['status' => 'error', 'message' => 'Not logged in'], 401);
    }
    $userId = $user->getProfile()->getId();

    $notifications = $em->getRepository(Notifications::class)->findBy(['user' => $userId]);
    foreach ($notifications as $notification) {
        $em->remove($notification);
    }
    $em->flush();

    return new JsonResponse(['status' => 'success']);
}

    #[Route('/logout', name: 'logout')]
    public function logout(): void
    {
        throw new \LogicException('This method should not be called directly.');
    }
}