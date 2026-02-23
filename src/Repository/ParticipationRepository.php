<?php

namespace App\Repository;

use App\Entity\Participation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Participation>
 */
class ParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participation::class);
    }

    /**
     * @return Participation[]
     */
    public function findByEtudiantWithEvenement(User $etudiant): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.evenement', 'e')
            ->addSelect('e')
            ->andWhere('p.etudiant = :etudiant')
            ->setParameter('etudiant', $etudiant)
            ->orderBy('e.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Participation[]
     */
    public function searchAndSort(string $q = '', string $sort = 'date_inscription', string $order = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.evenement', 'e')
            ->leftJoin('p.etudiant', 'u')
            ->addSelect('e', 'u');

        if ($q !== '') {
            $qb->andWhere('e.titre LIKE :q OR u.nom LIKE :q OR u.prenom LIKE :q OR u.email LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        $allowedSort = ['date_inscription', 'statut', 'evenement_titre'];
        if (!\in_array($sort, $allowedSort, true)) {
            $sort = 'date_inscription';
        }

        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        if ($sort === 'evenement_titre') {
            $qb->orderBy('e.titre', $order);
        } else {
            $qb->orderBy('p.' . $sort, $order);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Participation[]
     */
    public function searchAndSortForOrganisateur(User $organisateur, string $q = '', string $sort = 'date_inscription', string $order = 'DESC'): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.evenement', 'e')
            ->leftJoin('p.etudiant', 'u')
            ->addSelect('e', 'u')
            ->andWhere('e.organisateur = :organisateur')
            ->setParameter('organisateur', $organisateur);

        if ($q !== '') {
            $qb->andWhere('e.titre LIKE :q OR u.nom LIKE :q OR u.prenom LIKE :q OR u.email LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        $allowedSort = ['date_inscription', 'statut', 'evenement_titre'];
        if (!\in_array($sort, $allowedSort, true)) {
            $sort = 'date_inscription';
        }

        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        if ($sort === 'evenement_titre') {
            $qb->orderBy('e.titre', $order);
        } else {
            $qb->orderBy('p.' . $sort, $order);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Participation[]
     */
    public function searchFeedbacks(string $q = '', string $sort = 'feedback_at', string $order = 'DESC', ?User $organisateur = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.evenement', 'e')
            ->leftJoin('p.etudiant', 'u')
            ->leftJoin('e.organisateur', 'o')
            ->addSelect('e', 'u', 'o')
            ->andWhere("(p.note_satisfaction IS NOT NULL OR (p.feedback_commentaire IS NOT NULL AND p.feedback_commentaire <> ''))");

        if ($organisateur !== null) {
            $qb->andWhere('e.organisateur = :organisateur')
                ->setParameter('organisateur', $organisateur);
        }

        if ($q !== '') {
            $qb->andWhere("e.titre LIKE :q OR u.nom LIKE :q OR u.prenom LIKE :q OR u.email LIKE :q OR p.feedback_commentaire LIKE :q")
                ->setParameter('q', '%' . $q . '%');
        }

        $allowedSort = ['feedback_at', 'note_satisfaction', 'evenement_titre', 'etudiant_nom'];
        if (!\in_array($sort, $allowedSort, true)) {
            $sort = 'feedback_at';
        }

        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        if ($sort === 'evenement_titre') {
            $qb->orderBy('e.titre', $order);
        } elseif ($sort === 'etudiant_nom') {
            $qb->orderBy('u.nom', $order);
        } else {
            $qb->orderBy('p.' . $sort, $order);
        }

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Participation[] Returns an array of Participation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Participation
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
