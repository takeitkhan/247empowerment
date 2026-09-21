<?php

use function Ultimate_Blocks\includes\generate_css_string;

require_once dirname(dirname(dirname(__DIR__))) . '/includes/ultimate-blocks-styles-css-generator.php';

function ub_render_progress_bar_block($attributes, $block_content, $block){
    extract($attributes);
	$blockName = 'ub_progress-bar';
	$chosenProgressBar = '';
	$block_attrs = isset($block->parsed_block["attrs"]) ? $block->parsed_block["attrs"] : $attributes;
	$is_style_circle = isset($attributes['className']) ? strpos($attributes['className'], "is-style-ub-progress-bar-circle-wrapper") !== false : "";
	$is_style_half_circle = isset($attributes['className']) ? strpos($className, "is-style-ub-progress-bar-half-circle-wrapper") !== false : "";

	$percentage_position = $attributes['percentagePosition'];
	$text_position = isset($attributes['textPosition']) ? $attributes['textPosition'] : 'top';

	$padding = Ultimate_Blocks\includes\get_spacing_css( isset($block_attrs['padding']) ? $block_attrs['padding'] : array() );
	$margin = Ultimate_Blocks\includes\get_spacing_css( isset($block_attrs['margin']) ? $block_attrs['margin'] : array() );

	$wrapper_styles = [
		'padding-top'      => isset($padding['top']) ? esc_attr($padding['top']) : "",
		'padding-right'    => isset($padding['right']) ? esc_attr($padding['right']) : "",
		'padding-bottom'   => isset($padding['bottom']) ? esc_attr($padding['bottom']) : "",
		'padding-left'     => isset($padding['left']) ? esc_attr($padding['left']) : "",
		'margin-top'       => isset($margin['top']) ? esc_attr($margin['top'])  : "",
		'margin-right'     => isset($margin['left']) ? esc_attr($margin['left'])  : "",
		'margin-bottom'    => isset($margin['right']) ? esc_attr($margin['right'])  : "",
		'margin-left'      => isset($margin['bottom']) ? esc_attr($margin['bottom'])  : "",
	];
	$label_styles = array(
		'width' => isset($attributes['percentage']) ? $attributes['percentage'] . '%' : '100%',
		'color' => isset($attributes['labelColor']) ? $attributes['labelColor'] : 'inherit'
	);


	if ($percentage_position === 'inside' && !$is_style_circle && !$is_style_half_circle) {
		$label_styles['font-size'] = isset($block_attrs['barThickness']) ? ($block_attrs['barThickness'] + 5) . '%' : "6%";
	}

	$is_stripe = $attributes['isStripe'];

	$show_number                = isset($attributes['showNumber']) ? $attributes['showNumber'] : true;
	$number_prefix              = isset($attributes['numberPrefix']) ? $attributes['numberPrefix'] : '';
	$number_suffix              = isset($attributes['numberSuffix']) ? $attributes['numberSuffix'] : '%';
	$inside_percentage_class    = $percentage_position === 'inside' ? " ub_progress-bar-label-inside" : '';
	$stripe_style               = $is_stripe ? " ub_progress-bar-stripe" : '';
	$detail_text = sprintf(
		'<div class="ub_progress-bar-text" style="%3$s"><p%1$s>%2$s</p></div>',
		$blockID === '' ? ' style="justify-content: ' . esc_attr($detailAlign) . ';"' : '',
		wp_kses_post($detail),
		generate_css_string(array(
			'color' => isset($descriptionColor) ? $descriptionColor : '',
		))
	);

	$percentage_text = sprintf(
		'<div class="%1$s-label%2$s" style="%3$s"><p>
			<span class="ub-progress-number-prefix">%4$s</span>
			<span class="ub-progress-number-value">%5$s</span>
			<span class="ub-progress-number-suffix">%6$s</span>
		</p></div>',
		$blockName, // 1
		$percentage_position === 'top' ? ' ub_progress-bar-label-top' : '', // 2
		Ultimate_Blocks\includes\generate_css_string($label_styles), // 3
		wp_kses_post($number_prefix), // 4
		wp_kses_post($percentage), // 5
		wp_kses_post($number_suffix) // 6
	);

    if ($text_position === 'bottom') {
        $top_percentage = $show_number && $percentage_position === 'top'
            ? '<div class="ub_progress-detail-wrapper">' . $percentage_text . '</div>'
            : '';
        $bottom_detail = $detail_text;
    } else {
        $top_percentage = $show_number && $percentage_position === 'top'
            ? '<div class="ub_progress-detail-wrapper">' . $detail_text . $percentage_text . '</div>'
            : '<div class="ub_progress-detail-wrapper">' . $detail_text . '</div>';
        $bottom_detail = '';
    }
    $inside_percentage = $show_number && $percentage_position === 'inside'  ?
    '<foreignObject width="100%" height="100%" viewBox="0 0 120 10" x="0" y="0">
        ' . $percentage_text . '
    </foreignObject>' : "";
    $bottom_percentage = $show_number && $percentage_position === 'bottom' ? $percentage_text : "";

    $stripe_element = $is_stripe ?
        '<foreignObject width="100%" height="100%">
			<div class="ub_progress-bar-line-stripe" ></div>
		</foreignObject>' : '';

	$circle_percentage = $show_number ? sprintf(
		'<div class="%1$s-label" style="%5$s">
			<span class="ub-progress-number-prefix">%2$s</span>
			<span class="ub-progress-number-value">%3$s</span>
			<span class="ub-progress-number-suffix">%4$s</span>
		</div>',
		$blockName, // 1
		wp_kses_post($number_prefix), // 2
		wp_kses_post($percentage), // 3
		wp_kses_post($number_suffix), // 4
		Ultimate_Blocks\includes\generate_css_string($label_styles) // 5
	) : '';
    if(!$is_style_circle && !$is_style_half_circle){
		$line_path_styles = array(
			'--ub-progress-bar-filled-dashoffset' => 100 - $percentage . 'px;',
		);
		$line_bar_styles = [
			'border-top-left-radius'      => isset($block_attrs['barBorderRadius']['topLeft']) ? $block_attrs['barBorderRadius']['topLeft'] : '',
			'border-top-right-radius'     => isset($block_attrs['barBorderRadius']['topRight']) ? $block_attrs['barBorderRadius']['topRight'] : '',
			'border-bottom-left-radius'   => isset($block_attrs['barBorderRadius']['bottomLeft']) ? $block_attrs['barBorderRadius']['bottomLeft'] : '',
			'border-bottom-right-radius'  => isset($block_attrs['barBorderRadius']['bottomRight']) ? $block_attrs['barBorderRadius']['bottomRight'] : '',
		];
		$progressBarPath = sprintf('M%1$s,%1$s L%2$s,%1$s', $barThickness / 2, 100 - $barThickness / 2);
		$chosenProgressBar = sprintf(
			'<div class="%1$s-container%2$s%3$s" id="%4$s">
			%5$s
			<svg class="%1$s-line" viewBox="0 0 100 %6$s" style="%14$s" preserveAspectRatio="none">
				<path class="%1$s-line-trail" d="%7$s" stroke="%8$s" stroke-width="%6$s" />
				<path class="%1$s-line-path" d="%7$s" stroke="%9$s" stroke-width="%6$s" style="%13$s"/>
				%10$s
				%11$s
			</svg>
			%12$s
			%15$s
			</div>',
			$blockName, // 1
			$inside_percentage_class, // 2
			$stripe_style, // 3
			esc_attr($blockID), // 4
			$top_percentage, // 5
			esc_attr($barThickness), // 6
			$progressBarPath, // 7
			esc_attr($barBackgroundColor), // 8
			esc_attr($barColor), // 9
			$stripe_element, // 10
			$inside_percentage, // 11
			$bottom_percentage, // 12
			Ultimate_Blocks\includes\generate_css_string($line_path_styles), // 13
			Ultimate_Blocks\includes\generate_css_string($line_bar_styles), // 14
			$bottom_detail // 15
		);
    } else if ($is_style_circle) {
	    	$circleRadius = 50 - ($barThickness + 3) / 2;
		$circlePathLength = $circleRadius * M_PI * 2;
		$strokeArcLength = $circlePathLength * $percentage / 100;

		$stroke_dasharray_initial =   '0px, ' . $circlePathLength . 'px';
		$stroke_linecap = 'round';


		$stroke_dasharray_final =  $strokeArcLength . 'px, ' . $circlePathLength . 'px';
		$ub_progress_bar_container_styles = array(
			'height' => isset($circleSize) ? $circleSize . 'px' : '100px',
			'width' => isset($circleSize) ? $circleSize . 'px' : '100px',
			'float' => isset($detailAlign) && in_array($detailAlign, ['left', 'right']) ? $detailAlign : 'auto',
			'margin' => isset($detailAlign) && in_array($detailAlign, ['left', 'right']) ? '0' : 'auto'
		);
		$ub_progress_bar_circle_trail_styles = array(
			'stroke-dasharray' => $circlePathLength . 'px,' . $circlePathLength . 'px'
		);

		$ub_progress_bar_circle_path_styles = array(
			'--ub-progress-bar-dasharray' => $stroke_dasharray_initial,
			'stroke-linecap' => $stroke_linecap,
			'--ub-progress-bar-filled-dasharray' => $stroke_dasharray_final
		);

		$progressBarPath = sprintf(
			'M 50,50 m 0,%1$s a %2$s,%2$s 0 1 1 0,%3$s a %2$s,%2$s 0 1 1 0,%4$s',
			-$circleRadius, // 1
			$circleRadius, // 2
			2 * $circleRadius, // 3
			2 * -$circleRadius // 4
		);

		$chosenProgressBar = sprintf(
			'<div class="%1$s-container" style="%2$s">
				<svg class="%1$s-circle" height="%3$s" width="%3$s" viewBox="0 0 100 100">
					<path class="%1$s-circle-trail" d="%4$s" stroke="%5$s" stroke-width="%6$s" style="%7$s"/>
					<path class="%1$s-circle-path" d="%4$s" stroke="%8$s" stroke-width="%6$s" stroke-linecap="butt" style="%9$s"/>
				</svg>
				%10$s
			</div>',
			$blockName, // 1
			Ultimate_Blocks\includes\generate_css_string($ub_progress_bar_container_styles), // 2
			esc_attr($circleSize), // 3
			$progressBarPath, // 4
			esc_attr($barBackgroundColor), // 5
			$barThickness + 2, // 6
			Ultimate_Blocks\includes\generate_css_string($ub_progress_bar_circle_trail_styles), // 7
			esc_attr($barColor), // 8
			Ultimate_Blocks\includes\generate_css_string($ub_progress_bar_circle_path_styles), // 9
			$circle_percentage // 10
		);
    } else if ($is_style_half_circle) {
		$halfCircleRadius = 50 - ($barThickness + 2) / 2;
		$halfCirclePathLength = $halfCircleRadius * M_PI;

		$halfCircleStrokeArcLength = ($halfCirclePathLength * $percentage) / 100;
		$halfCircleProgressBarPath = sprintf(
			'M 50,50 m -%1$s,0 a %1$s,%1$s 0 1 1 %2$s,0',
			$halfCircleRadius, // 1
			$halfCircleRadius * 2 // 2
		);
		$stroke_dasharray_initial =   '0px, ' . $halfCirclePathLength . 'px';
		$stroke_linecap = 'round';
		$stroke_dasharray_final =  $halfCircleStrokeArcLength . 'px, ' . $halfCirclePathLength . 'px';
		$half_circle_size = isset($circleSize) ? $circleSize : 100;
		$ub_progress_bar_half_circle_container_styles = array(
			'height' => $half_circle_size . 'px',
			'width' => $half_circle_size . 'px',
			'margin-bottom' => '-' . floor($half_circle_size * 0.4) . 'px',
			'margin-left' => isset($detailAlign) && in_array($detailAlign, ['left', 'right']) ? '0' : 'auto',
			'margin-right' => isset($detailAlign) && in_array($detailAlign, ['left', 'right']) ? '0' : 'auto',
		);
		$ub_progress_bar_half_circle_trail_styles = array(
			'stroke-dasharray' => $halfCirclePathLength . 'px,' . $halfCirclePathLength . 'px'
		);

		$ub_progress_bar_half_circle_path_styles = array(
			'--ub-progress-bar-dasharray' => $stroke_dasharray_initial,
			'stroke-linecap' => $stroke_linecap,
			'--ub-progress-bar-filled-dasharray' => $stroke_dasharray_final
		);
		$chosenProgressBar = sprintf(
			'<div class="%1$s-container" style="%2$s">
			<svg class="%1$s-circle" height="%3$s" width="%3$s" viewBox="0 0 100 100">
				<path class="%1$s-circle-trail" d="%4$s" stroke="%5$s" stroke-width="%6$s" style="%7$s"/>
				<path class="%1$s-circle-path" d="%4$s" stroke="%8$s" stroke-width="%6$s" stroke-linecap="butt" style="%9$s"/>
			</svg>
			%10$s
			</div>',
			$blockName, // 1
			Ultimate_Blocks\includes\generate_css_string($ub_progress_bar_half_circle_container_styles), // 2
			esc_attr($circleSize), // 3
			$halfCircleProgressBarPath, // 4
			esc_attr($barBackgroundColor), // 5
			$barThickness + 2, // 6
			Ultimate_Blocks\includes\generate_css_string($ub_progress_bar_half_circle_trail_styles), // 7
			esc_attr($barColor), // 8
			Ultimate_Blocks\includes\generate_css_string($ub_progress_bar_half_circle_path_styles), // 9
			$circle_percentage // 10
		);
	}
	$classes = array( 'wp-block-ub-progress-bar', 'ub_progress-bar' );
	if(isset($className)){
		$classes[] = $className;
	}
	if(($is_style_circle || $is_style_half_circle) && $isCircleRounded){
		$classes[] = 'rounded-circle';
	}
	if (isset($detailAlign)) {
		$classes[] = 'ub-progress-bar-detail-align-' . $detailAlign;
	}

	$block_wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => implode(' ', $classes),
			'style' => Ultimate_Blocks\includes\generate_css_string($wrapper_styles)
		)
	);

	$is_circle_style = $is_style_circle || $is_style_half_circle;
	$circle_detail_before = $is_circle_style && $text_position !== 'bottom' ? $detail_text : '';
	$circle_detail_after  = $is_circle_style && $text_position === 'bottom'  ? $detail_text : '';

	return sprintf(
		'<div %1$s%2$s>%3$s%4$s%5$s</div>',
		$block_wrapper_attributes, // 1
		$blockID === '' ? '' : ' id="ub-progress-bar-' . esc_attr($blockID) . '"', // 2
		$circle_detail_before, // 3
		$chosenProgressBar, // 4
		$circle_detail_after // 5
	);
}

function ub_register_progress_bar_block() {
	if( function_exists( 'register_block_type_from_metadata' ) ) {
        require dirname(dirname(__DIR__)) . '/defaults.php';
		register_block_type_from_metadata( dirname(dirname(dirname(__DIR__))) . '/dist/blocks/progress-bar', array(
            'attributes' => $defaultValues['ub/progress-bar']['attributes'],
            'render_callback' => 'ub_render_progress_bar_block'));
    }
}

function ub_progress_bar_add_frontend_assets() {
	wp_register_script(
		'ultimate_blocks-progress-bar-front-script',
		plugins_url( 'progress-bar/front.build.js', dirname( __FILE__ ) ),
		array( ),
		Ultimate_Blocks_Constants::plugin_version(),
		true
	);
}

add_action( 'init', 'ub_register_progress_bar_block' );
add_action( 'wp_enqueue_scripts', 'ub_progress_bar_add_frontend_assets' );
