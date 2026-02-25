<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ExportService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Export des utilisateurs en Excel (alternative sans PhpSpreadsheet)
     */
    public function exportToExcel(array $users): Response
    {
        // Créer le contenu Excel en HTML (format .xls)
        $html = $this->generateExcelHtml($users);
        
        // Créer la réponse
        $response = new Response($html);
        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', 'attachment;filename="utilisateurs_' . date('Y-m-d') . '.xls"');
        
        return $response;
    }

    /**
     * Export des utilisateurs en PDF
     */
    public function exportToPdf(array $users): Response
    {
        // Options PDF
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true);
        $pdfOptions->set('isHtml5ParserEnabled', true);
        
        // Instancier Dompdf
        $dompdf = new Dompdf($pdfOptions);
        
        // Générer le HTML
        $html = $this->generatePdfHtml($users);
        
        // Charger le HTML
        $dompdf->loadHtml($html);
        
        // Format de la page
        $dompdf->setPaper('A4', 'landscape');
        
        // Rendre le PDF
        $dompdf->render();
        
        // Créer la réponse
        $response = new Response($dompdf->output());
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment;filename="utilisateurs_' . date('Y-m-d') . '.pdf"');
        
        return $response;
    }

    /**
     * Export des utilisateurs en CSV
     */
    public function exportToCsv(array $users): Response
    {
        // Créer le contenu CSV
        $csv = "ID;Nom;Prénom;Email;CIN;Rôle;Statut;Date d'inscription;Actif;Vérifié\n";
        
        foreach ($users as $user) {
            $csv .= sprintf(
                '%s;%s;%s;%s;%s;%s;%s;%s;%s;%s' . "\n",
                $user->getUserId(),
                $this->escapeCsv($user->getNom()),
                $this->escapeCsv($user->getPrenom()),
                $this->escapeCsv($user->getEmail()),
                $this->escapeCsv($user->getCin() ?? '-'),
                $this->escapeCsv($user->getRole()->value),
                $this->escapeCsv($user->getStatut()),
                $user->getCreatedAt()->format('d/m/Y H:i'),
                $user->isIsActive() ? 'Oui' : 'Non',
                $user->isVerified() ? 'Oui' : 'Non'
            );
        }
        
        // Créer la réponse
        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename="utilisateurs_' . date('Y-m-d') . '.csv"');
        
        return $response;
    }

    /**
     * Générer le HTML pour Excel
     */
    private function generateExcelHtml(array $users): string
    {
        $html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta charset="UTF-8">
            <!--[if gte mso 9]>
            <xml>
                <x:ExcelWorkbook>
                    <x:ExcelWorksheets>
                        <x:ExcelWorksheet>
                            <x:Name>Utilisateurs</x:Name>
                            <x:WorksheetOptions>
                                <x:Print>
                                    <x:ValidPrinterInfo/>
                                </x:Print>
                            </x:WorksheetOptions>
                        </x:ExcelWorksheet>
                    </x:ExcelWorksheets>
                </x:ExcelWorkbook>
            </xml>
            <![endif]-->
            <style>
                body { font-family: Arial, sans-serif; font-size: 11px; }
                table { border-collapse: collapse; width: 100%; }
                th { background-color: #4e73df; color: white; font-weight: bold; padding: 8px; text-align: center; border: 1px solid #ddd; }
                td { padding: 6px; border: 1px solid #ddd; }
                .title { font-size: 16px; font-weight: bold; color: #4e73df; text-align: center; padding: 15px; }
                .header { background-color: #6c757d; color: white; font-weight: bold; }
                .even { background-color: #f8f9fa; }
                .odd { background-color: #ffffff; }
            </style>
        </head>
        <body>
            <div class="title">Liste des utilisateurs - ' . date('d/m/Y') . '</div>
            <table>
                <thead>
                    <tr class="header">
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Email</th>
                        <th>CIN</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Date d\'inscription</th>
                        <th>Actif</th>
                        <th>Vérifié</th>
                    </tr>
                </thead>
                <tbody>';
        
        $row = 1;
        foreach ($users as $user) {
            $rowClass = $row % 2 == 0 ? 'even' : 'odd';
            
            $html .= '
                    <tr class="' . $rowClass . '">
                        <td>' . $user->getUserId() . '</td>
                        <td>' . htmlspecialchars($user->getNom()) . '</td>
                        <td>' . htmlspecialchars($user->getPrenom()) . '</td>
                        <td>' . htmlspecialchars($user->getEmail()) . '</td>
                        <td>' . htmlspecialchars($user->getCin() ?? '-') . '</td>
                        <td>' . htmlspecialchars($user->getRole()->value) . '</td>
                        <td>' . htmlspecialchars($user->getStatut()) . '</td>
                        <td>' . $user->getCreatedAt()->format('d/m/Y H:i') . '</td>
                        <td>' . ($user->isIsActive() ? 'Oui' : 'Non') . '</td>
                        <td>' . ($user->isVerified() ? 'Oui' : 'Non') . '</td>
                    </tr>';
            
            $row++;
        }
        
        $html .= '
                </tbody>
            </table>
            <div style="margin-top: 20px; font-size: 10px; color: #6c757d; text-align: right;">
                Généré le ' . date('d/m/Y à H:i') . ' | Total: ' . count($users) . ' utilisateurs
            </div>
        </body>
        </html>';
        
        return $html;
    }

    /**
     * Générer le HTML pour le PDF
     */
    private function generatePdfHtml(array $users): string
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Liste des utilisateurs</title>
            <style>
                @page { margin: 20px; }
                body { font-family: Arial, sans-serif; font-size: 10px; margin: 0; padding: 0; }
                .header { text-align: center; margin-bottom: 20px; }
                h1 { color: #4e73df; font-size: 18px; margin: 0 0 5px 0; }
                .subtitle { color: #6c757d; font-size: 12px; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th { background-color: #4e73df; color: white; padding: 8px; text-align: left; border: 1px solid #ddd; font-weight: bold; }
                td { padding: 6px; border: 1px solid #ddd; }
                tr:nth-child(even) { background-color: #f8f9fa; }
                .footer { margin-top: 30px; text-align: right; color: #6c757d; font-size: 9px; border-top: 1px solid #eee; padding-top: 10px; }
                .badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 9px; }
                .badge-success { background-color: #1cc88a; color: white; }
                .badge-warning { background-color: #f6c23e; color: white; }
                .badge-danger { background-color: #e74a3b; color: white; }
                .badge-info { background-color: #36b9cc; color: white; }
                .badge-secondary { background-color: #858796; color: white; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Liste des utilisateurs</h1>
                <div class="subtitle">
                    Généré le ' . date('d/m/Y à H:i') . ' | Total: ' . count($users) . ' utilisateurs
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Email</th>
                        <th>CIN</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Inscription</th>
                        <th>Actif</th>
                        <th>Vérifié</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($users as $user) {
            $roleClass = $this->getRoleClass($user->getRole()->value);
            $statusClass = $this->getStatusClass($user->getStatut());
            $activeClass = $user->isIsActive() ? 'badge-success' : 'badge-danger';
            $verifiedClass = $user->isVerified() ? 'badge-success' : 'badge-warning';
            
            $html .= '
                    <tr>
                        <td>' . $user->getUserId() . '</td>
                        <td>' . htmlspecialchars($user->getNom()) . '</td>
                        <td>' . htmlspecialchars($user->getPrenom()) . '</td>
                        <td>' . htmlspecialchars($user->getEmail()) . '</td>
                        <td>' . htmlspecialchars($user->getCin() ?? '-') . '</td>
                        <td><span class="badge ' . $roleClass . '">' . htmlspecialchars($user->getRole()->value) . '</span></td>
                        <td><span class="badge ' . $statusClass . '">' . htmlspecialchars($user->getStatut()) . '</span></td>
                        <td>' . $user->getCreatedAt()->format('d/m/Y H:i') . '</td>
                        <td><span class="badge ' . $activeClass . '">' . ($user->isIsActive() ? 'Oui' : 'Non') . '</span></td>
                        <td><span class="badge ' . $verifiedClass . '">' . ($user->isVerified() ? 'Oui' : 'Non') . '</span></td>
                    </tr>';
        }
        
        $html .= '
                </tbody>
            </table>
            <div class="footer">
                Plateforme UniMind - Système de gestion des utilisateurs<br>
                Page {PAGENO} sur {nbpg}
            </div>
        </body>
        </html>';
        
        return $html;
    }

    /**
     * Échapper les caractères pour CSV
     */
    private function escapeCsv(string $value): string
    {
        if (strpos($value, ';') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
            $value = '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }

    /**
     * Classe CSS pour les rôles
     */
    private function getRoleClass(string $role): string
    {
        $classes = [
            'Etudiant' => 'badge-info',
            'Psychologue' => 'badge-warning',
            'Responsable Etudiant' => 'badge-success',
            'Admin' => 'badge-danger'
        ];
        
        return $classes[$role] ?? 'badge-secondary';
    }

    /**
     * Classe CSS pour les statuts
     */
    private function getStatusClass(string $status): string
    {
        $classes = [
            'actif' => 'badge-success',
            'en_attente' => 'badge-warning',
            'inactif' => 'badge-danger',
            'rejeté' => 'badge-danger'
        ];
        
        return $classes[$status] ?? 'badge-secondary';
    }
}