<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
// http://78.24.222.131/report.php?secret=7s382s33&server_id=1&get_report_steamid=STEAM_0_1:123456&get_report_user=Wlokas&report_steamid=STEAM_1_1:111111&report_user=aNdrey&reason=cheat
require_once 'config.php'; // Подключение конфигурации
ini_set('date.timezone', 'Europe/Moscow'); // Устанавливаем часовой пояс МСК
if (!isset($_REQUEST)) {
    return;
}
require 'SourceQuery/bootstrap.php';
use xPaw\SourceQuery\SourceQuery;
if (isset($_GET['secret']) && $_GET['secret'] == REPORT_SECRET) {
    if(isset($_GET['server_id']) && isset($_GET['get_report_steamid'])&& isset($_GET['get_report_user']) && isset($_GET['report_steamid']) && isset($_GET['report_user']) && isset($_GET['reason'])) {
        define( 'SQ_TIMEOUT',     1 );
        define( 'SQ_ENGINE',      SourceQuery::SOURCE );
        $Query = new SourceQuery( );
        try {
            $pdo = new pdo('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=UTF8', DB_USER, DB_PASSWORD);
        } catch (PDOException $e) {
            send_message(1, "Ошибка подключение к базе данных, обратитесь к разработчику.");
            exit('Error: connect BD');
        }
        $report_string = "&#9888; Пришел репорт на игрока " . $_GET['report_user']. ' &#9888;<br><br>';
        $stmt = $pdo->query("SELECT `sid`, `ip`, `port` FROM `sb_servers` WHERE `enabled` = 1 AND `sid` = {$_GET['server_id']}");
        $server = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
        $stmt = $pdo->query("SELECT * FROM `vkbot_names_servers` WHERE `id_server` = {$_GET['server_id']}");
        $server_names = $stmt->fetchAll(PDO::FETCH_ASSOC);
            try
            {
                foreach ($server_names as $server_name) {
                    if ($server['sid'] == $server_name['id_server']) $server['name'] = $server_name['name'];
                }
                if ($server_name == NULL) {
                    $Query->Connect($server['ip'], $server['port'], SQ_TIMEOUT, SQ_ENGINE);
                    $server_info = $Query->GetInfo();
                    $server['name'] = $server_info['HostName'];
                }
                $report_string .= "Сервер: " . $server['name'].'<br>';
            }
            catch( Exception $e )
            {
                send_message(1, 'Боту не удалось получить информацию с сервера');
            }
            finally
            {
                $Query->Disconnect( );
            }
            $report_string .= "Подал жалобу: " . $_GET['get_report_user'] . '<br>';
            $report_string .= "STEAM_ID подавшего жалобу: " . $_GET['get_report_steamid'] . '<br>';
            $report_string .= "Ник нарушителя: " . $_GET['report_user'] . '<br>';
            $report_string .= "STEAM_ID нарушителя: " . $_GET['report_steamid'] . '<br>';
            $report_string .= "Причина: " . $_GET['reason'];
        $stmt = $pdo->query("SELECT `chat_id` FROM `vkbot_conversations` WHERE `name` = 'report'");
        $chats_id = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($chats_id as $value) {
            send_message($value['chat_id'], $report_string);
        }
        echo 'ok';
    } else exit("Error: params");
} else exit();

function send_message($chat_id, $message) {
    $random_id = rand(0, 999999999999);
    api('messages.send', array(
        'chat_id' => $chat_id,
        'message' => $message,
        'disable_mentions' => 1,
        'random_id' => $random_id,
    ));
}

function api($method, $params) {
    $params['access_token'] = ACCESS_TOKEN;
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