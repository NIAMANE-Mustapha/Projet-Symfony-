<?php

namespace App\Controller;

use App\Entity\ContenuPanier;
use App\Entity\Panier;
use App\Entity\Produit;
use App\Entity\Utilisateur;
use App\Form\ProduitType;
use App\Repository\PanierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ProduitController extends AbstractController
{
    // Fiche détaillée d'un produit
    #[Route('/produit/{id}', name: 'app_produit_show', requirements: ['id' => '\d+'], methods: ['GET'])]
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
        if (!$this->isCsrfTokenValid(
            'ajouter_panier_' . $produit->getId(),
            (string) $request->request->get('_token')
        )) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

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

        // Récupérer le panier non payé ou en créer un nouveau panier
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
    #[Route('/admin/produit/nouveau', name: 'app_produit_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        TranslatorInterface $translator
    ): Response {
        $produit = new Produit();

        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $photoFile */
            $photoFile = $form->get('photoFile')->getData();

            if ($photoFile) {
                try {
                    $produit->setPhoto(
                        $this->enregistrerPhoto($photoFile, $slugger)
                    );
                } catch (FileException $e) {
                    $this->addFlash(
                        'danger',
                        'Une erreur est survenue lors de l’enregistrement de la photo.'
                    );
                }
            }

            $entityManager->persist($produit);
            $entityManager->flush();

            $this->addFlash(
                'success',
                $translator->trans('flash.product_created')
            );

            return $this->redirectToRoute('app_produit_show', [
                'id' => $produit->getId(),
            ]);
        }

        return $this->render('produit/new.html.twig', [
            'produit' => $produit,
            'form' => $form->createView(),
        ]);
    }

    // Modifier un produit existant (administrateur)
    #[Route('/admin/produit/{id}/modifier', name: 'app_produit_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(
        Produit $produit,
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        TranslatorInterface $translator
    ): Response {
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $photoFile */
            $photoFile = $form->get('photoFile')->getData();

            if ($photoFile) {
                try {
                    // Enregistrer  la nouvelle photo
                    $nouvellePhoto = $this->enregistrerPhoto($photoFile, $slugger);

                    // Supprimer l'ancienne photo après l'enregistrement réussi 
                    $anciennePhoto = $produit->getPhoto();

                    if ($anciennePhoto) {
                        $ancienChemin = $this->getParameter('kernel.project_dir')
                            . '/public/' . $anciennePhoto;

                        if (file_exists($ancienChemin)) {
                            @unlink($ancienChemin);
                        }
                    }

                    $produit->setPhoto($nouvellePhoto);
                } catch (FileException $e) {
                    $this->addFlash(
                        'danger',
                        'Une erreur est survenue lors du téléversement de la photo.'
                    );
                }
            }

            $entityManager->flush();

            $this->addFlash(
                'success',
                $translator->trans('flash.product_updated')
            );

            return $this->redirectToRoute('app_produit_show', [
                'id' => $produit->getId(),
            ]);
        }

        return $this->render('produit/edit.html.twig', [
            'produit' => $produit,
            'form' => $form->createView(),
        ]);
    }

    // Supprimer produit (administrateur)
    #[Route('/admin/produit/{id}/supprimer', name: 'app_produit_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Produit $produit,
        Request $request,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator
    ): Response {
        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid(
            'delete_product_' . $produit->getId(),
            (string) $token
        )) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        // Supprimer également la photo du produit
        if ($produit->getPhoto()) {
            $filePath = $this->getParameter('kernel.project_dir')
                . '/public/' . $produit->getPhoto();

            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        $entityManager->remove($produit);
        $entityManager->flush();

        $this->addFlash(
            'success',
            $translator->trans('flash.product_deleted')
        );

        return $this->redirectToRoute('app_home');
    }

    // Enregistrer une photo dans public/uploads/produits
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

        return 'uploads/produits/' . $nomFichier;
    }
}