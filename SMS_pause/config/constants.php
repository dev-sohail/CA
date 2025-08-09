<?php
// Define the root directory of the project (the folder where the `SMS` folder is located)
define('APP_ROOT', $_SERVER["DOCUMENT_ROOT"] . '/SMS');  // Example: /var/www/html/SMS
define('APP_HOST_ROOT', $_SERVER['HTTP_HOST'] . '/SMS');
define('ROOT_DIR', __DIR__ . '/'); // Example: /var/www/html/SMS/

// Define the subfolders within the `SMS` project
define('APP_VIEWS', APP_ROOT . '/views');  // /var/www/html/SMS/views
define('APP_PUBLIC', APP_ROOT . '/public');  // /var/www/html/SMS/public
define('APP_CONFIG', APP_ROOT . '/config');  // /var/www/html/SMS/config
define('APP_CONTROLLERS', APP_ROOT . '/controllers');  // /var/www/html/SMS/controllers
define('APP_MODELS', APP_ROOT . '/models');  // /var/www/html/SMS/models
define('APP_SERVICES', APP_ROOT . '/services');  // /var/www/html/SMS/services
define('APP_UTILS', APP_ROOT . '/utils');  // /var/www/html/SMS/utils
define('APP_ROUTES', APP_ROOT . '/routes');  // /var/www/html/SMS/routes
define('APP_DATABASE', APP_ROOT . '/database');  // /var/www/html/SMS/database


// Define paths for public assets (CSS, JS, images, etc.)
define('APP_CSS', APP_PUBLIC . '/css');  // /var/www/html/SMS/public/css
define('APP_FONTS', APP_PUBLIC . '/fonts');  // /var/www/html/SMS/public/fonts
define('APP_IMAGES', APP_PUBLIC . '/images');  // /var/www/html/SMS/public/images
define('APP_JS', APP_PUBLIC . '/js');  // /var/www/html/SMS/public/js

// Define layout and auth paths within views
define('APP_LAY', APP_VIEWS . '/layout');  // /var/www/html/SMS/views/layout
define('APP_AUTH', APP_VIEWS . '/auth');  // /var/www/html/SMS/views/auth
define('APP_PORTALS', APP_VIEWS . '/portals');  // /var/www/html/SMS/portals
define('APP_TPORTAL', APP_PORTALS . '/tportal');  // /var/www/html/SMS/portals/tportal
define('APP_SPORTAL', APP_PORTALS . '/sportal');  // /var/www/html/SMS/portals/sportal
define('APP_STPORTAL', APP_PORTALS . '/stportal');  // /var/www/html/SMS/portals/stportal
define('APP_PPORTAL', APP_PORTALS . '/pportal');  // /var/www/html/SMS/portals/pportal



// Define paths for the sections (dynamic content)
define('APP_SEC', APP_VIEWS . '/sections');  // /var/www/html/SMS/views/sections

// Define for individual files
define('APP_HEAD_FILE', APP_LAY . '/head.php');  // /var/www/html/SMS/views/layout/head.php
define('APP_HEADER_FILE', APP_LAY . '/header.php');  // /var/www/html/SMS/views/layout/header.php
define('APP_FOOTER_FILE', APP_LAY . '/footer.php');  // /var/www/html/SMS/views/layout/footer.php
define('APP_CONN_FILE', APP_CONFIG . '/conn.php');  // /var/www/html/SMS/config/conn.php
define('APP_CCSS_FILE', '/SMS/public/css/custom-style.css');  // /var/www/html/SMS/public/css/custom-style.css
define('APP_CONFIG_FILE', APP_CONFIG . '/config.php');
// Define paths for specific sections
define('APP_SEC_INTRO_FILE', APP_SEC . '/intro.php');  // /var/www/html/SMS/views/sections/intro.php
define('APP_SEC_CP_FILE', APP_SEC . '/port.php');  // /var/www/html/SMS/views/sections/port.php
define('APP_SEC_QUO_FILE', APP_SEC . '/quote.php');  // /var/www/html/SMS/views/sections/quote.php
define('APP_SEC_FEAT_FILE', APP_SEC . '/features.php');  // /var/www/html/SMS/views/sections/features.php
define('APP_SEC_REVI_FILE', APP_SEC . '/review.php');  // /var/www/html/SMS/views/sections/review.php
define('APP_SEC_CONT_FILE', APP_SEC . '/contact.php');  // /var/www/html/SMS/views/sections/contact.php

// Define database and migrations
define('APP_DB', APP_DATABASE . '/caschool.sql');  // /var/www/html/SMS/database/caschool.sql
define('APP_DB_MIGRATIONS', APP_DATABASE . '/migrations');  // /var/www/html/SMS/database/migrations

// Define public URL paths for front-end assets (accessible via browser)
define('APP_ROOT_URL', '/SMS');  // URL path: http://localhost/SMS

// Define URL paths for specific views (accessible via browser)
define('APP_PUBLIC_URL', '/SMS/public');  // URL path: http://localhost/SMS/public
define('APP_VIEWS_URL', APP_ROOT_URL . '/views');  // URL path: http://localhost/SMS/views
define('APP_AUTH_URL', APP_VIEWS_URL . '/auth');  // URL path: http://localhost/SMS/views/auth
define('APP_LAY_URL', APP_VIEWS_URL . '/layout');  // URL path: http://localhost/SMS/views/layout
define('APP_PAGES_URL', APP_VIEWS_URL . '/pages');  // URL path: http://localhost/SMS/views/page
define('APP_SEC_URL', APP_VIEWS_URL . '/sections');  // URL path: http://localhost/SMS/views/sections
define('APP_CCSS_URL', APP_PUBLIC_URL . '/css');
define('APP_CJS_URL', APP_PUBLIC_URL . '/js');
define('APP_PORTALS_URL', APP_VIEWS_URL . '/portals');

define('APP_TPORTAL_URL', APP_PORTALS_URL . '/tportal');
define('APP_SPORTAL_URL', APP_PORTALS_URL . '/sportal');
define('APP_STPORTAL_URL', APP_PORTALS_URL . '/stportal');
define('APP_PPORTAL_URL', APP_PORTALS_URL . '/pportal');
define('APP_ADMIN_URL', APP_PORTALS_URL . '/admin');

// Define specific URL paths for individual files
define('APP_HEAD_URL', APP_LAY_URL . '/head.php');  // http://localhost/SMS/views/layout/head.php
define('APP_HEADER_URL', APP_LAY_URL . '/header.php');  // http://localhost/SMS/views/layout/header.php
define('APP_FOOTER_URL', APP_LAY_URL . '/footer.php');  // http://localhost/SMS/views/layout/footer.php

define('APP_IMAGES_URL', APP_PUBLIC_URL . '/images');  // URL path: http://localhost/SMS/public/images
?>
<?php
define('APP_TPORTAL_MENU', APP_TPORTAL . '/header.php');  // /var/www/html/SMS/views/portals//////
define('APP_STPORTAL_MENU', APP_STPORTAL . '/header.php');  // /var/www/html/SMS/views/portals//////
define('APP_SPORTAL_MENU', APP_SPORTAL . '/header.php');  // /var/www/html/SMS/views/portals//////
?>