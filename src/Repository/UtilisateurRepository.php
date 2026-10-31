<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 */
class UtilisateurRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Utilisateur) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Récupère les utilisateurs inscrits aujourd'hui, triés du plus récent au plus ancien.
     * Utilisé pour la vue SUPER_ADMIN.
     *
     * @return Utilisateur[]
     */
    public function findRegisteredToday(): array
    {
        $todayStart = new \DateTimeImmutable('today midnight');
        $todayEnd = new \DateTimeImmutable('tomorrow midnight -1 second');

        return $this->createQueryBuilder('u')
            ->where('u.dateInscription >= :start')
            ->andWhere('u.dateInscription <= :end')
            ->setParameter('start', $todayStart)
            ->setParameter('end', $todayEnd)
            ->orderBy('u.dateInscription', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
