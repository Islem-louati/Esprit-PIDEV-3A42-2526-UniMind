<?php

namespace App\Repository;

use App\Entity\RendezVous;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RendezVous>
 */
class RendezVousRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RendezVous::class);
    }

    /**
     * Récupère les rendez-vous d'un psychologue triés par date de début
     */
    public function findByPsyOrderedByDate(User $psy, string $order = 'ASC', ?int $limit = null)
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.disponibilite', 'd')
            ->andWhere('r.psy = :psy')
            ->setParameter('psy', $psy)
            ->orderBy('d.date_dispo', $order)
            ->addOrderBy('d.heure_debut', $order);

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les rendez-vous d'un utilisateur (étudiant) triés par date de début
     */
    public function findByEtudiantOrderedByDate(User $etudiant, string $order = 'DESC')
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.disponibilite', 'd')
            ->andWhere('r.etudiant = :etudiant')
            ->setParameter('etudiant', $etudiant)
            ->orderBy('d.date_dispo', $order)
            ->addOrderBy('d.heure_debut', $order)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les prochains rendez-vous d'un étudiant
     */
    public function findProchainsRendezVous(User $etudiant, int $limit = 5): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('r')
            ->leftJoin('r.disponibilite', 'd')
            ->andWhere('r.etudiant = :etudiant')
            ->andWhere('(d.date_dispo > :now OR (d.date_dispo = :date AND d.heure_fin > :time))')
            ->andWhere('r.statut IN (:statuts)')
            ->setParameter('etudiant', $etudiant)
            ->setParameter('now', $now->format('Y-m-d'))
            ->setParameter('date', $now->format('Y-m-d'))
            ->setParameter('time', $now->format('H:i:s'))
            ->setParameter('statuts', ['demande', 'confirme', 'en-cours'])
            ->orderBy('d.date_dispo', 'ASC')
            ->addOrderBy('d.heure_debut', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche et filtre les rendez-vous d'un étudiant avec plusieurs critères
     */
    public function findByFilters(User $etudiant, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.disponibilite', 'd')
            ->leftJoin('r.psy', 'p')
            ->andWhere('r.etudiant = :etudiant')
            ->setParameter('etudiant', $etudiant);

        // Filtre par psychologue
        if (!empty($filters['psy_id'])) {
            $qb->andWhere('r.psy = :psy')
               ->setParameter('psy', $filters['psy_id']);
        }

        // Filtre par statut
        if (!empty($filters['statut'])) {
            $qb->andWhere('r.statut = :statut')
               ->setParameter('statut', $filters['statut']);
        }

        // Filtre par type de consultation
        if (!empty($filters['type_consultation'])) {
            $qb->andWhere('d.type_consult = :type')
               ->setParameter('type', $filters['type_consultation']);
        }

        // Filtre par date de création (created_at)
        if (!empty($filters['created_at'])) {
            // On filtre sur la date de création (sans tenir compte de l'heure)
            $date = new \DateTime($filters['created_at']);
            $dateStart = $date->format('Y-m-d 00:00:00');
            $dateEnd = $date->format('Y-m-d 23:59:59');
            
            $qb->andWhere('r.created_at BETWEEN :date_start AND :date_end')
               ->setParameter('date_start', $dateStart)
               ->setParameter('date_end', $dateEnd);
        }

        // Recherche par mot-clé (nom du psy ou motif)
        if (!empty($filters['search'])) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(p.nom)', ':search'),
                    $qb->expr()->like('LOWER(p.prenom)', ':search'),
                    $qb->expr()->like('LOWER(r.motif)', ':search')
                )
            )
            ->setParameter('search', '%' . strtolower($filters['search']) . '%');
        }

        // Tri
        $orderBy = $filters['order_by'] ?? 'r.created_at';
        $orderDirection = $filters['order_direction'] ?? 'DESC';
        
        $qb->orderBy($orderBy, $orderDirection);
        
        // Tri secondaire si le tri principal n'est pas sur la date de rendez-vous
        if ($orderBy === 'r.created_at') {
            $qb->addOrderBy('d.date_dispo', $orderDirection)
               ->addOrderBy('d.heure_debut', $orderDirection);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte les rendez-vous par statut pour un étudiant
     */
    public function countByStatut(User $etudiant): array
    {
        $results = $this->createQueryBuilder('r')
            ->select('r.statut, COUNT(r.rendez_vous_id) as total')
            ->andWhere('r.etudiant = :etudiant')
            ->setParameter('etudiant', $etudiant)
            ->groupBy('r.statut')
            ->getQuery()
            ->getResult();

        $counts = [
            'demande' => 0,
            'confirme' => 0,
            'en-cours' => 0,
            'termine' => 0,
            'annule' => 0,
            'absent' => 0
        ];

        foreach ($results as $result) {
            $counts[$result['statut']] = (int) $result['total'];
        }

        return $counts;
    }
}