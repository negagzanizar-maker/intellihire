<?php
class AiService
{
    public function buildDashboardInsights(
        array $statsCandidatures,
        array $statsEntretiens,
        int $offresPubliees,
        int $totalUsers,
        array $candidaturesParOffre,
        array $candidaturesParMois,
        array $entretiensUpcoming,
        array $dernieresCandidatures
    ): array {
        $total      = (int)($statsCandidatures['total'] ?? 0);
        $recues     = (int)($statsCandidatures['recues'] ?? 0);
        $enCours    = (int)($statsCandidatures['en_cours'] ?? 0);
        $entretiens = (int)($statsCandidatures['entretiens'] ?? 0);
        $acceptees  = (int)($statsCandidatures['acceptees'] ?? 0);
        $refusees   = (int)($statsCandidatures['refusees'] ?? 0);

        $insights = [];

        $pending = $recues + $enCours;
        if ($pending > 0) {
            $insights[] = [
                'type'  => $pending >= 5 ? 'warning' : 'focus',
                'title' => 'Priorite de revue',
                'body'  => $pending . ' candidature' . ($pending > 1 ? 's' : '') . ' attendent encore une decision claire. Commencez par les meilleurs scores pour reduire le bruit rapidement.',
                'metric'=> $pending,
            ];
        }

        if ($total > 0) {
            $acceptRate = round($acceptees / $total * 100);
            $interviewRate = round($entretiens / $total * 100);
            $insights[] = [
                'type'  => $acceptRate >= 25 ? 'positive' : 'focus',
                'title' => 'Qualite du pipeline',
                'body'  => $acceptRate . '% des candidatures sont acceptees et ' . $interviewRate . '% sont en phase entretien. Le ratio donne une lecture rapide de la selectivite actuelle.',
                'metric'=> $acceptRate . '%',
            ];
        }

        $topOffer = $this->topOfferByApplications($candidaturesParOffre);
        if ($topOffer) {
            $insights[] = [
                'type'  => 'signal',
                'title' => 'Offre qui attire le plus',
                'body'  => '"' . $topOffer['titre'] . '" concentre ' . (int)$topOffer['nb'] . ' candidature' . ((int)$topOffer['nb'] > 1 ? 's' : '') . '. Utilisez-la comme reference pour ajuster les annonces moins performantes.',
                'metric'=> (int)$topOffer['nb'],
            ];
        }

        $trend = $this->monthlyTrend($candidaturesParMois);
        if ($trend !== null) {
            $insights[] = [
                'type'  => $trend >= 0 ? 'positive' : 'warning',
                'title' => $trend >= 0 ? 'Traction en hausse' : 'Traction a surveiller',
                'body'  => $trend >= 0
                    ? 'Le volume recent progresse de ' . $trend . ' candidature' . ($trend > 1 ? 's' : '') . ' par rapport au mois precedent.'
                    : 'Le volume recent baisse de ' . abs($trend) . ' candidature' . (abs($trend) > 1 ? 's' : '') . '. Revoyez les canaux de sourcing ou la clarte des offres.',
                'metric'=> ($trend >= 0 ? '+' : '') . $trend,
            ];
        }

        if (!empty($entretiensUpcoming)) {
            $insights[] = [
                'type'  => 'focus',
                'title' => 'Entretiens imminents',
                'body'  => count($entretiensUpcoming) . ' entretien' . (count($entretiensUpcoming) > 1 ? 's' : '') . ' sont a preparer. Generez des questions ciblees depuis les fiches candidature avant les rendez-vous.',
                'metric'=> count($entretiensUpcoming),
            ];
        }

        $highScoreCount = 0;
        foreach ($dernieresCandidatures as $candidature) {
            if ((float)($candidature['score_matching'] ?? 0) >= 75) {
                $highScoreCount++;
            }
        }
        if ($highScoreCount > 0) {
            $insights[] = [
                'type'  => 'positive',
                'title' => 'Talents a fort signal',
                'body'  => $highScoreCount . ' candidature' . ($highScoreCount > 1 ? 's recentes ont' : ' recente a') . ' un score superieur a 75%. Elles meritent une revue prioritaire.',
                'metric'=> $highScoreCount,
            ];
        }

        if (empty($insights)) {
            $insights[] = [
                'type'  => 'signal',
                'title' => 'Pipeline en construction',
                'body'  => 'Le systeme manque encore de donnees pour produire des signaux forts. Publiez des offres et centralisez les candidatures pour enrichir les prochaines analyses.',
                'metric'=> $offresPubliees . '/' . $totalUsers,
            ];
        }

        return array_slice($insights, 0, 5);
    }

    public function buildCandidateSummary(array $candidature, array $matching): array
    {
        $candidateName = trim(($candidature['prenom'] ?? '') . ' ' . ($candidature['nom'] ?? ''));
        $offerTitle    = (string)($candidature['titre_offre'] ?? 'ce poste');
        $score         = (float)($candidature['score_matching'] ?? 0);
        $years         = (int)($matching['years'] ?? $candidature['experience'] ?? 0);
        $status        = $this->formatStatus((string)($candidature['statut'] ?? ''));
        $letter        = trim((string)($candidature['lettre_motiv'] ?? ''));
        $matched       = $this->cleanList($matching['matched'] ?? []);
        $missing       = $this->cleanList($matching['missing'] ?? []);

        return [
            'overview'  => $this->buildOverview($candidateName, $offerTitle, $score, $years, $status),
            'strengths' => $this->buildStrengths($matched, $score, $years, $letter),
            'risks'     => $this->buildRisks($missing, $score, $letter),
            'questions' => $this->buildQuestions($matched, $missing, $offerTitle),
            'note'      => $this->buildInternalNote($candidateName, $score, $matched, $missing, $years),
            'meta'      => [
                'mode'         => 'mock-local',
                'generated_at' => date('c'),
            ],
        ];
    }

    public function parseCv(string $absolutePath, array $competences): array
    {
        $text = $this->extractCvText($absolutePath);
        $normalized = $this->normalizeText($text);
        $detectedSkills = [];
        $detectedIds = [];

        foreach ($competences as $competence) {
            $name = trim((string)($competence['nom'] ?? ''));
            if ($name === '') {
                continue;
            }

            $patterns = $this->skillPatterns($name);
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $normalized)) {
                    $detectedSkills[] = $name;
                    $detectedIds[] = (int)$competence['id_competence'];
                    break;
                }
            }
        }

        $years = $this->detectExperienceYears($normalized);

        return [
            'success' => trim($text) !== '' || !empty($detectedSkills) || $years > 0,
            'text_length' => strlen($text),
            'skills' => array_values(array_unique($detectedSkills)),
            'skill_ids' => array_values(array_unique(array_filter($detectedIds))),
            'experience' => $years,
            'summary' => $this->buildCvParserSummary($detectedSkills, $years, strlen($text)),
            'meta' => [
                'mode' => 'local-cv-parser',
                'generated_at' => date('c'),
            ],
        ];
    }

    private function buildOverview(string $candidateName, string $offerTitle, float $score, int $years, string $status): string
    {
        $level = $score >= 75 ? 'forte' : ($score >= 50 ? 'interessante' : ($score >= 25 ? 'partielle' : 'faible'));

        return sprintf(
            '%s presente une correspondance %s pour %s, avec un score de %s%%, %d an%s d experience et un statut actuel: %s.',
            $candidateName ?: 'Ce candidat',
            $level,
            $offerTitle,
            rtrim(rtrim(number_format($score, 2, '.', ''), '0'), '.'),
            $years,
            $years > 1 ? 's' : '',
            $status
        );
    }

    private function buildStrengths(array $matched, float $score, int $years, string $letter): array
    {
        $strengths = [];

        if ($score >= 75) {
            $strengths[] = 'Le score de matching indique une priorite de revue elevee.';
        } elseif ($score >= 50) {
            $strengths[] = 'Le profil dispose d une base pertinente pour avancer en revue.';
        } else {
            $strengths[] = 'Le profil peut etre utile si le poste accepte une montee en competence.';
        }

        if (!empty($matched)) {
            $strengths[] = 'Competences deja alignees: ' . implode(', ', array_slice($matched, 0, 5)) . '.';
        }

        if ($years > 0) {
            $strengths[] = $years . ' an' . ($years > 1 ? 's' : '') . ' d experience a valoriser pendant l entretien.';
        }

        if (strlen($letter) >= 180) {
            $strengths[] = 'La lettre de motivation donne assez de matiere pour preparer des questions ciblees.';
        }

        return array_values(array_unique($strengths));
    }

    private function buildRisks(array $missing, float $score, string $letter): array
    {
        $risks = [];

        if (!empty($missing)) {
            $risks[] = 'Competences a verifier ou compenser: ' . implode(', ', array_slice($missing, 0, 5)) . '.';
        }

        if ($score < 50) {
            $risks[] = 'Le score reste modere: valider rapidement l adequation avec les besoins essentiels du poste.';
        }

        if ($letter === '') {
            $risks[] = 'La lettre de motivation est absente, ce qui limite l analyse de motivation.';
        } elseif (strlen($letter) < 120) {
            $risks[] = 'La lettre est courte: approfondir les motivations et exemples concrets en entretien.';
        }

        if (empty($risks)) {
            $risks[] = 'Aucun risque majeur detecte dans les donnees disponibles; garder une verification humaine.';
        }

        return $risks;
    }

    private function buildQuestions(array $matched, array $missing, string $offerTitle): array
    {
        $questions = [];

        foreach (array_slice($matched, 0, 2) as $skill) {
            $questions[] = 'Pouvez-vous decrire un projet recent ou vous avez utilise ' . $skill . ' de maniere concrete ?';
        }

        foreach (array_slice($missing, 0, 2) as $skill) {
            $questions[] = 'Comment compenseriez-vous votre manque actuel sur ' . $skill . ' pour ce poste ?';
        }

        $questions[] = 'Qu est-ce qui vous attire specifiquement dans le poste ' . $offerTitle . ' ?';

        return array_slice(array_values(array_unique($questions)), 0, 4);
    }

    private function buildInternalNote(string $candidateName, float $score, array $matched, array $missing, int $years): string
    {
        if ($score >= 75) {
            $decision = 'Profil a prioriser pour un entretien.';
        } elseif ($score >= 50) {
            $decision = 'Profil a approfondir avant decision.';
        } else {
            $decision = 'Profil a comparer avec des candidatures plus proches du besoin.';
        }

        return sprintf(
            'Analyse IA locale: %s %s Points forts: %d competence%s alignee%s, %d an%s d experience. Points a verifier: %d competence%s manquante%s.',
            $candidateName ?: 'candidat',
            $decision,
            count($matched),
            count($matched) > 1 ? 's' : '',
            count($matched) > 1 ? 's' : '',
            $years,
            $years > 1 ? 's' : '',
            count($missing),
            count($missing) > 1 ? 's' : '',
            count($missing) > 1 ? 's' : ''
        );
    }

    private function cleanList(array $items): array
    {
        $clean = [];
        foreach ($items as $item) {
            $value = trim((string)$item);
            if ($value !== '') {
                $clean[] = $value;
            }
        }
        return array_values(array_unique($clean));
    }

    private function topOfferByApplications(array $rows): ?array
    {
        $top = null;
        foreach ($rows as $row) {
            if ($top === null || (int)($row['nb'] ?? 0) > (int)($top['nb'] ?? 0)) {
                $top = $row;
            }
        }
        return $top && (int)($top['nb'] ?? 0) > 0 ? $top : null;
    }

    private function monthlyTrend(array $rows): ?int
    {
        if (count($rows) < 2) {
            return null;
        }
        $values = array_values(array_map(fn($row) => (int)($row['total'] ?? 0), $rows));
        return $values[count($values) - 1] - $values[count($values) - 2];
    }

    private function extractCvText(string $absolutePath): string
    {
        if (!is_file($absolutePath)) {
            return '';
        }

        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        if (in_array($ext, ['txt', 'md', 'rtf'], true)) {
            return (string)file_get_contents($absolutePath);
        }

        if ($ext === 'docx') {
            return $this->extractDocxText($absolutePath);
        }

        return $this->extractPrintableText($absolutePath);
    }

    private function extractDocxText(string $absolutePath): string
    {
        if (!class_exists('ZipArchive')) {
            return $this->extractPrintableText($absolutePath);
        }

        $zip = new ZipArchive();
        if ($zip->open($absolutePath) !== true) {
            return '';
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            return '';
        }

        $xml = preg_replace('/<w:tab\\/>/i', ' ', $xml);
        $xml = preg_replace('/<w:br\\/>/i', "\n", $xml);
        return html_entity_decode(trim(strip_tags($xml)), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function extractPrintableText(string $absolutePath): string
    {
        $raw = (string)file_get_contents($absolutePath);
        $raw = preg_replace('/[^\\P{C}\\n\\r\\t]+/u', ' ', $raw) ?? $raw;
        $raw = preg_replace('/[^A-Za-z0-9À-ÿ@\\.\\+\\#\\-\\/\\s]/u', ' ', $raw) ?? $raw;
        return preg_replace('/\\s+/', ' ', $raw) ?? $raw;
    }

    private function normalizeText(string $text): string
    {
        $text = strtolower($text);
        $text = str_replace(['.', ',', ';', ':', '(', ')', '[', ']', '{', '}'], ' ', $text);
        return preg_replace('/\\s+/', ' ', $text) ?? $text;
    }

    private function skillPatterns(string $skill): array
    {
        $normalized = preg_quote(strtolower($skill), '/');
        $patterns = ['/(^|\\s)' . $normalized . '(\\s|$)/u'];

        $aliases = [
            'html/css' => ['html', 'css', 'html css'],
            'javascript' => ['js', 'javascript'],
            'typescript' => ['ts', 'typescript'],
            'node.js' => ['node', 'nodejs', 'node js'],
            'vue.js' => ['vue', 'vuejs', 'vue js'],
            'react' => ['react', 'reactjs', 'react js'],
            'ui/ux' => ['ui ux', 'ux ui', 'product design', 'interface design'],
            'mysql' => ['mysql', 'sql'],
            'postgresql' => ['postgres', 'postgresql'],
        ];

        $key = strtolower($skill);
        foreach ($aliases[$key] ?? [] as $alias) {
            $patterns[] = '/(^|\\s)' . preg_quote($alias, '/') . '(\\s|$)/u';
        }

        return array_values(array_unique($patterns));
    }

    private function detectExperienceYears(string $text): int
    {
        $years = 0;
        if (preg_match_all('/(\\d{1,2})\\s*(\\+)?\\s*(ans?|annees?|years?)\\s*(d\\s*)?(experience)?/u', $text, $matches)) {
            foreach ($matches[1] as $match) {
                $years = max($years, (int)$match);
            }
        }

        if (preg_match_all('/(experience|exp)\\s*[:\\-]?\\s*(\\d{1,2})/u', $text, $matches)) {
            foreach ($matches[2] as $match) {
                $years = max($years, (int)$match);
            }
        }

        return min($years, 50);
    }

    private function buildCvParserSummary(array $skills, int $years, int $textLength): string
    {
        $parts = [];
        if (!empty($skills)) {
            $parts[] = count($skills) . ' competence' . (count($skills) > 1 ? 's detectees' : ' detectee');
        }
        if ($years > 0) {
            $parts[] = $years . ' an' . ($years > 1 ? 's' : '') . ' d experience estimee';
        }
        if ($textLength === 0) {
            $parts[] = 'texte peu exploitable dans ce fichier';
        }

        return $parts ? implode(', ', $parts) . '.' : 'Aucun signal exploitable detecte automatiquement.';
    }

    private function formatStatus(string $status): string
    {
        return trim(str_replace('_', ' ', $status)) ?: 'non renseigne';
    }
}
