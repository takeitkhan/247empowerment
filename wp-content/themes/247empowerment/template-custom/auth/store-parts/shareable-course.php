<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title><?= esc_html(get_the_title($course)); ?> - Shareable</title>

    <link rel="canonical" href="<?= esc_url($custom_permalink . 'shareable/'); ?>" />

    <!-- Open Graph meta tags -->
    <meta property="og:title" content="<?= esc_attr(get_the_title($course)); ?>" />
    <meta property="og:description" content="<?= esc_attr($short_details); ?>" />
    <meta property="og:image" content="<?= esc_url($thumbnail_url); ?>" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="<?= esc_url($custom_permalink . 'shareable/'); ?>" />

    <!-- Twitter meta tags -->
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

        .btn-wrapper {
            text-align: center;
            margin-top: 2rem;
        }

        .btn {
            display: inline-block;
            margin: 0 10px 10px 10px;
            padding: 0.6rem 1.5rem;
            border-radius: 30px;
            font-weight: 600;
            text-decoration: none;
            transition: background-color 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background-color: #0984e3;
            color: white;
        }

        .btn-primary:hover {
            background-color: #065a9e;
        }

        .btn-secondary {
            background-color: #55efc4;
            color: #0a3d62;
        }

        .btn-secondary:hover {
            background-color: #32b28b;
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

    <div class="btn-wrapper">        
        <a href="<?= esc_url($login_url); ?>" class="btn btn-secondary">Sign In</a>
        <a href="<?= esc_url($register_url); ?>" class="btn btn-primary">Sign Up</a>
    </div>

</body>

</html>