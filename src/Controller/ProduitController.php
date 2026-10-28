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
use App\Form\ProduitType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

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
    #[Route('/produit/{id}',name: 'app_produit_show',requirements: ['id' => '\d+'],methods: ['GET'])]
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
    
    // Ajouter un nouveau produit (administrateur)
    #[Route('/produit/nouveau', name: 'app_produit_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        if (
            !$this->isGranted('ROLE_ADMIN')
            && !$this->isGranted('ROLE_SUPER_ADMIN')
        ) {
            throw $this->createAccessDeniedException();
        }

        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $photoFile */
            $photoFile = $form->get('photoFile')->getData();

            if ($photoFile) {
                $produit->setPhoto(
                    $this->enregistrerPhoto($photoFile, $slugger)
                );
            }

            $entityManager->persist($produit);
            $entityManager->flush();

            $this->addFlash('success', 'Le produit a été ajouté.');

            return $this->redirectToRoute('app_accueil');
        }

        return $this->render('produit/new.html.twig', [
            'form' => $form,
        ]);
    }

    // Modifier un produit existant (administrateur)
    #[Route('/produit/{id}/modifier', name: 'app_produit_edit', methods: ['GET', 'POST'])]
    public function edit(
        Produit $produit,
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        if (
            !$this->isGranted('ROLE_ADMIN')
            && !$this->isGranted('ROLE_SUPER_ADMIN')
        ) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $photoFile */
            $photoFile = $form->get('photoFile')->getData();

            // Si une nouvelle photo est envoyée, on remplace l'ancien nom.
            if ($photoFile) {
                $produit->setPhoto(
                    $this->enregistrerPhoto($photoFile, $slugger)
                );
            }

            $entityManager->flush();

            $this->addFlash('success', 'Le produit a été modifié.');

            return $this->redirectToRoute('app_produit_show', [
                'id' => $produit->getId(),
            ]);
        }

        return $this->render('produit/edit.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    // Supprimer un produit (administrateur)
    #[Route('/produit/{id}/supprimer', name: 'app_produit_delete', methods: ['POST'])]
    public function delete(
        Produit $produit,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (
            !$this->isGranted('ROLE_ADMIN')
            && !$this->isGranted('ROLE_SUPER_ADMIN')
        ) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid(
            'delete_produit_' . $produit->getId(),
            $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton de sécurité invalide.'
            );
        }

        $entityManager->remove($produit);
        $entityManager->flush();

        $this->addFlash('success', 'Le produit a été supprimé.');

        return $this->redirectToRoute('app_accueil');
    }

    // Enregistrer la photo dans public/uploads/produits
    private function enregistrerPhoto(
        UploadedFile $photo,
        SluggerInterface $slugger
    ): string {
        $nomOriginal = pathinfo(
            $photo->getClientOriginalName(),
            PATHINFO_FILENAME
        );

        $nomSecurise = $slugger->slug($nomOriginal);
        $extension = $photo->guessExtension() ?? 'bin';
        $nomFichier = $nomSecurise . '-' . bin2hex(random_bytes(6))
            . '.' . $extension;

        $dossier = $this->getParameter('kernel.project_dir')
            . '/public/uploads/produits';

        if (!is_dir($dossier)) {
            mkdir($dossier, 0775, true);
        }

        $photo->move($dossier, $nomFichier);

        return $nomFichier;
    }

}
