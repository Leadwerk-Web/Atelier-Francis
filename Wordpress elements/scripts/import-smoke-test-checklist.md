# Atelier Francis — Import Smoke-Test (WordPress)

Nach dem Live-Import in WordPress diese Checkliste abarbeiten.

## Vor dem Import

- [ ] Plugins aktiv: `leadwerk-fields`, `leadwerk-wpml-clone`, `leadwerk_importer`
- [ ] Theme `leadwerk_theme` aktiv
- [ ] Kein echtes ACF, kein echtes WPML
- [ ] `php scripts/sync-html-sources.php` (oder manuell synchronisiert)
- [ ] `php scripts/verify-leadwerk-deployment.php --strict-drift` → Exit 0

## Import

- [ ] **Werkzeuge → Leadwerk Import** → Live-Import
- [ ] Log: keine `selector_miss`, `parser_empty`, keine Blocking-Issues
- [ ] Summary: 17 DE-Seiten `publish`, 17 EN-Seiten `draft` + `not_translated`

## Nach dem Import

- [ ] **Einstellungen → Permalinks → Speichern**
- [ ] Startseite: `francis-index-v1` als statische Front Page
- [ ] WPForms anlegen, IDs in Leadwerk-Optionen (DE/EN)
- [ ] Impressum/Datenschutz-Inhalt prüfen

## Frontend DE (jede Seite)

| source_key | Slug | OK |
|---|---|---|
| francis-index-v1 | `/` | |
| francis-ueber-uns-v1 | `/ueber-uns/` | |
| francis-stundenplan-v1 | `/stundenplan/` | |
| francis-kursuebersicht-v1 | `/kursuebersicht/` | |
| francis-kurs-kindertanz-v1 | `/kurs-kindertanz/` | |
| francis-kurs-ballett-kinder-v1 | `/kurs-ballett-kinder/` | |
| francis-kurs-ballett-erwachsene-v1 | `/kurs-ballett-erwachsene/` | |
| francis-kurs-modern-v1 | `/kurs-modern/` | |
| francis-kurs-hip-hop-v1 | `/kurs-hip-hop/` | |
| francis-kurs-pilates-v1 | `/kurs-pilates/` | |
| francis-kurs-choreo-fit-v1 | `/kurs-choreo-fit/` | |
| francis-kurs-latino-v1 | `/kurs-latino/` | |
| francis-aktuelles-v1 | `/aktuelles/` | |
| francis-eindruecke-v1 | `/eindruecke/` | |
| francis-kontakt-v1 | `/kontakt/` | |
| francis-impressum-v1 | `/impressum/` | |
| francis-datenschutz-v1 | `/datenschutz/` | |

## Header/Footer

- [ ] Nav-Links zeigen WordPress-Permalinks (keine `.html`)
- [ ] Logo → Startseite `#hero`
- [ ] CTA „Probestunde“ → Startseite `#contact`
- [ ] Footer Kurs-Links + Impressum/Datenschutz lokal

## EN

- [ ] `/en/home-en/` (Draft, leere Shells akzeptabel)
- [ ] Sprachwechsel DE ↔ EN funktioniert

## Backend

- [ ] Jede Seite: Flexible-Content-Felder befüllt und editierbar
- [ ] Re-Import ohne Force: manuelle Edits bleiben (Write-Guard)
