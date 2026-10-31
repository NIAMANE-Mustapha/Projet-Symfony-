<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Entity\Utilisateur;
use App\Form\ProfileFormType;
use App\Repository\PanierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contrôleur de l'espace personnel de l'internaute et de l'historique de ses commandes.
 */
#[IsGranted('ROLE_USER')]
class CompteController extends AbstractController
{
    /**
     * Page "Mon compte" : édition du profil et consultation de l'historique des commandes.
     */
    #[Route('/compte', name: 'app_compte', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        PanierRepository $panierRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        TranslatorInterface $translator
    ): Response {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Modification optionnelle du mot de passe
            /** @var string|null $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            $entityManager->flush();

            $this->addFlash('success', $translator->trans('flash.profile_updated'));

            return $this->redirectToRoute('app_compte');
        }

        // Récupération des commandes achetées (etat = true)
        $commandes = $panierRepository->findPurchasedOrdersForUser($user);

        return $this->render('compte/index.html.twig', [
            'form' => $form->createView(),
            'commandes' => $commandes,
        ]);
    }

    /**
     * Page "Contenu de la commande" : affiche les articles et détails d'une commande payée.
     */
    #[Route('/commande/{id}', name: 'app_commande_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function showCommande(Panier $commande, TranslatorInterface $translator): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        // Vérification des droits : l'utilisateur doit être le propriétaire ou posséder le rôle ROLE_ADMIN
        $isOwner = $commande->getUtilisateur() && $commande->getUtilisateur()->getId() === $user->getId();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!$isOwner && !$isAdmin) {
            $this->addFlash('danger', $translator->trans('flash.access_denied'));
            return $this->redirectToRoute('app_compte');
        }

        // Si la commande n'a pas encore été payée, renvoyer vers le panier
        if (!$commande->isEtat()) {
            return $this->redirectToRoute('app_panier');
        }

        return $this->render('compte/commande.html.twig', [
            'commande' => $commande,
        ]);
    }
}
