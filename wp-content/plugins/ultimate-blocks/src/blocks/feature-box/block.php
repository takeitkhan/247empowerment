<?php

function ub_render_feature_box_block($attributes){
    extract($attributes);

	$column1 = sprintf(
		'<div class="ub_feature_1">
		<img class="ub_feature_one_img" src="%1$s" alt="%2$s"/>
		<p class="ub_feature_one_title" style="text-align: %3$s;">%4$s</p>
		<p class="ub_feature_one_body"%5$s>%6$s</p></div>',
		esc_url($imgOneURL),
		esc_attr($imgOneAlt),
		esc_attr($attributes['title1Align']),
		wp_kses_post($columnOneTitle),
		' style="text-align: ' . esc_attr($body1Align) . ';"',
		wp_kses_post($columnOneBody)
	);

	$column2 = sprintf(
		'<div class="ub_feature_2">
		<img class="ub_feature_two_img" src="%1$s" alt="%2$s"/>
		<p class="ub_feature_two_title"%3$s>%4$s</p>
		<p class="ub_feature_two_body"%5$s>%6$s</p></div>',
		esc_url($imgTwoURL),
		esc_attr($imgTwoAlt),
		' style="text-align: ' . esc_attr($title2Align) . ';"',
		wp_kses_post($columnTwoTitle),
		' style="text-align: ' . esc_attr($body2Align) . ';"',
		wp_kses_post($columnTwoBody)
	);

	$column3 = sprintf(
		'<div class="ub_feature_3">
		<img class="ub_feature_three_img" src="%1$s" alt="%2$s"/>
		<p class="ub_feature_three_title"%3$s>%4$s</p>
		<p class="ub_feature_three_body"%5$s>%6$s</p></div>',
		esc_url($imgThreeURL),
		esc_attr($imgThreeAlt),
		' style="text-align: ' . esc_attr($title3Align) . ';"',
		wp_kses_post($columnThreeTitle),
		' style="text-align: ' . esc_attr($body3Align) . ';"',
		wp_kses_post($columnThreeBody)
	);

	$columns = $column1;

	if ((int)$column >= 2) {
		$columns .= $column2;
	}
	if ((int)$column >= 3) {
		$columns .= $column3;
	}

	return sprintf(
		'<div class="ub_feature_box column_%1$s%2$s"%3$s>%4$s</div>',
		esc_attr($column),
		isset($className) ? ' ' . esc_attr($className) : '',
		$blockID === '' ? '' : ' id="ub_feature_box_' . esc_attr($blockID) . '"',
		$columns
	);
}

function ub_register_feature_box_block() {
	if ( function_exists( 'register_block_type_from_metadata' ) ) {
        require dirname(dirname(__DIR__)) . '/defaults.php';
        register_block_type_from_metadata( dirname(dirname(dirname(__DIR__))) . '/dist/blocks/feature-box', array(
            'attributes' => $defaultValues['ub/feature-box-block']['attributes'],
			'render_callback' => 'ub_render_feature_box_block'));
	}
}

add_action('init', 'ub_register_feature_box_block');
