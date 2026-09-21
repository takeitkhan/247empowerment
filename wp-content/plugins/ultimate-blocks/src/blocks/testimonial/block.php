<?php

function ub_render_testimonial_block($attributes, $_, $block){
	extract($attributes);

	$block_attrs = $block->parsed_block['attrs'];
	$padding = Ultimate_Blocks\includes\get_spacing_css( isset($block_attrs['padding']) ? $block_attrs['padding'] : array() );
	$margin = Ultimate_Blocks\includes\get_spacing_css( isset($block_attrs['margin']) ? $block_attrs['margin'] : array() );
	$text_color_important = isset($attributes['textColorImportant']) ? $attributes['textColorImportant'] : false;
	$background_color_important = isset($attributes['backgroundColorImportant']) ? $attributes['backgroundColorImportant'] : false;
	$wrapper_Styles = array(
		'background-color'   => isset($attributes['backgroundColor']) ? $attributes['backgroundColor'] . ($background_color_important ? " !important" : "") : "",
		'color'              => isset($attributes['textColor']) ? $attributes['textColor'] . ($text_color_important ? " !important" : "") : "inherit",
		'padding-top'        => isset($padding['top']) ? $padding['top'] : "",
		'padding-left'       => isset($padding['left']) ? $padding['left'] : "",
		'padding-right'      => isset($padding['right']) ? $padding['right'] : "",
		'padding-bottom'     => isset($padding['bottom']) ? $padding['bottom'] : "",
		'margin-top'         => !empty($margin['top']) ? $margin['top'] : "",
		'margin-left'        => !empty($margin['left']) ? $margin['left'] : "",
		'margin-right'       => !empty($margin['right']) ? $margin['right'] : "",
		'margin-bottom'      => !empty($margin['bottom']) ? $margin['bottom'] : "",
	);
	$testimonial_text = array(
		'font-size'   => isset($attributes['textSize']) ? $attributes['textSize'] . 'px' : '',
		'text-align'  => isset($attributes['textAlign']) ? $attributes['textAlign'] : '',
	);

	$testimonial_author = array(
		'text-align' => isset($attributes['authorAlign']) ? $attributes['authorAlign'] : '',
	);

	$testimonial_author_role = array(
		'text-align' => isset($attributes['authorRoleAlign']) ? $attributes['authorRoleAlign'] : '',
	);

	$testimonial_img = array(
		'border-radius' => isset($attributes['imgBorderRadius']) ? $attributes['imgBorderRadius'] . 'px' : '',
	);

	return sprintf(
		'<div>
			<div id="ub_testimonial_%11$s" class="wp-block-ub-testimonial ub_testimonial%1$s" style="%2$s">
				<div class="ub_testimonial_img" style="%12$s">
					<img src="%3$s" alt="%4$s" height="100" width="100" />
				</div>
				<div class="ub_testimonial_content">
					<p class="ub_testimonial_text" style="%5$s">%6$s</p>
				</div>
				<div class="ub_testimonial_sign">
					<p class="ub_testimonial_author" style="%7$s">%8$s</p>
					<p class="ub_testimonial_author_role" style="%9$s">%10$s</p>
				</div>
			</div>
		</div>',
		isset($className) ? ' ' . esc_attr($className) : '', // 1
		Ultimate_Blocks\includes\generate_css_string($wrapper_Styles), // 2
		esc_url($imgURL), // 3
		esc_attr($imgAlt), // 4
		Ultimate_Blocks\includes\generate_css_string($testimonial_text), // 5
		wp_kses_post($ub_testimonial_text), // 6
		Ultimate_Blocks\includes\generate_css_string($testimonial_author), // 7
		wp_kses_post($ub_testimonial_author), // 8
		Ultimate_Blocks\includes\generate_css_string($testimonial_author_role), // 9
		wp_kses_post($ub_testimonial_author_role), // 10
		isset($blockID) ? esc_attr($blockID) : "", // 11
		Ultimate_Blocks\includes\generate_css_string($testimonial_img) // 12
	);
}

function ub_register_testimonial_block() {
	if( function_exists( 'register_block_type_from_metadata' ) ) {
        require dirname(dirname(__DIR__)) . '/defaults.php';
		register_block_type_from_metadata(dirname(dirname(dirname(__DIR__))) . '/dist/blocks/testimonial', array(
            'attributes' =>$defaultValues['ub/testimonial']['attributes'],
            'render_callback' => 'ub_render_testimonial_block'));
    }
}

add_action('init', 'ub_register_testimonial_block');
