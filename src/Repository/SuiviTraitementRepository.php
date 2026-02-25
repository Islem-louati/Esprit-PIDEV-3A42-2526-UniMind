<?php

namespace App\Repository;

use App\Entity\SuiviTraitement;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SuiviTraitement>
 */
class SuiviTraitementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SuiviTraitement::class);
    }

    /**
     * Trouve les suivis d'un psychologue
     */
    public function findByPsychologue(User $psychologue): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.traitement', 't')
            ->innerJoin('t.psychologue', 'p')
            ->where('p.user_id = :psychologueId')
            ->setParameter('psychologueId', $psychologue->getId())
            ->orderBy('s.dateSuivi', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les suivis d'un étudiant
     */
    public function findByEtudiant(User $etudiant): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.traitement', 't')
            ->innerJoin('t.etudiant', 'e')
            ->where('e.user_id = :etudiantId')
            ->setParameter('etudiantId', $etudiant->getId())
            ->orderBy('s.dateSuivi', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les suivis non validés d'un psychologue
     */
    public function findNonValidesByPsychologue(User $psychologue): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.traitement', 't')
            ->innerJoin('t.psychologue', 'p')
            ->where('p.user_id = :psychologueId')
            ->andWhere('s.effectue = true')
            ->andWhere('s.valide = false')
            ->setParameter('psychologueId', $psychologue->getId())
            ->orderBy('s.dateSuivi', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les suivis en retard d'un étudiant
     */
    public function findEnRetardByEtudiant(User $etudiant): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.traitement', 't')
            ->innerJoin('t.etudiant', 'e')
            ->where('e.user_id = :etudiantId')
            ->andWhere('s.effectue = false')
            ->andWhere('s.dateSuivi < :today')
            ->setParameter('etudiantId', $etudiant->getId())
            ->setParameter('today', new \DateTime())
            ->orderBy('s.dateSuivi', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les suivis à faire aujourd'hui pour un étudiant
     */
    public function findAujourdhuiByEtudiant(User $etudiant): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.traitement', 't')
            ->innerJoin('t.etudiant', 'e')
            ->where('e.user_id = :etudiantId')
            ->andWhere('s.effectue = false')
            ->andWhere('s.dateSuivi = :today')
            ->setParameter('etudiantId', $etudiant->getId())
            ->setParameter('today', new \DateTime())
            ->orderBy('s.dateSuivi', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les suivis à venir pour un étudiant
     */
    public function findAVenirByEtudiant(User $etudiant): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.traitement', 't')
            ->innerJoin('t.etudiant', 'e')
            ->where('e.user_id = :etudiantId')
            ->andWhere('s.effectue = false')
            ->andWhere('s.dateSuivi > :today')
            ->setParameter('etudiantId', $etudiant->getId())
            ->setParameter('today', new \DateTime())
            ->orderBy('s.dateSuivi', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les suivis par traitement
     */
    public function countByTraitement(int $traitementId): array
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id) as total, SUM(CASE WHEN s.effectue = true THEN 1 ELSE 0 END) as effectues, SUM(CASE WHEN s.valide = true THEN 1 ELSE 0 END) as valides')
            ->innerJoin('s.traitement', 't')
            ->where('t.traitement_id = :traitementId')
            ->setParameter('traitementId', $traitementId)
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * Statistiques des suivis pour un psychologue
     */
    public function getStatistiquesByPsychologue(User $psychologue): array
    {
        $qb = $this->createQueryBuilder('s')
            ->innerJoin('s.traitement', 't')
            ->innerJoin('t.psychologue', 'p')
            ->where('p.user_id = :psychologueId')
            ->setParameter('psychologueId', $psychologue->getId());

        $total = $qb->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $effectues = $qb->select('COUNT(s.id)')
            ->andWhere('s.effectue = true')
            ->getQuery()
            ->getSingleScalarResult();

        $valides = $qb->select('COUNT(s.id)')
            ->andWhere('s.valide = true')
            ->getQuery()
            ->getSingleScalarResult();

        $enAttente = $qb->select('COUNT(s.id)')
            ->andWhere('s.effectue = true')
            ->andWhere('s.valide = false')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total,
            'effectues' => $effectues,
            'valides' => $valides,
            'en_attente' => $enAttente,
            'non_effectues' => $total - $effectues
        ];
    }

    /**
     * Statistiques des suivis pour un étudiant
     */
    public function getStatistiquesByEtudiant(User $etudiant): array
    {
        $qb = $this->createQueryBuilder('s')
            ->innerJoin('s.traitement', 't')
            ->innerJoin('t.etudiant', 'e')
            ->where('e.user_id = :etudiantId')
            ->setParameter('etudiantId', $etudiant->getId());

        $total = $qb->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $effectues = $qb->select('COUNT(s.id)')
            ->andWhere('s.effectue = true')
            ->getQuery()
            ->getSingleScalarResult();

        $valides = $qb->select('COUNT(s.id)')
            ->andWhere('s.valide = true')
            ->getQuery()
            ->getSingleScalarResult();

        $enRetard = $qb->select('COUNT(s.id)')
            ->andWhere('s.effectue = false')
            ->andWhere('s.dateSuivi < :today')
            ->setParameter('today', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total,
            'effectues' => $effectues,
            'valides' => $valides,
            'en_retard' => $enRetard,
            'a_faire' => $total - $effectues
        ];
    }

    /**
     * Trouve les suivis par période
     */
    public function findByPeriode(\DateTimeInterface $dateDebut, \DateTimeInterface $dateFin): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.dateSuivi >= :dateDebut')
            ->andWhere('s.dateSuivi <= :dateFin')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->orderBy('s.dateSuivi', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche de suivis
     */
    public function search(string $terme): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.traitement', 't')
            ->innerJoin('t.etudiant', 'e')
            ->innerJoin('t.psychologue', 'p')
            ->where('s.observations LIKE :terme')
            ->orWhere('s.observationsPsy LIKE :terme')
            ->orWhere('t.titre LIKE :terme')
            ->orWhere('e.nom LIKE :terme')
            ->orWhere('e.prenom LIKE :terme')
            ->orWhere('p.nom LIKE :terme')
            ->orWhere('p.prenom LIKE :terme')
            ->setParameter('terme', '%' . $terme . '%')
            ->orderBy('s.dateSuivi', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
