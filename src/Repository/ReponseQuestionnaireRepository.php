<?php
// src/Repository/ReponseQuestionnaireRepository.php

namespace App\Repository;

use App\Entity\ReponseQuestionnaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReponseQuestionnaire>
 */
class ReponseQuestionnaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReponseQuestionnaire::class);
    }

    /**
     * Compte les réponses entre deux dates
     */
    public function countByDateRange(\DateTime $debut, \DateTime $fin): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.reponse_questionnaire_id)')
            ->where('r.created_at BETWEEN :debut AND :fin')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les réponses avec niveau sévère
     */
    public function countSeveres(): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.reponse_questionnaire_id)')
            ->where('r.niveau = :niveau')
            ->setParameter('niveau', 'sévère')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les réponses d'un étudiant
     */
    public function findByEtudiant(int $etudiantId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.etudiant = :etudiantId')
            ->orderBy('r.created_at', 'DESC')
            ->setParameter('etudiantId', $etudiantId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les réponses pour un questionnaire
     */
    public function findByQuestionnaire(int $questionnaireId): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.questionnaire = :questionnaireId')
            ->orderBy('r.created_at', 'DESC')
            ->setParameter('questionnaireId', $questionnaireId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les réponses avec besoin psy
     */
    public function findWithBesoinPsy(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.a_besoin_psy = :besoin')
            ->orderBy('r.created_at', 'DESC')
            ->setParameter('besoin', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les statistiques par mois
     */
    public function getStatsByMonth(int $annee): array
    {
        return $this->createQueryBuilder('r')
            ->select('MONTH(r.created_at) as mois, COUNT(r.reponse_questionnaire_id) as total')
            ->where('YEAR(r.created_at) = :annee')
            ->groupBy('mois')
            ->orderBy('mois', 'ASC')
            ->setParameter('annee', $annee)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les réponses journalières pour les 30 derniers jours
     */
    public function getDailyResponses(int $jours): array
    {
        $fin = new \DateTime();
        $debut = (clone $fin)->modify("-$jours days");
        
        $results = $this->createQueryBuilder('r')
            ->select('COUNT(r.reponse_questionnaire_id) as count, DATE(r.created_at) as date')
            ->where('r.created_at BETWEEN :debut AND :fin')
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getResult();
        
        // Créer un tableau associatif date => count
        $daily = [];
        foreach ($results as $result) {
            $daily[$result['date']] = (int)$result['count'];
        }
        
        // Remplir tous les jours
        $data = [];
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($debut, $interval, $fin->modify('+1 day'));
        
        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $data[] = $daily[$dateStr] ?? 0;
        }
        
        return $data;
    }
}