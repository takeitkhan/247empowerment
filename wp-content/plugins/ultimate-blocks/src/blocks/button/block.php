<?php

/**
 * Enqueue frontend script for button block
 *
 * @return void
 */

function ub_multi_buttons_parse($b){
    require_once dirname(dirname(__DIR__)) . '/common.php';

    //defaults
    $buttonWidth = 'fixed' ;
    $url = '';
    $openInNewTab = true;
    $addNofollow = true;
    $addSponsored = false;
    $size = 'medium';
    $iconSize = 0;
    $chosenIcon = '';
    $buttonText = 'Button Text';
    $iconUnit = 'px';

    extract($b); //should overwrite the values above if they exist in the array

    $presetIconSize = array('small' => 25, 'medium' => 30, 'large' => 35, 'larger' => 40);

	if ((!isset($iconType) || isset($iconType) && $iconType === "preset") && $chosenIcon !== '' && !is_null($chosenIcon)) {
		$icon = sprintf(
			'<span class="ub-button-icon-holder">
				<svg xmlns="http://www.w3.org/2000/svg" height="%1$s" width="%2$s" viewBox="0 0 %3$s %4$s">
					<path fill="currentColor" d="%5$s">
				</svg>
			</span>',
			($iconSize ? : $presetIconSize[$size]) . ($iconUnit === 'em' ? 'em':''),
			($iconSize ? : $presetIconSize[$size]) . ($iconUnit === 'em' ? 'em' :''),
			Ultimate_Blocks_IconSet::generate_fontawesome_icon($chosenIcon)[0],
			Ultimate_Blocks_IconSet::generate_fontawesome_icon($chosenIcon)[1],
			Ultimate_Blocks_IconSet::generate_fontawesome_icon($chosenIcon)[2]
		);
	} else if (isset($iconType) && $iconType === "custom") {
		$icon = apply_filters('ubpro_buttons_custom_image', "", $b);
	} else {
		$icon = '';
	}

	$link_rel_parts = [];
	if ($openInNewTab) {
		$link_rel_parts[] = 'noopener';
		$link_rel_parts[] = 'noreferrer';
	}
	if ($addNofollow) {
		$link_rel_parts[] = 'nofollow';
	}
	if ($addSponsored) {
		$link_rel_parts[] = 'sponsored';
	}
	$link_rel = implode(' ', $link_rel_parts);

	$link_class = sprintf(
		'ub-button-block-main %1$s %2$s',
		$buttonWidth === 'full' ? ' ub-button-full-width' : '',
		$buttonWidth === 'flex' ? ' ub-button-flex' : ''
	);
	$ubpro_link_classes = apply_filters( 'ubpro_button_link_classes', "", $b);
	$link_class .= $ubpro_link_classes;

	$style_vars = [
		'--ub-button-background-color'			=> $buttonIsTransparent ? 'transparent' : esc_attr($buttonColor),
		'--ub-button-color'						=> $buttonIsTransparent ? esc_attr($buttonColor) : ($buttonTextColor ? esc_attr($buttonTextColor) : 'inherit'),
		'--ub-button-border'					=> $buttonIsTransparent ? '3px solid ' . esc_attr($buttonColor) : 'none',
		'--ub-button-hover-background-color'	=> $buttonIsTransparent ? '' : esc_attr($buttonHoverColor),
		'--ub-button-hover-color'				=> $buttonIsTransparent ? esc_attr($buttonHoverColor) : (isset($buttonTextHoverColor) ? esc_attr($buttonTextHoverColor) : ''),
		'--ub-button-hover-border'				=> $buttonIsTransparent ? '3px solid ' . esc_attr($buttonHoverColor) : 'none',
	];
	$pro_styles = apply_filters('ubpro_buttons_styles', [], $b);
	$style_vars = array_merge($style_vars, $pro_styles);

	$link_style = Ultimate_Blocks\includes\generate_css_string($style_vars);
	$pro_link_styles = apply_filters('ubpro_button_link_styles', array(), $b);
	$link_style .= Ultimate_Blocks\includes\generate_css_string($pro_link_styles);

	// Add typography styles if they exist
	$typography_styles = [];

	// Font family
	if (!empty($b['fontFamily'])) {
		$typography_styles['font-family'] = $b['fontFamily'];
	}

	// Font size
	if (!empty($b['fontSize'])) {
		$typography_styles['font-size'] = $b['fontSize'];
	}

	// Font style
	if (!empty($b['fontStyle'])) {
		$typography_styles['font-style'] = $b['fontStyle'];
	}

	// Font weight
	if (!empty($b['fontWeight'])) {
		$typography_styles['font-weight'] = $b['fontWeight'];
	}

	// Line height
	if (!empty($b['lineHeight'])) {
		$typography_styles['line-height'] = $b['lineHeight'];
	}

	// Letter spacing
	if (!empty($b['letterSpacing'])) {
		$typography_styles['letter-spacing'] = $b['letterSpacing'];
	}

	// Text transform
	if (!empty($b['textTransform'])) {
		$typography_styles['text-transform'] = $b['textTransform'];
	}

	// Text decoration
	if (!empty($b['textDecoration'])) {
		$typography_styles['text-decoration'] = $b['textDecoration'];
	}

	$link_style .= Ultimate_Blocks\includes\generate_css_string($typography_styles);

	// Add width styles if buttonWidth is fixed
	$width_styles = [];
	if (
		$buttonWidth === 'fixed' &&
		!empty($b['width']) &&
		is_array($b['width']) &&
		isset($b['width']['all'])
	) {
		$width_styles['width'] = Ultimate_Blocks\includes\spacing_preset_css_var($b['width']['all']);
	}
	$link_style .= Ultimate_Blocks\includes\generate_css_string($width_styles);

	// Add button padding styles
	$button_padding_styles = [];
		if (!empty($buttonPadding)) {
		$button_padding_css = Ultimate_Blocks\includes\get_spacing_css($buttonPadding);
	} else {
		$button_padding_css = [
			'top' => '10px',
			'right' => '10px',
			'bottom' => '10px',
			'left' => '10px',
		];
	}
	$button_padding_styles['padding-top'] =  $button_padding_css['top'];
	$button_padding_styles['padding-right'] =  $button_padding_css['right'];
	$button_padding_styles['padding-bottom'] =  $button_padding_css['bottom'];
	$button_padding_styles['padding-left'] =  $button_padding_css['left'];
	$link_style .= Ultimate_Blocks\includes\generate_css_string($button_padding_styles);

	if (isset($isBorderComponentChanged) && $isBorderComponentChanged) {
		$link_border_radius_styles = [
			'border-top-left-radius' => !empty( $borderRadius['topLeft'] ) ? esc_attr($borderRadius['topLeft']) . ';': "",
			'border-top-right-radius' => !empty( $borderRadius['topRight'] ) ?  esc_attr($borderRadius['topRight']) . ';': "",
			'border-bottom-left-radius' => !empty( $borderRadius['bottomLeft'] ) ?  esc_attr($borderRadius['bottomLeft']) . ';': "",
			'border-bottom-right-radius' => !empty( $borderRadius['bottomRight'] ) ?  esc_attr($borderRadius['bottomRight']) . ';': "",
		];
	} elseif ($buttonRounded) {
		if ( array_key_exists( 'topLeftRadius', $b ) && array_key_exists( 'topLeftRadiusUnit', $b ) && array_key_exists( 'topRightRadius', $b ) && array_key_exists( 'topRightRadiusUnit', $b ) && array_key_exists( 'bottomLeftRadius', $b ) && array_key_exists( 'bottomLeftRadiusUnit', $b ) && array_key_exists( 'bottomRightRadius', $b ) && array_key_exists( 'bottomRightRadiusUnit', $b ) ) {
			if ( count( array_unique( [ $b['topLeftRadius'], $b['topRightRadius'], $b['bottomLeftRadius'], $b['bottomRightRadius'] ] ) ) === 1 && count( array_unique( [ $b['topLeftRadiusUnit'], $b['topRightRadiusUnit'], $b['bottomLeftRadiusUnit'], $b['bottomRightRadiusUnit'] ] ) ) === 1 ) {
				$link_border_radius_styles = [
					'border-radius' => $b['topLeftRadius'] . $b['topLeftRadiusUnit']
				];
			} else {
				$link_border_radius_styles = [
					'border-radius' => $b['topLeftRadius'] . $b['topLeftRadiusUnit'] . ' ' . $b['topRightRadius'] . $b['topRightRadiusUnit'] . ' ' . $b['bottomRightRadius'] . $b['bottomRightRadiusUnit'] . ' ' . $b['bottomLeftRadius'] . $b['bottomLeftRadiusUnit']
				];
			}
		} else {
			$link_border_radius_styles = [
				'border-radius' => ( array_key_exists( 'buttonRadius', $b ) && $buttonRadius ? $buttonRadius : '60' ) . ( array_key_exists( 'buttonRadiusUnit', $b ) && $buttonRadiusUnit ? $buttonRadiusUnit : 'px' )
			];
		}
	} else {
		$link_border_radius_styles = [
			'border-radius' => '0'
		];
	}

	$link_style .= Ultimate_Blocks\includes\generate_css_string($link_border_radius_styles);

	// Add shadow styles if they exist
	if (isset($shadow) && !empty($shadow)) {
		$shadow_styles = [
			'box-shadow' => Ultimate_Blocks\includes\get_box_shadow_css($shadow)
		];
		$link_style .= Ultimate_Blocks\includes\generate_css_string($shadow_styles);
	}

	return sprintf(
		'<div class="ub-button-container%1$s">
			<a href="%2$s" target="%3$s"%4$s class="%5$s" role="button" style="%9$s">
				<div class="ub-button-content-holder" style="flex-direction: %8$s">
					%6$s<span class="ub-button-block-btn">%7$s</span>
				</div>
			</a>
		</div>',
		($buttonWidth === 'full' ? ' ub-button-full-container' : ''),
		esc_url($url),
		($openInNewTab ? '_blank' : '_self'),
		(!empty($link_rel) ? ' rel="' . esc_attr($link_rel) . '"' : ''),
		esc_attr($link_class),
		$icon,
		wp_kses_post($buttonText),
		$iconPosition === 'left' ? 'row' : 'row-reverse',
		esc_attr($link_style)
	);
}

function ub_single_button_parse($b) {
    require_once dirname(dirname(__DIR__)) . '/common.php';

	extract($b);

    $iconSize = array('small' => 25, 'medium' => 30, 'large' => 35, 'larger' => 40);

	$link_class = sprintf(
		'ub-button-block-main %1$s %2$s',
		$buttonWidth === 'full' ? 'ub-button-full-width' : '',
		$buttonWidth === 'flex' ? 'ub-button-flex' : ''
	);

	$ubpro_link_classes = apply_filters( 'ubpro_button_link_classes', "", $b);
	$link_class .= $ubpro_link_classes;

	$link_style = sprintf('border-radius: %1$spx;', $buttonRounded ? '60' : '0');
	$pro_link_styles = apply_filters('ubpro_button_link_styles', array(), $b);
	$link_style .= Ultimate_Blocks\includes\generate_css_string($pro_link_styles);

	// Initialize default values
	$button_color = !empty($buttonColor) ? $buttonColor : '';
	$button_text_color = !empty($buttonTextColor) ? $buttonTextColor : '';
	$button_hover_color = !empty($buttonHoverColor) ? $buttonHoverColor : '';
	$button_text_hover_color = !empty($buttonTextHoverColor) ? $buttonTextHoverColor : '';
	$button_is_transparent = !empty($buttonIsTransparent) ? $buttonIsTransparent : false;

	$style_vars = [
		'--ub-button-background-color'          => $button_is_transparent ? 'transparent' : esc_attr($button_color),
		'--ub-button-color'                     => $button_is_transparent ? esc_attr($button_color) : esc_attr($button_text_color),
		'--ub-button-border'                    => $button_is_transparent ? '3px solid ' . esc_attr($button_color) : 'none',
		'--ub-button-hover-background-color'    => $button_is_transparent ? '' : esc_attr($button_hover_color),
		'--ub-button-hover-color'               => $button_is_transparent ? esc_attr($button_hover_color) : esc_attr($button_text_hover_color),
		'--ub-button-hover-border'              => $button_is_transparent ? '3px solid ' . esc_attr($button_hover_color) : 'none',
	];
	$link_style .= Ultimate_Blocks\includes\generate_css_string($style_vars);

	// Add typography styles if they exist
	$typography_styles = [];

	// Font family
	if (!empty($fontFamily)) {
		$typography_styles['font-family'] = $fontFamily;
	}

	// Font size
	if (!empty($fontSize)) {
		$typography_styles['font-size'] = $fontSize;
	}

	// Font style
	if (!empty($fontStyle)) {
		$typography_styles['font-style'] = $fontStyle;
	}

	// Font weight
	if (!empty($fontWeight)) {
		$typography_styles['font-weight'] = $fontWeight;
	}

	// Line height
	if (!empty($lineHeight)) {
		$typography_styles['line-height'] = $lineHeight;
	}

	// Letter spacing
	if (!empty($letterSpacing)) {
		$typography_styles['letter-spacing'] = $letterSpacing;
	}

	// Text transform
	if (!empty($textTransform)) {
		$typography_styles['text-transform'] = $textTransform;
	}

	// Text decoration
	if (!empty($textDecoration)) {
		$typography_styles['text-decoration'] = $textDecoration;
	}

	$link_style .= Ultimate_Blocks\includes\generate_css_string($typography_styles);

	// Add width styles if buttonWidth is fixed
	$width_styles = [];
	if (
		$buttonWidth === 'fixed' &&
		!empty($width) &&
		is_array($width) &&
		isset($width['all'])
	) {
		$width_styles['width'] = Ultimate_Blocks\includes\spacing_preset_css_var($width['all']);
	}
	$link_style .= Ultimate_Blocks\includes\generate_css_string($width_styles);

	// Add button padding styles
	$button_padding_styles = [];
	if (!empty($buttonPadding)) {
		$button_padding_css = Ultimate_Blocks\includes\get_spacing_css($buttonPadding);
	} else {
		$button_padding_css = [
			'top' => '10px',
			'right' => '10px',
			'bottom' => '10px',
			'left' => '10px',
		];
	}
	$button_padding_styles['padding-top'] =  $button_padding_css['top'];
	$button_padding_styles['padding-right'] =  $button_padding_css['right'];
	$button_padding_styles['padding-bottom'] =  $button_padding_css['bottom'];
	$button_padding_styles['padding-left'] =  $button_padding_css['left'];
	$link_style .= Ultimate_Blocks\includes\generate_css_string($button_padding_styles);

	$link_rel_parts = [];
	if ($openInNewTab) {
		$link_rel_parts[] = 'noopener';
		$link_rel_parts[] = 'noreferrer';
	}
	if ($addNofollow) {
		$link_rel_parts[] = 'nofollow';
	}
	if (isset($addSponsored) && $addSponsored) {
		$link_rel_parts[] = 'sponsored';
	}
	$link_rel = implode(' ', $link_rel_parts);

	$link_attrs = sprintf(
		'href="%s" target="%s"%s class="%s" style="%s"',
		esc_url($url),
		($openInNewTab ? '_blank' : '_self'),
		(!empty($link_rel) ? ' rel="' . esc_attr($link_rel) . '"' : ''),
		esc_attr($link_class),
		esc_attr($link_style)
	);

	if ($chosenIcon !== '') {
		$icon = sprintf(
			'<span class="ub-button-icon-holder">
				<svg xmlns="http://www.w3.org/2000/svg" height="%1$s" width="%2$s" viewBox="0 0 %3$s %4$s">
					<path fill="currentColor" d="%5$s">
				</svg>
			</span>',
			esc_attr($iconSize[$size]),
			esc_attr($iconSize[$size]),
			Ultimate_Blocks_IconSet::generate_fontawesome_icon($chosenIcon)[0],
			Ultimate_Blocks_IconSet::generate_fontawesome_icon($chosenIcon)[1],
			Ultimate_Blocks_IconSet::generate_fontawesome_icon($chosenIcon)[2]
		);
	} else {
		$icon = '';
	}

	return sprintf(
		'<div %1$s>
			<a %2$s>
				<div class="ub-button-content-holder" style="flex-direction: %3$s">
					%4$s<span class="ub-button-block-btn">%5$s</span>
				</div>
			</a>
		</div>',
		$container_attrs,
		$link_attrs,
		$attributes['iconPosition'] === 'left' ? 'row' : 'row-reverse',
		$icon,
		wp_kses_post($buttonText)
	);
}

function ub_render_button_block($attributes, $_, $block){
    require_once dirname(dirname(__DIR__)) . '/common.php';
    extract($attributes);
	$block_attrs = $block->parsed_block['attrs'];

	$multiButton = isset($buttons) && count($buttons) > 0;

    	$classes = array();
	$styles = '';
	$styles .= !Ultimate_Blocks\includes\is_undefined( $blockSpacing ) ? '--ub-button-improved-block-spacing:' . Ultimate_Blocks\includes\spacing_preset_css_var($blockSpacing['all'])  . ';': "";

	if ($multiButton) {
		foreach ($buttons as $key => &$b) {
			$b['isBorderComponentChanged'] = $isBorderComponentChanged;
		}
		$buttonDisplay = join('', array_map('ub_multi_buttons_parse', $buttons));

		$classes[] = $align === '' ? 'align-button-center' : 'align-button-' . esc_attr($align);
		$classes[] = 'ub-buttons';

		$spacing = !Ultimate_Blocks\includes\is_undefined( $block_attrs['blockSpacing'] ) ?
			Ultimate_Blocks\includes\spacing_preset_css_var($block_attrs['blockSpacing']['all']) :
			'20px';
		$styles .= sprintf('gap: %1$s;', $spacing);
	} else {
		$buttonDisplay = ub_single_button_parse($attributes);
		$classes[] = 'ub-button';
	}

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
	);
	$styles .= Ultimate_Blocks\includes\generate_css_string($wrapper_padding);
    	$classes[] = 'orientation-button-' . esc_attr($orientation) . '';
	$classes[] = $isFlexWrap ? 'ub-flex-wrap' : "";

    $block_attributes = get_block_wrapper_attributes(
            array(
				'class' => implode(" ", $classes),
				'style' => $styles,
            )
    );

	if (isset($blockID) && $blockID !== '') {
		$id = sprintf('id="ub-button-%1$s"', esc_attr($blockID));
	} else {
		$id = '';
	}

	return sprintf(
		'<div %1$s %2$s>%3$s</div>',
		$block_attributes,
		$id,
		$buttonDisplay
	);
}

function ub_button_add_frontend_assets() {
    wp_register_script(
		'ultimate_blocks-button-front-script',
		plugins_url( 'button/front.build.js', dirname( __FILE__ ) ),
		array( ),
		Ultimate_Blocks_Constants::plugin_version(),
		true
	);
}

function ub_register_button_block() {
	if ( function_exists( 'register_block_type_from_metadata' ) ) {
        require dirname(dirname(__DIR__)) . '/defaults.php';
		register_block_type_from_metadata( dirname(dirname(dirname(__DIR__))) . '/dist/blocks/button', array(
            'attributes' => $defaultValues['ub/button']['attributes'],
			'render_callback' => 'ub_render_button_block'));
	}
}

add_action('init', 'ub_register_button_block');

add_action( 'wp_enqueue_scripts', 'ub_button_add_frontend_assets' );
