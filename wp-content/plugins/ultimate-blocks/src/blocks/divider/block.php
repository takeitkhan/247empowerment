<?php

function ub_render_divider_block($attributes, $_ ,$block){
    extract($attributes);

	$block_attrs = $block->parsed_block['attrs'];

	$padding = Ultimate_Blocks\includes\get_spacing_css( isset($block_attrs['padding']) ? $block_attrs['padding'] : array() );
	$margin  = Ultimate_Blocks\includes\get_spacing_css( isset($block_attrs['margin']) ? $block_attrs['margin'] : array() );
	$alignment = isset($block_attrs['alignment']) ? $block_attrs['alignment'] : 'center';
	$classNames = array( "wp-block-ub-divider", "ub_divider" );

	if(isset($orientation)){
		array_push($classNames, 'ub-divider-orientation-'. $orientation .'');
	}
	if(isset($align)){
		array_push($classNames, 'align'. $align .'');
	}


	$divider_wrapper_style = array( 'position' => 'relative' );
	$divider_style = array();
	if ($orientation === 'horizontal') {
		$divider_style['margin-top'] = isset($borderHeight) ? $borderHeight . 'px' : '';
		$divider_wrapper_style['margin-bottom'] = isset($borderHeight) ? $borderHeight . 'px' : '';
	} else {
		$divider_style['width'] = 'fit-content';
		$divider_style['height'] = isset($lineHeight) ? $lineHeight : '';
	}
	$divider_width = isset($attributes['isWidthControlChanged']) && $attributes['isWidthControlChanged'] && isset($attributes['dividerWidth']) ? $attributes['dividerWidth'] : $attributes['width'] . '%';

	if ($orientation === 'horizontal') {
		$divider_wrapper_style['width'] = $divider_width;
		$divider_wrapper_style['height'] = isset($borderHeight) ? $borderHeight . 'px': '';
		$divider_style['margin-top'] = isset($borderHeight) ? $borderHeight . 'px': '';
		$divider_wrapper_style['margin-bottom'] = isset($borderHeight) ? $borderHeight . 'px': '';
	} else {
		$divider_wrapper_style['width'] = $borderSize . "px";
		$divider_wrapper_style['height'] = isset($lineHeight) ? $lineHeight	: '';
	}

	$divider_style_string = sprintf(
		'style="%1$s: %2$s %3$s %4$s; %5$s"',
		$orientation === 'horizontal' ? 'border-top' : 'border-left',
		esc_attr($borderSize . 'px'),
		esc_attr($borderStyle),
		esc_attr($borderColor),
		Ultimate_Blocks\includes\generate_css_string($divider_style)
	);

	$styles  = array(
		'padding-top'        => isset($padding['top']) ? $padding['top'] : "",
		'padding-left'       => isset($padding['left']) ? $padding['left'] : "",
		'padding-right'      => isset($padding['right']) ? $padding['right'] : "",
		'padding-bottom'     => isset($padding['bottom']) ? $padding['bottom'] : "",
		'margin-top'         => !empty($margin['top']) ? $margin['top']  : "",
		'margin-left'        => !empty($margin['left']) ? $margin['left']  : "",
		'margin-right'       => !empty($margin['right']) ? $margin['right']  : "",
		'margin-bottom'      => !empty($margin['bottom']) ? $margin['bottom']  : "",
	);

	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => join(' ', $classNames),
			'id'	=> 'ub_divider_' . $blockID .'',
			'style' => Ultimate_Blocks\includes\generate_css_string($styles),

		)
	);

	$divider_html = sprintf(
		'<div %1$s><div class="ub_divider_wrapper" style="%5$s" data-divider-alignment="%4$s"><div class="ub_divider_line%2$s" %3$s></div>%6$s</div></div>',
		$wrapper_attributes, // 1
		isset($className) ? ' ' . esc_attr($className) : '', // 2
		$divider_style_string, // 3
		esc_attr($alignment), // 4
		Ultimate_Blocks\includes\generate_css_string($divider_wrapper_style), //5
		apply_filters('ubpro_divider_content', '', $attributes, $block) // 6
	);

	return $divider_html;
}

function ub_register_divider_block(){
    if ( function_exists( 'register_block_type_from_metadata' ) ) {
        require dirname(dirname(__DIR__)) . '/defaults.php';
        register_block_type_from_metadata( dirname(dirname(dirname(__DIR__))) . '/dist/blocks/divider', array(
            'attributes' => $defaultValues['ub/divider']['attributes'],
            'render_callback' => 'ub_render_divider_block'));
    }
}

add_action('init', 'ub_register_divider_block');
