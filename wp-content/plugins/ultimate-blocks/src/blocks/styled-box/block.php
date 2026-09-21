<?php

function ub_render_styled_box_bordered_content($attributes, $content){
    return $content;
}

function ub_register_styled_box_bordered_box_block() {
	if( function_exists( 'register_block_type_from_metadata' ) ) {
		register_block_type_from_metadata( dirname(dirname(dirname(__DIR__))) . '/dist/blocks/styled-box/styled-box-border/block.json', array(
            'attributes' => array(),
            'render_callback' => 'ub_render_styled_box_bordered_content')
        );
    }
}

add_action('init', 'ub_register_styled_box_bordered_box_block');

function ub_render_styled_box_numbered_box_column($attributes, $content){
    extract($attributes);
	$panel_styles = array(
		'border-color' =>  isset($attributes['borderColor']) ? $attributes['borderColor'] : "#000000"
	);
	$container_styles = array(
		'background-color' => isset($attributes['backColor']) ? $attributes['backColor'] : "#CCCCCC"
	);
	$number_display_styles = array(
		'color' => isset($attributes['numberColor']) ? $attributes['numberColor'] : "#000000"
	);
	$title_styles = array(
		'text-align' => isset($attributes['titleAlign']) ? $attributes['titleAlign'] : 'center'
	);
	$body_styles = array(
		'text-align' => isset($attributes['textAlign']) ? $attributes['textAlign'] : 'left'
	);

	return sprintf(
		'<div class="ub-number-panel" style="%4$s">
			<div class="ub-number-container" style="%5$s">
				<p class="ub-number-display" style="%6$s">%1$s</p>
			</div>
			<p class="ub-number-box-title" style="%7$s">%2$s</p>
			<div class="ub-number-box-body" style="%8$s">%3$s</div>
		</div>',
		wp_kses_post($number),
		wp_kses_post($title),
		wp_kses_post($content),
		Ultimate_Blocks\includes\generate_css_string($panel_styles),
		Ultimate_Blocks\includes\generate_css_string($container_styles),
		Ultimate_Blocks\includes\generate_css_string($number_display_styles),
		Ultimate_Blocks\includes\generate_css_string($title_styles),
		Ultimate_Blocks\includes\generate_css_string($body_styles)
	);
}

function ub_register_styled_box_numbered_box_column_block() {
	if( function_exists( 'register_block_type_from_metadata' ) ) {
		register_block_type_from_metadata( dirname(dirname(dirname(__DIR__))) . '/dist/blocks/styled-box/styled-box-numbered-box-column/block.json', array(
            'attributes' => array(
                'number' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'title' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'titleAlign' => array(
                    'type' => 'string',
                    'default' => 'center'
                ),
                'numberColor' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'backColor' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'borderColor' => array(
                    'type' => 'string',
                    'default' => ''
                )
            ),
            'render_callback' => 'ub_render_styled_box_numbered_box_column')
        );
    }
}

add_action('init', 'ub_register_styled_box_numbered_box_column_block');

function ub_render_styled_box_block($attributes, $content, $block){
    extract($attributes);
    $renderedBlock = '';
	$block_attrs = $block->parsed_block['attrs'];

	if ($mode === 'notification' && $text[0] != '') {
		$renderedBlock = sprintf('<div class="ub-notification-text">%1$s</div>', wp_kses_post($text[0]));
	} else if ($mode === 'feature') {
		foreach (range(0, count($text) - 1) as $i) {
			$title_styles = array(
				'text-align' => isset($attributes['titleAlign'][$i]) ? $attributes['titleAlign'][$i] : 'center'
			);

			$body_styles = array(
				'text-align' => isset($attributes['textAlign'][$i]) ? $attributes['textAlign'][$i] : 'left'
			);

			$renderedBlock .= sprintf(
				'<div class="ub-feature">%1$s<p class="ub-feature-title" style="%4$s">%2$s</p><p class="ub-feature-body" style="%5$s">%3$s</p></div>',
				empty($image[$i]['url']) ? '' : sprintf('<img class="ub-feature-img" src="%1$s"/>', esc_url($image[$i]['url'])),
				wp_kses_post($title[$i]),
				wp_kses_post($text[$i]),
				Ultimate_Blocks\includes\generate_css_string($title_styles),
				Ultimate_Blocks\includes\generate_css_string($body_styles)
			);
		}
	} else if ($mode === 'number') {
		$panel_styles = array(
			'border-color' =>  isset($attributes['outlineColor']) ? $attributes['outlineColor'] : "#000000"
		);
		$container_styles = array(
			'background-color' => isset($attributes['backColor']) ? $attributes['backColor'] : "#CCCCCC"
		);
		$number_display_styles = array(
			'color' => isset($attributes['foreColor']) ? $attributes['foreColor'] : "#000000"
		);

		if (count(array_filter($text, function ($item) { return $item !== ''; })) > 0 ||
			count(array_filter($title, function ($item) { return $item !== ''; })) > 0) {
			foreach (range(0, count($text) - 1) as $i) {
				$renderedBlock .= sprintf(
					'<div class="ub-number-panel" style="%4$s"><div class="ub-number-container" style="%5$s"><p class="ub-number-display" style="%6$s">%1$s</p></div><p class="ub-number-box-title">%2$s</p><p class="ub-number-box-body">%3$s</p></div>',
					wp_kses_post($number[$i]),
					wp_kses_post($title[$i]),
					wp_kses_post($text[$i]),
					Ultimate_Blocks\includes\generate_css_string($panel_styles),
					Ultimate_Blocks\includes\generate_css_string($container_styles),
					Ultimate_Blocks\includes\generate_css_string($number_display_styles)
				);
			}
		} else {
			$renderedBlock = $content;
		}
	} else if (in_array($mode, array('bordered', 'notification'))) {
		$renderedBlock = $content;
	}
	$classes = array('ub-styled-box');
	$wrapper_styles = array();
	$padding = Ultimate_Blocks\includes\get_spacing_css( isset($block_attrs['padding']) ? $block_attrs['padding'] : array() );
	$margin  = Ultimate_Blocks\includes\get_spacing_css( isset($block_attrs['margin']) ? $block_attrs['margin'] : array() );

	$wrapper_spacing = array(
		'padding-top'        => isset($padding['top']) ? $padding['top'] : "",
		'padding-left'       => isset($padding['left']) ? $padding['left'] : "",
		'padding-right'      => isset($padding['right']) ? $padding['right'] : "",
		'padding-bottom'     => isset($padding['bottom']) ? $padding['bottom'] : "",
		'margin-top'         => !empty($margin['top']) ? $margin['top']  : "",
		'margin-left'        => !empty($margin['left']) ? $margin['left']  : "",
		'margin-right'       => !empty($margin['right']) ? $margin['right']  : "",
		'margin-bottom'      => !empty($margin['bottom']) ? $margin['bottom']  : "",
	);


	if ($mode === 'notification') {
		if (isset($attributes['backColor']) && $attributes['backColor'] !== '') {
			$wrapper_styles['background-color'] =   $attributes['backColor'];
		}
		if (isset($attributes['foreColor']) && $attributes['foreColor'] !== '') {
			$wrapper_styles['color'] = $attributes['foreColor'];
		}
		if (isset($attributes['outlineColor']) && $attributes['outlineColor'] !== '') {
			$wrapper_styles['border-left-color'] = $attributes['outlineColor'];
		}
		if (isset($attributes['text'][0]) && $attributes['text'][0] !== '') {
			$wrapper_styles['text-align'] = $attributes['textAlign'][0];
		}
	} else if ($mode === 'bordered') {
		$radiusUnit = '';
		if (isset($attributes['outlineRadiusUnit'])) {
			if ($attributes['outlineRadiusUnit'] === 'percent') {
				$radiusUnit = '%';
			} elseif ($attributes['outlineRadiusUnit'] === 'pixel') {
				$radiusUnit = 'px';
			} elseif ($attributes['outlineRadiusUnit'] === 'em') {
				$radiusUnit = 'em';
			}
		}
		if (isset($attributes['outlineThickness'], $attributes['outlineStyle'], $attributes['outlineColor'])) {
			$wrapper_styles['border'] = $attributes['outlineThickness'] . 'px ' . $attributes['outlineStyle'] . ' ' . $attributes['outlineColor'];
		}
		if (isset($attributes['outlineRoundingRadius'])) {
			$wrapper_styles['border-radius'] = $attributes['outlineRoundingRadius'] . $radiusUnit;
		}
		if (isset($attributes['boxColor'])) {
			$wrapper_styles['background-color'] = $attributes['boxColor'] ?: 'inherit';
		}
	}
	if(isset($attributes['mode'])) {
		$classes[] = 'ub-' . $attributes['mode'] . '-box';
	}

	$wrapper_styles = array_merge($wrapper_styles, $wrapper_spacing);
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => implode(' ', $classes),
			'id'	=> 'ub-styled-box-' . $blockID,
			'style' => Ultimate_Blocks\includes\generate_css_string($wrapper_styles)
		)
	);
	return sprintf(
		'<div %2$s>%1$s</div>',
		$renderedBlock,
		$wrapper_attributes
	);
}

function ub_register_styled_box_block() {
	if( function_exists( 'register_block_type_from_metadata' ) ) {
        require dirname(dirname(__DIR__)) . '/defaults.php';
		register_block_type_from_metadata( dirname(dirname(dirname(__DIR__))) . '/dist/blocks/styled-box/block.json', array(
            'attributes' => $defaultValues['ub/styled-box']['attributes'],
            'render_callback' => 'ub_render_styled_box_block'));
    }
}

add_action('init', 'ub_register_styled_box_block');
