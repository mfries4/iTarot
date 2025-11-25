<?php

namespace App\Controller;

use App\Entity\Player;
use App\Form\PlayerType;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/players')]
#[IsGranted('ROLE_USER')]
class PlayerController extends AbstractController
{
    #[Route('/', name: 'app_player_index')]
    public function index(PlayerRepository $playerRepository): Response
    {
        $players = $playerRepository->findByOwner($this->getUser());

        return $this->render('player/index.html.twig', [
            'players' => $players,
        ]);
    }

    #[Route('/new', name: 'app_player_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $player = new Player();
        $form = $this->createForm(PlayerType::class, $player);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $player->setOwner($this->getUser());
            $entityManager->persist($player);
            $entityManager->flush();

            $this->addFlash('success', 'Le joueur a été ajouté avec succès.');

            return $this->redirectToRoute('app_player_index');
        }

        return $this->render('player/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_player_edit')]
    public function edit(Request $request, Player $player, EntityManagerInterface $entityManager): Response
    {
        if ($player->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(PlayerType::class, $player);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le joueur a été modifié avec succès.');

            return $this->redirectToRoute('app_player_index');
        }

        return $this->render('player/edit.html.twig', [
            'player' => $player,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_player_delete', methods: ['POST'])]
    public function delete(Request $request, Player $player, EntityManagerInterface $entityManager): Response
    {
        if ($player->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $player->getId(), $request->request->get('_token'))) {
            $entityManager->remove($player);
            $entityManager->flush();

            $this->addFlash('success', 'Le joueur a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_player_index');
    }
}
