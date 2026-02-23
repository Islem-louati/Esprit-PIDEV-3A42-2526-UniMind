<?php

namespace App\Repository;

use App\Entity\SeanceMeditation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SeanceMeditation>
 */
class SeanceMeditationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SeanceMeditation::class);
    }

//    /**
//     * @return SeanceMeditation[] Returns an array of SeanceMeditation objects
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

//    public function findOneBySomeField($value): ?SeanceMeditation
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

public function getDureeTotale(): int
{
    return $this->createQueryBuilder('s')
        ->select('SUM(s.duree)')
        ->getQuery()
        ->getSingleScalarResult() ?? 0;
}

public function getEvolution6Mois(): array
{
    $date = new \DateTime();
    $date->modify('-5 months');
    $date->modify('first day of this month');
    
    $result = [];
    
    for ($i = 0; $i < 6; $i++) {
        $debutMois = clone $date;
        $finMois = clone $date;
        $finMois->modify('last day of this month');
        
        $nbSeances = $this->createQueryBuilder('s')
            ->select('COUNT(s)')
            ->where('s.created_at BETWEEN :debut AND :fin')
            ->setParameter('debut', $debutMois)
            ->setParameter('fin', $finMois)
            ->getQuery()
            ->getSingleScalarResult();
            
        $result[] = [
            'mois' => $debutMois->format('M Y'),
            'nbSeances' => (int) $nbSeances
        ];
        
        $date->modify('+1 month');
    }
    
    return $result;
}
}
