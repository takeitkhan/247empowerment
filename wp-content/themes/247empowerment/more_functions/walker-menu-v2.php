<?php
/**
 * Custom Menu Walker - Version 2
 * 3-Level Nested Menu with Custom Styling
 * For Primary Menu Location Only
 */

class MM_Walker_Nav_Menu_V2 extends Walker_Nav_Menu {
    private function relative_url($url) {
        $path = wp_make_link_relative($url);
        return untrailingslashit($path) ?: '/';
    }

    /* START SUBMENU */
    public function start_lvl(&$output, $depth = 0, $args = null) {
        $indent = str_repeat("\t", $depth);
        
        // Level 1 menu list
        if ($depth === 0) {
            $class = 'primary-menu-dropdown';
        }
        // Level 2+ menu list
        else {
            $class = 'primary-submenu-dropdown';
        }
        
        $output .= "\n$indent<ul class=\"$class\">\n";
    }

    /* START ITEM */
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        $classes = empty($item->classes) ? [] : (array) $item->classes;
        $has_children = in_array('menu-item-has-children', $classes, true);

        /* ACTIVE CHECK */
        global $wp;
        $current_url  = home_url(add_query_arg([], $wp->request));
        $menu_path    = $this->relative_url($item->url);
        $current_path = $this->relative_url($current_url);

        $is_active = (
            $menu_path === $current_path ||
            in_array('current-menu-item', $classes, true) ||
            in_array('current-menu-ancestor', $classes, true)
        );

        /* <li> CLASSES */
        $li_classes = [];

        // Level 1 items
        if ($depth === 0) {
            $li_classes[] = 'nav-item';
            if ($has_children) {
                $li_classes[] = 'primary-dropdown';
            }
        }
        // Level 2+ items
        else {
            if ($has_children) {
                $li_classes[] = 'primary-submenu';
            }
        }

        if ($is_active) {
            $li_classes[] = 'active';
        }

        /* <a> CLASSES */
        $link_classes = [];
        
        if ($depth === 0) {
            $link_classes[] = 'nav-link';
        }

        if ($has_children) {
            $link_classes[] = 'primary-toggle';
        }

        /* LINK ATTRIBUTES */
        $atts = '';
        $atts .= ' class="' . esc_attr(implode(' ', $link_classes)) . '"';

        if ($has_children) {
            $atts .= ' href="javascript:void(0)"';
        } else {
            $atts .= ' href="' . esc_url($item->url) . '"';
        }

        if ($is_active) {
            $atts .= ' aria-current="page"';
        }

        /* OUTPUT */
        $output .= '<li' . ($li_classes ? ' class="' . esc_attr(implode(' ', $li_classes)) . '"' : '') . '>';
        $output .= '<a' . $atts . '>';
        $output .= esc_html($item->title);
        
        // Add arrow for items with children
        if ($has_children) {
            if ($depth === 0) {
                $output .= ' <span class="menu-arrow">▼</span>';
            } else {
                $output .= ' <span class="menu-arrow">▶</span>';
            }
        }
        
        $output .= '</a>';
    }

    /* END ITEM */
    public function end_el(&$output, $item, $depth = 0, $args = null) {
        $output .= "</li>\n";
    }

    /* END SUBMENU */
    public function end_lvl(&$output, $depth = 0, $args = null) {
        $indent = str_repeat("\t", $depth);
        $output .= "$indent</ul>\n";
    }
}
