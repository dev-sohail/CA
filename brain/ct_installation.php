<?php

/**
 * CyberAfridi Framework Installation System
 * Handles initial application setup and configuration
 */

// Prevent direct access in production
if (!defined('ROOT')) {
    define('ROOT', realpath(__DIR__ . '/..'));
}

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// Configuration paths
$configFile = ROOT . DIRECTORY_SEPARATOR . '.env';
$lockFile = ROOT . DIRECTORY_SEPARATOR . 'installed.lock';

// Initialize session securely
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

/**
 * Redirect to main application if already installed
 */
if (file_exists($lockFile)) {
    header('Location: /', true, 302);
    exit('Installation already completed. Redirecting...');
}

/**
 * Create .env file if it doesn't exist
 */
if (!file_exists($configFile)) {
    $envDir = dirname($configFile);
    if (!is_dir($envDir)) {
        mkdir($envDir, 0755, true);
    }

    $handle = fopen($configFile, 'w');
    if ($handle === false) {
        die('Error: Could not create .env file. Please check directory permissions.');
    }
    fclose($handle);
}

/**
 * Validation and sanitization functions
 */
function validateInput($input, $type = 'string')
{
    $input = trim($input);

    switch ($type) {
        case 'url':
            return filter_var($input, FILTER_VALIDATE_URL) !== false ? $input : '';
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL) !== false ? $input : '';
        case 'hostname':
            return preg_match('/^[a-zA-Z0-9.-]+$/', $input) ? $input : '';
        default:
            return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
}

function generateSecureKey($length = 32)
{
    return base64_encode(random_bytes($length));
}

function testDatabaseConnection($host, $user, $pass, $name, $port = 3306)
{
    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return ['success' => true, 'message' => 'Connection successful'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

$errors = [];
$success = false;

/**
 * Process installation form submission
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Sanitize and validate inputs
        $dbHost = validateInput($_POST['db_host'] ?? '', 'hostname');
        $dbPort = filter_var($_POST['db_port'] ?? 3306, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]) ?: 3306;
        $dbUser = validateInput($_POST['db_user'] ?? '');
        $dbPass = $_POST['db_pass'] ?? '';
        $dbName = validateInput($_POST['db_name'] ?? '');
        $siteTitle = validateInput($_POST['site_title'] ?? '');
        $siteCategory = validateInput($_POST['site_category'] ?? '');
        $siteIcon = validateInput($_POST['site_icon'] ?? '', 'url');
        $adminEmail = validateInput($_POST['admin_email'] ?? '', 'email');
        $appUrl = validateInput($_POST['app_url'] ?? '', 'url');

        // Validation
        if (empty($dbHost)) $errors[] = 'Database host is required.';
        if (empty($dbUser)) $errors[] = 'Database username is required.';
        if (empty($dbName)) $errors[] = 'Database name is required.';
        if (empty($siteTitle)) $errors[] = 'Site title is required.';
        if (empty($adminEmail)) $errors[] = 'Valid admin email is required.';
        if (empty($appUrl)) $errors[] = 'Valid application URL is required.';

        // Test database connection
        if (empty($errors)) {
            $dbTest = testDatabaseConnection($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
            if (!$dbTest['success']) {
                $errors[] = 'Database connection failed: ' . htmlspecialchars($dbTest['message']);
            }
        }

        // Generate configuration if no errors
        if (empty($errors)) {
            $appKey = generateSecureKey();
            $appSecret = generateSecureKey();
            $jwtSecret = generateSecureKey();

            $envContent = <<<ENV
#########################################
# CyberAfridi Custom Framework Settings
#########################################

APP_NAME={$siteTitle}
APP_ENV=production
SITE_CATEGORY={$siteCategory}
# DEV_MODE=0
APP_DEBUG=false
APP_URL=https://myawesomeapp.com
SITE_ICON={$siteIcon}
APP_PORT=8080
# APP_KEY=base64:ReplaceWithGeneratedKey
APP_KEY= e5875635767dd34bfa9eb349c6174adbefd800271813cc40ef26f03a072d5478745d580548eafd486758b94b48dfc9fee85c9fce2eb6720e26f2642d01e5914a4f1c684d9688e633360ca1dde17f139d039166818828b6da52980952d3bc87273618184011004923fc3434832399b9796273d74c0af7312aebad88752bb3822158e0d4c12f4d0508554105017ab6bb2cfaa36c37266a53a8559160065b34842f062525e205a8999aa226621ceb69f5d24b638825f37ed3d315284d9410b3c933ed768173c9d0ac4e81f50b4619c6a5b149732208d0c1c1489cf59123540e52d60b41d8bd52821764bca485c80af34e611b9b0f4297b2c5f7f05ae9bd606246444d5b0f5588b150739bf2cd01031ccfed89b314a5f16dad047edc4758fc6899e6d49b3f48707160083c1e25d21f888246c9823aaef6504bd9bc03b69b67cb26fbd9380004034fa3fc069f6930ab372cf74da65786e1e1977e91f4ed33db4abb789955e105cc4ef52eb6beb45868126904d50df56237c96d2caa4d837137e82e16f11ad1392161496d1bbd1a7b942c634d8fee79c587c833a6eddb189884e0f8e0e0386d83ddc2adaec6cbeaa2f461aa9e1adc50b13fd17db92778966111657eeeead5dccf20362d1f003e5dfece6b6436f31269de5c755760b58551665444dbc60318aba7aa2a77f1abab269c82e2cd33468160704ea292d783661029bcc3bb3c6a64825a0fef1472ca02bd44d6b9a44f8d076c9b824af8eed10fd9626112cc4a0f1154891f16fe56e8bea0b5c0b426f6f95ed46602e8ef0067f0dea8fb0c11ef642754216cb19c665701af9f883fef8b2fba02002afba29abed45172e1c56550c7856df1eba1e33afb644a0dcdb19125a440119bfb425ffa2c550d4fb1ac0c9ec18f0877fe9f3c3773a4a2e0cb1847f687dae9286ed0599b686fed37d4c0147793dc84a2b0fca264def16e2e1ef98f3dfcc119e44847892a8ce07784460791f27a0f31ac988db949bfb7d22237956d05b55167266dd6d8b52dd7847c71057241a6a3bf7d1e0cf79585efed8ef0f5b802d65f47a2c7cee4b567546816853e1b9c0ab142b2e140fd8c32b0f6162b937fc6f257289eb097dbc296583f3a9379c352c4f8678b6944b50ee45b3f5fdf2ae3e07123a6b63a804fe6b0f43a1c9f30d689f9d3c57c8646fcc1421b4708e13a4cf52f0028fc5d572b07aa132d2290b05a3c6a1d31c20f970336ea53e22c9f0d922188ff8c105dce5fa1046276f65a39fe1338a282b98c3351f4186f20a3e9aeb9a55293d705ed1f8d4680f39f582bc9355936c2d150ae9a6ae411df3579fc14859c342ec71e0d2eb3d85b1af12d32f09527cae1e4821d247bbdb6177d64d073bb603631d718eee30e70588484ea6a3a207a7f40bab1b191433f3bf844a67e947354c12be794b45af822569a14950e7ab182bf8f56986e71286f51b7a84953d85da538abc1204656ceb4daa46d1d18b1040b57cfb67581d726ade57a5fef0b814dcd13cb3474a1314b9e679fe604a9bad5d06078299545dcc816cb278ac4d7d7d5542b08f21437ac500997e39262a62f72f09a05f37b44e9f7538c39387d920a921a4512c878de13790ef82ee3ec5fa92ab509515b1dca866e3d5e4b0c4dc6db8dea9e26b8e859b90b2c3e3dfe9a84b1286232b60b291229b478ad83ecfa80e65e3df2539b64fbdbd30757367114a7ff35ecea0fa102c7b0aaf6de56a747287817948098f8c2e87e57857b57a3003ea1d5349a2a834bb6c3aea083f25051de832dc37b437c37047e145471e512e37da88a78a7e371158adae748965116d905f6dd4fcc971ed7eed619031a4ee5fde56480b11f3f447900cbd931a0144a91f7645870c18efc8c6063187c4b4503398a2bcb0ff32d893a63aee0b14755e1efbc44f36e001f9acdecb486df54c2c589eb394e5030ef4e151b9498cfca86f861624b4fdea6862ba4a44f359115a0479625951ccf18e7bba17315bbef2ffe869689c7dd16e50169c1a9fad77375860b0f0265c95c58b7d0e768f251aab9f42430cab6d79233e95476a961d9654d45535efd45664bd540bcae6cc6f6df3b98c9bf9ad94dfef96111ff3b5fa5e177140578713ad13e12fc4146af59d25d00cc569131a0be1eea1576546c25c66ffd2451a6df220b4c99c2c2dddd449a766c2ba12201990d85cd8b79343580cd7b9d99e663e796e5871206ccce3b6f26bbcd907635362706252d65f5e65c361f15497f385235e0763b377dd2bbad58ad32e1f2ca75c9afcd2cda1c925a44cdbd8c0310b9bd22092cd1f0af5a5bd600a1b8d76488b89827316927c84144d6ec9f3dd44fa76df841ee8d8e3276511152fac6c7cc6958ee8554cab3ea7c9c61a43e4c45b2f0d2b2593fd69801f2e8e096e84d4e2d1c9c63df02487d7df9b14ee1de5f55140382bc243169dd43b91604728fda418a33e35ee85e6194d6e4eb11b8670adf3f72a7cf8a592dcb0d6f4623a67d6183fe6ac1e14c301e83a364628bc9ef84e0e7a03fee95888ae3074b9f83a180bd558a829b79e0470232e39c7648ef96ed778aa383457061790f0cc18d82c3b53cc4348126fef4494d3ddf38b651960ef119f512c052eece881dd6bf9e697c96afb68867fabccf3fa871fcc296b6e99a2205bc2e01a3bc774fcd8c5496b5972c5e5b8407b847c796deec90b4d964ddb73821c5ea3b03eaddd9b52ab5dedc4528c6114513bc457dc76b1227ca735ef251f8fcb89a3f69a7648712afeef2c258231aebca786ad2b6cfe40c43d70bdb7be073434b55ae82f2d2ee10a7ba82ab897b9c7cb9c8fa236d640a4e5f75d742a2de4c6de0a71147f03b2b57662a0b94aaccef3c709534ce8e15c844ce5075f328ba5eba9bcc99e1125ce49b4a84e9e53e38fc69b0263c2b7af0409838ff1d2e8ba90faaa3922a38aa77ef375aebb4317c6a6dbfee1d0c2b8ac0638eee13bc2f19262b1cb66b4c2e126e7e9c6cad49cbe7461ac79417047c908a372899b4a9fc7b4d6fc316b1920ab4be241369f76a6b2857ee101d8bbbba4349abf2a76b11f0285ac656c2e8fe47db40d253ed83046bea52074d95ea174d6dc53d62831f375919793968292171bac6f202bf21f91141506578ae1c4ad90f1134bf5145f780db475dc9042a49ce3f8d3ef88e6d324ddc316d0ae099bced394970c9e28954b017fae4a5107b5f1136617afcd2af7c45729b54ad002ba48f37db1a90c2bc157e60750b21175fd47f025f50867c78e38e750e6dfdbfb458301420e377763d949713dae1772acf6a57792546e1c8056d035d99adde75c00c7fbabd1e532c7d2b39447402355e2f70249edf07475a151feed5d8283649c74dfa31dfa466709498b65fbb35841e8de84049712845e60581c2742265a3a2b7072973648fb0d7f3aeef8fabb0c72bf0d46e4443bce54a9e053e12aca58998800b17c29f8199bd3062547012cd44a596e442f712ac19bff4739a882ae5d2012c391343a3aa312a799f70725c316026b12c46866d87a8bc32e0477d6644fc83c628121766ab6935d7c06217f6ea0ae0f17151a37d8a3c18854d29c98e940b2cdaf0206782803bd6b1d69af1f15783c76189c593d529f659c922ae003df69aaf95fe3a0b7251f42dcfed1ca8815610f46ec9f3e445bc21820aa7db9480aef205c2e2bae33ed5c2711a071048d53767dedd7800217268ab8104c257f06e3c207efe4a48e7ab2cb6588f5de0f3a9c052e4dd714f4a7417b6dae181765effe00731294a6cee247e372722eed333f937e730034bb9d59f4b8d333637e9aeff249e03ec39da5218bb7bfb5eb756df2d16e9feebf7fded3dc10f5072eee2b5d49509ba3a35faeee2251d7d8efeb2774ba970b56cb536f341ecbf86bc6e0d35ea2bdb2c8196aa5179c57b76acf2a2160d2c805ecaacfcfc93d027df004854b4a1590f59aa9799c6281f69a4826f236ea58b8f09b68e351324f1d2fb55a97ff6fb4663f3d9aeeb7af138475a4e768379a721356b2c1a860f5e29b295e646b3dcb1dd32e9df9335920e3a0cbffc54ffbf647a638601c29da9977705e9301a0e09ff41c8e86c4aa9e0031aaa2d7898af204d4bc18aad106449c2d92197b0c7ee979ba2a1cc5b439405af0526e6e326440cf47d879d990b3a441138335ecadfe71d81924f5809fd537ad0b21028848cfe68dacf1cdf26e4b9586d195571e179b992ca2a65be9fe82ede0e17bd3d81e6d9105543574094f4cd7bfccc2e3966a086f7a3292b80f589a70e60af909d68cd25c42c159eb92b42725c9fa0978a19cca577fdaacbc893ed9ce6199d76c946a02ca0d60810e682c5ce543a19c99502b3ded9e7d77925b7a201932787412b3e28322c5c9957b66d8e997bba0cb29148ee51019565954a60b27f1376a751095aa5696d9bfba9ff05d79edb81280f8c1ebaaa241d31472aebb76ccb29ecfe008b0dad41d978bc5dae4ed81573eb5a0d4ba7d41dcd0264420de10e88ad0907e8c046f5ac832750ef626669952e673fd2033854e20f098459fd6cbe94892bb84ce42b366f22e6170015bdad2994014a04c046462c34672c9820a6ead6b4f7edb1992f10c4f65574b9c8cc709721d6608f6dfda09eacc6393bca869322a9aed66ecf5cfad9b6e220c47868c2d135baec3bc840f8f6e63004edd4b514944052a0c4c9ff84e0adb43ae3f2a44a3af686d790fde15980f382da0b5e5cdf3aff96b53cdce78f9fa1d4a278211c19eefae4314de974d39258fc3628b9ccf09c775c9f202038dc5e175b6dcc79439663034b74553a2b87a987e46705094a3a4f5c9bdb157389e3bb0d77057d13e6faee1a73c41ea61ed6ce966ef93053f56710adcabdeb795571cf8352d9fd625404f626b998985be3b317a2d2b10c7d187a73a0b4460a51baa8c83b1636370c7bbb105a2d0d54a5da167787c36ef6eb40394fa61abaa47a22ed1510573e04920b50ca1e26ebc959e3a89ce7cb0b263f74a9c57464aeac3a3ab202c4a84f7f7c4ac35358641f79797ad12f2438b689def76263c99c25e34493a735e955cc22701a0cd56c26bb210287a57eb6752d93ca7c7505e89e9d64fc504dc9bde7eef85598d0d664cef9fd1479da73db4a439fae7ae7040d582eec894118f16783fc42042c60d245d999332d9029518280044ca013f3e24bb3a3cdb1f44cae91fd380ca3f692972994db25ac3ad0cf23c0afd947384850625277bc110f08fcc213f7d1b5bfe1c73943481fb7747b5a938af8c4b2531fbdaa9c3da5c73a16ca08b21927b56f00ddc868391442f0c86ff35f3da039213e84689c275ff5104516affb805784d7a673107f2433e2cc6b6eb5d7c14c9ad04ceef3223628b996485f07c65acdd4398312a485601bda408000d0f070c26112a234693efe56c54aeee9e0d9ddf40620d7a2a8f8e3260ba3969a87806287830eed410f009b231a53b6042d17c5de306e9e86c0eb1bd3183642e39af0859901f97038a7c04f6041eaffc237153e7fa2528428191565004121dcef3aefd26cc64cc1d9cb571f992ccd8a96ef70e847bd51654da71e9dca3333da5f13551c7eaf6482fd1b4e1daaa3c577839b0cc67a608a55b43a61ff83601103798abd29d61061adf53af6cf34aa64a285892bf9470a3c09f6479756d7b085e47d28558dc23679df5a837135f4bd7e2f2b290bbf186225f5fa1f341be2ea26d8ad418c8991337087a557da1b57559c4ceebc9c2e78b2ecb9f46191b4b5ba5f508913a33dcd2c666231cc1ad885b95d4de43792c56685a1aa963c600fcf0d8006c38d167c83af72a57ffd8f86f0d166816beba93f62174fa149767349509af410553b71beb5df082fd15bf0b33c4343fe9c55ba7773fa17ceef4950eaf5b9373eef3aca7d793e49649f0678ba9c6ce820d419482e88efd897e17fd9de3ecc0801d998657615bf4c2aa3bce3d042f22668433e78271bf5f301576ad638621da3458aab19b6d920ea816fd907d72108e1ffb6a9291e00ca2f2dc6deaf8f2d74a895bc7597a4d8f33e94d3c7ec2cebef1d03a49fb84b8fb115be4df8467459af8a5696a8f0f9f4b893bae7dc0039ed7f344dcc07abc9413de80169e49e91f3dddc3e3f9a2bb4fba4a0c398f68c67ae24f618def63361b52084125c4b309eda535267a514a170766681f8f3075c1cd0e2e301ccb32f5cc613c70493d3b53e55c19c2a664f5c9e5c10d4e6ef1b0a395d3bf05b03030452982036cd5ed00dd94802b5dfce7eae97a6f8675614b2633d7dacffec328e344ec347c898d47cf172d796531fc8be8876c96af059999979f1f7f11d84267f8b123e290cff43b708126525780d1858c565abf16ed522990b397160e69036ac45fccd0aca39948654cc2ce093ad11476c20ffaac9b852e0486c0d370d101923b399b34c072753fef391974304b9b243074b501c581d021aa6d92dc32874c957e87ee6a71c285d9a7bf5115924f1151c3637876f568112a99373231c30b86b2f31c9b273deddd735a0d64a64a33411dc4d69ab41b98eb1b1ba77e6ba75c380558dc6a6b678c1e5361cdf50255e0d6c6f39ecffbe0f630f9ebdd3d4a1ccb46e4d2977062262dc69dd86ac765daf6a41e9aef0399e8065e2fec0fbfffe3bd6f5455c0664e57e47354dfaefbce77bf044f210db3cfdab2220b196d12a7ca6c9508089781de3da13e12f54399e0e91e4b878e3546a5c199ec00e179173ef1dee433a075ff480a7d7a0ac338cae51e5a8e8ceb19c60bbdaf0b1e7d2fab675ac1b04a570ef0a236e9f96df9d095ecb5bb2c071d4d80904b8a396655fdfa4945f29670bcbff2bf86e0c3bc7ae5701d111fca02ba5500c2f66e3fec7945b2a7bac9eac99513c131078fe422004d543d3d2b4f7c50971d8a49ec33f33da297685b1e66705bd25dbc9ae468fdca3d2105b9f3286f67bfbaaad89df74e25d5e1a294a7e1f3a8ea76163feccf06d28fd03a068905fe9405f3f0b2007d210544b6dc2d908f1c99d6e982afbbe365df4c3c0a869b13039bc39b5c39c4509c190c1773d2a698daba388b1a70249395dbaf545fcc4b899fe3094
APP_SECRET=supersecretstring
APP_TYPE=general #general, e-commerance, managment-system
TIMEZONE=UTC
LOCALE=en

# =========================================
# HTTP CONFIGURATION
# =========================================
FORCE_HTTPS=
#if empty => http else https

# =========================================
# DATABASE CONFIGURATION
# =========================================
# DB_CONNECTION=pgsql
# DB_HOST=db
# DB_PORT=5432
# DB_DATABASE=myapp_db
# DB_USERNAME=myapp_user
# DB_PASSWORD=supersecurepassword
DB_DRIVER=mysql
DB_HOST={$dbHost}
DB_USERNAME={$dbUser}
DB_PASSWORD={$dbPass}
DB_DATABASE={$dbName}
DB_PREFIX=

# Secondary/Read Replica (Optional)
DB_READ_HOST=replica-db
DB_READ_PORT=5432
DB_READ_USERNAME=readonly
DB_READ_PASSWORD=readonlypass

# =========================================
# REDIS / CACHE CONFIG
# =========================================
# CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
SESSION_NAME=caframework_session
CACHE_DRIVER=file
CACHE_TTL=3600

# =========================================
# MAIL SETTINGS
# =========================================
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=hello@myawesomeapp.com
MAIL_FROM_NAME="MyAwesomeApp"
CONFIG_SMTP_TIMEOUT=30

# =========================================
# LOGGING & ERROR TRACKING
# =========================================
LOG_CHANNEL=stack
# LOG_LEVEL=info
MEMORY_LIMIT=256M
TIME_LIMIT=3600
LOG_LEVEL=debug
LOG_FILE=storage/logs/error.log
SENTRY_DSN=https://examplePublicKey@o0.ingest.sentry.io/0
SENTRY_ENVIRONMENT=production

# =========================================
# CLOUD STORAGE (S3, MinIO, etc)
# =========================================
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your_aws_key
AWS_SECRET_ACCESS_KEY=your_aws_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=myapp-bucket
AWS_URL=https://myapp-bucket.s3.amazonaws.com
AWS_ENDPOINT=https://s3.amazonaws.com
AWS_USE_PATH_STYLE_ENDPOINT=false

# =========================================
# PAYMENT GATEWAYS
# =========================================
STRIPE_KEY=pk_test_123456789
STRIPE_SECRET=sk_test_abcdefg
PAYPAL_CLIENT_ID=your-paypal-client-id
PAYPAL_CLIENT_SECRET=your-paypal-secret
PAYMENT_CURRENCY=USD

# =========================================
# OAUTH / SOCIAL AUTH
# =========================================
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
FACEBOOK_CLIENT_ID=your-fb-client-id
FACEBOOK_CLIENT_SECRET=your-fb-client-secret
GITHUB_CLIENT_ID=your-github-client-id
GITHUB_CLIENT_SECRET=your-github-client-secret
OAUTH_REDIRECT_URI=https://myawesomeapp.com/oauth/callback

# =========================================
# THIRD-PARTY SERVICES
# =========================================
RECAPTCHA_SITE_KEY=your-recaptcha-site-key
RECAPTCHA_SECRET_KEY=your-recaptcha-secret-key
SENDGRID_API_KEY=your-sendgrid-api-key
TWILIO_ACCOUNT_SID=your-twilio-sid
TWILIO_AUTH_TOKEN=your-twilio-token
TWILIO_PHONE_NUMBER=+1234567890

# =========================================
# SECURITY
# =========================================
JWT_SECRET=supersecretjwtkey
JWT_TTL=3600
# SESSION_LIFETIME=120
SESSION_LIFETIME=7200
CORS_ALLOWED_ORIGINS=https://frontend.myawesomeapp.com
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE
CORS_ALLOWED_HEADERS=Authorization,Content-Type

# =========================================
# FEATURE FLAGS
# =========================================
FEATURE_REGISTRATION=true
FEATURE_BETA_ACCESS=false
FEATURE_TWO_FACTOR_AUTH=true

# =========================================
# FRONTEND / SPA INTEGRATION
# =========================================
FRONTEND_URL=https://frontend.myawesomeapp.com
API_URL=https://api.myawesomeapp.com

# =========================================
# DOCKER / KUBERNETES
# =========================================
DOCKER_ENV=true
K8S_NAMESPACE=myapp-prod
CONFIG_RELOAD_TOKEN=optional_reload_token

# =========================================
# CI / CD INTEGRATIONS
# =========================================
GITHUB_TOKEN=ghp_xxx
CI_BRANCH=main
CI_COMMIT_SHA=abc123def456
CI_DEPLOY_ENV=production

# =========================================
# MONITORING / APM
# =========================================
DATADOG_API_KEY=your-datadog-key
NEW_RELIC_LICENSE_KEY=your-newrelic-license-key
PROMETHEUS_ENABLED=true
HEALTHCHECK_URL=https://myawesomeapp.com/health
ENV;

            // Save configuration file
            if (file_put_contents($configFile, $envContent, LOCK_EX) !== false) {
                // Set secure permissions
                chmod($configFile, 0600);

                // Create installation lock file
                $lockContent = json_encode([
                    'installed_at' => date('c'),
                    'version' => '1.0.0',
                    'installer_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);

                if (file_put_contents($lockFile, $lockContent, LOCK_EX) !== false) {
                    chmod($lockFile, 0644);
                    $_SESSION['installation_completed'] = true;
                    $success = true;
                } else {
                    $errors[] = 'Could not create installation lock file.';
                }
            } else {
                $errors[] = 'Could not write configuration file. Please check permissions.';
            }
        }
    }
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Auto-redirect after successful installation
if ($success) {
    echo '<script>
        setTimeout(function() {
            window.location.href = "/";
        }, 3000);
    </script>';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>CyberAfridi Framework - Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .installation-container {
            margin-top: 2rem;
            margin-bottom: 2rem;
        }

        .card {
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: none;
        }

        .card-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #5a67d8, #6b46c1);
        }

        .success-message {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .form-label {
            font-weight: 600;
        }

        .required {
            color: #dc3545;
        }
    </style>
</head>

<body>
    <div class="container installation-container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header text-center">
                        <h2 class="mb-0">
                            <i class="fas fa-cog me-2"></i>
                            CyberAfridi Framework Installation
                        </h2>
                        <p class="mb-0 mt-2">Configure your application settings</p>
                    </div>
                    <div class="card-body p-4">

                        <?php if ($success): ?>
                            <div class="alert alert-success success-message text-center" role="alert">
                                <i class="fas fa-check-circle fa-2x mb-3"></i>
                                <h4>Installation Completed Successfully!</h4>
                                <p>Your application has been configured and is ready to use.</p>
                                <p><small>Redirecting to your application in 3 seconds...</small></p>
                                <a href="/" class="btn btn-success">Go to Application</a>
                            </div>
                        <?php else: ?>

                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Please fix the following errors:</strong>
                                    <ul class="mb-0 mt-2">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <form method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                                <div class="row">
                                    <!-- Database Configuration -->
                                    <div class="col-md-6">
                                        <div class="card mb-4">
                                            <div class="card-header">
                                                <h5 class="mb-0"><i class="fas fa-database me-2"></i>Database Configuration</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label for="db_host" class="form-label">Database Host <span class="required">*</span></label>
                                                    <input type="text" class="form-control" id="db_host" name="db_host"
                                                        value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost'); ?>"
                                                        placeholder="localhost" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="db_port" class="form-label">Database Port</label>
                                                    <input type="number" class="form-control" id="db_port" name="db_port"
                                                        value="<?php echo htmlspecialchars($_POST['db_port'] ?? '3306'); ?>"
                                                        min="1" max="65535">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="db_user" class="form-label">Database Username <span class="required">*</span></label>
                                                    <input type="text" class="form-control" id="db_user" name="db_user"
                                                        value="<?php echo htmlspecialchars($_POST['db_user'] ?? ''); ?>" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="db_pass" class="form-label">Database Password</label>
                                                    <input type="password" class="form-control" id="db_pass" name="db_pass">
                                                    <div class="form-text">Leave blank if no password is required</div>
                                                </div>

                                                <div class="mb-0">
                                                    <label for="db_name" class="form-label">Database Name <span class="required">*</span></label>
                                                    <input type="text" class="form-control" id="db_name" name="db_name"
                                                        value="<?php echo htmlspecialchars($_POST['db_name'] ?? ''); ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Application Configuration -->
                                    <div class="col-md-6">
                                        <div class="card mb-4">
                                            <div class="card-header">
                                                <h5 class="mb-0"><i class="fas fa-globe me-2"></i>Application Settings</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label for="site_title" class="form-label">Application Name <span class="required">*</span></label>
                                                    <input type="text" class="form-control" id="site_title" name="site_title"
                                                        value="<?php echo htmlspecialchars($_POST['site_title'] ?? ''); ?>"
                                                        placeholder="My Awesome App" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="app_url" class="form-label">Application URL <span class="required">*</span></label>
                                                    <input type="url" class="form-control" id="app_url" name="app_url"
                                                        value="<?php echo htmlspecialchars($_POST['app_url'] ?? 'https://'); ?>"
                                                        placeholder="https://myawesomeapp.com" required>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="site_category" class="form-label">Site Category</label>
                                                    <input type="text" class="form-control" id="site_category" name="site_category"
                                                        value="<?php echo htmlspecialchars($_POST['site_category'] ?? ''); ?>"
                                                        placeholder="Business, Blog, Portfolio, etc.">
                                                </div>

                                                <div class="mb-3">
                                                    <label for="admin_email" class="form-label">Administrator Email <span class="required">*</span></label>
                                                    <input type="email" class="form-control" id="admin_email" name="admin_email"
                                                        value="<?php echo htmlspecialchars($_POST['admin_email'] ?? ''); ?>"
                                                        placeholder="admin@myawesomeapp.com" required>
                                                </div>

                                                <div class="mb-0">
                                                    <label for="site_icon" class="form-label">Site Icon URL</label>
                                                    <input type="url" class="form-control" id="site_icon" name="site_icon"
                                                        value="<?php echo htmlspecialchars($_POST['site_icon'] ?? ''); ?>"
                                                        placeholder="https://example.com/icon.png">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg px-5">
                                        <i class="fas fa-rocket me-2"></i>
                                        Install Application
                                    </button>
                                </div>
                            </form>

                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
</body>

</html>