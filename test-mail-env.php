<?php
// test-mail-env.php
require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

echo "=== Test de la configuration depuis .env ===\n\n";

// Lisez directement depuis .env
$envFile = __DIR__.'/.env';
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($lines as $line) {
    if (strpos($line, 'MAILER_DSN') === 0) {
        $parts = explode('=', $line, 2);
        $dsn = trim($parts[1], ' "\'');
        echo "DSN trouvé dans .env: " . $dsn . "\n\n";
        
        try {
            // Test de connexion
            $transport = Transport::fromDsn($dsn);
            echo "✅ Connexion Gmail réussie !\n";
            
            // Test d'envoi
            $mailer = new Mailer($transport);
            $email = (new Email())
                ->from('louatiislem74@gmail.com')
                ->to('louatiislem74@gmail.com')
                ->subject('Test depuis .env - ' . date('H:i:s'))
                ->text('Test de configuration .env')
                ->html('<p>Test de <strong>configuration .env</strong></p>');
            
            $mailer->send($email);
            echo "✅ Email envoyé avec succès !\n";
            
        } catch (\Exception $e) {
            echo "❌ Erreur: " . $e->getMessage() . "\n";
            
            // Affichez plus de détails pour le débogage
            echo "\n=== Détails de l'erreur ===\n";
            if (strpos($e->getMessage(), 'ssl') !== false) {
                echo "Problème SSL détecté\n";
                echo "Essayez avec verify_peer=0\n";
            }
            if (strpos($e->getMessage(), 'password') !== false) {
                echo "Problème de mot de passe détecté\n";
                echo "Vérifiez que le mot de passe est correct\n";
            }
        }
        break;
    }
}