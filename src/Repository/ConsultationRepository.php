<?php

namespace App\Repository;

use App\Entity\Consultation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Consultation>
 */
class ConsultationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Consultation::class);
    }

    /**
     * Récupère les consultations d'un psychologue triées par date
     */
    public function findByPsyOrderedByDate(User $psy, string $order = 'DESC'): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.psy = :psy')
            ->setParameter('psy', $psy)
            ->orderBy('c.date_redaction', $order)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les consultations d'un étudiant triées par date
     */
    public function findByEtudiantOrderedByDate(User $etudiant, string $order = 'DESC'): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.etudiant = :etudiant')
            ->setParameter('etudiant', $etudiant)
            ->orderBy('c.date_redaction', $order)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère le nombre de consultations par mois pour un psychologue
     */
    public function getStatsParMois(User $psy, int $nombreMois = 6): array
    {
        $dateDebut = new \DateTime();
        $dateDebut->modify("-{$nombreMois} months");
        
        return $this->createQueryBuilder('c')
            ->select('DATE_FORMAT(c.date_redaction, \'%Y-%m\') as mois, COUNT(c.consultation_id) as total')
            ->andWhere('c.psy = :psy')
            ->andWhere('c.date_redaction >= :dateDebut')
            ->setParameter('psy', $psy)
            ->setParameter('dateDebut', $dateDebut->format('Y-m-01'))
            ->groupBy('mois')
            ->orderBy('mois', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les consultations récentes (derniers 30 jours)
     */
    public function findRecentConsultations(User $psy, int $jours = 30): array
    {
        $dateDebut = new \DateTime();
        $dateDebut->modify("-{$jours} days");
        
        return $this->createQueryBuilder('c')
            ->andWhere('c.psy = :psy')
            ->andWhere('c.date_redaction >= :dateDebut')
            ->setParameter('psy', $psy)
            ->setParameter('dateDebut', $dateDebut)
            ->orderBy('c.date_redaction', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les consultations d'un étudiant avec un psychologue spécifique
     */
    public function findByEtudiantAndPsy(User $etudiant, User $psy): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.etudiant = :etudiant')
            ->andWhere('c.psy = :psy')
            ->setParameter('etudiant', $etudiant)
            ->setParameter('psy', $psy)
            ->orderBy('c.date_redaction', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les consultations sans note de satisfaction
     */
    public function findWithoutNotes(User $psy): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.psy = :psy')
            ->andWhere('c.note_satisfaction IS NULL')
            ->setParameter('psy', $psy)
            ->orderBy('c.date_redaction', 'DESC')
            ->getQuery()
            ->getResult();
    }
}