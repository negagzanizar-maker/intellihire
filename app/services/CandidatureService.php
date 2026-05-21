<?php
class CandidatureService extends BaseService
{
    private const CV_MAX_SIZE = 10485760; // 10 MB
    private const CV_ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx'];

    private Competence $competenceModel;
    private OffreService $offreService;
    private NotificationService $notifService;
    private MailService $mailService;
    private AiService $aiService;

    public function __construct()
    {
        $this->model = new Candidature();
        $this->competenceModel = new Competence();
        $this->offreService = new OffreService();
        $this->notifService = new NotificationService();
        $this->mailService = new MailService();
        $this->aiService = new AiService();
    }

    public function soumettreCandidature(array $data): int
    {
        if (empty($data['id_offre']) || empty($data['id_profil'])) {
            throw new InvalidArgumentException("Offre et profil obligatoires.");
        }
        if ($this->model->findByProfilAndOffre($data['id_profil'], $data['id_offre'])) {
            throw new InvalidArgumentException("Vous avez déjà postulé à cette offre.");
        }
        $data['score_matching'] = $this->model->calculerScore($data['id_profil'], $data['id_offre'])['score'];
        return $this->model->create($data);
    }

    public function soumettreDepuisFormulaire(int $id_user, array $user, array $post, array $files): array
    {
        $id_offre    = (int)($post['id_offre'] ?? 0);
        $lettre      = trim($post['lettre_motiv'] ?? '');
        $experience  = (int)($post['experience'] ?? 0);
        $competences = $this->normalizeCompetenceIds($post['competences'] ?? []);
        $cv_parse    = null;

        $cv = $this->storeUploadedCv($files['cv'] ?? null, $id_user);
        $cv_path = $cv['path'];

        if ($cv['absolute_path'] !== null) {
            $cv_parse = $this->aiService->parseCv($cv['absolute_path'], $this->competenceModel->getAll());
            if (!empty($cv_parse['skill_ids'])) {
                $competences = array_values(array_unique(array_merge(
                    $competences,
                    array_map('intval', $cv_parse['skill_ids'])
                )));
            }
            if ($experience <= 0 && (int)($cv_parse['experience'] ?? 0) > 0) {
                $experience = (int)$cv_parse['experience'];
            }
        }

        $profil = $this->model->getProfilByUser($id_user);
        if (!$profil) {
            $id_profil = $this->model->createProfil($id_user, $cv_path, $experience);
        } else {
            $id_profil = (int)$profil['id_profil'];
            if ($cv_path) {
                $this->model->updateProfil($id_profil, $cv_path, $experience);
            }
        }

        if (!empty($competences)) {
            $this->model->attachCompetencesCandidat($id_profil, $competences);
        }

        $id_candidature = $this->soumettreCandidature([
            'id_offre'     => $id_offre,
            'id_profil'    => $id_profil,
            'lettre_motiv' => $lettre,
        ]);

        $this->notifierNouvelleCandidature($id_offre, $id_candidature, $user);

        return [
            'id_candidature' => $id_candidature,
            'cv_parse'       => $cv_parse,
        ];
    }

    public function changerStatut(
        int $id_candidature, string $nouveau_statut,
        string $note, int $id_user
    ): void {
        $ancienne = $this->model->findById($id_candidature);
        if (!$ancienne) throw new InvalidArgumentException("Candidature inexistante.");
        $this->model->updateStatut($id_candidature, $nouveau_statut);
        $this->model->addHistorique($id_candidature, $ancienne['statut'], $nouveau_statut, $note, $id_user);
    }

    public function changerStatutEtNotifier(int $id_candidature, string $nouveau_statut, string $note, int $id_user): void
    {
        $statuts_valides = ['Recue', 'En_cours', 'Entretien', 'Acceptee', 'Refusee'];
        if (!in_array($nouveau_statut, $statuts_valides, true)) {
            throw new InvalidArgumentException("Statut invalide.");
        }

        $this->changerStatut($id_candidature, $nouveau_statut, $note, $id_user);

        $candidature = $this->findById($id_candidature);
        if (!$candidature) {
            return;
        }

        @$this->mailService->sendStatusUpdate(
            $candidature['email'],
            trim($candidature['prenom'] . ' ' . $candidature['nom']),
            $candidature['titre_offre'],
            $nouveau_statut
        );
    }

    public function getProfilByUser(int $id_user): array|false
    {
        return $this->model->getProfilByUser($id_user);
    }

    public function findByProfilAndOffre(int $id_profil, int $id_offre): array|false
    {
        return $this->model->findByProfilAndOffre($id_profil, $id_offre);
    }

    public function calculerScore(int $id_profil, int $id_offre): array
    {
        return $this->model->calculerScore($id_profil, $id_offre);
    }

    private function notifierNouvelleCandidature(int $id_offre, int $id_candidature, array $user): void
    {
        $offre = $this->offreService->findById($id_offre);
        if (!$offre) {
            return;
        }

        $candidatName = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?: 'Candidat';
        $recruteurName = trim(($offre['prenom'] ?? '') . ' ' . ($offre['nom'] ?? ''));

        $this->notifService->notifyNewApplication(
            $candidatName,
            $offre['titre'],
            $id_candidature
        );

        @$this->mailService->sendNewApplication(
            $offre['email'] ?? '',
            $recruteurName,
            $candidatName,
            $offre['titre']
        );
    }

    private function storeUploadedCv(?array $file, int $id_user): array
    {
        if (!$file || empty($file['name']) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['path' => '', 'absolute_path' => null];
        }

        if ((int)$file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException($this->uploadErrorMessage((int)$file['error']));
        }

        $this->validateCvUpload($file);

        $uploadDir = ROOT . '/public/uploads/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new RuntimeException("Impossible de preparer le dossier d'upload.");
        }

        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        $filename = $this->safeCvFilename($id_user, $ext);
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException("Impossible d'enregistrer le CV.");
        }

        return [
            'path'          => 'uploads/' . $filename,
            'absolute_path' => $destination,
        ];
    }

    private function validateCvUpload(array $file): void
    {
        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::CV_ALLOWED_EXTENSIONS, true)) {
            throw new InvalidArgumentException("Format CV non autorise. Utilisez PDF, DOC ou DOCX.");
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0) {
            throw new InvalidArgumentException("Le fichier CV est vide.");
        }
        if ($size > self::CV_MAX_SIZE) {
            throw new InvalidArgumentException("Le CV ne doit pas depasser 10 Mo.");
        }

        $mime = $this->detectMimeType((string)$file['tmp_name']);
        if (!$this->isAllowedCvMime($ext, $mime)) {
            throw new InvalidArgumentException("Type de fichier CV non autorise.");
        }
    }

    private function detectMimeType(string $tmpPath): string
    {
        if (!is_file($tmpPath) || !function_exists('finfo_open')) {
            return '';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if (!$finfo) {
            return '';
        }

        $mime = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        return is_string($mime) ? strtolower($mime) : '';
    }

    private function isAllowedCvMime(string $ext, string $mime): bool
    {
        if ($mime === '') {
            return true;
        }

        $allowed = [
            'pdf'  => ['application/pdf', 'application/x-pdf'],
            'doc'  => [
                'application/msword',
                'application/vnd.ms-office',
                'application/cdfv2',
                'application/x-cfb',
                'application/x-ole-storage',
                'application/octet-stream',
            ],
            'docx' => [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip',
                'application/octet-stream',
            ],
        ];

        foreach ($allowed[$ext] ?? [] as $allowedMime) {
            if ($mime === $allowedMime || str_starts_with($mime, $allowedMime . ';')) {
                return true;
            }
        }

        return false;
    }

    private function safeCvFilename(int $id_user, string $ext): string
    {
        try {
            $token = bin2hex(random_bytes(8));
        } catch (Exception) {
            $token = str_replace('.', '', uniqid('', true));
        }

        return 'cv_' . $id_user . '_' . date('YmdHis') . '_' . $token . '.' . $ext;
    }

    private function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "Le CV depasse la taille autorisee.",
            UPLOAD_ERR_PARTIAL => "Le CV n'a ete envoye que partiellement.",
            UPLOAD_ERR_NO_TMP_DIR => "Dossier temporaire manquant pour l'upload.",
            UPLOAD_ERR_CANT_WRITE => "Impossible d'ecrire le CV sur le disque.",
            UPLOAD_ERR_EXTENSION => "Upload du CV bloque par une extension PHP.",
            default => "Erreur pendant l'upload du CV.",
        };
    }

    private function normalizeCompetenceIds(mixed $ids): array
    {
        return array_values(array_unique(array_filter(
            array_map('intval', (array)$ids),
            fn($id) => $id > 0
        )));
    }

    public function getHistorique(int $id): array { return $this->model->getHistorique($id); }
    public function getStats(): array             { return $this->model->getStatsByStatut(); }
    public function getParMois(): array           { return $this->model->getParMois(); }
    public function countAll(array $f = []): int  { return $this->model->countAll($f); }
}
