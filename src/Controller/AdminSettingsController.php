<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\SystemSettings;
use App\Repository\SystemSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/settings')]
#[IsGranted('ROLE_ADMIN')]
class AdminSettingsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SystemSettingsRepository $settingsRepository
    ) {
    }

    #[Route('/', name: 'app_admin_settings')]
    public function index(): Response
    {
        $settings = [
            'default_credits' => $this->settingsRepository->getValue(SystemSettings::DEFAULT_CREDITS, 500),
            'ai_generation_cost' => $this->settingsRepository->getValue(SystemSettings::AI_GENERATION_COST, 100),
            'openai_monthly_limit' => $this->settingsRepository->getValue(SystemSettings::OPENAI_MONTHLY_LIMIT, 1000000),
            'maintenance_mode' => $this->settingsRepository->getValue(SystemSettings::MAINTENANCE_MODE, false),
        ];

        return $this->render('admin/settings/index.html.twig', [
            'settings' => $settings,
        ]);
    }

    #[Route('/update', name: 'app_admin_settings_update', methods: ['POST'])]
    public function update(Request $request): Response
    {
        $settingsToUpdate = [
            SystemSettings::DEFAULT_CREDITS => [
                'value' => $request->request->get('default_credits'),
                'type' => 'integer',
                'description' => 'Domyślna liczba kredytów dla nowych użytkowników'
            ],
            SystemSettings::AI_GENERATION_COST => [
                'value' => $request->request->get('ai_generation_cost'),
                'type' => 'integer',
                'description' => 'Koszt jednej generacji AI w kredytach'
            ],
            SystemSettings::OPENAI_MONTHLY_LIMIT => [
                'value' => $request->request->get('openai_monthly_limit'),
                'type' => 'integer',
                'description' => 'Miesięczny limit tokenów OpenAI'
            ],
            SystemSettings::MAINTENANCE_MODE => [
                'value' => $request->request->get('maintenance_mode') ? 'true' : 'false',
                'type' => 'boolean',
                'description' => 'Tryb konserwacji systemu'
            ],
        ];

        foreach ($settingsToUpdate as $key => $data) {
            if ($data['value'] !== null) {
                $setting = $this->settingsRepository->findByKey($key);
                
                if (!$setting) {
                    $setting = new SystemSettings($key, (string) $data['value'], $data['type']);
                    $setting->setDescription($data['description']);
                    $this->entityManager->persist($setting);
                } else {
                    $setting->setValue((string) $data['value']);
                }
                
                $setting->setUpdatedBy($this->getUser());
            }
        }

        $this->entityManager->flush();
        
        $this->addFlash('success', 'Ustawienia zostały zaktualizowane');

        return $this->redirectToRoute('app_admin_settings');
    }
}