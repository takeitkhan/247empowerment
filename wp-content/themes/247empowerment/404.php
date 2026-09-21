<?php
if (is_user_logged_in()) {
    get_header('portal');
} else {
    get_header('main');
}
?>
<h1>Page Not Found</h1>
<?php
if (is_user_logged_in()) {
    get_footer('portal'); // loads footer-custom.php
} else {
    get_footer('main'); // loads footer-main.php
}
?>