<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Schedule;
use App\Entity\Profile;
use App\Entity\User;
use App\Entity\Projects;
use App\Entity\GlobalSettings;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

// Import User entity if you are managing users
use DateTime;
use DateInterval;
use DatePeriod;

class ShareRoosterController extends AbstractController
{
    #[Route('/admin/share_rooster', name: 'app_share_rooster')]
    public function index( EntityManagerInterface $em, Request $request ): Response
    {
        //settings import
        $settings = $em->getRepository(GlobalSettings::class) -> findOneBy([]);
        //projects import
        $projects = $em->getRepository(Projects::class) ->findAll() ;
        $now = new DateTime();
        $allPublished = true;
        $weekNumber = $now->modify('+1 week')->format( 'W' );
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
        $schedules = $em->getRepository( Schedule::class )->findBy( [
            'weekNumber' => $weekNumber,
            'year' => $year
        ] );
        foreach ($schedules as $schedule) {
            if ($schedule->isPublished() != 1) {
                $allPublished = false;
                break; // Stop de loop zodra we een niet-gepubliceerd rooster vinden
            }
        }
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

        return $this->render('admin/share_rooster.html.twig', [
            'allPublished' => $allPublished,
            'schedules' => $schedules,
            'weekNumber' => $weekNumber,
            'year' => $year,
            'weekDates' => $weekDates,
            'settings' => $settings,
            'projects' => $projects,
        ]);
    }
    #[ Route( '/admin/share_rooster/{year}/{weekNumber}', name: 'app_share_rooster_week' ) ]
    public function shareRosterWeek(int $year, int $weekNumber, EntityManagerInterface $em, Request $request): Response
    {
        //settings import
        $settings = $em->getRepository(GlobalSettings::class) -> findOneBy([]);

        $projects = $em->getRepository(Projects::class) ->findAll() ;
        $now = new DateTime();
        $allPublished = true;
        $currentWeekNumber = $now->format( 'W' );
        $currentYear = $now->format( 'Y' );
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

        $schedules = $em->getRepository( Schedule::class )->findBy( [
            'weekNumber' => $weekNumber,
            'year' => $year
        ] );
        foreach ($schedules as $schedule) {
            if ($schedule->isPublished() != 1) {
                $allPublished = false;
                break; // Stop de loop zodra we een niet-gepubliceerd rooster vinden
            }
        }
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

        return $this->render('admin/share_rooster.html.twig', [
            'allPublished' => $allPublished,
            
            'schedules' => $schedules,
            'weekNumber' => $weekNumber,
            'year' => $year,
            'weekDates' => $weekDates,
            'settings' => $settings,
            'projects' => $projects,
        ]);
    }

    #[Route('/admin/share_rooster/save_pdf', name: 'save_pdf_to_server')]
    public function uploadWeekrooster(
        Request $request, 
        #[Autowire('%kernel.project_dir%/public/rooster')] string $roosterDirectory,
        SluggerInterface $slugger
    ): JsonResponse {
        // Check if there is a file in the request
        /** @var UploadedFile $roosterFile */
        $roosterFile = $request->files->get('file');
    
        if (!$roosterFile) {
            return new JsonResponse(['error' => 'No file uploaded'], Response::HTTP_BAD_REQUEST);
        }
    
        // Get the original file name and create a safe filename using the Slugger
        $originalFilename = pathinfo($roosterFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        
        $newFilename = "weekrooster.pdf";
    
        try {
            // Move the file to the designated uploads directory
            $roosterFile->move($roosterDirectory, $newFilename);
    
            return new JsonResponse(['success' => 'File uploaded successfully', 'filename' => $newFilename]);
        } catch (FileException $e) {
            return new JsonResponse(['error' => 'Failed to upload file: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
