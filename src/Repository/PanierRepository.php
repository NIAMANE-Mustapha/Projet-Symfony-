<?php

namespace App\Repository;

use App\Entity\Panier;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Panier>
 */
class PanierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Panier::class);
    }

    /**
     * Récupère le panier actif (non payé) d'un utilisateur.
     */
    public function findActiveCartForUser(Utilisateur $user): ?Panier
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.contenuPaniers', 'cp')
            ->addSelect('cp')
            ->leftJoin('cp.produit', 'pr')
            ->addSelect('pr')
            ->where('p.utilisateur = :user')
            ->andWhere('p.etat = false')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère l'historique des commandes payées d'un utilisateur, triées par date décroissante.
     *
     * @return Panier[]
     */
    public function findPurchasedOrdersForUser(Utilisateur $user): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.contenuPaniers', 'cp')
            ->addSelect('cp')
            ->leftJoin('cp.produit', 'pr')
            ->addSelect('pr')
            ->where('p.utilisateur = :user')
            ->andWhere('p.etat = true')
            ->setParameter('user', $user)
            ->orderBy('p.dateAchat', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les paniers non payés (pour le SUPER_ADMIN).
     *
     * @return Panier[]
     */
    public function findUnpurchasedCarts(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.utilisateur', 'u')
            ->addSelect('u')
            ->leftJoin('p.contenuPaniers', 'cp')
            ->addSelect('cp')
            ->leftJoin('cp.produit', 'pr')
            ->addSelect('pr')
            ->where('p.etat = false')
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
