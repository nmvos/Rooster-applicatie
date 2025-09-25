<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\User;
use App\Entity\Schedule;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Psr\Log\LoggerInterface;

class AssignRightsController extends AbstractController
{
    #[Route('/admin/assign_rights', name: 'assign_rights')]
    public function assignRights (EntityManagerInterface $em, Request $request, LoggerInterface $logger): Response
    {
        if ($this->isGranted('ROLE_SUPERADMIN')){
            $query = $em->createQuery(
                'SELECT u
                FROM App\Entity\User u
                JOIN u.profile p
                ORDER BY p.name ASC'
            );
            $users = $query->getResult();
            
            $logger->info('{userName} laad rechten instellen', ['userName' => $this->getUser()->getProfile()->getName()]);
            return $this->render( 'admin/assign_rights.html.twig',[
                'users' => $users
            ]);
        } else {
            $logger->error('Niet SuperAdmin {userName} probeerde op rechten instellen te komen', ['userName' => $this->getUser()->getProfile()->getName()]);
            return $this->render('security/403_page.html.twig');
        }
    }

    #[Route('/admin/assign_rights/edit-roles/{id}', name: 'user_edit_roles', methods: ['POST'])]
    public function editRoles( int $id, Request $request, EntityManagerInterface $em, LoggerInterface $logger): Response
    {
        // Fetch the user by ID
        $user = $em->getRepository(User::class)->find($id);
    
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
    
        // Get the selected role from the form
        $selectedRole = $request->request->get('roles');
    
        // Define the role hierarchy
        $roleHierarchy = [
            'ROLE_USER' => ['ROLE_USER'],
            'ROLE_ADMIN' => ['ROLE_USER', 'ROLE_ADMIN'],
            'ROLE_SUPERADMIN' => ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_SUPERADMIN']
        ];
    
        // Set the appropriate roles based on the selected role
        if (array_key_exists($selectedRole, $roleHierarchy)) {
            $user->setRoles($roleHierarchy[$selectedRole]);
        }
    
        // Persist and flush changes to the database
        $em->persist($user);
        $em->flush();

        $logger->info('{editor} veranderde de rechten van {user} naar {rechten}', ['editor' => $this->getUser()->getProfile()->getName(), 'user' => $user->getProfile()->getName(), 'rechten' => $selectedRole]);
    
        return $this->redirectToRoute('assign_rights');
    }
}