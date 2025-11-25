<?php

namespace App\Controller;

use App\Repository\GameListRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    #[IsGranted('ROLE_USER')]
    public function index(GameListRepository $gameListRepository): Response
    {
        $lists = $gameListRepository->findByOwner($this->getUser());
        return $this->render('home/index.html.twig', [
            'lists' => $lists,
        ]);
    }
}
