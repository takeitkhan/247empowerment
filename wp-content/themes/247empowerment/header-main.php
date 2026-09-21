<?php
/**
 * Header template
 * Clean version – no duplicate SEO, OG, Twitter, or analytics
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name='impact-site-verification' value='cd519e58-e0d4-4876-9acc-9a0709a07aa7'>
    <script>
    window.addEventListener('load', function () {

    const players = document.querySelectorAll('.ytplayer iframe');

    players.forEach((iframe) => {
        let src = iframe.getAttribute('src');

        // remove old params to avoid duplication
        src = src.replace(/(&|\?)autoplay=1/g, '');

        // add required params
        const newSrc = src + '&autoplay=1&mute=1&enablejsapi=1&playsinline=1';

        iframe.setAttribute('src', newSrc);
    });

    });
    </script>

    <?php
    /**
     * WordPress + Plugins output:
     * - <title>
     * - Canonical
     * - Open Graph
     * - Twitter Cards
     * - Schema
     * - Styles & Scripts
     */
    wp_head();
    ?>

    <!-- Google Tag Manager -->
    <script>
        (function(w,d,s,l,i){
            w[l]=w[l]||[];
            w[l].push({'gtm.start': new Date().getTime(),event:'gtm.js'});
            var f=d.getElementsByTagName(s)[0],
                j=d.createElement(s),
                dl=l!='dataLayer'?'&l='+l:'';
            j.async=true;
            j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;
            f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','GTM-MMZ2RG8C');
    </script>
    <!-- End Google Tag Manager -->

    <!-- Facebook Pixel -->
    <script>
        !function(f,b,e,v,n,t,s){
            if(f.fbq)return;
            n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;
            n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];
            t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s);
        }(window, document,'script','https://connect.facebook.net/en_US/fbevents.js');
        fbq('init','1430184528309439');
        fbq('track','PageView');
    </script>
    <!-- End Facebook Pixel -->
</head>

<body <?php body_class( 'bg-dark-custom' ); ?>>

<!-- Google Tag Manager (noscript) -->
<noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MMZ2RG8C"
            height="0" width="0" style="display:none;visibility:hidden"></iframe>
</noscript>
<!-- End Google Tag Manager (noscript) -->
<header class="bg-white shadow-sm py-2 custom-navbar">
    <div class="d-flex align-items-center justify-content-between container container-home">

        <!-- LEFT SIDE: LOGO & SITE TITLE -->
        <div class="d-flex align-items-center gap-2 logo-container">
            <div class="logo-inner-container">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="d-flex align-items-center text-decoration-none">
                    <img
                        src="<?php echo esc_url( get_theme_mod( 'large_logo' ) ); ?>"
                        alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
                        class="logo-nav">
                </a>
            </div>
            <div class="logo-text-container vstack">
                <span class="ms-2 logo-text fw-bold">
                    <?php echo esc_html( get_bloginfo( 'name' ) ); ?>
                </span>
                <span class="ms-2 logo-description caveat-500">
                    <?php echo esc_html( get_bloginfo( 'description' ) ); ?>
                </span>
            </div>                
        </div>

        <!-- RIGHT SIDE: DESKTOP NAVIGATION -->
        <div class="d-lg-flex align-items-center gap-4 d-none">
            <!-- MAIN MENU -->
            <?php
                wp_nav_menu( [
                    'theme_location' => 'primary',
                    'container'      => false,
                    'menu_class'     => 'd-flex align-items-center gap-4 list-unstyled mb-0 custom-header-menu',
                    'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
                    'depth'          => 3,
                    'fallback_cb'    => false,
                    'walker'         => new MM_Walker_Nav_Menu(),
                ] );
            ?>

            <!-- AUTHENTICATION MENU -->
            <?php
                wp_nav_menu( [
                    'theme_location' => 'authentication',
                    'container'      => false,
                    'menu_class'     => 'd-flex align-items-center gap-3 list-unstyled mb-0 auth-buttons',
                    'items_wrap'     => '<ul class="%2$s">%3$s</ul>',
                    'fallback_cb'    => false,
                    'walker'         => new MM_Auth_Walker_Nav_Menu(),
                ] );
            ?>
        </div>

        <!-- MOBILE TOGGLE BUTTON (Visible only on mobile/tablet) -->
        <button class="btn btn-light menu-toggle d-lg-none" id="menuToggle" aria-label="Toggle Menu">
            <i class="bi bi-list"></i>
        </button>

    </div>
</header>

<!-- MOBILE DRAWER MENU (Slides in from the right) -->
<div class="mobile-drawer-overlay" id="drawerOverlay"></div>
<div class="mobile-drawer" id="mobileMenuDrawer">
    <!-- Close Button inside Drawer -->
    <div class="d-flex align-items-center justify-content-between p-3 border-bottom drawer-header">
        <span class="fw-bold fs-5">Menu</span>
        <button class="btn-close" id="closeDrawer" aria-label="Close Menu"></button>
    </div>
    
    <div class="p-4 drawer-body">
        <!-- Mobile Main Menu -->
        <nav class="mb-4">
            <?php
                wp_nav_menu( [
                    'theme_location' => 'primary',
                    'container'      => false,
                    'menu_class'     => 'list-unstyled d-flex flex-column gap-3 mobile-nav-links',
                    'items_wrap'     => '<ul class="%2$s">%3$s</ul>',
                    'depth'          => 3,
                    'fallback_cb'    => false,
                    'walker'         => new MM_Walker_Nav_Menu(),
                ] );
            ?>
        </nav>

        <hr class="my-4">

        <!-- Mobile Auth Menu -->
        <nav>
            <?php
                wp_nav_menu( [
                    'theme_location' => 'authentication',
                    'container'      => false,
                    'menu_class'     => 'list-unstyled d-flex flex-column gap-3 mobile-auth-links',
                    'items_wrap'     => '<ul class="%2$s">%3$s</ul>',
                    'fallback_cb'    => false,
                    'walker'         => new MM_Auth_Walker_Nav_Menu(),
                ] );
            ?>
        </nav>
    </div>
</div>