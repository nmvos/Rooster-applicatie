<?php

namespace App\Controller;

use App\Entity\Profile;;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProfilesController extends AbstractController
{
    #[Route('/admin/profiles', name: 'profiles')]
    public function Profiles (EntityManagerInterface $em, Request $request): Response
    {
        $profiles = $em->getRepository(Profile::class)->findBy([],['name' => 'ASC' ]);

        return $this->render('admin/profiles.html.twig',[
            'profiles' => $profiles,
        ]);
    }


 #[Route('/profile/{id}/update', name: 'profile_update', methods: ['POST'])]
    public function updateProfile($id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $profile = $em->getRepository(Profile::class)->findOneBy(['user' => $id]);
        if (!$profile) {
            return new JsonResponse(['success' => false, 'message' => 'Profile not found'], 404);
        }

        $userName = $request->request->get('userName');
        $profileName = $request->request->get('profileName');
        $department = $request->request->get('department');

        if ($profileName === null || $department === null) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vul alle verplichte velden in.'
            ], 400);
        }

        $profile->setName($profileName);
        $profile->setDepartment($department);

        $user = $profile->getUser();
        if ($user && $userName) {
            $user->setUsername($userName);
        }

        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}