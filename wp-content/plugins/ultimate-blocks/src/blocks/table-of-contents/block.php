<?php

function ub_render_table_of_contents_block($attributes, $_, $block){
    extract($attributes);
	$block_attrs = $block->parsed_block['attrs'];
	 if(isset($block_attrs['enableSmoothScroll']) && $block_attrs['enableSmoothScroll']
		&& !wp_script_is('ultimate_blocks-scrollby-polyfill', 'queue')){
		wp_enqueue_script(
		'ultimate_blocks-scrollby-polyfill',
		plugins_url( 'scrollby-polyfill.js', dirname( __FILE__ ) ),
		array(),
		Ultimate_Blocks_Constants::plugin_version(),
		true
		);
	}
    $linkArray = json_decode($links, true);
	$linkArray = is_null($linkArray) ? [] : $linkArray;
	$is_link_to_divider = isset($linkToDivider) && $linkToDivider;

	$filteredHeaders = $linkArray ? (array_values(array_filter($linkArray, function ($header) use ($allowedHeaders){
        return $allowedHeaders[$header['level'] - 1] &&
           (!array_key_exists("disabled",  $header) || (array_key_exists("disabled",  $header) && !$header['disabled']));
        }))) : [];

    if(!isset($gaps) || is_null($gaps)){
        $gaps = [];
    }

    $currentGaps = array_values(array_filter($gaps, function($gap, $index) use($allowedHeaders, $linkArray){
        return $allowedHeaders[$linkArray[$index]['level'] - 1] && (!array_key_exists("disabled",  $linkArray[$index]) || (array_key_exists("disabled", $linkArray[$index]) && !$linkArray[$index]['disabled']));
    }, ARRAY_FILTER_USE_BOTH));

    $sortedHeaders = [];

    foreach($filteredHeaders as $elem){
        $elem['content'] = trim(preg_replace('/(<.+?>)/', '', $elem['content']));
        $last = count($sortedHeaders) - 1;
        if (count($sortedHeaders) === 0 || $sortedHeaders[$last][0]['level'] < $elem['level']) {
            array_push($sortedHeaders, [$elem]);
        }
        else if ($sortedHeaders[$last][0]['level'] === $elem['level']){
            array_push($sortedHeaders[$last], $elem);
        }
        else{
            while($sortedHeaders[$last][0]['level'] > $elem['level'] && count($sortedHeaders) > 1){
                array_push($sortedHeaders[count($sortedHeaders) - 2], array_pop($sortedHeaders));
                $last = count($sortedHeaders) - 1;
            }
            if($sortedHeaders[$last][0]['level'] === $elem['level']){
                array_push($sortedHeaders[$last], $elem);
            }
        }
    }

    if(count($sortedHeaders) > 0){
        while(count($sortedHeaders) > 1 &&
            $sortedHeaders[count($sortedHeaders) - 1][0]['level'] > $sortedHeaders[count($sortedHeaders) - 2][0]['level']){
            array_push($sortedHeaders[count($sortedHeaders) - 2], array_pop($sortedHeaders));
        }
        $sortedHeaders = $sortedHeaders[0];
    }

    $listItems = '';

    if (!function_exists('ub_makeListItem')) {
		function ub_makeListItem($num, $item, $listStyle, $blockID, $currentGaps, $attributes){
            static $outputString = '';
            if($num === 0 && $outputString !== ''){
                $outputString = '';
            }

            if (isset($item['level'])){
                //intercept otter  headings here
                if(strpos($item["anchor"], "themeisle-otter ") === 0){
                    $anchor = '#' . str_replace("themeisle-otter ", "", $item["anchor"]);
                }
                else{
                    if(isset($item['blockName']) && 'ub/advanced-heading' === $item['blockName']){
                        $anchor = '#ub-advanced-heading-' . $item["clientId"];
                    } else {
                        $anchor = '#' . $item["anchor"];
                    }
                }

                if(count($currentGaps) > $num && get_query_var('page') !== $currentGaps[$num]){
                    $baseURL = get_permalink();
                    $anchor = $baseURL . ($currentGaps[$num] > 1 ? (get_post_status(get_the_ID()) === 'publish' ? '' : '&page=')
                            . $currentGaps[$num] : '') . $anchor;
                }

                $content = array_key_exists("customContent", $item) && !empty($item["customContent"]) ? $item["customContent"] : $item["content"];
				$list_item_styles = array();
				if (isset($attributes['listIconColor'])) {
					$list_item_styles['color'] = $attributes['listIconColor'];
				}
				$list_item_anchor_styles = array();
				if (isset($attributes['listColor'])) {
					$list_item_anchor_styles['color'] = $attributes['listColor'];
				}
				$outputString .= sprintf(
					'<li style="%4$s"><a href="%1$s" style="%3$s">%2$s</a></li>',
					esc_attr($anchor),
					esc_html($content),
					Ultimate_Blocks\includes\generate_css_string($list_item_anchor_styles),
					Ultimate_Blocks\includes\generate_css_string($list_item_styles)
				);
            }
            else{
                $openingTag = $listStyle === 'numbered' ? '<ol>' :
                    '<ul'.($listStyle === 'plain' && $blockID === '' ? ' style="list-style: none;"' : '').'>';

                $outputString = substr_replace($outputString, $openingTag,
                    strrpos($outputString, '</li>'), strlen('</li>'));

                forEach($item as $key => $subItem){
					ub_makeListItem($key + 1, $subItem, $listStyle, $blockID, $currentGaps, $attributes);
                }
                $outputString .= ($listStyle === 'numbered' ? '</ol>' : '</ul>') . '</li>';
            }
            return $outputString;
        }
    }

    if(count($sortedHeaders) > 0){
        foreach($sortedHeaders as $key => $item){
			$listItems = ub_makeListItem($key, $item, $listStyle, $blockID, $currentGaps, $attributes);
        }
    }

    $targetType = '';
    if ($scrollTargetType === 'id'){
        $targetType = '#';
    }
    else if ($scrollTargetType === 'class'){
        $targetType = '.';
    }
	$classes                  = array( 'wp-block-ub-table-of-contents-block', 'ub_table-of-contents' );
	if(!$showList){
		$classes[] = 'ub_table-of-contents-collapsed';
	}

	$padding = Ultimate_Blocks\includes\get_spacing_css( isset($block_attrs['padding']) ? $block_attrs['padding'] : array() );
	$margin  = Ultimate_Blocks\includes\get_spacing_css( isset($block_attrs['margin']) ? $block_attrs['margin'] : array() );

	$styles = array(
			'padding-top'        => isset($padding['top']) ? $padding['top'] : "",
			'padding-left'       => isset($padding['left']) ? $padding['left'] : "",
			'padding-right'      => isset($padding['right']) ? $padding['right'] : "",
			'padding-bottom'     => isset($padding['bottom']) ? $padding['bottom'] : "",
			'margin-top'         => !empty($margin['top']) ? $margin['top']  : "",
			'margin-left'        => !empty($margin['left']) ? $margin['left']  : "",
			'margin-right'       => !empty($margin['right']) ? $margin['right']  : "",
			'margin-bottom'      => !empty($margin['bottom']) ? $margin['bottom']  : "",
	);
	// Add border styles if set
	if (!empty($block_attrs['border'])) {
		$border_css = Ultimate_Blocks\includes\get_border_css($block_attrs['border']);
		$styles['border-top'] = Ultimate_Blocks\includes\get_single_side_border_value($border_css, 'top');
		$styles['border-left'] = Ultimate_Blocks\includes\get_single_side_border_value($border_css, 'left');
		$styles['border-right'] = Ultimate_Blocks\includes\get_single_side_border_value($border_css, 'right');
		$styles['border-bottom'] = Ultimate_Blocks\includes\get_single_side_border_value($border_css, 'bottom');
	}

	// Add border radius styles if set
	if (!empty($block_attrs['borderRadius'])) {
		$styles['border-top-left-radius'] = !empty($block_attrs['borderRadius']['topLeft']) ? $block_attrs['borderRadius']['topLeft'] : '';
		$styles['border-top-right-radius'] = !empty($block_attrs['borderRadius']['topRight']) ? $block_attrs['borderRadius']['topRight'] : '';
		$styles['border-bottom-left-radius'] = !empty($block_attrs['borderRadius']['bottomLeft']) ? $block_attrs['borderRadius']['bottomLeft'] : '';
		$styles['border-bottom-right-radius'] = !empty($block_attrs['borderRadius']['bottomRight']) ? $block_attrs['borderRadius']['bottomRight'] : '';
	}
	if ($attributes['allowToCHiding']) {
		$styles['max-width'] = 'fit-content';
		$styles['max-width'] = '-moz-fit-content';
	}
	$ubpro_classes = apply_filters('ubpro_table_of_contents_classes', array(), $attributes);
	$ubpro_styles = apply_filters('ubpro_table_of_contents_styles', array(), $attributes);

	// Merge ubpro classes and styles with existing values
	$classes = array_merge($classes, isset($ubpro_classes) ? $ubpro_classes : array());
	$styles = array_merge($styles, isset($ubpro_styles) ? $ubpro_styles : array());
	$block_wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => implode( ' ', $classes ),
			'id'    => $blockID === '' ? '' : 'ub_table-of-contents-' . $blockID . '',
			'style' => Ultimate_Blocks\includes\generate_css_string( $styles ),
			'data-linktodivider' => $is_link_to_divider ? "true" : "false",
			'data-showtext' => $showText ?: __('show', 'ultimate-blocks'),
			'data-hidetext' => $hideText ?: __('hide', 'ultimate-blocks'),
			'data-scrolltype' => $scrollOption,
			'data-enablesmoothscroll' => isset($attributes['enableSmoothScroll']) && $attributes['enableSmoothScroll'] ? "true" : "false",
		)
	);
	$toggle_link_styles = array();
	$toggle_styles = array();

	if (isset($attributes['titleBackgroundColor'])) {
		$toggle_link_styles['background-color'] = $attributes['titleBackgroundColor'];
	}
	if (isset($attributes['titleColor'])) {
		$toggle_link_styles['color'] = $attributes['titleColor'];
		$toggle_styles['color'] = $attributes['titleColor'];
	}

	$headerToggle = $allowToCHiding ? sprintf(
		'<div class="ub_table-of-contents-header-toggle">
			<div class="ub_table-of-contents-toggle" style="%3$s">
			&nbsp;[<a class="ub_table-of-contents-toggle-link" href="#" style="%2$s">%1$s</a>]
			</div>
		</div>',
		$showList ? ($hideText ?: __('hide', 'ultimate-blocks')) : ($showText ?: __('show', 'ultimate-blocks')),
		Ultimate_Blocks\includes\generate_css_string($toggle_link_styles),
		Ultimate_Blocks\includes\generate_css_string($toggle_styles)
	) : '';
	$headerToggle = apply_filters('ubpro_table_of_contents_header_toggle', $headerToggle, $attributes);

	$contents_header_styles = array(
		'text-align' => isset($attributes['titleAlignment']) ? $attributes['titleAlignment'] : 'left',
	);
	if ($attributes['allowToCHiding']) {
		$contents_header_styles['margin-bottom'] = '0';
	}
	$header_container_styles = array(
		'background-color' => isset($attributes['titleBackgroundColor']) ? $attributes['titleBackgroundColor'] : '',
		'color' => isset($attributes['titleColor']) ? $attributes['titleColor'] : '',
	);
	$title_styles = array(
		'font-size' => !empty($attributes['titleFontSize']) ? esc_attr($attributes['titleFontSize']) : '',
		'color'     => isset($attributes['titleColor']) ? $attributes['titleColor'] : '',
	);
	$headerContainer = sprintf(
		'<div class="ub_table-of-contents-header-container" style="%4$s">
			<div class="ub_table-of-contents-header" style="%3$s">
				<div class="ub_table-of-contents-title" style="%5$s">%1$s</div>
				%2$s
			</div>
		</div>',
		wp_kses_post($title),
		$headerToggle,
		Ultimate_Blocks\includes\generate_css_string($contents_header_styles),
		Ultimate_Blocks\includes\generate_css_string($header_container_styles),
		Ultimate_Blocks\includes\generate_css_string($title_styles)
	);
	$list_styles = array();
	$item_spacing = isset($attributes['itemSpacing']) ? $attributes['itemSpacing'] : array();
	if ( ! empty( $item_spacing ) ) {
		$item_spacing_css = Ultimate_Blocks\includes\get_spacing_css( $item_spacing );
		if ( ! empty( $item_spacing_css['top'] ) ) {
			$list_styles['--ub-toc-item-spacing'] = $item_spacing_css['top'];
		}
	}
	if ($attributes['listStyle'] === 'plain') {
		$list_styles['list-style'] = 'none';
	}
	$listOpeningTag = sprintf(
		'<%1$s style="%2$s">',
		$listStyle === 'numbered' ? 'ol' : 'ul',
		Ultimate_Blocks\includes\generate_css_string($list_styles)
	);
	$list_container_styles = array(
		'background-color' => isset($attributes['listBackgroundColor']) ? $attributes['listBackgroundColor'] : '',
		'font-size'        => !empty($attributes['listFontSize']) ? esc_attr($attributes['listFontSize']) : '',
	);
	$listContainer = sprintf(
		'<div class="ub_table-of-contents-extra-container" style="%6$s">
			<div class="ub_table-of-contents-container ub_table-of-contents-%1$s-column %2$s">
				%3$s%4$s%5$s
			</div>
		</div>',
		esc_attr($numColumns),
		$showList ? '' : 'ub-hide',
		$listOpeningTag,
		$listItems,
		$listStyle === 'numbered' ? '</ol>' : '</ul>',
		Ultimate_Blocks\includes\generate_css_string($list_container_styles)
	);
	$sticky_data = apply_filters('ubpro_table_of_contents_sticky_data', "", $attributes);

	return sprintf(
		'<div %8$s %1$s %2$s %3$s data-initiallyhideonmobile="%4$s" data-initiallyshow="%5$s">%6$s%7$s</div>',
		$block_wrapper_attributes,
		$scrollOption === 'fixedamount' ? 'data-scrollamount="' . esc_attr($scrollOffset) . '"' : '',
		$scrollOption === 'namedelement' ? 'data-scrolltarget="' . $targetType . esc_attr($scrollTarget) . '"' : '',
		json_encode($hideOnMobile),
		json_encode($showList),
		$headerContainer,
		$listContainer,
		$sticky_data
	);
}

function ub_register_table_of_contents_block() {
	if( function_exists( 'register_block_type_from_metadata' ) ) {
        require dirname(dirname(__DIR__)) . '/defaults.php';
		register_block_type_from_metadata( dirname(dirname(dirname(__DIR__))) . '/dist/blocks/table-of-contents/block.json', array(
            'attributes' => $defaultValues['ub/table-of-contents-block']['attributes'],
            'render_callback' => 'ub_render_table_of_contents_block'));
    }
}

function ub_table_of_contents_add_frontend_assets() {
	wp_register_script(
		'ultimate_blocks-table-of-contents-front-script',
		plugins_url( 'table-of-contents/front.build.js', dirname( __FILE__ ) ),
		array( ),
		Ultimate_Blocks_Constants::plugin_version(),
		true
	);
}

add_action('init', 'ub_register_table_of_contents_block');
add_action( 'wp_enqueue_scripts', 'ub_table_of_contents_add_frontend_assets' );
