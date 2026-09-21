<header class="py-2">
        <div class="container">
            <div class="align-items-center border-bottom overflow-hidden row">

                <!-- Logo -->
                <div class="px-0 col-1 col-md-1 col-lg-1">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="d-inline-block">
                        <img src="<?php echo esc_url(get_theme_mod('large_logo')); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>"
                            class="img-fluid" style="width: 70px;">
                    </a>
                </div>

                <!-- Navbar -->
                <div class="col-8 col-md-8 col-lg-8">
                    <nav class="bg-white p-0 navbar navbar-expand-lg navbar-light">
                        <div class="p-0 container">
                            <button class="navbar-toggler" ... data-bs-toggle="collapse" ...>
                                <span class="navbar-toggler-icon"></span>
                            </button>

                            <div class="collapse navbar-collapse justify-content-center" id="mainNavbar">
                                <?php
                                wp_nav_menu([
                                    'theme_location' => 'primary',
                                    'container'      => false,
                                    'menu_class'     => 'navbar-nav',
                                    'fallback_cb'    => false,
                                    'depth'          => 2,
                                    'walker'         => new MM_Walker_Nav_Menu(),
                                ]);
                                ?>
                            </div>
                        </div>
                    </nav>
                </div>

                <!-- Sign Up / Sign In -->
                <div class="align-items-center text-end col-3 col-md-3 col-lg-3">
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'authentication',
                        'container' => false,
                        'menu_class' => 'list-inline mb-0',
                        'items_wrap' => '<ul class="%2$s">%3$s</ul>',
                        'walker' => new MM_Auth_Walker_Nav_Menu(),
                        'fallback_cb' => false,
                    ]);
                    ?>
                </div>
            </div>
        </div>
    </header>


    <header class="py-2">
        <div class="container">
            <div class="align-items-center border-bottom overflow-hidden row">

                <!-- Logo -->
                <div class="px-0 col-1 col-md-1 col-lg-1">
                    <a href="<?php echo esc_url(home_url('/')); ?>" class="d-inline-block">
                        <img src="<?php echo esc_url(get_theme_mod('large_logo')); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>"
                            class="img-fluid" style="width: 70px;">
                    </a>
                </div>

                <!-- Navbar -->
                <div class="col-8 col-md-8 col-lg-8">
                    <nav class="bg-white p-0 navbar navbar-expand-lg navbar-light">
                        <div class="p-0 container">
                            <button class="navbar-toggler" ... data-bs-toggle="collapse" ...>
                                <span class="navbar-toggler-icon"></span>
                            </button>

                            <div class="collapse navbar-collapse justify-content-center" id="mainNavbar">
                                <?php
                                wp_nav_menu([
                                    'theme_location' => 'primary',
                                    'container'      => false,
                                    'menu_class'     => 'navbar-nav',
                                    'fallback_cb'    => false,
                                    'depth'          => 2,
                                    'walker'         => new MM_Walker_Nav_Menu(),
                                ]);
                                ?>
                            </div>
                        </div>
                    </nav>
                </div>

                <!-- Sign Up / Sign In -->
                <div class="align-items-center text-end col-3 col-md-3 col-lg-3">
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'authentication',
                        'container' => false,
                        'menu_class' => 'list-inline mb-0',
                        'items_wrap' => '<ul class="%2$s">%3$s</ul>',
                        'walker' => new MM_Auth_Walker_Nav_Menu(),
                        'fallback_cb' => false,
                    ]);
                    ?>
                </div>
            </div>
        </div>
    </header>