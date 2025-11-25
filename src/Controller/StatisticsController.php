<?php

namespace App\Controller;

use App\Service\StatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/statistics')]
#[IsGranted('ROLE_USER')]
class StatisticsController extends AbstractController
{
    #[Route('/', name: 'app_statistics')]
    public function index(StatisticsService $statisticsService): Response
    {
        $stats = $statisticsService->getUserStatistics($this->getUser());
        $chartData = $statisticsService->getChartData($this->getUser());

        return $this->render('statistics/index.html.twig', [
            'stats' => $stats,
            'chartData' => $chartData,
        ]);
    }
}
