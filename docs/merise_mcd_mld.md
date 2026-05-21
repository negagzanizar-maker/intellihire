# MCD et MLD Merise - IntelliHire / SmartRecruit

Source principale: `database/schema.sql`

Versions visuelles:

- `docs/mcd_merise.svg`
- `docs/mld_merise.svg`
- `docs/merise_visual.html`

Ce document suit une notation Merise classique:

- Entite: `ENTITE(#identifiant, attribut_1, attribut_2, ...)`
- Association: `ASSOCIATION : ENTITE_A (min,max) -- ENTITE_B (min,max)`
- Dans le MCD, les cles etrangeres ne sont pas placees comme attributs des entites. Elles apparaissent seulement dans le MLD.
- Les tables d'association du MLD proviennent des associations de type `(0,N) -- (0,N)`.

## 1. Regles de gestion

R1. Un utilisateur possede un et un seul role.

R2. Un role peut etre attribue a plusieurs utilisateurs.

R3. Les roles fonctionnels de l'application sont `RECRUTEUR` et `CANDIDAT`.

R4. Un recruteur peut publier plusieurs offres d'emploi.

R5. Une offre d'emploi est publiee par un seul recruteur dans le fonctionnement normal de l'application.

R6. Une offre peut demander plusieurs competences.

R7. Une competence peut etre demandee par plusieurs offres.

R8. Un utilisateur candidat peut posseder au maximum un profil candidat.

R9. Un profil candidat appartient a un seul utilisateur.

R10. Un profil candidat peut posseder plusieurs competences.

R11. Une competence peut etre possedee par plusieurs profils candidats.

R12. Un profil candidat peut deposer plusieurs candidatures.

R13. Une offre peut recevoir plusieurs candidatures.

R14. Un profil candidat ne peut deposer qu'une seule candidature pour une meme offre.

R15. Une candidature concerne une seule offre et un seul profil candidat.

R16. Une candidature peut avoir plusieurs lignes d'historique de statut.

R17. Une ligne d'historique concerne une seule candidature.

R18. Une ligne d'historique peut etre associee a l'utilisateur qui a effectue le changement de statut.

R19. Une candidature peut avoir au maximum un entretien.

R20. Un entretien concerne une seule candidature.

R21. Un utilisateur peut recevoir plusieurs notifications.

R22. Une notification appartient a un seul utilisateur.

## 2. MCD Merise

### 2.1 Entites

```text
ROLE(
  #id_role,
  nom_role
)

UTILISATEUR(
  #id_user,
  nom,
  prenom,
  email,
  mot_de_passe,
  actif,
  created_at,
  updated_at
)

COMPETENCE(
  #id_competence,
  nom,
  created_at
)

OFFRE_EMPLOI(
  #id_offre,
  titre,
  description,
  type_contrat,
  localisation,
  statut,
  created_at,
  updated_at
)

PROFIL_CANDIDAT(
  #id_profil,
  cv_path,
  experience,
  created_at,
  updated_at
)

CANDIDATURE(
  #id_candidature,
  lettre_motiv,
  score_matching,
  statut,
  created_at,
  updated_at
)

CANDIDATURE_HISTORIQUE(
  #id_historique,
  ancien_statut,
  nouveau_statut,
  note,
  created_at
)

ENTRETIEN(
  #id_entretien,
  date_entretien,
  type_entretien,
  lieu_ou_lien,
  decision,
  compte_rendu,
  created_at,
  updated_at
)

NOTIFICATION(
  #id_notif,
  message,
  lien,
  lu,
  created_at
)
```

Remarque Merise: `CANDIDATURE` est modelisee comme une entite associative, et non comme une simple association `POSTULER`, car elle possede son propre identifiant et elle est reliee a d'autres objets metier: historique et entretien.

### 2.2 Associations et cardinalites

```text
AFFECTER_ROLE
ROLE (0,N) ---------------- UTILISATEUR (1,1)

PUBLIER
UTILISATEUR / RECRUTEUR (0,N) ---------------- OFFRE_EMPLOI (1,1)

POSSEDER_PROFIL
UTILISATEUR / CANDIDAT (0,1) ---------------- PROFIL_CANDIDAT (1,1)

EXIGER
OFFRE_EMPLOI (0,N) ---------------- COMPETENCE (0,N)

MAITRISER
PROFIL_CANDIDAT (0,N) ---------------- COMPETENCE (0,N)

DEPOSER
PROFIL_CANDIDAT (0,N) ---------------- CANDIDATURE (1,1)

CONCERNER_OFFRE
OFFRE_EMPLOI (0,N) ---------------- CANDIDATURE (1,1)

TRACER_STATUT
CANDIDATURE (0,N) ---------------- CANDIDATURE_HISTORIQUE (1,1)

EFFECTUER_CHANGEMENT
UTILISATEUR (0,N) ---------------- CANDIDATURE_HISTORIQUE (0,1)

PLANIFIER
CANDIDATURE (0,1) ---------------- ENTRETIEN (1,1)

RECEVOIR_NOTIFICATION
UTILISATEUR (0,N) ---------------- NOTIFICATION (1,1)
```

### 2.3 Contraintes du MCD

```text
C1. UTILISATEUR.email est unique.

C2. ROLE.nom_role est unique.

C3. COMPETENCE.nom est unique.

C4. Un couple (PROFIL_CANDIDAT, OFFRE_EMPLOI) ne peut produire qu'une seule CANDIDATURE.

C5. Un UTILISATEUR ne peut avoir qu'un seul PROFIL_CANDIDAT.

C6. Une CANDIDATURE ne peut avoir qu'un seul ENTRETIEN au maximum.

C7. OFFRE_EMPLOI.type_contrat appartient a:
    CDI, CDD, Stage, Freelance.

C8. OFFRE_EMPLOI.statut appartient a:
    Brouillon, Publiee, Cloturee, Archivee.

C9. CANDIDATURE.statut appartient a:
    Recue, En_cours, Entretien, Acceptee, Refusee.

C10. ENTRETIEN.type_entretien appartient a:
     Presentiel, Visio, Telephonique.

C11. ENTRETIEN.decision appartient a:
     En_attente, Valide, Refuse.
```

## 3. Passage du MCD au MLD

### 3.1 Regles de transformation appliquees

```text
T1. Chaque entite du MCD devient une relation du MLD.

T2. L'identifiant de chaque entite devient la cle primaire de la relation.

T3. Une association de type (1,1) -- (0,N) est transformee par l'ajout
    de la cle primaire de l'entite cote (0,N) comme cle etrangere
    dans la relation cote (1,1).

T4. Une association de type (0,N) -- (0,N) devient une relation associative.
    Sa cle primaire est composee des deux cles etrangeres.

T5. Une association de type (0,1) -- (1,1) est transformee par une cle
    etrangere unique du cote qui depend de l'autre.

T6. Les contraintes d'unicite du MCD deviennent des contraintes UNIQUE
    dans le MLD.
```

### 3.2 MLD relationnel

```text
ROLE(
  id_role PK,
  nom_role UNIQUE
)

UTILISATEUR(
  id_user PK,
  nom,
  prenom,
  email UNIQUE,
  mot_de_passe,
  id_role FK -> ROLE(id_role),
  actif,
  created_at,
  updated_at
)

COMPETENCE(
  id_competence PK,
  nom UNIQUE,
  created_at
)

OFFRE_EMPLOI(
  id_offre PK,
  titre,
  description,
  type_contrat,
  localisation,
  statut,
  id_recruteur FK -> UTILISATEUR(id_user),
  created_at,
  updated_at
)

PROFIL_CANDIDAT(
  id_profil PK,
  id_user FK -> UTILISATEUR(id_user) UNIQUE,
  cv_path,
  experience,
  created_at,
  updated_at
)

CANDIDATURE(
  id_candidature PK,
  id_offre FK -> OFFRE_EMPLOI(id_offre),
  id_profil FK -> PROFIL_CANDIDAT(id_profil),
  lettre_motiv,
  score_matching,
  statut,
  created_at,
  updated_at,
  UNIQUE(id_offre, id_profil)
)

CANDIDATURE_HISTORIQUE(
  id_historique PK,
  id_candidature FK -> CANDIDATURE(id_candidature),
  ancien_statut,
  nouveau_statut,
  note,
  id_user FK -> UTILISATEUR(id_user),
  created_at
)

ENTRETIEN(
  id_entretien PK,
  id_candidature FK -> CANDIDATURE(id_candidature) UNIQUE,
  date_entretien,
  type_entretien,
  lieu_ou_lien,
  decision,
  compte_rendu,
  created_at,
  updated_at
)

NOTIFICATION(
  id_notif PK,
  id_user FK -> UTILISATEUR(id_user),
  message,
  lien,
  lu,
  created_at
)

COMPETENCE_OFFRE(
  id_offre PK, FK -> OFFRE_EMPLOI(id_offre),
  id_competence PK, FK -> COMPETENCE(id_competence)
)

COMPETENCE_CANDIDAT(
  id_profil PK, FK -> PROFIL_CANDIDAT(id_profil),
  id_competence PK, FK -> COMPETENCE(id_competence)
)
```

### 3.3 Contraintes physiques importantes du schema MySQL

```text
UTILISATEUR.id_role
  ON DELETE RESTRICT

OFFRE_EMPLOI.id_recruteur
  ON DELETE SET NULL

PROFIL_CANDIDAT.id_user
  ON DELETE CASCADE

COMPETENCE_OFFRE.id_offre
  ON DELETE CASCADE

COMPETENCE_OFFRE.id_competence
  ON DELETE CASCADE

COMPETENCE_CANDIDAT.id_profil
  ON DELETE CASCADE

COMPETENCE_CANDIDAT.id_competence
  ON DELETE CASCADE

CANDIDATURE.id_offre
  ON DELETE CASCADE

CANDIDATURE.id_profil
  ON DELETE CASCADE

CANDIDATURE_HISTORIQUE.id_candidature
  ON DELETE CASCADE

CANDIDATURE_HISTORIQUE.id_user
  ON DELETE SET NULL

ENTRETIEN.id_candidature
  ON DELETE CASCADE

NOTIFICATION.id_user
  ON DELETE CASCADE
```

## 4. Version compacte pour un rendu de devoir

### MCD compact

```text
ROLE(#id_role, nom_role)
UTILISATEUR(#id_user, nom, prenom, email, mot_de_passe, actif, created_at, updated_at)
COMPETENCE(#id_competence, nom, created_at)
OFFRE_EMPLOI(#id_offre, titre, description, type_contrat, localisation, statut, created_at, updated_at)
PROFIL_CANDIDAT(#id_profil, cv_path, experience, created_at, updated_at)
CANDIDATURE(#id_candidature, lettre_motiv, score_matching, statut, created_at, updated_at)
CANDIDATURE_HISTORIQUE(#id_historique, ancien_statut, nouveau_statut, note, created_at)
ENTRETIEN(#id_entretien, date_entretien, type_entretien, lieu_ou_lien, decision, compte_rendu, created_at, updated_at)
NOTIFICATION(#id_notif, message, lien, lu, created_at)

AFFECTER_ROLE: ROLE (0,N), UTILISATEUR (1,1)
PUBLIER: UTILISATEUR/RECRUTEUR (0,N), OFFRE_EMPLOI (1,1)
POSSEDER_PROFIL: UTILISATEUR/CANDIDAT (0,1), PROFIL_CANDIDAT (1,1)
EXIGER: OFFRE_EMPLOI (0,N), COMPETENCE (0,N)
MAITRISER: PROFIL_CANDIDAT (0,N), COMPETENCE (0,N)
DEPOSER: PROFIL_CANDIDAT (0,N), CANDIDATURE (1,1)
CONCERNER_OFFRE: OFFRE_EMPLOI (0,N), CANDIDATURE (1,1)
TRACER_STATUT: CANDIDATURE (0,N), CANDIDATURE_HISTORIQUE (1,1)
EFFECTUER_CHANGEMENT: UTILISATEUR (0,N), CANDIDATURE_HISTORIQUE (0,1)
PLANIFIER: CANDIDATURE (0,1), ENTRETIEN (1,1)
RECEVOIR_NOTIFICATION: UTILISATEUR (0,N), NOTIFICATION (1,1)
```

### MLD compact

```text
ROLE(id_role PK, nom_role UNIQUE)
UTILISATEUR(id_user PK, nom, prenom, email UNIQUE, mot_de_passe, id_role FK, actif, created_at, updated_at)
COMPETENCE(id_competence PK, nom UNIQUE, created_at)
OFFRE_EMPLOI(id_offre PK, titre, description, type_contrat, localisation, statut, id_recruteur FK, created_at, updated_at)
PROFIL_CANDIDAT(id_profil PK, id_user FK UNIQUE, cv_path, experience, created_at, updated_at)
CANDIDATURE(id_candidature PK, id_offre FK, id_profil FK, lettre_motiv, score_matching, statut, created_at, updated_at, UNIQUE(id_offre, id_profil))
CANDIDATURE_HISTORIQUE(id_historique PK, id_candidature FK, ancien_statut, nouveau_statut, note, id_user FK, created_at)
ENTRETIEN(id_entretien PK, id_candidature FK UNIQUE, date_entretien, type_entretien, lieu_ou_lien, decision, compte_rendu, created_at, updated_at)
NOTIFICATION(id_notif PK, id_user FK, message, lien, lu, created_at)
COMPETENCE_OFFRE(id_offre PK/FK, id_competence PK/FK)
COMPETENCE_CANDIDAT(id_profil PK/FK, id_competence PK/FK)
```
