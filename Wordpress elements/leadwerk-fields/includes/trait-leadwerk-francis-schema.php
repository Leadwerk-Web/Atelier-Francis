<?php
/**
 * Atelier Francis field groups and layout definitions.
 *
 * @package Leadwerk_Fields
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Leadwerk_Francis_Schema {

	/**
	 * Francis-only field groups.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	protected static function get_francis_groups() {
		return array(
			'francis_index_sections' => array(
				'label'       => 'Startseite',
				'description' => 'Alle Sektionen der Startseite.',
				'source_keys' => array( 'francis-index-v1' ),
				'layouts'     => array(
					'hero'               => self::layout_francis_hero_video(),
					'about'              => self::layout_francis_about_split(),
					'classes'            => self::layout_francis_classes_grid(),
					'statement'          => self::layout_francis_contact_statement(),
					'team'               => self::layout_francis_team_grid(),
					'impressions'        => self::layout_francis_impressions_slider(),
					'testimonials'       => self::layout_francis_testimonials(),
					'promo'              => self::layout_francis_promo_banner(),
					'pricing'            => self::layout_francis_pricing(),
				),
			),
			'francis_ueber_uns_sections' => array(
				'label'       => 'Über uns',
				'description' => 'Sektionen der Über-uns-Seite.',
				'source_keys' => array( 'francis-ueber-uns-v1' ),
				'layouts'     => array(
					'hero'           => self::layout_francis_hero_subpage(),
					'philosophy'     => self::layout_francis_about_split(),
					'values'         => self::layout_francis_values_grid(),
					'team_showcase'  => self::layout_francis_team_showcase(),
					'statement'      => self::layout_francis_contact_statement(),
					'testimonials'   => self::layout_francis_testimonials(),
				),
			),
			'francis_course_sections' => array(
				'label'       => 'Kurs-Unterseite',
				'description' => 'Hero und Kursinhalt.',
				'source_keys' => array(
					'francis-kurs-kindertanz-v1',
					'francis-kurs-ballett-kinder-v1',
					'francis-kurs-ballett-erwachsene-v1',
					'francis-kurs-modern-v1',
					'francis-kurs-hip-hop-v1',
					'francis-kurs-pilates-v1',
					'francis-kurs-choreo-fit-v1',
					'francis-kurs-latino-v1',
				),
				'layouts'     => array(
					'hero'    => self::layout_francis_hero_subpage(),
					'content' => self::layout_francis_course_content(),
				),
			),
			'francis_stundenplan_sections' => array(
				'label'       => 'Stundenplan',
				'description' => 'Stundenplan-Seite.',
				'source_keys' => array( 'francis-stundenplan-v1' ),
				'layouts'     => array(
					'hero'     => self::layout_francis_hero_subpage(),
					'schedule' => self::layout_francis_schedule_table(),
				),
			),
			'francis_kursuebersicht_sections' => array(
				'label'       => 'Kursübersicht',
				'description' => 'Kursübersicht-Seite.',
				'source_keys' => array( 'francis-kursuebersicht-v1' ),
				'layouts'     => array(
					'hero'    => self::layout_francis_hero_subpage(),
					'classes' => self::layout_francis_classes_grid(),
				),
			),
			'francis_aktuelles_sections' => array(
				'label'       => 'Aktuelles',
				'description' => 'Termine und Auftritte.',
				'source_keys' => array( 'francis-aktuelles-v1' ),
				'layouts'     => array(
					'hero'   => self::layout_francis_hero_subpage(),
					'events' => self::layout_francis_event_schedule(),
				),
			),
			'francis_eindruecke_sections' => array(
				'label'       => 'Eindrücke',
				'description' => 'Galerie und Abschluss-CTA.',
				'source_keys' => array( 'francis-eindruecke-v1' ),
				'layouts'     => array(
					'hero'    => self::layout_francis_hero_subpage(),
					'gallery' => self::layout_francis_gallery_grid(),
					'promo'   => self::layout_francis_promo_cta(),
				),
			),
			'francis_kontakt_sections' => array(
				'label'       => 'Kontakt',
				'description' => 'Kontaktformular und Infos.',
				'source_keys' => array( 'francis-kontakt-v1' ),
				'layouts'     => array(
					'hero'    => self::layout_francis_hero_subpage(),
					'contact' => self::layout_francis_contact_form(),
				),
			),
			'francis_danke_sections' => array(
				'label'       => 'Danke',
				'description' => 'Bestätigungsseite nach Kontaktformular.',
				'source_keys' => array( 'francis-danke-v1' ),
				'layouts'     => array(
					'hero'   => self::layout_francis_hero_subpage(),
					'thanks' => self::layout_francis_thank_you(),
				),
			),
			'impressum_page' => array(
				'label'             => 'Impressum',
				'description'       => 'Impressum bearbeiten.',
				'source_keys'       => array( 'francis-impressum-v1' ),
				'sync_post_content' => true,
				'fields'            => array(
					'headline' => self::text( 'Seitenüberschrift' ),
					'content'  => self::editor( 'Inhalt' ),
				),
			),
			'datenschutz_page' => array(
				'label'             => 'Datenschutz',
				'description'       => 'Datenschutzerklärung bearbeiten.',
				'source_keys'       => array( 'francis-datenschutz-v1' ),
				'sync_post_content' => true,
				'fields'            => array(
					'headline' => self::text( 'Seitenüberschrift' ),
					'content'  => self::editor( 'Inhalt' ),
				),
			),
		);
	}

	protected static function layout_francis_hero_video() {
		return array(
			'label'    => 'Hero Video',
			'template' => 'francis_hero_video',
			'fields'   => array_merge(
				array(
					'title'      => self::heading_html( 'Titel' ),
					'video'      => self::video( 'Hintergrundvideo' ),
					'video_poster' => self::image( 'Video Poster (optional)' ),
				),
				self::button_fields( 'cta' )
			),
		);
	}

	protected static function layout_francis_hero_subpage() {
		return array(
			'label'    => 'Hero Unterseite',
			'template' => 'francis_hero_subpage',
			'fields'   => array(
				'label'     => self::text( 'Überzeile' ),
				'title'     => self::heading_html( 'Titel' ),
				'image'     => self::image( 'Hintergrundbild' ),
				'image_alt' => self::text( 'Bild Alt-Text' ),
			),
		);
	}

	protected static function layout_francis_about_split() {
		return array(
			'label'    => 'About Split',
			'template' => 'francis_about_split',
			'fields'   => array_merge(
				array(
					'label'   => self::text( 'Section Label' ),
					'title'   => self::heading_html( 'Titel' ),
					'body'    => self::editor( 'Text' ),
					'image'   => self::image( 'Bild' ),
					'image_alt' => self::text( 'Bild Alt-Text' ),
					'kpis'    => self::repeater(
						'Kennzahlen',
						array(
							'number' => self::text( 'Zahl' ),
							'label'  => self::text( 'Label' ),
						),
						'Kennzahl hinzufügen'
					),
				),
				self::button_fields( 'cta' )
			),
		);
	}

	protected static function layout_francis_classes_grid() {
		return array(
			'label'    => 'Kurs-Grid',
			'template' => 'francis_classes_grid',
			'fields'   => array(
				'label' => self::text( 'Section Label' ),
				'title' => self::heading_html( 'Titel' ),
				'items' => self::repeater(
					'Kurs-Karten',
					array(
						'title'       => self::text( 'Titel' ),
						'description' => self::textarea( 'Kurzbeschreibung' ),
						'image'       => self::image( 'Bild' ),
						'image_alt'   => self::text( 'Bild Alt-Text' ),
						'page_key'    => self::text( 'Zielseite (source_key)' ),
						'url'         => self::url( 'URL (Fallback)' ),
					),
					'Kurs hinzufügen'
				),
			),
		);
	}

	protected static function layout_francis_contact_statement() {
		return array(
			'label'    => 'Kontakt Statement',
			'template' => 'francis_contact_statement',
			'fields'   => array_merge(
				array(
					'label'                => self::text( 'Überzeile' ),
					'title'                => self::heading_html( 'Titel' ),
					'body'                 => self::editor( 'Text' ),
					'background_image'     => self::image( 'Hintergrundbild' ),
					'background_image_alt' => self::text( 'Hintergrund Alt-Text' ),
					'secondary_label'      => self::text( 'Sekundärer Button' ),
					'secondary_page_key'   => self::text( 'Sekundär Zielseite (source_key)' ),
					'secondary_url'        => self::url( 'Sekundär URL (Fallback)' ),
				),
				self::button_fields( 'cta' )
			),
		);
	}

	protected static function layout_francis_team_grid() {
		return array(
			'label'    => 'Team Grid',
			'template' => 'francis_team_grid',
			'fields'   => array(
				'label' => self::text( 'Section Label' ),
				'title' => self::heading_html( 'Titel' ),
				'intro' => self::editor( 'Einleitung' ),
				'items' => self::repeater(
					'Team-Mitglieder',
					array(
						'name'  => self::text( 'Name' ),
						'role'  => self::text( 'Rolle' ),
						'bio'   => self::editor( 'Bio' ),
						'image' => self::image( 'Foto' ),
						'image_alt' => self::text( 'Foto Alt-Text' ),
					),
					'Person hinzufügen'
				),
			),
		);
	}

	protected static function layout_francis_team_showcase() {
		return self::layout_francis_team_grid();
	}

	protected static function layout_francis_impressions_slider() {
		return array(
			'label'    => 'Eindrücke Slider',
			'template' => 'francis_impressions_slider',
			'fields'   => array(
				'label'  => self::text( 'Section Label' ),
				'title'  => self::heading_html( 'Titel' ),
				'images' => self::repeater(
					'Bilder',
					array(
						'image'     => self::image( 'Bild' ),
						'image_alt' => self::text( 'Alt-Text' ),
					),
					'Bild hinzufügen'
				),
			),
		);
	}

	protected static function layout_francis_testimonials() {
		return array(
			'label'    => 'Testimonials',
			'template' => 'francis_testimonials',
			'fields'   => array(
				'label' => self::text( 'Section Label' ),
				'title' => self::heading_html( 'Titel' ),
				'items' => self::repeater(
					'Zitate',
					array(
						'quote'  => self::editor( 'Zitat' ),
						'author' => self::text( 'Autor' ),
						'role'   => self::text( 'Rolle/Kontext' ),
					),
					'Zitat hinzufügen'
				),
			),
		);
	}

	protected static function layout_francis_promo_banner() {
		return array(
			'label'    => 'Promo Banner',
			'template' => 'francis_promo_banner',
			'fields'   => array_merge(
				array(
					'title' => self::heading_html( 'Titel' ),
					'body'  => self::editor( 'Text' ),
					'image' => self::image( 'Bild' ),
					'image_alt' => self::text( 'Bild Alt-Text' ),
					'primary_label'    => self::text( 'Primärer Button' ),
					'primary_page_key' => self::text( 'Primär Zielseite (source_key)' ),
					'primary_url'      => self::url( 'Primär URL (Fallback)' ),
					'secondary_label'    => self::text( 'Sekundärer Button' ),
					'secondary_page_key' => self::text( 'Sekundär Zielseite (source_key)' ),
					'secondary_url'      => self::url( 'Sekundär URL (Fallback)' ),
				)
			),
		);
	}

	protected static function layout_francis_promo_cta() {
		return self::layout_francis_promo_banner();
	}

	protected static function layout_francis_pricing() {
		return array(
			'label'    => 'Preise',
			'template' => 'francis_pricing',
			'fields'   => array(
				'label' => self::text( 'Section Label' ),
				'title' => self::heading_html( 'Titel' ),
				'items' => self::repeater(
					'Preiskarten',
					array(
						'label'       => self::text( 'Paket-Label' ),
						'price'       => self::text( 'Preis' ),
						'period'      => self::text( 'Zeitraum' ),
						'description' => self::text( 'Beschreibung' ),
						'featured'    => self::checkbox( 'Hervorgehoben' ),
						'cta_label'   => self::text( 'Button Text' ),
						'cta_page_key'=> self::text( 'Button Zielseite (source_key)' ),
						'cta_url'     => self::url( 'Button URL (Fallback)' ),
					),
					'Preiskarte hinzufügen'
				),
				'notes' => self::editor( 'Fußnoten' ),
			),
		);
	}

	protected static function layout_francis_values_grid() {
		return array(
			'label'    => 'Werte',
			'template' => 'francis_values_grid',
			'fields'   => array(
				'label' => self::text( 'Section Label' ),
				'title' => self::heading_html( 'Titel' ),
				'items' => self::repeater(
					'Werte',
					array(
						'title'   => self::text( 'Titel' ),
						'content' => self::editor( 'Text' ),
					),
					'Wert hinzufügen'
				),
			),
		);
	}

	protected static function layout_francis_statement() {
		return array(
			'label'    => 'Statement',
			'template' => 'francis_statement',
			'fields'   => array(
				'quote'  => self::editor( 'Zitat' ),
				'author' => self::text( 'Autor' ),
			),
		);
	}

	protected static function layout_francis_course_content() {
		return array(
			'label'    => 'Kursinhalt',
			'template' => 'francis_course_content',
			'fields'   => array(
				'label'   => self::text( 'Section Label' ),
				'title'   => self::heading_html( 'Titel' ),
				'subline' => self::text( 'Unterzeile' ),
				'body'    => self::editor( 'Fließtext' ),
				'gallery' => self::repeater(
					'Galerie',
					array(
						'image'     => self::image( 'Bild' ),
						'image_alt' => self::text( 'Alt-Text' ),
					),
					'Bild hinzufügen'
				),
			),
		);
	}

	protected static function layout_francis_schedule_table() {
		return array(
			'label'    => 'Stundenplan',
			'template' => 'francis_schedule_table',
			'fields'   => array_merge(
				array(
					'label'          => self::text( 'Section Label' ),
					'title'          => self::heading_html( 'Titel' ),
					'intro'          => self::editor( 'Einleitung' ),
					'schedule_image' => self::image( 'Stundenplan-Bild' ),
					'schedule_image_alt' => self::text( 'Bild Alt-Text' ),
					'body'           => self::editor( 'Zusatztext / CTA-Bereich' ),
				),
				self::button_fields( 'cta' )
			),
		);
	}

	protected static function layout_francis_event_schedule() {
		return array(
			'label'    => 'Termine',
			'template' => 'francis_event_schedule',
			'fields'   => array(
				'label' => self::text( 'Section Label' ),
				'title' => self::heading_html( 'Titel' ),
				'intro' => self::editor( 'Einleitung' ),
				'items' => self::repeater(
					'Termine',
					array(
						'day'         => self::text( 'Tag' ),
						'month'       => self::text( 'Monat/Jahr' ),
						'title'       => self::text( 'Titel' ),
						'location'    => self::text( 'Ort' ),
						'description' => self::editor( 'Beschreibung' ),
						'image'       => self::image( 'Bild' ),
						'image_alt'   => self::text( 'Bild Alt-Text' ),
					),
					'Termin hinzufügen'
				),
			),
		);
	}

	protected static function layout_francis_gallery_grid() {
		return array(
			'label'    => 'Galerie',
			'template' => 'francis_gallery_grid',
			'fields'   => array(
				'label'  => self::text( 'Section Label' ),
				'title'  => self::heading_html( 'Titel' ),
				'images' => self::repeater(
					'Bilder',
					array(
						'image'     => self::image( 'Bild' ),
						'image_alt' => self::text( 'Alt-Text' ),
						'caption'   => self::text( 'Bildunterschrift' ),
					),
					'Bild hinzufügen'
				),
			),
		);
	}

	protected static function layout_francis_contact_form() {
		return array(
			'label'    => 'Kontakt',
			'template' => 'francis_contact_form',
			'fields'   => array(
				'label'       => self::text( 'Section Label' ),
				'title'       => self::heading_html( 'Titel' ),
				'intro'       => self::editor( 'Einleitung' ),
				'form_hint'   => self::textarea( 'Formular-Hinweis' ),
				'address_html'=> self::editor( 'Adressblock (Aside)' ),
			),
		);
	}

	protected static function layout_francis_thank_you() {
		return array(
			'label'    => 'Danke-Nachricht',
			'template' => 'francis_thank_you',
			'fields'   => array_merge(
				array(
					'label'        => self::text( 'Überzeile' ),
					'title_line_1' => self::heading_html( 'Titel Zeile 1' ),
					'title_line_2' => self::heading_html( 'Titel Zeile 2' ),
					'body'         => self::editor( 'Text' ),
				),
				self::button_fields( 'cta_primary' ),
				self::button_fields( 'cta_secondary' )
			),
		);
	}
}
