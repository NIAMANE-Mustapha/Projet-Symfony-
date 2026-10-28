<?php

namespace App\Controller;

use App\Entity\ContenuPanier;
use App\Entity\Panier;
use App\Entity\Produit;
use App\Entity\Utilisateur;
use App\Repository\PanierRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProduitController extends AbstractController
{
    // Page d'accueil : liste des produits
    #[Route('/', name: 'app_accueil', methods: ['GET'])]
    public function index(ProduitRepository $produitRepository): Response
    {
        return $this->render('produit/index.html.twig', [
            'produits' => $produitRepository->findAll(),
        ]);
    }

    // Fiche détaillée d'un produit
    #[Route('/produit/{id}', name: 'app_produit_show', methods: ['GET'])]
    public function show(Produit $produit): Response
    {
        return $this->render('produit/show.html.twig', [
            'produit' => $produit,
        ]);
    }

    // Ajouter un produit au panier de l'utilisateur connecté
    #[Route('/produit/{id}/ajouter-au-panier', name: 'app_panier_ajouter', methods: ['POST'])]
    public function ajouterAuPanier(
        Produit $produit,
        Request $request,
        PanierRepository $panierRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifier le formulaire contre les requêtes frauduleuses
        if (!$this->isCsrfTokenValid(
            'ajouter_panier_' . $produit->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        // L'utilisateur doit être connecté
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof Utilisateur) {
            throw $this->createAccessDeniedException(
                'Vous devez être connecté pour ajouter un produit au panier.'
            );
        }

        if ($produit->getStock() <= 0) {
            $this->addFlash('danger', 'Ce produit est en rupture de stock.');

            return $this->redirectToRoute('app_produit_show', [
                'id' => $produit->getId(),
            ]);
        }

        // Récupérer le panier non payé ou en créer un nouveau
        $panier = $panierRepository->findOneBy([
            'utilisateur' => $utilisateur,
            'etat' => false,
        ]);

        if (!$panier) {
            $panier = new Panier();
            $panier->setUtilisateur($utilisateur);
            $entityManager->persist($panier);
        }

        // Vérifier si le produit est déjà dans le panier
        $contenuExistant = null;

        foreach ($panier->getContenuPaniers() as $contenu) {
            if ($contenu->getProduit()?->getId() === $produit->getId()) {
                $contenuExistant = $contenu;
                break;
            }
        }

        if ($contenuExistant) {
            if ($contenuExistant->getQuantite() >= $produit->getStock()) {
                $this->addFlash('danger', 'La quantité demandée dépasse le stock disponible.');

                return $this->redirectToRoute('app_produit_show', [
                    'id' => $produit->getId(),
                ]);
            }

            $contenuExistant->setQuantite(
                $contenuExistant->getQuantite() + 1
            );
        } else {
            $contenuPanier = new ContenuPanier();
            $contenuPanier->setProduit($produit);
            $contenuPanier->setQuantite(1);

            $panier->addContenuPanier($contenuPanier);
        }

        $entityManager->flush();

        $this->addFlash('success', 'Le produit a été ajouté à votre panier.');

        return $this->redirectToRoute('app_produit_show', [
            'id' => $produit->getId(),
        ]);
    }
}
