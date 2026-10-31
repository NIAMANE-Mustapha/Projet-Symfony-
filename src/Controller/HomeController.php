<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProduitRepository $produitRepository): Response
    {
        $produits = $produitRepository->findBy([], ['id' => 'DESC']);

        return $this->render('home/index.html.twig', [
            'produits' => $produits,
        ]);
    }
}
