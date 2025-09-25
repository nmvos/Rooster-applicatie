<?php

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\User;
use App\Entity\Schedule;
use App\Entity\GlobalSettings;
use App\Entity\ConceptRooster;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Psr\Log\LoggerInterface;

use DateTime;
use DateInterval;
use DatePeriod;

class ProfileController extends AbstractController
{
    #[Route('/profile/{id}', name: 'profile_view')]
    public function view(int $id, EntityManagerInterface $em, LoggerInterface $logger): Response
    {
        $this->ensureThuiswerkenColorExists($em);
        $this->ensureEerderWegColorExists($em);
        $this->ensureNietVerwachtColorExists($em);
        $profile = $em->getRepository(Profile::class)->find($id);
        $user = $profile ? $profile->getUser() : null;
        $settings = $em->getRepository(GlobalSettings::class) -> findOneBy([]);

        if (!$profile || !$user) {
            throw $this->createNotFoundException('The profile does not exist');
        }

        // Check if current user is admin or if the profile belongs to the current user
        if (!$this->isGranted('ROLE_ADMIN') && $profile->getUser() !== $this->getUser()) {
            $logger->error('Normale gebruiker {userName} probeerde op een andere profiel te komen', ['userName' => $this->getUser()->getProfile()->getName()]);
            throw new AccessDeniedException('Access denied');
        }

        $now = new \DateTime();
        $weekNumber = $now->format('W');
        $year = $now->format('Y');

        $logger->info('Het profiel van {userName} wordt geladen', ['userName' => $profile->getName()]);

        // Fetch or create the schedule for the current week and year
        $schedule = $em->getRepository(Schedule::class)->findOneBy([
            'user' => $user,
            'weekNumber' => $weekNumber,
            'year' => $year
        ]);
     
        return $this->render('profile/profile.html.twig', [
            'profile' => $profile,
            'user' => $user,
            'schedule' => $schedule,
            'weekNumber' => $weekNumber,
            'year' => $year,
            'settings' => $settings
        ]);
    }

    #[Route('/profile/{id}/{year}/{weekNumber}', name: 'profile_view_other_week')]
    public function view_other_week(int $id, int $year, int $weekNumber, EntityManagerInterface $em, LoggerInterface $logger): Response
    {
        $profile = $em->getRepository(Profile::class)->find($id);
        $user = $profile ? $profile->getUser() : null;
        $settings = $em->getRepository(GlobalSettings::class) -> findOneBy([]);
        
        $now = new DateTime();

        
        // Check if the year has 53 weeks
        $lastWeekOfYear = (new DateTime())->setISODate($year, 53)->format('W') === '53';
        // Check if current user is admin or if the profile belongs to the current user
        if (!$this->isGranted('ROLE_ADMIN') && $profile->getUser() !== $this->getUser()) {
            $logger->error('Normale gebruiker {userName} probeerde op een andere profiel te komen via Weekselector', ['userName' => $this->getUser()->getProfile()->getName()]);
            throw new AccessDeniedException('Access denied');
        }
        if (($weekNumber > 52 && !$lastWeekOfYear) || ($weekNumber > 53 && $lastWeekOfYear)) {
            // Handle the case when the week number is out of range
            $logger->error('Gebruiker {userName} probeerde outofbounds te gaan via Weekselector', ['userName' => $this->getUser()->getProfile()->getName()]);
            return $this->redirectToRoute('404');  // Redirect to error or fallback logic
        }
        // Fetch or create the schedule for the current week and year
        $schedule = $em->getRepository(Schedule::class)->findOneBy([
            'user' => $user,
            'weekNumber' => $weekNumber,
            'year' => $year
        ]);

        return $this->render('profile/profile.html.twig', [
            'profile' => $profile,
            'user' => $user,
            'schedule' => $schedule,
            'weekNumber' => $weekNumber,
            'year' => $year,
            'settings' => $settings
        ]);
    }

    #[Route('/profile/{id}/edit-department', name: 'profile_edit_department', methods: ['POST'])]
    public function editDepartment(int $id, Request $request, EntityManagerInterface $em, LoggerInterface $logger): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $profileId = $data['profileId'];
        $department = $data['department'];

        $profile = $em->getRepository(Profile::class)->find($profileId);
        $currentDepartment = $profile->getDepartment();
        $userId = $profile->getUser()->getId();
        $days = array("monday", "tuesday", "wednesday", "thursday", "friday");

        // get the conceptRoster so we can save the department changes on the concept roosters
        $conceptRooster = $em->getRepository(ConceptRooster::class) -> findOneBy(['user' => $userId]);
        $basicConceptRooster = $conceptRooster->getBasic();
        $evenConceptRooster = $conceptRooster->getEven();
        $oddConceptRooster = $conceptRooster->getOdd();


        if (!$profile) {
            throw $this->createNotFoundException('The profile does not exist');
        }

        // Check if current user is admin
        if (!$this->isGranted('ROLE_ADMIN')) {
            $logger->error('Normale gebruiker {userName} probeerde op een profiels department aan te passen', ['userName' => $this->getUser()->getProfile()->getName()]);
            throw new AccessDeniedException('Access denied');
        }

        if ($department && $department != $currentDepartment) {
            foreach($days as $day){
                if ($basicConceptRooster['schedule'][$day]['department'] == $currentDepartment){
                    $basicConceptRooster['schedule'][$day]['department'] = $department;
                }
                if ($evenConceptRooster['schedule'][$day]['department'] == $currentDepartment){
                    $evenConceptRooster['schedule'][$day]['department'] = $department;
                }
                if ($oddConceptRooster['schedule'][$day]['department'] == $currentDepartment){
                    $oddConceptRooster['schedule'][$day]['department'] = $department;
                }
            }
            
            $conceptRooster->setBasic($basicConceptRooster);
            $conceptRooster->setEven($evenConceptRooster);
            $conceptRooster->setOdd($oddConceptRooster);
            $em->persist($conceptRooster);
            $profile->setDepartment($department);
            $em->persist($profile);
            $em->flush();
        }

        return new JsonResponse(['status' => 'success']);
    }

    #[Route('/profile/{id}/reset-password', name: 'user_password_reset', methods: ['POST'])]
    public function resetPassword(int $id, EntityManagerInterface $em, UserPasswordHasherInterface $passwordEncoder, LoggerInterface $logger): Response
    {
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('The user does not exist');
        }

        // Check if current user is admin
        if (!$this->isGranted('ROLE_ADMIN')) {
            $logger->error('Normale gebruiker {userName} probeerde op een profiels wachtwoord te resetten', ['userName' => $this->getUser()->getProfile()->getName()]);
            throw new AccessDeniedException('Access denied');
        }

        // Set default password
        $defaultPassword = '#K@tt3nkwaad!';
        $encodedPassword = $passwordEncoder->hashPassword($user, $defaultPassword);
        $user->setPassword($encodedPassword);
        $user->setPasswordReset(true);
        $em->persist($user);
        $em->flush();

        return $this->redirectToRoute('profile_view', ['id' => $user->getProfile()->getId()]);
    }

    #[Route('/profile/{id}/delete', name: 'profile_delete', methods: ['POST'])]
    public function deleteProfile(int $id, EntityManagerInterface $em, LoggerInterface $logger): Response
    {
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('The profile does not exist');
        }

        if (!$this->isGranted('ROLE_ADMIN')) {
            $logger->error('Normale gebruiker {userName} probeerde op een account te verwijderen', ['userName' => $this->getUser()->getProfile()->getName()]);
            throw new AccessDeniedException('Access denied');
        }


        $em->remove($user);
        $em->flush();

        return $this->redirectToRoute('profiles');
    }

    public function loadRooster(int $weekNumber, int $year, int $id, EntityManagerInterface $em, LoggerInterface $logger): Response
    {
        $profile = $em->getRepository(Profile::class)->find($id);
        $user = $profile ? $profile->getUser() : null;
        $settings = $em->getRepository(GlobalSettings::class) -> findOneBy([]);

        // Check if current user is admin or if the profile belongs to the current user
        if (!$this->isGranted('ROLE_ADMIN') && $profile->getUser() !== $this->getUser()) {
            throw new AccessDeniedException('Access denied');
        }

        // Fetch the schedule for the given week, year and user
        $schedule = $em->getRepository(Schedule::class)->findOneBy([
            'user' => $user,
            'weekNumber' => $weekNumber,
            'year' => $year
        ]);
         
        if ($schedule)
        {   
            $firstDayOfYear = new DateTime( "$year-01-01" );
            $firstMondayOfYear = $firstDayOfYear->modify( 'monday this week' );
            $weekStart = ( clone $firstMondayOfYear )->modify( "+{$weekNumber} week" )->modify( '-1 week' );
            $weekEnd = ( clone $weekStart )->modify( 'next sunday' );

            // Generate week dates in the desired format
            $period = new DatePeriod( $weekStart, new DateInterval( 'P1D' ), $weekEnd );
            $weekDates = [];
            foreach ( $period as $date ) {
                $weekDates[] = $date->format( 'd-m' );
            }
            
            
            //if the schedule exist renders the correct weekrooster
            return $this->render('profile/_profileWeekrooster.html.twig',
            [
                'weekNumber' => $weekNumber,
                'year' => $year,
                'schedule' => $schedule,
                'weekDates' => $weekDates,
                'settings' => $settings
            ]);
        }

        else
        {   //if the schedule doesn't exist renders there is no weekrooster for this week
            return $this->render('profile/_noSchedule.html.twig');
        }
    }

    #[Route('/profile/{id}/edit_profile_name', name: 'edit_profile_name', methods: ['POST'])]
    public function editProfileName(Request $request, EntityManagerInterface $em, int $id, LoggerInterface $logger): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $logger->error('Normale gebruiker {userName} probeerde op een account profielnaam aan te passen', ['userName' => $this->getUser()->getProfile()->getName()]);
            throw new AccessDeniedException('Access denied');
        }

        $data = json_decode($request->getContent(), true);

        $profileId = $id;
        $profileName = $data['value'];

        $profile = $em->getRepository(Profile::class)->find($profileId);

        $profile->setName($profileName);
        
        $em->persist($profile);
        $em->flush();

        return new JsonResponse(['status'=> 'success']);
    }

    #[Route('/profile/{id}/edit_user_name', name: 'edit_user_name', methods: ['POST'])]
    public function editUserName(Request $request, EntityManagerInterface $em, int $id, LoggerInterface $logger): JsonResponse
    {   
        if (!$this->isGranted('ROLE_ADMIN')) {
            $logger->error('Normale gebruiker {userName} probeerde op een account gebruikersnaam aan te passen', ['userName' => $this->getUser()->getProfile()->getName()]);
            throw new AccessDeniedException('Access denied');
        }

        $data = json_decode($request->getContent(), true);

        $profileId = $id;
        $userName = $data['value'];

        $profile = $em->getRepository(Profile::class)->find($profileId); 
        $user = $profile->getUser();
        
        $user->setUsername($userName);
        
        $em->persist($user);
        $em->flush();

        return new JsonResponse(['status'=> 'success']);
    }


   // Fallback voor thuiswerken
   public function ensureThuiswerkenColorExists(EntityManagerInterface $em): void
   {
   $repo = $em->getRepository(GlobalSettings::class);
   $settings = $repo->findOneBy([]); 

   if (!$settings) {
       $settings = new GlobalSettings(); 
   }

   $colors = $settings->getColors(); 

   if (!array_key_exists('Thuiswerken', $colors)) {
       $colors['Thuiswerken'] = '#4A90E2'; 
       $settings->setColors($colors); 

       $em->persist($settings); 
       $em->flush(); 
   }
   }

   // Fallback voor Eerder weg
    public function ensureEerderWegColorExists(EntityManagerInterface $em): void
    {
    $repo = $em->getRepository(GlobalSettings::class);
    $settings = $repo->findOneBy([]); 

    if (!$settings) {
        $settings = new GlobalSettings(); 
    }

    $colors = $settings->getColors(); 

    if (!array_key_exists('Eerder weg', $colors)) {
        $colors['Eerder weg'] = '#BB00BB'; 
        $settings->setColors($colors); 

        $em->persist($settings); 
        $em->flush(); 
    }
    }


     // Fallback voor Niet verwacht
   public function ensureNietVerwachtColorExists(EntityManagerInterface $em): void
   {
   $repo = $em->getRepository(GlobalSettings::class);
   $settings = $repo->findOneBy([]); 

   if (!$settings) {
       $settings = new GlobalSettings(); 
   }

   $colors = $settings->getColors(); 

   if (!array_key_exists('Niet verwacht', $colors)) {
       $colors['Niet verwacht'] = '#7c7a79'; 
       $settings->setColors($colors); 

       $em->persist($settings); 
       $em->flush(); 
   }
   }
}