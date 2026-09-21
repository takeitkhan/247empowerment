<?php
/**
 * Socialize your content with Social Share Block.
 *
 * @package SocialShareBlock
 */

/**
 * Include icons.
 */
require_once 'icons/icons.php';

/**
 * Renders from server side.
 *
 * @param array $attributes The block attributes.
 */
function ub_render_social_share_block( $attributes, $_, $block ) {
    extract($attributes);
	$icon_sizes = array(
		'normal' => 20,
		'medium' => 30,
		'large'  => 40,
	);
	$block_attrs = $block->parsed_block['attrs'];

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

    $icon_size  = $icon_sizes[ $iconSize ];
    $additionalStyle =  ' style="width:' . ( $icon_size * 1.5 ) . 'px;height:' . ( $icon_size * 1.5 ) . 'px;"';

	$iconDetails = array(
		'facebook' => ub_get_facebook_icon( $attributes, $icon_size, $iconShape, $useCaptions ? $facebookCaption : '', $addOutline && $useCaptions ),
		'twitter' => ub_get_twitter_icon( $attributes, $icon_size, $iconShape, $useCaptions ? $twitterCaption : '', $addOutline && $useCaptions ),
		'linkedin' => ub_get_linkedin_icon( $attributes, $icon_size, $iconShape, $useCaptions ? $linkedInCaption : '', $addOutline && $useCaptions ),
		'pinterest' => ub_get_pinterest_icon( $attributes, $icon_size, $iconShape, $useCaptions ? $pinterestCaption : '', $addOutline && $useCaptions ),
		'reddit' => ub_get_reddit_icon( $attributes, $icon_size, $iconShape, $useCaptions ? $redditCaption : '', $addOutline && $useCaptions ),
		'tumblr' => ub_get_tumblr_icon( $attributes, $icon_size, $iconShape, $useCaptions ? $tumblrCaption : '', $addOutline && $useCaptions)
	);

	$icons = '';

	foreach($iconOrder as $icon){
		$icons .= $iconDetails[$icon];
	}

	$classes = array( 'wp-block-ub-social-share' );
	if(isset($className)) {
		$classes[] = $className;
	}
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' =>  implode(' ', $classes),
			'id'    => 'ub-social-share-' . $blockID,
			'style' => Ultimate_Blocks\includes\generate_css_string($styles),
		)
	);
	if($blockID === ''){
		$icons = str_replace('"><svg', '"' . $additionalStyle . '><svg', $icons);
	}

	return sprintf(
		'<div %1$s>
			<div class="social-share-icons align-icons-%2$s orientation-icons-%3$s%4$s">%5$s</div>
		</div>',
		$wrapper_attributes,
		esc_attr($align),
		esc_attr($orientation),
		$useCaptions && !$addOutline ? ' no-outline' : '',
		$icons
	);
}

/**
 * Generate Facebook Icons.
 *
 * @param  array   $attributes Options of the block.
 * @param  integer $icon_size Size of Icon.
 * @param  string  $iconShape Shape of Icon.
 * @return string
 */

function ub_prepare_social_share_icon($icon, $iconShape, $siteName, $link, $caption, $hasOutline, $icon_size, $attributes){
	$icon_styles = array(
		'width'  => ( $icon_size * 1.5 ) . 'px',
		'height' => ( $icon_size * 1.5 ) . 'px',
	);
	$text_style = array();
	$site_container_styles = array();

	if ( $iconShape === 'square' ) {
		$icon_border_radius = isset( $attributes['iconBorderRadius'] ) ? $attributes['iconBorderRadius'] : array();

		$icon_styles['border-top-left-radius']     = ! empty( $icon_border_radius['topLeft'] ) ? $icon_border_radius['topLeft'] : '';
		$icon_styles['border-top-right-radius']    = ! empty( $icon_border_radius['topRight'] ) ? $icon_border_radius['topRight'] : '';
		$icon_styles['border-bottom-left-radius']  = ! empty( $icon_border_radius['bottomLeft'] ) ? $icon_border_radius['bottomLeft'] : '';
		$icon_styles['border-bottom-right-radius'] = ! empty( $icon_border_radius['bottomRight'] ) ? $icon_border_radius['bottomRight'] : '';
	}

	if ( $attributes['buttonColor'] !== '' ) {
		if( $attributes['useCaptions'] ){
			$icon_styles['background-color'] = $iconShape === 'none' ? 'transparent' : $attributes['buttonColor'];
			$text_style['color'] =  $attributes['buttonColor'] ;
			if ( $attributes['addOutline'] ) {
				$site_container_styles['border'] = '1px solid ' . $attributes['buttonColor'];
			} else {
				$site_container_styles['border'] = 'none';
			}
		}
	} else {
		$siteColors = array(
			'facebook'  => '#1877f2',
			'twitter'   => '#1d9bf0',
			'linkedin'  => '#2867b2',
			'pinterest' => '#e60023',
			'reddit'    => '#ff4500',
			'tumblr'    => '#001935'
		);

		foreach ( $siteColors as $site => $color ) {
			if ( $site === $siteName ) {
				$site_container_styles['border-color'] = $color;
				break;
			}
		}
	}

	if ( $attributes['iconShape'] === 'none' ) {
		$anchor_styles = array(
			'background-color' => 'transparent',
			'box-shadow'       => 'none',
		);
		$site_container_styles = array_merge($site_container_styles, $anchor_styles);
	}
	if($hasOutline){
		$caption_html = $caption ? sprintf(
			'<span style="%2$s">%1$s</span>',
			wp_kses_post($caption),
			Ultimate_Blocks\includes\generate_css_string($text_style)
			) : '';
		return sprintf(
			'<a aria-label="%1$s-logo" target="_blank" rel="nofollow" href="%2$s" class="ub-social-share-%1$s-container" style="%8$s">
				<span class="social-share-icon ub-social-share-%1$s%3$s" style="%6$s">%4$s</span>%5$s
			</a>',
			$siteName,
			esc_url($link),
			($iconShape === 'none' ? '' : ' ' . esc_attr($iconShape)),
			$icon,
			$caption_html,
			Ultimate_Blocks\includes\generate_css_string($icon_styles),
			Ultimate_Blocks\includes\generate_css_string($text_style),
			Ultimate_Blocks\includes\generate_css_string($site_container_styles)
		);
	} else {
		$caption_html = $caption ? sprintf(
			'<span><a style="%4$s" aria-label="%1$s-logo" target="_blank" href="%2$s">%3$s</a></span>',
			$siteName,
			esc_url($link),
			wp_kses_post($caption),
			Ultimate_Blocks\includes\generate_css_string($text_style)
		) : '';
		return sprintf(
			'%1$s<a aria-label="%2$s-logo" target="_blank" rel="nofollow" href="%3$s" class="social-share-icon ub-social-share-%2$s %4$s%5$s" style="%7$s">%6$s</a>%8$s',
			$caption ? '<div class="ub-social-share-' . $siteName . '-container" style="' . Ultimate_Blocks\includes\generate_css_string($site_container_styles) . '">' : '',
			$siteName,
			esc_url($link),
			$caption ? ' ' : 'ub-social-share-standalone-icon ',
			$iconShape === 'none' ? '' : ' ' . esc_attr($iconShape),
			$icon,
			Ultimate_Blocks\includes\generate_css_string($icon_styles),
			$caption ? $caption_html . '</div>' : ''
		);
	}
}

function ub_get_facebook_icon( $attributes, $icon_size, $iconShape, $caption, $hasOutline ) {
    extract($attributes);
	if ( !$showFacebookIcon ) {
		return '';
	}

	// Generate the Facebook Icon.
	$facebook_icon = facebook_icon(
		array(
			'width'     => $icon_size,
			'height'    => $icon_size,
			'color'		=> $iconShape === 'none' ? ($buttonColor ?: '#1877f2') : ''
		)
	);

	// Generate the Facebook URL.
    $facebook_url = 'https://www.facebook.com/sharer/sharer.php?u='
        . rawurlencode( get_the_permalink() ) . '&title=' . rawurlencode( get_the_title() );

	return ub_prepare_social_share_icon($facebook_icon, $iconShape, 'facebook', $facebook_url, $caption, $hasOutline, $icon_size, $attributes);
}

/**
 * Generate Twitter Icon.
 *
 * @param  array   $attributes Options of the block.
 * @param  integer $icon_size Size of Icon.
 * @param  string  $iconShape Shape of Icon.
 * @return string
 */
function ub_get_twitter_icon( $attributes, $icon_size, $iconShape, $caption, $hasOutline ) {
    extract($attributes);
	if ( !$showTwitterIcon ) {
		return '';
	}

	// Generate the Twitter Icon.
	$twitter_icon = twitter_icon(
		array(
			'width'     => $icon_size,
			'height'    => $icon_size,
			'color'		=> $iconShape === 'none' ? ($buttonColor ?: '#1d9BF0' ): ''
		)
	);

	// Generate the Twitter URL.
    $twitter_url = 'http://twitter.com/intent/tweet?url=' . rawurlencode( get_the_permalink() ) . '&text=' . rawurlencode( get_the_title() );

	return ub_prepare_social_share_icon($twitter_icon, $iconShape, 'twitter', $twitter_url, $caption, $hasOutline, $icon_size, $attributes);
}


/**
 * Generate Linked In Icon.
 *
 * @param  array   $attributes Options of the block.
 * @param  integer $icon_size Size of Icon.
 * @param  string  $iconShape Shape of Icon.
 * @return string
 */
function ub_get_linkedin_icon( $attributes, $icon_size, $iconShape, $caption, $hasOutline ) {
    extract($attributes);
	if ( ! $showLinkedInIcon ) {
		return '';
	}

	// Generate the linked In Icon.
	$linkedin_icon = linkedin_icon(
		array(
			'width'     => $icon_size,
			'height'    => $icon_size,
			'color'		=> $iconShape === 'none' ? ( $buttonColor ?: '#2867b2' ) : ''
		)
	);

	// Generate the Linked In URL.
	$linkedin_url = 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( get_the_permalink() );

	return ub_prepare_social_share_icon($linkedin_icon, $iconShape, 'linkedin', $linkedin_url, $caption, $hasOutline, $icon_size, $attributes);
}


/**
 * Generate Pinterest Icon.
 *
 * @param  array   $attributes Options of the block.
 * @param  integer $icon_size Size of Icon.
 * @param  string  $iconShape Shape of Icon.
 * @return string
 */
function ub_get_pinterest_icon( $attributes, $icon_size, $iconShape, $caption, $hasOutline ) {
	global $post;
    extract($attributes);
	if ( ! $showPinterestIcon ) {
		return '';
	}

	// Get the featured image.
	if ( has_post_thumbnail() ) {
		$thumbnail_id = get_post_thumbnail_id( $post->ID );
		$thumbnail    = $thumbnail_id ? current( wp_get_attachment_image_src( $thumbnail_id, 'large', true ) ) : '';
	} else {
		$thumbnail = null;
	}

	// Generate the Pinterest Icon.
	$pinterest_icon = pinterest_icon(
		array(
			'width'     => $icon_size,
			'height'    => $icon_size,
			'color'		=> $iconShape === 'none' ? ( $buttonColor ?: '#e60023' ) : ''
		)
	);

	// Generate the Pinterest URL.
    $pinterest_url = 'https://pinterest.com/pin/create/button/?&url='
        . rawurlencode( get_the_permalink() )
        . '&description=' . rawurlencode( get_the_title() )
        . '&media=' . $thumbnail;

	return ub_prepare_social_share_icon($pinterest_icon, $iconShape, 'pinterest', $pinterest_url, $caption, $hasOutline, $icon_size, $attributes);
}


/**
 * Generate Reddit Icon.
 *
 * @param  array   $attributes Options of the block.
 * @param  integer $icon_size Size of Icon.
 * @param  string  $iconShape Shape of Icon.
 * @return string
 */
function ub_get_reddit_icon( $attributes, $icon_size, $iconShape, $caption, $hasOutline ) {
    extract($attributes);
	if ( ! $showRedditIcon ) {
		return '';
	}

	// Generate the Reddit Icon.
	$reddit_icon = reddit_icon(
		array(
			'width'     => $icon_size,
			'height'    => $icon_size,
			'color'		=> $iconShape === 'none' ? ($buttonColor ?: '#ff4500') : ''
		)
	);

	// Generate the Reddit URL.
    $reddit_url = 'http://www.reddit.com/submit?url='
        . rawurlencode( get_the_permalink() ) .
        '&title=' . rawurlencode( get_the_title() );

	return ub_prepare_social_share_icon($reddit_icon, $iconShape, 'reddit', $reddit_url, $caption, $hasOutline, $icon_size, $attributes);
}


/**
 * Generate Tumblr Icon.
 *
 * @param  array   $attributes Options of the block.
 * @param  integer $icon_size Size of Icon.
 * @param  string  $iconShape Shape of Icon.
 * @return string
 */
function ub_get_tumblr_icon( $attributes, $icon_size, $iconShape, $caption, $hasOutline ) {
    extract($attributes);
	if ( ! $showTumblrIcon ) {
		return '';
	}

	// Generate the tumblr Icon.
	$tumblr_icon = tumblr_icon(
		array(
			'width'     => $icon_size,
			'height'    => $icon_size,
			'color'		=> $iconShape === 'none' ? ( $buttonColor ?: '#001935' ) : ''
		)
	);

	// Generate the tumblr URL.
    $tumblr_url = 'https://www.tumblr.com/widgets/share/tool?canonicalUrl='
        . rawurlencode( get_the_permalink() )
		. '&title=' . rawurlencode( get_the_title() );

	return ub_prepare_social_share_icon($tumblr_icon, $iconShape, 'tumblr', $tumblr_url, $caption, $hasOutline, $icon_size, $attributes);
}

/**
 * Register Block
 *
 * @return void
 */
function ub_register_social_share_block() {
	if( function_exists( 'register_block_type_from_metadata' ) ) {
        require dirname(dirname(__DIR__)) . '/defaults.php';
		register_block_type_from_metadata( dirname(dirname(dirname(__DIR__))) . '/dist/blocks/social-share/block.json', array(
			'attributes'      => $defaultValues['ub/social-share']['attributes'],
			'render_callback' => 'ub_render_social_share_block',
		) );
	}
}


add_action( 'init', 'ub_register_social_share_block' );
