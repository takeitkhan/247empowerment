<?php
function ub_render_advanced_video_block($attributes, $_, $block){
    require_once dirname(dirname(__DIR__)) . '/common.php';
    extract($attributes);
	$block_attrs = $block->parsed_block['attrs'];

    $classes = array( 'ub-advanced-video-container' );
    $ids = array( 'ub-advanced-video-'. esc_attr($blockID) .'' );

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

	$video_embed_styles = array(
		'box-shadow' => (
			$attributes['shadow'][0]['radius'] * cos(deg2rad((450 - $attributes['shadow'][0]['angle']) % 360))
		) . 'px ' .
		(
			-($attributes['shadow'][0]['radius'] * sin(deg2rad((450 - $attributes['shadow'][0]['angle']) % 360)))
		) . 'px ' .
		$attributes['shadow'][0]['blur'] . 'px ' . $attributes['shadow'][0]['spread'] . 'px ' .
		'rgba(' .
		hexdec(substr($attributes['shadow'][0]['color'], 1, 2)) . ', ' .
		hexdec(substr($attributes['shadow'][0]['color'], 3, 2)) . ', ' .
		hexdec(substr($attributes['shadow'][0]['color'], 5, 2)) . ', ' .
		((100 - $attributes['shadow'][0]['transparency']) / 100) .
		')',
	);
	$video_embed_styles['border-top'] = !empty($block_attrs['border']) ? Ultimate_Blocks\includes\get_single_side_border_value(Ultimate_Blocks\includes\get_border_css($block_attrs['border']), 'top') : '';
	$video_embed_styles['border-left'] = !empty($block_attrs['border']) ? Ultimate_Blocks\includes\get_single_side_border_value(Ultimate_Blocks\includes\get_border_css($block_attrs['border']), 'left') : '';
	$video_embed_styles['border-right'] = !empty($block_attrs['border']) ? Ultimate_Blocks\includes\get_single_side_border_value(Ultimate_Blocks\includes\get_border_css($block_attrs['border']), 'right') : '';
	$video_embed_styles['border-bottom'] = !empty($block_attrs['border']) ? Ultimate_Blocks\includes\get_single_side_border_value(Ultimate_Blocks\includes\get_border_css($block_attrs['border']), 'bottom') : '';

	$video_embed_styles['border-top-left-radius'] = !empty($block_attrs['borderRadius']['topLeft']) ? $block_attrs['borderRadius']['topLeft'] : '';
	$video_embed_styles['border-top-right-radius'] = !empty($block_attrs['borderRadius']['topRight']) ? $block_attrs['borderRadius']['topRight'] : '';
	$video_embed_styles['border-bottom-left-radius'] = !empty($block_attrs['borderRadius']['bottomLeft']) ? $block_attrs['borderRadius']['bottomLeft'] : '';
	$video_embed_styles['border-bottom-right-radius'] = !empty($block_attrs['borderRadius']['bottomRight']) ? $block_attrs['borderRadius']['bottomRight'] : '';

	$video_thumbnail_styles = array();
	if (isset($attributes['autofit']) && $attributes['autofit']) {
		$styles['width'] = '100%';
		switch ($attributes['videoSource']) {
			case 'youtube':
				$current_aspect_ratio = !empty($attributes['aspectRatio']) && $attributes['aspectRatio'] !== 'auto' ?  $attributes['aspectRatio'] : $attributes['origWidth'] . '/' . $attributes['origHeight'];
				$video_embed_styles['--ub-advanced-video-aspect-ratio'] = $current_aspect_ratio;
				break;
			case 'vimeo':
				$video_embed_styles['padding-top'] = ( $attributes['origHeight'] / $attributes['origWidth'] * 100 ) . '%';
				break;
			case 'dailymotion':
				$video_embed_styles['padding-bottom'] = ( $attributes['origHeight'] / $attributes['origWidth'] * 100 ) . '%';
				break;
			case 'unknown':
				$local_aspect_ratio = !empty($attributes['aspectRatio']) && $attributes['aspectRatio'] !== 'auto' ?  $attributes['aspectRatio'] : $attributes['origWidth'] . '/' . $attributes['origHeight'];
				$video_embed_styles['--ub-advanced-video-aspect-ratio'] = $local_aspect_ratio;
				break;
			default:
				break;
		}
		$video_thumbnail_styles['aspect-ratio'] = $attributes['origWidth'] . '/' . $attributes['origHeight'];
	}
	if (isset($attributes['autofit']) && !$attributes['autofit']) {
		$video_embed_styles['width'] = $attributes['width'] . '%';
	}

    $block_wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => implode(' ', $classes),
			'id'    => implode(' ', $ids),
			'style' => Ultimate_Blocks\includes\generate_css_string($styles)
		)
    );
    //enclosing div needed to prevent embedded video from trying to use the full height of the screen
	return sprintf(
		'<div %1$s >%2$s<div class="ub-advanced-video-embed%3$s"%4$s style="%7$s">%5$s%6$s</div></div>',
		$block_wrapper_attributes, //1
		(!in_array($videoSource, ['local', 'unknown', 'videopress']) && $thumbnail !== '' ?
			sprintf(
				'<div class="ub-advanced-video-thumbnail" style="height:%1$spx; width:%2$s%%;%5$s">' .
				'<img class="ub-advanced-video-thumbnail-image" height="100%%" width="100%%" src="%3$s">' .
				'<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 60 60" xml:space="preserve" width="%4$s%%">' .
				'<g><path d="M45.563,29.174l-22-15c-0.307-0.208-0.703-0.231-1.031-0.058C22.205,14.289,22,14.629,22,15v30c0,0.371,0.205,0.711,0.533,0.884C22.679,45.962,22.84,46,23,46c0.197,0,0.394-0.059,0.563-0.174l22-15C45.836,30.64,46,30.331,46,30S45.836,29.36,45.563,29.174z M24,43.107V16.893L43.225,30L24,43.107z"/>' .
				'<path d="M30,0C13.458,0,0,13.458,0,30s13.458,30,30,30s30-13.458,30-30S46.542,0,30,0z M30,58C14.561,58,2,45.439,2,30S14.561,2,30,2s28,12.561,28,28S45.439,58,30,58z"/></g>' .
				'</svg></div>',
				esc_attr($height),
				esc_attr($width),
				esc_url($thumbnail),
				$width / 10,
				Ultimate_Blocks\includes\generate_css_string($video_thumbnail_styles)
			) : '' //2
		),
		($autofit && in_array($videoSource, ['youtube', 'vimeo', 'dailymotion']) ? (' ub-advanced-video-autofit-' . esc_attr($videoSource)) : ''), //3
		($thumbnail !== '' && !in_array($videoSource, ['local', 'unknown', 'videopress']) ? ' hidden' : ''), //4
		wp_kses($videoEmbedCode, ub_get_video_embed_kses_tags()), //5
		($autofit && $videoSource === 'vimeo' ? '<script src="https://player.vimeo.com/api/player.js"></script>' : ''), //6
		Ultimate_Blocks\includes\generate_css_string($video_embed_styles) //7
	);
}

function ub_register_advanced_video_block() {
	if ( function_exists( 'register_block_type_from_metadata' ) ) {
        require dirname(dirname(__DIR__)) . '/defaults.php';
		register_block_type_from_metadata( dirname(dirname(dirname(__DIR__))) . '/dist/blocks/advanced-video', array(
            'attributes' => $defaultValues['ub/advanced-video']['attributes'],
			'render_callback' => 'ub_render_advanced_video_block'));
	}
}

function ub_advanced_video_add_frontend_assets() {
    wp_register_script(
		'ultimate_blocks-advanced-video-front-script',
		plugins_url( 'advanced-video/front.build.js', dirname( __FILE__ ) ),
		array( ),
		Ultimate_Blocks_Constants::plugin_version(),
		true
	);
}

add_action( 'wp_enqueue_scripts', 'ub_advanced_video_add_frontend_assets' );

add_action('init', 'ub_register_advanced_video_block');
