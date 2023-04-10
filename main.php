<?php

/**
 * VK Bot for NOVICHOK project
 * version: 1.00
 * author: Maksim Sadowscky aka Wlokas
 * vk.com/maksimsadovsky
 */

/*ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);*/

require_once 'config.php'; // Подключение конфигурации
ini_set('date.timezone', 'Europe/Moscow'); // Устанавливаем часовой пояс МСК
if (!isset($_REQUEST)) {
    return;
}


//Получаем и декодируем уведомление
$data = json_decode(file_get_contents('php://input'));
if (isset($_GET['test'])) {
    $data = json_decode($_GET['test']);
}

switch ($data->type) {
    case 'wall_post_new':
        if ($data->secret == SECRET_KEY && $data->group_id == MAIN_GROUP_ID) { // Проверка что запрос не сфальфифицирован
            // Подключение к базе данных
            try {
                $pdo = new pdo('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=UTF8', DB_USER, DB_PASSWORD);
            } catch (PDOException $e) {
                send_message(1, "Ошибка подключение к базе данных, обратитесь к разработчику.");
                exit('ok');
            }
            $wall_id = $data->object->id;
            $wall_owner_id = $data->object->owner_id;
            $stmp = $pdo->query("SELECT `chat_id` FROM `vkbot_conversations` WHERE `name` = 'admin_team'");
            $chats_ids = $stmp->fetchAll(PDO::FETCH_ASSOC);
            foreach ($chats_ids as $value) {
                api('messages.send', array(
                    'chat_id' => $value['chat_id'],
                    'message' => "В нашей группе ВКонтакте вышел новый пост:",
                    'attachment' => "wall" . $wall_owner_id . "_" . $wall_id,
                    'disable_mentions' => 1,
                    'random_id' => 0,
                ));
            }
        }
        echo('ok');
        break;
    case 'confirmation':
        if ($data->group_id == MAIN_GROUP_ID) {
            echo MAIN_CONFIRMATION_TOKEN;
        } else echo CONFIRMATION_TOKEN; // Отправляем ключ для подтверждения группы
        break;
    case 'message_new': // Новое сообщение
        if ($data->secret == SECRET_KEY) { // Проверка что запрос не сфальфифицирован

            // Инцилизация переменных
            $user_id = $data->object->from_id;
            $chat_id = $data->object->peer_id - 2000000000;
            $message = $data->object->text;
            $user_info = api('users.get', [
                'user_ids' => $user_id,
                'fields' => 'domain'
            ])[0];
            $names_conversations = [
                'admin_team' => "Беседа Администрации сервера",
                'dev_team' => "Техническая беседа разработчиков",
                'report' => 'Беседа репорта',
                'free' => 'Свободная конференция'
            ];

            // Подключение к базе данных
            try {
                $pdo = new pdo('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=UTF8', DB_USER, DB_PASSWORD);
            } catch (PDOException $e) {
                send_message($chat_id, "Ошибка подключение к базе данных, обратитесь к разработчику.");
                exit();
            }

            require_once 'commands.php'; // Покдлючение команд бота


            // Проверка на наличие беседы в базе данных

            $stmp = $pdo->query("SELECT `name`, `chat_id` FROM `vkbot_conversations` WHERE `chat_id` = {$chat_id}");
            if ($stmp->rowCount() == 0) {
                $official_conv = false;
            } else $official_conv = $stmp->fetchAll(PDO::FETCH_ASSOC)[0]['name'];

            //-----------------------------------------------------

            // Если к беседе подключился новый человек
            if ($data->object->action != NULL && $data->object->action->type == "chat_invite_user") {
                if ($data->object->action->member_id == ($data->group_id * -1)) {
                    if (!$official_conv) {
                        send_message($chat_id, "Привет, &#129302; я чат-бот проекта NOVICHOK &#129302;<br>Эту беседу я не смог распознать &#128560;.
                        Исправьте это при помощи /setconv [Номер классификации],
                        1 - Беседа Администрации сервера,
                        2 - Техническая беседа разработчиков,
                        3 - Беседа репорта
                        4 - Свободная конференция.");
                    } else {
                        send_message($chat_id, "Привет, &#129302; я чат-бот проекта NOVICHOK &#129302;,
                        классификация этой беседы установлена как: {$names_conversations[$official_conv]}.
                        <br>Изменить её можно при помощи /setconv [Номер классификации].");
                    }
                    send_message($chat_id, "&#9888; Выдайте администратора беседы боту &#9888;");
                } else {
                    if ($official_conv) {
                        $invite_user_info = api('users.get', [
                            'user_ids' => $data->object->action->member_id,
                            'fields' => 'domain'
                        ])[0];
                        $stmp = $pdo->query("SELECT `aid`, `user`, `srv_group` FROM `sb_admins` WHERE `vk` = '{$invite_user_info['domain']}'");
                        if ($stmp->rowCount() != 0) {
                            $info = $stmp->fetchAll(PDO::FETCH_ASSOC)[0];
                            send_message($chat_id, "&#127381; {$invite_user_info['first_name']} {$invite_user_info['last_name']} определен как {$info['user']}.&#127381;<br>&#128101; Группа: {$info['srv_group']} &#128101;");
                        } else {
                            send_message($chat_id, "&#10071; {$invite_user_info['first_name']} {$invite_user_info['last_name']} не был определен системой. &#10071;<br>&#128101; Группа: Пользователь &#128101;");
                        }
                    }
                }
            }

            if ($official_conv || preg_match('/\/setconv/', $message)) {

                $stmp = $pdo->query("SELECT `aid` as `id`, `user` as `nickname`, `srv_group` as `group`, `authid` as `auth` FROM `sb_admins` WHERE `vk` = '{$user_info['domain']}'");
                if ($stmp->rowCount() != 0) {
                    $user = $stmp->fetchAll(PDO::FETCH_ASSOC)[0];
                    $user['id_vk'] = $user_id;
                    $user['domain'] = $user_info['domain'];
                    $user['name'] = $user_info['first_name'] . ' ' . $user_info['last_name'];
                    preg_match('/STEAM_[0-1]:[0-1]:(\d+)/', $user['auth'], $match);
                    $user['auth'] = $match[1];
                } else {
                    $user['nickname'] = $user_info['first_name'] . ' ' . $user_info['last_name'];
                    $user['group'] = "Пользователь";
                    $user['name'] = $user_info['first_name'] . ' ' . $user_info['last_name'];
                    $user['domain'] = $user_info['domain'];
                    $user['id_vk'] = $user_id;
                    $user['id'] = -1;
                }


                //================================================== Обработка команд ======================================================

                if (preg_match('/^\/(.+)\s?(.*)/s', trim($message), $match_string)) {
                    $command_info = explode(" ", $match_string[1]);
                    $command = $command_info[0];
                    if (isset($comands[$command])) {
                        foreach ($comands[$command]['access'] as $group) { // Проверка на доступность команды пользователю
                            if ($user['group'] == $group) {
                                $access = true;
                                break;
                            }
                        }
                        if ($comands[$command]['conversations'] != NULL) { // Проверка на доступ команды в конкретно данной беседе
                            foreach ($comands[$command]['conversations'] as $conv) {
                                if ($official_conv == $conv) {
                                    $access_conv = true;
                                }
                            }
                        } else $access_conv = true;
                        if ($access) {
                            if ($access_conv) {
                                for ($i = 1; $i < count($command_info); $i++) {
                                    $params_string .= $command_info[$i] . ' ';
                                }
                                $params_string = substr($params_string,0,-1);
                                if (preg_match($comands[$command]['reg_params'], $params_string) || $comands[$command]['reg_params'] == NULL) {
                                    $comands[$command]['function']($data, $command_info, $params_string);
                                } else send_message($chat_id, $comands[$command]['description']);
                            } else send_message($chat_id, "&#9940; В данной беседе запрещена эта команда. &#9940;");
                        } else send_message($chat_id, "&#9940; У Вас нет доступа к этой команде. &#9940;");
                    } else send_message($chat_id, "&#9888; Команда не найдена, используйте /help. &#9888;");
                }

                //=============================================== Кик юзера при выходе с беседы ============================================

                if ($data->object->action != NULL && $data->object->action->type == "chat_kick_user") {
                    $user_id = $data->object->action->member_id;
                    $stmp = $pdo->query("SELECT `chat_id` FROM `vkbot_conversations`");
                    $chats_id = $stmp->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($chats_id as $value) {
                        api("messages.removeChatUser", ['chat_id' => $value['chat_id'], 'user_id' => $user_id]);
                    }
                }

                //=============================================== Добавление статистики =====================================================
                if ($data->object->attachments != NULL) { // Проверка, есть ли в сообщении вложения
                    switch ($data->object->attachments[0]->type) {
                        case 'audio_message': $stats_param = "voices"; break;
                        case 'photo': $stats_param = "images"; break;
                        case 'video': $stats_param = "videos"; break;
                        default: $stats_param = "other";
                    }

                } elseif ($data->object->text != "") $stats_param = "messages"; // Если нет, то добавляем как обычное сообщение
                if ($stats_param) {
                    $stmt = $pdo->query("UPDATE `vkbot_users_stats` SET {$stats_param} = {$stats_param} + 1 WHERE `vk_id` = {$user_id}"); // Обновляем статистику
                    if ($stmt->rowCount() == 0) {
                        $stmt = $pdo->query("INSERT INTO `vkbot_users_stats`(`{$stats_param}`, `vk_id`) VALUES (1, {$user_id})"); // Если записи с юзером не существует, добавляем
                    }
                }

                //=============================================== Добавление статистики =====================================================

            }


            echo('ok');
        }
        break;
}

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
