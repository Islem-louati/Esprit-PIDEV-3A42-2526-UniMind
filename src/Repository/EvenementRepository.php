<?php

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evenement>
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    /**
     * Recherche et tri des événements (back office).
     *
     * @param string $q    Mot-clé (titre, lieu)
     * @param string $sort Champ de tri : titre, type, dateDebut, lieu, capaciteMax, statut
     * @param string $order ASC ou DESC
     * @return Evenement[]
     */
    public function searchAndSort(string $q = '', string $sort = 'dateDebut', string $order = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('e');
        if ($q !== '') {
            $qb->andWhere('e.titre LIKE :q OR e.lieu LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }
        $allowedSort = ['titre', 'type', 'dateDebut', 'lieu', 'capaciteMax', 'statut'];
        if (!\in_array($sort, $allowedSort, true)) {
            $sort = 'dateDebut';
        }
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        $qb->orderBy('e.' . $sort, $order);
        return $qb->getQuery()->getResult();
    }

    /**
     * Recherche / filtres (Front Office).
     *
     * @param string $q Recherche texte (titre, description, lieu)
     * @param string $type Valeur enum TypeEvenement (ex: atelier)
     * @param string $statut Valeur enum StatutEvenement (ex: a_venir)
     * @param \DateTimeInterface|null $dateFrom Filtre: e.dateDebut >= dateFrom
     * @param \DateTimeInterface|null $dateTo Filtre: e.dateFin <= dateTo
     * @param string $sort Champ de tri (dateDebut par défaut)
     * @param string $order ASC / DESC
     * @return Evenement[]
     */
    public function searchWithFilters(
        string $q = '',
        string $type = '',
        string $statut = '',
        ?\DateTimeInterface $dateFrom = null,
        ?\DateTimeInterface $dateTo = null,
        string $sort = 'dateDebut',
        string $order = 'ASC'
    ): array {
        $qb = $this->createQueryBuilder('e');

        if ($q !== '') {
            $qb->andWhere('e.titre LIKE :q OR e.description LIKE :q OR e.lieu LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        if ($type !== '') {
            $qb->andWhere('e.type = :type')
                ->setParameter('type', $type);
        }

        if ($statut !== '') {
            $qb->andWhere('e.statut = :statut')
                ->setParameter('statut', $statut);
        }

        if ($dateFrom !== null) {
            $qb->andWhere('e.dateDebut >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo !== null) {
            $qb->andWhere('e.dateFin <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }

        $allowedSort = ['titre', 'type', 'dateDebut', 'dateFin', 'lieu', 'capaciteMax', 'statut'];
        if (!\in_array($sort, $allowedSort, true)) {
            $sort = 'dateDebut';
        }
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        $qb->orderBy('e.' . $sort, $order);

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Evenement[] Returns an array of Evenement objects
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

//    public function findOneBySomeField($value): ?Evenement
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
