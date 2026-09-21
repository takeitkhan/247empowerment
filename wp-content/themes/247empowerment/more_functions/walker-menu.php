<?php

function register_my_menus()
{
    register_nav_menus([
        'primary' => __('Primary Menu', 'mm'),
        'portalmenu' => __('Portal Menu', 'mm'),
        'portalmobilemenu' => __('Portal Mobile Menu', 'mm'),
        'secondary' => __('Footer Menu', 'mm'),
        'authentication' => __('Authentication Menu', 'mm'),
        'profilemenu' => __('Profile Menu', 'mm'),
        'editprofilemenu' => __('Edit Profile Menu', 'mm'),
        'portalmegamenu' => __('Portal Mega Menu', 'mm'),
        'megarightmenu' => __('Mega Right Menu', 'mm'),
        'zoommenu' => __('Zoom Menu', 'mm'),
    ]);
}
add_action('after_setup_theme', 'register_my_menus');


acf_add_local_field_group([
    'key' => 'menu_icons_group',
    'title' => 'Menu Icon',
    'fields' => [
        [
            'key' => 'field_menu_icon_image',
            'label' => 'Icon Image',
            'name' => 'menu_icon_image',
            'type' => 'image',
            'return_format' => 'url',
        ],
        [
            'key' => 'field_menu_icon_class',
            'label' => 'Icon Size Class (optional)',
            'name' => 'menu_icon_class',
            'type' => 'text',
            'placeholder' => 'e.g., img24 or img20',
        ],
    ],
    'location' => [
        [
            [
                'param' => 'nav_menu_item',
                'operator' => '==',
                'value' => 'all',
            ],
        ],
    ],
]);


class MM_Walker_Nav_Menu extends Walker_Nav_Menu
{
    private function relative_url($url)
    {
        $path = wp_make_link_relative($url);
        return untrailingslashit($path) ?: '/';
    }

    /* START SUBMENU */
    public function start_lvl(&$output, $depth = 0, $args = null)
    {
        $indent = str_repeat("\t", $depth);
        $submenu_class = $depth === 0 ? 'dropdown-menu' : 'dropdown-menu dropdown-submenu';
        $output .= "\n$indent<ul class=\"$submenu_class\">\n";
    }

    /* START ITEM */
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
    {
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
        $li_classes = ['nav-item'];

        if ($depth === 0 && $has_children) {
            $li_classes[] = 'dropdown';
        } elseif ($depth > 0 && $has_children) {
            $li_classes[] = 'dropdown-submenu';
        }

        if ($is_active) {
            $li_classes[] = 'active';
        }

        /* <a> CLASSES */
        $link_classes = $depth === 0 ? ['nav-link'] : ['dropdown-item'];

        if ($has_children) {
            $link_classes[] = 'dropdown-toggle';
        }

        /* LINK ATTRIBUTES */
        $atts = '';
        $atts .= ' class="' . esc_attr(implode(' ', $link_classes)) . '"';

        if ($has_children) {
            // IMPORTANT: prevent navigation on toggle
            $atts .= ' href="javascript:void(0)"';
        } else {
            $atts .= ' href="' . esc_url($item->url) . '"';
        }

        if ($is_active) {
            $atts .= ' aria-current="page"';
        }

        /* OUTPUT */
        $output .= '<li class="' . esc_attr(implode(' ', $li_classes)) . '">';
        $output .= '<a' . $atts . '>';
        $output .= esc_html($item->title);
        $output .= '</a>';
    }

    /* END ITEM */
    public function end_el(&$output, $item, $depth = 0, $args = null)
    {
        $output .= "</li>\n";
    }

    /* END SUBMENU */
    public function end_lvl(&$output, $depth = 0, $args = null)
    {
        $output .= "</ul>\n";
    }
}



class MM_Footer_Walker_Nav_Menu extends Walker_Nav_Menu
{
    function start_lvl(&$output, $depth = 0, $args = null)
    {
        // No submenus for footer — skip
    }

    function end_lvl(&$output, $depth = 0, $args = null)
    {
        // No submenus for footer — skip
    }

    function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
    {
        $output .= '<li class="footer-list-inline-item list-inline-item">';

        $atts = [
            'href'  => !empty($item->url) ? esc_url($item->url) : '#',
            'class' => 'text-white text-decoration-none',
        ];

        $attributes = '';
        foreach ($atts as $attr => $value) {
            $attributes .= ' ' . $attr . '="' . esc_attr($value) . '"';
        }

        $title = apply_filters('the_title', $item->title, $item->ID);
        $output .= '<a' . $attributes . '>' . esc_html($title) . '</a>';
    }

    function end_el(&$output, $item, $depth = 0, $args = null)
    {
        $output .= "</li>\n";
    }
}

class MM_Auth_Walker_Nav_Menu extends Walker_Nav_Menu
{
    function start_lvl(&$output, $depth = 0, $args = null) {}
    function end_lvl(&$output, $depth = 0, $args = null) {}

    function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
    {
        // Detect "Sign In" or "Sign Up"
        $title_lower = strtolower($item->title);
        $is_sign_in = strpos($title_lower, 'sign in') !== false;
        $is_sign_up = strpos($title_lower, 'sign up') !== false;

        // Assign classes
        $classes = '';
        if ($is_sign_in) {
            $classes = 'btn btn-outline-primary btn-sm px-4';
        } elseif ($is_sign_up) {
            $classes = 'me-2 text-decoration-none';
        } else {
            $classes = 'text-decoration-none';
        }

        $output .= '<li class="list-inline-item">';
        $output .= '<a href="' . esc_url($item->url) . '" class="' . esc_attr($classes) . '">'
            . esc_html($item->title) . '</a>';
    }

    function end_el(&$output, $item, $depth = 0, $args = null)
    {
        $output .= '</li>';
    }
}

function mm_relative_url($url) {
    $path = parse_url($url, PHP_URL_PATH) ?? '';
    $path = trim($path, '/');

    return '/' . $path;
}

class Image_Icon_Walker_Nav_Menu extends Walker_Nav_Menu
{
    function start_el(&$output, $item, $depth = 0, $args = [], $id = 0)
    {
        $icon_url   = get_field('menu_icon_image', $item);
        $icon_class = get_field('menu_icon_class', $item) ?: 'img24';

        // Ensure we get the ID safely and it's not pointing to the homepage/front page
        $mega_menu_id = absint(get_field('mega_menu_template', $item));
        
        $current_user = wp_get_current_user();
        $username = $current_user->user_nicename ?: 'guest';
        $url = str_replace('$username', $username, $item->url);

        $item_class = 'nav-item ' . ($mega_menu_id ? ' has-mega-menu' : '');

        $output .= '<li class="' . esc_attr($item_class) . '">';
        $output .= '<a href="' . esc_url($url) . '" class="d-flex align-items-baseline gap-2 nav-link">';

        if ($icon_url) {
            $output .= '<div class="' . esc_attr($icon_class) . '">';
            $output .= '<img class="w-100 h-100 object-fit-contain" src="' . esc_url($icon_url) . '" alt="">';
            $output .= '</div>';
        }

        $output .= esc_html($item->title);
        $output .= '</a>';

        // Render Mega Menu only if a valid, non-home post ID is found
        if ($mega_menu_id && get_option('page_on_front') != $mega_menu_id) {
            $output .= '<div class="mega-menu-content container">';
            
            $page = get_post($mega_menu_id);
            if ($page) {
                // Safely parse Gutenberg blocks without breaking the global loop state
                $output .= do_blocks($page->post_content);
            }
            
            $output .= '</div>';
        }

        $output .= '</li>';
    }
}

// class Image_Icon_Walker_Nav_Menu extends Walker_Nav_Menu
// {
//     function start_el(&$output, $item, $depth = 0, $args = [], $id = 0)
//     {
//         $icon_url   = get_field('menu_icon_image', $item);
//         $icon_class = get_field('menu_icon_class', $item) ?: 'img24';

//         $mega_menu_id = get_field('mega_menu_template', $item->ID);

//         $current_user = wp_get_current_user();
//         $username = $current_user->user_nicename ?: 'guest';
//         $url = str_replace('$username', $username, $item->url);

//         $item_class = 'nav-item ' . ($mega_menu_id ? ' has-mega-menu' : '');

//         $output .= '<li class="' . esc_attr($item_class) . '">';
//         $output .= '<a href="' . esc_url($url) . '" class="d-flex align-items-baseline gap-2 nav-link">';

//         if ($icon_url) {
//             $output .= '<div class="' . esc_attr($icon_class) . '">';
//             $output .= '<img class="w-100 h-100 object-fit-contain" src="' . esc_url($icon_url) . '" alt="">';
//             $output .= '</div>';
//         }

//         $output .= esc_html($item->title);
//         $output .= '</a>';

//         if ($mega_menu_id) {
//             $output .= '<div class="mega-menu-content container">';
            
//             // Use Gutenberg page content instead of Elementor
//             $page = get_post($mega_menu_id);
//             if ($page) {
//                 $output .= apply_filters('the_content', $page->post_content);
//             }
            
//             $output .= '</div>';
//         }

//         $output .= '</li>';
//     }

// }

class Profile_Menu_Walker extends Walker_Nav_Menu
{

    function start_lvl(&$output, $depth = 0, $args = null)
    {
        $output .= "\n<ul class=\"nav d-flex flex-column gap-2\">\n";
    }

    function end_lvl(&$output, $depth = 0, $args = null)
    {
        $output .= "</ul>\n";
    }

    function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
    {
        // 🔥 Detect active item
        $active_class = '';
        if (
            in_array('current-menu-item', $item->classes) ||
            in_array('current_page_item', $item->classes) ||
            in_array('current-menu-ancestor', $item->classes) ||
            in_array('current_page_ancestor', $item->classes) ||
            in_array('current-menu-parent', $item->classes)
        ) {
            $active_class = ' active-menu';
        }

        // ACF icon fields
        $icon_url   = get_field('menu_icon_image', $item);
        $icon_class = get_field('menu_icon_class', $item) ?: 'img24';

        if (!$icon_url) {
            $icon_url = get_template_directory_uri() . '/images/default-icon.png';
        }

        // Output LI with active class
        $output .= '<li class="d-flex align-items-center nav-item gap10' . $active_class . '">';

        $output .= '<img src="' . esc_url($icon_url) . '" alt="' . esc_attr($item->title) . '" class="icon-img ' . esc_attr($icon_class) . '">';
        $output .= '<a href="' . esc_url($item->url) . '" class="p-0 text">' . esc_html($item->title) . '</a>';
    }

    function end_el(&$output, $item, $depth = 0, $args = null)
    {
        $output .= "</li>\n";
    }
}

class Edit_Profile_Walker extends Walker_Nav_Menu
{
    private $current_collapse_id = '';

    // ১. সাব-মেনু (UL) শুরু
    function start_lvl( &$output, $depth = 0, $args = null ) {
        // start_el থেকে সেট করা ইউনিক আইডি এখানে সরাসরি ব্যবহার হবে
        $output .= "\n<div id=\"{$this->current_collapse_id}\" class=\"accordion-collapse collapse\">\n";
        $output .= "<ul class=\"sub-menu list-unstyled m-0 p-0\">\n";
    }

    // ২. সাব-মেনু (UL) শেষ
    function end_lvl( &$output, $depth = 0, $args = null ) {
        $output .= "</ul>\n</div>\n";
    }

    // ৩. প্রতিটা উপাদান রেন্ডার
    function start_el(&$output, $data_object, $depth = 0, $args = array(), $id = 0)
    {
        $item = is_array($data_object) ? (object) $data_object : $data_object;

        $active_class = '';
        if (in_array('current-menu-item', $item->classes) ||
            in_array('current_page_item', $item->classes) ||
            in_array('current-menu-ancestor', $item->classes) ||
            in_array('current_page_ancestor', $item->classes)) {
            $active_class = ' active-menu';
        }

        $has_children = in_array('menu-item-has-children', $item->classes) || (isset($item->has_children) && $item->has_children);
        
        // অত্যন্ত গুরুত্বপূর্ণ: ইউনিক আইডি প্রি-জেনারেশন (যা বাটন ও সাব-মেনু উভয়েই হুবহু এক থাকবে)
        if ($has_children) {
            $this->current_collapse_id = 'flush-collapse-' . $item->ID;
        }

        $icon = get_field('menu_icon_image', $item);
        $icon_html = '';
        if ($icon) {
            $icon_html = '<img src="' . esc_url($icon) . '" alt="' . esc_attr($item->title) . '" class="me-2 icon-img" style="max-width:20px; height:auto;">';
        }

        if ( $depth === 0 && $has_children ) {
            $output .= "<li class=\"accordion-item border-0 w-100 {$active_class}\">";
            $output .= "<h2 class=\"accordion-header\">";
            $output .= sprintf(
                '<button class="d-flex align-items-center accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#%s" aria-expanded="false" aria-controls="%s">%s %s</button>',
                $this->current_collapse_id,
                $this->current_collapse_id,
                $icon_html,
                esc_html($item->title)
            );
            $output .= "</h2>";
        } else {
            $output .= '<li class="d-flex align-items-center nav-item gap10' . $active_class . '">';
            if ($icon) {
                $output .= $icon_html;
            }
            $output .= '<a href="' . esc_url($item->url) . '" class="p-0 text">' . esc_html($item->title) . '</a>';
            $output .= '</li>';
        }
    }
}

// {
//     function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0)
//     {
//         // Check if this menu item is active
//         $active_class = '';
//         if (in_array('current-menu-item', $item->classes) ||
//             in_array('current_page_item', $item->classes) ||
//             in_array('current-menu-ancestor', $item->classes) ||
//             in_array('current_page_ancestor', $item->classes)) {
//             $active_class = ' active-menu';
//         }

//         $icon = get_field('menu_icon_image', $item); // ACF icon image

//         $output .= '<li class="d-flex align-items-center nav-item gap10' . $active_class . '">';

//         if ($icon) {
//             $output .= '<img src="' . esc_url($icon) . '" alt="' . esc_attr($item->title) . '" class="icon-img">';
//         }

//         $output .= '<a href="' . esc_url($item->url) . '" class="p-0 text">' . esc_html($item->title) . '</a>';
//         $output .= '</li>';
//     }
// }


class Portal_Mega_FB_Style_Walker extends Walker_Nav_Menu {

    function start_lvl(&$output, $depth = 0, $args = null) {
        $output .= '<ul class="ps-0 list-unstyled mega-subitems">';
    }

    function end_lvl(&$output, $depth = 0, $args = null) {
        $output .= '</ul>';
    }

    function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
    {
        // Unified icon system
        // $icon_url   = get_field('menu_icon_image', $item);
        // $icon_class = get_field('menu_icon_class', $item) ?: 'img24';        

        // Text fields
        $title = esc_html($item->title);
        $desc  = esc_html($item->description);
        $url   = esc_url($item->url);

        // ---- 0: Section Title ----
        if ($depth === 0) {
            $output .= "<h6 class='mb-2 mega-section-title'>{$title}</h6>";
            return;
        }

        // ---- 1+: Sub Item with icon ----
        $output .= "<li class='d-flex align-items-start gap-3 mega-item'>";
        
        // <div class='mega-icon'></div>";
        // {$icon_class}
        // Icon only if exists
        // if ($icon_url) {
        //     $output .= "<img src='" . esc_url($icon_url) . "' class='w-100 h-100 object-fit-contain' alt=''>";
        // }

        $output .= "
            <a href='{$url}' class='d-flex flex-column mega-link'>
                <span class='mega-title'>{$title}</span>
                <small class='text-muted mega-desc'>{$desc}</small>
            </a>
        </li>";
    }

    function end_el(&$output, $item, $depth = 0, $args = null) {
        // no closing tag needed
    }
}

class Mega_Right_Walker extends Walker_Nav_Menu {

    function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        // Get custom fields for the menu item
        $icon_url   = get_field('menu_icon_image', 'menu_item_' . $item->ID);
        $icon_class = get_field('menu_icon_class', 'menu_item_' . $item->ID) ?: '';

        // Replace $username in URL with current user nicename
        $current_user = wp_get_current_user();
        $username = $current_user->user_nicename ?: 'guest';
        $url = str_replace('$username', $username, $item->url);

        // Start menu item
        $output .= '<li>';
        $output .= '<a href="' . esc_url($url) . '">';

        // Output icon or image
        if ($icon_url) {
            $output .= '<div class="' . esc_attr($icon_class ?: 'img24') . '">';
            $output .= '<img class="w-100 h-100 object-fit-contain" src="' . esc_url($icon_url) . '" alt="">';
            $output .= '</div>';
        } elseif ($icon_class) {
            $output .= '<i class="icon ' . esc_attr($icon_class) . '"></i> ';
        }

        // Menu title
        $output .= esc_html($item->title);
        $output .= '</a></li>';
    }

    function start_lvl(&$output, $depth = 0, $args = null) {
        $output .= '<ul class="list-unstyled mega-create-list">';
    }

    function end_lvl(&$output, $depth = 0, $args = null) {
        $output .= '</ul>';
    }
}

/**
 * Zoom Menu Walker
 * Manages Zoom App Functionalities menu with icon support
 * Same styling as Profile Menu
 */
// class Zoom_Menu_Walker extends Walker_Nav_Menu
// {
//     function start_lvl(&$output, $depth = 0, $args = null)
//     {
//         $output .= "\n<ul class=\"nav d-flex flex-column gap-2\">\n";
//     }

//     function end_lvl(&$output, $depth = 0, $args = null)
//     {
//         $output .= "</ul>\n";
//     }

//     function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
//     {
//         // 🔥 Detect active item
//         $active_class = '';
//         if (
//             in_array('current-menu-item', $item->classes) ||
//             in_array('current_page_item', $item->classes) ||
//             in_array('current-menu-ancestor', $item->classes) ||
//             in_array('current_page_ancestor', $item->classes) ||
//             in_array('current-menu-parent', $item->classes)
//         ) {
//             $active_class = ' active-menu';
//         }

//         // ACF icon fields
//         $icon_url   = get_field('menu_icon_image', $item);
//         $icon_class = get_field('menu_icon_class', $item) ?: 'img24';

//         // Output LI with active class
//         $output .= '<li class="d-flex align-items-center nav-item gap10' . $active_class . '">';

//         // Only output image if icon exists (matches Edit_Profile_Walker)
//         if ($icon_url) {
//             $output .= '<img src="' . esc_url($icon_url) . '" alt="' . esc_attr($item->title) . '" class="icon-img ' . esc_attr($icon_class) . '">';
//         }

//         $output .= '<a href="' . esc_url($item->url) . '" class="p-0 text">' . esc_html($item->title) . '</a>';
//     }

//     function end_el(&$output, $item, $depth = 0, $args = null)
//     {
//         $output .= "</li>\n";
//     }
// }


/**
 * Zoom Menu Walker (Updated with Bootstrap Accordion)
 * Manages Zoom App Functionalities menu with icon support
 * Same styling as Profile Menu Accordion
 */
class Zoom_Menu_Walker extends Walker_Nav_Menu
{
    private $item_index = 0;

    // ১. সাব-মেনু (UL) শুরু হওয়ার সময় বুটস্ট্র্যাপের collapse কন্টেইনার দিয়ে র্যাপ করা
    function start_lvl( &$output, $depth = 0, $args = null ) {
        $this->item_index++;
        $collapse_id = 'zoom-collapse-' . $this->item_index;
        
        $output .= "\n<div id=\"{$collapse_id}\" class=\"accordion-collapse collapse\" data-bs-parent=\"#accordionFlushZoom\">\n";
        $output .= "<ul class=\"sub-menu list-unstyled m-0 p-0\">\n";
    }

    // ২. সাব-মেনু (UL) শেষ হলে ডিভ ক্লোজ করা
    function end_lvl( &$output, $depth = 0, $args = null ) {
        $output .= "</ul>\n</div>\n";
    }

    // ৩. প্রতিটা উপাদান রেন্ডার করা
    function start_el(&$output, $data_object, $depth = 0, $args = array(), $id = 0)
    {
        // অবজেক্ট সাপোর্ট হ্যান্ডল করা
        $item = is_array($data_object) ? (object) $data_object : $data_object;

        // একটিভ আইটেম ডিটেক্ট করা
        $active_class = '';
        if (
            in_array('current-menu-item', $item->classes) ||
            in_array('current_page_item', $item->classes) ||
            in_array('current-menu-ancestor', $item->classes) ||
            in_array('current_page_ancestor', $item->classes) ||
            in_array('current-menu-parent', $item->classes)
        ) {
            $active_class = ' active-menu';
        }

        // চাইল্ড/সাব-মেনু আছে কিনা চেক করা
        $has_children = in_array('menu-item-has-children', $item->classes) || (isset($item->has_children) && $item->has_children);
        
        // ACF icon fields
        $icon_url   = get_field('menu_icon_image', $item);
        $icon_class = get_field('menu_icon_class', $item) ?: 'img24';
        
        $icon_html = '';
        if ($icon_url) {
            $icon_html = '<img src="' . esc_url($icon_url) . '" alt="' . esc_attr($item->title) . '" class="icon-img me-2 ' . esc_attr($icon_class) . '" style="max-width:20px; height:auto;">';
        }

        // কন্ডিশন: মূল প্যারেন্ট মেনু এবং চাইল্ড থাকলে তাকে বুটস্ট্র্যাপ অ্যাকরডিয়ন বাটন বানানো হবে
        if ( $depth === 0 && $has_children ) {
            $this->item_index++;
            $collapse_id = 'zoom-collapse-' . $this->item_index;

            $output .= "<li class=\"accordion-item border-0 w-100 {$active_class}\">";
            $output .= "<h2 class=\"accordion-header\">";
            $output .= sprintf(
                '<button class="d-flex align-items-center accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#%s" aria-expanded="false" aria-controls="%s">%s %s</button>',
                $collapse_id,
                $collapse_id,
                $icon_html, // ACF আইকন
                esc_html($item->title)
            );
            $output .= "</h2>";
        } else {
            // সাধারণ বা সাব-মেনু লিঙ্ক
            $output .= '<li class="d-flex align-items-center nav-item gap10' . $active_class . '">';
            
            if ($icon_url) {
                $output .= $icon_html;
            }

            $output .= '<a href="' . esc_url($item->url) . '" class="p-0 text">' . esc_html($item->title) . '</a>';
        }
    }

    function end_el(&$output, $item, $depth = 0, $args = null)
    {
        $output .= "</li>\n";
    }
}