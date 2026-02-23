<?php

namespace App\Repository;

use App\Entity\EvenementSponsor;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EvenementSponsor>
 */
class EvenementSponsorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EvenementSponsor::class);
    }

    /**
     * @return EvenementSponsor[]
     */
    public function findForOrganisateur(User $organisateur): array
    {
        return $this->createQueryBuilder('es')
            ->leftJoin('es.evenement', 'e')
            ->leftJoin('es.sponsor', 's')
            ->addSelect('e', 's')
            ->andWhere('e.organisateur = :organisateur')
            ->setParameter('organisateur', $organisateur)
            ->orderBy('es.dateContribution', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return EvenementSponsor[]
     */
    public function searchAndSort(string $q = '', string $sort = 'dateContribution', string $order = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('es')
            ->leftJoin('es.evenement', 'e')
            ->leftJoin('es.sponsor', 's')
            ->addSelect('e', 's');

        if ($q !== '') {
            $qb->andWhere('e.titre LIKE :q OR s.nomSponsor LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        $allowedSort = ['dateContribution', 'montantContribution', 'statut', 'typeContribution', 'evenement_titre', 'sponsor_nom'];
        if (!\in_array($sort, $allowedSort, true)) {
            $sort = 'dateContribution';
        }

        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        if ($sort === 'evenement_titre') {
            $qb->orderBy('e.titre', $order);
        } elseif ($sort === 'sponsor_nom') {
            $qb->orderBy('s.nomSponsor', $order);
        } else {
            $qb->orderBy('es.' . $sort, $order);
        }

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return EvenementSponsor[] Returns an array of EvenementSponsor objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?EvenementSponsor
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
