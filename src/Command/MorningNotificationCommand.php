<?php

namespace App\Command;

use App\Entity\Schedule;
use App\Entity\User;
use App\Entity\Notifications;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;
use DateTimeZone;

#[AsCommand('app:notification:morning', 'Creates a morning notification if not everybody is checked')]
class MorningNotificationCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
    ){
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new DateTime();
        $dayNumber = $now->format('N');
        $weekNumber = $now->format( 'W' );
        $yearNumber = $now->format( 'Y' );
        $i = 0;
        $dayNameArray = [
            'monday',    // index 1
            'tuesday',   // index 2
            'wednesday', // index 3
            'thursday',  // index 4
            'friday',    // index 5
            'saturday',  // index 6
            'sunday'     // index 7
        ];
        $dayName = $dayNameArray[$dayNumber-1];
        $comparisonTime = "12:30";

        $schedules = $this->em->getRepository( Schedule::class )->findBy([
            'weekNumber' => $weekNumber,
            'year' => $yearNumber 
        ]);

        foreach ($schedules as $schedule){
            $scheduleData = $schedule->getScheduled();
            $scheduleMorningScheduled = $scheduleData['schedule'][$dayName]['morning']['scheduled'];
            $scheduleMorningAttended = $scheduleData['schedule'][$dayName]['morning']['attended'];
            $scheduleDifferentScheduled = $scheduleData['schedule'][$dayName]['different_timing']['scheduled'];
            $scheduleDifferentAttended = $scheduleData['schedule'][$dayName]['different_timing']['attended'];
            $firstTime = explode(' ', $scheduleDifferentScheduled)[0];
            if ($scheduleMorningScheduled and $scheduleMorningAttended == ''){
                $i++;
            }
            if ($scheduleDifferentScheduled and $firstTime < $comparisonTime and $scheduleDifferentAttended == ''){
                $i++;
            }
        }
        
        if($i > 0){
            $users = $this->em->getRepository( User::class )->findAll();
            $timezone = new DateTimeZone('Europe/Amsterdam');
            $now = new DateTime('now', $timezone);
            $formattedDate = $now->format('Y-m-d H:i:s');
            foreach ($users as $user){
                $userRoles = $user->getRoles();
                $isAdmin = in_array( "ROLE_ADMIN", $userRoles);
                if($isAdmin){
                    $notification = new Notifications();
                    $notification->setUser($user);
                    if($i == 1){
                        $notification->setMessage([
                            'time' => $formattedDate,
                            'message' => "$i persoon heeft geen registratie deze ochtend",
                            'type' => "aanwezigheid"
                        ]);
                    }
                    if($i > 1){
                        $notification->setMessage([
                            'time' => $formattedDate,
                            'message' => "$i personen hebben geen registratie deze ochtend",
                            'type' => "aanwezigheid"
                        ]);
                    }
                    $notification->setSeen(false);
                    $this->em->persist($notification);
                }

                
            }

            $this->em->flush();
        }

        $io->success(sprintf('Number: %d', $i));

        return Command::SUCCESS;
    }
}
