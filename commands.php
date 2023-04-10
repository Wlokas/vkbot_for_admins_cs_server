<?php

require 'SourceQuery/bootstrap.php';
use xPaw\SourceQuery\SourceQuery;

$comands = [ // Команды бота

    "setconv" => [
        'reg_params' => "/^([0-4])$/",
        'short_description' => "/setconv - Установить/убрать классификацию беседе",
        'description' => "/setconv [Номер Классификации]
                   0 - Удалить классификацию,
                   1 - Беседа Администрации сервера,
                   2 - Техническая беседа разработчиков,
                   3 - Беседа репорта
                   4 - Свободная конференция..",
        "access" => ["Куратор", "Создатель"],
        "conversations" => NULL,
        'function' => function($data, $params) use ($pdo, $chat_id, $user_id, $user_info, $user) {
            //global $names_conversations;
            switch ($params[1]) {
                case 0:
                    $pdo->query("DELETE FROM `vkbot_conversations` WHERE `chat_id` = {$chat_id}");
                    send_message($chat_id, "&#9989; Классификация беседы удалена. &#9989;");
                    return;
                    break;
                case 1: $name_conv = "admin_team"; break;
                case 2: $name_conv = "dev_team"; break;
                case 3: $name_conv = "report"; break;
                case 4: $name_conv = "free"; break;
            }
            $stmp = $pdo->query("SELECT `name`, `chat_id` FROM `vkbot_conversations` WHERE `chat_id` = {$chat_id}");
            if ($stmp->rowCount() != 0) {
                $pdo->query("UPDATE `vkbot_conversations` SET `name`= '{$name_conv}' WHERE `chat_id` = {$chat_id}");
                send_message($chat_id, "&#9989; Классификация беседы успешно изменена. &#9989; ");
            } else {
                $pdo->query("INSERT INTO `vkbot_conversations` (`name`, `chat_id`, `install_id`) VALUES ('$name_conv', {$chat_id}, {$user_id})");
                send_message($chat_id, "&#9989; Классификация беседы успешно установлена. &#9989;");
            }
            //api("messages.editChat", ['chat_id' => $chat_id, 'title' => '[NOVICHOK] ' . $names_conversations[$name_conv]]);
        }
    ],
    "news" => [
        'reg_params' => NULL,
        'short_description' => "/news - Показать последнюю новость группы",
        'description' => "/news - Показать последнюю новость группы",
        "access" => ["Куратор", "Создатель", "Главный Администратор", "Зам. Главного Администратора", "Администратор", "Пользователь"],
        "conversations" => ['admin_team', 'dev_team', 'free'],
        'function' => function($data, $params) use ($pdo, $chat_id, $user_id, $user_info, $user) {
            $params_api = [
                "owner_id" => MAIN_GROUP_ID * -1,
                "count" => 1,
            ];
            $params_api['access_token'] = "775851b8775851b8775851b83c773658af77758775851b82a88062861c1c1977974ad21";
            $params_api['v'] = API_VERSION;
            $query = http_build_query($params_api);
            $url = 'https://api.vk.com/method/' . "wall.get" . '?' . $query;
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $json = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($json, true);
            $wall_id = $response['response']['items'][0]['id'];
            $wall_owner_id = $response['response']['items'][0]['owner_id'];
            api('messages.send', array(
                'chat_id' => $chat_id,
                'message' => 'Последний пост в группе:',
                'attachment' => "wall" . $wall_owner_id . "_" . $wall_id,
                'disable_mentions' => 1,
                'random_id' => 0,
            ));
        }
    ],
    "stats" => [
        'reg_params' => NULL,
        'short_description' => "/stats - Узнать информацию о себе",
        'description' => "/stats - Узнать информацию о себе",
        "access" => ["Куратор", "Создатель", "Главный Администратор", "Зам. Главного Администратора", "Администратор", "Пользователь"],
        "conversations" => ['admin_team', 'dev_team', 'free'],
        'function' => function($data, $params) use ($pdo, $chat_id, $user_id) {
            global $user;
            $stats_string = "&#127760; Информация о " . $user['name'];
            if ($user['id'] != -1) $stats_string .= " ({$user['nickname']}): &#127760;<br><br>Ник на проекте: {$user['nickname']}<br>";
            else $stats_string .= ": &#127760;<br><br>Ник на проекте: &#9888; Не найден &#9888;<br>";
            $stats_string .= "Группа: " . $user['group'] . '<br><br>';
            $stats_string .= '&#128190; Статистика беседы: &#128190;<br><br>';
            $stmt = $pdo->query("SELECT * FROM `vkbot_users_stats` WHERE `vk_id` = {$user_id}");
            if ($stmt->rowCount() != 0) {
                $stats = $stmt->fetchAll()[0];
            } else $stats = ["messages" => 0, 'voices' => 0, 'images' => 0, 'videos' => 0, 'other' => 0];
            $stats_string .= "Сообщения: " . $stats['messages'] . '<br>';
            $stats_string .= "Голосовые сообщения: " . $stats['voices'] . '<br>';
            $stats_string .= "Картинок отправлено: " . $stats['images'] . '<br>';
            $stats_string .= "Видео отправлено: " . $stats['videos'] . '<br>';
            $stats_string .= "Остальное: " . $stats['other'] . '<br><br>';
            $response = api("messages.getConversationMembers", ['peer_id' => 2000000000 + $chat_id, 'group_id' => $data->object->id]);
            foreach ($response['items'] as $value) {
                if ($value['member_id'] == $user['id_vk']) {
                    $stats_string .= "Приглашен в беседу: " . date("d.m.Y", $value['join_date']) . "<br><br>";
                    break;
                }
            }
            if ($user['id'] != -1) {
                define( 'SQ_TIMEOUT',     1 );
                define( 'SQ_ENGINE',      SourceQuery::SOURCE );
                $Query = new SourceQuery( );
                $stmt = $pdo->query('SELECT * FROM `vkbot_names_servers`');
                $server_names = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt = $pdo->query("SELECT `sid`, `ip`, `port` FROM `sb_servers` INNER JOIN `sb_admins_servers_groups` ON sb_admins_servers_groups.admin_id = {$user['id']} WHERE `enabled` = 1 AND sb_admins_servers_groups.server_id = sb_servers.sid");
                $servers= $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stats_string .= "<br><br>&#128305; Администратор на серверах: &#128305;<br>";
                $count_server = 1;
                foreach ($servers as $value) {
                    try
                    {
                        foreach ($server_names as $server_name) {
                            if ($value['sid'] == $server_name['id_server']) $value['name'] = $server_name['name'];
                        }
                        if ($value['name'] == NULL) {
                            $Query->Connect($value['ip'], $value['port'], SQ_TIMEOUT, SQ_ENGINE);
                            $server_info = $Query->GetInfo();
                            $value['name'] = $server_info['HostName'];
                        }
                        $stats_string .= $count_server . '. ' . $value['name'] . '<br>';
                        $count_server++;
                    }
                    catch( Exception $e )
                    {
                        send_message($chat_id, 'Боту не удалось получить информацию с сервера: '. $value['name']);
                    }
                    finally
                    {
                        $Query->Disconnect( );
                    }
                }
            }
            send_message($chat_id, $stats_string);
        }
    ],
    "who" => [
        'reg_params' => "/(.+)/",
        'short_description' => "/who - Узнать информацию о человеке из беседы",
        'description' => "/who [Упомянуть/Ник] - Узнать информацию о человеке из беседы",
        "access" => ["Куратор", "Создатель", "Главный Администратор", "Зам. Главного Администратора", "Администратор", "Пользователь"],
        "conversations" => ['admin_team', 'dev_team', 'free'],
        'function' => function($data, $params, $params_string) use ($pdo, $chat_id, $user_id) {
            global $user;
            $is_find = false;
            $info_string = "&#128100; Информация о человеке &#128100;<br><br>";
            if (preg_match("/\[id(\d+)\|.+\]/", $params_string, $match)) {
                $user_vk_info = api('users.get', [
                    'user_ids' => $match[1],
                    'fields' => 'domain'
                ])[0];
                $stmt = $pdo->query("SELECT `user`, `aid` as `id`, `srv_group` as `group` FROM `sb_admins` WHERE `vk` = '{$user_vk_info['domain']}'");
                if ($stmt->rowCount() != 0) {
                    $is_find = true;
                    $user_info = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
                    $info_string .= "Ник: " . $user_info['user'] . '<br>';
                    $info_string .= "ВК: [id" . $user_vk_info['id'] . '|' . $user_vk_info['first_name'] . ' ' . $user_vk_info['last_name'] . ']' . '<br>';
                } else $info_string .= "&#9888; Игрок с таким ВК не найден &#9888;";
            }
            else
            {
                $stmt = $pdo->query("SELECT `user`, `aid` as `id`, `srv_group` as `group`, `vk` FROM `sb_admins` WHERE `user` = '$params_string'");
                if ($stmt->rowCount() != 0) {
                    $is_find = true;
                    $user_info = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
                    $user_vk_info = api('users.get', [
                        'user_ids' => $user_info['vk'],
                        'fields' => 'domain'
                    ])[0];
                    $info_string .= "Ник: " . $user_info['user'] . '<br>';
                    $info_string .= "ВК: [id" . $user_vk_info['id'] . '|' . $user_vk_info['first_name'] . ' ' . $user_vk_info['last_name'] . ']' . '<br>';
                    $info_string .= "Группа: " . $user_info['group'] . '<br>';
                } else $info_string .= "&#9888; Игрок с таким ником не найден &#9888;";
            }
            if ($is_find) {
                define( 'SQ_TIMEOUT',     1 );
                define( 'SQ_ENGINE',      SourceQuery::SOURCE );
                $Query = new SourceQuery( );
                $stmt = $pdo->query("SELECT `sid`, `ip`, `port` FROM `sb_servers` INNER JOIN `sb_admins_servers_groups` ON sb_admins_servers_groups.admin_id = {$user_info['id']} WHERE `enabled` = 1 AND sb_admins_servers_groups.server_id = sb_servers.sid");
                $servers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $info_string .= "<br><br>&#128305; Администратор на серверах: &#128305;<br>";
                $stmt = $pdo->query('SELECT * FROM `vkbot_names_servers`');
                $server_names = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $count_server = 1;
                foreach ($servers as $value) {
                    try
                    {
                        foreach ($server_names as $server_name) {
                            if ($value['sid'] == $server_name['id_server']) $value['name'] = $server_name['name'];
                        }
                        if ($value['name'] == NULL) {
                            $Query->Connect($value['ip'], $value['port'], SQ_TIMEOUT, SQ_ENGINE);
                            $server_info = $Query->GetInfo();
                            $value['name'] = $server_info['HostName'];
                        }
                        $info_string .= $count_server . '. ' . $value['name'] . '<br>';
                        $count_server++;
                    }
                    catch( Exception $e )
                    {
                        send_message($chat_id, 'Боту не удалось получить информацию с сервера');
                    }
                    finally
                    {
                        $Query->Disconnect( );
                    }
                }
            }
            send_message($chat_id, $info_string);
        }
    ],
    "setservername" => [
        'reg_params' => NULL,
        'short_description' => "/setservername - Установить название сервера для беседы",
        'description' => "/setservername [ID сервера] [Новое название] - Установить название сервера для беседы",
        "access" => ["Куратор", "Создатель"],
        "conversations" => ['dev_team'],
        'function' => function($data, $params, $params_string) use ($pdo, $chat_id, $user_id) {
            define( 'SQ_TIMEOUT',     1 );
            define( 'SQ_ENGINE',      SourceQuery::SOURCE );
            $Query = new SourceQuery( );
            $stmt = $pdo->query("SELECT `ip`, `port`, `sid` FROM `sb_servers` WHERE `enabled` = 1");
            $servers_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $regex_values = "";
            foreach ($servers_info as $value) { // Вписываем ID серверов для регулярки
                $regex_values .= $value['sid'] . ',';
            }
            $regex_values = substr($regex_values,0,-1); // Удаляем последний ненужный символ запятой
            if (preg_match("/([$regex_values]) (.+)/", $params_string, $match)) {
                if ($match[2] != 'delete') {
                    try {
                        $pdo->query("INSERT INTO `vkbot_names_servers` (`id_server`, `name`) VALUES ({$match[1]}, '{$match[2]}')");
                        send_message($chat_id, "&#9989; Вы успешно изменили имя сервера на: " . $match[2]);
                    }
                    catch (PDOException $e) {
                        send_message($chat_id, 'Произошла ошибка при добавлении - '. $e->getMessage());
                    }
                } else {
                    $stmt = $pdo->query("DELETE FROM `vkbot_names_servers` WHERE `id_server` = {$match[1]}");
                    send_message($chat_id, "&#9989; Вы успешно изменили сбросили имя сервера");
                }
            } else {
                $description = "/setservername [ID сервера] [Новое название] - Установить название сервера для беседы<br><br>ID Серверов:<br>";
                foreach ($servers_info as $value) {
                    try
                    {
                        $Query->Connect($value['ip'], $value['port'], SQ_TIMEOUT, SQ_ENGINE);
                        $server_info = $Query->GetInfo();
                        $description .= $value['sid'] . ' - ' . $server_info['HostName'] . '<br>';
                    }
                    catch( Exception $e )
                    {
                        send_message($chat_id, 'Боту не удалось получить информацию с сервера: '. $value['name']);
                    }
                    finally
                    {
                        $Query->Disconnect( );
                    }
                }
                send_message($chat_id, $description . "<br><br> Используйте в качевстве названия 'delete' чтобы сбросить его.");
            }
        }
    ],
    "say" => [
        'reg_params' => "/(.+)/",
        'short_description' => "/say - Отправить сообщение по беседам от имени бота",
        'description' => "/say [Текст] - Отправить сообщение по беседам от имени бота",
        "access" => ["Куратор", "Создатель", "Главный Администратор", "Зам. Главного Администратора"],
        "conversations" => ['dev_team'],
        'function' => function($data, $params, $params_string) use ($pdo, $chat_id, $user_id) {
            $stmt = $pdo->query("SELECT `chat_id` FROM `vkbot_conversations` WHERE `name` = 'admin_team'");
            $chats_id = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($chats_id as $value) {
                send_message($value['chat_id'], $params_string);
            }
            send_message($chat_id, '&#9989; Текст успешно разослан по всем беседам. &#9989;');
        }
    ],
    /*"getconv" => [
        'reg_params' => NULL,
        'short_description' => "/getconv - Получить все беседы, используемые ботом",
        'description' => "/getconv - Получить все беседы, используемые ботом",
        "access" => ["Куратор", "Создатель", "Главный Администратор", "Зам. Главного Администратора"],
        "conversations" => ['dev_team'],
        'function' => function($data, $params, $params_string) use ($pdo, $chat_id, $user_id) {
            $stmt = $pdo->query("SELECT `chat_id` FROM `vkbot_conversations` WHERE `name` = 'admin_team'");
            $chats_id = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $vk_params_chat_ids = "";
            $chats_info = "Название : Тип<br><br>";
            foreach ($chats_id as $value) {
                $vk_params_chat_ids .= $value['chat_id'].',';
            }
            $response = api('messages.getChat', ['chat_ids' => $vk_params_chat_ids]);
            foreach ($response[0] as $value) {
                $chats_info .= $value['title'];
                foreach ($chats_id as $id) {
                    if ($id['chat_id'] == $value['id']) {
                        $chats_info .= ' : ' . $id['name'].'<br>';
                    }
                }
            }
            send_message($chat_id, $chats_info);
        }
    ],*/
    "kick" => [
        'reg_params' => "/^\[id(\d+)\|.+\]$/",
        'short_description' => "/kick - Кикнуть человека со всех бесед",
        'description' => "/kick [Упомянуть человека] - Кикнуть человека со всех бесед",
        "access" => ["Куратор", "Создатель", "Главный Администратор", "Зам. Главного Администратора"],
        "conversations" => NULL,
        'function' => function($data, $params) use ($pdo, $chat_id, $user_id) {
            preg_match("/^\[id(\d+)\|.+\]$/", $params[1], $match);
            $stmt = $pdo->query("SELECT `chat_id` FROM `vkbot_conversations`");
            $chats_id = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($chats_id as $value) {
                api("messages.removeChatUser", ['chat_id' => $value['chat_id'], 'user_id' => $match[1]]);
            }
        }
    ],
    "call" => [
        'reg_params' => NULL,
        'short_description' => "/call - Вызвать всех из бесед/беседы",
        'description' => "/call - Вызвать всех из бесед/беседы",
        "access" => ["Куратор", "Создатель", "Главный Администратор", "Зам. Главного Администратора"],
        "conversations" => ['admin_team', 'dev_team', 'free'],
        'function' => function($data, $params) use ($pdo, $chat_id, $user_id) {
            global $official_conv;
            if ($official_conv == "dev_team") {
                $stmt = $pdo->query("SELECT `chat_id` FROM `vkbot_conversations` WHERE `name` = 'admin_team'");
                $chats_id = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($chats_id as $value) {
                    $message = "&#8252; ОБЩИЙ СОЗЫВ &#8252;<br><br>";
                    $response = api("messages.getConversationMembers", ['peer_id' => $value['chat_id'] + 2000000000, 'group_id' => $data->group_id]);
                    foreach ($response['profiles'] as $profile) {
                        $message .= "[id{$profile['id']}|{$profile['first_name']} {$profile['last_name']}] ";
                    }
                    $random_id = rand(0, 999999999999);
                    api('messages.send', array(
                        'chat_id' => $value['chat_id'],
                        'message' => $message,
                        'random_id' => $random_id,
                    ));
                }
                send_message($chat_id, '&#9989; Созыв был отправлен по всем беседам. &#9989;');
            } else {
                $message = "&#8252; ОБЩИЙ СОЗЫВ &#8252;<br><br>";
                $response = api("messages.getConversationMembers", ['peer_id' => $chat_id + 2000000000, 'group_id' => $data->group_id]);
                foreach ($response['profiles'] as $profile) {
                    $message .= "[id{$profile['id']}|{$profile['first_name']} {$profile['last_name']}] ";
                }
                $random_id = rand(0, 999999999999);
                api('messages.send', array(
                    'chat_id' => $chat_id,
                    'message' => $message,
                    'random_id' => $random_id,
                ));
            }
        }
    ],
    "online" => [
        'reg_params' => NULL,
        'short_description' => "/online - Узнать онлайн серверов",
        'description' => "/online - Узнать онлайн серверов",
        "access" => ["Куратор", "Создатель", "Главный Администратор", "Зам. Главного Администратора", "Администратор", "Пользователь"],
        "conversations" => ['admin_team', 'dev_team', 'free'],
        'function' => function($data, $params) use ($pdo, $chat_id, $user_id) {
            define( 'SQ_TIMEOUT',     1 );
            define( 'SQ_ENGINE',      SourceQuery::SOURCE );
            $Query = new SourceQuery( );
            $stmt = $pdo->query("SELECT `sid`, `ip`, `port` FROM `sb_servers` WHERE `enabled` = 1");
            $servers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $online_string = '&#128202; Онлайн серверов проекта: &#128202;<br><br>';
            $all_online = 0;
            $all_max_online = 0;
            $stmt = $pdo->query('SELECT * FROM `vkbot_names_servers`');
            $server_names = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $count_server = 1;
            foreach ($servers as $value) {
                try
                {
                    $Query->Connect($value['ip'], $value['port'], SQ_TIMEOUT, SQ_ENGINE);
                    $server_info = $Query->GetInfo();
                    $value['name'] = $server_info['HostName'];
                    foreach ($server_names as $server_name) {
                        if ($value['sid'] == $server_name['id_server']) $value['name'] = $server_name['name'];
                    }
                    $online_string .= $count_server . '. '. $value['name'] . ' - ' . ($server_info['Players'] - $server_info['Bots']) . '/' . $server_info['MaxPlayers'] . " ({$server_info['Map']})" . ' &#128100;<br><br>';
                    $all_online += ($server_info['Players'] - $server_info['Bots']);
                    $all_max_online += $server_info['MaxPlayers'];
                    $count_server++;
                }
                catch( Exception $e )
                {
                    send_message($chat_id, 'Боту не удалось получить информацию с сервера: '. $value['name']);
                }
                finally
                {
                    $Query->Disconnect( );
                }
            }
            $online_string .= "<br>&#128101; Общий онлайн серверов: ".$all_online . ' / ' . $all_max_online . ' &#128101;';
            send_message($chat_id, $online_string);
        }
    ],
    "admins" => [
        'reg_params' => NULL,
        'short_description' => "/admins - Узнать администрацию онлайн",
        'description' => "/admins - Узнать администрацию онлайн",
        "access" => ["Куратор", "Создатель", "Главный Администратор", "Зам. Главного Администратора", "Администратор", "Пользователь"],
        "conversations" => ['admin_team', 'dev_team', 'free'],
        'function' => function($data, $params, $params_string) use ($pdo, $chat_id, $user_id) {
            define( 'SQ_TIMEOUT',     1 );
            define( 'SQ_ENGINE',      SourceQuery::SOURCE );
            $Query = new SourceQuery( );
            $stmt = $pdo->query("SELECT `ip`, `port`, `sid` FROM `sb_servers` WHERE `enabled` = 1");
            $servers_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt = $pdo->query('SELECT * FROM `vkbot_names_servers`');
            $server_names = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $regex_values = "";
            foreach ($servers_info as $value) { // Вписываем ID серверов для регулярки
                $regex_values .= $value['sid'] . ',';
            }
            $regex_values = substr($regex_values,0,-1); // Удаляем последний ненужный символ запятой
            if (preg_match("/(.+)/", $params_string, $match)) {
                if (preg_match("/([$regex_values])/", $params_string, $match)) {
                    $stmt = $pdo->query("SELECT `user`, `vk`, sb_servers.sid as sid, sb_servers.ip as ip, sb_servers.port as port FROM `sb_admins` INNER JOIN `sb_admins_servers_groups` ON sb_admins_servers_groups.admin_id=sb_admins.aid JOIN `sb_servers` ON sb_admins_servers_groups.server_id = sb_servers.sid WHERE sb_servers.enabled = 1");
                    $all_admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $stmt = $pdo->query("SELECT `sid`, `ip`, `port` FROM `sb_servers` WHERE `enabled` = 1 AND `sid` = {$match[1]}");
                    $servers = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                else
                {
                    $description = "/admins [ID сервера] - Узнать администрацию онлайн<br><br>ID Серверов:<br>";
                    foreach ($servers_info as $value) {
                        try
                        {
                            foreach ($server_names as $server_name) {
                                if ($value['sid'] == $server_name['id_server']) $value['name'] = $server_name['name'];
                            }
                            if ($value['name'] == NULL) {
                                $Query->Connect($value['ip'], $value['port'], SQ_TIMEOUT, SQ_ENGINE);
                                $server_info = $Query->GetInfo();
                                $value['name'] = $server_info['HostName'];
                            }
                            $description .= $value['sid'] . ' - ' . $value['name'] . '<br>';
                        }
                        catch( Exception $e )
                        {
                            send_message($chat_id, 'Боту не удалось получить информацию с сервера: '. $value['name']);
                        }
                        finally
                        {
                            $Query->Disconnect( );
                        }
                    }
                    send_message($chat_id, $description);
                    return;
                }
            }
            else {
                $stmt = $pdo->query("SELECT `user`, `vk`, sb_servers.sid as sid, sb_servers.ip as ip, sb_servers.port as port FROM `sb_admins` INNER JOIN `sb_admins_servers_groups` ON sb_admins_servers_groups.admin_id=sb_admins.aid JOIN `sb_servers` ON sb_admins_servers_groups.server_id = sb_servers.sid WHERE sb_servers.enabled = 1");
                $all_admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt = $pdo->query("SELECT `sid`, `ip`, `port` FROM `sb_servers` WHERE `enabled` = 1");
                $servers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            $online_string = '&#128172; Администрация сервера: &#128172;<br><br>';
            $admins_count = [];
            foreach ($servers as $value) {
                try
                {
                    $Query->Connect($value['ip'], $value['port'], SQ_TIMEOUT, SQ_ENGINE);
                    $server_info = $Query->GetInfo();
                    $server_players = $Query->GetPlayers();
                    $value['name'] = $server_info['HostName'];
                    foreach ($server_names as $server_name) {
                        if ($value['sid'] == $server_name['id_server']) $value['name'] = $server_name['name'];
                    }
                    $online_string .= "Сервер " . $value['name'] . ':<br>';
                    $admins_count[$value['sid']] = 0;
                    foreach ($all_admins as $admin) {
                        foreach ($server_players as $player) {
                            if ($player['Name'] == $admin['user'] && $admin['sid'] == $value['sid']) {
                                $online_string .= '[' . $admin['vk'] . '|' . $admin['user'] . ']<br>';
                                $admins_count[$value['sid']]++;
                            }
                        }
                    }
                    if ($admins_count[$value['sid']] == 0) {
                        $online_string .= "&#8252; Администрации нет на сервере &#8252;<br><br>";
                    } else $online_string .= "Кол-во администрации: {$admins_count[$value['sid']]}<br><br>";
                }
                catch( Exception $e )
                {
                    send_message($chat_id, 'Боту не удалось получить информацию с сервера: ');
                }
                finally
                {
                    $Query->Disconnect( );
                }
            }
            send_message($chat_id, $online_string);
        }
    ],
    "myonline" => [
        'reg_params' => NULL,
        'short_description' => "/myonline - Узнать свой онлайн за сегодня",
        'description' => "/myonline - Узнать свой онлайн за сегодня",
        "access" => ["Куратор", "Создатель", "Главный Администратор", "Зам. Главного Администратора", "Администратор"],
        "conversations" => ['admin_team', 'dev_team', 'free'],
        'function' => function($data, $params) use ($pdo, $chat_id, $user_id) {
            global $user;
            define( 'SQ_TIMEOUT',     1 );
            define( 'SQ_ENGINE',      SourceQuery::SOURCE );
            $Query = new SourceQuery( );
            $online_string = "&#9201; Онлайн администратора " . $user['nickname'] . ' &#9201;<br><br>';
            $stmt = $pdo->query("SELECT admin_online.server_id AS sid, admin_online.today AS today, sb_servers.ip as ip, sb_servers.port as port FROM `admin_online` INNER JOIN `sb_servers` ON sb_servers.sid=admin_online.server_id WHERE admin_online.auth LIKE '%{$user['auth']}'");
            if ($stmt->rowCount() == 0) {
                send_message($chat_id, "Информация о Вашей норме не найдена.");
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
                    send_message($chat_id, 'Боту не удалось получить информацию с сервера: '. $value['name']);
                }
                finally
                {
                    $Query->Disconnect( );
                }
            }
            send_message($chat_id, $online_string);
        }
    ],
    "rules" => [
        'reg_params' => NULL,
        'short_description' => "/rules - Узнать правила беседы",
        'description' => "/rules - Узнать правила беседы",
        "access" => ["Куратор", "Создатель", "Главный Администратор", "Зам. Главного Администратора", "Администратор", "Пользователь"],
        "conversations" => NULL,
        'function' => function($data, $params) use ($pdo, $chat_id, $user_id) {
            $rules = str_replace('{emoji_ban}', '&#9940;', RULES);
            $rules = str_replace('{emoji_warning}', '&#9888;', $rules);
            $rules = str_replace('{emoji_allowed}', '&#9989;', $rules);
            send_message($chat_id, $rules);
        }
    ],
    "help" => [
        'reg_params' => NULL,
        'short_description' => "/help - узнать команды бота",
        'description' => "/help - узнать команды бота",
        "access" => ["Куратор", "Создатель", "Главный Администратор", "Зам. Главного Администратора", "Администратор", "Пользователь"],
        "conversations" => NULL,
        'function' => function($data, $params) use ($pdo, $chat_id, $user_id, $user) {
            global $comands, $user, $official_conv;
            $access_group = false;
            $access_conv = false;
            $help_string = "&#128104;&#8205;&#128187; Доступные Вам команды бота в данной беседе: &#128105;&#8205;&#128187;<br><br>";
            foreach ($comands as $value) {
                foreach ($value['access'] as $group) {
                    if ($user['group'] == $group) {
                        $access_group = true;
                        break;
                    }
                }
                if ($value['conversations'] == NULL) {
                    $access_conv = true;
                }
                else {
                    foreach ($value['conversations'] as $conv) {
                        if ($official_conv == $conv || $conv == NULL) {
                            $access_conv = true;
                            break;
                        }
                    }
                }
                if ($access_group && $access_conv) $help_string .= $value['short_description'].'<br>';
                $access_group = false;
                $access_conv = false;
            }
            send_message($chat_id, $help_string . '<br>' . "Условные обозначения:\n\n&#128213; - Администратор оффлайн.\n&#128215; - Администратор онлайн.\n&#10004; - Норма выполнена.");
        }
    ],

];
