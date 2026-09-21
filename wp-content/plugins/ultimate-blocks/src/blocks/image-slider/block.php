<?php

use function Ultimate_Blocks\includes\generate_css_string;

/**
 * Enqueue frontend script for content toggle block
 *
 * @return void
 */

function ub_render_image_slider_block($attributes, $_, $block){
    extract($attributes);

    $block_attrs = $block->parsed_block['attrs'];

    $imageArray = isset($pics) ? (count($pics) > 0 ? $pics : json_decode($images, true)) : array();
    $captionArray = isset($descriptions) ? count($descriptions) > 0 ? $descriptions : json_decode($captions, true) : array();

    $gallery = '';
    $sliderHeight = isset($attributes['sliderHeight']) ? $attributes['sliderHeight'] : 200; // Default height
    $swiper_slide_image_styles = array('height' => $sliderHeight . 'px;');

    foreach($imageArray as $key => $image){
		if(!empty($captionArray[$key]['link'])){
			$swiper_slide_image_styles['width'] = "100%";
		}
        $imageTag = sprintf(
            '<img src="%1$s" alt="%2$s" style="%3$s">',
            esc_url($image['url']),
            esc_attr($image['alt']),
            Ultimate_Blocks\includes\generate_css_string($swiper_slide_image_styles)
        );

        // Wrap image with link if link exists
        if (!empty($captionArray[$key]['link'])) {
            $imageTag = sprintf(
                '<a href="%1$s" style="display: block;">%2$s</a>',
                esc_url($captionArray[$key]['link']),
                $imageTag
            );
        }

        $gallery .= sprintf(
            '<figure class="swiper-slide">
                %1$s
                <figcaption class="ub_image_slider_image_caption">%2$s</figcaption>
            </figure>',
            $imageTag,
            wp_kses_post($captionArray[$key]['text'])
        );
    }

    $classes = array('ub_image_slider');
    if (!empty($align)) {
        $classes[] = 'align' . esc_attr($align);
    }
    if (isset($useNavigation) && $useNavigation && isset($attributes['navigationPosition']) && $attributes['navigationPosition'] === 'outside') {
        $classes[] = 'ub-navigation-outside';
    } else {
        $classes[] = 'swiper-container';
    }
	$margin = Ultimate_Blocks\includes\get_spacing_css(isset($block_attrs['margin']) ? $block_attrs['margin'] : array());
	$padding = Ultimate_Blocks\includes\get_spacing_css(isset($block_attrs['padding']) ? $block_attrs['padding'] : array());
	$navigationColor = isset($attributes['navigationColor']) ? $attributes['navigationColor'] : '';
	$activePaginationColor = isset($attributes['activePaginationColor']) ? $attributes['activePaginationColor'] : '';
	$paginationColor = isset($attributes['paginationColor']) ? $attributes['paginationColor'] : '';

	$image_slider_wrapper_styles = array(
		'--swiper-navigation-color'				=> $navigationColor,
		'--swiper-pagination-color'				=> $activePaginationColor,
		'--swiper-inactive-pagination-color'	=> $paginationColor,
		'--swiper-navigation-background-color'	=> Ultimate_Blocks\includes\get_background_color_var($attributes, 'navigationBackgroundColor', 'navigationGradientColor'),
		'padding-top'           			 	=> isset($padding['top']) ? $padding['top'] : "",
		'padding-left'          			 	=> isset($padding['left']) ? $padding['left'] : "",
		'padding-right'         			 	=> isset($padding['right']) ? $padding['right'] : "",
		'padding-bottom'        			 	=> isset($padding['bottom']) ? $padding['bottom'] : "",
		'margin-top'            			 	=> isset($margin['top']) ? $margin['top']  : "",
		'margin-right'          			 	=> isset($margin['left']) ? $margin['left']  : "",
		'margin-bottom'         			 	=> isset($margin['right']) ? $margin['right']  : "",
		'margin-left'           			 	=> isset($margin['bottom']) ? $margin['bottom']  : "",
		'min-height' 							=> (35 + $sliderHeight) . 'px;',
	);

    $wrapper_attributes = get_block_wrapper_attributes(
        array(
            'class' => implode(' ', $classes),
            'style' => Ultimate_Blocks\includes\generate_css_string($image_slider_wrapper_styles),
        )
    );

    $navigationPosition = isset($attributes['navigationPosition']) ? $attributes['navigationPosition'] : 'inside';
    $showNavigationOutside = $useNavigation && $navigationPosition === 'outside';

    $navigationConfig = '';
    $wrapperStart = '';
    $wrapperEnd = '';
    $swiperContainerAttrs = '';

    if ($useNavigation) {
        if ($showNavigationOutside) {
            $navigationConfig = '"navigation": {"nextEl": ".ub-swiper-button-next", "prevEl": ".ub-swiper-button-prev"},';
            // Put wrapper attributes on outer wrapper div
            $wrapperStart = '<div ' . $wrapper_attributes . '><div class="ub-slider-wrapper"><div class="ub-swiper-button-prev swiper-button-prev"></div>';
            $wrapperEnd = '<div class="ub-swiper-button-next swiper-button-next"></div></div></div>';
            // Swiper container needs ub_image_slider class for JS to find it
            $swiperContainerAttrs = 'class="ub_image_slider swiper-container swiper"';
        } else {
            $navigationConfig = '"navigation": {"nextEl": ".swiper-button-next", "prevEl": ".swiper-button-prev"},';
            $swiperContainerAttrs = $wrapper_attributes;
        }
    } else {
        $swiperContainerAttrs = $wrapper_attributes;
    }

    $slider_html = sprintf(
        '%1$s<div %2$s %3$s data-swiper-data=\'{"speed":%4$s,"spaceBetween":%5$s,"slidesPerView":%6$s,"loop":%7$s,"pagination":{"el": %8$s , "type": "%9$s"%10$s},%11$s "keyboard": { "enabled": true }, "effect": "%12$s"%13$s%14$s%15$s%16$s%17$s%18$s}\'>
            <div class="swiper-wrapper">%19$s</div>
            <div class="swiper-pagination"></div>
            %20$s
        </div>%21$s',
        $wrapperStart, // 1 - wrapper start with prev button (if outside)
        $swiperContainerAttrs, // 2 - swiper container attributes
        ($blockID === '' ? 'style="min-height: ' . (25 + (count($imageArray) > 0 ? esc_attr($sliderHeight) : 200)) . 'px;"' : 'id="ub_image_slider_' . esc_attr($blockID) . '"'), // 3
        esc_attr($speed), // 4
        esc_attr($spaceBetween), // 5
        esc_attr($slidesPerView), // 6
        json_encode($wrapsAround), // 7
        ($usePagination ? '".swiper-pagination"' : 'null'), // 8
        esc_attr($paginationType), // 9
        ($paginationType === 'bullets' ? ', "clickable":true' : ''), // 10
        $navigationConfig, // 11
        esc_attr($transition), // 12
        ($transition === 'fade' ? ',"fadeEffect":{"crossFade": true}' : ''), // 13
        ($transition === 'coverflow' ? ',"coverflowEffect":{"slideShadows":' . json_encode($slideShadows) . ', "rotate": ' . esc_attr($rotate) . ', "stretch": ' . esc_attr($stretch) . ', "depth": ' . esc_attr($depth) . ', "modifier": ' . esc_attr($modifier) . '}' : ''), // 14
        ($transition === 'cube' ? ',"cubeEffect":{"slideShadows":' . json_encode($slideShadows) . ', "shadow":' . json_encode($shadow) . ', "shadowOffset":' . esc_attr($shadowOffset) . ', "shadowScale":' . esc_attr($shadowScale) . '}' : ''), // 15
        ($transition === 'flip' ? ', "flipEffect":{"slideShadows":' . json_encode($slideShadows) . ', "limitRotation": ' . json_encode($limitRotation) . '}' : ''), // 16
        ($autoplays ? ',"autoplay":{"delay": '. ($autoplayDuration * 1000) . '}' : ''), // 17
        (!$isDraggable ? ',"simulateTouch":false' : ''), // 18
        $gallery, // 19
        ($useNavigation && !$showNavigationOutside ? '<div class="swiper-button-prev"></div> <div class="swiper-button-next"></div>' : ""), // 20 - navigation inside
        $wrapperEnd // 21 - wrapper end with next button (if outside)
    );
    if (defined( 'ULTIMATE_BLOCKS_PRO_LICENSE' ) && ULTIMATE_BLOCKS_PRO_LICENSE) {
	   $slider_html = apply_filters('ubpro_image_slider_filter', $slider_html, $block);
    }
    return $slider_html;
}


function ub_register_image_slider_block(){
    if ( function_exists( 'register_block_type_from_metadata' ) ) {
        require dirname(dirname(__DIR__)) . '/defaults.php';
        register_block_type_from_metadata(dirname(dirname(dirname(__DIR__))) . '/dist/blocks/image-slider/block.json', array(
            'attributes' => $defaultValues['ub/image-slider']['attributes'],
            'render_callback' => 'ub_render_image_slider_block'));
    }
}

function ub_image_slider_add_frontend_assets() {
	wp_register_script(
		'ultimate_blocks-swiper',
		plugins_url( '/swiper-bundle.js', __FILE__ ),
		array(),
		Ultimate_Blocks_Constants::plugin_version()
	);
	wp_register_script(
		'ultimate_blocks-image-slider-init-script',
		plugins_url( '/front.build.js', __FILE__ ),
		array('ultimate_blocks-swiper'),
		Ultimate_Blocks_Constants::plugin_version(),
		true
	);
}

add_action('init', 'ub_register_image_slider_block');
add_action( 'wp_enqueue_scripts', 'ub_image_slider_add_frontend_assets' );
