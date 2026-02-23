<?php

namespace App\Repository;

use App\Entity\CategorieMeditation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategorieMeditation>
 */
class CategorieMeditationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategorieMeditation::class);
    }

//    /**
//     * @return CategorieMeditation[] Returns an array of CategorieMeditation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?CategorieMeditation
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

public function findAllWithStats(): array
{
    $results = $this->createQueryBuilder('c')
        ->leftJoin('c.seanceMeditations', 's')
        ->select('c', 'COUNT(s) as nbSeances', 'SUM(s.duree) as dureeTotale')
        ->groupBy('c.categorie_id')
        ->orderBy('nbSeances', 'DESC')
        ->getQuery()
        ->getResult();
    
    // Transformez les résultats pour avoir un format cohérent
    $formattedResults = [];
    foreach ($results as $result) {
        if (is_array($result)) {
            // $result est un tableau [0 => CategorieMeditation, 'nbSeances' => X, 'dureeTotale' => Y]
            $categorie = $result[0];
            $nbSeances = $result['nbSeances'];
            $dureeTotale = $result['dureeTotale'] ?? 0;
        } else {
            // $result est directement la catégorie (peu probable)
            $categorie = $result;
            $nbSeances = 0;
            $dureeTotale = 0;
        }
        
        $formattedResults[] = [
            'categorie' => $categorie,
            'nbSeances' => (int) $nbSeances,
            'dureeTotale' => (int) $dureeTotale
        ];
    }
    
    return $formattedResults;
}


}
