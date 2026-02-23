<?php

namespace App\Repository;

use App\Entity\Traitement;
use App\Entity\User;
use App\Enum\StatutTraitement;
use App\Enum\CategorieTraitement;
use App\Enum\PrioriteTraitement;
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
            ->setParameter('statut', StatutTraitement::EN_COURS->value)
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
            ->setParameter('statut', StatutTraitement::TERMINE->value)
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
            ->setParameter('statut', StatutTraitement::EN_COURS->value)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Recherche avancée de traitements avec critères multiples
     */
    public function findByCriteria(array $criteria, ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.etudiant', 'e')
            ->leftJoin('t.psychologue', 'p')
            ->addSelect('e', 'p');

        // Filtrage par rôle si utilisateur fourni
        if ($user) {
            if (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_RESPONSABLE_ETUDIANT', $user->getRoles())) {
                // Admin et responsable voient tout
            } elseif (in_array('ROLE_PSYCHOLOGUE', $user->getRoles())) {
                $qb->andWhere('t.psychologue = :user')
                   ->setParameter('user', $user);
            } elseif (in_array('ROLE_ETUDIANT', $user->getRoles())) {
                $qb->andWhere('t.etudiant = :user')
                   ->setParameter('user', $user);
            }
        }

        // Recherche textuelle
        if (isset($criteria['search']) && !empty($criteria['search'])) {
            $qb->andWhere('t.titre LIKE :search OR t.description LIKE :search OR t.type LIKE :search OR t.dosage LIKE :search')
               ->setParameter('search', '%' . $criteria['search'] . '%');
        }

        // Filtre par statut
        if (isset($criteria['statut']) && !empty($criteria['statut'])) {
            $qb->andWhere('t.statut = :statut')
               ->setParameter('statut', $criteria['statut']);
        }

        // Filtre par catégorie
        if (isset($criteria['categorie']) && !empty($criteria['categorie'])) {
            $qb->andWhere('t.categorie = :categorie')
               ->setParameter('categorie', $criteria['categorie']);
        }

        // Filtre par priorité
        if (isset($criteria['priorite']) && !empty($criteria['priorite'])) {
            $qb->andWhere('t.priorite = :priorite')
               ->setParameter('priorite', $criteria['priorite']);
        }

        // Filtre par étudiant
        if (isset($criteria['etudiant_id'])) {
            $qb->andWhere('t.etudiant = :etudiantId')
               ->setParameter('etudiantId', $criteria['etudiant_id']);
        }

        // Filtre par psychologue
        if (isset($criteria['psychologue_id'])) {
            $qb->andWhere('t.psychologue = :psychologueId')
               ->setParameter('psychologueId', $criteria['psychologue_id']);
        }

        // Filtre par plage de dates
        if (isset($criteria['date_debut'])) {
            $qb->andWhere('t.date_debut >= :dateDebut')
               ->setParameter('dateDebut', $criteria['date_debut']);
        }

        if (isset($criteria['date_fin'])) {
            $qb->andWhere('t.date_fin <= :dateFin')
               ->setParameter('dateFin', $criteria['date_fin']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Recherche de traitements avec pagination
     */
    public function findWithPagination(array $criteria = [], int $page = 1, int $limit = 20, ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.etudiant', 'e')
            ->leftJoin('t.psychologue', 'p')
            ->addSelect('e', 'p');

        // Appliquer les mêmes filtres que findByCriteria
        if ($user) {
            if (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_RESPONSABLE_ETUDIANT', $user->getRoles())) {
                // Admin et responsable voient tout
            } elseif (in_array('ROLE_PSYCHOLOGUE', $user->getRoles())) {
                $qb->andWhere('t.psychologue = :user')
                   ->setParameter('user', $user);
            } elseif (in_array('ROLE_ETUDIANT', $user->getRoles())) {
                $qb->andWhere('t.etudiant = :user')
                   ->setParameter('user', $user);
            }
        }

        if (isset($criteria['search']) && !empty($criteria['search'])) {
            $qb->andWhere('t.titre LIKE :search OR t.description LIKE :search OR t.type LIKE :search OR t.dosage LIKE :search')
               ->setParameter('search', '%' . $criteria['search'] . '%');
        }

        if (isset($criteria['statut']) && !empty($criteria['statut'])) {
            $qb->andWhere('t.statut = :statut')
               ->setParameter('statut', $criteria['statut']);
        }

        if (isset($criteria['categorie']) && !empty($criteria['categorie'])) {
            $qb->andWhere('t.categorie = :categorie')
               ->setParameter('categorie', $criteria['categorie']);
        }

        if (isset($criteria['priorite']) && !empty($criteria['priorite'])) {
            $qb->andWhere('t.priorite = :priorite')
               ->setParameter('priorite', $criteria['priorite']);
        }

        // Tri par défaut
        $sortBy = $criteria['sort'] ?? 'created_at';
        $order = $criteria['order'] ?? 'DESC';
        
        $allowedSortFields = ['t.titre', 't.created_at', 't.date_debut', 't.date_fin', 't.statut', 't.priorite', 't.categorie', 't.type', 'e.nom', 'p.nom'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 't.created_at';
        }
        
        $qb->orderBy($sortBy, $order);

        // Pagination
        $offset = ($page - 1) * $limit;
        $qb->setFirstResult($offset)
           ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Compter les traitements selon les critères
     */
    public function countByCriteria(array $criteria = [], ?User $user = null): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(DISTINCT t.traitement_id)');

        // Appliquer les mêmes filtres que findByCriteria
        if ($user) {
            if (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_RESPONSABLE_ETUDIANT', $user->getRoles())) {
                // Admin et responsable voient tout
            } elseif (in_array('ROLE_PSYCHOLOGUE', $user->getRoles())) {
                $qb->andWhere('t.psychologue = :user')
                   ->setParameter('user', $user);
            } elseif (in_array('ROLE_ETUDIANT', $user->getRoles())) {
                $qb->andWhere('t.etudiant = :user')
                   ->setParameter('user', $user);
            }
        }

        if (isset($criteria['search']) && !empty($criteria['search'])) {
            $qb->andWhere('t.titre LIKE :search OR t.description LIKE :search OR t.type LIKE :search OR t.dosage LIKE :search')
               ->setParameter('search', '%' . $criteria['search'] . '%');
        }

        if (isset($criteria['statut']) && !empty($criteria['statut'])) {
            $qb->andWhere('t.statut = :statut')
               ->setParameter('statut', $criteria['statut']);
        }

        if (isset($criteria['categorie']) && !empty($criteria['categorie'])) {
            $qb->andWhere('t.categorie = :categorie')
               ->setParameter('categorie', $criteria['categorie']);
        }

        if (isset($criteria['priorite']) && !empty($criteria['priorite'])) {
            $qb->andWhere('t.priorite = :priorite')
               ->setParameter('priorite', $criteria['priorite']);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Trouver les traitements par statut
     */
    public function findByStatut(string $statut): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.etudiant', 'e')
            ->leftJoin('t.psychologue', 'p')
            ->addSelect('e', 'p')
            ->andWhere('t.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('t.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les traitements par priorité
     */
    public function findByPriorite(string $priorite): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.etudiant', 'e')
            ->leftJoin('t.psychologue', 'p')
            ->addSelect('e', 'p')
            ->andWhere('t.priorite = :priorite')
            ->setParameter('priorite', $priorite)
            ->orderBy('t.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver les traitements par catégorie
     */
    public function findByCategorie(string $categorie): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.etudiant', 'e')
            ->leftJoin('t.psychologue', 'p')
            ->addSelect('e', 'p')
            ->andWhere('t.categorie = :categorie')
            ->setParameter('categorie', $categorie)
            ->orderBy('t.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Rechercher des traitements par texte
     */
    public function searchByKeyword(string $keyword): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.etudiant', 'e')
            ->leftJoin('t.psychologue', 'p')
            ->addSelect('e', 'p')
            ->andWhere('t.titre LIKE :keyword OR t.description LIKE :keyword OR t.type LIKE :keyword OR t.dosage LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('t.created_at', 'DESC')
            ->getQuery()
            ->getResult();
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