<?php

namespace App\Controller;

use App\Entity\GameList;
use App\Entity\GamePlayer;
use App\Service\StatisticsService;
use App\Form\GameListType;
use App\Repository\GameListRepository;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/lists')]
#[IsGranted('ROLE_USER')]
class GameListController extends AbstractController
{
    #[Route('/', name: 'app_list_index')]
    public function index(GameListRepository $repo): Response
    {
        return $this->render('list/index.html.twig', [
            'lists' => $repo->findByOwner($this->getUser()),
        ]);
    }

    #[Route('/new', name: 'app_list_new')]
    public function new(Request $request, PlayerRepository $playerRepository, EntityManagerInterface $em): Response
    {
        $players = $playerRepository->findByOwner($this->getUser());
        if (count($players) < 3) {
            $this->addFlash('warning', 'Ajoutez au moins 3 joueurs avant de créer une liste.');
            return $this->redirectToRoute('app_player_new');
        }

        $list = new GameList();
        $form = $this->createForm(GameListType::class, $list, ['players' => $players]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $list->setOwner($this->getUser());
            $selected = $form->get('players')->getData();
            foreach ($selected as $player) {
                $list->addPlayer($player);
            }
            $em->persist($list);
            $em->flush();
            $this->addFlash('success', 'Liste créée.');
            return $this->redirectToRoute('app_list_show', ['id' => $list->getId()]);
        }

        return $this->render('list/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_list_show')]
    public function show(GameList $gameList, StatisticsService $statisticsService): Response
    {
        if ($gameList->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        $cumulative = $statisticsService->getGameListStatistics($gameList);
        $chartData = $statisticsService->getGameListChartData($gameList);
        return $this->render('list/show.html.twig', [
            'list' => $gameList,
            'cumulative' => $cumulative,
            'chartData' => $chartData,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_list_delete', methods: ['POST'])]
    public function delete(Request $request, GameList $gameList, EntityManagerInterface $em): Response
    {
        if ($gameList->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        if ($this->isCsrfTokenValid('delete' . $gameList->getId(), $request->request->get('_token'))) {
            $em->remove($gameList);
            $em->flush();
            $this->addFlash('success', 'La liste a été supprimée avec succès.');
        }
        return $this->redirectToRoute('app_list_index');
    }
}
