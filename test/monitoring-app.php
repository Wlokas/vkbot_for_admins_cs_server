<?php
#!/usr/bin/php
require '/var/www/idrjepww/data/www/api.novichoknsk.ru/vkbot/SourceQuery/bootstrap.php';
use xPaw\SourceQuery\SourceQuery;

require_once '/var/www/idrjepww/data/www/api.novichoknsk.ru/vkbot/config.php'; // Подключение конфигурации

try {
    $pdo = new pdo('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=UTF8', DB_USER, DB_PASSWORD);
} catch (PDOException $e) {
    exit('Error: BD');
}

define( 'SQ_TIMEOUT',     1 );
define( 'SQ_ENGINE',      SourceQuery::SOURCE );
$Query = new SourceQuery( );
$stmt = $pdo->query("SELECT `sid`, `ip`, `port` FROM `sb_servers` WHERE `enabled` = 1");
$servers = $stmt->fetchAll(PDO::FETCH_ASSOC);
$widget = [];
$widget['title'] = "Наши сервера:";
$widget['head'][0] = ['text' => "Название"];
$widget['head'][1] = ['text' => "Игроки", "align" => "center"];
$widget['head'][2] = ['text' => "IP : Port"];
$i = 0;
$all_online = 0;
$all_max_online = 0;
foreach ($servers as $value) {

    try {
        $Query->Connect($value['ip'], $value['port'], SQ_TIMEOUT, SQ_ENGINE);
        $server_info = $Query->GetInfo();
        $value['name'] = $server_info['HostName'];
        $widget_info['players'] = ($server_info['Players'] - $server_info['Bots']) . '/' . $server_info['MaxPlayers'];
        $all_online += ($server_info['Players'] - $server_info['Bots']);
        $all_max_online += $server_info['MaxPlayers'];
    }
    catch (Exception $e) {
        $value['name'] = 'Unknown';
        $widget_info['players'] = "Выключен";
    }
    finally {
        $Query->Disconnect( );
    }
    foreach ($monitoring_servers_name as $server_name) {
        if ($value['ip'] == $server_name['ip'] && $value['port'] == $server_name['port']) {
            $value['name'] = $server_name['name'];
            $widget_info['icon_id'] = $server_name['icon_id'];
        }
    }
    $widget['body'][$i][] = ['text' => $value['name'], 'icon_id' => $widget_info['icon_id']];
    $widget['body'][$i][] = ['text' => $widget_info['players']];
    $widget['body'][$i][] = ['text' => $value['ip'] . ':' . $value['port']];
    $i++;
}
$widget['more'] = 'Общий онлайн: ' . $all_online . ' / ' . $all_max_online;
$widget['more_url'] = 'https://vk.com/novichok_servers';
$json = json_encode($widget, JSON_UNESCAPED_UNICODE);
$response = api("appWidgets.update", ['type' => 'table', 'code' => 'return ' . $json . ';']);
print_r($response);
function api($method, $params) {
    $params['access_token'] = MONITORING_ACCESS_KEY;
    $params['v'] = API_VERSION;
    $query = http_build_query($params);
    $url = 'https://api.vk.com/method/' . $method . '?' . $query;
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $json = curl_exec($curl);
    curl_close($curl);
    $response = json_decode($json, true);
    return $response['response'];
}