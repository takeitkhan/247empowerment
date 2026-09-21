<?php
/**
 * Click to tweet block.
 */

/**
 * Rendering the block dynamically.
 *
 * @param $attributes
 *
 * @return string
 *
 */
function ub_render_click_to_tweet_block( $attributes, $_, $block ) {
    extract($attributes);
	$via = isset( $attributes['ubVia'] ) ? $attributes['ubVia'] : false;
	$via = ( $via ) ? '&via=' .  mb_strimwidth( preg_replace( '/[^A-Za-z0-9_]/', '', $via ), 0, 15  ): false; //ensure that only valid Twitter usernames appear
    	$tweet = preg_replace('/<br><br>$/', '<br>', $ubTweet);
	$tweet_url  = ( $tweet ) ? rawurlencode( preg_replace('/<.+?>/', '', str_replace("<br>","\n",$tweet) )) : false;
	$block_attrs = $block->parsed_block['attrs'];

    /*$tweetFontSize = isset( $attributes['tweetFontSize'] ) ? "font-size:{$attributes['tweetFontSize']}" : "font-size: 20";
	$tweetColor = isset( $attributes['tweetColor'] ) ? "color:{$attributes['tweetColor']}" : "color: #444444";
    $borderColor = isset( $attributes['borderColor'] ) ? "border-color:{$attributes['borderColor']}" : "border-color: #CCCCCC";
    */

	$permalink = esc_url( get_the_permalink() );
	$url       = apply_filters( 'ub_click_to_tweet_url', "http://twitter.com/intent/tweet?&text={$tweet_url}&url={$permalink}{$via}" );
	$padding = Ultimate_Blocks\includes\get_spacing_css( isset($block_attrs['padding']) ? $block_attrs['padding'] : array() );
	$margin  = Ultimate_Blocks\includes\get_spacing_css( isset($block_attrs['margin']) ? $block_attrs['margin'] : array() );

	$wrapper_styles = array(
		'padding-top'        => isset($padding['top']) ? $padding['top'] : "",
		'padding-left'       => isset($padding['left']) ? $padding['left'] : "",
		'padding-right'      => isset($padding['right']) ? $padding['right'] : "",
		'padding-bottom'     => isset($padding['bottom']) ? $padding['bottom'] : "",
		'margin-top'         => !empty($margin['top']) ? $margin['top']  : "",
		'margin-left'        => !empty($margin['left']) ? $margin['left']  : "",
		'margin-right'       => !empty($margin['right']) ? $margin['right']  : "",
		'margin-bottom'      => !empty($margin['bottom']) ? $margin['bottom']  : "",
		'border-color' 	 => isset($attributes['borderColor']) ? $attributes['borderColor'] : "",
	);

	$output = sprintf(
		'<div class="wp-block-ub-click-to-tweet ub_click_to_tweet%1$s" %2$s style="%3$s">
			<div class="ub_tweet" style="font-size: %4$spx; color: %5$s;">
				%6$s
			</div>
			<div class="ub_click_tweet">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 512 512"><path fill="#171111" d="M389.2 48h70.6L305.6 224.2L487 464H345L233.7 318.6L106.5 464H35.8l164.9-188.5L26.8 48h145.6l100.5 132.9zm-24.8 373.8h39.1L151.1 88h-42z"/></svg>
				<a target="_blank" href="%7$s">' . __( 'Post On X', 'ultimate-blocks' ) . '</a>
			</div>
		</div>',
		(isset($className) ? ' ' . esc_attr($className) : ''), // 1
		($blockID === '' ? '' : 'id="' . esc_attr('ub_click_to_tweet_' . $blockID) . '"'), //2
		Ultimate_Blocks\includes\generate_css_string($wrapper_styles), //3
		esc_attr($tweetFontSize), //4
		esc_attr($tweetColor), //5
		wp_kses_post($tweet), //6
		esc_url($url) //7
	);

	return $output;
}

/**
 * Registering dynamic block.
 */
function ub_register_click_to_tweet_block() {
	if ( function_exists( 'register_block_type_from_metadata' ) ) {
        require dirname(dirname(__DIR__)) . '/defaults.php';
		register_block_type_from_metadata( dirname(dirname(dirname(__DIR__))) . '/dist/blocks/click-to-tweet', array(
            'attributes' => $defaultValues['ub/click-to-tweet']['attributes'],
			'render_callback' => 'ub_render_click_to_tweet_block'));
	}
}

add_action('init', 'ub_register_click_to_tweet_block');
