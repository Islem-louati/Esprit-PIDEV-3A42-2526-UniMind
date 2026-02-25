<?php

namespace App\Repository;

use App\Entity\Traitement;
use App\Entity\User; // Import de l'entité User
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Traitement>
 */
class TraitementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Traitement::class);
    }

    /**
     * Retourne les traitements actifs d'un étudiant.
     *
     * @param User $user L'étudiant concerné
     * @return Traitement[]
     */
    public function findActifsByEtudiant(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.etudiant = :etudiant')
            ->andWhere('t.statut = :statut')
            ->setParameter('etudiant', $user)
            ->setParameter('statut', 'actif')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les traitements terminés d'un étudiant.
     *
     * @param User $user L'étudiant concerné
     * @return Traitement[]
     */
    public function findTerminesByEtudiant(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.etudiant = :etudiant')
            ->andWhere('t.statut = :statut')
            ->setParameter('etudiant', $user)
            ->setParameter('statut', 'terminé')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les traitements actifs d'un étudiant.
     *
     * @param User $user L'étudiant concerné
     * @return int
     */
    public function countActifsByEtudiant(User $user): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.traitement_id)')
            ->andWhere('t.etudiant = :etudiant')
            ->andWhere('t.statut = :statut')
            ->setParameter('etudiant', $user)
            ->setParameter('statut', 'actif')
            ->getQuery()
            ->getSingleScalarResult();
    }

    //    /**
//     * @return Traitement[] Returns an array of Traitement objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Traitement
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}