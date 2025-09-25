<?php

namespace App\Controller;

use App\Entity\GlobalSettings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Form\FavIconFormType;
use App\Form\LogoFormType;
use App\Form\BackGroundFormType;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Entity\DepartmentLimit;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\Tools\SchemaTool;
use Psr\Log\LoggerInterface;

class SettingsController extends AbstractController
{
    #[Route('/admin/settings', name: 'settings')]
    public function assignRights (
        EntityManagerInterface $em, 
        Request $request, 
        #[Autowire('%kernel.project_dir%/public/uploads/favicon')] string $faviconDirectory,
        #[Autowire('%kernel.project_dir%/public/uploads/logo')] string $logoDirectory, 
        #[Autowire('%kernel.project_dir%/public/uploads/background')] string $backgroundDirectory,  
        SluggerInterface $slugger,
        LoggerInterface $logger
    ): Response
    {
        if ($this->isGranted('ROLE_SUPERADMIN')){
            $ExistingSettings = $em->getRepository(GlobalSettings::class) -> findOneBy([]);

            if(!$ExistingSettings){
                $settings = new GlobalSettings();
                $settings->setDepartments(["Algemeen"]);
                $settings->setDepartmentColor([
                    "Algemeen" => '#a0a0a0'
                ]);
                $settings->setColors([
                    'Aanwezig' => '#1cab1c',
                    'Afwezig' => '#ee6055',
                    'Ingeroosterd' => '#94e8b4',
                    'Niet Ingeroosterd' => '#cdbbf2',
                    'Niet verwacht' => '#808080',
                    'Ziek' => '#8a6642',
                    'Geen registratie' => '#CC6CE7',
                    'Vrij' => "#FFDE59",
                    'Te laat' => '#D20103',
                    'Eerder weg' => '#BB00BB'
                ]);
                $em->persist( $settings );
                $em->flush();

                $ExistingSettings = $settings;
            }
            
            $IconForm = $this->createForm(FaviconFormType::class);
            $IconForm->handleRequest($request);
            if ($IconForm->isSubmitted() && $IconForm->isValid()) {
                $favIconFile = $IconForm->get('favIcon')->getData();

                if ($favIconFile) {
                    $originalFilename = pathinfo($favIconFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = 'fav-icon.'.$favIconFile->guessExtension();
                    try {
                        $favIconFile->move($faviconDirectory, $newFilename);
                    } catch (FileException $e) {
                        dump('File upload error: ' . $e->getMessage());
                    }
                }  
            } 

            $LogoForm = $this->createForm(LogoFormType::class);
            $LogoForm->handleRequest($request);
            if ($LogoForm->isSubmitted() && $LogoForm->isValid()){
                $logoFile = $LogoForm->get('Logo')->getData();
                
                if ($logoFile) {
                    $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = 'logo.'.$logoFile->guessExtension();
                    try {
                        $logoFile->move($logoDirectory, $newFilename);
                    } catch (FileException $e) {
                        dump('File upload error: ' . $e->getMessage());
                    }
                }
            }

            $BackGroundForm = $this->createForm(BackGroundFormType::class);
            $BackGroundForm->handleRequest($request);
            if ($BackGroundForm->isSubmitted() && $BackGroundForm->isValid()){
                $backgroundFile = $BackGroundForm->get('BackGround')->getData();
                
                if ($backgroundFile) {
                    $originalFilename = pathinfo($backgroundFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = 'background.'.$backgroundFile->guessExtension();
                    try {
                        $backgroundFile->move($backgroundDirectory, $newFilename);
                    } catch (FileException $e) {
                        dump('File upload error: ' . $e->getMessage());
                    }
                }
            }

            $users = [];
            $query = $em->createQuery(
                'SELECT u
                FROM App\Entity\User u
                JOIN u.profile p
                ORDER BY p.name ASC'
            );
            $users = $query->getResult();

            $logger->info('{userName} laad rechten instellen', [
                'userName' => $this->getUser()->getProfile()->getName()
            ]);

            return $this->render( 'admin/settings.html.twig',[
                'settings' => $ExistingSettings,
                'favIconForm' => $IconForm->createView(),
                'logoForm' => $LogoForm->createView(),
                'backgroundForm' => $BackGroundForm->createView(),
                'departments' => $this->getDepartmentLimits($em),
                'users' => $users
            ]);
        } else {
            $logger->error('Niet SuperAdmin {userName} probeerde op rechten instellen te komen', [
                'userName' => $this->getUser()->getProfile()->getName()
            ]);
            return $this->render('security/403_page.html.twig');
        }
    }

    #[Route('/admin/settings/updateColor', name: 'settings_update_color')]
    public function updateColors (EntityManagerInterface $em, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $colorType = $data['colorType'];
        $color = $data['value'];

        $settings = $em->getRepository(GlobalSettings::class) -> findOneBy([]);

        $editColors= $settings->getColors();
        $editColors[$colorType] = $color;
        $settings->setColors($editColors);
        $em->persist( $settings );
        $em->flush();

        return new JsonResponse(['status' => 'success']);
    }
    #[Route('/admin/settings/updateSignOff', name:'settings_update_sign_off')]
    public function updateSignOff (EntityManagerInterface $em, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $person = $data['persoon'];
        $phone = $data['telefoon'];
        $settings = $em->getRepository(GlobalSettings::class)->findOneBy([]);
        $settings->setSignOff([
            $person => $phone,
        ]);
        $em->persist( $settings );
        $em->flush();
        return new JsonResponse(['status'=> 'success']);
        
    } 
    #[Route('/admin/settings/updateDepartmentColor', name: 'settings_update_department_color')]
    public function updateDepartmentColor (EntityManagerInterface $em, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $colorType = $data['colorType'];
        $color = $data['value'];

        $settings = $em->getRepository(GlobalSettings::class) -> findOneBy([]);

        $editColors= $settings->getDepartmentColor();
        $editColors[$colorType] = $color;
        $settings->setDepartmentColor($editColors);
        $em->persist( $settings );
        $em->flush();

        return new JsonResponse(['status' => 'success']);
    }

    #[Route('/admin/settings/deleteDepartment', name: 'settings_delete_department')]
    public function deleteDepartment (EntityManagerInterface $em, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $value = $data['value'];

        $settings = $em->getRepository(GlobalSettings::class) -> findOneBy([]);

        $editDepartments = $settings->getDepartments(); //array
        $editDepartmentColor = $settings->getDepartmentColor(); //dictonary

        // Check if the value exists in the array
        if (($key = array_search($value, $editDepartments)) !== false) {
            // Remove the value from the array
            unset($editDepartments[$key]);
            unset($editDepartmentColor[$value]);

    
            // Re-index the array to avoid gaps in the keys
            $editDepartments = array_values($editDepartments);
    
            // Update the departments in the settings
            $settings->setDepartments($editDepartments);
            $settings->setDepartmentColor($editDepartmentColor);
    
            // Persist the updated settings entity
            $em->persist($settings);
            $em->flush();
    
            return new JsonResponse(['status' => 'success']);
        }
    
        return new JsonResponse(['status' => 'error', 'message' => 'Department not found'], 404);
    }

    #[Route('/admin/settings/addDepartment', name: 'settings_add_department')]
    public function addDepartment(EntityManagerInterface $em, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $value = $data['value'] ?? null;
        $color = $data['color'] ?? '#BB00BB';
        $maxEmployees = isset($data['maxEmployees']) ? (int)$data['maxEmployees'] : null;

        if (!$value || $maxEmployees === null || $maxEmployees < 0) {
            return new JsonResponse(['status' => 'error', 'message' => 'Ongeldige invoer'], 400);
        }

        // Fetch the settings entity
        $settings = $em->getRepository(GlobalSettings::class)->findOneBy([]);

        // Get the current departments array
        $editDepartments = $settings->getDepartments();
        $editDepartmentColor = $settings->getDepartmentColor();

        foreach ($editDepartments as $existingDepartment) {
            if (mb_strtolower(trim($existingDepartment)) === mb_strtolower(trim($value))) {
                return new JsonResponse(['status' => 'error', 'message' => 'Afdeling bestaat al'], 409);
            }
        }

        // Add the new value to the departments array
        $editDepartments[] = $value;
        $editDepartmentColor[$value] = $color;

        // Set the updated departments array back to the entity
        $settings->setDepartments($editDepartments);
        $settings->setDepartmentColor($editDepartmentColor);

        // Persist and flush the changes
        $em->persist($settings);

        $departmentLimit = $em->getRepository(DepartmentLimit::class)->findOneBy(['department' => $value]);
        if (!$departmentLimit) {
            $departmentLimit = new DepartmentLimit();
            $departmentLimit->setDepartment($value);
        }
        $departmentLimit->setMaxEmployees($maxEmployees);
        $em->persist($departmentLimit);

        $em->flush();

        return new JsonResponse(['status' => 'success']);
    }

    //aantal vrije dagen controller functie
    #[Route('/admin/settings/AVD', name: 'settings_AVD')]
    public function AVD(EntityManagerInterface $em, Request $request) : JsonResponse 
    {
        $data = json_decode($request->getContent(), true);

        $value = $data['value'];

        $settings = $em->getRepository(GlobalSettings::class)->findOneBy([]);

        $settings->setAVD($value);

        $em->persist($settings);
        $em->flush();

        return new JsonResponse(['status' => 'succes']);
    }

    // maximaal aantal medewerkers per afdeling per dag
    #[Route('/admin/settings/updateDepartmentMax', name: 'max_departments')]
    public function updateDepartmentMax(Request $request, EntityManagerInterface $em): JsonResponse
    {
  
    $data = json_decode($request->getContent(), true);

    if (isset($data['department']) && isset($data['maxEmployees'])) {
        $departmentName = $data['department'];
        $maxEmployees = $data['maxEmployees'];

        if ($maxEmployees < 0) {
            return new JsonResponse(['success' => false, 'message' => 'Aantal mag niet negatief zijn'], 400);
        }

        $departmentLimit = $em->getRepository(DepartmentLimit::class)->findOneBy(['department' => $departmentName]);

        if (!$departmentLimit) {
            $departmentLimit = new DepartmentLimit();
            $departmentLimit->setDepartment($departmentName);
        }
      
        $departmentLimit->setMaxEmployees($maxEmployees);
       
        $em->persist($departmentLimit);
        $em->flush();
       
        return new JsonResponse(['success' => true]);
    }

    
    return new JsonResponse(['success' => false, 'message' => 'Invalid data'], 400);
}


#[Route('/admin/settings', name: 'admin_settings')]
public function settings(EntityManagerInterface $em)
{
   
    $departments = $em->getRepository(DepartmentLimit::class)->findAll();

   
    if (!$departments) {
        throw $this->createNotFoundException('Geen afdelingen gevonden.');
    }

 
    return $this->render('admin/settings.html.twig', [
        'departments' => $departments,  
    ]);
}

#[Route('/admin/settings/assign_rights/edit-roles/{id}', name: 'user_edit_roles', methods: ['POST'])]
public function editRoles( int $id, Request $request, EntityManagerInterface $em, LoggerInterface $logger): Response
{
    // Fetch the user by ID
    $user = $em->getRepository(\App\Entity\User::class)->find($id);

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

    $logger->info('{editor} veranderde de rechten van {user} naar {rechten}', [
        'editor' => $this->getUser()->getProfile()->getName(),
        'user' => $user->getProfile()->getName(),
        'rechten' => $selectedRole
    ]);

    return $this->redirectToRoute('settings', ['open' => 'permissionsSettings']);
}

private function ensureDepartmentLimitTableExists(EntityManagerInterface $em): void
{
    $schemaTool = new SchemaTool($em);
    $metadata = $em->getClassMetadata(DepartmentLimit::class);

    try {
        $em->getConnection()->executeQuery('SELECT 1 FROM department_limit LIMIT 1');
    } catch (TableNotFoundException $e) {
     
        try {
            $schemaTool->createSchema([$metadata]);
        } catch (\Exception $ex) {
            throw new \RuntimeException('Failed to create the department_limit table: ' . $ex->getMessage());
        }
    }
}

private function getDepartmentLimits(EntityManagerInterface $em): array
{
    $this->ensureDepartmentLimitTableExists($em); 

    $departmentLimits = $em->getRepository(DepartmentLimit::class)->findAll();
    $result = [];
    foreach ($departmentLimits as $limit) {
        $result[$limit->getDepartment()] = [
            'maxEmployees' => $limit->getMaxEmployees(),
        ];
    }
    return $result;
}
private function handleFileUpload($file, $directory, $newFilename, LoggerInterface $logger)
{
    if ($file) {
        try {
            $file->move($directory, $newFilename);
            return true;
        } catch (FileException $e) {
            $logger->error('File upload error: ' . $e->getMessage());
            return false;
        }
    }
    return false;
}

}