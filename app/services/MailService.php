<?php
/**
 * app/services/MailService.php
 * Envoi d'emails via PHPMailer (SMTP)
 *
 * Installation PHPMailer : composer require phpmailer/phpmailer
 * OU télécharger manuellement dans /vendor/phpmailer/
 *
 * En cas d'absence de PHPMailer, le service fail gracieusement (log seulement).
 */
class MailService
{
    private array $config;

    public function __construct()
    {
        $this->config = require ROOT . '/config/mail.php';
    }

    /**
     * Envoie un email HTML via SMTP
     */
    public function send(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody
    ): bool {
        // Vérifier si PHPMailer est disponible
        $phpmailerPath = ROOT . '/vendor/phpmailer/PHPMailer.php';
        if (!file_exists($phpmailerPath)) {
            // Fallback : log l'email (ne pas crasher l'app)
            $this->logEmail($toEmail, $subject, $htmlBody);
            return true; // Silencieux en dev si PHPMailer manquant
        }

        try {
            require_once $phpmailerPath;
            require_once ROOT . '/vendor/phpmailer/SMTP.php';
            require_once ROOT . '/vendor/phpmailer/Exception.php';

            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

            // Serveur SMTP
            $mail->isSMTP();
            $mail->Host       = $this->config['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['username'];
            $mail->Password   = $this->config['password'];
            $mail->SMTPSecure = $this->config['encryption'];
            $mail->Port       = $this->config['port'];
            $mail->CharSet    = 'UTF-8';
            $mail->SMTPDebug  = $this->config['debug'];

            // Expéditeur
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);

            // Destinataire
            $mail->addAddress($toEmail, $toName);

            // Contenu
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);

            $mail->send();
            return true;

        } catch (\Exception $e) {
            error_log("[MailService] Erreur envoi email : " . $e->getMessage());
            return false;
        }
    }

    /** Template : notification de nouvelle candidature */
    public function sendNewApplication(
        string $recruteurEmail, string $recruteurName,
        string $candidatNom, string $offreTitre
    ): bool {
        $subject = "🎯 Nouvelle candidature — {$offreTitre}";
        $html    = $this->template("Nouvelle candidature reçue",
            "<p>Bonjour <strong>{$recruteurName}</strong>,</p>
             <p>Une nouvelle candidature a été soumise pour le poste de :</p>
             <p style='font-size:1.2rem;color:#10b981;font-weight:bold;'>{$offreTitre}</p>
             <p><strong>Candidat :</strong> {$candidatNom}</p>
             <p>Connectez-vous à IntelliHire pour examiner cette candidature.</p>"
        );
        return $this->send($recruteurEmail, $recruteurName, $subject, $html);
    }

    /** Template : changement de statut pour le candidat */
    public function sendStatusUpdate(
        string $candidatEmail, string $candidatNom,
        string $offreTitre, string $nouveauStatut
    ): bool {
        $statuts = [
            'En_cours'  => ['🔍 Votre candidature est en cours d\'examen', '#3b82f6'],
            'Entretien' => ['📅 Vous êtes convoqué(e) à un entretien !', '#f59e0b'],
            'Acceptee'  => ['🎉 Félicitations ! Votre candidature est acceptée', '#10b981'],
            'Refusee'   => ['Résultat de votre candidature', '#f43f5e'],
        ];
        $info    = $statuts[$nouveauStatut] ?? ['Mise à jour de votre candidature', '#64748b'];
        $subject = $info[0] . " — {$offreTitre}";
        $html    = $this->template("Mise à jour de candidature",
            "<p>Bonjour <strong>{$candidatNom}</strong>,</p>
             <p>Votre candidature pour le poste de <strong>{$offreTitre}</strong> a été mise à jour :</p>
             <p style='font-size:1.1rem;color:{$info[1]};font-weight:bold;padding:1rem;background:#0f1117;border-radius:8px;'>" .
             htmlspecialchars(str_replace('_', ' ', $nouveauStatut)) . "</p>
             <p>Connectez-vous à IntelliHire pour plus d'informations.</p>"
        );
        return $this->send($candidatEmail, $candidatNom, $subject, $html);
    }

    /** Template HTML générique */
    private function template(string $title, string $body): string
    {
        return "<!DOCTYPE html><html><head><meta charset='utf-8'>
        <style>
          body{font-family:'Segoe UI',sans-serif;background:#0a0f1a;color:#e2e8f0;margin:0;padding:2rem;}
          .card{background:#131b2e;border:1px solid #1e3a5f;border-radius:12px;padding:2rem;max-width:520px;margin:auto;}
          h1{color:#10b981;font-size:1.3rem;border-bottom:1px solid #1e3a5f;padding-bottom:1rem;}
          a.btn{display:inline-block;background:#10b981;color:#fff;padding:.7rem 1.5rem;border-radius:8px;text-decoration:none;margin-top:1rem;}
          .footer{text-align:center;color:#475569;font-size:.75rem;margin-top:1.5rem;}
        </style></head><body>
        <div class='card'>
          <h1>🎯 IntelliHire — {$title}</h1>
          {$body}
          <a class='btn' href='" . BASE_URL . "/index.php'>Accéder à la plateforme</a>
          <div class='footer'>IntelliHire Platform &mdash; Ne pas répondre à cet email.</div>
        </div></body></html>";
    }

    private function logEmail(string $to, string $subject, string $body): void
    {
        $log = ROOT . '/storage/mail.log';
        if (!is_dir(dirname($log))) @mkdir(dirname($log), 0755, true);
        $entry = "[" . date('Y-m-d H:i:s') . "] TO: {$to} | SUBJECT: {$subject}\n";
        @file_put_contents($log, $entry, FILE_APPEND);
    }
}

