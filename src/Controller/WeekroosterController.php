<?php
//
// src/Controller/WeekroosterController.php

namespace App\Controller;

use App\Entity\Profile;
use App\Entity\Schedule;
use App\Entity\User;
use App\Entity\Notifications;
use App\Entity\PublishedRooster;
use App\Entity\GlobalSettings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use DateTime;
use DateInterval;
use DatePeriod;
use DateTimeZone;

class WeekroosterController extends AbstractController
 {
    #[ Route( '/admin/weekrooster', name: 'weekrooster' ) ]
    public function list( EntityManagerInterface $em, Request $request ): Response
    {   
        $settings = $em->getRepository(GlobalSettings::class) -> findOneBy([]);
        $now = new DateTime();
        $weekNumber = $now->format( 'W' );
        $year = $now->format( 'Y' );

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


        // Fetch all schedules for the current week and year, sorted by profile name
        $schedules = $em->getRepository(Schedule::class)
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

        
        return $this->render( 'admin/weekrooster.html.twig', [
            'schedules' => $schedules,
            'weekNumber' => $weekNumber,
            'year' => $year,
            'weekDates' => $weekDates,
            'differentView' => false,
            'settings' => $settings
        ] );
    }

    #[ Route( '/admin/weekrooster/{year}/{weekNumber}', name: 'weekrooster_different_week' ) ]
    public function different_week( int $year, int $weekNumber, EntityManagerInterface $em, Request $request ): Response
    {
        $settings = $em->getRepository(GlobalSettings::class) -> findOneBy([]);
        $now = new DateTime();
        $currentWeekNumber = $now->format( 'W' );
        $currentYear = $now->format( 'Y' );

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


        // Fetch all schedules for the current week and year, sorted by profile name
        $schedules = $em->getRepository(Schedule::class)
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


        
        return $this->render( 'admin/weekrooster.html.twig', [
            'schedules' => $schedules,
            'weekNumber' => $weekNumber,
            'year' => $year,
            'weekDates' => $weekDates,
            'differentView' => true,
            'settings' => $settings
        ] );
    }
    #[ Route( '/admin/weekrooster/{year}/{weekNumber}/geenregistratie', name: 'weekrooster_different_week_no_register' ) ]
    public function different_week_no_register( int $year, int $weekNumber, EntityManagerInterface $em, Request $request ): Response
    {
        $settings = $em->getRepository(GlobalSettings::class) -> findOneBy([]);
        $now = new DateTime();
        $currentWeekNumber = $now->format( 'W' );
        $currentYear = $now->format( 'Y' );

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


        // Fetch all schedules for the current week and year, sorted by profile name
        $schedules = $em->getRepository(Schedule::class)
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


        
        return $this->render( 'admin/weekrooster.html.twig', [
            'schedules' => $schedules,
            'weekNumber' => $weekNumber,
            'year' => $year,
            'weekDates' => $weekDates,
            'differentView' => true,
            'settings' => $settings,
            'noRegister' => true
        ] );
    }

    #[Route('/admin/weekrooster/update-attendence', name: 'update_attendence')]
    public function updateAttendance(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $userId= $data['userId'];
        $week = $data['week'];
        $year = $data['year'];
        $day = $data['day'];
        $daySlot = $data['daySlot'];
        $value = $data['value'];

        $now = new \DateTime();
        $weekNumber = $now->format('W');

        $selectedSchedule = $em->getRepository(Schedule::class)->findOneBy([
            'user' => $userId,
            'weekNumber' => $week,
            'year' => $year
        ]);

        if (!$selectedSchedule) {
            return new JsonResponse(['error' => 'Schedule not found for this week'], 404);
        }

        if ($daySlot == 'whole_day' ) {
            $editSchedule = $selectedSchedule->getScheduled();
            $editSchedule['schedule'][$day]['morning']['attended'] = $value;
            $editSchedule['schedule'][$day]['afternoon']['attended'] = $value;
            $selectedSchedule->setScheduled($editSchedule);
            $em->persist( $selectedSchedule);
            $em->flush();
        }

        if ($daySlot !== 'whole_day'){
            $editSchedule = $selectedSchedule->getScheduled();
            $editSchedule['schedule'][$day][$daySlot]['attended'] = $value;
            $selectedSchedule->setScheduled($editSchedule);
            $em->persist( $selectedSchedule);
            $em->flush();
        }
        return new JsonResponse(['status' => 'success']);
    }

    #[Route('/admin/weekrooster/update-no-schedule', name: 'update_no_schedule')]
    public function updateNoAttendance(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $userId= $data['userId'];
        $week = $data['week'];
        $year = $data['year'];
        $day = $data['day'];
        $daySlot = $data['daySlot'];
        $value = $data['value'];

        $now = new \DateTime();
        $weekNumber = $now->format('W');

        $selectedSchedule = $em->getRepository(Schedule::class)->findOneBy([
            'user' => $userId,
            'weekNumber' => $week,
            'year' => $year
        ]);

        if (!$selectedSchedule) {
            return new JsonResponse(['error' => 'Schedule not found for this week'], 404);
        }

        if ($daySlot == 'whole_day' ) {
            $editSchedule = $selectedSchedule->getScheduled();
            $editSchedule['schedule'][$day]['morning']['attended'] = $value;
            $editSchedule['schedule'][$day]['afternoon']['attended'] = $value;
            $selectedSchedule->setScheduled($editSchedule);
            $em->persist( $selectedSchedule);
            $em->flush();
        }

        if ($daySlot == 'morning'){
            $editSchedule = $selectedSchedule->getScheduled();
            $editSchedule['schedule'][$day]['morning']['attended'] = $value;
            $editSchedule['schedule'][$day]['afternoon']['attended'] = '';
            $selectedSchedule->setScheduled($editSchedule);
            $em->persist( $selectedSchedule);
            $em->flush();
        }

        if ($daySlot == 'afternoon'){
            $editSchedule = $selectedSchedule->getScheduled();
            $editSchedule['schedule'][$day]['morning']['attended'] = '';
            $editSchedule['schedule'][$day]['afternoon']['attended'] = $value;
            $selectedSchedule->setScheduled($editSchedule);
            $em->persist( $selectedSchedule);
            $em->flush();
        }
        
        if ($daySlot == ''){
            $editSchedule = $selectedSchedule->getScheduled();
            $editSchedule['schedule'][$day]['morning']['attended'] = '';
            $editSchedule['schedule'][$day]['afternoon']['attended'] = '';
            $selectedSchedule->setScheduled($editSchedule);
            $em->persist( $selectedSchedule);
            $em->flush();
        }

        return new JsonResponse(['status' => 'success']);
    }

    #[Route('/admin/weekrooster/notification', name: 'schedule_notification')]
    public function scheduleNotification(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $week = $data['weeknumber'];

        $users = $em->getRepository( User::class )->findAll();
        $timezone = new DateTimeZone('Europe/Amsterdam');
        $now = new DateTime('now', $timezone);
        $formattedDate = $now->format('Y-m-d H:i:s');

        foreach ($users as $user){
            $notification = new Notifications();
            $notification->setUser($user);
            $notification->setMessage([
                'time' => $formattedDate,
                'message' => "Bekijk het laatste weekrooster",
                'type' => 'rooster'
            ]);
            $notification->setSeen(false);
            $em->persist($notification);
        }

        $em->flush();

        return new JsonResponse( [ 'success' => true ] );
    }
}

