<div class="dropdown mega-menu-wrapper">
    <button
        class="position-relative bg-supporting rounded-circle img44 btn-custom btn-focus"
        type="button"
        data-bs-toggle="dropdown"
        aria-expanded="false">
        <img class="object-fit-contain"
            src="<?= esc_url(get_template_directory_uri() . '/assets/img/nd/menu-svgrepo-com.svg'); ?>"
            alt="">
    </button>

    <div class="shadow p-4 border-0 rounded-3 dropdown-menu mega-menu dropdown-menu-end">
        <h5>Quick Links</h5>
        <div class="d-flex gap-4 mega-menu-inner">

            <!-- LEFT PANEL -->
            <div class="mega-left flex-grow-1">
                <!-- <input type="text" class="mb-3 form-control mega-search" placeholder="Search menu"> -->

                <?php
                wp_nav_menu([
                    'theme_location' => 'portalmegamenu',
                    'container'      => false,
                    'menu_class'     => 'mega-section-list',
                    'fallback_cb'    => false,
                    'walker'         => new Portal_Mega_FB_Style_Walker()
                ]);
                ?>
            </div>

            <!-- RIGHT PANEL -->
            <div class="mega-right">
                <h5 class="mb-3">Main Navigations</h5>
                <?php
                wp_nav_menu([
                    'theme_location' => 'megarightmenu', // Use theme location, not menu name
                    'container'      => false,
                    'menu_class'     => 'mega-create-list', // walker handles classes
                    'walker'         => new Mega_Right_Walker(),
                ]);
                ?>
            </div>


        </div>
    </div>
</div>

<script>
document.addEventListener("click", function (event) {
    const wrapper = document.querySelector(".mega-menu-wrapper");
    if (!wrapper) return;

    const dropdown = wrapper.querySelector(".dropdown-menu");

    // Prevent closing when tapping inside
    if (dropdown.contains(event.target)) {
        event.stopPropagation();
    }
});
</script>
