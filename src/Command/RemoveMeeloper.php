<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Profile;
use App\Entity\Notifications;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use DateTime;
use DateTimeZone;

#[AsCommand('app:remove:meeloper', 'Removes meeloper if there time is up')]
class RemoveMeeloper extends Command
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
        $formattedNow = $now->format('Y-m-d');
        $users = $this->em->getRepository( User::class )->findAll();
        $meelopers = [];
        foreach ($users as $user) {
            $removeDate =  $user->getProfile()->getRemoveDate();
            if ($removeDate != null){
                $formattedDate = $removeDate->format('Y-m-d');
                if ($formattedDate < $formattedNow and $removeDate != null ){
                    $meeloperName = $user->getProfile()->getName();
                    array_push($meelopers, $meeloperName);
                    $this->em->remove($user);
                }
            }
        }
        
        if(count($meelopers) > 0){
            $users = $this->em->getRepository( User::class )->findAll();
            $timezone = new DateTimeZone('Europe/Amsterdam');
            $now = new DateTime('now', $timezone);
            
            foreach ($users as $user){
                $userRoles = $user->getRoles();
                $isAdmin = in_array( "ROLE_ADMIN", $userRoles);
                if($isAdmin){
                    foreach ($meelopers as $meeloper) {
                        $notification = new Notifications();
                        $notification->setUser($user);
                        $notification->setMessage([
                            'time' => $formattedNow,
                            'message' => "$meeloper is verwijderd",
                            'type' => "meeloper"
                        ]);
                        $notification->setSeen(false);
                        $this->em->persist($notification);
                    }
                }
            }

            $this->em->flush();
        }

        $io->success(sprintf('Verwijderd: %d', count($meelopers)));

        return Command::SUCCESS;
    }
}
