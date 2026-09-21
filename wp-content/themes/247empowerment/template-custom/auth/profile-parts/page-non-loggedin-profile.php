<?php

/**
 * Template Name: Non-Logged-In Profile Page
 */

// Replace with your logic to set the course-related variables
$course = get_queried_object_id(); // or get_post()
$custom_permalink = get_permalink($course);

$thumbnail_url   = get_the_post_thumbnail_url($course, 'full');
$short_details   = get_post_meta($course, 'short_description', true);
$instructor      = get_post_meta($course, 'instructor_name', true);
$duration        = get_post_meta($course, 'duration', true);
$register_url    = wp_registration_url();
$login_url       = wp_login_url($custom_permalink . 'shareable/'); // redirect back
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title><?= esc_html(get_the_title($course)); ?> - Shareable</title>
    <link rel="canonical" href="<?= esc_url($custom_permalink . 'shareable/'); ?>" />

    <!-- Open Graph meta -->
    <meta property="og:title" content="<?= esc_attr(get_the_title($course)); ?>" />
    <meta property="og:description" content="<?= esc_attr($short_details); ?>" />
    <meta property="og:image" content="<?= esc_url($thumbnail_url); ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?= esc_url($custom_permalink . 'shareable/'); ?>" />

    <!-- Twitter Card meta -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?= esc_attr(get_the_title($course)); ?>" />
    <meta name="twitter:description" content="<?= esc_attr($short_details); ?>" />
    <meta name="twitter:image" content="<?= esc_url($thumbnail_url); ?>" />

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f9fafb;
            color: #222;
            max-width: 720px;
            margin: 40px auto;
            padding: 30px;
            box-shadow: 0 6px 20px rgb(0 0 0 / 0.1);
            border-radius: 12px;
        }

        img {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgb(0 0 0 / 0.12);
            margin: 1.5em 0;
            display: block;
        }

        h1 {
            font-weight: 700;
            font-size: 2.4rem;
            margin-bottom: 0.5em;
            color: #0a3d62;
            text-align: center;
        }

        p {
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 1.25em;
            color: #444;
        }

        p.lead {
            font-size: 1.25rem;
            font-weight: 500;
            color: #2d3436;
            margin-top: 0;
        }

        p strong {
            color: #0984e3;
        }

        p em {
            display: block;
            margin-top: 2em;
            font-style: italic;
            color: #636e72;
            text-align: center;
            font-size: 1rem;
        }

        @media (max-width: 480px) {
            body {
                margin: 20px 10px;
                padding: 20px;
            }

            h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>

<body>

    <h1><?= esc_html(get_the_title($course)); ?></h1>

    <img src="<?= esc_url($thumbnail_url); ?>" alt="<?= esc_attr(get_the_title($course)); ?>" />

    <?php if ($instructor): ?>
        <p><strong>Instructor:</strong> <?= esc_html($instructor); ?></p>
    <?php endif; ?>

    <?php if ($duration): ?>
        <p><strong>Duration:</strong> <?= esc_html($duration); ?></p>
    <?php endif; ?>

    <?php if ($short_details): ?>
        <p class="lead"><?= esc_html($short_details); ?></p>
    <?php endif; ?>

    <p><em>Full course content available after login.</em></p>

    <div style="text-align: center; margin-top: 2rem;">
        <a href="<?= esc_url($register_url); ?>"
            style="display:inline-block; margin:10px; padding:0.6rem 1.5rem;
           background-color:#0984e3; color:white; text-decoration:none;
           border-radius:30px; font-weight:600;
           box-shadow:0 4px 8px rgba(9,132,227,0.4); transition:0.3s ease;"
            onmouseover="this.style.backgroundColor='#065a9e'"
            onmouseout="this.style.backgroundColor='#0984e3'">
            Sign Up
        </a>

        <a href="<?= esc_url($login_url); ?>"
            style="display:inline-block; margin:10px; padding:0.6rem 1.5rem;
           background-color:#55efc4; color:#0a3d62; text-decoration:none;
           border-radius:30px; font-weight:600;
           box-shadow:0 4px 8px rgba(85,239,196,0.4); transition:0.3s ease;"
            onmouseover="this.style.backgroundColor='#32b28b'"
            onmouseout="this.style.backgroundColor='#55efc4'">
            Sign In
        </a>
    </div>

</body>

</html>