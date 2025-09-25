<?php

namespace App\Controller;

use App\Entity\Projects;
use App\Form\ProjectFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProjectsController extends AbstractController
{
    #[Route('/admin/projects', name: 'projects')]
    public function Projects (EntityManagerInterface $em, Request $request): Response
    {
        $projects = $em->getRepository(Projects::class) ->findAll();

        $newProject = new Projects();

        $form = $this->createForm(ProjectFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // the startweek is a key value pair like {'year':$year, 'Week':$Week}
            $startWeek = $form->get('startWeek')->getData();

            // set all the variables
            $newProject->setName($form->get('name')->getData());
            $newProject->setStartYear($startWeek['year']);
            $newProject->setStartWeek($startWeek['week']);
            $newProject->setEndWeek(null);
            $newProject->setEndYear(null);
            $newProject->setActive(true);

            //sends the newly created project to the database
            $em->persist($newProject);
            $em->flush();

            return $this->redirectToRoute('projects');
        }

        
        return $this->render( 'admin/projects.html.twig',[
            'projects' => $projects,
            'projectForm' => $form->createView(),
        ]);
    }
    
    #[Route('/admin/finishProject', name: 'finish_project')]
    public function Finish_Project (EntityManagerInterface $em, Request $request): JsonResponse
    {
        $data = json_decode( $request->getContent(), true );

        $id = $data['id'];
        $endYear = $data['eYear'];
        $endWeek = $data['eWeek'];

        $project = $em->getRepository(Projects::class) ->find($id);

        $project->setEndYear($endYear);
        $project->setEndWeek($endWeek);
        $project->setActive(false);

        $em->persist($project);
        $em->flush();

        return new JsonResponse(['status' => 'success']);
    }

}
