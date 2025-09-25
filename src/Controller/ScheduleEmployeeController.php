<?php
// src/Controller/ScheduleEmployeeController.php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Schedule;
use App\Entity\Profile;
use App\Entity\User;
use App\Entity\GlobalSettings;
use App\Entity\ConceptRooster;
use Psr\Log\LoggerInterface;

use DateTime;
use DateInterval;
use DatePeriod;

class ScheduleEmployeeController extends AbstractController
 {
    #[ Route( '/admin/schedule_employee', name: 'schedule_employee' ) ]
    public function currentWeekSchedules( EntityManagerInterface $em, Request $request, LoggerInterface $logger ): Response
    {
        $settings = $em->getRepository(GlobalSettings::class) -> findOneBy([]);
        $now = new DateTime();
        $currentWeekNumber = $now->format( 'W' );
        $currentYear = $now->format( 'Y' );
        $weekNumber = $now->modify('+1 week')->format( 'W' );
        $year = $now->format( 'Y' );

        $weekDays = ['monday','tuesday','wednesday','thursday','friday'];
        $schedulePartDays = ['morning', 'afternoon', 'different_timing'];

        $weekStart = ( clone $now )->modify( 'monday this week' );
        $weekEnd = ( clone $weekStart )->modify( 'next sunday' );
        $period = new DatePeriod( $weekStart, new DateInterval( 'P1D' ), $weekEnd );
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
        // Fetch the list of users ( assuming you have a User entity and repository )
        $users = $em->getRepository( User::class )->findAll();

        // Fetch all schedules for the current week and year after creating new ones
        $schedules = $em->getRepository( Schedule::class )
            ->createQueryBuilder('s')
            ->join('s.user', 'u')
            ->join('u.profile', 'p')
            ->where('s.weekNumber = :weekNumber')
            ->andWhere('s.year = :year')
            ->setParameter('weekNumber', $weekNumber)
            ->setParameter('year', $year)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();

        $logger->info('{userName} laad rooster inplannen', ['userName' => $this->getUser()->getProfile()->getName()]);
        
        // Get the highlight parameter from the request
        $highlight = $request->query->get('highlight');

        // Render the Twig template and pass the schedules and highlight parameter
        return $this->render( 'admin/schedule_employee.html.twig', [
            'schedules' => $schedules,
            'weekNumber' => $weekNumber,
            'year' => $year,
            'weekDates' => $weekDates,
            'settings' => $settings,
            'highlight' => $highlight,
        ] );
    }

    public function updateSchedule( Request $request, EntityManagerInterface $em, LoggerInterface $logger): Response
 {
        // Decode the JSON payload
        $data = json_decode( $request->getContent(), true );

        // Get the profile ID, day, time, and checked state from the payload
        $profileId = $data[ 'profileId' ];
        $day = $data[ 'day' ];
        $time = $data[ 'time' ];
        $isChecked = $data[ 'isChecked' ];
        $differentTimingChecked = $data[ 'differentTimingChecked' ];

        // Find the profile by ID
        $profile = $em->getRepository( Profile::class )->find( $profileId );
        if ( !$profile ) {
            return new JsonResponse( [ 'success' => false, 'message' => 'Profile not found' ], 404 );
        }

        // Get or create the schedule
        $schedule = $profile->getSchedule();
        if ( !$schedule ) {
            $schedule = new Schedule();
            $profile->setSchedule( $schedule );
            $schedule->setProfile( $profile );
            $em->persist( $schedule );
        }

        // Update the appropriate schedule field based on the day and time
        $scheduleSetter = 'set' . ucfirst( $day ) . ucfirst( $time );
        if ( method_exists( $schedule, $scheduleSetter ) ) {
            $schedule->$scheduleSetter( $isChecked );
        }

        // Update different timing if both beginTiming and endTiming are provided and differentTiming is checked
        $differentTimingSetter = 'set' . ucfirst( $day ) . 'DifferentTiming';
        if ( $differentTimingChecked ) {
            if ( isset( $data[ 'beginTiming' ] ) && isset( $data[ 'endTiming' ] ) ) {
                if ( method_exists( $schedule, $differentTimingSetter ) ) {
                    $schedule->$differentTimingSetter( $data[ 'beginTiming' ] . ' - ' . $data[ 'endTiming' ] );
                }
            }
        } else {
            // Set different timing to null if the checkbox is not checked
            if ( method_exists( $schedule, $differentTimingSetter ) ) {
                $schedule->$differentTimingSetter( null );
            }
        }

        // Save changes to the database
        $em->persist( $schedule );
        $em->flush();
        
        $logger->info('{userName} heeft een aanpassing gedaan aan een rooster', ['userName' => $this->getUser()->getProfile()->getName()]);

        // Return a success response
        return new JsonResponse( [ 'success' => true ] );
    }
    #[ Route( 'admin/schedule_employee/{year}/{weekNumber}', name: 'week_selector' ) ]

    public function view_other_week( int $year, int $weekNumber, EntityManagerInterface $em , SessionInterface $session, LoggerInterface $logger): Response
 {
        $check = $session->get('check', false);
        $settings = $em->getRepository(GlobalSettings::class) -> findOneBy([]);
        $now = new DateTime();
        $currentWeekNumber = $now->format( 'W' );
        $currentYear = $now->format( 'Y' );
        $weekDays = ['monday','tuesday','wednesday','thursday','friday'];
        $schedulePartDays = ['morning', 'afternoon', 'different_timing'];
        $lastWeekOfYear = (new DateTime())->setISODate($year, 53)->format('W') === '53';
    
        if (($weekNumber > 52 && !$lastWeekOfYear) || ($weekNumber > 53 && $lastWeekOfYear)) {
            // Handle the case when the week number is out of range
            return $this->redirectToRoute('404');  // Redirect to error or fallback logic
        }
        if ($check){
            $schedules = $em->getRepository( Schedule::class )
            ->createQueryBuilder('s')
            ->join('s.user', 'u')
            ->join('u.profile', 'p')
            ->where('s.weekNumber = :weekNumber')
            ->andWhere('s.year = :year')
            ->setParameter('weekNumber', $weekNumber)
            ->setParameter('year', $year)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();

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
        $session->set('check', false);
        return $this->render( 'admin/schedule_employee.html.twig', [
            'schedules' => $schedules,
            'weekNumber' => $weekNumber,
            'year' => $year,
            'weekDates' => $weekDates,
            'settings' => $settings
        ] );
        }
        $users = $em->getRepository( User::class )->findAll();

        // Fetch all schedules for the current week and year
        $schedules = $em->getRepository( Schedule::class )
            ->createQueryBuilder('s')
            ->join('s.user', 'u')
            ->join('u.profile', 'p')
            ->where('s.weekNumber = :weekNumber')
            ->andWhere('s.year = :year')
            ->setParameter('weekNumber', $weekNumber)
            ->setParameter('year', $year)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();

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

        $logger->info('{userName} laad rooster inplannen voor jaar {year} week {week}', ['userName' => $this->getUser()->getProfile()->getName(), 'year' => $year, 'week' => $weekNumber ]);

        $session->set('check', false);
        return $this->render( 'admin/schedule_employee.html.twig', [
            'schedules' => $schedules,
            'weekNumber' => $weekNumber,
            'year' => $year,
            'weekDates' => $weekDates,
            'settings' => $settings
        ] );
    }

    #[ Route( '/admin/update_scheduled_department', name: 'update_scheduled_department' ) ]
    public function updateScheduledDepartment( Request $request, EntityManagerInterface $em , LoggerInterface $logger): Response
    {
        // Decode the JSON payload
        $data = json_decode($request->getContent(), true);

        $userId= $data['userId'];
        $day= $data['day'];
		$department= $data['department'];
		$week= $data['week'];
		$year= $data['year'];

        $existingSchedule = $em->getRepository( Schedule::class )->findOneBy( [
            'user' => $userId,
            'weekNumber' => $week,
            'year' => $year
        ] );
        if(!$existingSchedule){
            return new JsonResponse(['success' => false, 'message' => 'dit zou niet moeten kunnen je past iets aan wat niet zichtbaar is'], 404);
        }
        if($existingSchedule){
            $editSchedule = $existingSchedule->getScheduled();
            $editSchedule['schedule'][$day]['department'] = $department;
            $existingSchedule->setScheduled($editSchedule);
            $em->persist( $existingSchedule );
            $em->flush();

        }

        $logger->info('{userName} veranderd een afdeling in rooster inplannen', ['userName' => $this->getUser()->getProfile()->getName()]);
        return new JsonResponse(['success' => true, 'message' => 'dat gaat goed']);
    }
    #[ Route( '/admin/update_scheduled_user', name: 'update_scheduled_user' ) ]
    public function updateScheduledUser( Request $request, EntityManagerInterface $em , LoggerInterface $logger): Response
    {
        // Decode the JSON payload
        $data = json_decode($request->getContent(), true);

        $userId= $data['userId'];
        $day= $data['day'];
        $value = $data['value'];
        $different = $data['different'];
		$week= $data['week'];
		$year= $data['year'];

        $existingSchedule = $em->getRepository( Schedule::class )->findOneBy( [
            'user' => $userId,
            'weekNumber' => $week,
            'year' => $year
        ] );
        if(!$existingSchedule){
            return new JsonResponse(['success' => false, 'message' => 'dit zou niet moeten kunnen je past iets aan wat niet zichtbaar is'], 404);
        }
        if($existingSchedule){
            $editSchedule = $existingSchedule->getScheduled();
            if ($value == 'whole_day'){
                $editSchedule['schedule'][$day]['morning']['scheduled'] = true;
                $editSchedule['schedule'][$day]['afternoon']['scheduled'] = true;
                $editSchedule['schedule'][$day]['different_timing']['scheduled'] = null;
            } elseif ($value == "morning"){
                $editSchedule['schedule'][$day]['morning']['scheduled'] = true;
                $editSchedule['schedule'][$day]['afternoon']['scheduled'] = false;
                $editSchedule['schedule'][$day]['different_timing']['scheduled'] = null;
            } elseif ($value == "afternoon"){
                $editSchedule['schedule'][$day]['morning']['scheduled'] = false;
                $editSchedule['schedule'][$day]['afternoon']['scheduled'] = true;
                $editSchedule['schedule'][$day]['different_timing']['scheduled'] = null;
            } elseif ($value == "different_timing"){
                $editSchedule['schedule'][$day]['morning']['scheduled'] = false;
                $editSchedule['schedule'][$day]['afternoon']['scheduled'] = false;
                $editSchedule['schedule'][$day]['different_timing']['scheduled'] = $different;
            } elseif (is_null($value)) {
                $editSchedule['schedule'][$day]['morning']['scheduled'] = false;
                $editSchedule['schedule'][$day]['afternoon']['scheduled'] = false;
                $editSchedule['schedule'][$day]['different_timing']['scheduled'] = null;
            }
            $existingSchedule->setScheduled($editSchedule);
            $em->persist( $existingSchedule );
            $em->flush();

            $logger->info('{userName} veranderd een dagdeel in rooster inplannen', ['userName' => $this->getUser()->getProfile()->getName()]);
        }
        return new JsonResponse(['success' => true, 'message' => 'dat gaat goed']);
    }

    #[ Route ('/admin/publish_schedule/{year}/{weekNumber}', name: 'publish_week')]
    public function publishSchedule ( int $year, int $weekNumber, Request $request, EntityManagerInterface $em, SessionInterface $session, LoggerInterface $logger)
    {
        $users = $em->getRepository( User::class )->findAll();

        foreach ( $users as $user ) {
            $currentSchedule = $em->getRepository( Schedule::class )->findOneBy( [
                'user' => $user,
                'weekNumber' => $weekNumber,
                'year' => $year
            ]);
            $currentSchedule->setPublished(true);
            $em->persist($currentSchedule);
            
        }
        $em->flush();

        $logger->info('{userName} publiceerd week {week} in rooster inplannen', ['userName' => $this->getUser()->getProfile()->getName(), 'week' => $weekNumber]);

        $session->set('check', true);
        return $this->redirectToRoute('week_selector', [
            'year' => $year,
            'weekNumber' => $weekNumber,
        ]);
    }

    #[ Route('/admin/sync_concept', name:'sync_concept')]
    public function syncConcept( Request $request, EntityManagerInterface $em , LoggerInterface $logger): Response
    {
        $data = json_decode($request->getContent(), true);

        $type= $data['type'];
        $weekNumber= $data['week'];
		$year= $data['year'];

        $users = $em->getRepository( User::class )->findAll();

        foreach ($users as $user) {
            if ($type === 'basis'){
                $concept = $em->getRepository( ConceptRooster::class )->findOneBy(['user' => $user])->getBasic();
            } else if ($type === 'even'){
                $concept = $em->getRepository( ConceptRooster::class )->findOneBy(['user' => $user])->getEven();
            } else if ($type === 'odd'){
                $concept = $em->getRepository( ConceptRooster::class )->findOneBy(['user' => $user])->getOdd();
            }
            $schedule = $em->getRepository( Schedule::class )->findOneBy([
                'user' => $user,
                'weekNumber' => $weekNumber,
                'year' => $year
                ]);
            if (!$schedule){
                $schedule = new Schedule();
                $schedule->setUser( $user );
                $schedule->setWeekNumber( $weekNumber );
                $schedule->setYear( $year );
                $schedule->setScheduled($concept);
                $em->persist($schedule);
            }
        }
        
        $em->flush();

        $logger->info('{userName} laad alle {type} concept roosters voor {week} in.', ['userName' => $this->getUser()->getProfile()->getName(), 'type' => $type, 'week' => $weekNumber]);

        return $this->json([
            'redirect' => $this->generateUrl('week_selector', [
                'year' => $year,
                'weekNumber' => $weekNumber
            ])
        ]);
    }
}