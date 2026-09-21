<!-- Messages Dropdown -->
<div class="dropdown">
    <button class="position-relative bg-supporting rounded-circle img44 btn-custom"
        type="button"
        data-bs-toggle="dropdown"
        data-bs-auto-close="outside"
        aria-expanded="false">
        <img class=" " src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/nd/'); ?>messag.png" alt="">

        <!-- Badge -->
        <span class="position-absolute m-badge rounded-circle">
            3
        </span>

    </button>


    <div class="shadow-sm border-0 rounded-3 dropdown-menu dropdown-message-box dropdown-menu-end custom-card">
        <div class="d-flex align-items-center justify-content-between pb-4">
            <h6 class="mb-0 text-black fs24 fw-bold">Messages</h6>
        </div>

        <!-- Search -->
        <div class="input-group position-relative pb-4">
            <i class="position-absolute bi bi-search icon-message"></i>

            <input type="text" class="input-message" placeholder="Search messages" aria-label="Search messages">
        </div>

        <!-- Tabs -->
        <ul class="d-flex gap-3 mb-3 nav" id="messageTabs" role="tablist">
            <li class="text-center nav-item" role="presentation">
                <button class="w-100 text-black fs-18 fw-medium active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                    All
                </button>
            </li>
            <li class="text-center nav-item" role="presentation">
                <button class="w-100 text-black fs-18 fw-medium" id="unread-tab" data-bs-toggle="tab" data-bs-target="#unread" type="button" role="tab">
                    Unread
                </button>
            </li>
        </ul>


        <!-- Tab Content -->
        <div class="tab-content" id="messageTabsContent">

            <!-- All Tab -->
            <div class="tab-pane fade show active" id="all" role="tabpanel">
                <!-- Message 1 -->
                <div class="d-flex flex-wrap align-items-start justify-content-between mb-3">
                    <div>
                        <a href="search.php" class="d-flex align-items-center gap-3 pb-3 text-reset">
                            <div class="position-relative img44">
                                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/loggedin_images/'); ?>banner.jpg" class="rounded-circle w-100 h-100 object-fit-cover" alt="Profile">
                                <img class="position-absolute active-icon" src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/nd/'); ?>active_icon.png" alt="">
                            </div>

                            <div class="d-flex flex-column post-user">
                                <span class="p_name">Maria Johnson</span>

                            </div>
                        </a>

                        <div class="d-flex align-items-center gap-3 pb-3">

                            <div class="position-relative img44">
                                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/loggedin_images/'); ?>banner.jpg" class="rounded-circle w-100 h-100 object-fit-cover" alt="Profile">
                                <img class="position-absolute active-icon" src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/nd/'); ?>active_icon.png" alt="">
                            </div>

                            <div class="d-flex flex-column post-user">
                                <span class="p_name">Maria Johnson</span>

                            </div>
                        </div>
                    </div>

                    <!-- 3-dot dropdown (nested) -->
                    <div class="dropdown">
                        <button class="ms-2 p-0 text-muted btn btn-link" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="shadow-sm px-3 py-3 border-0 rounded-3 dropdown-menu dropdown-menu-end">
                            <li class="pb10"><a class="dropdown-item" href="#"><img class="me-2" src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/nd/'); ?>message-square-dot.png" alt="">Mark as read</a></li>
                            <li class="pb10"><a class="dropdown-item" href="#"><img class="me-2" src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/nd/'); ?>bell-off.png" alt="">Turn off notifications</a></li>
                            <li class="pb10"><a class="dropdown-item" href="#"><img class="me-2" src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/nd/'); ?>circle-user-round.png" alt="">See profile</a></li>

                            <li class="ps-1 pb10"><a class="d-flex gap-2 dropdown-item" href="#">
                                    <p class="img20"><img class="me-2 w-100 h-100 object-fit-contain" src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/nd/'); ?>delete.png" alt=""></p>Delete
                                </a></li>


                        </ul>
                    </div>
                </div>



                <!-- more messages... -->
            </div>

            <!-- Unread Tab -->
            <div class="tab-pane fade" id="unread" role="tabpanel">
                <div class="py-3 text-muted text-center">No unread messages</div>
            </div>
        </div>
    </div>
</div>