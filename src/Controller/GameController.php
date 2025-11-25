<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\GamePlayer;
use App\Entity\GameList;
use App\Form\GameType;
use App\Repository\GameRepository;
use App\Service\TarotScoreCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/lists/{listId}/games')]
#[IsGranted('ROLE_USER')]
class GameController extends AbstractController
{
    #[Route('/', name: 'app_game_index')]
    public function index(int $listId, EntityManagerInterface $em): Response
    {
        $gameList = $em->getRepository(GameList::class)->find($listId);
        if (!$gameList || $gameList->getOwner() !== $this->getUser()) {
            throw $this->createNotFoundException();
        }
        return $this->render('game/index.html.twig', [
            'list' => $gameList,
            'games' => $gameList->getGames(),
        ]);
    }

    #[Route('/new', name: 'app_game_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        TarotScoreCalculator $scoreCalculator
    ): Response {
        $listId = $request->attributes->get('listId');
        $gameList = $listId ? $entityManager->getRepository(GameList::class)->find($listId) : null;
        if (!$gameList || $gameList->getOwner() !== $this->getUser()) {
            throw $this->createNotFoundException();
        }
        $players = $gameList->getPlayers();
        if ($players->count() < 3) {
            $this->addFlash('warning', 'La liste doit contenir au moins 3 joueurs.');
            return $this->redirectToRoute('app_list_show', ['id' => $gameList->getId()]);
        }
        $game = new Game();
        $game->setPlayedAt(new \DateTime());
        $game->setGameList($gameList);
        $form = $this->createForm(GameType::class, $game, [
            'players' => $players->toArray()
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $taker = $form->get('taker')->getData();
            $ally = $form->get('ally')->getData();
            $numberOfPlayers = $players->count();
            if ($numberOfPlayers == 5 && !$ally) {
                $this->addFlash('error', 'Vous devez sélectionner un allié pour une partie à 5 joueurs.');
                return $this->render('game/new.html.twig', [
                    'form' => $form->createView(),
                    'list' => $gameList,
                ]);
            }
            if ($numberOfPlayers != 5 && $ally) {
                $this->addFlash('error', 'Il ne peut y avoir d\'allié que dans une partie à 5 joueurs.');
                return $this->render('game/new.html.twig', [
                    'form' => $form->createView(),
                    'list' => $gameList,
                ]);
            }
            // Créer les GamePlayer
            foreach ($players as $player) {
                $gamePlayer = new GamePlayer();
                $gamePlayer->setGame($game);
                $gamePlayer->setPlayer($player);
                $gamePlayer->setIsTaker($player === $taker);
                $gamePlayer->setIsAlly($ally && $player === $ally);
                $game->addGamePlayer($gamePlayer);
            }

            // Calculer les scores
            try {
                $scoreCalculator->calculateScores($game);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors du calcul des scores : ' . $e->getMessage());
                return $this->render('game/new.html.twig', [
                    'form' => $form->createView(),
                    'list' => $gameList,
                ]);
            }

            $entityManager->persist($game);
            $entityManager->flush();

            $this->addFlash('success', 'La partie a été enregistrée avec succès.');

            return $this->redirectToRoute('app_game_show', ['listId' => $gameList->getId(), 'gameId' => $game->getId()]);
        }

        return $this->render('game/new.html.twig', [
            'form' => $form->createView(),
            'list' => $gameList,
        ]);
    }

    #[Route('/{gameId}', name: 'app_game_show')]
    public function show(int $listId, int $gameId, GameRepository $gameRepository, EntityManagerInterface $em, TarotScoreCalculator $scoreCalculator): Response
    {
        $gameList = $em->getRepository(GameList::class)->find($listId);
        $game = $gameRepository->find($gameId);
        if (!$gameList || !$game || $game->getGameList()->getId() !== $gameList->getId() || $gameList->getOwner() !== $this->getUser()) {
            throw $this->createNotFoundException();
        }
        return $this->render('game/show.html.twig', [
            'game' => $game,
            'scoreCalculator' => $scoreCalculator,
        ]);
    }

    #[Route('/{gameId}/delete', name: 'app_game_delete', methods: ['POST'])]
    public function delete(Request $request, int $listId, int $gameId, GameRepository $gameRepository, EntityManagerInterface $entityManager): Response
    {
        $game = $gameRepository->find($gameId);
        if (!$game || $game->getGameList()->getId() !== $listId || $game->getGameList()->getOwner() !== $this->getUser()) {
            throw $this->createNotFoundException();
        }
        if ($this->isCsrfTokenValid('delete' . $game->getId(), $request->request->get('_token'))) {
            $entityManager->remove($game);
            $entityManager->flush();
            $this->addFlash('success', 'La partie a été supprimée avec succès.');
        }
        return $this->redirectToRoute('app_game_index', ['listId' => $game->getGameList()->getId()]);
    }
}
