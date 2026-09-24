# CLAUDE.md

Website der Ballettschule **Atelier Francis** (Ettlingen, https://www.atelierfrancis.de/). Sprache der Inhalte: Deutsch.

## Aufbau

Zwei Teile im selben Repo:

1. **Statische Website (Repo-Root)** – die kanonische Quelle für Inhalt, Markup, CSS und JS.
2. **WordPress-Umsetzung (`Wordpress elements/`)** – Theme + Plugins, die die statischen HTML-Seiten nahezu 1:1 in WordPress importieren und rendern.

Änderungen an Inhalt/Design gehören in die Root-Dateien; das WordPress-Theme bezieht sie per Sync.

### Statische Website
- Kein Build, kein Framework, kein Paketmanager: reines HTML/CSS/JS, direkt im Browser lauffähig.
- Seiten: `index.html`, `ueber-uns.html`, `stundenplan.html`, `kursuebersicht.html`, `aktuelles.html`, `eindruecke.html`, `kontakt.html`, `danke.html`, `impressum.html`, `datenschutz.html`, `404.html` sowie 8 Kursseiten `kurs-*.html`.
- Navigation und Footer sind in den HTML-Dateien jeweils dupliziert (kein Templating) – Änderungen daran in allen Seiten nachziehen (`grep -l 'class="nav"' *.html`).
- `styles.css`: ein großes Stylesheet mit Design-Tokens in `:root` (u. a. `--gold #c4a574`, `--pink #c4507a`, Fonts Cormorant Garamond / Inter). BEM-artige Klassen (`nav__links`, `btn--gold`). Tokens verwenden statt Hardcodes.
- `script.js`: alle Interaktionen, gegliedert durch `/* --- Abschnitt --- */`-Kommentare (Nav-Scroll, Mobilmenü, Scroll-Reveal via `.reveal`, Team-Slider, Testimonials, Lightbox, Eindrücke-Nachladen, Kontaktformular per `mailto`, Kurszeiten-Panels).
- `cursor-*.svg`: eigene Mauszeiger.

### Medien
- `Fotos/` mit thematischen Unterordnern; Bilder sind WebP. Pfade enthalten Leerzeichen und Umlaute – beim Referenzieren exakt übernehmen (Groß-/Kleinschreibung der Endung beachten, Deployment ist case-sensitive).
- `scripts/convert-images-to-webp.py` (Pillow) konvertiert PNG/JPG → WebP und schreibt Referenzen in HTML/CSS/JS um; Ergebnisse in `webp-conversion-manifest.json` / `webp-conversion-report.json`.
- `image_fix.ipynb`: findet Bildreferenzen mit abweichender Endungs-Schreibweise.

### WordPress (`Wordpress elements/`)
- `leadwerk_theme/` – Theme; `inc/exact-francis-render.php` + `exact-francis-bindings.php` rendern die HTML-Shells, `js/script.js` und `css/styles.css` sind Kopien der Root-Dateien.
- `leadwerk-fields/` – ACF-Pro-Ersatz (`get_field`/`update_field` auf post_meta). Kein echtes ACF installieren.
- `leadwerk-wpml-clone/` – eigene DE/EN-Übersetzungsschicht. Kein echtes WPML installieren.
- `leadwerk_importer/` – Import von Seiten, Medien und Feldern; gesteuert über `manifest/mapping.json`, `import-manifest.json`, `translation-seeds.json`. `source_assets/` ist gitignored und wird per Sync befüllt.

## Workflow Static → WordPress

Aus `Wordpress elements/` heraus:

```sh
php scripts/sync-html-sources.php                      # Root-HTML/CSS/JS/Fotos → leadwerk_importer/source_assets, CSS/JS → Theme
php scripts/verify-leadwerk-deployment.php --strict-drift   # muss mit Exit 0 enden
```

Neue HTML-Seite hinzufügen: in `$html_files` von `sync-html-sources.php` und in `manifest/mapping.json` eintragen. Danach Import in WP unter *Werkzeuge → Leadwerk Import* und `scripts/import-smoke-test-checklist.md` abarbeiten.

## Hinweise
- Commit-Nachrichten auf Deutsch, kurz.
- `.DS_Store`, `desktop.ini` und Debug-Logs nicht neu einchecken.
