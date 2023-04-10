<?php
require 'SourceQuery/bootstrap.php';
use xPaw\SourceQuery\SourceQuery;
$user['auth'] = "STEAM_1:1:99674915";
preg_match('/STEAM_[0-1]:[0-1]:(\d+)/', $user['auth'], $match);
$user['auth'] = $match[1];
require_once 'config.php'; // Подключение конфигурации
try {
    $pdo = new pdo('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=UTF8', DB_USER, DB_PASSWORD);
} catch (PDOException $e) {
    exit();
}
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
define( 'SQ_TIMEOUT',     1 );
define( 'SQ_ENGINE',      SourceQuery::SOURCE );
$Query = new SourceQuery( );
$online_string = "&#9201; Онлайн администратора " . $user['nickname'] . ' &#9201;<br><br>';
$stmt = $pdo->query("SELECT admin_online.server_id AS sid, admin_online.today AS today, sb_servers.ip as ip, sb_servers.port as port FROM `admin_online` INNER JOIN `sb_servers` ON sb_servers.sid=admin_online.server_id WHERE admin_online.auth LIKE '%{$user['auth']}'");
if ($stmt->rowCount() == 0) {
    echo 'Информация о норме не найдена';
    return;
}
$admin_online = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->query('SELECT * FROM `vkbot_names_servers`');
$server_names = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($admin_online as $value) {
    try
    {
        $Query->Connect($value['ip'], $value['port'], SQ_TIMEOUT, SQ_ENGINE);
        $server_info = $Query->GetInfo();
        $value['name_server'] = $server_info['HostName'];
        $server_players = $Query->GetPlayers();
        foreach ($server_names as $server_name) {
            if ($value['sid'] == $server_name['id_server']) $value['name_server'] = $server_name['name'];
        }
        $is_online = false;
        foreach ($server_players as $server_player) {
            if ($server_player['Name'] == $user['nickname']) {
                $is_online = true;
            }
        }
        $online_string .= 'Сервер ' . $value['name_server'] . ':<br>';


        if ($value['today'] >= A_ONLINE) $emoji = "&#10004;"; // Если норма взята, выдаем эмодзи зеленого значка
        else $emoji = "";

        if ($is_online) {
            $online_emoji = "&#128215;";
        } else $online_emoji = "&#128213;";
        $online_string .= 'Отыграно ' . date("G ч. i мин. s сек.", mktime(0, 0, $value['today'])) . ' ' . $emoji . $online_emoji . '<br><br>';
    }
    catch( Exception $e )
    {
        echo 'Информацию получить не удалось';
    }
    finally
    {
        $Query->Disconnect( );
    }
}
echo $online_string;
function api($method, $params) {
    $params['access_token'] = ACCESS_TOKEN;
    $params['v'] = API_VERSION;
    $query = http_build_query($params);
    $url = 'https://api.vk.com/method/' . $method . '?' . $query;
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $json = curl_exec($curl);
    echo $json;
    curl_close($curl);
    $response = json_decode($json, true);
    return $response['response'];
}