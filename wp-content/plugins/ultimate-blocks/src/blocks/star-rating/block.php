<?php

function ub_render_star_rating_block($attributes,$_, $block){
    require_once dirname(dirname(__DIR__)) . '/common.php';

    extract($attributes);
	$block_attrs = $block->parsed_block['attrs'];


    $stars = ub_generateStarDisplay($selectedStars, $starCount, $blockID,
    'none', $starColor, $starColor, "", "ub_star_rating_filter-", $starSize);

	$padding = Ultimate_Blocks\includes\get_spacing_css( isset($block_attrs['padding']) ? $block_attrs['padding'] : array() );
	$margin = Ultimate_Blocks\includes\get_spacing_css( isset($block_attrs['margin']) ? $block_attrs['margin'] : array() );
	$gap = isset($attributes['gap']['all']) ?  Ultimate_Blocks\includes\spacing_preset_css_var($attributes['gap']['all']) : "20px";
	$main_wrapper_styles = array(
		'gap' => $gap,
		'padding-top'        => isset($padding['top']) ? $padding['top']  : "",
		'padding-left'       => isset($padding['left']) ? $padding['left']  : "",
		'padding-right'      => isset($padding['right']) ? $padding['right']  : "",
		'padding-bottom'     => isset($padding['bottom']) ? $padding['bottom']  : "",
		'margin-top'         => !empty($margin['top']) ? $margin['top'] : "",
		'margin-left'        => !empty($margin['left']) ? $margin['left'] : "",
		'margin-right'       => !empty($margin['right']) ? $margin['right'] : "",
		'margin-bottom'      => !empty($margin['bottom']) ? $margin['bottom'] : "",
	);

	$outer_container_styles = array(
		'justify-content' => isset($attributes['starAlign']) && $attributes['starAlign'] === 'center' ? 'center' : ( 'flex-' . ( isset($attributes['starAlign']) && $attributes['starAlign'] === 'left' ? 'start' : 'end' ) )
	);
	$text_styles  = array(
		'text-align' => isset($attributes['reviewTextAlign']) ? $attributes['reviewTextAlign'] : 'left',
		'color' => isset($attributes['reviewTextColor']) ? $attributes['reviewTextColor'] : 'inherit',
		'font-size' => isset($attributes['textFontSize']) ? $attributes['textFontSize'] : ''
	);

    if($blockID === ''){
        $stars = preg_replace_callback('/<svg ([^>]+)>/', function($svgAttributes){
            if(preg_match('/fill=\"([^"]+)\"/', $svgAttributes[1], $matches)){
				if (isset($attributes['starColor'])) {
					return '<svg ' . $svgAttributes[1] . ' style="fill:' . esc_attr($attributes['starColor']) . ';">';
				}
				return '<svg ' . $svgAttributes[1] . ' style="fill:' . $matches[1] . ';">';
            }
            return $svgAttributes[0];
        }, $stars);
    }
	$classes = array( 'ub-star-rating' );
	if( !empty($textPosition) ){
		$classes[] = 'ub-star-rating-text-' . esc_attr($textPosition) ;
	}
	if( !empty($starAlign) ){
		$classes[] = 'ub-star-rating-align-' . esc_attr($starAlign) ;
	}

	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => implode(' ', $classes),
			'id' => 'ub-star-rating-' . $blockID . '',
			'style' => Ultimate_Blocks\includes\generate_css_string($main_wrapper_styles)
		)
	);
	$review_text_html = sprintf(
		 '<div class="ub-review-text" style="%1$s">%2$s</div>',
		Ultimate_Blocks\includes\generate_css_string($text_styles),
		$reviewText === '' || false === $isShowReviewText ? '' : wp_kses_post($reviewText)
	);
	return sprintf(
		'<div %1$s>
			<div class="ub-star-outer-container" style="%2$s">
				<div class="ub-star-inner-container">%3$s</div>
			</div>%4$s
		</div>',
		$wrapper_attributes, //1
		Ultimate_Blocks\includes\generate_css_string($outer_container_styles), //2
		$stars, //3
		$review_text_html //4
	);
}

function ub_register_star_rating_block() {
	if( function_exists( 'register_block_type_from_metadata' ) ) {
        require dirname(dirname(__DIR__)) . '/defaults.php';
		register_block_type_from_metadata( dirname(dirname(dirname(__DIR__))) . '/dist/blocks/star-rating', array(
            'attributes' => $defaultValues['ub/star-rating-block']['attributes'],
            'render_callback' => 'ub_render_star_rating_block'));
    }
}

add_action('init', 'ub_register_star_rating_block');
