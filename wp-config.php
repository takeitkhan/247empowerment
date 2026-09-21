<?php
define( 'WP_CACHE', true );

define('DB_NAME', 'wp');
define('DB_USER', 'wp');
define('DB_PASSWORD', 'wp');
define('DB_HOST', 'db');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');
define('WP_MEMORY_LIMIT', '256M');
define('AUTH_KEY',         '(3}98R?w%dPgWoT6)(t[b*-oh=rXriI&!OLXzILDU,{0b~v{K^4]HAjMsSEnezY@');
define('SECURE_AUTH_KEY',  'Ek/%)X0n)NcHw=?n~L)I2ir=FhK2<-Gawm:)lgovp<n:)=)l}Y=47z+FCXY_{>*6');
define('LOGGED_IN_KEY',    'o8P&DfG4a&XCw,N6$NC;?V~r+GK}oCRf$p=F;8CxLH0,nYA@#niox@p9}ehKLi4k');
define('NONCE_KEY',        'os._5$Zvh[r 2 a@;, qWRo&Gc[$q+iAuri+L)#H$8hrSv2bI33BORSOv=TeV6ab');
define('AUTH_SALT',        '8G s-Ah_DBHG;JERC!^N[I1A&5QtseVu9|8Aiv i}cUk&fF:F(![cMDtg^q@BpZr');
define('SECURE_AUTH_SALT', 'c@UPmGnR*K$oG5uv!Q5IV5CZ/o0HAhdxg>Zk_:4xbwLtF1qO[Dy]S]2(43=ZeUam');
define('LOGGED_IN_SALT',   '6!Yd6k4MPF+i7!Yjr4+~>i~u<wc=ITKa4^E;z]%P}}/)w.D1o~}hAGgf>C#}xD9W');
define('NONCE_SALT',       'CEA?k&^U62r`AygxW9jm<3W`F}!S#fDF?0}Q6weyB+=T7mW#iA{wUZ9S.D(VUH;s');
$table_prefix = 'wp_bc82891ba1_';
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/html/wp-content/debug-php.log');
define('WP_HOME', 'http://pet.test');
define('WP_SITEURL', 'http://pet.test');
define('FS_METHOD', 'direct');
define('FS_CHMOD_DIR', 0755);
define('FS_CHMOD_FILE', 0644);
define('ALTERNATE_WP_CRON', true);
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
