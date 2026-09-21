<?php

require_once dirname(dirname(dirname(__DIR__))) . '/includes/ultimate-blocks-styles-css-generator.php';

class Ultimate_Counter {
     /**
      * Constructor
      *
      * @return void
      */
     public function __construct(){
          add_action( 'init', array( $this, 'register_block' ) );
     }

     /**
      * Render callback for the Ultimate Counter block.
      *
      * @param array $attributes The block's attributes, which control its behavior and appearance.
      * @param string $content The inner content of the block.
      *
      * @return string The HTML markup that represents the rendered block.
      */
     public function ub_render_counter_block($attributes, $_, $block){
          $start_number = $attributes['startNumber'];
          $end_number = $attributes['endNumber'];
          $prefix = $attributes['prefix'];
          $suffix = $attributes['suffix'];
          $animation_duration = $attributes['animationDuration'];
          $alignment = $attributes['alignment'];
          $label = $attributes['label'];
          $label_position = $attributes['labelPosition'];
		$block_attrs = $block->parsed_block['attrs'];

		$gap 			 	= isset($block_attrs['gap']['all']) ?  Ultimate_Blocks\includes\spacing_preset_css_var($block_attrs['gap']['all']) : "";
		$margin 			 	= Ultimate_Blocks\includes\get_spacing_css( isset($block_attrs['margin']) ? $block_attrs['margin'] : array() );
          $padding 			 	= Ultimate_Blocks\includes\get_spacing_css( isset($block_attrs['padding']) ? $block_attrs['padding'] : array() );
          $label_color 		 	= $block_attrs['labelColor'];
          $label_font_size 	 	= $block_attrs['labelFontSize'];
		$label_decoration 	 	= isset($block_attrs['labelDecoration']) ? $block_attrs['labelDecoration'] : "";
		$counter_font_size 	 	= $block_attrs['counterFontSize'];
		$counter_decoration  	= isset($block_attrs['counterDecoration']) ? $block_attrs['counterDecoration'] : "";
		$counter_font_family 	= isset($block_attrs['counterFontFamily']) ? $block_attrs['counterFontFamily'] : "";
		$label_font_family   	= isset($block_attrs['labelFontFamily']) ? $block_attrs['labelFontFamily'] : "";
		$counter_line_height 	= isset($block_attrs['counterLineHeight']) ? $block_attrs['counterLineHeight'] : "";
		$label_line_height   	= isset($block_attrs['labelLineHeight']) ? $block_attrs['labelLineHeight'] : "";
		$counter_letter_spacing	= isset($block_attrs['counterLetterSpacing']) ? $block_attrs['counterLetterSpacing'] : "";
		$label_letter_spacing  	= isset($block_attrs['labelLetterSpacing']) ? $block_attrs['labelLetterSpacing'] : "";
		$counter_font_style  	= isset( $block_attrs['counterFontAppearance']['fontStyle'] ) ? $block_attrs['counterFontAppearance']['fontStyle'] : "";
		$counter_font_weight 	= isset( $block_attrs['counterFontAppearance']['fontWeight'] ) ? $block_attrs['counterFontAppearance']['fontWeight'] : "";
		$label_font_style 	 	= isset( $block_attrs['labelFontAppearance']['fontStyle'] ) ? $block_attrs['labelFontAppearance']['fontStyle'] : "";
		$label_font_weight 	 	= isset( $block_attrs['labelFontAppearance']['fontWeight'] ) ? $block_attrs['labelFontAppearance']['fontWeight'] : "";

		// Extract new color attributes
		$counter_color 			= isset($block_attrs['counterColor']) ? $block_attrs['counterColor'] : null;
		$background_color 		= isset($block_attrs['backgroundColor']) ? $block_attrs['backgroundColor'] : null;
		$background_gradient 	= isset($block_attrs['backgroundGradient']) ? $block_attrs['backgroundGradient'] : null;

		// Backward compatibility: check old style.color attributes if new ones are not set
		if (!$counter_color && isset($block_attrs['style']['color']['text'])) {
			$counter_color = $block_attrs['style']['color']['text'];
		}
		if (!$background_color && !$background_gradient && isset($block_attrs['style']['color']['background'])) {
			$background_color = $block_attrs['style']['color']['background'];
		}

		// Determine background value (gradient takes priority over color)
		$background = $background_gradient ? $background_gradient : $background_color;


		$label_style = array(
			'color'            	=> $label_color,
			'font-size'        	=> $label_font_size,
			'text-decoration'  	=> $label_decoration,
			'font-family'      	=> $label_font_family,
			'line-height'      	=> $label_line_height,
			'letter-spacing'   	=> $label_letter_spacing,
			'font-style'	   	=> $label_font_style,
			'font-weight'	   	=> $label_font_weight,
		);
		$counter_styles = array(
			'color'					 => $counter_color,
			'font-size'              => $counter_font_size,
			'text-decoration'        => $counter_decoration,
			'font-family'            => $counter_font_family,
			'line-height'            => $counter_line_height,
			'letter-spacing'         => $counter_letter_spacing,
			'font-style'	   	   	=> $counter_font_style,
			'font-weight'	   	   	=> $counter_font_weight,
		);
		$counter_wrapper_styles = array(
			'gap'	=> $gap,
		);
		$container_styles = array(
			'background'			 => $background,
			'padding-top'            => isset($padding['top']) ? $padding['top'] : "",
               'padding-left'           => isset($padding['left']) ? $padding['left'] : "",
               'padding-right'          => isset($padding['right']) ? $padding['right'] : "",
               'padding-bottom'         => isset($padding['bottom']) ? $padding['bottom'] : "",
               'margin-top'             => isset($margin['top']) ? $margin['top']  : "",
               'margin-right'           => isset($margin['left']) ? $margin['left']  : "",
               'margin-bottom'          => isset($margin['right']) ? $margin['right']  : "",
               'margin-left'            => isset($margin['bottom']) ? $margin['bottom']  : "",
		);

          $wrapper_attributes = get_block_wrapper_attributes(
               array(
                    'class' => 'ub_counter-container',
                    'style' => Ultimate_Blocks\includes\generate_css_string($container_styles)
               )
          );
		$label_markup = sprintf(
			'<div class="ub_counter-label-wrapper" style="%1$s"><span class="ub_counter-label">%2$s</span></div>',
			Ultimate_Blocks\includes\generate_css_string($label_style),
			wp_kses_post($label)
		);
          $block_content = sprintf(
			   '<div %1$s>
					<div
						 class="ub_counter ub_text-%2$s"
						 data-start_num="%3$s"
						 data-end_num="%4$s"
						 data-animation_duration="%5$s"
						 style="%11$s"
					>
						 %8$s
						 <div class="ub_counter-number-wrapper" style="%10$s">
							  <span class="ub_counter-prefix">%6$s</span>
							  <span class="ub_counter-number">0</span>
							  <span class="ub_counter-suffix">%7$s</span>
						 </div>
						 %9$s
					</div>
			   </div>',
			   $wrapper_attributes, // 1
			   esc_attr( $alignment ), // 2
			   esc_attr( $start_number ), // 3
			   esc_attr( $end_number ), // 4
			   esc_attr( $animation_duration ), // 5
			   wp_kses_post( $prefix ), // 6
			   wp_kses_post( $suffix ), // 7
			   $label_position === 'top' ? $label_markup : "", // 8
			   $label_position === 'bottom' ? $label_markup : "", // 9
			   Ultimate_Blocks\includes\generate_css_string($counter_styles), // 10
			   Ultimate_Blocks\includes\generate_css_string($counter_wrapper_styles) // 11

          );

          return $block_content;
     }
     public function register_block() {
          require dirname(dirname(__DIR__)) . '/defaults.php';

          wp_register_script(
			'ub-counter-frontend-script',
			plugins_url( 'counter/front.build.js', dirname( __FILE__ ) ),
			array(),
			Ultimate_Blocks_Constants::plugin_version(),
			true
          );
          register_block_type_from_metadata( dirname(dirname(dirname(__DIR__))) . '/dist/blocks/counter', array(
               'attributes' => $defaultValues['ub/counter']['attributes'],
               'render_callback' => array($this, 'ub_render_counter_block')
          ));
     }

}
new Ultimate_Counter();
