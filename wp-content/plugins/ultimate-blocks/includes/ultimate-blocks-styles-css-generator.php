<?php

namespace Ultimate_Blocks\includes;


/**
 * Check is spacing value is presets or custom
 *
 * @param string $value - spacing value.
 */
function spacing_preset( $value ) {

     if ( ! $value || ! is_string( $value ) ) {
          return false;
     }
     return '0' === $value || strpos( $value, 'var:preset|spacing|' ) === 0;
}
/**
 * Return the spacing variable
 *
 * @param string $value - spacing value.
 */
function spacing_preset_css_var( $value ) {
     if ( ! $value ) {
          return null;
     }

     $matches = array();
     preg_match( '/var:preset\|spacing\|(.+)/', $value, $matches );

     if ( empty( $matches ) ) {
          return $value;
     }
     return "var(--wp--preset--spacing--{$matches[1]})";
}
/**
 * Get the spacing css
 *
 * @param array $object - spacing object.
 */
function get_spacing_css( $object ) {
     $css = array();

     foreach ( $object as $key => $value ) {
          if ( spacing_preset( $value ) ) {
               $css[ $key ] = spacing_preset_css_var( $value );
          } else {
               $css[ $key ] = $value;
          }
     }

     return $css;
}

     /**
      * Check value is undefined
      *
      * @param string $value - value.
      */
function is_undefined( $value ) {
          return !isset( $value ) || null === $value   || empty( $value );
     }

/**
 * Generate the css if value is not empty
 *
 * @param object $styles - spacing value.
 */
function generate_css_string( $styles ) {
     $css_string = '';

     foreach ( $styles as $key => $value ) {
          if ( ! is_undefined( $value ) && false !== $value && ! empty( $value ) && (is_string($value) && trim( $value ) !== '' && trim( $value ) !== 'undefined undefined undefined') ) {
               $css_string .= $key . ': ' . esc_attr( $value ) . '; ';
          }
     }

     return $css_string;
}

/**
 * Get CSS value for box shadow
 *
 * @param array $shadow - Shadow object.
 * @return string CSS value for box-shadow.
 */
function get_box_shadow_css( $shadow ) {
	if ( empty( $shadow ) || !is_array( $shadow ) ) {
		return '';
	}

	// If shadow has a 'shadow' property and preset is not custom, use it directly
	if ( isset( $shadow['shadow'] ) && isset( $shadow['preset'] ) && $shadow['preset'] !== 'custom' ) {
		return $shadow['shadow'];
	}

	// Build from individual properties (for custom or when shadow property is not set)
	$color = isset( $shadow['color'] ) ? $shadow['color'] : 'rgba(0, 0, 0, 0.2)';
	$horizontal = isset( $shadow['horizontal'] ) ? $shadow['horizontal'] . 'px' : '0px';
	$vertical = isset( $shadow['vertical'] ) ? $shadow['vertical'] . 'px' : '0px';
	$blur = isset( $shadow['blur'] ) ? $shadow['blur'] . 'px' : '0px';
	$spread = isset( $shadow['spread'] ) ? $shadow['spread'] . 'px' : '0px';
	$position = isset( $shadow['position'] ) && $shadow['position'] === 'inset' ? 'inset ' : '';

	return $position . $horizontal . ' ' . $vertical . ' ' . $blur . ' ' . $spread . ' ' . $color;
}

/**
 * Get the CSS value for a single side of the border.
 *
 * @param array  $border - border.
 * @param string $side - border side.
 * @return string CSS value for the specified side.
 */
function get_single_side_border_value( $border, $side ) {
     $width = isset($border[ $side ]['width']) ? $border[ $side ]['width'] : '';
     $style = isset($border[ $side ]['style']) ? $border[ $side ]['style'] : '';
     $color = isset($border[ $side ]['color']) ? $border[ $side ]['color'] : '';

     return "{$width} " . ( $width && empty( $border[ $side ]['style'] ) ? 'solid ' : $style ) . (!empty($width) && empty($color) ? "" : $color);
}
/**
 * Check if border has split borders.
 *
 * @param array $border - block border.
 * @return bool Whether the border has split sides.
 */
function has_split_borders( $border = array() ) {
     $sides = array( 'top', 'right', 'bottom', 'left' );
     foreach ( $border as $side => $value ) {
          if ( in_array( $side, $sides, true ) ) {
               return true;
          }
     }

     return false;
}
/**
 * Get the border CSS from attributes.
 *
 * @param array $object - block border.
 * @return array CSS styles for the border.
*/
function get_border_css( $object ) {
     $css = array();

     if ( ! has_split_borders( $object ) ) {
          $css['top']    = $object;
          $css['right']  = $object;
          $css['bottom'] = $object;
          $css['left']   = $object;
          return $css;
     }

     return $object;
}
/**
 * Get border variables for CSS.
 *
 * @param array  $border - border.
 * @param string $slug - slug to use in variable.
 * @return array CSS styles for the border variables.
 */
function get_border_variables_css( $border, $slug ) {
     $border_in_dimensions = get_border_css( $border );
     $border_sides         = array( 'top', 'right', 'bottom', 'left' );
     $borders              = array();

     foreach ( $border_sides as $side ) {
          $side_property             = "--ub-{$slug}-border-{$side}";
          $side_value                = get_single_side_border_value( $border_in_dimensions, $side );
          $borders[ $side_property ] = $side_value;
     }

     return $borders;
}

function get_background_color_var(
	$attributes,
	$bg_color_attr_key,
	$gradient_attr_key
) {
	if (!empty($attributes[$bg_color_attr_key])) {
		return $attributes[$bg_color_attr_key];
	} else if (!empty($attributes[$gradient_attr_key])) {
		return $attributes[$gradient_attr_key];
	} else {
		return "";
	}
}

/**
 * Strip XSS from HTML content.
 *
 * This function is created for escaping content because WordPress custom functions
 * (like wp_kses) escape SVG elements as well and remove them. This provides a safer
 * alternative that preserves SVG content while still removing potentially harmful scripts.
 *
 * @param string $html - The HTML content to sanitize.
 * @return string Sanitized HTML content.
 */
function strip_xss( $html ) {
	if ( ! $html ) {
		return '';
	}

	$dom = new \DOMDocument( '1.0', 'UTF-8' );
	$html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . htmlspecialchars_decode(htmlentities($html, ENT_QUOTES, 'UTF-8')) . '</body></html>';

	// Suppress errors due to malformed HTML.
	@$dom->loadHTML( $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

	$xpath = new \DOMXPath( $dom );
	$elements = $xpath->query( '//*' );

	foreach ( $elements as $element ) {
		foreach ( $element->attributes as $attr ) {
			if ( strpos( $attr->name, 'on' ) === 0 ) {
				$element->removeAttribute( $attr->name );
			}
		}
	}
	$script_tags = $dom->getElementsByTagName( 'script' );
	while ( $script_tags->length > 0 ) {
		$script_tags->item( 0 )->parentNode->removeChild( $script_tags->item( 0 ) );
	}

	// Extract only the body content
	$body = $dom->getElementsByTagName('body')->item(0);
	$html_content = '';

	if ($body && $body->childNodes) {
		foreach ($body->childNodes as $node) {
			$html_content .= $dom->saveHTML($node);
		}
	}

	return $html_content;
}
