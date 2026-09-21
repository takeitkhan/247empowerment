<?php

namespace Ultimate_Blocks\includes\pro_manager\extensions;

use Ultimate_Blocks\includes\pro_manager\base\Pro_Extension_Upsell;
use Ultimate_Blocks\includes\pro_manager\inc\Pro_Editor_Control_Data;

/**
 * Countdown block extension.
 */
class Countdown_Extension extends Pro_Extension_Upsell {

	/**
	 * Use generate_upsell_data inside this function to generate your extension upsell data.
	 *
	 * Returned data array structure:
	 *
	 *  [
	 *      [ feature_id =>
	 *          [feature_name, feature_description, feature_screenshot(can be omitted for auto search)]
	 *      ],
	 *      ...
	 *  ]
	 *
	 *  Beside feature_id, remaining data should match arguments of `generate_upsell_data` method.
	 *
	 * @return array data
	 */
	public function add_upsell_data() {
		return [
			'expiryContent' => [
				__( 'Expiry Content', 'ultimate-blocks' ),
				__( 'Display custom blocks when the countdown expires. Add any blocks (paragraphs, buttons, images, etc.) directly in the editor that will appear when the timer ends. Perfect for showing special offers, thank you messages, or next steps.',
					'ultimate-blocks' )
			],
			'redirectOnExpiry' => [
				__( 'Redirect on Expiry', 'ultimate-blocks' ),
				__( 'Automatically redirect visitors to a custom URL when the countdown ends. Great for launching sales pages, event registrations, or limited-time offers.',
					'ultimate-blocks' )
			]
		];
	}

	/**
	 * Add data for editor sidebar upsell dummy controls.
	 *
	 * Override this function to actually send data, default is an empty array.
	 *
	 * @return array editor control data
	 */
	public function add_editor_dummy_control_data() {
		$expiry_panel_content = [
			Pro_Editor_Control_Data::generate_toggle_control_data( 'enableExpiryContent',
				__( 'Show Expiry Content', 'ultimate-blocks' ) ),
			Pro_Editor_Control_Data::generate_toggle_control_data( 'enableRedirect',
				__( 'Redirect on Expiry', 'ultimate-blocks' ) )
		];

		return [
			Pro_Editor_Control_Data::generate_panel_data( 'expiryOptionsPanel', __( 'Expiry Options', 'ultimate-blocks' ),
				$expiry_panel_content )
		];
	}
}
