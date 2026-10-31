<?php

namespace App\Controller;

use App\Repository\PanierRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de l'espace réservé au SUPER_ADMIN.
 * Donne accès à la surveillance des paniers abandonnés/en cours et des nouvelles inscriptions du jour.
 */
#[IsGranted('ROLE_SUPER_ADMIN')]
class SuperAdminController extends AbstractController
{
    #[Route('/super-admin', name: 'app_super_admin', methods: ['GET'])]
    public function index(
        PanierRepository $panierRepository,
        UtilisateurRepository $utilisateurRepository
    ): Response {
        // 1. Lister tous les paniers non achetés (etat = false)
        $paniersNonAchetes = $panierRepository->findUnpurchasedCarts();

        // 2. Afficher les utilisateurs inscrits aujourd'hui du plus récent au plus ancien
        $utilisateursAujourdhui = $utilisateurRepository->findRegisteredToday();

        return $this->render('super_admin/index.html.twig', [
            'paniers' => $paniersNonAchetes,
            'utilisateurs' => $utilisateursAujourdhui,
        ]);
    }
}
