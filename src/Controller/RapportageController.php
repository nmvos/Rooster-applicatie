<?php

namespace App\Controller;

use App\Entity\Schedule;
use App\Entity\GlobalSettings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class RapportageController extends AbstractController
{
    #[Route('/admin/rapportage', name: 'rapportage')]
    public function rapportage(EntityManagerInterface $em, Request $request): Response
    {
        $this->ensureThuiswerkenColorExists($em);
        $this->ensureEerderWegColorExists($em);
        $this->ensureNietVerwachtColorExists($em);
        $colors = $em->getRepository(GlobalSettings::class)->findOneBy([])?->getColors();


        if ($this->isGranted('ROLE_SUPERADMIN')) {
            $settings = $em->getRepository(GlobalSettings::class)->findOneBy([]);
            $check = false;
            $now = new \DateTime();
            $currentWeek = $now->format('W');
            $currentYear = $now->format('Y');
            $currentMonth = $now->format('m');

            // Calculate the first and last day of the month
            $firstDayOfMonth = new \DateTime("$currentYear-$currentMonth-01");
            $lastDayOfMonth = (clone $firstDayOfMonth)->modify('last day of this month');

            // Calculate the first and last week number of the month
            $firstWeekNumber = $firstDayOfMonth->format('W');
            $lastWeekNumber = $lastDayOfMonth->format('W');

            // Verkrijg de jaar van de eerste en laatste dag van de maand
            $firstDayOfMonthYear = $firstDayOfMonth->format('Y');
            $lastDayOfMonthYear = $lastDayOfMonth->format('Y');

            // Controleer of de eerste dag van de maand op een maandag valt
            if ($firstDayOfMonth->format('N') != 1) {
                $check = true;
                // Haal het weeknummer van de laatste week van het vorige jaar op
                $previousYearLastWeek = (clone $firstDayOfMonth)->modify('-1 week')->format('W');
                $previousYear = $currentYear - 1;
            }

            // Controleer of de maand december is en de laatste week in het nieuwe jaar valt
            if ($currentMonth == 12 && $lastWeekNumber == 1) {
                $check = true;
                $nextYear = $currentYear + 1;
            }

            // Query samenstellen
            if (!$check) {
                // Normaal geval: weeknummers zitten in hetzelfde jaar
                $schedules = $em->getRepository(Schedule::class)->createQueryBuilder('s')
                    ->join('s.user', 'u')
                    ->join('u.profile', 'p')
                    ->where('s.weekNumber BETWEEN :firstWeekNumber AND :lastWeekNumber')
                    ->andWhere('s.year = :year')
                    ->setParameter('firstWeekNumber', $firstWeekNumber)
                    ->setParameter('lastWeekNumber', $lastWeekNumber)
                    ->setParameter('year', $currentYear)
                    ->orderBy('p.name', 'ASC')
                    ->getQuery()
                    ->getResult();
            } else {
                // Speciale gevallen: weeknummers zitten in verschillende jaren
                $qb = $em->getRepository(Schedule::class)->createQueryBuilder('s');

                if ($currentMonth == 12 && $lastWeekNumber == 1) {
                    // December scenario: laatste week is in het nieuwe jaar
                    $schedules = $qb
                        ->join('s.user', 'u')
                        ->join('u.profile', 'p')
                        ->where(
                            '(s.year = :currentYear AND s.weekNumber >= :firstWeekNumber) OR
                            (s.year = :nextYear AND s.weekNumber = 1)'
                        )
                        ->setParameter('firstWeekNumber', $firstWeekNumber)
                        ->setParameter('currentYear', $currentYear)
                        ->setParameter('nextYear', $nextYear)
                        ->orderBy('p.name', 'ASC')
                        ->getQuery()
                        ->getResult();
                } elseif ($check && $firstDayOfMonth->format('N') != 1) {
                    // Januari scenario: eerste week is in het vorige jaar
                    $schedules = $qb
                        ->join('s.user', 'u')
                        ->join('u.profile', 'p')
                        ->where('s.weekNumber = :previousYearLastWeek AND s.year = :previousYear')
                        ->orWhere('s.weekNumber BETWEEN 1 AND :lastWeekNumber AND s.year = :currentYear')
                        ->setParameter('previousYearLastWeek', $previousYearLastWeek)
                        ->setParameter('previousYear', $previousYear)
                        ->setParameter('lastWeekNumber', $lastWeekNumber)
                        ->setParameter('currentYear', $currentYear)
                        ->orderBy('p.name', 'ASC')
                        ->getQuery()
                        ->getResult();
                }
            }

            // Ophalen van alle roosters binnen de maand
            $userSchedules = [];
            foreach ($schedules as $schedule) {
                $userId = $schedule->getUser()->getId();
                if (!isset($userSchedules[$userId])) {
                    $userSchedules[$userId] = [
                        'user' => $schedule->getUser(),
                        'days' => []
                    ];
                }

                // Voeg de dagelijkse gegevens van de week toe aan de bestaande gegevens
                foreach ($schedule->getScheduled()['schedule'] as $day => $info) {
                    if ($currentMonth == 12 && $schedule->getWeekNumber() == 1) {
                        $date = $this->getDateFromWeekDay($nextYear, $schedule->getWeekNumber(), $day);
                    } else {
                        $date = $this->getDateFromWeekDay($currentYear, $schedule->getWeekNumber(), $day);
                    }

                    // Check of de datum binnen de huidige maand valt
                    if ($date >= $firstDayOfMonth && $date <= $lastDayOfMonth) {
                        $morningAttended = $info['morning']['attended'];
                        $afternoonAttended = $info['afternoon']['attended'];
                        $differentTimingAttended = $info['different_timing']['attended'];

                        $morningScheduled = $info['afternoon']['scheduled'];
                        $afternoonScheduled = $info['morning']['scheduled'];
                        $differentTimingScheduled = $info['different_timing']['scheduled'];
                        $scheduled = $morningScheduled ?: $afternoonScheduled ?: $differentTimingScheduled ?: 'Niet ingeroosterd';
                        if ($scheduled) {
                            $attended = $morningAttended ?: $afternoonAttended ?: $differentTimingAttended ?: '?';
                        }
                        if ($scheduled === 'Niet ingeroosterd') {
                            $attended = $morningAttended ?: $afternoonAttended ?: $differentTimingAttended ?: '-';
                        }
                        $userSchedules[$userId]['days'][$date->format('d-m')] = $attended;
                    }
                }
            }

            // Genereer data voor elke dag van de maand
            $period = new \DatePeriod(
                $firstDayOfMonth,
                new \DateInterval('P1D'),
                $lastDayOfMonth->modify('+1 day') // Inclusief de laatste dag
            );

            $monthDates = [];
            foreach ($period as $date) {
                $monthDates[] = $date->format('d-m');
            }

            // Ophalen van alle unieke jaren
            $years = $em->getRepository(Schedule::class)->createQueryBuilder('s')
                ->select('DISTINCT s.year')
                ->orderBy('s.year', 'ASC')
                ->getQuery()
                ->getResult();

            $yearsWithMonths = [];
            foreach ($years as $year) {
                $yearValue = $year['year'];

                // Ophalen van alle unieke weeknummers voor het jaar
                $weekNumbers = $em->getRepository(Schedule::class)->createQueryBuilder('s')
                    ->select('DISTINCT s.weekNumber')
                    ->where('s.year = :year')
                    ->setParameter('year', $yearValue)
                    ->orderBy('s.weekNumber', 'ASC')
                    ->getQuery()
                    ->getResult();

                // Vertaal weeknummers naar maanden
                $months = [];
                foreach ($weekNumbers as $weekNumber) {
                    $week = (int) $weekNumber['weekNumber'];
                    if ($week == 1) {
                        // Check if the current date is the first Monday of the year
                        if ($date->format('z') == 0 && $date->format('N') == 1) {
                            // Do nothing if it's the first Monday of the year
                        } else {
                            $week++;
                        }
                    }
                    $date = (new \DateTime())->setISODate($yearValue, $week);
                    $actualYear = (int) $date->format('Y');

                    // Bepaal de datum voor de maandag van de week

                    // Verkrijg de maand van de datum
                    $month = (int) $date->format('m');

                    // Voeg de maand toe als deze nog niet is toegevoegd
                    if (!in_array($month, $months)) {
                        $months[] = $month;
                    }
                }

                // Voeg de maanden toe aan het jaar
                $yearsWithMonths[$yearValue] = $months;
            }

            // Converteer de yearsWithMonths array naar JSON
            $yearsWithMonthsJson = json_encode($yearsWithMonths);

            return $this->render('admin/rapportage.html.twig', [
                'settings' => $settings,
                'userSchedules' => $userSchedules,
                'monthDates' => $monthDates,
                'currentWeek' => $currentWeek,
                'currentMonth' => $firstDayOfMonth->format('m'),
                'currentYear' => $firstDayOfMonth->format('Y'),
                'yearsWithMonths' => $yearsWithMonthsJson,
                'future' => false,
                'past' => false,
            ]);
        } else {
            return $this->render('security/403_page.html.twig');
        }
    }

    private function getDateFromWeekDay($year, $week, $day)
    {
        // Start met 1 januari van het opgegeven jaar
        $date = new \DateTime("{$year}-01-01");

        // Zoek de eerste maandag van het jaar
        $date->modify('monday this week');

        // Verplaats naar de start van de opgegeven week
        // De weeknummers beginnen bij 1, dus we trekken 1 week af om naar de week 1 te gaan
        $date->modify('+' . ($week - 1) . ' week');

        // Voeg de dag toe aan de startdatum van de week
        // We veronderstellen hier dat $day een string zoals 'Monday', 'Tuesday', etc. is
        // Je kunt deze waarde aanpassen indien nodig
        $date->modify($day);

        return $date;
    }


 
    #[Route('/admin/rapportage/{year}/{month}', name: 'rapportage_selector')]
    public function selectRapportage(int $year, int $month, EntityManagerInterface $em, Request $request): Response
    {
        $this->ensureThuiswerkenColorExists($em);
        $this->ensureEerderWegColorExists($em);
        $colors = $em->getRepository(GlobalSettings::class)->findOneBy([])?->getColors();

        if ($this->isGranted('ROLE_SUPERADMIN')) {
            $settings = $em->getRepository(GlobalSettings::class)->findOneBy([]);
            $check = false;
            $now = new \DateTime();
            $currentYear = $year;
            $currentMonth = $month;
            $currentWeek = $now->format('W');
            $trueMonth = $now->format('m');
            $trueYear = $now->format('Y');
            $future = false;
            $past = false;

            // Bereken of je naar de toekomst kijkt
            if ($trueYear < $currentYear || ($trueYear == $currentYear && $trueMonth < $currentMonth)) {
                $future = true;
            }
            if ($trueYear > $currentYear || ($trueYear == $currentYear && $trueMonth > $currentMonth)) {
                $past = true;
            }

            // Bereken het eerste en laatste weeknummer van de maand
            $firstDayOfMonth = new \DateTime("$currentYear-$currentMonth-01");
            $lastDayOfMonth = (clone $firstDayOfMonth)->modify('last day of this month');

            $firstWeekNumber = $firstDayOfMonth->format('W');
            $lastWeekNumber = $lastDayOfMonth->format('W');

            // Verkrijg de jaar van de eerste en laatste dag van de maand
            $firstDayOfMonthYear = $firstDayOfMonth->format('Y');
            $lastDayOfMonthYear = $lastDayOfMonth->format('Y');

            // Controleer of de eerste dag van de maand op een maandag valt
            if ($firstDayOfMonth->format('N') != 1) {
                $check = true;
                // Haal het weeknummer van de laatste week van het vorige jaar op
                $previousYearLastWeek = (clone $firstDayOfMonth)->modify('-1 week')->format('W');
                $previousYear = $currentYear - 1;
            }

            // Controleer of de maand december is en de laatste week in het nieuwe jaar valt
            if ($currentMonth == 12 && $lastWeekNumber == 1) {
                $check = true;
                $nextYear = $currentYear + 1;
            }

            // Query samenstellen
            if (!$check) {
                // Normaal geval: weeknummers zitten in hetzelfde jaar
                $schedules = $em->getRepository(Schedule::class)->createQueryBuilder('s')
                    ->join('s.user', 'u')
                    ->join('u.profile', 'p')
                    ->where('s.weekNumber BETWEEN :firstWeekNumber AND :lastWeekNumber')
                    ->andWhere('s.year = :year')
                    ->setParameter('firstWeekNumber', $firstWeekNumber)
                    ->setParameter('lastWeekNumber', $lastWeekNumber)
                    ->setParameter('year', $currentYear)
                    ->orderBy('p.name', 'ASC')
                    ->getQuery()
                    ->getResult();
            } else {
                // Speciale gevallen: weeknummers zitten in verschillende jaren
                $qb = $em->getRepository(Schedule::class)->createQueryBuilder('s');

                if ($currentMonth == 12 && $lastWeekNumber == 1) {
                    // December scenario: laatste week is in het nieuwe jaar
                    $schedules = $qb
                        ->join('s.user', 'u')
                        ->join('u.profile', 'p')
                        ->where(
                            '(s.year = :currentYear AND s.weekNumber >= :firstWeekNumber) OR
                            (s.year = :nextYear AND s.weekNumber = 1)'
                        )
                        ->setParameter('firstWeekNumber', $firstWeekNumber)
                        ->setParameter('currentYear', $currentYear)
                        ->setParameter('nextYear', $nextYear)
                        ->orderBy('p.name', 'ASC')
                        ->getQuery()
                        ->getResult();
                } elseif ($check && $firstDayOfMonth->format('N') != 1) {
                    // Januari scenario: eerste week is in het vorige jaar
                    $schedules = $qb
                        ->join('s.user', 'u')
                        ->join('u.profile', 'p')
                        ->where('s.weekNumber = :previousYearLastWeek AND s.year = :previousYear')
                        ->orWhere('s.weekNumber BETWEEN 1 AND :lastWeekNumber AND s.year = :currentYear')
                        ->setParameter('previousYearLastWeek', $previousYearLastWeek)
                        ->setParameter('previousYear', $previousYear)
                        ->setParameter('lastWeekNumber', $lastWeekNumber)
                        ->setParameter('currentYear', $currentYear)
                        ->orderBy('p.name', 'ASC')
                        ->getQuery()
                        ->getResult();
                }
            }

            // Ophalen van alle roosters binnen de maand
            $userSchedules = [];
            foreach ($schedules as $schedule) {
                $userId = $schedule->getUser()->getId();
                if (!isset($userSchedules[$userId])) {
                    $userSchedules[$userId] = [
                        'user' => $schedule->getUser(),
                        'days' => []
                    ];
                }

                // Voeg de dagelijkse gegevens van de week toe aan de bestaande gegevens
                foreach ($schedule->getScheduled()['schedule'] as $day => $info) {
                    if ($currentMonth == 12 && $schedule->getWeekNumber() == 1) {
                        $date = $this->getDateFromWeekDay($nextYear, $schedule->getWeekNumber(), $day);
                    } else {
                        $date = $this->getDateFromWeekDay($currentYear, $schedule->getWeekNumber(), $day);
                    }

                    // Check of de datum binnen de huidige maand valt
                    if ($date >= $firstDayOfMonth && $date <= $lastDayOfMonth) {
                        $morningAttended = $info['morning']['attended'];
                        $afternoonAttended = $info['afternoon']['attended'];
                        $differentTimingAttended = $info['different_timing']['attended'];

                        $morningScheduled = $info['afternoon']['scheduled'];
                        $afternoonScheduled = $info['morning']['scheduled'];
                        $differentTimingScheduled = $info['different_timing']['scheduled'];
                        $scheduled = $morningScheduled ?: $afternoonScheduled ?: $differentTimingScheduled ?: 'Niet ingeroosterd';
                        if ($scheduled) {
                            $attended = $morningAttended ?: $afternoonAttended ?: $differentTimingAttended ?: '?';
                        }
                        if ($scheduled === 'Niet ingeroosterd') {
                            $attended = $morningAttended ?: $afternoonAttended ?: $differentTimingAttended ?: '-';
                        }
                        $userSchedules[$userId]['days'][$date->format('d-m')] = $attended;
                    }
                }
            }

            // Genereer data voor elke dag van de maand
            $period = new \DatePeriod(
                $firstDayOfMonth,
                new \DateInterval('P1D'),
                $lastDayOfMonth->modify('+1 day') // Inclusief de laatste dag
            );

            $monthDates = [];
            foreach ($period as $date) {
                $monthDates[] = $date->format('d-m');
            }

            // Ophalen van alle unieke jaren
            $years = $em->getRepository(Schedule::class)->createQueryBuilder('s')
                ->select('DISTINCT s.year')
                ->orderBy('s.year', 'ASC')
                ->getQuery()
                ->getResult();

            $yearsWithMonths = [];
            foreach ($years as $year) {
                $yearValue = $year['year'];

                // Ophalen van alle unieke weeknummers voor het jaar
                $weekNumbers = $em->getRepository(Schedule::class)->createQueryBuilder('s')
                    ->select('DISTINCT s.weekNumber')
                    ->where('s.year = :year')
                    ->setParameter('year', $yearValue)
                    ->orderBy('s.weekNumber', 'ASC')
                    ->getQuery()
                    ->getResult();

                // Vertaal weeknummers naar maanden
                $months = [];
                foreach ($weekNumbers as $weekNumber) {
                    $week = (int) $weekNumber['weekNumber'];
                    if ($week == 1) {
                        // Check if the current date is the first Monday of the year
                        if ($date->format('z') == 0 && $date->format('N') == 1) {
                            // Do nothing if it's the first Monday of the year
                        } else {
                            $week++;
                        }
                    }
                    $date = (new \DateTime())->setISODate($yearValue, $week);
                    $actualYear = (int) $date->format('Y');

                    // Bepaal de datum voor de maandag van de week

                    // Verkrijg de maand van de datum
                    $month = (int) $date->format('m');

                    // Controleer of de week in december maar naar januari van het volgende jaar verwijst

                    // Voeg de maand toe als deze nog niet is toegevoegd
                    if (!in_array($month, $months)) {
                        $months[] = $month;
                    }
                }

                // Voeg de maanden toe aan het jaar
                $yearsWithMonths[$yearValue] = $months;
            }

            // Converteer de yearsWithMonths array naar JSON
            $yearsWithMonthsJson = json_encode($yearsWithMonths);

            return $this->render('admin/rapportage.html.twig', [
                'userSchedules' => $userSchedules,
                'monthDates' => $monthDates,
                'currentWeek' => $currentWeek,
                'currentMonth' => $firstDayOfMonth->format('m'),
                'currentYear' => $firstDayOfMonth->format('Y'),
                'yearsWithMonths' => $yearsWithMonthsJson,
                'settings' => $settings,
                'future' => $future,
                'past' => $past,
            ]);
        } else {
            return $this->render('security/403_page.html.twig');
        }
    }

    #[Route('/admin/rapportage/{year}', name: 'year_rapportage')]
    public function yearRapportage(int $year, EntityManagerInterface $em, Request $request): Response
    {
        $this->ensureThuiswerkenColorExists($em);
        $this->ensureEerderWegColorExists($em);
        $colors = $em->getRepository(GlobalSettings::class)->findOneBy([])?->getColors();

        if ($this->isGranted('ROLE_SUPERADMIN')) {
            $settings = $em->getRepository(GlobalSettings::class)->findOneBy([]);
            $now = new \DateTime();
            $currentYear = $year;
            $currentWeek = $now->format('W');
            $trueYear = $now->format('Y');
            $future = false;
            $past = false;

            // Determine if the year is in the future or past
            if ($trueYear < $currentYear) {
                $future = true;
            }
            if ($trueYear > $currentYear) {
                $past = true;
            }

            // Handle next and previous year references
            $nextYear = $currentYear + 1;
            $previousYear = $currentYear - 1;

            // Calculate the first and last day of the year
            $firstDayOfYear = new \DateTime("$currentYear-01-01");
            $lastDayOfYear = new \DateTime("$currentYear-12-31");
            $lastDayOfPreviousYear = new \DateTime("$previousYear-12-31");
            $previousYearLastWeek = $lastDayOfPreviousYear->format('W');

            // Get the first week number of the year (ISO week)
            $firstWeekNumber = $firstDayOfYear->format('W');

            // Check if the first week of January belongs to the last week of the previous year
            if ($firstDayOfYear->format('W') == 53 || $firstDayOfYear->format('W') == 52) {
                // Adjust to the last week of the previous year
                $firstWeekNumber = (new \DateTime("$previousYear-12-31"))->format('W');
            }

            // Get the last week number of the year (ISO week)
            $lastWeekNumber = $lastDayOfYear->format('W');

            // Check if the last few days of the year belong to the first week of the next year
            if ($lastWeekNumber == '01') {
                // Adjust to the last valid week number within the current year
                $lastWeekNumber = (new \DateTime("$currentYear-12-24"))->format('W');
            }

            // Query schedules that may span both the current year and adjacent years
            $qb = $em->getRepository(Schedule::class)->createQueryBuilder('s');
            $schedules = $qb
                ->join('s.user', 'u')
                ->join('u.profile', 'p')
                ->where('(s.year = :currentYear AND s.weekNumber BETWEEN :firstWeekNumber AND :lastWeekNumber)')
                ->orWhere('(s.year = :previousYear AND s.weekNumber = :lastWeekOfPreviousYear)')
                ->orWhere('(s.year = :nextYear AND s.weekNumber = 1)')
                ->setParameter('currentYear', $currentYear)
                ->setParameter('firstWeekNumber', $firstWeekNumber)
                ->setParameter('lastWeekNumber', $lastWeekNumber)
                ->setParameter('previousYear', $previousYear)
                ->setParameter('lastWeekOfPreviousYear', $previousYearLastWeek)
                ->setParameter('nextYear', $nextYear)
                ->orderBy('p.name', 'ASC')
                ->getQuery()
                ->getResult();

            // Process the weekly schedules across both current and adjacent years
            $userSchedules = [];
            foreach ($schedules as $schedule) {
                $userId = $schedule->getUser()->getId();
                if (!isset($userSchedules[$userId])) {
                    $userSchedules[$userId] = [
                        'user' => $schedule->getUser(),
                        'weeks' => []
                    ];
                }

                // Loop through each day of the week for this schedule
                foreach ($schedule->getScheduled()['schedule'] as $day => $info) {
                    if ($schedule->getYear() == $previousYear && $schedule->getWeekNumber() == $previousYearLastWeek) {
                        // Get the date for the last few days of the previous year
                        $date = $this->getDateFromWeekDay($previousYear, $schedule->getWeekNumber(), $day);
                    } elseif ($schedule->getYear() == $nextYear && $schedule->getWeekNumber() == 1) {
                        // Get the date for the first few days of the next year
                        $date = $this->getDateFromWeekDay($nextYear, $schedule->getWeekNumber(), $day);
                    } else {
                        // Regular case: schedule is within the current year
                        $date = $this->getDateFromWeekDay($currentYear, $schedule->getWeekNumber(), $day);
                    }

                    if ($date >= $firstDayOfYear && $date <= $lastDayOfYear) {
                        $morningAttended = $info['morning']['attended'];
                        $afternoonAttended = $info['afternoon']['attended'];
                        $differentTimingAttended = $info['different_timing']['attended'];

                        $morningScheduled = $info['afternoon']['scheduled'];
                        $afternoonScheduled = $info['morning']['scheduled'];
                        $differentTimingScheduled = $info['different_timing']['scheduled'];
                        $scheduled = $morningScheduled ?: $afternoonScheduled ?: $differentTimingScheduled ?: 'Niet ingeroosterd';
                        if ($scheduled) {
                            $attended = $morningAttended ?: $afternoonAttended ?: $differentTimingAttended ?: '?';
                        }
                        if ($scheduled === 'Niet ingeroosterd') {
                            $attended = $morningAttended ?: $afternoonAttended ?: $differentTimingAttended ?: '-';
                        }
                        // Store schedule data for the week and day
                        $weekNumber = $date->format('W');
                        $userSchedules[$userId]['weeks'][$weekNumber][$date->format('d-m')] = [
                            'scheduled' => $scheduled,
                            'attended'  => $attended
                        ];

                    }
                }
            }

            // Generate week ranges for the entire year
            $yearWeeks = [];
            $weekStart = (new \DateTime())->setISODate($currentYear, 1);
            while ($weekStart->format('Y') == $currentYear) {
                $weekEnd = (clone $weekStart)->modify('+6 days');
                $weekNumber = $weekStart->format('W');
                $yearWeeks[] = [
                    'weekNumber' => $weekNumber,
                    'weekRange' => $weekStart->format('d-m') . ' - ' . $weekEnd->format('d-m')
                ];
                $weekStart->modify('+1 week');
            }

            // Retrieve all unique years for dropdown selection
            $years = $em->getRepository(Schedule::class)->createQueryBuilder('s')
                ->select('DISTINCT s.year')
                ->orderBy('s.year', 'ASC')
                ->getQuery()
                ->getScalarResult();
            // Use getScalarResult to retrieve an array of scalar values

            // Convert years to JSON for frontend use (year selection dropdown)
            $yearsJson = json_encode(array_column($years, 'year'));

            $period = new \DatePeriod(
                $firstDayOfYear,
                new \DateInterval('P1D'),
                $lastDayOfYear->modify('+1 day') // Include the last day
            );

            $yearDates = [];
            foreach ($period as $date) {
                $yearDates[] = $date->format('d-m');
                // You can change the format as needed
            }

            return $this->render('admin/rapportage_year.html.twig', [
                'userSchedules' => $userSchedules,
                'yearWeeks' => $yearWeeks,  // Send week ranges to the view
                'currentWeek' => $currentWeek,
                'currentYear' => $currentYear,
                'yearDates' => $yearDates,
                'yearsJson' => $yearsJson,
                'settings' => $settings,
                'future' => $future,
                'past' => $past,
                'trueYear' => $trueYear,
            ]);
        } else {
            return $this->render('security/403_page.html.twig');
        }
    }

    #[Route('/admin/weekrapportage/{year?}/{week?}', name: 'week_rapportage')]
    public function weekRapportage(?int $year = null, ?int $week = null, EntityManagerInterface $em, Request $request): Response
    {

        $this->ensureThuiswerkenColorExists($em);
        $this->ensureEerderWegColorExists($em);
        $colors = $em->getRepository(GlobalSettings::class)->findOneBy([])?->getColors();

        if ($this->isGranted('ROLE_SUPERADMIN')) {
            $settings = $em->getRepository(GlobalSettings::class)->findOneBy([]);
            $now = new \DateTime();

            $currentWeek = $now->format('W');
            $currentYear = $now->format('Y');
            // If year or week is not provided, use the current values
            $year = $year ?? $currentYear;
            $week = $week ?? $currentWeek;
            if ($currentYear == $year && $currentWeek == $week) {
                $past = false;
                $future = false;
                $isNow = true;
            } elseif ($currentYear > $year || ($currentYear == $year && $currentWeek > $week)) {
                $past = true;
                $future = false;
                $isNow = false;
            } else {
                $past = false;
                $future = true;
                $isNow = false;
            }

            $firstDayOfWeek = (new \DateTime())->setISODate($year, $week);
            $lastDayOfWeek = (clone $firstDayOfWeek)->modify('+4 days');

            // Query to retrieve schedules for the specified week
            $schedules = $em->getRepository(Schedule::class)
                ->createQueryBuilder('s')
                ->join('s.user', 'u')
                ->join('u.profile', 'p')
                ->where('s.weekNumber = :weekNumber')
                ->andWhere('s.year = :year')
                ->setParameter('weekNumber', $week)
                ->setParameter('year', $year)
                ->orderBy('p.name', 'ASC')
                ->getQuery()
                ->getResult();

            // Initialize user schedules for the specified week
            $userSchedules = [];
            foreach ($schedules as $schedule) {
                $userId = $schedule->getUser()->getId();
                if (!isset($userSchedules[$userId])) {
                    $userSchedules[$userId] = [
                        'user' => $schedule->getUser(),
                        'days' => []
                    ];
                }

                // Add daily schedule information for each day in the week
                foreach ($schedule->getScheduled()['schedule'] as $day => $info) {
                    $date = $this->getDateFromWeekDay($year, $schedule->getWeekNumber(), $day);
                    $morningAttended = $info['morning']['attended'];
                    $afternoonAttended = $info['afternoon']['attended'];
                    $differentTimingAttended = $info['different_timing']['attended'];

                    $morningScheduled = $info['morning']['scheduled'];
                    $afternoonScheduled = $info['afternoon']['scheduled'];
                    $differentTimingScheduled = $info['different_timing']['scheduled'];
                    $scheduled = $morningScheduled ?: $afternoonScheduled ?: $differentTimingScheduled ?: 'Niet ingeroosterd';

                    $attended = $scheduled === 'Niet ingeroosterd'
                        ? ($morningAttended ?: $afternoonAttended ?: $differentTimingAttended ?: '-')
                        : ($morningAttended ?: $afternoonAttended ?: $differentTimingAttended ?: '?');

                    $userSchedules[$userId]['days'][$date->format('d-m')] = $attended;
                }
            }

            // Generate dates for the current week
            $period = new \DatePeriod($firstDayOfWeek, new \DateInterval('P1D'), $lastDayOfWeek->modify('+1 day'));
            $weekDates = [];
            foreach ($period as $date) {
                $weekDates[] = $date->format('d-m');
            }

            // Retrieve all unique years for dropdown selection
            $years = $em->getRepository(Schedule::class)->createQueryBuilder('s')
                ->select('DISTINCT s.year')
                ->orderBy('s.year', 'ASC')
                ->getQuery()
                ->getScalarResult();
            // Use getScalarResult to retrieve an array of scalar values

            // Convert years to JSON for frontend use (year selection dropdown)
            $yearsJson = json_encode(array_column($years, 'year'));

            // Retrieve all unique weeks of a specific for dropdown selection
            $weeks = $em->getRepository(Schedule::class)
                ->createQueryBuilder('s')
                ->select('DISTINCT s.weekNumber')
                ->where('s.year = :year')
                ->setParameter('year', $year)
                ->orderBy('s.weekNumber', 'ASC')
                ->getQuery()
                ->getScalarResult();
            // Use getScalarResult to retrieve an array of scalar values
                $weekNumbers = array_column($weeks, 'weekNumber');

                // Check if $week exists in $weekNumbers, if not, set it to the lowest available week
                if (!in_array($week, $weekNumbers)) {
                    $week = !empty($weekNumbers) ? max($weekNumbers) : null; // Set to the lowest week or null if no weeks exist
                }
            // Convert years to JSON for frontend use (year selection dropdown)
            $weeksJson = json_encode(array_column($weeks, 'weekNumber'));



                // Check if the URL parameters are incorrect and redirect if needed
            if ($request->attributes->get('week') != $week || $request->attributes->get('year') != $year) {
                return $this->redirectToRoute('week_rapportage', [
                    'year' => $year,
                    'week' => $week,
                ]);
            }

            return $this->render('admin/rapportage_week.html.twig', [
                'settings' => $settings,
                'userSchedules' => $userSchedules,
                'weekDates' => $weekDates,
                'currentWeek' => $currentWeek,
                'selectedWeek' => $week,
                'currentYear' => $year,
                'future' => $future,
                'past' => $past,
                'isNow' => $isNow,
                'yearsJson' => $yearsJson,
                'weeksJson' => $weeksJson,
            ]);
        } else {
            return $this->render('security/403_page.html.twig');
        }
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


