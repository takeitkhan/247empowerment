<div class="portal-menus-wrapper">

    <!-- Profile Menu Section -->
    <div class="bg-white custom-card navbar-link edit-profile-wrapper">
        <div class="d-flex align-items-center justify-content-between edit-profile-header xpy-2 xborder-bottom">
            <h5 class="mb-0 text-start portal-title fw-bold">Profile</h5>
        </div>

        <div class="edit-profile-menu py-2">
            <div class="edit-profile-wrapper">
                <div class="accordion accordion-flush" id="accordionFlushExample">
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'editprofilemenu',
                        'container' => false,
                        'menu_class' => 'list-unstyled m-0 p-0',
                        'walker' => new Edit_Profile_Walker(), // আমাদের নতুন ওল্ড-মিক্সড ওয়াকার
                    ]);
                    ?>
                </div>
            </div>
        </div>
    </div>
<?php
/**
    <div class="bg-white mt-3 custom-card navbar-link zoom-menu-wrapper">

        <div class="d-flex align-items-center justify-content-between zoom-menu-toggle p-3 border-bottom">
            <h5 class="mb-0 text-start portal-title fw-bold">Collaboration with Zoom</h5>
        </div>

        <div class="zoom-menu-content p-2">
            <div class="accordion accordion-flush" id="accordionFlushZoom">
                <?php
                try {
                    if (!class_exists('Zoom_Menu_Walker')) {
                        throw new Exception('Zoom_Menu_Walker class not found');
                    }

                    $current_user_id = get_current_user_id();
                    $user_data = wp_get_current_user();
                    $is_subscriber = in_array('subscriber', $user_data->roles);
                    $has_zoom_token = !empty(get_user_meta($current_user_id, 'zoom_access_token', true));

                    if ($is_subscriber && !$has_zoom_token) {
                        wp_nav_menu([
                            'theme_location' => 'zoommenu',
                            'container' => false,
                            'menu_class' => 'list-unstyled m-0 p-0',
                            'fallback_cb' => false,
                        ]);
                    } else {
                        wp_nav_menu([
                            'theme_location' => 'zoommenu',
                            'container' => false,
                            'menu_class' => 'list-unstyled m-0 p-0',
                            'walker' => new Zoom_Menu_Walker(),
                            'fallback_cb' => false,
                        ]);
                    }

                } catch (Exception $e) {
                    error_log('[ZOOM MENU ERROR] ' . $e->getMessage() . ' - ' . $e->getFile() . ':' . $e->getLine());
                    echo '<p style="color: #ff9800; padding: 10px; background: #fff3cd; border-radius: 3px;">⚠️ Zoom menu not available: ' . esc_html($e->getMessage()) . '</p>';
                }
                ?>
            </div>
        </div>

    </div>
**/
?>
</div>

<style>
    /* বুটস্ট্র্যাপ অ্যাকরডিয়ন বাটন ডিজাইন (যেমন: Security, User & Password) */
    #accordionFlushExample .accordion-button {
        font-size: 16px;
        font-weight: 600;
        color: #000000;
        background-color: #ffffff;
        border: 1px solid #e0e0e0;
        padding: 12px 16px;
        box-shadow: none;
        border-radius: 0;
    }

    #accordionFlushExample .accordion-item {
        margin-bottom: 12px;
    }

    /* সাব-মেনুর ভেতরের লিঙ্কের আন্ডারলাইন ও ডিসপ্লে ফ্ল্যাট করা */
    #accordionFlushExample .sub-menu li {
        display: block !important;
        width: 100%;
        background: none !important;
    }
    #accordionFlushExample .sub-menu li.active-menu a {
        color: #2563EB !important;
    }

    /* সিএসএস ফাইল বা স্টাইল ট্যাগে শুধু এইটুকু রাখুন */
    .accordion-collapse.collapse {
        display: none;
    }
    .accordion-collapse.collapse.show {
        display: block;
    }

    /* স্মুথ ট্রানজিশন অ্যাপিয়ারেন্স */
    .accordion-collapse {
        transition: all 0.2s ease-in-out;
    }





.sub-menu.list-unstyled {
    background: rgba(255, 255, 255, 0.05);
    border-left: 2px solid rgba(255, 255, 255, 0.1);
    padding: 8px 0 8px 0px !important;
    border-radius: 0 0 0 8px;
}

.sub-menu .nav-item {
    list-style: none;
    padding: 10px 14px;
    margin-bottom: 4px;
    border-radius: 8px;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    position: relative;
    cursor: pointer;
}

.sub-menu .nav-item a.text {
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    width: 100%;
    transition: color 0.3s ease;
}

.sub-menu .nav-item .icon-img {
    filter: grayscale(30%) opacity(0.8);
    transition: all 0.3s ease;
}

.sub-menu .nav-item:hover a.text {
    color: #2563EB !important;
}

.sub-menu .nav-item:hover .icon-img {
    filter: grayscale(0%) opacity(1);
    transform: scale(1.1);
}

.sub-menu .nav-item.active-menu {
    background: linear-gradient(90deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.02) 100%); 
    border-left: 3px solid #3b82f6;
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

.sub-menu .nav-item.active-menu a.text {
    color: #000000 !important; /* একটিভ টেক্সট কালার (যেমন- রয়্যাল ব্লু) */
    font-weight: 600;
}

.sub-menu .nav-item.active-menu .icon-img {
    filter: grayscale(0%) opacity(1);
}

/* ডার্ক মোড সাপোর্ট (যদি আপনার সাইট ডার্ক থিমের হয়) */
@media (prefers-color-scheme: dark) {
    .sub-menu.list-unstyled {
        background: rgba(0, 0, 0, 0.2);
    }
}


/****
    #accordionFlushZoom .accordion-button {
        font-size: 16px;
        font-weight: 600;
        color: #000000;
        background-color: #ffffff;
        border: 1px solid #e0e0e0;
        padding: 12px 16px;
        box-shadow: none;
        border-radius: 0;
    }

    #accordionFlushZoom .accordion-item {
        margin-bottom: 12px;
    }
    #accordionFlushZoom .sub-menu li {
        display: block !important;
        width: 100%;
    }

    #accordionFlushZoom .sub-menu li a {
        display: block !important;
        padding: 12px 16px !important;
        font-size: 15px;
        text-decoration: underline !important;
        font-weight: 500;
        width: 100%;
        background-color: #4a8bee !important;
        color: #ffffff !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.15) !important;
    }

****/
</style>