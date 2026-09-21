<?php
require_once dirname(dirname(dirname(__DIR__))) . '/includes/ultimate-blocks-styles-css-generator.php';
require_once dirname(dirname(dirname(__DIR__))) . '/src/common.php';

function ub_render_styled_list_block($attributes, $contents, $block){
    extract($attributes);

    $listItems = '';

    if(json_encode($listItem) === '[' . join(',', array_fill(0, 3,'{"text":"","selectedIcon":"check","indent":0}')) . ']'){
        $listItems = $list;
    }
    else{
        $sortedItems = [];

        foreach($listItem as $elem){
            $last = count($sortedItems) - 1;
            if (count($sortedItems) === 0 || $sortedItems[$last][0]['indent'] < $elem['indent']) {
                array_push($sortedItems, array($elem));
            }
            else if ($sortedItems[$last][0]['indent'] === $elem['indent']){
                array_push($sortedItems[$last], $elem);
            }
            else{
                while($sortedItems[$last][0]['indent'] > $elem['indent']){
                    array_push($sortedItems[count($sortedItems) - 2], array_pop($sortedItems));
                    $last = count($sortedItems) - 1;
                }
                if($sortedItems[$last][0]['indent'] === $elem['indent']){
                    array_push($sortedItems[$last], $elem);
                }
            }
        }

        while(count($sortedItems) > 1 &&
            $sortedItems[count($sortedItems) - 1][0]['indent'] > $sortedItems[count($sortedItems) - 2][0]['indent']){
            array_push($sortedItems[count($sortedItems) - 2], array_pop($sortedItems));
        }

        $sortedItems = $sortedItems[0];

        if (!function_exists('ub_makeList')) {
            function ub_makeList($num, $item, $color, $size){
                static $outputString = '';
                if($num === 0 && $outputString != ''){
                    $outputString = '';
                }
                if (isset($item['indent'])){
                    $outputString .= '<li>'.($item['text'] === '' ? '<br/>' : $item['text']) . '</li>';
                }
                else{
                    $outputString = substr_replace($outputString, '<ul class="fa-ul">',
                        strrpos($outputString, '</li>'), strlen('</li>'));

                    forEach($item as $key => $subItem){
                        ub_makeList($key+1, $subItem, $color, $size);
                    }
                    $outputString .= '</ul>' . '</li>';
                }
                return $outputString;
            }
        }

        foreach($sortedItems as $key => $item){
            $listItems = ub_makeList($key, $item, $iconColor, $iconSize);
        }
    }
    $list_alignment_class = !empty($listAlignment) ? "ub-list-alignment-" . esc_attr($listAlignment) : "";


	$block_attributes  = isset($block->parsed_block['attrs']) ? $block->parsed_block['attrs'] : array();

    	$padding = Ultimate_Blocks\includes\get_spacing_css( isset($block_attributes['padding']) ? $block_attributes['padding'] : array() );
	$margin = Ultimate_Blocks\includes\get_spacing_css( isset($block_attributes['margin']) ? $block_attributes['margin'] : array() );

	// Handle icon rendering based on icon source
	$iconSource = isset($attributes['iconSource']) ? $attributes['iconSource'] : 'fontawesome';
	$customIconSVG = isset($attributes['customIconSVG']) ? $attributes['customIconSVG'] : '';

	if ($iconSource === 'custom' && !empty($customIconSVG)) {
		// Use custom SVG - we'll embed it in CSS as a data URI
		$iconData = array();
		// Sanitize the SVG
		$sanitized_svg = wp_kses($customIconSVG, array(
			'svg' => array(
				'xmlns' => array(),
				'viewbox' => array(),
				'width' => array(),
				'height' => array(),
				'fill' => array(),
				'stroke' => array(),
				'stroke-width' => array(),
				'class' => array(),
				'aria-hidden' => array(),
				'aria-labelledby' => array(),
				'role' => array(),
			),
			'path' => array(
				'd' => array(),
				'fill' => array(),
				'stroke' => array(),
				'stroke-width' => array(),
				'class' => array(),
			),
			'circle' => array(
				'cx' => array(),
				'cy' => array(),
				'r' => array(),
				'fill' => array(),
				'stroke' => array(),
				'stroke-width' => array(),
				'class' => array(),
			),
			'rect' => array(
				'x' => array(),
				'y' => array(),
				'width' => array(),
				'height' => array(),
				'fill' => array(),
				'stroke' => array(),
				'stroke-width' => array(),
				'class' => array(),
			),
			'line' => array(
				'x1' => array(),
				'y1' => array(),
				'x2' => array(),
				'y2' => array(),
				'stroke' => array(),
				'stroke-width' => array(),
				'class' => array(),
			),
			'polyline' => array(
				'points' => array(),
				'fill' => array(),
				'stroke' => array(),
				'stroke-width' => array(),
				'class' => array(),
			),
			'polygon' => array(
				'points' => array(),
				'fill' => array(),
				'stroke' => array(),
				'stroke-width' => array(),
				'class' => array(),
			),
			'g' => array(
				'fill' => array(),
				'stroke' => array(),
				'stroke-width' => array(),
				'class' => array(),
			),
		));
		// URL encode for data URI
		$encoded_svg = rawurlencode($sanitized_svg);
		$iconBackgroundImage = 'url(\'data:image/svg+xml,' . $encoded_svg . '\')';
	} else {
		// Use FontAwesome icon
		$iconData = Ultimate_Blocks_IconSet::generate_fontawesome_icon( $attributes['selectedIcon'] );
		$iconBackgroundImage = 'url(\'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $iconData[0] . ' ' . $iconData[1] . '"><path fill="%23' . substr( $attributes['iconColor'], 1 ) . '" d="' . $iconData[2] . '"></path></svg>\')';
	}

	$list_styles = array(
		'padding-top'         => isset($padding['top']) ? esc_attr($padding['top']) : "",
		'padding-left'        => isset($padding['left']) ? esc_attr($padding['left']) : "",
		'padding-right'       => isset($padding['right']) ? esc_attr($padding['right']) : "",
		'padding-bottom'      => isset($padding['bottom']) ? esc_attr($padding['bottom']) : "",
		'margin-top'          => !empty($margin['top']) ? esc_attr($margin['top']) . " !important" : "",
		'margin-left'         => !empty($margin['left']) ? esc_attr($margin['left']) . " !important" : "",
		'margin-right'        => !empty($margin['right']) ? esc_attr($margin['right']) . " !important" : "",
		'margin-bottom'       => !empty($margin['bottom']) ? esc_attr($margin['bottom']) . " !important" : "",
		'background-color'    => !empty($attributes['backgroundColor']) ? esc_attr($attributes['backgroundColor']) : "",
	);

	$list_styles['text-align'] = $attributes['alignment'];
	if (isset($attributes['textColor'])) {
		$list_styles['color'] = $attributes['textColor'];
	}
	if (isset($attributes['backgroundColor'])) {
		$list_styles['background-color'] = $attributes['backgroundColor'];
	}

	// Typography styles for parent list
	if (!empty($attributes['listFontFamily'])) {
		$list_styles['font-family'] = esc_attr($attributes['listFontFamily']);
	}
	if (!empty($attributes['listFontSize'])) {
		$list_styles['font-size'] = esc_attr($attributes['listFontSize']);
	}
	if (!empty($attributes['listFontAppearance']['fontStyle'])) {
		$list_styles['font-style'] = esc_attr($attributes['listFontAppearance']['fontStyle']);
	}
	if (!empty($attributes['listFontAppearance']['fontWeight'])) {
		$list_styles['font-weight'] = esc_attr($attributes['listFontAppearance']['fontWeight']);
	}
	if (!empty($attributes['listLineHeight'])) {
		$list_styles['line-height'] = esc_attr($attributes['listLineHeight']);
	}
	if (!empty($attributes['listLetterSpacing'])) {
		$list_styles['letter-spacing'] = esc_attr($attributes['listLetterSpacing']);
	}
	if (!empty($attributes['listTextDecoration'])) {
		$list_styles['text-decoration'] = esc_attr($attributes['listTextDecoration']);
	}
	if (!empty($attributes['listTextTransform'])) {
		$list_styles['text-transform'] = esc_attr($attributes['listTextTransform']);
	}
	$list_styles['--ub-list-item-icon-top'] = ( $attributes['iconSize'] >= 5 ? 3 : ( $attributes['iconSize'] < 3 ? 2 : 0 ) ) . 'px;';
	$list_styles['--ub-list-item-icon-size'] = ( ( 4 + $attributes['iconSize'] ) / 10 ) . 'em';
	$list_styles['--ub-list-item-background-image'] = $iconBackgroundImage;
	if ( $attributes['iconSize'] < 3 ) {
		$list_styles['--ub-list-item-fa-li-top'] = '-0.1em';
	} elseif ( $attributes['iconSize'] >= 5 ) {
		$list_styles['--ub-list-item-fa-li-top'] = '3px';
	}

	if(isset($attributes['itemSpacing']) && $isRootList){
		$list_styles['--ub-list-item-spacing'] = $attributes['itemSpacing'] . 'px';
	}

	if(!isset($padding['left']) ){
		$list_styles['padding-left'] = isset($attributes['iconSize']) ? ( ( 6 + $attributes['iconSize'] ) / 10 ) . 'em' : "";
	}
	$list_layout_styles = array(
		'text-align'         			=> isset($attributes['alignment']) ? esc_attr($attributes['alignment']) : "",
		'color'              			=> !empty($attributes['textColor']) ? esc_attr($attributes['textColor']) : "",
	);
	if ($isRootList) {
		$list_layout_styles['column-count'] = isset($attributes['columns']) ? esc_attr($attributes['columns']) : "";

		// Add responsive column CSS custom properties
		if (isset($attributes['tabletColumns'])) {
			$list_layout_styles['--ub-list-tablet-column-count'] = esc_attr($attributes['tabletColumns']);
		}
		if (isset($attributes['maxMobileColumns'])) {
			$list_layout_styles['--ub-list-mobile-column-count'] = esc_attr($attributes['maxMobileColumns']);
		}
	}

	if(!empty($listAlignment) && $listAlignment === 'left'){
		$list_layout_styles['width'] = 'fit-content';
		$list_layout_styles['margin-right'] = 'auto';
		$list_layout_styles['margin-left'] = '0';
	}
	if(!empty($listAlignment) && $listAlignment === 'center'){
		$list_layout_styles['width'] = 'fit-content';
		$list_layout_styles['margin-right'] = 'auto';
		$list_layout_styles['margin-left'] = 'auto';
	}
	if(!empty($listAlignment) && $listAlignment === 'right'){
		$list_layout_styles['width'] = 'fit-content';
		$list_layout_styles['margin-right'] = '0';
		$list_layout_styles['margin-left'] = 'auto';
	}

	// Merge layout styles onto <ul> — putting a <div> inside <ul> is invalid HTML (ADA compliance)
	$list_styles = array_merge($list_styles, array_filter($list_layout_styles, function($v) { return $v !== ''; }));

	$classes = array('wp-block-ub-styled-list');
	$classes[] = $isRootList ? "ub_styled_list" : "ub_styled_list_sublist";
	if (!empty($list_alignment_class)) {
		$classes[] = $list_alignment_class;
	}
	if (isset($className)) {
		$classes[] = esc_attr($className);
	}

	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => implode(' ', $classes),
			'id' 	=> $blockID === '' ? null : 'ub_styled_list-' . $blockID,
			'style' => Ultimate_Blocks\includes\generate_css_string($list_styles),
		)
	);
    if($list === ''){
		return sprintf(
			'<ul %1$s>%2$s</ul>',
			$wrapper_attributes, //1
			Ultimate_Blocks\includes\strip_xss($contents) //2
		);
    }
    else{
		return sprintf(
			'<div %1$s><ul class="fa-ul">%2$s</ul></div>',
			$wrapper_attributes, //1
			wp_kses_post($listItems) //2
		);
    }

}

function ub_register_styled_list_block() {
	if ( function_exists( 'register_block_type_from_metadata' ) ) {
        require dirname(dirname(__DIR__)) . '/defaults.php';
        register_block_type_from_metadata( dirname(dirname(dirname(__DIR__))) . '/dist/blocks/styled-list/block.json', array(
            'attributes' => $defaultValues['ub/styled-list']['attributes'],

            'render_callback' => 'ub_render_styled_list_block'));
	}
}

function ub_render_styled_list_item_block($attributes, $contents, $block){
    	extract($attributes);
	$block_attributes  = isset($block->parsed_block['attrs']) ? $block->parsed_block['attrs'] : array();

    	$padding 	= Ultimate_Blocks\includes\get_spacing_css( isset($block_attributes['padding']) ? $block_attributes['padding'] : array() );
	$margin 	= Ultimate_Blocks\includes\get_spacing_css( isset($block_attributes['margin']) ? $block_attributes['margin'] : array() );

	// Handle icon rendering based on icon source
	$iconSource = isset($attributes['iconSource']) ? $attributes['iconSource'] : 'fontawesome';
	$customIconSVG = isset($attributes['customIconSVG']) ? $attributes['customIconSVG'] : '';

	$iconHTML = '';
	if ($iconSource === 'custom' && !empty($customIconSVG)) {
		// Use custom SVG - sanitize and render directly
		$sanitized_svg = wp_kses($customIconSVG, array(
			'svg' => array(
				'xmlns' => array(),
				'viewbox' => array(),
				'width' => array(),
				'height' => array(),
				'fill' => array(),
				'stroke' => array(),
				'stroke-width' => array(),
				'class' => array(),
				'aria-hidden' => array(),
				'aria-labelledby' => array(),
				'role' => array(),
			),
			'path' => array(
				'd' => array(),
				'fill' => array(),
				'stroke' => array(),
				'stroke-width' => array(),
				'class' => array(),
			),
			'circle' => array(
				'cx' => array(),
				'cy' => array(),
				'r' => array(),
				'fill' => array(),
				'stroke' => array(),
				'stroke-width' => array(),
				'class' => array(),
			),
			'rect' => array(
				'x' => array(),
				'y' => array(),
				'width' => array(),
				'height' => array(),
				'fill' => array(),
				'stroke' => array(),
				'stroke-width' => array(),
				'class' => array(),
			),
			'line' => array(
				'x1' => array(),
				'y1' => array(),
				'x2' => array(),
				'y2' => array(),
				'stroke' => array(),
				'stroke-width' => array(),
				'class' => array(),
			),
			'polyline' => array(
				'points' => array(),
				'fill' => array(),
				'stroke' => array(),
				'stroke-width' => array(),
				'class' => array(),
			),
			'polygon' => array(
				'points' => array(),
				'fill' => array(),
				'stroke' => array(),
				'stroke-width' => array(),
				'class' => array(),
			),
			'g' => array(
				'fill' => array(),
				'stroke' => array(),
				'stroke-width' => array(),
				'class' => array(),
			),
		));
		$iconHTML = $sanitized_svg;
		$iconData = array(); // Not needed for custom SVG
		$encoded_svg = rawurlencode($sanitized_svg);
		$iconBackgroundImage = 'url(\'data:image/svg+xml,' . $encoded_svg . '\')';
	} else {
		// Use FontAwesome icon
		$iconData = !empty($attributes['selectedIcon']) ? Ultimate_Blocks_IconSet::generate_fontawesome_icon( $attributes['selectedIcon'] ) : array();
		if (!empty($iconData)) {
			$iconHTML = sprintf(
				'<svg width="%1$s" height="%1$s" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %2$s %3$s"><path fill="%4$s" d="%5$s"></path></svg>',
				esc_attr(( ( 4 + $attributes['iconSize'] ) / 10 ) . 'em'),
				esc_attr($iconData[0]),
				esc_attr($iconData[1]),
				esc_attr($attributes['iconColor']),
				esc_attr($iconData[2])
			);
		}
		$iconBackgroundImage = !empty($iconData) ? 'url(\'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $iconData[0] . ' ' . $iconData[1] . '"><path fill="%23' . substr( $attributes['iconColor'], 1 ) . '" d="' . $iconData[2] . '"></path></svg>\')' : '';
	}

	$list_item_styles = array(
		'padding-top'         			=> isset($padding['top']) ? esc_attr($padding['top']) : "",
		'padding-left'        			=> isset($padding['left']) ? esc_attr($padding['left']) : "",
		'padding-right'       			=> isset($padding['right']) ? esc_attr($padding['right']) : "",
		'padding-bottom'      			=> isset($padding['bottom']) ? esc_attr($padding['bottom']) : "",
		'margin-top'       	 			=> !empty($margin['top']) ? esc_attr($margin['top']) . " !important" : "",
		'margin-left'       			=> !empty($margin['left']) ? esc_attr($margin['left']) . " !important" : "",
		'margin-right'      			=> !empty($margin['right']) ? esc_attr($margin['right']) . " !important" : "",
		'margin-bottom'     			=> !empty($margin['bottom']) ? esc_attr($margin['bottom']) . " !important" : "",
		'font-size'					=> $attributes['fontSize'] > 0 ?  ( $attributes['fontSize'] ) . 'px;' : '',
		'color'						=> !empty($attributes['itemTextColor']) ? esc_attr($attributes['itemTextColor']) : '',
		'background-color'			=> !empty($attributes['itemBackgroundColor']) ? esc_attr($attributes['itemBackgroundColor']) : '',
		'--ub-list-item-icon-top' 		=> ( $attributes['iconSize'] >= 5 ? 3 : ( $attributes['iconSize'] < 3 ? 2 : 0 ) ) . 'px',
		'--ub-list-item-icon-size' 		=> ( ( 4 + $attributes['iconSize'] ) / 10 ) . 'em',
		'--ub-list-item-background-image' 	=> $iconBackgroundImage,
	);

	// Typography styles for list item (overrides parent when set)
	if (!empty($attributes['itemFontFamily'])) {
		$list_item_styles['font-family'] = esc_attr($attributes['itemFontFamily']);
	}
	if (!empty($attributes['itemFontSize'])) {
		$list_item_styles['font-size'] = esc_attr($attributes['itemFontSize']);
	}
	if (!empty($attributes['itemFontAppearance']['fontStyle'])) {
		$list_item_styles['font-style'] = esc_attr($attributes['itemFontAppearance']['fontStyle']);
	}
	if (!empty($attributes['itemFontAppearance']['fontWeight'])) {
		$list_item_styles['font-weight'] = esc_attr($attributes['itemFontAppearance']['fontWeight']);
	}
	if (!empty($attributes['itemLineHeight'])) {
		$list_item_styles['line-height'] = esc_attr($attributes['itemLineHeight']);
	}
	if (!empty($attributes['itemLetterSpacing'])) {
		$list_item_styles['letter-spacing'] = esc_attr($attributes['itemLetterSpacing']);
	}
	if (!empty($attributes['itemTextDecoration'])) {
		$list_item_styles['text-decoration'] = esc_attr($attributes['itemTextDecoration']);
	}
	if (!empty($attributes['itemTextTransform'])) {
		$list_item_styles['text-transform'] = esc_attr($attributes['itemTextTransform']);
	}
	return sprintf(
		'<li class="ub_styled_list_item" style="%1$s">
			<div class="ub_list_item_content">
				<span class="ub_list_item_icon">
					%8$s
				</span>
				<span class="ub_list_item_text">%2$s</span>
			</div>
			%3$s
		</li>',
		Ultimate_Blocks\includes\generate_css_string( $list_item_styles ), // 1
		wp_kses_post($itemText), // 2
		Ultimate_Blocks\includes\strip_xss($contents), // 3
		'', // 4 - deprecated
		'', // 5 - deprecated
		'', // 6 - deprecated
		'', // 7 - deprecated
		$iconHTML // 8 - Icon HTML (either custom SVG or FontAwesome)
	);
}

function ub_register_styled_list_item_block(){
    if ( function_exists( 'register_block_type_from_metadata' ) ) {
        require dirname(dirname(__DIR__)) . '/defaults.php';
        register_block_type_from_metadata( dirname(dirname(dirname(__DIR__))) . '/dist/blocks/styled-list/style-list-item/block.json', array(
            'attributes' => $defaultValues['ub/styled-list-item']['attributes'],
            'render_callback' => 'ub_render_styled_list_item_block'));
	}
}

add_action('init', 'ub_register_styled_list_block');
add_action('init', 'ub_register_styled_list_item_block');
