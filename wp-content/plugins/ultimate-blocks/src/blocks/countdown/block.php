<?php

function ub_filter_time_display($timeArray, $largestUnit, $smallestUnit){
    $timeUnits = ["week", "day", "hour", "minute", "second"];
    return array_slice($timeArray, array_search($largestUnit, $timeUnits),
    (array_search($smallestUnit, $timeUnits)-array_search($largestUnit, $timeUnits)+1) );
}

function ub_render_countdown_block($attributes, $_, $block){
    //used to display initial rendering
    extract($attributes);
	$block_attrs = $block->parsed_block['attrs'];
    $timeUnits = ["week", "day", "hour", "minute", "second"];
	$circle_size = isset($circleSize) ? $circleSize . 'px' : '70px';
    $timeLeft = $endDate - time();
    $seconds = $timeLeft % 60;
    $minutes = (($timeLeft - $seconds) % 3600) / 60;

    $hours = ($timeLeft - $minutes * 60 - $seconds) / 3600;

    if(array_search($largestUnit, $timeUnits) < 2 ){
        $hours %= 24;
    }

    $days = ($timeLeft - $hours * 3600 - $minutes * 60 - $seconds) / 86400;

    if($largestUnit === 'week'){
        $days %= 7;
    }

    $weeks = ($timeLeft - $days * 86400 - $hours * 3600 - $minutes * 60 - $seconds) / 604800;

    $defaultFormatValues = ['<span class="ub_countdown_week ub-countdown-digit">' . $weeks . '</span> <span class="ub-countdown-unit">' . __( 'weeks', 'ultimate-blocks' ),
    '</span> <span class="ub_countdown_day ub-countdown-digit">' . $days . '</span> <span class="ub-countdown-unit">' . __('days', 'ultimate-blocks'),
    '</span> <span class="ub_countdown_hour ub-countdown-digit">' . $hours . '</span> <span class="ub-countdown-unit">' . __( 'hours', 'ultimate-blocks' ),
    '</span> <span class="ub_countdown_minute ub-countdown-digit">' . $minutes . '</span> <span class="ub-countdown-unit">' . __( 'minutes', 'ultimate-blocks' ),
    '</span> <span class="ub_countdown_second ub-countdown-digit">' . $seconds . '</span> <span class="ub-countdown-unit">' . __( 'seconds', 'ultimate-blocks' ) . '</span>',];

    $defaultFormat = implode(' ', ub_filter_time_display($defaultFormatValues, $largestUnit, $smallestUnit) );

    if(!function_exists('ub_generateCircle')){
        function ub_generateCircle($label, $value, $limit, $color, $size){
            $circlePath = "M 50,50 m 0,-35 a 35,35 0 1 1 0,70 a 35,35 0 1 1 0,-70";
            $prefix = "ub_countdown_circle_";
            return '<div class="' . $prefix . $label . '" style="width: ' . esc_attr($size) . 'px; height: ' . esc_attr($size) . 'px;">
                        <svg height="' . esc_attr($size) . '" width="' . esc_attr($size) . '" viewBox="0 0 100 100">
                            <path class="' . $prefix . 'trail" d="' . $circlePath . '" stroke-width="3" ></path>
                            <path class="' . $prefix . 'path" d="'.$circlePath.'" stroke="' . esc_attr($color) .
                                '" stroke-width="3" style="stroke-dasharray: ' . $value * 219.911/$limit . 'px, 219.911px;"></path>
                        </svg>
                        <div class="' . $prefix . 'label ub_countdown_' . $label . ' ub-countdown-digit">' . $value . '</div>
                    </div>';
        }
    }

    $circularFormatValues = [ub_generateCircle("week", $weeks, 52, $circleColor, $circleSize),
    ub_generateCircle("day", $days, 7, $circleColor, $circleSize),
    ub_generateCircle("hour", $hours, 24, $circleColor, $circleSize),
    ub_generateCircle("minute", $minutes, 60, $circleColor, $circleSize),
    ub_generateCircle("second", $seconds, 60, $circleColor, $circleSize)];

    $circularFormatLabels =  [ '<p class="ub-countdown-unit">'.__( 'Weeks', 'ultimate-blocks' ).'</p>',
    '<p class="ub-countdown-unit">'.__( 'Days', 'ultimate-blocks' ).'</p>',
    '<p class="ub-countdown-unit">'.__( 'Hours', 'ultimate-blocks' ).'</p>',
    '<p class="ub-countdown-unit">'.__( 'Minutes', 'ultimate-blocks' ).'</p>',
    '<p class="ub-countdown-unit">'.__( 'Seconds', 'ultimate-blocks' ).'</p>'];

	$circularFormatStyles = array(
		'grid-template-columns' => implode(' ', array_fill(0, array_search($attributes['smallestUnit'], $timeUnits) - array_search($attributes['largestUnit'], $timeUnits) + 1, '1fr')) . ';',
	);

	$circularFormat = sprintf(
		'<div class="ub_countdown_circular_container" style="%1$s">%2$s%3$s</div>',
		Ultimate_Blocks\includes\generate_css_string($circularFormatStyles),
		implode('', ub_filter_time_display($circularFormatValues, $largestUnit, $smallestUnit)),
		implode('', ub_filter_time_display($circularFormatLabels, $largestUnit, $smallestUnit))
	);

    if(!function_exists('ub_generateDigitArray')){
        function ub_generateDigitArray($value, $maxValue = 0){
            $digits = [];

            while($value > 0){
                $digits[] = $value % 10;
                $value  = ((int) ($value/10));
            }

            $missingDigits = ($maxValue ? floor(log10($maxValue)) + 1 : 1) - count($digits);

            $digits = array_merge( ( $missingDigits > 0 ?  array_fill(0, $missingDigits, 0) : []),
                                 array_reverse($digits));

            return array_map(function($digit){
                return '<div class="ub-countdown-odometer-digit">' . $digit . '</div>';
            }, $digits);
        }
    }

    $odometerValues = ['<div class="ub-countdown-odometer ub-countdown-digit-container ub_countdown_week ub-countdown-digit">' . implode(ub_generateDigitArray($weeks)) .'</div>',
        '<div class="ub-countdown-odometer ub-countdown-digit-container ub_countdown_day ub-countdown-digit">' . implode(ub_generateDigitArray($days, $largestUnit === 'day' ? 0 : 6) ) . '</div>',
        '<div class="ub-countdown-odometer ub-countdown-digit-container ub_countdown_hour ub-countdown-digit">' . implode(ub_generateDigitArray($hours, $largestUnit === 'hour' ? 0 : 23) )  . '</div>',
        '<div class="ub-countdown-odometer ub-countdown-digit-container ub_countdown_minute ub-countdown-digit">' . implode(ub_generateDigitArray($minutes, 59) ) . '</div>',
        '<div class="ub-countdown-odometer ub-countdown-digit-container ub_countdown_second ub-countdown-digit">' . implode(ub_generateDigitArray($seconds, 59) ). '</div>'];

    $odometerLabels = ['<span class="ub-countdown-unit">'.__( 'Weeks', 'ultimate-blocks' ).'</span>',
        '<span class="ub-countdown-unit">'.__( 'Days', 'ultimate-blocks' ).'</span>',
        '<span class="ub-countdown-unit">'.__( 'Hours', 'ultimate-blocks' ).'</span>',
        '<span class="ub-countdown-unit">'.__( 'Minutes', 'ultimate-blocks' ).'</span>',
        '<span class="ub-countdown-unit">'.__( 'Seconds', 'ultimate-blocks' ).'</span>'];

	$odometerStyles = array(
		'grid-template-columns' => implode(' auto ', array_fill(0, array_search($attributes['smallestUnit'], $timeUnits) - array_search($attributes['largestUnit'], $timeUnits) + 1, '1fr')) . ';'
	);
	$odometerFormat = sprintf(
		'<div class="ub-countdown-odometer-container" style="%1$s">%2$s%3$s</div>',
		Ultimate_Blocks\includes\generate_css_string($odometerStyles), //1
		implode('<span></span>', ub_filter_time_display($odometerLabels, $largestUnit, $smallestUnit)), // 2
		implode('<span class="ub-countdown-separator">:</span>', ub_filter_time_display($odometerValues, $largestUnit, $smallestUnit)) //3
	);

    $selctedFormat = $defaultFormat;

    if($style === 'Regular'){
        $selectedFormat = $defaultFormat;
    }
    elseif ($style === 'Circular') {
        $selectedFormat = $circularFormat;
    }
    else{
        $selectedFormat = $odometerFormat;
    }
    $unit_color = isset($attributes['unitColor']) ? $attributes['unitColor'] : '';
    $countdown_color = isset($attributes['countdownColor']) ? $attributes['countdownColor'] : '';

    $padding = Ultimate_Blocks\includes\get_spacing_css( isset($block_attrs['padding']) ? $block_attrs['padding'] : array() );
    $margin = Ultimate_Blocks\includes\get_spacing_css( isset($block_attrs['margin']) ? $block_attrs['margin'] : array() );

    $styles = array(
        "--ub-countdown-unit-color" => $unit_color,
        "--ub-countdown-digit-color" => $countdown_color,
        'padding-top'        => isset($padding['top']) ? $padding['top'] : "",
        'padding-left'       => isset($padding['left']) ? $padding['left'] : "",
        'padding-right'      => isset($padding['right']) ? $padding['right'] : "",
        'padding-bottom'     => isset($padding['bottom']) ? $padding['bottom'] : "",
        'margin-top'         => !empty($margin['top']) ? $margin['top']  : "",
        'margin-left'        => !empty($margin['left']) ? $margin['left']  : "",
        'margin-right'       => !empty($margin['right']) ? $margin['right']  : "",
        'margin-bottom'      => !empty($margin['bottom']) ? $margin['bottom']  : "",
        'text-align' 		 => isset($attributes['messageAlign']) ? $attributes['messageAlign']  : '',
    );

    $pro_data_attributes = apply_filters(
        'ubpro_countdown_data_attributes',
        array(),
        $attributes,
        $block
    );
    $pro_attribute_string = '';
    if ( is_array( $pro_data_attributes ) && ! empty( $pro_data_attributes ) ) {
        foreach ( $pro_data_attributes as $attr => $value ) {
            if ( '' === $value || null === $value ) {
                continue;
            }

            $pro_attribute_string .= sprintf( ' %1$s="%2$s"', esc_attr( $attr ), esc_attr( $value ) );
        }
    }

    if($timeLeft > 0){
        return sprintf(
            '<div style="%1$s" %2$s%9$s class="wp-block-ub-countdown ub-countdown ub-countdown-wrapper%3$s" data-expirymessage="%4$s" data-enddate="%5$s" data-largestUnit="%6$s" data-smallestunit="%7$s">%8$s</div>',
            Ultimate_Blocks\includes\generate_css_string($styles),
            ($blockID === '' ? '' : 'id="ub_countdown_' . esc_attr($blockID) . '"'),
            (isset($className) ? ' ' . esc_attr($className) : ''),
            esc_attr($expiryMessage),
            esc_attr($endDate),
            esc_attr($largestUnit),
            esc_attr($smallestUnit),
            $selectedFormat,
            $pro_attribute_string
        );
        }
    else {
        return sprintf(
            '<div class="wp-block-ub-countdown ub-countdown %1$s" %2$s%4$s>%3$s</div>',
            (isset($className) ? ' ' . esc_attr($className) : ''),
            ($blockID === '' ? 'style="text-align:' . esc_attr($messageAlign) . ';"' : 'id="ub_countdown_' . esc_attr($blockID) . '"'),
            wp_kses_post($expiryMessage),
            $pro_attribute_string
        );
    }
}

function ub_register_countdown_block() {
	if( function_exists( 'register_block_type_from_metadata' ) ) {
        require dirname(dirname(__DIR__)) . '/defaults.php';
		register_block_type_from_metadata( dirname(dirname(dirname(__DIR__))) . '/dist/blocks/countdown', array(
            'attributes' => $defaultValues['ub/countdown']['attributes'],
            'render_callback' => 'ub_render_countdown_block'));
    }
}

add_action( 'init', 'ub_register_countdown_block' );

function ub_countdown_add_frontend_assets() {
    wp_register_script(
		'ultimate_blocks-countdown-script',
		plugins_url( 'countdown/front.build.js', dirname( __FILE__ ) ),
		array(  ),
		Ultimate_Blocks_Constants::plugin_version(),
		true
	);
}

add_action( 'wp_enqueue_scripts', 'ub_countdown_add_frontend_assets' );
