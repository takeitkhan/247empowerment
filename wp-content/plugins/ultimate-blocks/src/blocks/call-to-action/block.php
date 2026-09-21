<?php

function ub_render_call_to_action_block($attributes, $_, $block) {
    extract($attributes);
    $classes = array( 'ub_call_to_action' );
	$block_attrs = $block->parsed_block['attrs'];

	$padding = Ultimate_Blocks\includes\get_spacing_css( isset($block_attrs['padding']) ? $block_attrs['padding'] : array() );
	$margin  = Ultimate_Blocks\includes\get_spacing_css( isset($block_attrs['margin']) ? $block_attrs['margin'] : array() );

	$wrapper_padding = array(
		'padding-top'        => isset($padding['top']) ? $padding['top'] : "",
		'padding-left'       => isset($padding['left']) ? $padding['left'] : "",
		'padding-right'      => isset($padding['right']) ? $padding['right'] : "",
		'padding-bottom'     => isset($padding['bottom']) ? $padding['bottom'] : "",
		'margin-top'         => !empty($margin['top']) ? $margin['top']  : "",
		'margin-left'        => !empty($margin['left']) ? $margin['left']  : "",
		'margin-right'       => !empty($margin['right']) ? $margin['right']  : "",
		'margin-bottom'      => !empty($margin['bottom']) ? $margin['bottom']  : "",
		'background-color' => isset($attributes['ctaBackgroundColor']) ? $attributes['ctaBackgroundColor'] : "",
		'border-width' => isset($attributes['ctaBorderSize']) ? $attributes['ctaBorderSize'] . 'px' : "",
		'border-color' => isset($attributes['ctaBorderColor']) ? $attributes['ctaBorderColor'] : "",
	);
	$cta_headline_styles = array(
	'font-size' => isset($attributes['headFontSize']) ? $attributes['headFontSize'] . 'px' : "",
	'color' => isset($attributes['headColor']) ? $attributes['headColor'] : "inherit",
	'text-align' => isset($attributes['headAlign']) ? $attributes['headAlign'] : "",
	);

	$cta_content_styles = array(
	'font-size' => isset($attributes['contentFontSize']) ? $attributes['contentFontSize'] . 'px' : "",
	'color' => isset($attributes['contentColor']) ? $attributes['contentColor'] : "inherit",
	'text-align' => isset($attributes['contentAlign']) ? $attributes['contentAlign'] : "",
	);

	$cta_button_styles = array(
	'background-color' => isset($attributes['buttonColor']) ? $attributes['buttonColor'] : "",
	'width' => isset($attributes['buttonWidth']) ? $attributes['buttonWidth'] . 'px' : "",
	);

	$cta_button_text_styles = array(
	'color' => isset($attributes['buttonTextColor']) ? $attributes['buttonTextColor'] : "inherit",
	'font-size' => isset($attributes['buttonFontSize']) ? $attributes['buttonFontSize'] . 'px' : "",
	);


	$wrapper_styles = Ultimate_Blocks\includes\generate_css_string($wrapper_padding);

    $block_wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => implode(' ', $classes),
			'style' => $wrapper_styles,
		)
    );

	if (!in_array($selectedHeadingTag, ['h2', 'h3', 'h4', 'h5', 'h6'])) {
		$selectedHeadingTag = 'h2';
	}

	return sprintf(
		'<div %1$s %2$s>
			<div class="ub_call_to_action_headline">
				<%3$s class="ub_call_to_action_headline_text"%4$s style="%14$s">%5$s</%3$s>
			</div>
			<div class="ub_call_to_action_content">
				<p class="ub_cta_content_text"%6$s style="%15$s">%7$s</p>
			</div>
			<div class="ub_call_to_action_button">
				<a href="%8$s" target="_%9$s" rel="%10$s" class="ub_cta_button"%11$s style="%16$s">
					<p class="ub_cta_button_text"%12$s style="%17$s">%13$s</p>
				</a>
			</div>
		</div>',
		$block_wrapper_attributes, // 1
		($blockID !== '' ? ' id="ub_call_to_action_' . esc_attr($blockID) . '"' :
			'style="background-color: ' . esc_attr($ctaBackgroundColor) . '; border-width: ' . esc_attr($ctaBorderSize) . 'px; border-color: ' . esc_attr($ctaBorderColor) . '"'), // 2
		($useHeadingTag ? esc_attr($selectedHeadingTag) : 'p'), // 3
		($blockID === '' ? ' style="font-size: ' . esc_attr($headFontSize) . 'px; color: ' . esc_attr($headColor) . '; text-align: ' . esc_attr($headAlign) . ';"' : ''), // 4
		wp_kses_post($ub_call_to_action_headline_text), // 5
		($blockID === '' ? ' style="font-size: ' . esc_attr($contentFontSize) . 'px; color: ' . esc_attr($contentColor) . '; text-align: ' . esc_attr($contentAlign) . ';"' : ''), // 6
		wp_kses_post($ub_cta_content_text), // 7
		esc_url($url), // 8
		($openInNewTab ? 'blank' : 'self'), // 9
		($addNofollow ? 'nofollow ' : '') . ($linkIsSponsored ? 'sponsored ' : '') . 'noopener noreferrer', // 10
		($blockID === '' ? ' style="background-color: ' . esc_attr($buttonColor) . '; width: ' . esc_attr($buttonWidth) . 'px;"' : ''), // 11
		($blockID === '' ? ' style="color: ' . esc_attr($buttonTextColor) . '; font-size: ' . esc_attr($buttonFontSize) . 'px;"' : ''), // 12
		wp_kses_post($ub_cta_button_text), // 13
		 esc_attr( Ultimate_Blocks\includes\generate_css_string($cta_headline_styles) ), //14
		 esc_attr( Ultimate_Blocks\includes\generate_css_string($cta_content_styles) ), //15
		 esc_attr( Ultimate_Blocks\includes\generate_css_string($cta_button_styles) ), //16
		 esc_attr( Ultimate_Blocks\includes\generate_css_string($cta_button_text_styles) ) //17
	);
}

function ub_register_call_to_action_block() {
	if ( function_exists( 'register_block_type_from_metadata' ) ) {
        require dirname(dirname(__DIR__)) . '/defaults.php';
		register_block_type_from_metadata( dirname(dirname(dirname(__DIR__))) . '/dist/blocks/call-to-action', array(
            'attributes' => $defaultValues['ub/call-to-action-block']['attributes'],
			'render_callback' => 'ub_render_call_to_action_block'));
	}
}

add_action('init', 'ub_register_call_to_action_block');
