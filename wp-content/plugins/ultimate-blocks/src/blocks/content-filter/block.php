<?php
require_once dirname(dirname(dirname(__DIR__))) . '/includes/ultimate-blocks-styles-css-generator.php';

/**
 * Enqueue frontend script for table fo contents block
 *
 * @return void
 */

 function ub_render_content_filter_entry_block($attributes, $content){
    extract($attributes);
	return sprintf(
		'<div class="ub-content-filter-panel%1$s%2$s" data-selectedFilters="%3$s">%4$s</div>',
		isset($className) ? ' ' . esc_attr($className) : '', //1
		$initiallyShow ? '' : ' ub-hide', //2
		esc_attr(json_encode($selectedFilters)), //3
		wp_kses_post($content) //4
	);
}

function ub_register_content_filter_entry_block(){
    if ( function_exists( 'register_block_type_from_metadata' ) ) {
        register_block_type_from_metadata( dirname(dirname(dirname(__DIR__))) . '/dist/blocks/content-filter/components/block.json', array(
            'attributes' => array(
                // UNCOMMENTED OUT, IN JS BLOCK GET UNDEFINED AND BREAKS.
                'availableFilters' => array(
                    'type' => 'array',
                    'default' => array()//get list of filters from parent block
                ),
                'selectedFilters' => array(
                    'type' => 'array',
                    'default' => array()
                ),
                'buttonColor' => array(
                    'type' => 'string',
                    'default' => '#aaaaaa'
                ),
                'buttonTextColor' => array(
                    'type' => 'string',
                    'default' => '#000000'
                ),
                'initiallyShow' => array(
                    'type' => 'boolean',
                    'default' => true
                ),
                'padding'   => array(
                    'type'    => 'array',
                    'default' => array()
                ),
                'margin'   => array(
                    'type'    => 'array',
                    'default' => array()
                ),
            ),
                'render_callback' => 'ub_render_content_filter_entry_block'));
        }
}

function ub_render_content_filter_block($attributes, $content, $block){
    extract($attributes);
    $block_attributes  = isset($block->parsed_block['attrs']) ? $block->parsed_block['attrs'] : array();

    if(!isset($filterArray)){
        $filterArray = array();
    }

    $newFilterArray = json_decode(json_encode($filterArray), true);

    $filterList = '';
	$content_filter_buttons_styles = array(
		'justify-content' => isset($attributes['filterButtonAlignment']) ? $attributes['filterButtonAlignment'] : 'left',
	);
	$content_tags_styles = array(
		'--ub-content-tags-background-color' => isset($attributes['buttonColor']) ? $attributes['buttonColor'] : '#eeeeee',
		'--ub-content-tags-text-color' => isset($attributes['buttonTextColor']) ? $attributes['buttonTextColor'] : '',
		'--ub-content-tags-active-background-color' => isset($attributes['activeButtonColor']) ? $attributes['activeButtonColor'] : '#fcb900',
		'--ub-content-tags-active-text-color' => isset($attributes['activeButtonTextColor']) ? $attributes['activeButtonTextColor'] : '',
	);
    foreach((array)$newFilterArray as $key1 => $filterGroup){
		$filterList .= sprintf(
			'<div class="ub-content-filter-category">
				<div class="ub-content-filter-category-name">%1$s</div>',
			wp_kses_post($filterGroup['category']) //1
		);

		$filters = sprintf(
			'<div class="ub-content-filter-buttons-wrapper" data-canUseMultiple="%1$s" style="%2$s">',
			json_encode($filterGroup['canUseMultiple']), //1
			Ultimate_Blocks\includes\generate_css_string($content_filter_buttons_styles)
		);

		foreach ($filterGroup['filters'] as $key2 => $tag) {
			$filters .= sprintf(
				'<div data-tagIsSelected="false" data-categoryNumber="%1$s" data-filterNumber="%2$s" %3$s class="ub-content-filter-tag" style="%5$s">%4$s</div>',
				$key1, //1
				$key2, //2
				($blockID === '' ? sprintf(
					'data-normalColor="%1$s" data-normalTextColor="%2$s" data-activeColor="%3$s" data-activeTextColor="%4$s" style="background-color: %1$s; color: %2$s"',
					esc_attr($buttonColor), //1
					esc_attr($buttonTextColor), //2
					esc_attr($activeButtonColor), //3
					esc_attr($activeButtonTextColor) //4
				) : ''),
				wp_kses_post($tag), //4
				Ultimate_Blocks\includes\generate_css_string($content_tags_styles) //5
			);
		}

		$filterList .= $filters . '</div>';
		$filterList .= '</div>';
    }

	$currentSelection = array_map(function($category){
		return ($category['canUseMultiple'] ?
				array_fill(0, count($category['filters']), false) :
				-1);
	}, (array)$filterArray);

	$padding = Ultimate_Blocks\includes\get_spacing_css( isset($block_attributes['padding']) ? $block_attributes['padding'] : array() );
	$margin = Ultimate_Blocks\includes\get_spacing_css( isset($block_attributes['margin']) ? $block_attributes['margin'] : array() );

	$contentFilterWrapperStyles = array(
		'padding-top'         => isset($padding['top']) ? $padding['top'] : "",
		'padding-left'        => isset($padding['left']) ? $padding['left'] : "",
		'padding-right'       => isset($padding['right']) ? $padding['right'] : "",
		'padding-bottom'      => isset($padding['bottom']) ? $padding['bottom'] : "",
		'margin-top'         => !empty($margin['top']) ? $margin['top'] . " !important" : "",
		'margin-left'        => !empty($margin['left']) ? $margin['left'] . " !important" : "",
		'margin-right'       => !empty($margin['right']) ? $margin['right'] . " !important" : "",
		'margin-bottom'      => !empty($margin['bottom']) ? $margin['bottom'] . " !important" : "",
	);

	$classes = array();
    $block_attributes = get_block_wrapper_attributes(
            array(
			'class' => implode(" ", $classes),
			'style' => Ultimate_Blocks\includes\generate_css_string($contentFilterWrapperStyles),
			'id' => 'ub-content-filter-block-' . esc_attr($blockID)
            )
    );
return sprintf(
	'<div %1$s%2$s data-currentSelection="%3$s" data-initiallyShowAll="%4$s" data-matchingOption="%5$s">%6$s%7$s</div>',
	$block_attributes, //1
	($blockID === '' ? '' : ' id="ub-content-filter-block' . esc_attr($blockID) . '"'), //2
	json_encode($currentSelection), //3
	json_encode($initiallyShowAll), //4
	esc_attr($matchingOption), //5
	$filterList, //6
	$content //7
);
}

function ub_register_content_filter_block(){
    if ( function_exists( 'register_block_type_from_metadata' ) ) {
        require dirname(dirname(__DIR__)) . '/defaults.php';
        register_block_type_from_metadata( dirname(dirname(dirname(__DIR__))) . '/dist/blocks/content-filter/block.json', array(
            'attributes' => $defaultValues['ub/content-filter-block']['attributes'],
                'render_callback' => 'ub_render_content_filter_block'));

    }

}

function ub_content_filter_add_frontend_assets() {
	wp_register_script(
		'ultimate_blocks-content-filter-front-script',
		plugins_url( 'content-filter/front.build.js', dirname( __FILE__ ) ),
		array( ),
		Ultimate_Blocks_Constants::plugin_version(),
		true
	);
}

add_action( 'wp_enqueue_scripts', 'ub_content_filter_add_frontend_assets' );
add_action('init', 'ub_register_content_filter_entry_block');
add_action('init', 'ub_register_content_filter_block');
