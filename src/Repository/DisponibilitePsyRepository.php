<?php
// src/Repository/DisponibilitePsyRepository.php

namespace App\Repository;

use App\Entity\DisponibilitePsy;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DisponibilitePsy>
 */
class DisponibilitePsyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DisponibilitePsy::class);
    }

    /**
     * Trouve les disponibilités d'un psychologue
     */
    public function findByPsy(User $psy)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.user = :psy')
            ->setParameter('psy', $psy)
            ->orderBy('d.date_dispo', 'DESC')
            ->addOrderBy('d.heure_debut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les disponibilités futures d'un psychologue
     */
    public function findFuturesByPsy(User $psy)
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('d')
            ->andWhere('d.user = :psy')
            ->andWhere('d.date_dispo >= :today OR (d.date_dispo = :today_date AND d.heure_fin > :now_time)')
            ->andWhere('d.statut = :statut')
            ->setParameter('psy', $psy)
            ->setParameter('today', $now->format('Y-m-d'))
            ->setParameter('today_date', $now->format('Y-m-d'))
            ->setParameter('now_time', $now->format('H:i:s'))
            ->setParameter('statut', 'disponible')
            ->orderBy('d.date_dispo', 'ASC')
            ->addOrderBy('d.heure_debut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les disponibilités d'un jour spécifique
     */
    public function findByPsyAndDate(User $psy, \DateTimeInterface $date)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.user = :psy')
            ->andWhere('d.date_dispo = :date')
            ->setParameter('psy', $psy)
            ->setParameter('date', $date->format('Y-m-d'))
            ->orderBy('d.heure_debut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un créneau est déjà pris
     */
    public function isCreneauDisponible(User $psy, \DateTimeInterface $date, \DateTimeInterface $heureDebut, \DateTimeInterface $heureFin): bool
    {
        $result = $this->createQueryBuilder('d')
            ->andWhere('d.user = :psy')
            ->andWhere('d.date_dispo = :date')
            ->andWhere('d.statut = :statut')
            ->andWhere('NOT (d.heure_fin <= :debut OR d.heure_debut >= :fin)')
            ->setParameter('psy', $psy)
            ->setParameter('date', $date->format('Y-m-d'))
            ->setParameter('statut', 'disponible')
            ->setParameter('debut', $heureDebut->format('H:i:s'))
            ->setParameter('fin', $heureFin->format('H:i:s'))
            ->getQuery()
            ->getResult();

        return empty($result);
    }

    public function findDisponibilitesDisponibles(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.statut = :statut')
            ->andWhere('d.date_dispo >= :date OR (d.date_dispo = :date AND d.heure_fin >= :time)')
            ->setParameter('statut', 'disponible')
            ->setParameter('date', $date->format('Y-m-d'))
            ->setParameter('time', $date->format('H:i:s'))
            ->orderBy('d.date_dispo', 'ASC')
            ->addOrderBy('d.heure_debut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // ========== MÉTHODES POUR LES FILTRES (CORRIGÉES) ==========

    /**
     * Recherche et filtre les disponibilités avec plusieurs critères
     * 
     * Filtres supportés:
     * - search: Recherche par nom, prénom ou spécialité du psychologue
     * - psy_id: ID du psychologue
     * - type_consultation: Type de consultation (présentiel/en ligne)
     * - date_debut: Date de début (filtre sur date_dispo >= date_debut)
     * - date_fin: Date de fin (filtre sur date_dispo <= date_fin)
     * - heure_debut: Heure minimale (filtre sur heure_debut >= heure_debut)
     * - heure_fin: Heure maximale (filtre sur heure_fin <= heure_fin)
     */
    public function findDisponibilitesAvecFiltres(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('d')
            ->leftJoin('d.user', 'u')
            ->andWhere('d.statut = :statut')
            ->setParameter('statut', 'disponible');

        // Filtre par psychologue
        if (!empty($filters['psy_id'])) {
            $qb->andWhere('d.user = :psy')
               ->setParameter('psy', $filters['psy_id']);
        }

        // Filtre par type de consultation
        if (!empty($filters['type_consultation'])) {
            $qb->andWhere('d.type_consult = :type')
               ->setParameter('type', $filters['type_consultation']);
        }

        // Filtre par date de disponibilité (date_dispo)
        // Date de début: chercher les créneaux à partir de cette date
       if (!empty($filters['date'])) {
    $qb->andWhere('d.date_dispo = :date')
       ->setParameter('date', $filters['date']);
}

        // Filtre par plage horaire
        // Heure de début minimale: les créneaux qui commencent après cette heure
        if (!empty($filters['heure_debut'])) {
            $qb->andWhere('d.heure_debut >= :heure_debut')
               ->setParameter('heure_debut', $filters['heure_debut']);
        }

        // Heure de fin maximale: les créneaux qui se terminent avant cette heure
        if (!empty($filters['heure_fin'])) {
            $qb->andWhere('d.heure_fin <= :heure_fin')
               ->setParameter('heure_fin', $filters['heure_fin']);
        }

        // Recherche par nom de psychologue ou spécialité
        if (!empty($filters['search'])) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(u.nom)', ':search'),
                    $qb->expr()->like('LOWER(u.prenom)', ':search'),
                    $qb->expr()->like('LOWER(u.specialite)', ':search')
                )
            )
            ->setParameter('search', '%' . strtolower($filters['search']) . '%');
        }

        // Seulement les disponibilités futures
        $now = new \DateTime();
        $qb->andWhere('d.date_dispo > :now OR (d.date_dispo = :date AND d.heure_fin > :time)')
           ->setParameter('now', $now->format('Y-m-d'))
           ->setParameter('date', $now->format('Y-m-d'))
           ->setParameter('time', $now->format('H:i:s'));

        // Tri par date puis par heure de début
        return $qb->orderBy('d.date_dispo', 'ASC')
            ->addOrderBy('d.heure_debut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les psychologues qui ont des disponibilités futures
     */
    public function getPsychologuesAvecDisponibilites(): array
    {
        $now = new \DateTime();
        
        // Récupérer les IDs des psychologues avec disponibilités
        $qb = $this->createQueryBuilder('d')
            ->select('IDENTITY(d.user) as user_id')
            ->andWhere('d.statut = :statut')
            ->andWhere('d.date_dispo > :now OR (d.date_dispo = :date AND d.heure_fin > :time)')
            ->setParameter('statut', 'disponible')
            ->setParameter('now', $now->format('Y-m-d'))
            ->setParameter('date', $now->format('Y-m-d'))
            ->setParameter('time', $now->format('H:i:s'))
            ->distinct()
            ->getQuery()
            ->getResult();

        // Extraire les IDs
        $userIds = array_column($qb, 'user_id');

        if (empty($userIds)) {
            return [];
        }

        // Récupérer les utilisateurs
        $em = $this->getEntityManager();
        return $em->createQueryBuilder()
            ->select('u')
            ->from('App\Entity\User', 'u')
            ->where('u.user_id IN (:ids)')
            ->setParameter('ids', $userIds)
            ->orderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de disponibilités futures par psychologue
     */
    public function countDisponibilitesByPsy(User $psy): int
    {
        $now = new \DateTime();
        
        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.dispo_id)')
            ->andWhere('d.user = :psy')
            ->andWhere('d.statut = :statut')
            ->andWhere('d.date_dispo > :now OR (d.date_dispo = :date AND d.heure_fin > :time)')
            ->setParameter('psy', $psy)
            ->setParameter('statut', 'disponible')
            ->setParameter('now', $now->format('Y-m-d'))
            ->setParameter('date', $now->format('Y-m-d'))
            ->setParameter('time', $now->format('H:i:s'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les statistiques de disponibilités
     */
    public function getStatistiques(): array
    {
        $now = new \DateTime();
        
        $results = $this->createQueryBuilder('d')
            ->select('d.statut, COUNT(d.dispo_id) as total')
            ->andWhere('d.date_dispo > :now OR (d.date_dispo = :date AND d.heure_fin > :time)')
            ->setParameter('now', $now->format('Y-m-d'))
            ->setParameter('date', $now->format('Y-m-d'))
            ->setParameter('time', $now->format('H:i:s'))
            ->groupBy('d.statut')
            ->getQuery()
            ->getResult();

        $stats = [
            'disponible' => 0,
            'reserve' => 0,
            'annule' => 0,
            'total' => 0
        ];

        foreach ($results as $result) {
            $stats[$result['statut']] = (int) $result['total'];
            $stats['total'] += (int) $result['total'];
        }

        return $stats;
    }
}