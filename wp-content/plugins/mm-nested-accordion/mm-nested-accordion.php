<?php
/**
 * Plugin Name: MM Nested Accordion Manager
 * Description: Advanced multi-layered accordion manager with organized dashboard and full shortcode rendering.
 * Version: 2.1
 * Author: Samrat Khan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Register Custom Post Type with Proper Capabilities
function mmna_register_cpt() {
    $args = [
        'label' => 'MM Accordions',
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'supports' => ['title'],
        'menu_icon' => 'dashicons-feedback',
        'capability_type' => 'post',
        'map_meta_cap' => true,
    ];
    register_post_type( 'mm_accordion', $args );
}
add_action( 'init', 'mmna_register_cpt' );

// 2. Add Meta Boxes
function mmna_add_metaboxes() {
    add_meta_box(
        'mmna_accordion_content_meta',
        '🎨 Accordion Visual Builder & Dashboard',
        'mmna_render_metabox',
        'mm_accordion',
        'normal',
        'high'
    );
    add_meta_box(
        'mmna_shortcode_meta',
        '📋 Implementation Shortcode',
        'mmna_render_shortcode_metabox',
        'mm_accordion',
        'side',
        'core'
    );
}
add_action( 'add_meta_boxes', 'mmna_add_metaboxes' );

// Enqueue admin styles for icon pickers
function mmna_enqueue_admin_styles() {
    wp_enqueue_style( 'font-awesome-free', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css' );
    wp_enqueue_style( 'bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css' );
}
add_action( 'admin_enqueue_scripts', 'mmna_enqueue_admin_styles' );

// Shortcode Sidebar Box
function mmna_render_shortcode_metabox( $post ) {
    if ( $post->post_status === 'publish' ) {
        echo '<p style="margin-bottom:8px;">Copy and paste this shortcode on any page or post:</p>';
        echo '<input type="text" readonly value="[mm_nested_accordion id=\'' . $post->ID . '\']" style="width:100%; text-align:center; font-weight:bold; background:#f0f6fc; border:1px solid #cce0ff; color:#0366d6; padding:8px; border-radius:4px;" onclick="this.select();">';
    } else {
        echo '<p style="color:#d97706;"><em>Publish or update this accordion first to generate your custom shortcode.</em></p>';
    }
}

// 3. Main Modern Dashboard UI Builder
function mmna_render_metabox( $post ) {
    wp_nonce_field( 'mmna_save_accordion_data', 'mmna_nonce' );
    $accordions = get_post_meta( $post->ID, '_mmna_accordion_data', true );
    if ( ! is_array( $accordions ) ) {
        $accordions = [];
    }
    ?>
    <div class="mmna-admin-wrap">
        <div id="mmna-items-container">
            <?php 
            if ( ! empty( $accordions ) ) {
                foreach ( $accordions as $index => $acc ) {
                    mmna_render_main_row( $index, $acc );
                }
            }
            ?>
        </div>
        
        <div class="mmna-builder-actions">
            <button type="button" class="button button-primary button-hero" id="mmna-add-main-item">
                <span class="dashicons dashicons-plus-alt2" style="margin-top: 4px;"></span> Add 1st Layer Accordion
            </button>
        </div>
    </div>

    <style>
        .mmna-admin-wrap { background: #f1f1f6; padding: 15px; border-radius: 8px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .mmna-main-box { background: #fff; border: 1px solid #dcdcde; border-radius: 6px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden; }
        .mmna-box-header { background: #2c3338; color: #fff; padding: 12px 18px; display: flex; justify-content: space-between; align-items: center; }
        .mmna-box-header strong { font-size: 14px; letter-spacing: 0.5px; text-transform: uppercase; color: #fff; }
        .mmna-box-body { padding: 20px; }
        .mmna-section-group { background: #f9f9f9; border: 1px solid #e2e4e7; border-radius: 6px; padding: 15px; margin-bottom: 20px; }
        .mmna-section-title { font-weight: 700; color: #2c3338; margin: 0 0 15px 0; font-size: 13px; text-transform: uppercase; }
        .mmna-row-grid { display: grid; grid-template-columns: 2fr 2fr 1fr 1fr; gap: 15px; margin-bottom: 12px; }
        .mmna-row-grid.sub-grid { grid-template-columns: 2fr 3fr 1fr 1fr 1fr; }
        .mmna-row-grid.single-item-grid { grid-template-columns: 1.5fr 2fr 1fr 0.8fr 1fr; }
        .mmna-field-group label { display: block; font-weight: 600; font-size: 12px; color: #3c434a; margin-bottom: 4px; }
        .mmna-field-group input[type="text"], .mmna-field-group textarea { width: 100%; padding: 8px 10px; border: 1px solid #8c8f94; border-radius: 4px; background: #fff; }
        .mmna-icon-picker-wrapper { display: flex; gap: 8px; align-items: stretch; }
        .mmna-icon-picker-wrapper input { flex: 1; }
        .mmna-icon-picker-wrapper .button { flex-shrink: 0; padding: 8px 12px; }
        .mmna-sub-accordion-box { background: #f8f9fa; border: 1px solid #dcdcde; border-radius: 6px; padding: 15px; margin-top: 15px; margin-bottom: 15px; }
        .mmna-sub-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e4e7; padding-bottom: 8px; margin-bottom: 12px; color: #1d2327; font-weight: 600; }
        .mmna-child-item-box { background: #ffffff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 12px; margin-top: 10px; }
        .mmna-child-header { display: flex; justify-content: space-between; align-items: center; font-size: 12px; font-weight: 600; color: #50575e; margin-bottom: 8px; }
        .mmna-builder-actions { text-align: center; padding: 15px 0; }
        .button.button-hero { padding: 8px 20px; height: auto; font-size: 14px; }
    </style>

    <script>
    jQuery(document).ready(function($) {
        let mainIndex = $('#mmna-items-container .mmna-main-box').length;

        // Add 1st Layer
        $('#mmna-add-main-item').on('click', function() {
            let html = `
                <div class="mmna-main-box" data-index="${mainIndex}">
                    <div class="mmna-box-header">
                        <strong>🛡️ 1st Layer Accordion</strong>
                        <button type="button" class="button button-link-delete mmna-remove-main" style="color:#ffb8b8;">Remove Accordion</button>
                    </div>
                    <div class="mmna-box-body">
                        <!-- GLOBAL SETTINGS SECTION -->
                        <div class="mmna-section-group">
                            <h4 class="mmna-section-title">⚙️ Global Settings</h4>
                            <div class="mmna-row-grid">
                                <div class="mmna-field-group">
                                    <label>Main Title</label>
                                    <input type="text" name="mmna[${mainIndex}][title]" value="" placeholder="e.g. Support & Training" />
                                </div>
                                <div class="mmna-field-group">
                                    <label>Subtitle (optional)</label>
                                    <input type="text" name="mmna[${mainIndex}][subtitle]" value="" placeholder="e.g. For goals & dreams" />
                                </div>
                                <div class="mmna-field-group">
                                    <label>Icon (Emoji)</label>
                                    <div class="mmna-icon-picker-wrapper">
                                        <input type="text" name="mmna[${mainIndex}][icon]" class="mmna-icon-input" value="🛡️" />
                                        <button type="button" class="button mmna-icon-picker" data-index="${mainIndex}" title="Pick Icon">🎯 Pick</button>
                                    </div>
                                </div>
                                <div class="mmna-field-group">
                                    <label>Icon Background</label>
                                    <input type="text" name="mmna[${mainIndex}][icon_bg]" value="rgba(255,255,255,0.2)" />
                                </div>
                            </div>
                        </div>

                        <!-- TYPE SELECTOR SECTION -->
                        <div class="mmna-section-group">
                            <h4 class="mmna-section-title">📋 Structure Type</h4>
                            <div style="display:flex; gap:15px;">
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                    <input type="radio" name="mmna[${mainIndex}][type]" value="single" class="mmna-type-toggle" />
                                    <span><strong>Single Layer</strong> (Just items, no sub-accordions)</span>
                                </label>
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                    <input type="radio" name="mmna[${mainIndex}][type]" value="multi" class="mmna-type-toggle" checked />
                                    <span><strong>Multi-Layer</strong> (With sub-accordions)</span>
                                </label>
                            </div>
                        </div>

                        <!-- GLOBAL ACTION LINKS (for all items) -->
                        <div class="mmna-section-group">
                            <h4 class="mmna-section-title">🔗 Global Action Links (All Items)</h4>
                            <p style="font-size:11px; color:#6b7280; margin:0 0 12px 0;">These links will appear on all items in this accordion:</p>
                            <div class="mmna-row-grid" style="grid-template-columns: 1fr 1fr;">
                                <div class="mmna-field-group">
                                    <label>🎤 Speak with a Live Person</label>
                                    <input type="text" name="mmna[${mainIndex}][btn1_url]" value="" placeholder="https://example.com/live-chat" />
                                </div>
                                <div class="mmna-field-group">
                                    <label>👤 Hire an Expert</label>
                                    <input type="text" name="mmna[${mainIndex}][btn2_url]" value="" placeholder="https://example.com/hire-expert" />
                                </div>
                            </div>
                        </div>

                        <!-- SUB-ACCORDIONS SECTION -->
                        <div class="mmna-sub-section">
                            <hr style="border:0; border-top:1px solid #e2e4e7; margin:20px 0;">
                            <h4 style="margin:0 0 10px 0; color:#2c3338;">📁 Sub-Accordions (2nd Layer)</h4>
                            <div class="mmna-sub-container"></div>
                            <button type="button" class="button mmna-add-sub" data-main-index="${mainIndex}">+ Add Sub-Accordion</button>
                        </div>

                        <!-- SINGLE LAYER ITEMS SECTION -->
                        <div class="mmna-single-items-section" style="display: none;">
                            <hr style="border:0; border-top:1px solid #e2e4e7; margin:20px 0;">
                            <h4 style="margin:0 0 10px 0; color:#2c3338;">📝 Direct Items</h4>
                            <div class="mmna-single-items-container"></div>
                            <button type="button" class="button mmna-add-single-item" data-main-index="${mainIndex}">+ Add Direct Item</button>
                        </div>
                    </div>
                </div>
            `;
            $('#mmna-items-container').append(html);
            mainIndex++;
        });

        // Remove Main
        $(document).on('click', '.mmna-remove-main', function() {
            if(confirm('Are you sure you want to delete this main accordion?')) {
                $(this).closest('.mmna-main-box').remove();
            }
        });

        // Add Sub-Accordion (2nd Layer)
        $(document).on('click', '.mmna-add-sub', function() {
            let mIndex = $(this).data('main-index');
            let container = $(this).siblings('.mmna-sub-container');
            let subIndex = container.find('.mmna-sub-accordion-box').length;

            let subHtml = `
                <div class="mmna-sub-accordion-box" data-sub-index="${subIndex}">
                    <div class="mmna-sub-header">
                        <span>📁 Sub-Accordion Item</span>
                        <button type="button" class="button-link-delete mmna-remove-sub">Remove Sub-Accordion</button>
                    </div>
                    <div class="mmna-row-grid sub-grid">
                        <div class="mmna-field-group">
                            <label>Sub-Accordion Title</label>
                            <input type="text" name="mmna[${mIndex}][subs][${subIndex}][title]" value="" placeholder="e.g. Personal Growth" />
                        </div>
                        <div class="mmna-field-group">
                            <label>Description</label>
                            <textarea name="mmna[${mIndex}][subs][${subIndex}][desc]" placeholder="Short description text..."></textarea>
                        </div>
                        <div class="mmna-field-group">
                            <label>Icon</label>
                            <input type="text" name="mmna[${mIndex}][subs][${subIndex}][icon]" value="🎓" />
                        </div>
                        <div class="mmna-field-group">
                            <label>Icon BG</label>
                            <input type="text" name="mmna[${mIndex}][subs][${subIndex}][icon_bg]" value="#ede9fe" />
                        </div>
                        <div class="mmna-field-group">
                            <label>Icon Color</label>
                            <input type="text" name="mmna[${mIndex}][subs][${subIndex}][icon_color]" value="#7c3aed" />
                        </div>
                    </div>

                    <div style="margin-top:15px; padding-top:10px; border-top:1px dashed #ccd0d4;">
                        <h5 style="margin:0 0 8px 0; color:#50575e;">📄 Child Items Inside This Sub-Accordion</h5>
                        <div class="mmna-child-container"></div>
                        <button type="button" class="button button-small mmna-add-child" data-main-index="${mIndex}" data-sub-index="${subIndex}">+ Add Child Item</button>
                    </div>
                </div>
            `;
            container.append(subHtml);
        });

        // Remove Sub-Accordion
        $(document).on('click', '.mmna-remove-sub', function() {
            $(this).closest('.mmna-sub-accordion-box').remove();
        });

        // Add Child Item (3rd Layer)
        $(document).on('click', '.mmna-add-child', function() {
            let mIndex = $(this).data('main-index');
            let sIndex = $(this).data('sub-index');
            let container = $(this).siblings('.mmna-child-container');
            let cIndex = container.find('.mmna-child-item-box').length;

            let childHtml = `
                <div class="mmna-child-item-box">
                    <div class="mmna-child-header">
                        <span>🔹 Child Item</span>
                        <button type="button" class="button-link-delete mmna-remove-child" style="color:#b32d2e;">Remove</button>
                    </div>
                    <div class="mmna-row-grid" style="grid-template-columns: 1.5fr 2fr 1fr 0.8fr 1fr; margin-bottom:0;">
                        <div class="mmna-field-group">
                            <label>Item Title</label>
                            <input type="text" name="mmna[${mIndex}][subs][${sIndex}][children][${cIndex}][title]" value="" placeholder="Child item title" />
                        </div>
                        <div class="mmna-field-group">
                            <label>Item Details / Content</label>
                            <input type="text" name="mmna[${mIndex}][subs][${sIndex}][children][${cIndex}][content]" value="" placeholder="Detailed description..." />
                        </div>
                        <div class="mmna-field-group">
                            <label>Item URL</label>
                            <input type="text" name="mmna[${mIndex}][subs][${sIndex}][children][${cIndex}][url]" value="" placeholder="https://..." />
                        </div>
                        <div class="mmna-field-group">
                            <label>Icon</label>
                            <input type="text" name="mmna[${mIndex}][subs][${sIndex}][children][${cIndex}][icon]" value="📌" />
                        </div>
                        <div class="mmna-field-group">
                            <label>DIY Link</label>
                            <input type="text" name="mmna[${mIndex}][subs][${sIndex}][children][${cIndex}][btn3_url]" value="" placeholder="https://..." />
                        </div>
                    </div>
                </div>
            `;
            container.append(childHtml);
        });

        // Remove Child Item
        $(document).on('click', '.mmna-remove-child', function() {
            $(this).closest('.mmna-child-item-box').remove();
        });

        // Type Toggle Handler
        $(document).on('change', '.mmna-type-toggle', function() {
            let mainBox = $(this).closest('.mmna-main-box');
            let type = mainBox.find('input[name*="[type]"]:checked').val();
            
            if (type === 'single') {
                mainBox.find('.mmna-sub-section').slideUp();
                mainBox.find('.mmna-single-items-section').slideDown();
                mainBox.attr('data-type', 'single');
            } else {
                mainBox.find('.mmna-single-items-section').slideUp();
                mainBox.find('.mmna-sub-section').slideDown();
                mainBox.attr('data-type', 'multi');
            }
        });

        // Add Single Item
        $(document).on('click', '.mmna-add-single-item', function() {
            let mIndex = $(this).data('main-index');
            let container = $(this).siblings('.mmna-single-items-container');
            let itemIdx = container.find('.mmna-child-item-box').length;

            let itemHtml = `
                <div class="mmna-child-item-box">
                    <div class="mmna-child-header">
                        <span>🔹 Item</span>
                        <button type="button" class="button-link-delete mmna-remove-single-item" style="color:#b32d2e;">Remove</button>
                    </div>
                    <div class="mmna-row-grid single-item-grid" style="margin-bottom:0;">
                        <div class="mmna-field-group">
                            <label>Item Title</label>
                            <input type="text" name="mmna[${mIndex}][items][${itemIdx}][title]" value="" placeholder="Item title" />
                        </div>
                        <div class="mmna-field-group">
                            <label>Item Details / Content</label>
                            <input type="text" name="mmna[${mIndex}][items][${itemIdx}][content]" value="" placeholder="Description..." />
                        </div>
                        <div class="mmna-field-group">
                            <label>Item URL</label>
                            <input type="text" name="mmna[${mIndex}][items][${itemIdx}][url]" value="" placeholder="https://..." />
                        </div>
                        <div class="mmna-field-group">
                            <label>Icon</label>
                            <input type="text" name="mmna[${mIndex}][items][${itemIdx}][icon]" value="📌" />
                        </div>
                        <div class="mmna-field-group">
                            <label>DIY Link</label>
                            <input type="text" name="mmna[${mIndex}][items][${itemIdx}][btn3_url]" value="" placeholder="https://..." />
                        </div>
                    </div>
                </div>
            `;
            container.append(itemHtml);
        });

        // Remove Single Item
        $(document).on('click', '.mmna-remove-single-item', function() {
            $(this).closest('.mmna-child-item-box').remove();
        });

        // Icon Picker Modal
        $(document).on('click', '.mmna-icon-picker', function(e) {
            e.preventDefault();
            let index = $(this).data('index');
            let iconInput = $(this).siblings('.mmna-icon-input');

            let icons = {
                // --- SUPPORT ---
                'Handshake': '🤝', 
                'Lifebuoy': '🛟', 
                'FirstAid': '🩹', 
                'ShieldCheck': '🛡️', 
                'Umbrella': '☂️', 
                'HandsHelping': '🤲', 
                'Headset': '🎧', 
                'ChatBubble': '💬', 
                'Pill': '💊', 
                'Hospital': '🏥', 
                'Scale': '⚖️', 
                'Anchor': '⚓',
                'SafetyHelmet': '⛑️',

                // --- COLLABORATE ---
                'Team': '👥', 
                'PuzzlePiece': '🧩', 
                'PeopleGroup': '🧑‍🤝‍🧑', 
                'Briefcase': '💼', 
                'Clipboard': '📋', 
                'Megaphone': '📢', 
                'Network': '🕸️', 
                'ChartBar': '📊', 
                'Workstation': '🖥️', 
                'OfficeBuilding': '🏢', 
                'CoffeeBreak': '☕', 
                'VennDiagram': '🔀',

                // --- GROW ---
                'Seedling': '🌱', 
                'ChartUp': '📈', 
                'DeciduousTree': '🌳', 
                'Rocket': '🚀', 
                'Target': '🎯', 
                'Sun': '☀️', 
                'Sparkles': '✨', 
                'Trophy': '🏆', 
                'Medal': '🏅', 
                'Crown': '👑', 
                'Diamond': '💎', 
                'Fire': '🔥', 
                'Lightbulb': '💡',

                // --- CONNECT ---
                'Globe': '🌐', 
                'Link': '🔗', 
                'Radio': '📻', 
                'Satellite': '🛰️', 
                'Mail': '✉️', 
                'Phone': '📞', 
                'Plug': '🔌', 
                'Compass': '🧭', 
                'Map': '🗺️', 
                'Wifi': '📶', 
                'Key': '🔑', 
                'Door': '🚪',
                'Antenna': '📡',

                // --- AI RELATED ---
                'Robot': '🤖', 
                'Brain': '🧠', 
                'CrystalBall': '🔮', 
                'Microchip': '🔲', 
                'Abacus': '🧮', 
                'SatelliteDish': '📡', 
                'Vortex': '🌀', 
                'Atom': '⚛️', 
                'Joystick': '🕹️', 
                'ComputerMouse': '🖱️', 
                'FloppyDisk': '💾', 
                'CD': '💿'
            };

            let pickerHtml = '<div style="padding:20px; max-height:400px; overflow-y:auto;">';
            pickerHtml += '<h3 style="margin-top:0;">Select an Icon</h3>';
            pickerHtml += '<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(60px,1fr)); gap:8px;">';
            
            for (let iconName in icons) {
                let iconValue = icons[iconName];
                pickerHtml += '<button type="button" class="mmna-icon-option" data-icon="' + iconValue + '" style="padding:10px; border:1px solid #ccc; border-radius:4px; cursor:pointer; font-size:24px; background:#f9f9f9;">' + iconValue + '</button>';
            }
            
            pickerHtml += '</div></div>';

            let modal = jQuery('<div id="mmna-icon-picker-modal" style="position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; border:2px solid #0073aa; border-radius:8px; box-shadow:0 5px 40px rgba(0,0,0,0.16); z-index:9999; max-width:500px; width:90%;">' + pickerHtml + '</div>');
            
            jQuery('body').append(modal);
            jQuery('#mmna-icon-picker-modal').on('click', '.mmna-icon-option', function(e) {
                e.preventDefault();
                iconInput.val(jQuery(this).data('icon'));
                jQuery('#mmna-icon-picker-modal').remove();
            });

            jQuery(document).on('click', function(e) {
                if (!jQuery(e.target).closest('#mmna-icon-picker-modal, .mmna-icon-picker').length) {
                    jQuery('#mmna-icon-picker-modal').remove();
                }
            });
        });
    });
    </script>
    <?php
}

// Helper function to render existing rows on reload
function mmna_render_main_row( $index, $acc ) {
    $type = $acc['type'] ?? 'multi';
    ?>
    <div class="mmna-main-box" data-index="<?php echo $index; ?>" data-type="<?php echo esc_attr( $type ); ?>">
        <div class="mmna-box-header">
            <strong>🛡️ 1st Layer Accordion</strong>
            <button type="button" class="button button-link-delete mmna-remove-main" style="color:#ffb8b8;">Remove Accordion</button>
        </div>
        <div class="mmna-box-body">
            <!-- GLOBAL SETTINGS SECTION -->
            <div class="mmna-section-group">
                <h4 class="mmna-section-title">⚙️ Global Settings</h4>
                <div class="mmna-row-grid">
                    <div class="mmna-field-group">
                        <label>Main Title</label>
                        <input type="text" name="mmna[<?php echo $index; ?>][title]" value="<?php echo esc_attr( $acc['title'] ?? '' ); ?>" />
                    </div>
                    <div class="mmna-field-group">
                        <label>Subtitle (optional)</label>
                        <input type="text" name="mmna[<?php echo $index; ?>][subtitle]" value="<?php echo esc_attr( $acc['subtitle'] ?? '' ); ?>" />
                    </div>
                    <div class="mmna-field-group">
                        <label>Icon (Emoji)</label>
                        <div class="mmna-icon-picker-wrapper">
                            <input type="text" name="mmna[<?php echo $index; ?>][icon]" class="mmna-icon-input" value="<?php echo esc_attr( $acc['icon'] ?? '🛡️' ); ?>" />
                            <button type="button" class="button mmna-icon-picker" data-index="<?php echo $index; ?>" title="Pick Icon">🎯 Pick</button>
                        </div>
                    </div>
                    <div class="mmna-field-group">
                        <label>Icon Background</label>
                        <input type="text" name="mmna[<?php echo $index; ?>][icon_bg]" value="<?php echo esc_attr( $acc['icon_bg'] ?? 'rgba(255,255,255,0.2)' ); ?>" />
                    </div>
                </div>
            </div>

            <!-- TYPE SELECTOR SECTION -->
            <div class="mmna-section-group">
                <h4 class="mmna-section-title">📋 Structure Type</h4>
                <div style="display:flex; gap:15px;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="radio" name="mmna[<?php echo $index; ?>][type]" value="single" 
                            <?php checked( $type, 'single' ); ?> class="mmna-type-toggle" />
                        <span><strong>Single Layer</strong> (Just items, no sub-accordions)</span>
                    </label>
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="radio" name="mmna[<?php echo $index; ?>][type]" value="multi" 
                            <?php checked( $type, 'multi' ); ?> class="mmna-type-toggle" />
                        <span><strong>Multi-Layer</strong> (With sub-accordions)</span>
                    </label>
                </div>
            </div>

            <!-- GLOBAL ACTION LINKS -->
            <div class="mmna-section-group">
                <h4 class="mmna-section-title">🔗 Global Action Links (All Items)</h4>
                <p style="font-size:11px; color:#6b7280; margin:0 0 12px 0;">These links will appear on all items in this accordion:</p>
                <div class="mmna-row-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="mmna-field-group">
                        <label>🎤 Speak with a Live Person</label>
                        <input type="text" name="mmna[<?php echo $index; ?>][btn1_url]" value="<?php echo esc_attr( $acc['btn1_url'] ?? '' ); ?>" placeholder="https://example.com/live-chat" />
                    </div>
                    <div class="mmna-field-group">
                        <label>👤 Hire an Expert</label>
                        <input type="text" name="mmna[<?php echo $index; ?>][btn2_url]" value="<?php echo esc_attr( $acc['btn2_url'] ?? '' ); ?>" placeholder="https://example.com/hire-expert" />
                    </div>
                </div>
            </div>

            <!-- SUB-ACCORDIONS SECTION -->
            <div class="mmna-sub-section" style="display: <?php echo $type === 'multi' ? 'block' : 'none'; ?>;">
                <hr style="border:0; border-top:1px solid #e2e4e7; margin:20px 0;">
                <h4 style="margin:0 0 10px 0; color:#2c3338;">📁 Sub-Accordions (2nd Layer)</h4>
                <div class="mmna-sub-container">
                    <?php if ( ! empty( $acc['subs'] ) ) : foreach ( $acc['subs'] as $subIndex => $sub ) : ?>
                        <div class="mmna-sub-accordion-box" data-sub-index="<?php echo $subIndex; ?>">
                            <div class="mmna-sub-header">
                                <span>📁 Sub-Accordion Item</span>
                                <button type="button" class="button-link-delete mmna-remove-sub">Remove Sub-Accordion</button>
                            </div>
                            <div class="mmna-row-grid sub-grid">
                                <div class="mmna-field-group">
                                    <label>Sub-Accordion Title</label>
                                    <input type="text" name="mmna[<?php echo $index; ?>][subs][<?php echo $subIndex; ?>][title]" value="<?php echo esc_attr( $sub['title'] ?? '' ); ?>" />
                                </div>
                                <div class="mmna-field-group">
                                    <label>Description</label>
                                    <textarea name="mmna[<?php echo $index; ?>][subs][<?php echo $subIndex; ?>][desc]"><?php echo esc_textarea( $sub['desc'] ?? '' ); ?></textarea>
                                </div>
                                <div class="mmna-field-group">
                                    <label>Icon</label>
                                    <input type="text" name="mmna[<?php echo $index; ?>][subs][<?php echo $subIndex; ?>][icon]" value="<?php echo esc_attr( $sub['icon'] ?? '🎓' ); ?>" />
                                </div>
                                <div class="mmna-field-group">
                                    <label>Icon BG</label>
                                    <input type="text" name="mmna[<?php echo $index; ?>][subs][<?php echo $subIndex; ?>][icon_bg]" value="<?php echo esc_attr( $sub['icon_bg'] ?? '#ede9fe' ); ?>" />
                                </div>
                                <div class="mmna-field-group">
                                    <label>Icon Color</label>
                                    <input type="text" name="mmna[<?php echo $index; ?>][subs][<?php echo $subIndex; ?>][icon_color]" value="<?php echo esc_attr( $sub['icon_color'] ?? '#7c3aed' ); ?>" />
                                </div>
                            </div>

                            <div style="margin-top:15px; padding-top:10px; border-top:1px dashed #ccd0d4;">
                                <h5 style="margin:0 0 8px 0; color:#50575e;">📄 Child Items Inside This Sub-Accordion</h5>
                                <div class="mmna-child-container">
                                    <?php if ( ! empty( $sub['children'] ) ) : foreach ( $sub['children'] as $cIndex => $child ) : ?>
                                        <div class="mmna-child-item-box">
                                            <div class="mmna-child-header">
                                                <span>🔹 Child Item</span>
                                                <button type="button" class="button-link-delete mmna-remove-child" style="color:#b32d2e;">Remove</button>
                                            </div>
                                            <div class="mmna-row-grid" style="grid-template-columns: 1.5fr 2fr 1fr 0.8fr 1fr; margin-bottom:0;">
                                                <div class="mmna-field-group">
                                                    <label>Item Title</label>
                                                    <input type="text" name="mmna[<?php echo $index; ?>][subs][<?php echo $subIndex; ?>][children][<?php echo $cIndex; ?>][title]" value="<?php echo esc_attr( $child['title'] ?? '' ); ?>" />
                                                </div>
                                                <div class="mmna-field-group">
                                                    <label>Item Details / Content</label>
                                                    <input type="text" name="mmna[<?php echo $index; ?>][subs][<?php echo $subIndex; ?>][children][<?php echo $cIndex; ?>][content]" value="<?php echo esc_attr( $child['content'] ?? '' ); ?>" />
                                                </div>
                                                <div class="mmna-field-group">
                                                    <label>Item URL</label>
                                                    <input type="text" name="mmna[<?php echo $index; ?>][subs][<?php echo $subIndex; ?>][children][<?php echo $cIndex; ?>][url]" value="<?php echo esc_attr( $child['url'] ?? '' ); ?>" placeholder="https://example.com" />
                                                </div>
                                                <div class="mmna-field-group">
                                                    <label>Icon</label>
                                                    <input type="text" name="mmna[<?php echo $index; ?>][subs][<?php echo $subIndex; ?>][children][<?php echo $cIndex; ?>][icon]" value="<?php echo esc_attr( $child['icon'] ?? '📌' ); ?>" />
                                                </div>
                                                <div class="mmna-field-group">
                                                    <label>DIY Link</label>
                                                    <input type="text" name="mmna[<?php echo $index; ?>][subs][<?php echo $subIndex; ?>][children][<?php echo $cIndex; ?>][btn3_url]" value="<?php echo esc_attr( $child['btn3_url'] ?? '' ); ?>" placeholder="https://example.com/diy" />
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; endif; ?>
                                </div>
                                <button type="button" class="button button-small mmna-add-child" data-main-index="<?php echo $index; ?>" data-sub-index="<?php echo $subIndex; ?>" style="margin-top:8px;">+ Add Child Item</button>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
                <button type="button" class="button mmna-add-sub" data-main-index="<?php echo $index; ?>" style="margin-top:10px;">+ Add Sub-Accordion</button>
            </div>

            <!-- SINGLE LAYER ITEMS - ONLY SHOW FOR SINGLE-LAYER -->
            <div class="mmna-single-items-section" style="display: <?php echo $type === 'single' ? 'block' : 'none'; ?>;">
                <hr style="border:0; border-top:1px solid #e2e4e7; margin:20px 0;">
                <h4 style="margin:0 0 10px 0; color:#2c3338;">📝 Direct Items</h4>
                <div class="mmna-single-items-container">
                    <?php if ( ! empty( $acc['items'] ) ) : foreach ( $acc['items'] as $itemIdx => $item ) : ?>
                        <div class="mmna-child-item-box">
                            <div class="mmna-child-header">
                                <span>🔹 Item</span>
                                <button type="button" class="button-link-delete mmna-remove-single-item" style="color:#b32d2e;">Remove</button>
                            </div>
                            <div class="mmna-row-grid single-item-grid" style="margin-bottom:0;">
                                <div class="mmna-field-group">
                                    <label>Item Title</label>
                                    <input type="text" name="mmna[<?php echo $index; ?>][items][<?php echo $itemIdx; ?>][title]" value="<?php echo esc_attr( $item['title'] ?? '' ); ?>" />
                                </div>
                                <div class="mmna-field-group">
                                    <label>Item Details / Content</label>
                                    <input type="text" name="mmna[<?php echo $index; ?>][items][<?php echo $itemIdx; ?>][content]" value="<?php echo esc_attr( $item['content'] ?? '' ); ?>" />
                                </div>
                                <div class="mmna-field-group">
                                    <label>Item URL</label>
                                    <input type="text" name="mmna[<?php echo $index; ?>][items][<?php echo $itemIdx; ?>][url]" value="<?php echo esc_attr( $item['url'] ?? '' ); ?>" placeholder="https://example.com" />
                                </div>
                                <div class="mmna-field-group">
                                    <label>Icon</label>
                                    <input type="text" name="mmna[<?php echo $index; ?>][items][<?php echo $itemIdx; ?>][icon]" value="<?php echo esc_attr( $item['icon'] ?? '📌' ); ?>" />
                                </div>
                                <div class="mmna-field-group">
                                    <label>DIY Link</label>
                                    <input type="text" name="mmna[<?php echo $index; ?>][items][<?php echo $itemIdx; ?>][btn3_url]" value="<?php echo esc_attr( $item['btn3_url'] ?? '' ); ?>" placeholder="https://example.com/diy" />
                                </div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
                <button type="button" class="button mmna-add-single-item" data-main-index="<?php echo $index; ?>" style="margin-top:10px;">+ Add Direct Item</button>
            </div>
        </div>
    </div>
    <?php
}

// 4. Save Meta Data securely
function mmna_save_accordion_meta( $post_id ) {
    if ( ! isset( $_POST['mmna_nonce'] ) || ! wp_verify_nonce( $_POST['mmna_nonce'], 'mmna_save_accordion_data' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( isset( $_POST['mmna'] ) ) {
        $cleaned_data = wp_unslash( $_POST['mmna'] );
        update_post_meta( $post_id, '_mmna_accordion_data', $cleaned_data );
    } else {
        delete_post_meta( $post_id, '_mmna_accordion_data' );
    }
}
add_action( 'save_post_mm_accordion', 'mmna_save_accordion_meta' );

// 5. Shortcode Output for Multi-Tier Hierarchy Rendering
function mmna_accordion_shortcode( $atts ) {
    $atts = shortcode_atts( ['id' => 0], $atts, 'mm_nested_accordion' );
    $post_id = intval( $atts['id'] );
    if ( ! $post_id ) {
        return '<p>Invalid Accordion ID.</p>';
    }

    $accordions = get_post_meta( $post_id, '_mmna_accordion_data', true );
    if ( empty( $accordions ) || ! is_array( $accordions ) ) {
        return '<p>No accordion configurations found.</p>';
    }

    ob_start();
    ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <div class="custom-accordion-wrapper">
        <?php foreach ( $accordions as $acc ) : 
            $type = $acc['type'] ?? 'multi';
            $icon_html = mmna_render_icon( $acc['icon'] ?? '🛡️' );
            $btn1_url = $acc['btn1_url'] ?? '';
            $btn2_url = $acc['btn2_url'] ?? '';
            
            $has_content = $type === 'multi' ? ( ! empty( $acc['subs'] ) && is_array( $acc['subs'] ) ) : ( ! empty( $acc['items'] ) && is_array( $acc['items'] ) );
        ?>
            <div class="main-accordion-item" data-type="<?php echo esc_attr( $type ); ?>">
                <button class="main-accordion-header <?php echo ! $has_content ? 'no-content' : ''; ?>" type="button">
                    <div class="header-left">
                        <span class="icon-box main-icon" style="background: <?php echo esc_attr( $acc['icon_bg'] ?? 'rgba(255,255,255,0.2)' ); ?>;"><?php echo $icon_html; ?></span>
                        <span class="header-title">
                            <?php echo esc_html( $acc['title'] ?? '' ); ?>
                            <?php if ( ! empty( $acc['subtitle'] ) ) : ?>
                                <small class="subtitle-text"><?php echo esc_html( $acc['subtitle'] ); ?></small>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if ( $has_content ) : ?>
                        <span class="arrow-icon">▼</span>
                    <?php endif; ?>
                </button>

                <?php if ( $type === 'multi' && $has_content ) : ?>
                    <!-- MULTI-LAYER CONTENT -->
                    <div class="main-accordion-content">
                        <div class="content-inner">
                            <?php foreach ( $acc['subs'] as $sub ) : 
                                $has_children = ! empty( $sub['children'] ) && is_array( $sub['children'] );
                                $sub_icon_html = mmna_render_icon( $sub['icon'] ?? '🎓' );
                            ?>
                                <div class="sub-accordion-item">
                                    <div class="sub-accordion-header-wrap <?php echo $has_children ? 'has-child-toggle' : ''; ?>">
                                        <div class="header-left">
                                            <span class="icon-box sub-icon" style="background: <?php echo esc_attr( $sub['icon_bg'] ?? '#ede9fe' ); ?>; color: <?php echo esc_attr( $sub['icon_color'] ?? '#7c3aed' ); ?>;"><?php echo $sub_icon_html; ?></span>
                                            <div class="sub-title-group">
                                                <span class="sub-title"><?php echo esc_html( $sub['title'] ?? '' ); ?></span>
                                                <?php if ( ! empty( $sub['desc'] ) ) : ?>
                                                    <p><?php echo nl2br( esc_html( $sub['desc'] ) ); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if ( $has_children ) : ?>
                                            <span class="sub-arrow">›</span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ( $has_children ) : ?>
                                        <div class="sub-accordion-children">
                                            <div class="content-inner sub-items">
                                            <?php foreach ( $sub['children'] as $child ) : 
                                                $child_btn1_url = ! empty( $child['btn1_url'] ) ? $child['btn1_url'] : $btn1_url;
                                                $child_btn2_url = ! empty( $child['btn2_url'] ) ? $child['btn2_url'] : $btn2_url;
                                                $child_btn3_url = $child['btn3_url'] ?? '';
                                            ?>
                                                <div class="child-item-row">
                                                    <span class="child-icon"><?php echo mmna_render_icon( $child['icon'] ?? '📌' ); ?></span>
                                                    <div class="child-text">
                                                        <?php if ( ! empty( $child['url'] ) ) : ?>
                                                            <strong><a href="<?php echo esc_url( $child['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $child['title'] ?? '' ); ?></a></strong>
                                                        <?php else : ?>
                                                            <strong><?php echo esc_html( $child['title'] ?? '' ); ?></strong>
                                                        <?php endif; ?>
                                                        <?php if ( ! empty( $child['content'] ) ) : ?>
                                                            <span><?php echo esc_html( $child['content'] ); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="item-actions">
                                                        <?php if ( ! empty( $child_btn1_url ) ) : ?>
                                                            <a href="<?php echo esc_url( $child_btn1_url ); ?>" class="action-btn btn-live" target="_blank" rel="noopener noreferrer">
                                                                <i class="bi bi-telephone-fill"></i>
                                                                <span>Speak to a live person</span>
                                                            </a>
                                                        <?php else : ?>
                                                            <span class="action-btn btn-live disabled">
                                                                <i class="bi bi-telephone-fill"></i>
                                                                <span>Speak to a live person</span>
                                                            </span>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ( ! empty( $child_btn2_url ) ) : ?>
                                                            <a href="<?php echo esc_url( $child_btn2_url ); ?>" class="action-btn btn-expert" target="_blank" rel="noopener noreferrer">
                                                                <i class="bi-person-fill-check bi"></i>
                                                                <span>Hire an expert</span>
                                                            </a>
                                                        <?php else : ?>
                                                            <span class="action-btn btn-expert disabled">
                                                                <i class="bi-person-fill-check bi"></i>
                                                                <span>Hire an expert</span>
                                                            </span>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ( ! empty( $child_btn3_url ) ) : ?>
                                                            <a href="<?php echo esc_url( $child_btn3_url ); ?>" class="action-btn btn-diy" target="_blank" rel="noopener noreferrer">
                                                                <i class="bi bi-tools"></i>
                                                                <span>Do It Yourself</span>
                                                            </a>
                                                        <?php else : ?>
                                                            <span class="action-btn btn-diy disabled">
                                                                <i class="bi bi-tools"></i>
                                                                <span>Do It Yourself</span>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                <?php elseif ( $type === 'single' && $has_content ) : ?>
                    <!-- SINGLE-LAYER CONTENT -->
                    <div class="main-accordion-content">
                        <div class="content-inner single-items">
                            <?php foreach ( $acc['items'] as $item ) : 
                                // Get URLs for this item - always use global if item doesn't have specific ones
                                $item_btn1_url = ! empty( $item['btn1_url'] ) ? $item['btn1_url'] : $btn1_url;
                                $item_btn2_url = ! empty( $item['btn2_url'] ) ? $item['btn2_url'] : $btn2_url;
                                $item_btn3_url = $item['btn3_url'] ?? '';
                            ?>
                                <div class="single-item-row">
                                    <span class="item-icon"><?php echo mmna_render_icon( $item['icon'] ?? '📌' ); ?></span>
                                    <div class="item-text">
                                        <?php if ( ! empty( $item['url'] ) ) : ?>
                                            <strong><a href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $item['title'] ?? '' ); ?></a></strong>
                                        <?php else : ?>
                                            <strong><?php echo esc_html( $item['title'] ?? '' ); ?></strong>
                                        <?php endif; ?>
                                        <?php if ( ! empty( $item['content'] ) ) : ?>
                                            <span><?php echo esc_html( $item['content'] ); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-actions">
                                        <?php if ( ! empty( $item_btn1_url ) ) : ?>
                                            <a href="<?php echo esc_url( $item_btn1_url ); ?>" class="action-btn btn-live" target="_blank" rel="noopener noreferrer">
                                                <i class="bi bi-telephone-fill"></i>
                                                <span>Speak to a live person</span>
                                            </a>
                                        <?php else : ?>
                                            <span class="action-btn btn-live disabled">
                                                <i class="bi bi-telephone-fill"></i>
                                                <span>Speak to a live person</span>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ( ! empty( $item_btn2_url ) ) : ?>
                                            <a href="<?php echo esc_url( $item_btn2_url ); ?>" class="action-btn btn-expert" target="_blank" rel="noopener noreferrer">
                                                <i class="bi-person-fill-check bi"></i>
                                                <span>Hire an expert</span>
                                            </a>
                                        <?php else : ?>
                                            <span class="action-btn btn-expert disabled">
                                                <i class="bi-person-fill-check bi"></i>
                                                <span>Hire an expert</span>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ( ! empty( $item_btn3_url ) ) : ?>
                                            <a href="<?php echo esc_url( $item_btn3_url ); ?>" class="action-btn btn-diy" target="_blank" rel="noopener noreferrer">
                                                <i class="bi bi-tools"></i>
                                                <span>Do It Yourself</span>
                                            </a>
                                        <?php else : ?>
                                            <span class="action-btn btn-diy disabled">
                                                <i class="bi bi-tools"></i>
                                                <span>Do It Yourself</span>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <style>
    .custom-accordion-wrapper { margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    .main-accordion-item { background: #fff; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 8px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.01); }
    .main-accordion-header { width: 100%; background: #ededed; background: linear-gradient(0deg, rgba(237, 237, 237, 1) 0%, rgba(223, 197, 157, 1) 50%, rgba(177, 145, 96, 1) 100%); color: #fff; padding: 8px 16px; border: none; display: flex; justify-content: space-between; align-items: center; cursor: pointer; text-align: left; font-size: 15px; font-weight: 600; }
    .main-accordion-header.no-content { cursor: default; }
    .header-left { display: flex; align-items: center; gap: 25px; flex: 1; }
    .icon-box {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 30px;
        flex-shrink: 0;
    }
    .header-title { font-size: 20px; color: #2c3338; }
    .header-title small.subtitle-text { display: block; font-size: 11px; font-weight: 400; color: #d8b4fe; margin-top: 0px; }
    .main-accordion-content { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-in-out; background: #fafafa; }
    .content-inner { padding: 4px; }
    .content-inner.single-items { padding: 4px 15px; }
    .content-inner.sub-items { padding: 4px 15px; }
    .main-accordion-item.active .main-accordion-header .arrow-icon { transform: rotate(180deg); }
    .main-accordion-header .arrow-icon { transition: transform 0.3s ease; font-weight: bold; font-size: 20px; color: rgba(143, 104, 61, 1); }
    
    .sub-accordion-item { background: #fff; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 8px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.01); }
    .sub-accordion-header-wrap { display: flex; justify-content: space-between; align-items: center; width: 100%; background: #f3f4f6; color: #1f2937; padding: 10px 16px; text-align: left; border-bottom: 1px solid #e5e7eb; }
    .sub-accordion-header-wrap.has-child-toggle { cursor: pointer; }
    .sub-title { font-size: 14px; font-weight: 600; color: #1f2937; display: block; }
    .sub-title-group { flex: 1; }
    .sub-title-group p { margin: 2px 0 0 0; font-size: 11px; font-weight: 400; color: #6b7280; line-height: 1.3; }
    .sub-arrow { font-size: 16px; font-weight: bold; color: #6b7280; transition: transform 0.3s ease; }
    
    /* Single layer items styling */
    .single-item-row {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 4px 0;
        font-size: 15px;
        color: #4b5563;
        border-bottom: 1px solid #f0f0f0;
    }
    .single-item-row:last-child { border-bottom: none; }
    .item-text { flex: 1; }
    .item-text strong { color: #1f2937; display: block; font-size: 15px; }
    .item-text strong a { color: #1f2937; text-decoration: none; }
    .item-text strong a:hover { text-decoration: underline; color: #5b21b6; }
    .item-text span { color: #6b7280; display: block; }
    .item-icon { font-size: 15px; min-width: 20px; flex-shrink: 0; }
    .item-actions { display: flex; gap: 8px; flex-shrink: 0; flex-wrap: wrap; }
    .action-btn { display: inline-flex; justify-content: center; gap: 6px; padding: 0px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; transition: all 0.2s ease; white-space: nowrap; border: 1px solid; }
    .action-btn i { width: 15px; height: 14px; flex-shrink: 0; }
    .action-btn span { font-size: 15px; font-weight: 500; }
    .action-btn.disabled { opacity: 0.5; cursor: not-allowed; }
    .action-btn.btn-live { background-color: #cffafe; border-color: #06b6d4; color: #0e7490; }
    .action-btn.btn-live:hover:not(.disabled) { background-color: #a5f3fc; border-color: #0891b2; }
    .action-btn.btn-expert { background-color: #f3e8ff; border-color: #c084fc; color: #6d28d9; }
    .action-btn.btn-expert:hover:not(.disabled) { background-color: #ede9fe; border-color: #a855f7; }
    .action-btn.btn-diy { background-color: #dcfce7; border-color: #86efac; color: #16a34a; }
    .action-btn.btn-diy:hover:not(.disabled) { background-color: #bbf7d0; border-color: #4ade80; }
    
    /* Multi layer items styling */
    .sub-accordion-children { max-height: 0; overflow: hidden; transition: max-height 0.3s ease-in-out; background: #fafafa; }
    .sub-accordion-item.child-active .sub-accordion-children { max-height: 500px; }
    .sub-accordion-item.child-active .sub-arrow { transform: rotate(180deg); }
    .child-item-row { 
        display: flex; 
        align-items: center; 
        gap: 20px; 
        padding: 4px 0; 
        font-size: 15px; 
        color: #4b5563; 
        border-bottom: 1px solid #f0f0f0; 
    }
    .child-item-row:last-child { border-bottom: none; }
    .child-text { flex: 1; }
    .child-text strong { color: #1f2937; display: block; font-size: 15px; }
    .child-text strong a { color: #1f2937; text-decoration: none; }
    .child-text strong a:hover { text-decoration: underline; color: #5b21b6; }
    .child-text span { font-size: 15px; color: #6b7280; }
    .child-icon { font-size: 15px; min-width: 20px; flex-shrink: 0; }
    </style>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        function calculateContentHeight(contentElement) {
            const contentInner = contentElement.querySelector('.content-inner');
            if (!contentInner) return contentElement.scrollHeight;
            
            const childrenSections = contentInner.querySelectorAll('.sub-accordion-children');
            const originalChildHeights = [];
            childrenSections.forEach(child => {
                originalChildHeights.push(child.style.maxHeight);
                child.style.maxHeight = 'none';
            });
            
            const originalMaxHeight = contentElement.style.maxHeight;
            contentElement.style.maxHeight = 'none';
            
            const height = contentInner.scrollHeight + 12;
            
            contentElement.style.maxHeight = originalMaxHeight;
            childrenSections.forEach((child, index) => {
                child.style.maxHeight = originalChildHeights[index];
            });
            
            return height;
        }

        document.querySelectorAll('.main-accordion-header').forEach(header => {
            const parent = header.parentElement;
            const content = parent.querySelector('.main-accordion-content');
            
            if (content) {
                header.addEventListener('click', function() {
                    const wrapper = parent.closest('.custom-accordion-wrapper');
                    const isCurrentlyActive = parent.classList.contains('active');
                    
                    if (!isCurrentlyActive) {
                        wrapper.querySelectorAll('.main-accordion-item.active').forEach(activeItem => {
                            if (activeItem !== parent) {
                                activeItem.classList.remove('active');
                                const activeContent = activeItem.querySelector('.main-accordion-content');
                                if (activeContent) {
                                    activeContent.style.maxHeight = null;
                                }
                            }
                        });
                    }
                    
                    parent.classList.toggle('active');
                    if (parent.classList.contains('active')) {
                        const calcHeight = calculateContentHeight(content);
                        content.style.maxHeight = calcHeight + "px";
                    } else {
                        content.style.maxHeight = null;
                    }
                });
                header.style.cursor = 'pointer';
            }
        });

        document.querySelectorAll('.sub-accordion-header-wrap.has-child-toggle').forEach(wrap => {
            wrap.addEventListener('click', function() {
                const subItem = this.parentElement;
                subItem.classList.toggle('child-active');
                
                const mainContent = subItem.closest('.main-accordion-content');
                const mainParent = mainContent.parentElement;
                
                if (mainParent && mainParent.classList.contains('active')) {
                    requestAnimationFrame(() => {
                        setTimeout(() => {
                            const calcHeight = calculateContentHeight(mainContent);
                            mainContent.style.maxHeight = calcHeight + "px";
                        }, 0);
                    });
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'mm_nested_accordion', 'mmna_accordion_shortcode' );

// Enqueue frontend styles for icon rendering
function mmna_enqueue_frontend_styles() {
    wp_enqueue_style( 'font-awesome-free', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css' );
    wp_enqueue_style( 'bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css' );
}
add_action( 'wp_enqueue_scripts', 'mmna_enqueue_frontend_styles' );

// Helper function to render icons
function mmna_render_icon( $icon_value ) {
    return esc_html( $icon_value );
}
