<?php
/**
 * Blocks Initializer
 *
 * Enqueue CSS/JS of all the blocks.
 *
 * @since    1.0.0
 * @package CGB
 */

// Exit if accessed directly.

use PHPUnit\Event\Runtime\PHP;
use Ultimate_Blocks\includes\Editor_Data_Manager;

use function Ultimate_Blocks\includes\get_border_css;

require_once dirname(__DIR__) . '/src/extensions/extension-manager.php';
require_once dirname(__DIR__) . '/includes/ultimate-blocks-styles-css-generator.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'get_current_screen' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/screen.php' );
}
function ub_get_spacing_styles( $attributes, $paddingImportant = false ) {
	$padding = Ultimate_Blocks\includes\get_spacing_css( isset($attributes['padding']) ? $attributes['padding'] : array() );
	$margin = Ultimate_Blocks\includes\get_spacing_css( isset($attributes['margin']) ? $attributes['margin'] : array() );

	$styles = array(
		'padding-top'        => isset($padding['top']) ? $padding['top'] . ($paddingImportant ? " !important" : "") : "",
		'padding-left'       => isset($padding['left']) ? $padding['left'] . ($paddingImportant ? " !important" : "") : "",
		'padding-right'      => isset($padding['right']) ? $padding['right'] . ($paddingImportant ? " !important" : "") : "",
		'padding-bottom'     => isset($padding['bottom']) ? $padding['bottom'] . ($paddingImportant ? " !important" : "") : "",
		'margin-top'         => !empty($margin['top']) ? $margin['top'] . " !important" : "",
		'margin-left'        => !empty($margin['left']) ? $margin['left'] . " !important" : "",
		'margin-right'       => !empty($margin['right']) ? $margin['right'] . " !important" : "",
		'margin-bottom'      => !empty($margin['bottom']) ? $margin['bottom'] . " !important" : "",
	);

	return Ultimate_Blocks\includes\generate_css_string( $styles );
}

/**
 * Check if the current page is the Gutenberg block editor.
 * @return bool
 */
function ub_check_is_gutenberg_page() {

	// The Gutenberg plugin is on.
	if ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) {
		return true;
	}

	// Gutenberg page on WordPress 5+.
	$current_screen = get_current_screen();
	if ( $current_screen !== null && method_exists( $current_screen,
					'is_block_editor' ) && $current_screen->is_block_editor() ) {
		return true;
	}

	return false;

}

/**
 * Enqueue Gutenberg block assets for both frontend + backend.
 *
 * `wp-blocks`: includes block type registration and related functions.
 *
 * @since 1.0.0
 */

function ub_update_css_version( $updated ) {
	static $frontendStyleUpdated = false;
	static $editorStyleUpdated = false;
	if ( $updated === 'frontend' ) {
		$frontendStyleUpdated = true;
	} elseif ( $updated === 'editor' ) {
		$editorStyleUpdated = true;
	}

	if ( $frontendStyleUpdated && $editorStyleUpdated ) {
		update_option( 'ultimate_blocks_css_version', Ultimate_Blocks_Constants::plugin_version() );
		$frontendStyleUpdated = false;
		$editorStyleUpdated   = false;
	}
}

function ub_load_assets() {
	wp_register_style(
			'ultimate_blocks-cgb-style-css', // Handle.
			plugins_url( 'dist/blocks.style.build.css', dirname( __FILE__ ) ), // Block style CSS.
			array(), // Dependency to include the CSS after it.
			Ultimate_Blocks_Constants::plugin_version()  // Version: latest version number.
	);
}
add_action( "init", "ub_load_assets" );
function ub_advanced_heading_add_assets( $fontList ) {

	$fontNames = join( "|", array_filter( $fontList, function ( $item ) {
		return $item !== 'Default';
	} ) );

	wp_enqueue_style( 'ultimate_blocks-advanced-heading-fonts',
			'https://fonts.googleapis.com/css?family=' . $fontNames );
}

function ub_generate_widget_block_list( $output = false ) {
	static $blockList = array();
	require_once plugin_dir_path( __FILE__ ) . 'common.php';

	if ( ! $output ) {
		$widget_elements = get_option( 'widget_block' );
		foreach ( (array) $widget_elements as $key => $widget_element ) {
			if ( ! empty( $widget_element['content'] ) ) {

				$widget_blocks = ub_getPresentBlocks( $widget_element['content'] );

				foreach ( $widget_blocks as $block ) {
					$blockList[] = $block;
				}
			}
		}
	}

	return $blockList;
}

function ultimate_blocks_cgb_block_assets() {
	// Styles.
	if ( is_singular() and has_blocks() ) {
		require_once plugin_dir_path( __FILE__ ) . 'common.php';

		$main_assets_loaded = false;

		$advanced_heading_font_list = array();

		$widget_blocks = ub_generate_widget_block_list();

		$defaultFont = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif';

		foreach ( $widget_blocks as $block ) {
			if ( strpos( $block['blockName'], 'ub/' ) === 0 ) {

				if ( ! $main_assets_loaded ) {
					ub_load_assets();
					$main_assets_loaded = true;
				}

				if ( strpos( $block['blockName'], 'ub/advanced-heading' ) === 0 ) {
					if ( $block['attrs']['fontFamily'] !== $defaultFont && ! in_array( $block['attrs']['fontFamily'],
									$advanced_heading_font_list ) ) {
						array_push( $advanced_heading_font_list, $block['attrs']['fontFamily'] );
					}
				}
			}
		}

		if ( ! ( $main_assets_loaded ) ) {
			$presentBlocks = ub_getPresentBlocks();

			foreach ( $presentBlocks as $block ) {
				if ( strpos( $block['blockName'], 'ub/' ) === 0 ) {

					if ( ! $main_assets_loaded ) {
						ub_load_assets();
						$main_assets_loaded = true;
					}

					if ( strpos( $block['blockName'], 'ub/advanced-heading' ) === 0 ) {
						if ( $block['attrs']['fontFamily'] !== $defaultFont && ! in_array( $block['attrs']['fontFamily'],
										$advanced_heading_font_list ) ) {
							array_push( $advanced_heading_font_list, $block['attrs']['fontFamily'] );
						}

					}
				}
			}
		}

		if ( count( $advanced_heading_font_list ) > 0 ) {
			ub_advanced_heading_add_assets( $advanced_heading_font_list );
		}

	} elseif ( ub_check_is_gutenberg_page() ) {
		ub_load_assets();
	}
} // End function ultimate_blocks_cgb_block_assets().

// Hook: Frontend assets.
add_action( 'enqueue_block_assets', 'ultimate_blocks_cgb_block_assets' );

/**
 * Enqueue assets which are important to be initialized before any version of plugin assets are.
 * @return void
 */
function ultimate_blocks_priority_editor_assets() {
	wp_enqueue_script( 'ultimate-blocks-priority-script',
			trailingslashit( ULTIMATE_BLOCKS_URL ) . 'dist/priority.build.js', [ 'wp-blocks' ], ULTIMATE_BLOCKS_VERSION,
			false );

	Editor_Data_Manager::get_instance()->attach_priority_data( [], 'ultimate-blocks-priority-script' );
}

/**
 * Enqueue Gutenberg block assets for backend editor.
 *
 * `wp-blocks`: includes block type registration and related functions.
 * `wp-element`: includes the WordPress Element abstraction for describing the structure of your blocks.
 * `wp-i18n`: To internationalize the block's text.
 *
 * @since 1.0.0
 */
function ultimate_blocks_cgb_editor_assets() {
	// Scripts.
	wp_register_script(
			'ultimate_blocks-cgb-block-js', // Handle.
			plugins_url( '/dist/blocks.build.js', dirname( __FILE__ ) ),
			// Block.build.js: We register the block here. Built with Webpack.
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-api', 'lodash'), // Dependencies, defined above.
			Ultimate_Blocks_Constants::plugin_version(), true  // Version: latest version number.
	);

	$extensions = get_option('ultimate_blocks_extensions');
	wp_localize_script( 'ultimate_blocks-cgb-block-js', 'ub_extensions', $extensions );
	Editor_Data_Manager::get_instance()->attach_editor_data( [], 'ultimate_blocks-cgb-block-js' );

	wp_enqueue_script(
			'ultimate_blocks-cgb-deactivator-js', // Handle.
			plugins_url( '/includes/assets/js/deactivator.js', dirname( __FILE__ ) ),
			// Block.build.js: We register the block here. Built with Webpack.
			array( 'wp-editor', 'wp-blocks', 'wp-i18n', 'wp-element' ), // Dependencies, defined above.
			Ultimate_Blocks_Constants::plugin_version(), // Version: latest version number.
			true
	);

	wp_register_style(
			'ultimate_blocks-cgb-block-editor-css', // Handle.
			plugins_url( 'dist/blocks.editor.build.css', dirname( __FILE__ ) ), // Block editor CSS.
			array( 'wp-edit-blocks' ), // Dependency to include the CSS after it.
			Ultimate_Blocks_Constants::plugin_version() // Version: latest version number
	);
} // End function ultimate_blocks_cgb_editor_assets().

// Hook: Editor assets.
add_action( 'enqueue_block_editor_assets', 'ultimate_blocks_priority_editor_assets', 1 );

// Hook: Editor assets.
add_action( 'enqueue_block_editor_assets', 'ultimate_blocks_cgb_editor_assets' );


function ub_register_settings() {
	register_setting( 'ub_settings', 'ub_icon_choices', array(
			'type'         => 'string',
			'show_in_rest' => true,
			'default'      => '' //value should be in json
	) );
}

add_action( 'init', 'ub_register_settings' );


/**
 * Rank Math ToC Plugins List.
 */
add_filter( 'rank_math/researches/toc_plugins', function ( $toc_plugins ) {
	$toc_plugins['ultimate-blocks/ultimate-blocks.php'] = 'Ultimate Blocks';

	return $toc_plugins;
} );

// Click to Tweet Block.
require_once plugin_dir_path( __FILE__ ) . 'blocks/click-to-tweet/block.php';

// Social Share Block.
require_once plugin_dir_path( __FILE__ ) . 'blocks/social-share/block.php';

// Content toggle Block.
require_once plugin_dir_path( __FILE__ ) . 'blocks/content-toggle/block.php';

// Tabbed Content Block.
require_once plugin_dir_path( __FILE__ ) . 'blocks/tabbed-content/block.php';

// Progress Bar Block.
require_once plugin_dir_path( __FILE__ ) . 'blocks/progress-bar/block.php';

// Countdown Block
require_once plugin_dir_path( __FILE__ ) . 'blocks/countdown/block.php';

// Image Slider Block
require_once plugin_dir_path( __FILE__ ) . 'blocks/image-slider/block.php';

// Table of Contents Block
require_once plugin_dir_path( __FILE__ ) . 'blocks/table-of-contents/block.php';

// Button Block
require_once plugin_dir_path( __FILE__ ) . 'blocks/button/block.php';

// Content Filter Block
require_once plugin_dir_path( __FILE__ ) . 'blocks/content-filter/block.php';

// Call to Action Block
require_once plugin_dir_path( __FILE__ ) . 'blocks/call-to-action/block.php';

// Feature Box
require_once plugin_dir_path( __FILE__ ) . 'blocks/feature-box/block.php';

// Notification Box
require_once plugin_dir_path( __FILE__ ) . 'blocks/notification-box/block.php';

// Number Box
require_once plugin_dir_path( __FILE__ ) . 'blocks/number-box/block.php';

// Star Rating
require_once plugin_dir_path( __FILE__ ) . 'blocks/star-rating/block.php';

// Testimonial
require_once plugin_dir_path( __FILE__ ) . 'blocks/testimonial/block.php';

// Review
require_once plugin_dir_path( __FILE__ ) . 'blocks/review/block.php';

// Divider
require_once plugin_dir_path( __FILE__ ) . 'blocks/divider/block.php';

//Post-Grid
require_once plugin_dir_path( __File__ ) . 'blocks/post-grid/block.php';

//Styled Box
require_once plugin_dir_path( __FILE__ ) . 'blocks/styled-box/block.php';

//Expand
require_once plugin_dir_path( __FILE__ ) . 'blocks/expand/block.php';

// Styled List
require_once plugin_dir_path( __FILE__ ) . 'blocks/styled-list/block.php';

// How To
require_once plugin_dir_path( __FILE__ ) . 'blocks/how-to/block.php';

// Advanced Heading
require_once plugin_dir_path( __FILE__ ) . 'blocks/advanced-heading/block.php';

// Advanced Video
require_once plugin_dir_path( __FILE__ ) . 'blocks/advanced-video/block.php';

// Icon
require_once plugin_dir_path( __FILE__ ) . 'blocks/icon/block.php';

// Counter
require_once plugin_dir_path( __FILE__ ) . 'blocks/counter/block.php';

// Image
require_once plugin_dir_path( __FILE__ ) . 'blocks/image/block.php';

/**
 * Innerblocks.
 */
// icon innerblock
require_once plugin_dir_path( __FILE__ ) . 'blocks/icon-inner/block.php';
