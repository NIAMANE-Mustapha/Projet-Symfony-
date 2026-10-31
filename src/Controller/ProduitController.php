<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
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

/**
 * Contrôleur gérant l'affichage de la fiche produit ainsi que le CRUD pour les administrateurs.
 */
class ProduitController extends AbstractController
{
    /**
     * Fiche détaillée d'un produit.
     */
    #[Route('/produit/{id}', name: 'app_produit_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Produit $produit): Response
    {
        return $this->render('produit/show.html.twig', [
            'produit' => $produit,
        ]);
    }

    /**
     * Création d'un nouveau produit (réservé aux administrateurs).
     */
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
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid('', true) . '.' . $photoFile->guessExtension();

                $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/produits';
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0777, true);
                }

                try {
                    $photoFile->move($uploadsDir, $newFilename);
                    $produit->setPhoto('uploads/produits/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Une erreur est survenue lors de l\'enregistrement de la photo.');
                }
            }

            $entityManager->persist($produit);
            $entityManager->flush();

            $this->addFlash('success', $translator->trans('flash.product_created'));

            return $this->redirectToRoute('app_produit_show', ['id' => $produit->getId()]);
        }

        return $this->render('produit/new.html.twig', [
            'produit' => $produit,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Modification d'un produit existant (réservé aux administrateurs).
     */
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
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid('', true) . '.' . $photoFile->guessExtension();

                $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/produits';
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0777, true);
                }

                try {
                    // Suppression de l'ancienne image si existante
                    if ($produit->getPhoto()) {
                        $oldPath = $this->getParameter('kernel.project_dir') . '/public/' . $produit->getPhoto();
                        if (file_exists($oldPath)) {
                            @unlink($oldPath);
                        }
                    }

                    $photoFile->move($uploadsDir, $newFilename);
                    $produit->setPhoto('uploads/produits/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors du téléversement du fichier.');
                }
            }

            $entityManager->flush();

            $this->addFlash('success', $translator->trans('flash.product_updated'));

            return $this->redirectToRoute('app_produit_show', ['id' => $produit->getId()]);
        }

        return $this->render('produit/edit.html.twig', [
            'produit' => $produit,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Suppression d'un produit (réservé aux administrateurs avec validation CSRF).
     */
    #[Route('/admin/produit/{id}/supprimer', name: 'app_produit_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Produit $produit,
        Request $request,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator
    ): Response {
        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete_product_' . $produit->getId(), (string) $token)) {
            // Suppression de l'image associée du disque
            if ($produit->getPhoto()) {
                $filePath = $this->getParameter('kernel.project_dir') . '/public/' . $produit->getPhoto();
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            $entityManager->remove($produit);
            $entityManager->flush();

            $this->addFlash('success', $translator->trans('flash.product_deleted'));
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_home');
    }
}
