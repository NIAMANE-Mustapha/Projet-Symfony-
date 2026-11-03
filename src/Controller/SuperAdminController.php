<?php

namespace App\Controller;

use App\Repository\PanierRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
class SuperAdminController extends AbstractController
{
    #[Route('/super-admin', name: 'app_super_admin', methods: ['GET'])]
    public function index(
        PanierRepository $panierRepository,
        UtilisateurRepository $utilisateurRepository
    ): Response {
        $paniersNonAchetes = $panierRepository->findUnpurchasedCarts();
        $utilisateursAujourdhui = $utilisateurRepository->findRegisteredToday();

        return $this->render('super_admin/index.html.twig', [
            'paniers' => $paniersNonAchetes,
            'utilisateurs' => $utilisateursAujourdhui,
        ]);
    }
}
