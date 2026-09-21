<?php
/**
 * Content toggle main block file.
 *
 * @package Ultimate_Blocks
 */

/**
 * Enqueue frontend script for content toggle block
 *
 * @return void
 */
function ub_content_toggle_add_frontend_assets() {
	wp_register_script(
		'ultimate_blocks-content-toggle-front-script',
		plugins_url( 'content-toggle/front.build.js', __DIR__ ),
		array(),
		Ultimate_Blocks_Constants::plugin_version(),
		true
	);
}

if ( ! class_exists( 'ub_simple_html_dom_node' ) ) {
	require dirname( dirname( __DIR__ ) ) . '/simple_html_dom.php';
}

function ub_render_content_toggle_block( $attributes, $content, $block ) {
	extract( $attributes );
	$block_attrs = $block->parsed_block['attrs'];
	if ( is_singular() && isset( $block_attrs['hasFAQSchema'] ) && $block_attrs['hasFAQSchema'] ) {
		add_action( 'wp_footer', 'ub_merge_faqpages', 80 );
	}
	$padding = Ultimate_Blocks\includes\get_spacing_css( isset($block_attrs['padding']) ? $block_attrs['padding'] : array() );
	$margin  = Ultimate_Blocks\includes\get_spacing_css( isset($block_attrs['margin']) ? $block_attrs['margin'] : array() );
	$styles  = array(
		'padding-top'        => isset($padding['top']) ? $padding['top'] : "",
		'padding-left'       => isset($padding['left']) ? $padding['left'] : "",
		'padding-right'      => isset($padding['right']) ? $padding['right'] : "",
		'padding-bottom'     => isset($padding['bottom']) ? $padding['bottom'] : "",
		'margin-top'         => !empty($margin['top']) ? $margin['top']  : "",
		'margin-left'        => !empty($margin['left']) ? $margin['left']  : "",
		'margin-right'       => !empty($margin['right']) ? $margin['right']  : "",
		'margin-bottom'      => !empty($margin['bottom']) ? $margin['bottom']  : "",
	);

	$classes                  = array('wp-block-ub-content-toggle');

	if(isset($className)){
		$classes[] = $className;
	}
	$block_wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => implode( ' ', $classes ),
			'id'    => 'ub-content-toggle-block-' . esc_attr($blockID),
			'style' => Ultimate_Blocks\includes\generate_css_string($styles),
			'data-mobilecollapse' => json_encode($collapsedOnMobile),
			'data-desktopcollapse' => json_encode($collapsed),
			'data-preventcollapse' => $preventCollapse ? 'true' : 'false',
			'data-showonlyone' => $showOnlyOne ? 'true' : 'false',
		)
	);

	return sprintf(
		'<div %1$s>%2$s</div>',
		$block_wrapper_attributes, // 1
		$content // 2
	);
}

function ub_render_content_toggle_panel_block( $attributes, $content, $block_object ) {
	$classNamePrefix = 'wp-block-ub-content-toggle';
	extract( $attributes );
	$icons        = json_decode( file_get_contents( __DIR__ . '/icons/icons.json' ) );
	$icon_class   = $icons->$toggleIcon;

	$block_context = $block_object->context;

	if ( isset( $block_context['parentID'] ) ) {
		$parentID = $block_context['parentID'];
	}

	$panelBorderRadius = isset( $block_context['panelBorderRadius'] ) ? $block_context['panelBorderRadius'] : array();
	$border_radius_styles = array(
		'border-top-left-radius' => ! empty( $panelBorderRadius['topLeft'] ) ? esc_attr( $panelBorderRadius['topLeft'] ) : '',
		'border-top-right-radius' => ! empty( $panelBorderRadius['topRight'] ) ? esc_attr( $panelBorderRadius['topRight'] ) : '',
		'border-bottom-left-radius' => ! empty( $panelBorderRadius['bottomLeft'] ) ? esc_attr( $panelBorderRadius['bottomLeft'] ) : '',
		'border-bottom-right-radius' => ! empty( $panelBorderRadius['bottomRight'] ) ? esc_attr( $panelBorderRadius['bottomRight'] ) : '',
	);

	$panelBorder = isset( $block_context['panelBorder'] ) ? $block_context['panelBorder'] : array();
	if ( ! empty( $panelBorder ) ) {
		$border_css = Ultimate_Blocks\includes\get_border_css( $panelBorder );
		$border_styles = array(
			'border-top' => esc_attr( Ultimate_Blocks\includes\get_single_side_border_value( $border_css, 'top' ) ),
			'border-right' => esc_attr( Ultimate_Blocks\includes\get_single_side_border_value( $border_css, 'right' ) ),
			'border-bottom' => esc_attr( Ultimate_Blocks\includes\get_single_side_border_value( $border_css, 'bottom' ) ),
			'border-left' => esc_attr( Ultimate_Blocks\includes\get_single_side_border_value( $border_css, 'left' ) ),
		);
	} else {
		$border_styles = array( 'border-color' => esc_attr( $theme ) );
	}

	$should_collapsed = $collapsed && ! $defaultOpen;

	if (!in_array($titleTag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'])) {
		$titleTag = 'p';
	}
	$toggle_icon_html = sprintf(
		'<div class="%1$s-accordion-toggle-wrap %2$s" style="color: %5$s;"><span class="%1$s-accordion-state-indicator %3$s%4$s"></span></div>',
		$classNamePrefix,
		esc_attr($toggleLocation),
		esc_attr($icon_class),
		$should_collapsed ? '' : ' open',
		esc_attr($toggleColor)
	);
	$title_styles = array(
		'color' => isset($titleColor) ? $titleColor : '',
		'--ub-content-toggle-title-link-color' => isset($titleLinkColor) ? $titleLinkColor : '',
	);

	$toggle_wrap = sprintf(
		'<div class="%1$s"%2$s%3$s aria-controls="%4$s" tabindex="0">
			<%5$s class="%6$s" style="%7$s">%8$s</%5$s>
			%9$s
		</div>',
		$classNamePrefix . '-accordion-title-wrap', // 1
		 'style="background-color: ' . esc_attr($theme) . ';"' , // 2
		$preventCollapse ? ' aria-disabled="true"' : '', // 3
		'ub-content-toggle-panel-' . esc_attr($index) . '-' . esc_attr($parentID), // 4
		esc_attr($titleTag), // 5
		$classNamePrefix . '-accordion-title ub-content-toggle-title-' . esc_attr($parentID), // 6
		Ultimate_Blocks\includes\generate_css_string($title_styles) , // 7
		wp_kses_post($panelTitle), // 8
		$toggle_icon_html // 9
	);

	$panel_wrapper_styles = array_merge(
		$border_styles,
		$border_radius_styles
	);

	return sprintf(
		'<div %1$s class="%2$s%3$s"%4$s>
			%5$s
			<div role="region" aria-expanded="%6$s" class="%7$s" id="%8$s">%9$s</div>
		</div>',
		$toggleID === '' ? '' : 'id="' . esc_attr($toggleID) . '" ', // 1
		$classNamePrefix . '-accordion', // 2
		isset($className) ? ' ' . esc_attr($className) : '', // 3
		'style="' . Ultimate_Blocks\includes\generate_css_string($panel_wrapper_styles) . '"', // 4
		$toggle_wrap, // 5
		json_encode(! $should_collapsed), // 6
		$classNamePrefix . '-accordion-content-wrap' . ( $should_collapsed ? ' ub-hide' : '' ), // 7
		'ub-content-toggle-panel-' . esc_attr($index) . '-' . esc_attr($parentID), // 8
		$content // 9
	);
}
function ub_register_content_toggle_panel_block() {
	if ( function_exists( 'register_block_type_from_metadata' ) ) {
		require dirname( dirname( __DIR__ ) ) . '/defaults.php';
		register_block_type_from_metadata( dirname(dirname(dirname(__DIR__))) . '/dist/blocks/content-toggle/components/panel/block.json', array(
			'attributes'      => $defaultValues['ub/content-toggle-panel-block']['attributes'],
			'render_callback' => 'ub_render_content_toggle_panel_block'
		) );
	}
}

function ub_register_content_toggle_block() {
	if ( function_exists( 'register_block_type_from_metadata' ) ) {
		require dirname( dirname( __DIR__ ) ) . '/defaults.php';
		register_block_type_from_metadata( dirname(dirname(dirname(__DIR__))) . '/dist/blocks/content-toggle/block.json',
			array(
				'attributes'      => $defaultValues['ub/content-toggle-block']['attributes'],
				'render_callback' => 'ub_render_content_toggle_block',
			)
		);
	}
}

add_action( 'init', 'ub_register_content_toggle_block' );

add_action( 'init', 'ub_register_content_toggle_panel_block' );

add_action( 'wp_enqueue_scripts', 'ub_content_toggle_add_frontend_assets' );

add_filter( 'render_block', 'ub_content_toggle_filter', 10, 3 );

function ub_faq_questions( $qna = array() ) {
	static $parsed_qna = array();

	if ( ! isset( $qna ) ) {
		$parsed_qna = array();
	}

	if ( empty( $qna ) ) {
		return $parsed_qna;
	} else {
		$current_qna = array();

		$current  = array_map(
			function ( $item ) {
				return json_encode( $item );
			},
			$parsed_qna
		);
		$newItems = array_map(
			function ( $item ) {
				return json_encode( $item );
			},
			$qna
		);

		foreach ( $newItems as $index => $item ) {
			if ( ! in_array( $item, $current ) ) {
				array_push( $current_qna, $qna[ $index ] );
			}
		}
		$parsed_qna = array_merge( $parsed_qna, $current_qna );

		return true;
	}
}

function ub_content_toggle_filter( $block_content, $block ) {

	if ( 'ub/content-toggle-block' !== $block['blockName'] ) {
		return $block_content;
	}

	$output = $block_content;

	if ( isset( $block['attrs']['hasFAQSchema'] ) && $block['attrs']['hasFAQSchema'] ) {
		$parsedBlockContent = ub_str_get_html(
			preg_replace(
				'/^<div class="wp-block-ub-content-toggle(?: [^>]*)?" id="ub-content-toggle-.*?">/',
				'<div class="toggleroot">',
				$block_content
			)
		);
		$panel = array();

		if( !empty($parsedBlockContent) && gettype($parsedBlockContent) !== "boolean" ){
			$panel = $parsedBlockContent->find( '.toggleroot>.wp-block-ub-content-toggle-accordion>.wp-block-ub-content-toggle-accordion-content-wrap' );
		}
		foreach ( $panel as $elem ) {
			// look for possible nested content toggles and remove existing ones
			foreach ( $elem->find( '.wp-block-ub-content-toggle' ) as $nestedToggle ) {
				$nestedToggle->outertext = '';
			}
			foreach ( $elem->find( 'script[type="application/ld+json"]' ) as $nestedToggle ) {
				$nestedToggle->outertext = '';
			}
		}

		$panel = array_map(
			function ( $elem ) {
				return $elem->innertext;
			},
			$panel
		);

		$questions = array();

		foreach ( $block['innerBlocks'] as $key => $togglePanel ) {
			if ( isset( $panel[ $key ] ) ) {
				$answer = preg_replace_callback(
					'/<([a-z1-6]+)[^>]*?>[^<]*?<\/(\1)>/i',
					function ( $matches ) {
						return ( in_array(
							$matches[1],
							array(
								'script',
								'svg',
								'iframe',
								'applet',
								'map',
								'audio',
								'button',
								'table',
								'datalist',
								'form',
								'frameset',
								'select',
								'optgroup',
								'picture',
								'style',
								'video',
							)
						) ? '' : $matches[0] );
					},
					$panel[ $key ]
				);

				$answer = preg_replace_callback(
					'/<\/?([a-z1-6]+).*?\/?>/i',
					function ( $matches ) {
						if ( in_array(
							$matches[1],
							array(
								'h1',
								'h2',
								'h3',
								'h4',
								'h5',
								'h6',
								'a',
								'br',
								'ol',
								'ul',
								'li',
								'p',
								'div',
								'b',
								'strong',
								'i',
								'em',
								'u',
								'del',
							)
						) ) {
							return $matches[0];
						} else {
							$replacement = '';
							if ( $matches[1] === 'ins' ) {
								$replacement = 'u';
							} elseif ( $matches[1] === 'big' ) {
								$replacement = 'strong';
							} elseif ( $matches[1] === 'q' ) {
								$replacement = 'p';
							} elseif ( $matches[1] === 'dir' ) {
								$replacement = 'ul';
							} elseif ( $matches[1] === 'address' || $matches[1] === 'cite' ) {
								$replacement = 'em';
							} elseif ( in_array(
								$matches[1],
								array(
									'article',
									'aside',
									'blockquote',
									'details',
									'dialog',
									'figure',
									'figcaption',
									'footer',
									'header',
									'nav',
									'pre',
									'section',
									'textarea',
								)
							) ) {
								$replacement = 'div';
							}

							return ( $replacement === '' ? '' : str_replace( $matches[1], $replacement, $matches[0] ) );
						}
					},
					$answer
				);

				while ( preg_match_all(
					'/<([a-z1-6]+)[^>]*?><\/(\1)>/i',
					$answer
				) > 0 ) { // remove empty tags and tags that only contain empty tags
					$answer = preg_replace( '/<([a-z1-6]+)[^>]*?><\/(\1)>/i', '', $answer );
				}

				// check all attributes

				$answer = preg_replace_callback(
					'/<[a-z1-6]+( (?:(?:aria|data)-[^\t\n\f \/>"\'=]+|[a-z]+)=[\'"][\s\S]+?[\'"])>/i',
					function ( $matches ) {
						$attributeList = preg_replace_callback(
							'/ ([\S]+)=([\'"])([\s\S]*?)(\2)/',
							function ( $matches ) {
								return $matches[1] === 'href' ? ( " href='" . $matches[3] . "'" ) : '';
							},
							$matches[1]
						);

						return str_replace( $matches[1], $attributeList, $matches[0] );
					},
					$answer
				);

				if ( $answer !== '' && array_key_exists(
					'panelTitle',
					$togglePanel['attrs']
				) && $togglePanel['attrs']['panelTitle'] !== '' ) { // blank answers and questions are invalid

					array_push(
						$questions,
						array(
							'@type'          => 'Question',
							'name'           => wp_filter_nohtml_kses( $togglePanel['attrs']['panelTitle'] ),
							'acceptedAnswer' => array(
								'@type' => 'Answer',
								'text'  => trim( str_replace( '"', '\"', $answer ) ),
							),
						)
					);
				}
			}
		}
		ub_faq_questions( $questions );
	}

	return $output;
}

function ub_merge_faqpages() {
	?>
	<?php
	echo '<script type="application/ld+json">{
            "@context":"http://schema.org/",
            "@type":"FAQPage",
            "mainEntity": ' . json_encode( ub_faq_questions(), JSON_UNESCAPED_SLASHES ) . '}</script>';
	?>
	<?php
}

/**
 * Add extra context information for content toggle panel.
 *
 * @param array    $current_context current context of target block.
 * @param array    $current_block block info array.
 * @param WP_Block $parent_block parent block.
 *
 * @return array block context
 */
function content_toggle_panel_context( $current_context, $current_block, $parent_block = null ) {
	$target_content_toggle_parts = array( 'ub/content-toggle-panel-block', 'ub/content-toggle-panel' );

	if ( in_array( $current_block['blockName'], $target_content_toggle_parts, true ) ) {
		if ( ! isset( $current_context['parentID'] ) && ! is_null( $parent_block ) ) {
			$current_context['parentID'] = $parent_block->parsed_block['attrs']['blockID'];
		}
	}

	return $current_context;
}

add_filter( 'render_block_context', 'content_toggle_panel_context', 10, 3 );
