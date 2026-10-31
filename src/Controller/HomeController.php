<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur de la page d'accueil affichant la liste des produits.
 */
class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ProduitRepository $produitRepository): Response
    {
        // Récupération de l'ensemble des produits du catalogue
        $produits = $produitRepository->findBy([], ['id' => 'DESC']);

        return $this->render('home/index.html.twig', [
            'produits' => $produits,
        ]);
    }
}
