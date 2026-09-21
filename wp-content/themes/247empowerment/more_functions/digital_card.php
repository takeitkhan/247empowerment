<?php
add_action('init', function () {
    if (isset($_GET['download_referral_card'])) {
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }

        $user = wp_get_current_user();
        $referral_link = add_query_arg('ref', $user->ID, home_url('/register'));

        // Image size
        $width = 900;
        $height = 1200;
        $img = imagecreatetruecolor($width, $height);

        // Colors
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        $blue  = imagecolorallocate($img, 30, 90, 180);

        imagefill($img, 0, 0, $white);

        // Font
        $font = get_template_directory() . '/assets/fonts/OpenSans-Regular.ttf';

        imagettftext($img, 36, 0, 50, 120, $blue, $font, 'Join Using My Referral');
        imagettftext($img, 28, 0, 50, 200, $black, $font, $user->display_name);

        // QR Code
        $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($referral_link);
        $qr = imagecreatefrompng($qr_url);

        imagecopyresampled($img, $qr, 300, 350, 0, 0, 300, 300, imagesx($qr), imagesy($qr));
        imagedestroy($qr);

        // Output
        header('Content-Type: image/jpeg');
        header('Content-Disposition: attachment; filename="referral-card-' . $user->ID . '.jpg"');
        imagejpeg($img, null, 90);
        imagedestroy($img);
        exit;
    }
});


add_action('init', function () {
    if (isset($_GET['download_referral_qr'])) {
        if (!is_user_logged_in()) {
            wp_die('Unauthorized');
        }

        $user = wp_get_current_user();
        $referral_link = add_query_arg('ref', $user->ID, home_url('/register'));

        $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=600x600&data=' . urlencode($referral_link);

        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="referral-qr-'.$user->ID.'.png"');
        readfile($qr_url);
        exit;
    }
});
