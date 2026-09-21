<?php

/**
 * Handle the block registration on server side and rendering.
 */
class Image {

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'block_registration' ) );
	}

	/**
	 * Get border variables
	 *
	 * @param array $border_attribute - border.
	 */
	public function get_border_variables_css( $border_attribute ) {
		$border_sides     = array( 'top', 'right', 'bottom', 'left' );
		$splitted_borders = Ultimate_Blocks\includes\get_border_css( $border_attribute );
		$borders          = array();

		foreach ( $border_sides as $side ) {
			$side_property = "border-$side";
			$side_value    = Ultimate_Blocks\includes\get_single_side_border_value( $splitted_borders, $side );

			$borders[ $side_property ] = $side_value;
		}

		return $borders;
	}

	/**
	 * Build the CSS style for the image element.
	 *
	 * @param array $attributes Block attributes.
	 * @return string The CSS style string.
	 */
	public function build_image_style( $attributes ) {
		$styles = array();

		// Get basic image properties
		if ( ! empty( $attributes['aspectRatio'] ) ) {
			$styles['aspect-ratio'] = $attributes['aspectRatio'];
		}

		if ( ! empty( $attributes['scale'] ) ) {
			$styles['object-fit'] = $attributes['scale'];
		}

		if ( ! empty( $attributes['width'] ) ) {
			$styles['width'] = $attributes['width'];
		}

		if ( ! empty( $attributes['height'] ) ) {
			$styles['height'] = $attributes['height'];
		}

		// Apply border styles
		$borders = $this->get_border_variables_css( $attributes['border'] ?? array() );
		if ( ! empty( $borders ) ) {
			foreach ( $borders as $property => $value ) {
				if ( ! empty( trim( $value ) ) ) {
					$styles[ $property ] = $value;
				}
			}
		}

		// Apply border radius
		$border_radius = $attributes['borderRadius'] ?? array();
		if ( ! empty( $border_radius ) ) {
			if ( isset( $border_radius['topLeft'] ) ) {
				$styles['border-top-left-radius'] = $border_radius['topLeft'];
			}
			if ( isset( $border_radius['topRight'] ) ) {
				$styles['border-top-right-radius'] = $border_radius['topRight'];
			}
			if ( isset( $border_radius['bottomLeft'] ) ) {
				$styles['border-bottom-left-radius'] = $border_radius['bottomLeft'];
			}
			if ( isset( $border_radius['bottomRight'] ) ) {
				$styles['border-bottom-right-radius'] = $border_radius['bottomRight'];
			}
		}

		return Ultimate_Blocks\includes\generate_css_string( $styles );
	}

	/**
	 * Renders the custom image block on the server.
	 *
	 * @param array    $attributes The block attributes.
	 * @param string   $content    The block content.
	 * @param WP_Block $block      The block object.
	 * @return string Returns the HTML content for the custom image block.
	 */
	public function render_ub_image_block( $attributes, $content, $block ) {
		$size_slug    = $attributes['sizeSlug'];
		$media        = $attributes['media'];
		$url          = isset( $media['sizes'][ $size_slug ]['url'] ) ? $media['sizes'][ $size_slug ]['url'] : $media['url'] ?? '';
		$alt          = $attributes['alt'];
		$caption      = $attributes['caption'];
		$align        = $attributes['align'] ?? '';
		$href         = $attributes['href'];
		$rel          = $attributes['rel'];
		$link_class   = $attributes['linkClass'];
		$width        = $attributes['width'];
		$height       = $attributes['height'];
		$aspect_ratio = $attributes['aspectRatio'];
		$scale        = $attributes['scale'];
		$id           = isset( $media['id'] );
		$link_target  = $attributes['linkTarget'];

		$new_rel       = ! empty( $rel ) ? ' rel = "' . esc_attr( $rel ) . '"' : '';
		$border        = $this->get_border_variables_css( $attributes['border'] );
		$border_radius = $attributes['borderRadius'];

		$classes = array( 'wp-block-ub-image' );
		if( !empty($align) ){
			$classes[] = 'align' . $align;
		}
		if(!empty($size_slug)) {
			$classes[]= 'size-' . $size_slug;
		}
		if($width || $height){
			$classes[] = 'is-resized';
		}
		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => implode(' ', $classes)
			)
		);

		$image_classes = join(
			' ',
			array_filter(
				[
					$id ? 'wp-image-' . esc_attr( $id ) : '',
				]
			)
		);

		$image = sprintf(
			'<img src="%1$s" alt="%2$s" class="%3$s" style="%4$s" />',
			esc_url( $url ),
			esc_attr( $alt ),
			esc_attr( $image_classes ),
			$this->build_image_style( $attributes )
		);

		$figure = '';

		if ( $href ) {
			$figure = sprintf(
				'<a href="%1$s" class="%2$s" target="%3$s"%4$s>%5$s</a>',
				esc_url( $href ),
				esc_attr( $link_class ),
				esc_attr( $link_target ),
				$new_rel,
				$image
			);
		} else {
			$figure = $image;
		}

		if ( ! empty( $caption ) ) {
			$figure .= sprintf(
				'<figcaption class="wp-element-caption">%1$s</figcaption>',
				wp_kses_post( $caption )
			);
		}

		return sprintf(
			'<figure %1$s>%2$s</figure>',
			$wrapper_attributes,
			$figure
		);
	}

	/**
	 * Register the block.
	 */
	public function block_registration() {
		register_block_type(
			dirname(dirname(dirname(__DIR__)))  . '/dist/blocks/image/block.json',
			array(
				'render_callback' => array( $this, 'render_ub_image_block' ),
			)
		);
	}
}
new Image();
