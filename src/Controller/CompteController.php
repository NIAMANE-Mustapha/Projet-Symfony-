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

#[IsGranted('ROLE_USER')]
class CompteController extends AbstractController
{
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
            /** @var string|null $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            }

            $entityManager->flush();

            $this->addFlash('success', $translator->trans('flash.profile_updated'));

            return $this->redirectToRoute('app_compte');
        }

        $commandes = $panierRepository->findPurchasedOrdersForUser($user);

        return $this->render('compte/index.html.twig', [
            'form' => $form->createView(),
            'commandes' => $commandes,
        ]);
    }

    #[Route('/commande/{id}', name: 'app_commande_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function showCommande(Panier $commande, TranslatorInterface $translator): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        $isOwner = $commande->getUtilisateur() && $commande->getUtilisateur()->getId() === $user->getId();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!$isOwner && !$isAdmin) {
            $this->addFlash('danger', $translator->trans('flash.access_denied'));
            return $this->redirectToRoute('app_compte');
        }

        if (!$commande->isEtat()) {
            return $this->redirectToRoute('app_panier');
        }

        return $this->render('compte/commande.html.twig', [
            'commande' => $commande,
        ]);
    }
}
