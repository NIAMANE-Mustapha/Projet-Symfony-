<?php

namespace App\Controller;

use App\Entity\ContenuPanier;
use App\Entity\Panier;
use App\Entity\Produit;
use App\Entity\Utilisateur;
use App\Repository\ContenuPanierRepository;
use App\Repository\PanierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contrôleur gérant le panier d'achat de l'utilisateur connecté.
 */
#[IsGranted('ROLE_USER')]
class PanierController extends AbstractController
{
    /**
     * Affiche le panier en cours (non payé) de l'internaute connecté.
     */
    #[Route('/panier', name: 'app_panier', methods: ['GET'])]
    public function index(PanierRepository $panierRepository): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        // Récupération du panier actif non payé (etat = false)
        $panier = $panierRepository->findActiveCartForUser($user);

        return $this->render('panier/index.html.twig', [
            'panier' => $panier,
        ]);
    }

    /**
     * Ajoute un produit au panier de l'internaute connecté.
     */
    #[Route('/panier/ajouter/{id}', name: 'app_panier_add', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function add(
        Produit $produit,
        Request $request,
        PanierRepository $panierRepository,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator
    ): Response {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('add_to_cart_' . $produit->getId(), (string) $token)) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_produit_show', ['id' => $produit->getId()]);
        }

        // Récupération de la quantité demandée
        $quantite = max(1, (int) $request->request->get('quantite', 1));

        // Vérification de la disponibilité du stock
        if ($produit->getStock() < $quantite) {
            $this->addFlash('danger', $translator->trans('flash.insufficient_stock', ['%stock%' => $produit->getStock()]));
            return $this->redirectToRoute('app_produit_show', ['id' => $produit->getId()]);
        }

        // Récupération ou création du panier actif (non payé)
        $panier = $panierRepository->findActiveCartForUser($user);
        if (!$panier) {
            $panier = new Panier();
            $panier->setUtilisateur($user);
            $panier->setEtat(false);
            $entityManager->persist($panier);
        }

        // Vérifier si le produit est déjà présent dans le panier
        $existingItem = null;
        foreach ($panier->getContenuPaniers() as $item) {
            if ($item->getProduit() && $item->getProduit()->getId() === $produit->getId()) {
                $existingItem = $item;
                break;
            }
        }

        if ($existingItem) {
            $nouvelleQuantite = $existingItem->getQuantite() + $quantite;
            if ($produit->getStock() < $nouvelleQuantite) {
                $this->addFlash('danger', $translator->trans('flash.insufficient_stock', ['%stock%' => $produit->getStock()]));
                return $this->redirectToRoute('app_produit_show', ['id' => $produit->getId()]);
            }
            $existingItem->setQuantite($nouvelleQuantite);
            $existingItem->setDate(new \DateTimeImmutable());
        } else {
            $contenuPanier = new ContenuPanier();
            $contenuPanier->setPanier($panier);
            $contenuPanier->setProduit($produit);
            $contenuPanier->setQuantite($quantite);
            $contenuPanier->setDate(new \DateTimeImmutable());
            $panier->addContenuPanier($contenuPanier);
            $entityManager->persist($contenuPanier);
        }

        $entityManager->flush();

        $this->addFlash('success', $translator->trans('flash.added_to_cart'));

        return $this->redirectToRoute('app_panier');
    }

    /**
     * Supprime un article du panier.
     */
    #[Route('/panier/supprimer/{id}', name: 'app_panier_remove', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function remove(
        ContenuPanier $contenuPanier,
        Request $request,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator
    ): Response {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        // Sécurité : s'assurer que le contenu appartient bien au panier de l'utilisateur connecté
        if ($contenuPanier->getPanier()?->getUtilisateur() !== $user || $contenuPanier->getPanier()?->isEtat()) {
            $this->addFlash('danger', $translator->trans('flash.access_denied'));
            return $this->redirectToRoute('app_panier');
        }

        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete_item_' . $contenuPanier->getId(), (string) $token)) {
            $panier = $contenuPanier->getPanier();
            $panier->removeContenuPanier($contenuPanier);
            $entityManager->remove($contenuPanier);
            $entityManager->flush();

            $this->addFlash('success', $translator->trans('flash.removed_from_cart'));
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_panier');
    }

    /**
     * Achat et validation du panier en cours.
     */
    #[Route('/panier/acheter', name: 'app_panier_checkout', methods: ['POST'])]
    public function checkout(
        Request $request,
        PanierRepository $panierRepository,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator
    ): Response {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('cart_checkout', (string) $token)) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_panier');
        }

        $panier = $panierRepository->findActiveCartForUser($user);
        if (!$panier || $panier->getContenuPaniers()->isEmpty()) {
            $this->addFlash('danger', $translator->trans('flash.empty_cart_checkout'));
            return $this->redirectToRoute('app_panier');
        }

        // Vérification des stocks pour l'ensemble des articles
        foreach ($panier->getContenuPaniers() as $item) {
            $produit = $item->getProduit();
            if (!$produit || $produit->getStock() < $item->getQuantite()) {
                $this->addFlash('danger', 'Le produit "' . ($produit?->getNom() ?? 'Inconnu') . '" n\'a plus assez de stock disponible.');
                return $this->redirectToRoute('app_panier');
            }
        }

        // Décrémentation des stocks des produits
        foreach ($panier->getContenuPaniers() as $item) {
            $produit = $item->getProduit();
            $produit->setStock($produit->getStock() - $item->getQuantite());
        }

        // Passage à l'état payé (true) et attribution automatique de la date d'achat
        $panier->setEtat(true);
        $panier->setDateAchat(new \DateTimeImmutable());

        $entityManager->flush();

        $this->addFlash('success', $translator->trans('flash.order_success'));

        return $this->redirectToRoute('app_commande_show', ['id' => $panier->getId()]);
    }
}
