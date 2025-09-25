<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\GlobalSettings;
use App\Entity\ConceptRooster;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;


class ConceptRoosterController extends AbstractController
{
    #[Route('/admin/concept_rooster', name: 'conceptRooster')]
    public function index(EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_SUPERADMIN')){
            return $this->render('security/403_page.html.twig');
        }
        $settings = $em->getRepository(GlobalSettings::class) -> findOneBy([]);
        $users = $em->getRepository(User::class)->findAll();

        foreach ($users as $user) {
            $existingConcept = $em->getRepository( ConceptRooster::class )->findOneBy( ['user' => $user]);
            if (!$existingConcept){
                $defaultDepartment = $user->getProfile()->getDepartment();
                $baseConcept = [
                    'schedule' => [
                        'monday' => [ 'department'=> $defaultDepartment, 'morning' => [ 'scheduled' => false, 'attended' => '' ], 'afternoon' => [ 'scheduled' => false, 'attended' => '' ], 'different_timing' => [ 'scheduled' => null, 'attended' => '' ] ],
                        'tuesday' =>  [ 'department'=> $defaultDepartment, 'morning' => [ 'scheduled' => false, 'attended' => '' ], 'afternoon' => [ 'scheduled' => false, 'attended' => '' ], 'different_timing' => [ 'scheduled' => null, 'attended' => '' ] ],
                        'wednesday' =>  [ 'department'=> $defaultDepartment, 'morning' => [ 'scheduled' => false, 'attended' => '' ], 'afternoon' => [ 'scheduled' => false, 'attended' => '' ], 'different_timing' => [ 'scheduled' => null, 'attended' => '' ] ],
                        'thursday' =>  [ 'department'=> $defaultDepartment, 'morning' => [ 'scheduled' => false, 'attended' => '' ], 'afternoon' => [ 'scheduled' => false, 'attended' => '' ], 'different_timing' => [ 'scheduled' => null, 'attended' => '' ] ],
                        'friday' =>  [ 'department'=> $defaultDepartment, 'morning' => [ 'scheduled' => false, 'attended' => '' ], 'afternoon' => [ 'scheduled' => false, 'attended' => '' ], 'different_timing' => [ 'scheduled' => null, 'attended' => '' ] ],
                    ]
                ];
                $conceptRooster = new ConceptRooster;
                $conceptRooster->setUser($user);
                $conceptRooster->setBasic($baseConcept);
                $conceptRooster->setEven($baseConcept);
                $conceptRooster->setOdd($baseConcept);
                $em->persist( $conceptRooster );
                $em->flush();
            }
        }

        $qb = $em->createQueryBuilder();
        $qb->select('c')
        ->from(ConceptRooster::class, 'c')
        ->join('c.user', 'u')
        ->join('u.profile', 'p')
        ->orderBy('p.name', 'ASC');

        $conceptRoosters = $qb->getQuery()->getResult();

        return $this->render('admin/concept_rooster.html.twig', 
        [   
            'conceptRoosters' => $conceptRoosters,
            'settings' => $settings
        ]);
    }

    #[Route('/admin/concept_rooster/updateDepartment', name: 'concept_update_department')]
    public function conceptUpdateDepartment (EntityManagerInterface $em, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $userId = $data['userId'];
        $day = $data['day'];
        $type = $data['type'];
        $value = $data['value'];

        $conceptRooster = $em->getRepository(ConceptRooster::class) -> findOneBy(['user' => $userId]);

        if ($type == 'basic'){
            $editConcept = $conceptRooster->getBasic();
            $editConcept['schedule'][$day]['department'] = $value;
            $conceptRooster->setBasic($editConcept);
        } elseif ($type == 'odd') {
            $editConcept = $conceptRooster->getOdd();
            $editConcept['schedule'][$day]['department'] = $value;
            $conceptRooster->setOdd($editConcept);
        } elseif ($type == 'even') {
            $editConcept = $conceptRooster->getEven();
            $editConcept['schedule'][$day]['department'] = $value;
            $conceptRooster->setEven($editConcept);
        }

        $em->persist( $conceptRooster );
        $em->flush();

        return new JsonResponse(['status' => 'success']);
    }

    #[Route('/admin/concept_rooster/updateTiming', name: 'concept_update_timing')]
    public function updateTiming (EntityManagerInterface $em, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $userId = $data['userId'];
        $day = $data['day'];
        $type = $data['type'];  
        $value = $data['value'];

        $conceptRooster = $em->getRepository(ConceptRooster::class) -> findOneBy(['user' => $userId]);

        if ($type == 'basic'){
            $editConcept = $conceptRooster->getBasic();
            if ($value == 'whole_day'){
                $editConcept['schedule'][$day]['morning']['scheduled'] = true;
                $editConcept['schedule'][$day]['afternoon']['scheduled'] = true;
                $editConcept['schedule'][$day]['different_timing']['scheduled'] = null;
            } elseif ($value == "morning"){
                $editConcept['schedule'][$day]['morning']['scheduled'] = true;
                $editConcept['schedule'][$day]['afternoon']['scheduled'] = false;
                $editConcept['schedule'][$day]['different_timing']['scheduled'] = null;
            } elseif ($value == "afternoon"){
                $editConcept['schedule'][$day]['morning']['scheduled'] = false;
                $editConcept['schedule'][$day]['afternoon']['scheduled'] = true;
                $editConcept['schedule'][$day]['different_timing']['scheduled'] = null;
            } elseif ($value == 'none'){
                $editConcept['schedule'][$day]['morning']['scheduled'] = false;
                $editConcept['schedule'][$day]['afternoon']['scheduled'] = false;
                $editConcept['schedule'][$day]['different_timing']['scheduled'] = null;
            }
            $conceptRooster->setBasic($editConcept);
        } elseif ($type == 'odd') {
            $editConcept = $conceptRooster->getOdd();
            if ($value == 'whole_day'){
                $editConcept['schedule'][$day]['morning']['scheduled'] = true;
                $editConcept['schedule'][$day]['afternoon']['scheduled'] = true;
                $editConcept['schedule'][$day]['different_timing']['scheduled'] = null;
            } elseif ($value == "morning"){
                $editConcept['schedule'][$day]['morning']['scheduled'] = true;
                $editConcept['schedule'][$day]['afternoon']['scheduled'] = false;
                $editConcept['schedule'][$day]['different_timing']['scheduled'] = null;
            } elseif ($value == "afternoon"){
                $editConcept['schedule'][$day]['morning']['scheduled'] = false;
                $editConcept['schedule'][$day]['afternoon']['scheduled'] = true;
                $editConcept['schedule'][$day]['different_timing']['scheduled'] = null;
            } elseif ($value == 'none'){
                $editConcept['schedule'][$day]['morning']['scheduled'] = false;
                $editConcept['schedule'][$day]['afternoon']['scheduled'] = false;
                $editConcept['schedule'][$day]['different_timing']['scheduled'] = null;
            }
            $conceptRooster->setOdd($editConcept);
        } elseif ($type == 'even') {
            $editConcept = $conceptRooster->getEven();
            if ($value == 'whole_day'){
                $editConcept['schedule'][$day]['morning']['scheduled'] = true;
                $editConcept['schedule'][$day]['afternoon']['scheduled'] = true;
                $editConcept['schedule'][$day]['different_timing']['scheduled'] = null;
            } elseif ($value == "morning"){
                $editConcept['schedule'][$day]['morning']['scheduled'] = true;
                $editConcept['schedule'][$day]['afternoon']['scheduled'] = false;
                $editConcept['schedule'][$day]['different_timing']['scheduled'] = null;
            } elseif ($value == "afternoon"){
                $editConcept['schedule'][$day]['morning']['scheduled'] = false;
                $editConcept['schedule'][$day]['afternoon']['scheduled'] = true;
                $editConcept['schedule'][$day]['different_timing']['scheduled'] = null;
            } elseif ($value == 'none'){
                $editConcept['schedule'][$day]['morning']['scheduled'] = false;
                $editConcept['schedule'][$day]['afternoon']['scheduled'] = false;
                $editConcept['schedule'][$day]['different_timing']['scheduled'] = null;
            }
            $conceptRooster->setEven($editConcept);
        }

        $em->persist( $conceptRooster );
        $em->flush();

        return new JsonResponse(['status' => 'success']);
    }

    #[Route('/admin/concept_rooster/updateDifferentTiming', name: 'concept_update_different_timing')]
    public function updateDifferentTiming (EntityManagerInterface $em, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $userId = $data['userId'];
        $day = $data['day'];
        $type = $data['type'];
        $value = $data['value'];

        $conceptRooster = $em->getRepository(ConceptRooster::class) -> findOneBy(['user' => $userId]);

        if ($type == 'basic'){
            $editConcept = $conceptRooster->getBasic();
            $editConcept['schedule'][$day]['morning']['scheduled'] = false;
            $editConcept['schedule'][$day]['afternoon']['scheduled'] = false;
            $editConcept['schedule'][$day]['different_timing']['scheduled'] = $value;
            $conceptRooster->setBasic($editConcept);
        } elseif ($type == 'odd') {
            $editConcept = $conceptRooster->getOdd();
            $editConcept['schedule'][$day]['morning']['scheduled'] = false;
            $editConcept['schedule'][$day]['afternoon']['scheduled'] = false;
            $editConcept['schedule'][$day]['different_timing']['scheduled'] = $value;
            $conceptRooster->setOdd($editConcept);
        } elseif ($type == 'even') {
            $editConcept = $conceptRooster->getEven();
            $editConcept['schedule'][$day]['morning']['scheduled'] = false;
            $editConcept['schedule'][$day]['afternoon']['scheduled'] = false;
            $editConcept['schedule'][$day]['different_timing']['scheduled'] = $value;
            $conceptRooster->setEven($editConcept);
        }

        $em->persist( $conceptRooster );
        $em->flush();

        return new JsonResponse(['status' => 'success']);
    }
}
