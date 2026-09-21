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
      <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" /> -->
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
      <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

      <!-- Toastify CSS -->
      <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">

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
      <header
          class="custom-z-index position-fixed bg-white custom-box-shadow header-navbar end-0 start-0">
          <nav
              class="d-flex flex-wrap w-100 h-100 navbar main-container navbar-expand-lg">
              <div
                  class="d-flex align-items-center justify-content-between w-100 h-full">
                  <div class="d-flex align-items-center">
                      <div class="me-2">
                          <?php if (is_user_logged_in()): ?>
                              <?php $user = UserProfileData::getInstance(); ?>
                              <a
                                  class="position-relative d-flex align-items-center justify-content-center logo-box"
                                  href="<?php echo esc_url($user->getProfileUrl()); ?>">
                                  <img class="bottom-0 position-absolute w-100 h-100 object-fit-cover" src="<?php echo esc_url(get_theme_mod('large_logo')); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
                              </a>
                          <?php endif; ?>
                      </div>

                      <?php
                        $current_user = wp_get_current_user();
                        $username = $current_user->user_nicename;
                        $referrals_url = site_url("/{$username}/referrals/");
                        ?>

                      <form method="get" action="<?php echo esc_url($referrals_url); ?>">
                          <div class="input-group d-lg-block d-none">
                              <div class="position-relative">
                                  <img class="img-icon" src="<?php echo get_template_directory_uri(); ?>/assets/img/loggedin_images/search.png" alt="" />
                                  <input
                                      type="text"
                                      name="search"
                                      value="<?php echo esc_attr($_GET['search'] ?? ''); ?>"
                                      placeholder="Search referral partners"
                                      style="border-radius: 100px; width: 230px; padding-left: 2rem;"
                                      aria-label="Search referrals"
                                      class="form-control" />
                              </div>
                          </div>
                      </form>


                  </div>
                  <?php $current_url = home_url(add_query_arg(array(), $wp->request)); ?>

                  <div class="d-lg-block w-50 d-none">
                      <div class="d-flex justify-content-evenly middle-col">

                          <div>
                              <a href="/" class="<?php echo (untrailingslashit(home_url('/')) === untrailingslashit($current_url)) ? 'active-menu' : ''; ?>">
                                  <i class="bi bi-house fs-4"></i>
                              </a>
                          </div>

                          <div>
                              <a href="<?php echo esc_url(home_url("/$username/store")); ?>" class="<?php echo (home_url("/$username/store") === $current_url) ? 'active-menu' : ''; ?>">
                                  <i class="bi bi-shop fs-4"></i>
                              </a>
                          </div>

                          <div>
                              <a href="<?php echo esc_url(home_url("/$username/referrals")); ?>" class="<?php echo (home_url("/$username/referrals") === $current_url) ? 'active-menu' : ''; ?>">
                                  <i class="bi bi-people fs-4"></i>
                              </a>
                          </div>

                          <div>
                              <a href="<?php echo esc_url(home_url("/$username/events")); ?>" class="<?php echo (home_url("/$username/events") === $current_url) ? 'active-menu' : ''; ?>">
                                  <i class="bi bi-calendar-event fs-4"></i>
                              </a>
                          </div>


                      </div>
                  </div>


                  <div>
                      <div class="d-flex">
                          <ul
                              class="right-navbar-gap flex-row align-items-center navbar-nav">
                              <!-- <li
                                  class="right-navbar-li position-relative rounded-circle text-center nav-item">
                                  <a
                                      class="d-flex align-items-center justify-content-center w-100 h-100 nav-link"
                                      href="#">
                                      <img class="dropdown-icon" src="<?php echo get_template_directory_uri(); ?>/assets/img/loggedin_images/union.png" alt="" />
                                  </a>
                              </li> -->

                              <li class="rounded-circle list-style-none nav-item dropdown">
                                  <a
                                      href="#"
                                      class="right-navbar-li position-relative d-flex align-items-center justify-content-center p-0 rounded-circle nav-link"
                                      id="iconDropdown"
                                      role="button"
                                      data-bs-toggle="dropdown"
                                      aria-expanded="false">
                                      <img
                                          class="z-0 rounded-circle w-100 h-100 dropdown-icon"
                                          src="<?php echo esc_url(get_user_meta(get_current_user_id(), 'profile_photo', true) ?: get_template_directory_uri() . '/assets/img/loggedin_images/banner.jpg'); ?>"
                                          alt="Dropdown Icon" />
                                      <div class="bottom-0 z-2 position-absolute d-flex align-content-center justify-content-center border-2 border-white rounded-circle text-center end-0 avatar-size">

                                          <i class="rounded-circle w-100 h-100 fa-caret-down fa-solid"></i>

                                      </div>
                                  </a>
                                  <ul
                                      class="position-absolute dropdown-menu navbar-ul-width dropdown-menu-end"
                                      aria-labelledby="iconDropdown">
                                      <?php
                                        if (is_user_logged_in()) :
                                            $current_user = wp_get_current_user();
                                            $first_name = $current_user->first_name;
                                            $last_name = $current_user->last_name;

                                            // Get the current user's username (slug)
                                            $user_slug = $current_user->user_login;

                                            // Create the profile URL using the username
                                            $profile_url = home_url('/' . $user_slug);  // This should link to the user's profile page

                                        ?>
                                          <li>
                                              <a class="dropdown-item" href="<?php echo esc_url($profile_url); ?>">
                                                  <?php echo esc_html($first_name . ' ' . $last_name); ?>
                                              </a>
                                          </li>
                                      <?php endif; ?>
                                      <li>
                                          <a class="dropdown-item" href="<?php echo esc_url('/modify-profile'); ?>">Update Profile</a>
                                      </li>
                                      <li>
                                          <?php if (current_user_can('administrator')) : ?>
                                              <a class="dropdown-item" href="<?php echo esc_url(admin_url()); ?>">Dashboard</a>
                                          <?php endif; ?>
                                      </li>
                                      <?php if (is_user_logged_in()) : ?>
                                          <li>
                                              <a class="dropdown-item" href="<?php echo esc_url(home_url('/report')); ?>">Report an issue</a>
                                          </li>
                                      <?php endif; ?>
                                      <?php if (is_user_logged_in()) : ?>
                                          <li>
                                              <a class="dropdown-item" href="<?php echo esc_url(home_url('/suggestion')); ?>">Make a suggestion</a>
                                          </li>
                                      <?php endif; ?>

                                      <li><a class="dropdown-item" href="<?php echo wp_logout_url(home_url('/')); ?>">Logout</a></li>
                                  </ul>
                              </li>
                          </ul>
                      </div>
                  </div>
              </div>
          </nav>
      </header>