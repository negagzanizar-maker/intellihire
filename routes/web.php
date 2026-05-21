<?php
/**
 * routes/web.php
 * Toutes les routes de l'application IntelliHire
 * Le routeur résout automatiquement Controller/action/param
 * mais on peut aussi déclarer des routes explicites ici.
 *
 * Format : $app->route('pattern/{param}', function($param) { ... });
 *
 * La résolution automatique gère déjà :
 *   dashboard           → DashboardController::index()
 *   auth/login          → AuthController::login()
 *   auth/register       → AuthController::register()
 *   auth/logout         → AuthController::logout()
 *   offres              → OffreController::index()
 *   offres/create       → OffreController::create()
 *   offres/store        → OffreController::store()
 *   offres/detail/3     → OffreController::detail('3')
 *   offres/edit/3       → OffreController::edit('3')
 *   offres/update/3     → OffreController::update('3')
 *   offres/delete/3     → OffreController::delete('3')
 *   candidatures        → CandidatureController::index()
 *   candidatures/postuler/3 → CandidatureController::postuler('3')
 *   candidatures/store  → CandidatureController::store()
 *   candidatures/detail/3 → CandidatureController::detail('3')
 *   candidatures/statut/3 → CandidatureController::updateStatut('3')
 *   entretiens          → EntretienController::index()
 *   entretiens/create/3 → EntretienController::create('3')
 *   entretiens/store    → EntretienController::store()
 *   entretiens/decision/3 → EntretienController::updateDecision('3')
 *   ajax/candidatures   → AjaxController::candidatures()
 *   ajax/notifications  → AjaxController::notifications()
 *   ajax/mark_read      → AjaxController::markRead()
 */

// Routes explicites — quand le nom d'URL diffère du nom de la méthode.
// Le routeur auto gère tous les autres cas (controller/action/param).
// Ce fichier est inclus depuis App::__construct(), donc $this est l'instance App.

/** @var App $this */
$this->route('candidatures/statut/{id}', function ($id) {
    (new CandidatureController())->updateStatut($id);
});

$this->route('entretiens/decision/{id}', function ($id) {
    (new EntretienController())->updateDecision($id);
});

$this->route('ai/candidate-summary/{id}', function ($id) {
    (new AiController())->candidateSummary($id);
});

$this->route('ajax/mark_read', function () {
    (new AjaxController())->markRead();
});

