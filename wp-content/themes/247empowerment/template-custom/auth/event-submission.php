<?php

/**
 * Template Name: Event Submission Page
 */
get_header_based_on_login();
?>
<main>
    <div class="main-container s-main-con">
        <div class="row g-3">
            <div class="d-md-block bottom-0 position-sticky col d-none">
                <div class="bg-white custom-box-shadow p-3 custom-border-radius h-100">
                    <?php include 'event-parts/left-column.php'; ?>
                </div>
            </div>

            <div class="ms-md-auto col-12 col-md-8 col-lg-9 col-xl-9">
                <div class="bg-white custom-box-shadow mb-3 p-3 custom-border-radius">
                    <h3 class="fw-bold market-title">Submit Your Event</h3>
                    <p class="m-text">Fill out the form below to submit your event.</p>
                </div>

                <div class="mb-3">
                    <?php
                    // Display the event submission form shortcode
                    echo do_shortcode('[event_submission_form]');
                    ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php get_footer_based_on_login(); ?>