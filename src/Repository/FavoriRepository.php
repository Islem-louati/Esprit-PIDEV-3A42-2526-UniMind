<?php

namespace App\Repository;

use App\Entity\Favori;
use App\Entity\Evenement;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Favori>
 */
class FavoriRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favori::class);
    }

    public function findOneByEtudiantAndEvenement(User $etudiant, Evenement $evenement): ?Favori
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.etudiant = :etudiant')
            ->andWhere('f.evenement = :evenement')
            ->setParameter('etudiant', $etudiant)
            ->setParameter('evenement', $evenement)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function existsByEtudiantAndEvenement(User $etudiant, Evenement $evenement): bool
    {
        $count = (int) $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.etudiant = :etudiant')
            ->andWhere('f.evenement = :evenement')
            ->setParameter('etudiant', $etudiant)
            ->setParameter('evenement', $evenement)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * @return Favori[]
     */
    public function findFavorisByEtudiantWithEvenement(User $etudiant): array
    {
        return $this->createQueryBuilder('f')
            ->addSelect('e')
            ->innerJoin('f.evenement', 'e')
            ->andWhere('f.etudiant = :etudiant')
            ->setParameter('etudiant', $etudiant)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Evenement[]
     */
    public function findEvenementsFavorisByEtudiant(User $etudiant): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('e')
            ->from(Evenement::class, 'e')
            ->innerJoin(Favori::class, 'f', 'WITH', 'f.evenement = e')
            ->andWhere('f.etudiant = :etudiant')
            ->setParameter('etudiant', $etudiant)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Favori[] Returns an array of Favori objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Favori
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
