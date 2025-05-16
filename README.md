# BikeBuddy Backend Dokumentation

Diese Dokumentation beschreibt das Backend der BikeBuddy Web-App, die für die Bürger-Interessen-Gemeinschaft Lindenhof entwickelt wurde, um Probleme im Radwegenetz zu melden.

**Verweis zum Frontend:**

* Die Dokumentation für das Frontend findest du hier: \[https://github.com/mrothpa/BikeBuddy]

## Inhaltsverzeichnis

1.  [Einleitung](#einleitung)
2.  [Technologien](#technologien)
3.  [Ziel der Dokumentation](#ziel-der-dokumentation)
4.  [Voraussetzungen](#voraussetzungen)
5.  [Installation](#installation)
6.  [Konfiguration](#konfiguration)
7.  [Projektstruktur](#projektstruktur)
8.  [Datenbankstruktur](#datenbankstruktur)
9.  [API-Endpunkte](#api-endpunkte)
10. [Authentifizierung & Autorisierung](#authentifizierung--autorisierung)
11. [Problem-Management](#problem-management)
12. [Lösungsvorschläge](#lösungsvorschläge)
13. [Upvotes](#upvotes)
14. [API Optimierung & Testen](#api-optimierung--testen)
15. [Deployment](#deployment)
17. [Weiterentwicklung](#weiterentwicklung)

## Einleitung

Diese Dokumentation beschreibt das Backend der BikeBuddy Web-App, die für die Bürger-Interessen-Gemeinschaft Lindenhof entwickelt wurde, um Probleme im Radwegenetz zu melden und an die Stadtverwaltung weiterzuleiten. [cite: 1, 2, 3] Das Backend stellt die API für das Frontend bereit und verwaltet die Daten.

## Technologien

* PHP
* Symfony
* MySQL
* Paseto (oder JWT)

## Ziel der Dokumentation

Diese Dokumentation soll Entwicklern helfen, das Backend der BikeBuddy-App zu verstehen, es einzurichten, zu verwenden und weiterzuentwickeln.

## Voraussetzungen

Stelle sicher, dass du Folgendes installiert hast:

* PHP (Version 8.2 oder höher empfohlen)
* Composer
* Ein Datenbankmanagementsystem (SQLite, MySQL oder MariaDB)

## Installation

1.  Klone das Repository:

    ```bash
    git clone https://github.com/mrothpa/BikeBuddyBackend.git
    cd fahrrad-backend
    ```

2.  Installiere die Abhängigkeiten mit Composer:

    ```bash
    composer install
    ```

## Konfiguration

1.  Konfiguriere die Datenbankverbindung in der `.env`-Datei:

    ```
    DATABASE_URL=mysql://benutzer:passwort@server:port/datenbankname
    ```

2.  Passe bei Bedarf andere Umgebungsvariablen an (z.B. CORS-Einstellungen).

## Projektstruktur
backend/  
├── config/          // Symfony Konfiguration  
├── src/             // PHP Code  
│   ├── Controller/  // API Controller  
│   ├── Entity/      // Datenbankentitäten  
│   └── Repository/  // Datenbankabfragen  
├── migrations/      // Datenbankmigrationen  
├── public/          // Web-Root  
│   └── index.php    // Einstiegspunkt der Anwendung  
├── templates/       // Twig Templates (Standard-Twig-Templates sind vorhanden, werden aber nicht benutzt)  
├── var/             // Cache, Logs  
├── .env             // Umgebungsvariablen (nicht im Prod.)  
├── .env.local       // Lokale Umgebungsvariablen  
├── composer.json    // Composer Konfiguration  
└── README.md  

## Datenbankstruktur

Die Datenbank besteht aus folgenden Tabellen:

1.  **users**
    * Speichert registrierte Nutzer (Bürger:innen, Admins).
    * Felder: `id`, `email`, `password`, `role`, `created_at`
2.  **problems**
    * Speichert gemeldete Probleme. 
    * Felder: `id`, `user_id`, `title`, `description`, `latitude`, `longitude`, `category`, `upvotes`, `status`, `created_at`
3.  **solutions**
    * Speichert Lösungsvorschläge zu Problemen.
    * Felder: `id`, `problem_id`, `user_id`, `description`, `created_at`
4.  **upvotes**
    * Speichert, wer ein Problem unterstützt hat.
    * Felder: `id`, `user_id`, `problem_id`, `created_at`

* **Beziehungen:**
    * 1 Benutzer → viele Probleme (1:N)
    * 1 Benutzer → viele Lösungsvorschläge (1:N)
    * 1 Problem → viele Lösungsvorschläge (1:N)
    * 1 Benutzer → kann viele Probleme upvoten, aber nur einmal pro Problem (M:N, durch `upvotes`)

## API-Endpunkte

Die API folgt den RESTful-Prinzipien.

## Authentifizierung & Autorisierung

* **Registrierung:** `POST /api/signup`
    * Erstellt einen neuen Benutzer.
    * Erforderliche Daten: `email`, `password`, `role`
* **Login:** `POST /api/login` [cite: 58]
    * Authentifiziert einen Benutzer und gibt ein Token zurück.
    * Erforderliche Daten: `email`, `password`
* **Geschützte Routen:**
    * Einige Routen erfordern eine gültige Authentifizierung (z.B. über einen `Authorization: Bearer <TOKEN>` Header).
    * Die Autorisierung erfolgt über die `role` des Benutzers (z.B. dürfen nur Admins den Problemstatus ändern).

## Problem-Management

* **Problem erstellen:** `POST /api/problems`
    * Erstellt ein neues Problem.
    * Erforderliche Daten: `title`, `description`, `category`, `latitude`, `longitude`
* **Alle Probleme abrufen:** `GET /api/problems` 
    * Ruft eine Liste aller Probleme ab.
    * Optionale Parameter: `category`, `status` (für Filterung)
* **Einzelnes Problem abrufen:** `GET /api/problems/{id}`
    * Ruft die Details eines einzelnen Problems ab.
* **Problemstatus ändern:** `PATCH /api/problems/{id}`
    * Ändert den Status eines Problems (nur für Admins).
    * Erforderliche Daten: `status`

## Lösungsvorschläge

* **Lösung erstellen:** `POST /api/solutions`
    * Erstellt einen neuen Lösungsvorschlag für ein Problem.
    * Erforderliche Daten: `problem_id`, `description`
* **Lösungen abrufen:** `GET /api/problems/{id}/solutions`
    * Ruft alle Lösungsvorschläge für ein Problem ab.

## Upvotes

* **Upvote hinzufügen:** `POST /api/problems/{id}/upvote`
    * Fügt einen Upvote für ein Problem hinzu.
    * Ein Benutzer kann ein Problem nur einmal upvoten.
* **Upvote entfernen:** `DELETE /api/problems/{id}/upvote`
    * Entfernt den Upvote eines Benutzers für ein Problem.

## API Optimierung & Testen

* **Validierung:** Alle API-Endpunkte validieren die Eingabedaten.
* **Fehlerbehandlung:** Die API gibt aussagekräftige Fehlermeldungen zurück.
* **Tests:** Alle API-Endpunkte wurden mehrmals getestet.

## Deployment

Kurz zusammengefasst (für einen Debian-Server):
1.  Produktionsmodus aktivieren (`APP_ENV=prod`, `APP_DEBUG=0`)
2.  Abhängigkeiten lokal installieren (`composer install --no-dev --optimize-autoloader`)
3.  `.env` nach `.env.local` kopieren und anpassen
4.  CORS-Einstellungen prüfen 
5.  `.htaccess` für Apache konfigurieren (falls nötig)
6.  Dateiberechtigungen setzen

Alternativ lokal bauen inkl. .htaccess-Datei etc. und auf den Server hochladen, falls kein Composer installiert werden kann

## Weiterentwicklung

* Befolge die Symfony Coding Standards.
* Schreibe aussagekräftige Kommentare im Code.
* Erstelle bei Änderungen an der Datenbank Migrationen.
* Teste alle Änderungen gründlich.
* Clone das Repository und markiere dieses hier.
* Beachte die Affero General Public License Version 3 (AGPLv3)
