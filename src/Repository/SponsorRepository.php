<?php

namespace App\Repository;

use App\Entity\Sponsor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sponsor>
 */
class SponsorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sponsor::class);
    }

    /**
     * @return Sponsor[]
     */
    public function searchAndSort(string $q = '', string $sort = 'nomSponsor', string $order = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('s');

        if ($q !== '') {
            $qb->andWhere('s.nomSponsor LIKE :q OR s.emailContact LIKE :q OR s.domaineActivite LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        $allowedSort = ['nomSponsor', 'typeSponsor', 'emailContact', 'statut'];
        if (!\in_array($sort, $allowedSort, true)) {
            $sort = 'nomSponsor';
        }

        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        $qb->orderBy('s.' . $sort, $order);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Sponsor[]
     */
    public function findSponsorsWithoutContribution(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.evenementSponsors', 'es')
            ->andWhere('es IS NULL')
            ->orderBy('s.nomSponsor', 'ASC')
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Sponsor[] Returns an array of Sponsor objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Sponsor
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
