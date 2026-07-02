<?php
/**
 * Shared structured schema for Atelier Francis importer, fields metaboxes and theme renderers.
 *
 * @package Leadwerk_Fields
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/trait-leadwerk-francis-schema.php';

class Leadwerk_Content_Schema {
	use Leadwerk_Francis_Schema;

	/**
	 * Return all section field groups.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function get_groups() {
		static $groups = null;

		if ( null !== $groups ) {
			return $groups;
		}

		$groups = self::get_francis_groups();
		return $groups;
	}
	/**
	 * Return one field group schema.
	 *
	 * @param string $field_name Field name.
	 * @return array<string,mixed>|null
	 */
	public static function get_group( $field_name ) {
		$groups = self::get_groups();
		return $groups[ $field_name ] ?? null;
	}

	/**
	 * Resolve a field group by source key.
	 *
	 * @param string $source_key Source key.
	 * @return array<string,mixed>|null
	 */
	public static function get_group_for_source_key( $source_key ) {
		foreach ( self::get_groups() as $field_name => $group ) {
			if ( in_array( $source_key, $group['source_keys'], true ) ) {
				$group['field_name'] = $field_name;
				return $group;
			}
		}

		return null;
	}

	/**
	 * Resolve a field group by post.
	 *
	 * @param int|WP_Post $post Post object or ID.
	 * @return array<string,mixed>|null
	 */
	public static function get_group_for_post( $post ) {
		$post_id = is_object( $post ) ? (int) $post->ID : (int) $post;
		if ( ! $post_id ) {
			return null;
		}

		$source_key = (string) get_post_meta( $post_id, 'leadwerk_source_key', true );
		return self::get_group_for_source_key( $source_key );
	}

	/**
	 * Resolve a layout schema.
	 *
	 * @param string $field_name Field group name.
	 * @param string $layout     Layout name.
	 * @return array<string,mixed>|null
	 */
	public static function get_layout( $field_name, $layout ) {
		$group = self::get_group( $field_name );
		if ( ! $group ) {
			return null;
		}

		return $group['layouts'][ $layout ] ?? null;
	}

	/**
	 * Default value for a field definition.
	 *
	 * @param array<string,mixed> $definition Field definition.
	 * @return mixed
	 */
	public static function get_default_value( $definition ) {
		if ( is_array( $definition ) && array_key_exists( 'default', $definition ) ) {
			return $definition['default'];
		}

		$type = $definition['type'] ?? 'text';

		switch ( $type ) {
			case 'checkbox':
				return false;
			case 'image':
			case 'video':
			case 'file':
				return 0;
			case 'repeater':
			case 'select_options':
				return array();
			default:
				return '';
		}
	}

	/**
	 * Basic text field definition.
	 *
	 * @param string $label Label.
	 * @return array<string,mixed>
	 */
	protected static function text( $label ) {
		return array(
			'label' => $label,
			'type'  => 'text',
		);
	}

	/**
	 * Basic textarea field definition.
	 *
	 * @param string $label Label.
	 * @return array<string,mixed>
	 */
	protected static function textarea( $label ) {
		return array(
			'label' => $label,
			'type'  => 'textarea',
		);
	}

	/**
	 * Raw SVG markup (admin-trusted). Uses svg_code field type so save does not strip tags.
	 *
	 * @param string $label Label.
	 * @return array<string,mixed>
	 */
	protected static function svg_code( $label ) {
		return array(
			'label' => $label,
			'type'  => 'svg_code',
		);
	}

	/**
	 * Rich text field definition.
	 *
	 * @param string $label Label.
	 * @return array<string,mixed>
	 */
	protected static function editor( $label ) {
		return array(
			'label' => $label,
			'type'  => 'classic_editor',
		);
	}

	/**
	 * Inline-safe rich text field definition for headings.
	 *
	 * @param string $label Label.
	 * @return array<string,mixed>
	 */
	protected static function heading_html( $label ) {
		return array(
			'label' => $label,
			'type'  => 'heading_html',
		);
	}

	/**
	 * Normalize heading markup so it can be injected into existing h* nodes.
	 *
	 * @param string $html Raw heading markup.
	 * @return string
	 */
	public static function sanitize_heading_html( $html ) {
		$html = wp_kses_post( (string) $html );
		if ( '' === trim( wp_strip_all_tags( $html ) ) ) {
			return '';
		}

		if ( ! class_exists( 'DOMDocument' ) ) {
			$fallback = preg_replace( '#</?(p|div|section|article)\b[^>]*>#i', '', $html );
			$fallback = is_string( $fallback ) ? trim( $fallback ) : '';
			return '' === trim( wp_strip_all_tags( $fallback ) ) ? '' : $fallback;
		}

		$temp = new DOMDocument( '1.0', 'UTF-8' );
		libxml_use_internal_errors( true );
		$temp->loadHTML( '<?xml encoding="utf-8" ?><div id="leadwerk-heading-root">' . $html . '</div>' );
		libxml_clear_errors();

		$root = ( new DOMXPath( $temp ) )->query( '//*[@id="leadwerk-heading-root"]' )->item( 0 );
		if ( ! $root instanceof DOMNode ) {
			return '';
		}

		$normalized = self::serialize_inline_heading_children(
			$root,
			array(
				'a'      => true,
				'abbr'   => true,
				'b'      => true,
				'br'     => true,
				'cite'   => true,
				'code'   => true,
				'em'     => true,
				'i'      => true,
				'mark'   => true,
				'small'  => true,
				'span'   => true,
				'strong' => true,
				'sub'    => true,
				'sup'    => true,
				'u'      => true,
				'wbr'    => true,
			),
			array(
				'article',
				'aside',
				'blockquote',
				'div',
				'footer',
				'h1',
				'h2',
				'h3',
				'h4',
				'h5',
				'h6',
				'header',
				'li',
				'main',
				'ol',
				'p',
				'section',
				'ul',
			)
		);

		$normalized = trim( preg_replace( '/(?:<br>\s*){3,}/i', '<br><br>', (string) $normalized ) );
		return '' === trim( wp_strip_all_tags( $normalized ) ) ? '' : $normalized;
	}

	/**
	 * Serialize child nodes into inline-only heading HTML.
	 *
	 * @param DOMNode              $node                Root node.
	 * @param array<string,bool>   $allowed_inline_tags Allowed inline tags.
	 * @param string[]             $block_tags          Tags that should be flattened.
	 * @return string
	 */
	protected static function serialize_inline_heading_children( $node, $allowed_inline_tags, $block_tags ) {
		$chunks      = array();
		$last_was_br = false;

		foreach ( $node->childNodes as $child ) {
			$is_block = $child instanceof DOMElement && in_array( strtolower( $child->tagName ), $block_tags, true );
			$chunk    = self::serialize_inline_heading_node( $child, $allowed_inline_tags, $block_tags );
			if ( '' === $chunk ) {
				continue;
			}

			if ( $is_block && ! empty( $chunks ) && ! $last_was_br ) {
				$chunks[] = '<br>';
			}

			$chunks[]    = $chunk;
			$last_was_br = '<br>' === $chunk;
		}

		return implode( '', $chunks );
	}

	/**
	 * Serialize one node into inline-safe heading HTML.
	 *
	 * @param DOMNode            $node                Node.
	 * @param array<string,bool> $allowed_inline_tags Allowed inline tags.
	 * @param string[]           $block_tags          Tags that should be flattened.
	 * @return string
	 */
	protected static function serialize_inline_heading_node( $node, $allowed_inline_tags, $block_tags ) {
		if ( $node instanceof DOMText ) {
			$text = preg_replace( '/\s+/u', ' ', (string) $node->nodeValue );
			return '' === trim( (string) $text ) ? '' : esc_html( (string) $text );
		}

		if ( ! $node instanceof DOMElement ) {
			return '';
		}

		$tag = strtolower( $node->tagName );
		if ( 'br' === $tag ) {
			return '<br>';
		}

		if ( 'wbr' === $tag ) {
			return '<wbr>';
		}

		$children_html = self::serialize_inline_heading_children( $node, $allowed_inline_tags, $block_tags );
		if ( in_array( $tag, $block_tags, true ) || ! isset( $allowed_inline_tags[ $tag ] ) ) {
			return $children_html;
		}

		$attrs = '';
		if ( $node->hasAttributes() ) {
			foreach ( $node->attributes as $attribute ) {
				if ( ! $attribute instanceof DOMAttr ) {
					continue;
				}

				$attrs .= sprintf(
					' %1$s="%2$s"',
					esc_attr( $attribute->nodeName ),
					esc_attr( $attribute->nodeValue )
				);
			}
		}

		return sprintf( '<%1$s%2$s>%3$s</%1$s>', $tag, $attrs, $children_html );
	}

	/**
	 * URL field definition.
	 *
	 * @param string $label Label.
	 * @return array<string,mixed>
	 */
	protected static function url( $label ) {
		return array(
			'label' => $label,
			'type'  => 'url',
		);
	}

	/**
	 * Image field definition.
	 *
	 * @param string $label Label.
	 * @return array<string,mixed>
	 */
	protected static function image( $label ) {
		return array(
			'label' => $label,
			'type'  => 'image',
		);
	}

	/**
	 * Video (Mediathek-Anhang-ID, wie Bild).
	 *
	 * @param string $label Label.
	 * @return array<string,mixed>
	 */
	protected static function video( $label ) {
		return array(
			'label' => $label,
			'type'  => 'video',
		);
	}

	/**
	 * Datei aus der Mediathek (Anhang-ID), optional MIME fuer wp.media-Filter.
	 *
	 * @param string $label Label.
	 * @param string $mime  z.B. application/pdf.
	 * @return array<string,mixed>
	 */
	protected static function file( $label, $mime = 'application/pdf' ) {
		return array(
			'label' => $label,
			'type'  => 'file',
			'mime'  => (string) $mime,
		);
	}

	/**
	 * Checkbox field definition.
	 *
	 * @param string $label Label.
	 * @return array<string,mixed>
	 */
	protected static function checkbox( $label, $default = false ) {
		return array(
			'label'   => $label,
			'type'    => 'checkbox',
			'default' => ! empty( $default ),
		);
	}

	/**
	 * Repeater field definition.
	 *
	 * @param string              $label    Label.
	 * @param array<string,mixed> $fields   Sub-fields.
	 * @param string|null         $add_text Optional button label.
	 * @param array<string,mixed> $options  Optional: top_add_bar (bool) — prominente +-Leiste oben.
	 * @return array<string,mixed>
	 */
	protected static function repeater( $label, $fields, $add_text = null, $options = null ) {
		$definition = array(
			'label'  => $label,
			'type'   => 'repeater',
			'fields' => $fields,
		);

		if ( null !== $add_text ) {
			$definition['add_button_label'] = $add_text;
		}

		if ( is_array( $options ) ) {
			foreach ( $options as $opt_key => $opt_val ) {
				$definition[ $opt_key ] = $opt_val;
			}
		}

		return $definition;
	}

	/**
	 * Internal/external button field set.
	 *
	 * @param string $prefix Field prefix.
	 * @return array<string,array<string,mixed>>
	 */
	protected static function button_fields( $prefix ) {
		return array(
			$prefix . '_label'    => self::text( 'Button Text' ),
			$prefix . '_page_key' => self::text( 'Button Zielseite (source_key)' ),
			$prefix . '_url'      => self::url( 'Button URL (Fallback/extern)' ),
		);
	}
}
