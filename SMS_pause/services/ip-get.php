function getUserIP() {

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {

        return $_SERVER['HTTP_CLIENT_IP'];

    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {

        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];

    } else {

        return $_SERVER['REMOTE_ADDR'];

    }

}
 
function isFromPakistan($ip) {

    $url = "http://ip-api.com/json/$ip";

    $response = file_get_contents($url);

    $data = json_decode($response, true);

    if ($data && isset($data['country'])) {

        return strtolower($data['country']) === 'pakistan';

    }

    return false;

}
 
// Usage

$ip = getUserIP();

if (isFromPakistan($ip)) {

    echo "User is from Pakistan.";

} else {

    echo "User is NOT from Pakistan.";

}
 