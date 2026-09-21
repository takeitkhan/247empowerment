<?php
/**
 * Render_Assistant class.
 *
 * @package ultimate-blocks
 */

namespace Ultimate_Blocks\includes\managers;

use Ultimate_Blocks\includes\common\traits\Manager_Base_Trait;
use function add_filter;

/**
 * Assistant to improve render operations of plugin blocks.
 */
class Render_Assistant {
    use Manager_Base_Trait;

    private $defaultValues = [];

    protected function init_process() {
        // Load defaults once here
        $defaults_file = trailingslashit( ULTIMATE_BLOCKS_PATH ) . 'src/defaults.php';
        if ( file_exists( $defaults_file ) ) {
            require $defaults_file;
            if ( isset( $defaultValues ) && is_array( $defaultValues ) ) {
                $this->defaultValues = $defaultValues;
            }
        }

        add_filter( 'render_block_data', array( $this, 'inject_render_data' ), 10, 1 );
    }

    public function inject_render_data( $block ) {
        $block_name      = $block['blockName'];
        $is_plugin_block = ! is_null( $block_name ) && preg_match( '/^ub\/(.+)$/', $block_name );

        if ( $is_plugin_block && isset( $this->defaultValues[ $block_name ]['attributes'] ) ) {
            $block_default_attrs = $this->defaultValues[ $block_name ]['attributes'];
            unset( $block_default_attrs['blockID'] );

            $parsed_default_attrs = array_reduce(
                array_keys( $block_default_attrs ),
                function ( $carry, $attr_id ) use ( $block_default_attrs ) {
                    if ( isset( $block_default_attrs[ $attr_id ]['default'] ) ) {
                        $carry[ $attr_id ] = $block_default_attrs[ $attr_id ]['default'];
                    }
                    return $carry;
                },
                array()
            );

            $block['attrs'] = array_merge( $parsed_default_attrs, isset($block['attrs']) ? $block['attrs'] : array() );
        }

        return $block;
    }
}
