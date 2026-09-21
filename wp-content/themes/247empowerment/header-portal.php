  <?php
    global $post;

    // Check if the page is the front page (custom or default)
    if (is_front_page()) {
        if (is_home()) {
            // Default blog page, no need to change this, unless you want specific settings.
            $og_title = get_bloginfo('name');
            $og_description = get_bloginfo('description');
            $og_url = home_url();
            $og_image = get_template_directory_uri() . '/assets/img/helping_image.jpg'; // fallback image
        } else {
            // Custom front page
            $og_title = get_the_title(get_option('page_on_front')); // Title of the custom front page
            $og_description = get_the_excerpt(get_option('page_on_front')); // Excerpt of the custom front page
            $og_url = home_url(); // Front page URL
            $og_image = get_the_post_thumbnail_url(get_option('page_on_front'), 'full'); // Get image from custom front page
            if (empty($og_image)) {
                $og_image = get_template_directory_uri() . '/assets/img/helping_image.jpg'; // fallback if no image
            }
        }
    } elseif (is_singular()) {
        // For posts or pages
        setup_postdata($post);
        $og_title = get_the_title($post);
        $og_description = get_the_excerpt($post);
        $og_url = get_permalink($post);

        if (has_post_thumbnail($post)) {
            $og_image = get_the_post_thumbnail_url($post, 'full');
        } else {
            $og_image = get_template_directory_uri() . '/assets/img/helping_image.jpg';
        }
        wp_reset_postdata();
    } else {
        // Fallback for other pages
        $og_title = get_bloginfo('name');
        $og_description = get_bloginfo('description');
        $og_url = home_url();
        $og_image = get_template_directory_uri() . '/assets/img/helping_image.jpg';
    }
    ?>
  <!DOCTYPE html>
  <html <?php language_attributes(); ?>>

  <head>
      <meta charset="<?php bloginfo('charset'); ?>">
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <meta name="zoom-domain-verification" content="ZOOM_verify_3f562159b8034f7888f6e326d334129c">

      <title><?php echo esc_html($og_title); ?> | <?php bloginfo('name'); ?></title>


      <!-- Open Graph Meta -->
      <meta property="fb:app_id" content="1389188676349074" />
      <meta property="og:title" content="<?php echo esc_attr($og_title); ?>" />
      <meta property="og:description" content="<?php echo esc_attr($og_description); ?>" />
      <meta property="og:type" content="website" />
      <meta property="og:url" content="<?php echo esc_url($og_url); ?>" />
      <meta property="og:image" content="<?php echo esc_url($og_image); ?>" />

      <!-- Twitter Meta -->
      <meta name="twitter:card" content="<?php echo esc_attr($og_title); ?>">
      <meta name="twitter:title" content="<?php echo esc_attr($og_title); ?>">
      <meta name="twitter:description" content="<?php echo esc_attr($og_description); ?>">
      <meta name="twitter:image" content="<?php echo esc_url($og_image); ?>">

      <link rel="canonical" href="<?php echo esc_url($og_url); ?>" />
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
      <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

      <!-- Toastify CSS -->
      <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

      <!-- Dropdown Fix CSS -->
      <style>
        /* Force dropdown menu visibility and positioning */
        .dropdown {
          position: relative;
        }

        .dropdown-menu {
          display: none;
          position: absolute;
          top: calc(100% + 0.5rem);
          min-width: 160px;
          padding: 0.5rem 0;
          margin: 0;
          background-color: #fff;
          border: 1px solid rgba(0, 0, 0, 0.15);
          border-radius: 0.375rem;
          box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
          z-index: 1060;
        }

        /* When dropdown is shown, make it visible */
        .dropdown-menu.show {
          display: block;
        }

        /* Right-aligned dropdown */
        .dropdown-menu-end {
          right: 0;
          left: auto;
        }

        /* Dropdown items */
        .dropdown-item {
          display: block;
          width: 100%;
          padding: 0.25rem 1rem;
          clear: both;
          font-weight: 400;
          color: #212529;
          text-align: inherit;
          white-space: nowrap;
          background-color: transparent;
          border: 0;
          text-decoration: none;
          cursor: pointer;
        }

        .dropdown-item:hover,
        .dropdown-item:focus {
          color: #1e2125;
          background-color: #e9ecef;
        }

        /* Prevent overflow from hiding dropdown */
        header {
          overflow: visible !important;
        }

        .navbar {
          overflow: visible !important;
        }

        /* Ensure portal header allows dropdowns */
        .portal-header {
          overflow: visible !important;
        }

        /* Fix custom dropdown styling that might conflict */
        .dropdown-menu.custom-card {
          position: absolute;
          display: none;
        }

        .dropdown-menu.custom-card.show {
          display: block;
        }

        .dropdown-menu ul {
          margin: 0;
          padding: 0;
          list-style: none;
        }
        .logo-box {
          text-decoration: none;
        }
      </style>

      <!-- Toastify JS -->
      <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

      <script>
          document.addEventListener("DOMContentLoaded", function() {
              const storedReferrer = sessionStorage.getItem("referred_by");

              if (!storedReferrer) {
                  const segments = window.location.pathname.split('/');
                  const referrer = segments[1]; // 'joseph'

                  if (referrer) {
                      sessionStorage.setItem("referred_by", referrer);
                      console.log(`Referral set to ${referrer}`);
                  }
              } else {
                  console.log(`Already referred by ${storedReferrer}`);
              }
          });
      </script>

      <!-- Google tag (gtag.js) -->
      <script async src="https://www.googletagmanager.com/gtag/js?id=G-C3V0VTNC42"></script>
      <script>
          window.dataLayer = window.dataLayer || [];

          function gtag() {
              dataLayer.push(arguments);
          }
          gtag('js', new Date());

          gtag('config', 'G-C3V0VTNC42');
      </script>
      <!-- Google Tag Manager -->
      <script>
          (function(w, d, s, l, i) {
              w[l] = w[l] || [];
              w[l].push({
                  'gtm.start': new Date().getTime(),
                  event: 'gtm.js'
              });
              var f = d.getElementsByTagName(s)[0],
                  j = d.createElement(s),
                  dl = l != 'dataLayer' ? '&l=' + l : '';
              j.async = true;
              j.src =
                  'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
              f.parentNode.insertBefore(j, f);
          })(window, document, 'script', 'dataLayer', 'GTM-MMZ2RG8C');
      </script>
      <!-- End Google Tag Manager -->
      <!-- Facebook Pixel Code -->
      <script nonce="gnuSF21j">
          ! function(f, b, e, v, n, t, s) {
              if (f.fbq) return;
              n = f.fbq = function() {
                  n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments)
              };
              if (!f._fbq) f._fbq = n;
              n.push = n;
              n.loaded = !0;
              n.version = '2.0';
              n.queue = [];
              t = b.createElement(e);
              t.async = !0;
              t.src = v;
              s = b.getElementsByTagName(e)[0];
              s.parentNode.insertBefore(t, s)
          }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
          fbq('init', '1430184528309439');
          fbq('track', "PageView");
      </script>
      <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=1430184528309439&ev=PageView&noscript=1" /></noscript> <!-- End Facebook Pixel Code -->
      <?php wp_head(); ?>
  </head>

  <body>
      <header class="custom-navbar">
          <div class="d-flex align-items-center justify-content-between w-100 h-100 container">
              <!-- logo-nav -->
              <div class="d-flex align-items-center gap-2 logo-container">
                  <div class="logo-inner-container">
                      <?php 
                      // Determine the destination URL based on login status
                      $logo_url = is_user_logged_in() && class_exists('UserProfileData') 
                          ? UserProfileData::getInstance()->getProfileUrl() 
                          : home_url( '/' ); 
                      ?>
                      <a href="<?php echo esc_url( $logo_url ); ?>" class="d-flex align-items-center text-decoration-none">
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

              <?php
                wp_nav_menu([
                    'theme_location' => 'portalmenu',
                    'menu_class'     => 'd-lg-flex gap-0 nav d-none',
                    'walker'         => new Image_Icon_Walker_Nav_Menu(),
                ]);
                ?>


              <div class="d-flex align-items-center gap-1 gap-md-3">
                  <?php get_template_part('template-parts/portal-header/header', 'search'); ?>
                  <?php //get_template_part('template-parts/portal-header/header', 'messages'); ?>
                  <?php get_template_part('template-parts/portal-header/header', 'quick-links'); ?>
                  <?php get_template_part('template-parts/portal-header/header', 'notifications'); ?>
                  <?php get_template_part('template-parts/portal-header/header', 'profile-dropdown'); ?>

                  <button class="btn btn-light d-lg-none" id="menuToggle"><i class="bi bi-list fs-4"></i></button>
              </div>
          </div>

          <div class="bg-white shadow-sm mobile-menu d-lg-none">
              <?php
                wp_nav_menu([
                    'theme_location' => 'portalmobilemenu',
                    'menu_class'     => 'flex-column p-3 text-center nav',
                    'container'      => false,
                    'walker'         => new Image_Icon_Walker_Nav_Menu(),
                ]);
                ?>
          </div>
      </header>
      <?php get_template_part('template-parts/soft-launch-banner'); ?>

      <script>
      // Enhanced Bootstrap Dropdown Initialization with CDN wait
      function initializeDropdowns() {
        console.log('🔧 Initializing dropdowns...');
        
        // Initialize Bootstrap dropdowns if available
        if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
          console.log('✅ Bootstrap detected, initializing dropdowns');
          const dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
          dropdownElementList.forEach(function (dropdownToggleEl) {
            new bootstrap.Dropdown(dropdownToggleEl);
          });
          return true;
        }
        return false;
      }

      // Wait for Bootstrap to load (with timeout)
      function waitForBootstrap() {
        const maxAttempts = 50; // ~5 seconds with 100ms intervals
        let attempts = 0;
        
        const checkBootstrap = setInterval(() => {
          attempts++;
          if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
            clearInterval(checkBootstrap);
            initializeDropdowns();
            console.log('✅ Bootstrap loaded after ' + (attempts * 100) + 'ms');
          } else if (attempts >= maxAttempts) {
            clearInterval(checkBootstrap);
            console.warn('⚠️ Bootstrap failed to load, using fallback dropdown handler');
            setupFallbackDropdowns();
          }
        }, 100);
      }

      function setupFallbackDropdowns() {
        // Fallback: Manual dropdown toggle handler
        document.addEventListener('click', function(e) {
          let toggleBtn = e.target.closest('[data-bs-toggle="dropdown"]');
          
          if (!toggleBtn) return;
          
          e.preventDefault();
          e.stopPropagation();
          
          const dropdown = toggleBtn.closest('.dropdown');
          if (!dropdown) return;
          
          const dropdownMenu = dropdown.querySelector('.dropdown-menu');
          if (!dropdownMenu) return;
          
          dropdownMenu.classList.toggle('show');
          toggleBtn.setAttribute('aria-expanded', dropdownMenu.classList.contains('show'));
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
          if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
              menu.classList.remove('show');
              menu.closest('.dropdown')?.querySelector('[data-bs-toggle="dropdown"]')?.setAttribute('aria-expanded', 'false');
            });
          }
        });
      }

      // Start initialization when DOM is ready
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', waitForBootstrap);
      } else {
        waitForBootstrap();
      }
      </script>