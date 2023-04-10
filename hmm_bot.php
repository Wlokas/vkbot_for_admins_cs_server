<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'hmm_bot');
define('DB_PASSWORD', '7s382s33');
define('DB_NAME', 'hmm_bot');
try {
    $pdo = new pdo('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD);
} catch (PDOException $e){
    echo 'Ошибка при подключении к базе данных';
}
if (!isset($_REQUEST)) {
    return;
}

if (isset($_GET['LS']) && isset($_GET['SF']) && isset($_GET['LV']) && isset($_GET['players']) && isset($_GET['bot'])) {
    $stmp = $pdo->query("SELECT * FROM `hmm_vk_bot_finds` ORDER BY `time` DESC LIMIT 1");
    $buffer_finds = $stmp->fetchAll(PDO::FETCH_ASSOC)[0];
    if ($buffer_finds['LS']*2 != $_GET['LS'] && $buffer_finds['SF']*2 && $_GET['SF'] && $buffer_finds['SF']*2 != $_GET['LV']) {
        $time = time();
        $stmp = $pdo->query("UPDATE `hmm_vk_bot_finds` SET `LS`={$_GET['LS']}, `SF`={$_GET['SF']}, `LV`={$_GET['LV']}, `players`='{$_GET['players']}', `time`=$time");
        if (date("G") < 12 && date("G") >= 0) {

        } else $stmp = $pdo->query("INSERT INTO `hmm_vk_bot_finds_history` (`time`, `LS`, `SF`, `LV`) VALUES ($time, {$_GET['LS']}, {$_GET['SF']}, {$_GET['LV']})");
        exit('ok');
    }
}


if (isset($_GET['freport'])) {
    $status = 0;
    if (date("w", time() - 650) == 0) $status = 1; // За неделю
    if (date("j", time() - 650) == date("t", time() - 650)) $status = 2; // За месяц
    switch($status) {
        case 0: $time['start'] = mktime(0, 0, 0, date("n", time() - 650), date("j", time() - 650), date("Y", time() - 650)); $time['end'] = time(); $string_between_date = date("d/m/Y", time() - 650)." (день)"; break;
        case 1: $time['start'] = mktime(0, 0, 0, date("n", time() - 650), date("j", time() - 650) - date("N", time() - 650) + 1); $time['end'] = time(); $string_between_date = date("d/m/Y", $time['start']).' - '.date("d/m/Y", time() - 650)." (неделя)"; break;
        case 2: $time['start'] = mktime(0, 0, 0, date("n", time() - 650), 1); $time['end'] = time(); $string_between_date = date("d/m/Y", $time['start']).' - '.date("d/m/Y", time() - 650)." (месяц)"; break;
    }
    $stmp = $pdo->query("SELECT `LS`, `SF`, `LV`, `time` FROM `hmm_vk_bot_finds_history` WHERE `time` >= {$time['start']} AND `time` <= {$time['end']}");
    $finds = $stmp->fetchAll(PDO::FETCH_ASSOC);
    $result = ['LS' => 0, 'SF' => 0, 'LV' => 0];
    $max_find = ['LS' => ['result' => 0, 'time' => 0], 'SF' => ['result' => 0, 'time' => 0], 'LV' => ['result' => 0, 'time' => 0]];
    foreach ($finds as $value) {
        $result['LS'] += $value['LS'];
        $result['SF'] += $value['SF'];
        $result['LV'] += $value['LV'];
        if ($value['LS'] > $max_find['LS']['result']) { $max_find['LS']['result'] = $value['LS']; $max_find['LS']['time'] = $value['time']; }
        if ($value['SF'] > $max_find['SF']['result']) { $max_find['SF']['result'] = $value['SF']; $max_find['SF']['time'] = $value['time'];}
        if ($value['LV'] > $max_find['LV']['result']) { $max_find['LV']['result'] = $value['LV']; $max_find['LV']['time'] = $value['time'];}
    }
    $result['LS'] /= count($finds);
    $result['SF'] /= count($finds);
    $result['LV'] /= count($finds);
    switch (true) {
        case $result['LS'] > $result['SF'] && $result['LS'] > $result['LV']: $emoji['best']['LS'] = "&#129351;"; break;
        case ($result['LS'] > $result['SF'] && $result['LS'] < $result['LV']) || ($result['LS'] > $result['LV'] && $result['LS'] < $result['SF']): $emoji['best']['LS'] = "&#129352;"; break;
        default: $emoji['best']['LS'] = "&#129353;"; break;
    }

    switch (true) {
        case $result['SF'] > $result['LS'] && $result['SF'] > $result['LV']: $emoji['best']['SF'] = "&#129351;"; break;
        case ($result['SF'] > $result['LS'] && $result['SF'] < $result['LV']) || ($result['SF'] > $result['LV'] && $result['SF'] < $result['LS']): $emoji['best']['SF'] = "&#129352;"; break;
        default: $emoji['best']['SF'] = "&#129353;"; break;
    }

    switch (true) {
        case $result['LV'] > $result['SF'] && $result['LV'] > $result['LS']: $emoji['best']['LV'] = "&#129351;"; break;
        case ($result['LV'] > $result['SF'] && $result['LV'] < $result['LS']) || ($result['LV'] > $result['LS'] && $result['LV'] < $result['SF']): $emoji['best']['LV'] = "&#129352;"; break;
        default: $emoji['best']['LV'] = "&#129353;"; break;
    }

    /////////////////////////////////////////////////////////////////////////////////////////
    switch (true) {
        case $max_find['LS']['result'] > $max_find['SF']['result'] && $max_find['LS']['result'] > $max_find['LV']['result']: $emoji['maxfind']['LS'] = "&#129351;"; break;
        case ($max_find['LS']['result'] >= $max_find['SF']['result'] && $max_find['LS']['result'] <= $max_find['LV']['result']) || ($max_find['LS']['result'] >= $max_find['LV']['result'] && $max_find['LS']['result'] <= $max_find['SF']['result']): $emoji['maxfind']['LS'] = "&#129352;"; break;
        default: $emoji['maxfind']['LS'] = "&#129353;"; break;
    }

    switch (true) {
        case $max_find['SF']['result'] > $max_find['LS']['result'] && $max_find['SF']['result'] > $max_find['LV']['result']: $emoji['maxfind']['SF'] = "&#129351;"; break;
        case ($max_find['SF']['result'] >= $max_find['LS']['result'] && $max_find['SF']['result'] <= $max_find['LV']['result']) || ($max_find['SF']['result'] >= $max_find['LV']['result'] && $max_find['SF']['result'] <= $max_find['LS']['result']): $emoji['maxfind']['SF'] = "&#129352;"; break;
        default: $emoji['maxfind']['SF'] = "&#129353;"; break;
    }

    switch (true) {
        case $max_find['LV']['result'] > $max_find['SF']['result'] && $max_find['LV']['result'] > $max_find['LS']['result']: $emoji['maxfind']['LV'] = "&#129351;"; break;
        case ($max_find['LV']['result'] >= $max_find['SF']['result'] && $max_find['LV']['result'] <= $max_find['LS']['result']) || ($max_find['LV']['result'] >= $max_find['LS']['result'] && $max_find['LV']['result'] <= $max_find['SF']['result']): $emoji['maxfind']['LV'] = "&#129352;"; break;
        default: $emoji['maxfind']['LV'] = "&#129353;"; break;
    }
    send_message("Статистика по финдам мед. центров за $string_between_date:<br><br>".
        "Средний финд у мед. центров за данный промежуток времени:<br>".
        'Floris Med. C - '.round($result['LS'], 2).$emoji['best']['LS'].
        '<br>St. Francis Med. C - '.round($result['SF'], 2).$emoji['best']['SF'].
        '<br>Kindred Med. C - '.round($result['LV'], 2).$emoji['best']['LV'].
        "<br><br>Максимальные финды у мед центров за данный промежуток времени:<br>".
        'Floris Med. C - '.$max_find['LS']['result'].$emoji['maxfind']['LS'].' ('.date("H:i:s [d/m/y]", $max_find['LS']['time']).')'.
        '<br>St. Francis Med. C - '.$max_find['SF']['result'].$emoji['maxfind']['SF'].' ('.date("H:i:s [d/m/y]", $max_find['SF']['time']).')'.
        '<br>Kindred Med. C - '.$max_find['LV']['result'].$emoji['maxfind']['LV'].' ('.date("H:i:s [d/m/y]", $max_find['LV']['time']).')'.
        '<br><br>Статистика составлена на основе '.count($finds).' записей.'.
        '<br>Подронее можно просмотреть http://drp-script.ru/freport.php?s='.$time['start'].'&e='.$time['end'], 2000000001);
    send_message("Статистика по финдам мед. центров за $string_between_date:<br><br>".
        "Средний финд у мед. центров за данный промежуток времени:<br>".
        'Floris Med. C - '.round($result['LS'], 2).$emoji['best']['LS'].
        '<br>St. Francis Med. C - '.round($result['SF'], 2).$emoji['best']['SF'].
        '<br>Kindred Med. C - '.round($result['LV'], 2).$emoji['best']['LV'].
        "<br><br>Максимальные финды у мед центров за данный промежуток времени:<br>".
        'Floris Med. C - '.$max_find['LS']['result'].$emoji['maxfind']['LS'].' ('.date("H:i:s [d/m/y]", $max_find['LS']['time']).')'.
        '<br>St. Francis Med. C - '.$max_find['SF']['result'].$emoji['maxfind']['SF'].' ('.date("H:i:s [d/m/y]", $max_find['SF']['time']).')'.
        '<br>Kindred Med. C - '.$max_find['LV']['result'].$emoji['maxfind']['LV'].' ('.date("H:i:s [d/m/y]", $max_find['LV']['time']).')'.
        '<br><br>Статистика составлена на основе '.count($finds).' записей.'.
        '<br>Подронее можно просмотреть http://drp-script.ru/freport.php?s='.$time['start'].'&e='.$time['end'], 2000000002);
    echo("Статистика по финдам мед. центров за $string_between_date:<br><br>".
        "Средний финд у мед. центров за данный промежуток времени:<br>".
        'Floris Med. C - '.round($result['LS'], 2).$emoji['best']['LS'].
        '<br>St. Francis Med. C - '.round($result['SF'], 2).$emoji['best']['SF'].
        '<br>Kindred Med. C - '.round($result['LV'], 2).$emoji['best']['LV'].
        "<br><br>Максимальные финды у мед центров за данный промежуток времени:<br>".
        'Floris Med. C - '.$max_find['LS']['result'].$emoji['maxfind']['LS'].' ('.date("H:i:s [d/m/y]", $max_find['LS']['time']).')'.
        '<br>St. Francis Med. C - '.$max_find['SF']['result'].$emoji['maxfind']['SF'].' ('.date("H:i:s [d/m/y]", $max_find['SF']['time']).')'.
        '<br>Kindred Med. C - '.$max_find['LV']['result'].$emoji['maxfind']['LV'].' ('.date("H:i:s [d/m/y]", $max_find['LV']['time']).')'.
        '<br><br>Статистика составлена на основе '.count($finds).' записей.'.
        '<br>Подронее можно просмотреть http://drp-script.ru/freport.php?s='.$time['start'].'&e='.$time['end']);
}

if (isset($_POST['LS']) && isset($_POST['SF']) && isset($_POST['LV']) && isset($_POST['players']) && isset($_POST['sKey'])) {
	if ($_POST['sKey'] == "P9L3pUFoPcwtxyV1VWGXTc3EpQGPXQ4I") {
	    $time = time();
	    $stmp = $pdo->query("UPDATE `hmm_vk_bot_finds` SET `LS`={$_POST['LS']}, `SF`={$_POST['SF']}, `LV`={$_POST['LV']}, `players`='{$_POST['players']}', `time`=$time");
	    $stmp = $pdo->query("INSERT INTO `hmm_vk_bot_finds_history` (`time`, `LS`, `SF`, `LV`) VALUES ($time, {$_POST['LS']}, {$_POST['SF']}, {$_POST['LV']})");
	    if (!isset($_POST['bot'])) {
            $emoji_status = ['LS', 'SF', 'LV'];
            switch (true) {
                case $_POST['LS'] < 3:
                    $emoji_status['LS'] = "&#8252;";
                    break;
                case $_POST['LS'] <= 6:
                    $emoji_status['LS'] = "&#9888;";
                    break;
                case $_POST['LS'] > 6:
                    $emoji_status['LS'] = "&#9989;";
                    break;
            }
            switch (true) {
                case $_POST['SF'] < 3:
                    $emoji_status['SF'] = "&#8252;";
                    break;
                case $_POST['SF'] <= 6:
                    $emoji_status['SF'] = "&#9888;";
                    break;
                case $_POST['SF'] > 6:
                    $emoji_status['SF'] = "&#9989;";
                    break;
            }
            switch (true) {
                case $_POST['LV'] < 3:
                    $emoji_status['LV'] = "&#8252;";
                    break;
                case $_POST['LV'] <= 6:
                    $emoji_status['LV'] = "&#9888;";
                    break;
                case $_POST['LV'] > 6:
                    $emoji_status['LV'] = "&#9989;";
                    break;
            }
            $players = json_decode($_POST['players']);
            $players_string = "";
            foreach ($players as $value) {
                $empty_value = explode(" ", $value)[0];
                $stmp = $pdo->query("SELECT `id` FROM `hmm_vk_bot` WHERE `nickname` = '$empty_value'");
                $result = $stmp->fetchAll(PDO::FETCH_ASSOC)[0];
                if ($result != null) {
                    $value = str_replace($empty_value, "[id{$result['id']}|$empty_value]", $value);
                }
                $players_string .= $value . "<br>";
            }
            $players_string = str_replace("{ffa800}[AFK: 00:01]", "", $players_string);
            $players_string = str_replace("{ffa800}", "&#128164; ", $players_string);
            send_message("Финды медицинских центров: &#128101;<br>Floris Med. C - {$_POST['LS']} {$emoji_status['LS']}<br>St. Francis Med. C - {$_POST['SF']} {$emoji_status['SF']}<br>Kindred Med. C - {$_POST['LV']} {$emoji_status['LV']}<br><br>Старший состав онлайн: &#128100;<br>$players_string<br><br>Средний финд всех мед центров: " . floor(($_POST['LS'] + $_POST['SF'] + $_POST['LV']) / 3) . "<br>Последнее обновление информации: " . date("H:i:s d/m/Y (только что)"), 2000000002);
        }
        echo 'ok';
	} else echo 'Error:1';
}


//Строка для подтверждения адреса сервера из настроек Callback API
$confirmation_token = '0a20541d';

//Ключ доступа сообщества
$token = '3749efb0d5675430c7b52c548fc096015c7cbc280b7eba56a15c73ffe7a63069126a86fd3d278210cb41a';

//Получаем и декодируем уведомление
$data = json_decode(file_get_contents('php://input'));

//Проверяем, что находится в поле "type"
switch ($data->type) {
//Если это уведомление для подтверждения адреса...
    case 'confirmation':
//...отправляем строку для подтверждения
        echo $confirmation_token;
        break;

//Если это уведомление о новом сообщении...
    case 'message_new':
//...получаем id его автора
        $user_id = $data->object->from_id;
        $peer_id = $data->object->peer_id;
        $message = $data->object->text;
        if ($peer_id == 2000000002)  send_message($user_id.':'.$message, 2000000003);
            if ($peer_id < 2000000000) {
                $rand_json = json_decode(file_get_contents("randsms.txt"), true);
                if ($rand_json['id'] == $user_id) {
                    if ($rand_json['date'] >= time()) {
                        send_message($message, 2000000002);
                        send_message("Сообщение отправленно в беседу.", $peer_id);
                        exit('ok');
                    } else {
                        send_message("Срок действия в 12 часов истек.", $peer_id);
                    }
                }
            }
//затем с помощью users.get получаем данные об авторе
            if ($data->object->action != null) {
                if ($data->object->action->type == "chat_invite_user") {
                    send_message("Welcome to the club buddy", $peer_id);
                }
                if ($data->object->action->type == "chat_invite_user_by_link") {
                    $user_id = $data->object->action->member_id;
                    $stmp = $pdo->query("SELECT `nickname`, `rang`, `hospital`, `date` FROM hmm_vk_bot_invites WHERE id = {$user_id}");
                    $user = $stmp->fetchAll()[0];
                    if ($user[0] != null) {
                        if ($user['date'] >= time()) {
                            $stmp = $pdo->prepare("INSERT INTO `hmm_vk_bot`(`id`, `nickname`, `rang`, `admin`, `hospital`) VALUES (?,?,?,?,?)");
                            $stmp->execute([$user_id, $user['nickname'], $user['rang'], 0, $user['hospital']]);
                            $stmp = $pdo->prepare("DELETE FROM `hmm_vk_bot_invites` WHERE `id` = ?");
                            $stmp->execute([$user_id]);
                            send_message("Welcome to the club buddy<br>{$user['nickname']} - {$user['rang']} ранг {$user['hospital']}", $peer_id);
                        }
                        else {
                            $request_params = array(
                                'chat_id' => $peer_id - 2000000000,
                                'user_id' => $user_id,
                                'access_token' => '3749efb0d5675430c7b52c548fc096015c7cbc280b7eba56a15c73ffe7a63069126a86fd3d278210cb41a',
                                'v' => '5.101'
                            );
                            $get_params = http_build_query($request_params);
                            file_get_contents('https://api.vk.com/method/messages.removeChatUser?' . $get_params);
                            send_message("Ранее приглашенный пользователь {$user['nickname']} попытался войти в беседу спустя 3 дня.<br>Обновить его приглашение можно командой /invite", $peer_id);
                        }
                    }
                    else {
                        $request_params = array(
                            'chat_id' => $peer_id - 2000000000,
                            'user_id' => $user_id,
                            'access_token' => '3749efb0d5675430c7b52c548fc096015c7cbc280b7eba56a15c73ffe7a63069126a86fd3d278210cb41a',
                            'v' => '5.101'
                        );
                        $get_params = http_build_query($request_params);
                        file_get_contents('https://api.vk.com/method/messages.removeChatUser?' . $get_params);
                        send_message("Неизвестный пользователь попытался подключиться к беседе по ссылке.", $peer_id);
                    }
                }
                if ($data->object->action->type == "chat_kick_user") {
                    $user_id = $data->object->action->member_id;
                    $stmp = $pdo->query("SELECT `nickname`, `rang`, `admin`, `hospital`, `messages`, `lvl_exp`, `lvl` FROM hmm_vk_bot WHERE id = {$user_id}");
                    $user = $stmp->fetchAll()[0];
                    $arrContextOptions=array(
                    "ssl"=>array(
                        "verify_peer"=>false,
                        "verify_peer_name"=>false,
                         ),
                    ); 
                    if ($user[0] != null) {
                        $stmp = $pdo->prepare("DELETE FROM `hmm_vk_bot` WHERE `id` = ?");
                        $stmp->execute([$user_id]);
                    }
                    else {
                        $user_info = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$user_id}&access_token={$token}&v=5.101", false, stream_context_create($arrContextOptions)));
                        $user['nickname'] = $user_info->response[0]->first_name;
                    }
                    $request_params = array(
                        'chat_id' => $peer_id - 2000000000,
                        'user_id' => $user_id,
                        'access_token' => '3749efb0d5675430c7b52c548fc096015c7cbc280b7eba56a15c73ffe7a63069126a86fd3d278210cb41a',
                        'v' => '5.101'
                    );
                    $get_params = http_build_query($request_params);
                    file_get_contents('https://api.vk.com/method/messages.removeChatUser?' . $get_params, false, stream_context_create($arrContextOptions));
                    send_message("Игрок [id$user_id|{$user['nickname']}] удален из беседы.", $peer_id);
                }
            }
            if (preg_match("/^\/(call|freport|find|who|von|poll|rep|randsms|topmed|random|kto|leaders|8ball|help|info|delete|setplayer|setadmin|invite)/", $message, $command)) {
                $stmp = $pdo->query("SELECT `nickname`, `rang`, `admin`, `hospital`, `messages`, `lvl_exp`, `lvl`, `polls`, `poll_voting` FROM hmm_vk_bot WHERE id = {$user_id}");
                $user = $stmp->fetchAll()[0];

				// Блок выдачи админки создателю -----------------

                if ($user_id == 160959773 && $user[0] == null)
                {
                	$user[0] = true;
                	$user['nickname'] = "admin";
                	$user['admin'] = 2;
                }

                // -----------------------------------------------

                if ($user[0] != null) {
                    if ($user['rang'] >= 10 && $user['admin'] == 0) $user['admin'] = 1; // выдача админки лидерам
                    if ($command[1] == "call") {
                        if ($user['rang'] == 11) {
                            $stmp = $pdo->query("SELECT `id`, `nickname` FROM `hmm_vk_bot` WHERE `hospital` != 'ADM'");
                            $call = $stmp->fetchAll(PDO::FETCH_ASSOC);
                            $call_string = "";
                            foreach ($call as $value) {
                                $call_string .= "[id{$value['id']}|{$value['nickname']}]<br>";
                            }
                            $arrContextOptions=array(
                                "ssl"=>array(
                                    "verify_peer"=>false,
                                    "verify_peer_name"=>false,
                                ),
                            );
                            $request_params = array(
                                'message' => '&#8252; <<<<ВНИМАНИЕ!>>>> &#8252;<br><br>'.$call_string.'<br>&#8252; <<<<МИНИСТР СОЗЫВАЕТ ВСЕХ>>>> &#8252;',
                                'random_id' => 0,
                                'peer_id' => $peer_id,
                                'access_token' => '3749efb0d5675430c7b52c548fc096015c7cbc280b7eba56a15c73ffe7a63069126a86fd3d278210cb41a',
                                'v' => '5.101'
                            );

                            $get_params = http_build_query($request_params);

                            file_get_contents('https://api.vk.com/method/messages.send?'. $get_params, false, stream_context_create($arrContextOptions));

                        } else send_message("У вас нет доступа к этой команде.", $peer_id);
                    }
                    if ($command[1] == "freport") {
                        if ($user['admin'] >= 1) {
                            if (preg_match("/^\/freport (день|неделя|месяц)/", strtolower($message), $command_params)) {
                                switch($command_params[1]) {
                                    case 'день': $time['start'] = mktime(0, 0, 0, date("n"), date("j"), date("Y")); $time['end'] = time(); $string_between_date = date("d/m/Y")." (день)"; break;
                                    case 'неделя': $time['start'] = mktime(0, 0, 0, date("n"), date("j") - date("N") + 1); $time['end'] = time(); $string_between_date = date("d/m/Y", $time['start']).' - '.date("d/m/Y")." (неделя)"; break;
                                    case 'месяц': $time['start'] = mktime(0, 0, 0, date("n"), 1); $time['end'] = time(); $string_between_date = date("d/m/Y", $time['start']).' - '.date("d/m/Y")." (месяц)"; break;
                                }
                                $stmp = $pdo->query("SELECT `LS`, `SF`, `LV`, `time` FROM `hmm_vk_bot_finds_history` WHERE `time` >= {$time['start']} AND `time` <= {$time['end']}");
                                $finds = $stmp->fetchAll(PDO::FETCH_ASSOC);
                                $result = ['LS' => 0, 'SF' => 0, 'LV' => 0];
                                $max_find = ['LS' => ['result' => 0, 'time' => 0], 'SF' => ['result' => 0, 'time' => 0], 'LV' => ['result' => 0, 'time' => 0]];
                                foreach ($finds as $value) {
                                    $result['LS'] += $value['LS'];
                                    $result['SF'] += $value['SF'];
                                    $result['LV'] += $value['LV'];
                                    if ($value['LS'] > $max_find['LS']['result']) { $max_find['LS']['result'] = $value['LS']; $max_find['LS']['time'] = $value['time']; }
                                    if ($value['SF'] > $max_find['SF']['result']) { $max_find['SF']['result'] = $value['SF']; $max_find['SF']['time'] = $value['time'];}
                                    if ($value['LV'] > $max_find['LV']['result']) { $max_find['LV']['result'] = $value['LV']; $max_find['LV']['time'] = $value['time'];}
                                }
                                $result['LS'] /= count($finds);
                                $result['SF'] /= count($finds);
                                $result['LV'] /= count($finds);
                                switch (true) {
                                    case $result['LS'] > $result['SF'] && $result['LS'] > $result['LV']: $emoji['best']['LS'] = "&#129351;"; break;
                                    case ($result['LS'] > $result['SF'] && $result['LS'] < $result['LV']) || ($result['LS'] > $result['LV'] && $result['LS'] < $result['SF']): $emoji['best']['LS'] = "&#129352;"; break;
                                    default: $emoji['best']['LS'] = "&#129353;"; break;
                                }

                                switch (true) {
                                    case $result['SF'] > $result['LS'] && $result['SF'] > $result['LV']: $emoji['best']['SF'] = "&#129351;"; break;
                                    case ($result['SF'] > $result['LS'] && $result['SF'] < $result['LV']) || ($result['SF'] > $result['LV'] && $result['SF'] < $result['LS']): $emoji['best']['SF'] = "&#129352;"; break;
                                    default: $emoji['best']['SF'] = "&#129353;"; break;
                                }

                                switch (true) {
                                    case $result['LV'] > $result['SF'] && $result['LV'] > $result['LS']: $emoji['best']['LV'] = "&#129351;"; break;
                                    case ($result['LV'] > $result['SF'] && $result['LV'] < $result['LS']) || ($result['LV'] > $result['LS'] && $result['LV'] < $result['SF']): $emoji['best']['LV'] = "&#129352;"; break;
                                    default: $emoji['best']['LV'] = "&#129353;"; break;
                                }
                                
                                /////////////////////////////////////////////////////////////////////////////////////////
                                switch (true) {
                                    case $max_find['LS']['result'] > $max_find['SF']['result'] && $max_find['LS']['result'] > $max_find['LV']['result']: $emoji['maxfind']['LS'] = "&#129351;"; break;
                                    case ($max_find['LS']['result'] >= $max_find['SF']['result'] && $max_find['LS']['result'] <= $max_find['LV']['result']) || ($max_find['LS']['result'] >= $max_find['LV']['result'] && $max_find['LS']['result'] <= $max_find['SF']['result']): $emoji['maxfind']['LS'] = "&#129352;"; break;
                                    default: $emoji['maxfind']['LS'] = "&#129353;"; break;
                                }

                                switch (true) {
                                    case $max_find['SF']['result'] > $max_find['LS']['result'] && $max_find['SF']['result'] > $max_find['LV']['result']: $emoji['maxfind']['SF'] = "&#129351;"; break;
                                    case ($max_find['SF']['result'] >= $max_find['LS']['result'] && $max_find['SF']['result'] <= $max_find['LV']['result']) || ($max_find['SF']['result'] >= $max_find['LV']['result'] && $max_find['SF']['result'] <= $max_find['LS']['result']): $emoji['maxfind']['SF'] = "&#129352;"; break;
                                    default: $emoji['maxfind']['SF'] = "&#129353;"; break;
                                }

                                switch (true) {
                                    case $max_find['LV']['result'] > $max_find['SF']['result'] && $max_find['LV']['result'] > $max_find['LS']['result']: $emoji['maxfind']['LV'] = "&#129351;"; break;
                                    case ($max_find['LV']['result'] >= $max_find['SF']['result'] && $max_find['LV']['result'] <= $max_find['LS']['result']) || ($max_find['LV']['result'] >= $max_find['LS']['result'] && $max_find['LV']['result'] <= $max_find['SF']['result']): $emoji['maxfind']['LV'] = "&#129352;"; break;
                                    default: $emoji['maxfind']['LV'] = "&#129353;"; break;
                                }
                                
                                send_message("Статистика по финдам мед. центров за $string_between_date:<br><br>".
                                    "Средний финд у мед. центров за данный промежуток времени:<br>".
                                    'Floris Med. C - '.round($result['LS'], 2).$emoji['best']['LS'].
                                    '<br>St. Francis Med. C - '.round($result['SF'], 2).$emoji['best']['SF'].
                                    '<br>Kindred Med. C - '.round($result['LV'], 2).$emoji['best']['LV'].
                                    "<br><br>Максимальные финды у мед центров за данный промежуток времени:<br>".
                                    'Floris Med. C - '.$max_find['LS']['result'].$emoji['maxfind']['LS'].' ('.date("H:i:s [d/m/y]", $max_find['LS']['time']).')'.
                                    '<br>St. Francis Med. C - '.$max_find['SF']['result'].$emoji['maxfind']['SF'].' ('.date("H:i:s [d/m/y]", $max_find['SF']['time']).')'.
                                    '<br>Kindred Med. C - '.$max_find['LV']['result'].$emoji['maxfind']['LV'].' ('.date("H:i:s [d/m/y]", $max_find['LV']['time']).')'.
                                    '<br><br>Статистика составлена на основе '.count($finds).' записей.'.
                                    '<br>Подронее можно просмотреть http://drp-script.ru/freport.php?s='.$time['start'].'&e='.$time['end'], $peer_id);
                            } else send_message("/freport [день/неделя/месяц]", $peer_id);
                        } else send_message("У вас нет доступа к этой команде.", $peer_id);
                    }
                    if ($command[1] == "find") {
                        $stmp = $pdo->query("SELECT * FROM `hmm_vk_bot_finds`");
                        $finds = $stmp->fetchAll(PDO::FETCH_ASSOC)[0];
                        $emoji_status = ['LS', 'SF', 'LV'];
                        switch (true) {
                            case $finds['LS'] < 3:
                                $emoji_status['LS'] = "&#8252;";
                                break;
                            case $finds['LS'] <= 6:
                                $emoji_status['LS'] = "&#9888;";
                                break;
                            case $finds['LS'] > 6:
                                $emoji_status['LS'] = "&#9989;";
                                break;
                        }
                        switch (true) {
                            case $finds['SF'] < 3:
                                $emoji_status['SF'] = "&#8252;";
                                break;
                            case $finds['SF'] <= 6:
                                $emoji_status['SF'] = "&#9888;";
                                break;
                            case $finds['SF'] > 6:
                                $emoji_status['SF'] = "&#9989;";
                                break;
                        }
                        switch (true) {
                            case $finds['LV'] < 3:
                                $emoji_status['LV'] = "&#8252;";
                                break;
                            case $finds['LV'] <= 6:
                                $emoji_status['LV'] = "&#9888;";
                                break;
                            case $finds['LV'] > 6:
                                $emoji_status['LV'] = "&#9989;";
                                break;
                        }
                        $players = json_decode($finds['players']);
                        $players_string = "";
                        foreach ($players as $value) {
                            $empty_value = explode(" ", $value)[0];
                            $stmp = $pdo->query("SELECT `id` FROM `hmm_vk_bot` WHERE `nickname` = '$empty_value'");
                            $result = $stmp->fetchAll(PDO::FETCH_ASSOC)[0];
                            if ($result != null) {
                                $value = str_replace($empty_value, "[id{$result['id']}|$empty_value]", $value);
                            }
                            $players_string .= $value . "<br>";
                        }
                        $players_string = str_replace("[AFK: 00:01]", "", $players_string);
                        $players_string = str_replace("[AFK:", "&#128164; [AFK:", $players_string);
                        $players_string = str_replace("{ffa800}", "", $players_string);
                        if (($finds['time'] + 900) < time()) $warning = "<br><br>&#9201; Данные были обновлены более 15-ти минут назад! &#9201;";
                        send_message("Финды медицинских центров: &#128101;<br>Floris Med. C - {$finds['LS']} {$emoji_status['LS']}<br>St. Francis Med. C - {$finds['SF']} {$emoji_status['SF']}<br>Kindred Med. C - {$finds['LV']} {$emoji_status['LV']}<br><br>Старший состав онлайн: &#128100;<br>$players_string<br><br>Средний финд всех мед центров: " . floor(($finds['LS'] + $finds['SF'] + $finds['LV']) / 3) . "<br>Последнее обновление информации: " . date("H:i:s d/m/Y", $finds['time']).$warning, $peer_id);
                    }
                    if ($command[1] == "who") {
                        if (preg_match("/^\/who (.+)/", $message, $command_params)) {
                            $stmp = $pdo->query("SELECT `id`, `nickname` FROM `hmm_vk_bot` WHERE `nickname` != 'Royan_Millans' ORDER BY RAND() LIMIT 1");
                            $result = $stmp->fetchAll(PDO::FETCH_ASSOC)[0];
                            send_message("Это [id{$result['id']}|{$result['nickname']}].", $peer_id);
                        } else send_message("/who [Описание], и бот выдаст кто это.", $peer_id);
                    }
                    if ($command[1] == "von") {
                        if ($user['polls'] != 999) {
                            if ($user['poll_voting'] != 1) {
                                if (preg_match("/^\/von \[id(\d+)\|.+\]/", $message, $command_params)) {
                                    if ($command_params[1] != $user_id) {
                                        $stmp = $pdo->query("UPDATE `hmm_vk_bot` SET `polls` = `polls` + 1 WHERE `id` = {$command_params[1]}");
                                        $stmp = $pdo->query("UPDATE `hmm_vk_bot` SET `poll_voting` = 1 WHERE `id` = {$user_id}");
                                        send_message("Ваш голос был учтен.", $peer_id);
                                    } elseif ($command_params[1] == 229347236) send_message("Нельзя голосовать за самого себя. ДА-ДА БУЛАТ, РАДИ ТЕБЯ ЭТУ ПРОВЕРКУ СДЕЛАЛ.", $peer_id);
                                    else  send_message("Нельзя голосовать за самого себя.", $peer_id);
                                } else {
                                    send_message("/von [Упомянуть пользователя]", $peer_id);
                                }
                            }
                            else {
                                send_message("Вы уже проголосовали.", $peer_id);
                            }
                        }
                        else {
                            send_message("Голосование не было запущено, попросите Админов если уверены что хотите начать.", $peer_id);
                        }
                    }
                    if ($command[1] == "poll") {
                        if ($user['admin'] >= 2) {
                            if ($user['polls'] == 999) {
                                $stmp = $pdo->query("UPDATE `hmm_vk_bot` SET `polls` = 0, `poll_voting` = 0");
                                send_message("Внимание, было открыто голосование на выбывание бота.<br>Если Вы подозреваете что один человек из беседы бот,<br>введите команду /von [Упомянуть пользователя].", $peer_id);
                            } else {
                                $stmp = $pdo->query("SELECT `nickname`, `bot_result` FROM `hmm_vk_bot` ORDER BY `polls` DESC LIMIT 1");
                                $result = $stmp->fetchAll(PDO::FETCH_ASSOC)[0];
                                if ($result['bot_result'] == 1) {
                                    send_message("Бот был угадан!<br>Им оказался {$result['nickname']}", $peer_id);
                                    $stmp = $pdo->query("UPDATE `hmm_vk_bot` SET `bot_result` = 0");
                                    $stmp = $pdo->query("SELECT `id`, `nickname` FROM `hmm_vk_bot` WHERE `hospital` != 'ADM' ORDER BY RAND() LIMIT 1");
                                    $rand_info = $stmp->fetchAll(PDO::FETCH_ASSOC)[0];
                                    $stmp = $pdo->prepare("UPDATE `hmm_vk_bot` SET `bot_result` = 1 WHERE `id` = ?");
                                    $stmp->execute([$rand_info['id']]);
                                    $fp = fopen('randsms.txt', 'w');
                                    $json_info = ['id' => $rand_info['id'], 'date' => time()+(60*60*12)];
                                    fwrite($fp, json_encode($json_info));
                                    fclose($fp);
                                    send_message("Новому случайному человеку из этой беседы был выдан доступ к боту.<br><br>Чтобы писать от имени бота, нужно написать ему в личные сообшения (группы).", $peer_id);
                                    $request_params = array(
                                        'message' => "Доступ был выдан [id{$rand_info['id']}|{$rand_info['nickname']}] на 12 часов",
                                        'random_id' => 0,
                                        'peer_id' => $user_id,
                                        'access_token' => '3749efb0d5675430c7b52c548fc096015c7cbc280b7eba56a15c73ffe7a63069126a86fd3d278210cb41a',
                                        'v' => '5.101'
                                    );

                                    $get_params = http_build_query($request_params);
                                    file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                                } else {
                                    send_message("По результатам голосования был выбран - {$result['nickname']}.<br>Данный человек оказался НЕ ботом<br>Доступ к боту продлен еще на 12 часов, следующее голосование можно будет начать через 12 часов.", $peer_id);
                                    $rand_json = json_decode(file_get_contents("randsms.txt"), true);
                                    $rand_json['date'] += (60*60)*12;
                                    file_put_contents('randsms.txt', json_encode($rand_json));
                                }
                                $stmp = $pdo->query("UPDATE `hmm_vk_bot` SET `polls` = 999, `poll_voting` = 0");
                            }
                        }
                        else {
                            send_message("У вас нет доступа к этой команде", $peer_id);
                        }
                    }
                    if ($command[1] == 'rep') {
                        $phrases = [
                          "Следите за новостями проекта.",
                            "Приятной игры!",
                            "Слежу",
                            "РП процесс",
                            "Узнайте РП путем",
                            "Не увидел нарушений со стороны игрока",
                            "Нет",
                            "Да",
                            "Конечно",
                            "Не оффтопьте",
                            "Адекватнее",
                            "Не понял сути вашего вопроса",
                            "Осуждаю",
                            "Зачем?",
                            "Передам старшей администрации",
                            "Ожидайте",
                            "Адекватнее",
                        ];
                        send_message($phrases[rand(0, count($phrases))], $peer_id);
                    }
                    if ($command[1] == "randsms") {
                        if ($user['admin'] >= 2) {
                            $stmp = $pdo->query("UPDATE `hmm_vk_bot` SET `bot_result` = 0");
                            $stmp = $pdo->query("SELECT `id`, `nickname` FROM `hmm_vk_bot` WHERE `hospital` != 'ADM' ORDER BY RAND() LIMIT 1");
                            $rand_info = $stmp->fetchAll(PDO::FETCH_ASSOC)[0];
                            $stmp = $pdo->prepare("UPDATE `hmm_vk_bot` SET `bot_result` = 1 WHERE `id` = ?");
                            $stmp->execute([$rand_info['id']]);
                            $fp = fopen('randsms.txt', 'w');
                            $json_info = ['id' => $rand_info['id'], 'date' => time()+(60*60*12)];
                            fwrite($fp, json_encode($json_info));
                            fclose($fp);
                            $arrContextOptions=array(
                                    "ssl"=>array(
                                        "verify_peer"=>false,
                                        "verify_peer_name"=>false,
                                         ),
                                    ); 
                            send_message("Вы успешно выдали доступ к отправлению сообщений от имени бота случайному человеку из этой беседы на 12 часов.<br><br>Чтобы писать от имени бота, нужно написать ему в личные сообшения (группы).", $peer_id);
                                $request_params = array(
                                    'message' => "Доступ был выдан [id{$rand_info['id']}|{$rand_info['nickname']}] на 12 часов",
                                    'random_id' => 0,
                                    'peer_id' => $user_id,
                                    'access_token' => '3749efb0d5675430c7b52c548fc096015c7cbc280b7eba56a15c73ffe7a63069126a86fd3d278210cb41a',
                                    'v' => '5.101'
                                );

                                $get_params = http_build_query($request_params);
                                file_get_contents('https://api.vk.com/method/messages.send?'. $get_params, false, stream_context_create($arrContextOptions));
                        }
                        else {
                            send_message("У вас нет доступа к этой команде", $peer_id);
                        }
                    }
                    if ($command[1] == "invite") {
                        if ($user['admin'] >= 1 || $user['rang'] >= 10) {
                            if (preg_match("/^\/invite (https?:\/\/)?(www\.)?vk\.com\/(.+) (.+) (8|9|10|11) (LS|SF|LV|MZ|ADM)$/", $message, $command_params)) {
                                $invite_user = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$command_params[3]}&access_token={$token}&fields=can_write_private_message&v=5.101"));
                                if (!isset($invite_user->error)) {
                                    if ($invite_user->response[0]->can_write_private_message == 1) {
                                        $invite_link = "https://vk.me/join/AJQ1d2/h_BO2U9q0F1ytkpxa";
                                        $stmp = $pdo->query("SELECT `date` FROM hmm_vk_bot_invites WHERE id = {$invite_user->response[0]->id}");
                                        $invite_date_user = $stmp->fetchAll()[0];
                                        if ($invite_date_user['date'] == null) {
                                            $stmp = $pdo->prepare("INSERT INTO `hmm_vk_bot_invites` (`id`, `nickname`, `rang`, `hospital`, `date`) VALUES (?, ?, ?, ?, ?)");
                                            $stmp->execute([$invite_user->response[0]->id, $command_params[4], $command_params[5], $command_params[6], time() + (60*60*24)*3]);
                                            send_message("Вы отправили приглашение данному игроку. Он .", $peer_id);
                                        }
                                        elseif ($invite_date_user['date'] <= time()) {
                                            $stmp = $pdo->prepare("UPDATE `hmm_vk_bot_invites` SET `date` = ? WHERE `id` = ?");
                                            $stmp->execute([time() + (60*60*24)*3, $invite_user->response[0]->id]);
                                            send_message("Вы обновили приглашение данному игроку на 3 суток. Ожидайте пока он его примет.", $peer_id);
                                        }
                                        else {
                                            send_message("Данному пользователю уже отправленно приглашение.", $peer_id);
                                        }
                                    }
                                    else {
                                         send_message("У данного пользователя закрыты личные сообщения. Отправляте в ручную.", $peer_id);
                                    }
                                }
                                elseif ($invite_user->error->error_code == 113) {
                                    send_message("Данный пользователь не найден. Проверьте ссылку на страницу.", $peer_id);
                                }
                                else {
                                    send_message("Неизвестная ошибка.", $peer_id);
                                }
                            }
                            else {
                                send_message("/invite [Ссылка на страницу ВК] [Ник] [Ранг] [Мед Центр]", $peer_id);
                            }
                        }
                        else {
                            send_message("У вас нет достпа к этой команде.", $peer_id);
                        }
                    }
                    if ($command[1] == 'topmed') {
                        switch (rand(1, 3)) {
                            case 1:
                                send_message("Лучший мед центр это: LS", $peer_id);
                                break;
                            case 2:
                                send_message("Лучший мед центр это: SF", $peer_id);
                                break;
                            case 3:
                                send_message("Лучший мед центр это: LV", $peer_id);
                                break;
                        }
                    }
                    if ($command[1] == 'random') {
                        if (preg_match("/^\/random (\d+) (\d+)/", $message, $command_params)) {
                            send_message("Ваше волшебное число: " . rand($command_params[1], $command_params[2]), $peer_id);
                        } else {
                            send_message("/random [мин] [макс]", $peer_id);
                        }
                    }
                    if ($command[1] == 'kto') {
                        if (preg_match("/^\/kto \[id(\d+)\|.+\]/", $message, $command_params)) {
                            if ($command_params[1] != $user_id) {
                                $stmp = $pdo->query("SELECT `nickname` FROM hmm_vk_bot WHERE id = {$command_params[1]}");
                                $kto_nickname = $stmp->fetchAll()[0]['nickname'];
                                $phrases = ["сегодня не заходил в игру", "будет снят", "просто бомж", "печенька", "забанненый, причина: Продажа вирт", "забанненый, причина: слив лидера", "забанненый, причина: читы", "должен Рояну Миллансу ".rand(1000, 1000000)."$", "скоро будет лидером", "скоро будет админом", "скоро что-нибудь напиздит", "осуждает бота", "лучший МЗшник этой недели", "ездит на затонированном Кловере черного цвета двацатаго века", "гей", "трансвистит", "друг твоих подруг", "чикса под прекрытием", "получал премию Лучший Отрезатель Членов Года", "нужно идти делать уроки", "завтра завалит контрошу (Да-да, ОНА БУДЕТ)", "упакуют копы на митинге", "взломает твой акк", "ФСБшник под прекрытием", "афигел совсем", "сидит с мобильного интернета", "не хочеть жить", "хочет чтобы ему зафлудили личку", "скоро ПСЖ", "хацкер", "имеет детей", "прямо сейчас смотрит порно", "офигивает от того, что бот следит за ним", "пошел нахер", "сегодня снимут выговор", "сегодня получит выговор", "сегодня сольют"];
                                send_message("[id{$command_params[1]}|$kto_nickname] ".$phrases[rand(0, count($phrases))].".", $peer_id);
                            } else {
                                send_message("Сам свою судьбу не узнаешь!", $peer_id);
                            }
                        } else {
                            send_message("/kto [Упомянуть пользователя]", $peer_id);
                        }
                    }
                    if ($command[1] == 'help') {
                        send_message("/help - посмотреть команды<br>/8ball - Магический Шар<br>/leaders - посмотреть лидеров сообщений<br>/kto [Упомяните пользователя] - узнать кто этот человек.<br>/random [Мин число] [Макс число] - рандом число.<br>/topmed - лучший мед центр<br>/rep - спросить что-нибудь в репорт<br>/von [Упомянуть пользователя] - проголосовать за возможного бота<br>/who [Описание] - узнать кто этот человек<br>/find - узнать финд мед центров", $peer_id);
                        if ($user['admin'] >= 1) {
                            send_message("Админ команды (1):<br>/info - вывести блок с сотрудниками<br>/delete [NickName] - удалить игрока из системы<br>/setplayer [Упомяните пользователя] [Nickname] [Ранг (8-11)] [Место работы (LS/SF/LV/MZ/ADM)] - добавить игрока в систему<br>/freport [день/неделя/месяц] - отчетность о финдах<br>/call - Созвать всех (только для министра)", $peer_id);
                        }
                        if ($user['admin'] >= 2) {
                            send_message("Админ команды (2):<br>/randsms - выдать рандомному игроку из беседы доступ к боту<br>/poll - запустить/закончить голосование на выбывание бота<br> /setadmin [Упомянуть] [Лвл (0-2)] - выдать админку бота", $peer_id);
                        }
                    }
                    /*if ($command[1] == 'leave') {
                        $stmp = $pdo->prepare("DELETE FROM `hmm_vk_bot` WHERE `id` = ? and `id_conversation` = ?");
                        $stmp->execute([$user_id, $peer_id]);
                        $request_params = array(
                            'chat_id' => $peer_id - 2000000000,
                            'user_id' => $user_id,
                            'access_token' => '3749efb0d5675430c7b52c548fc096015c7cbc280b7eba56a15c73ffe7a63069126a86fd3d278210cb41a',
                            'v' => '5.101'
                        );
                        $get_params = http_build_query($request_params);
                        file_get_contents('https://api.vk.com/method/messages.removeChatUser?' . $get_params);
                        send_message("Игрок покинул беседу.", $peer_id);
                    }*/
                    if ($command[1] == "leaders") {
                        $stmp = $pdo->query("SELECT `nickname`, `lvl`, `messages` FROM hmm_vk_bot ORDER BY `lvl` DESC");
                        $leaders = $stmp->fetchAll();
                        $leaders_message = "";
                        foreach ($leaders as $value) {
                            $leaders_message .= $value['nickname'] . " ({$value['lvl']} уровень) {$value['messages']} сообщений.<br>";
                        }
                        send_message($leaders_message, $peer_id);
                    }
                    if ($command[1] == "8ball") {
                        $ball8 = rand(1, 20);
                        switch ($ball8) {
                            case 1:
                                send_message("Бесспорно", $peer_id);
                                break;
                            case 2:
                                send_message("Предрешено", $peer_id);
                                break;
                            case 3:
                                send_message("Никаких сомнений", $peer_id);
                                break;
                            case 4:
                                send_message("Определённо да", $peer_id);
                                break;
                            case 5:
                                send_message("Можешь быть уверен в этом", $peer_id);
                                break;
                            case 6:
                                send_message("Мне кажется — «да»", $peer_id);
                                break;
                            case 7:
                                send_message("Вероятнее всего", $peer_id);
                                break;
                            case 8:
                                send_message("Хорошие перспективы", $peer_id);
                                break;
                            case 9:
                                send_message("Знаки говорят — «да»", $peer_id);
                                break;
                            case 10:
                                send_message("Да", $peer_id);
                                break;
                            case 11:
                                send_message("Сейчас нельзя предсказать", $peer_id);
                                break;
                            case 12:
                                send_message("Лучше не рассказывать", $peer_id);
                                break;
                            case 13:
                                send_message("Мне всё равно, я просто хочу министра слить..<br>Кхем..", $peer_id);
                                break;
                            case 14:
                                send_message("А тебе то чо?", $peer_id);
                                break;
                            case 15:
                                send_message("Всё равно от бана не уйдешь @Royan Millans", $peer_id);
                                break;
                            case 16:
                                send_message("Даже не думай", $peer_id);
                                break;
                            case 17:
                                send_message("Мой ответ — «нет»", $peer_id);
                                break;
                            case 18:
                                send_message("По моим данным — «нет»", $peer_id);
                                break;
                            case 19:
                                send_message("Перспективы не очень хорошие", $peer_id);
                                break;
                            case 20:
                                send_message("Весьма сомнительно", $peer_id);
                                break;
                        }
                    }
                    /*if ($command[1] == "memes") {
                        $stmp = $pdo->query("SELECT `media_id`, `owner_id`, `access_key` FROM `hmm_vk_bot_mems`");
                        $memes = $stmp->fetchAll(PDO::FETCH_ASSOC);
                        $attachments_memes = "";
                        foreach ($memes as $value) {
                            $attachments_memes .= "photo".$value['owner_id'].'_'.$value['media_id'].'_'.$value['access_key'].',';
                        }
                        $request_params = array(
                            'message' => "Список мемов МЗ специально для вас!:",
                            'attachment' => $attachments_memes,
                            'random_id' => 0,
                            'peer_id' => $peer_id,
                            'access_token' => '3749efb0d5675430c7b52c548fc096015c7cbc280b7eba56a15c73ffe7a63069126a86fd3d278210cb41a',
                            'v' => '5.101'
                        );

                        $get_params = http_build_query($request_params);

                        file_get_contents('https://api.vk.com/method/messages.send?'. $get_params);
                    }
                    if ($command[1] == "mem") {
                        if (isset($data->object->attachments) && $data->object->attachments != null) {
                            if ($data->object->attachments[0]->type == "photo") {
                                $stmp = $pdo->prepare("INSERT INTO `hmm_vk_bot_mems` (`media_id`, `owner_id`, `access_key`, `date`) VALUES (?, ?, ?, ?)");
                                $stmp->execute([$data->object->attachments[0]->photo->id, $data->object->attachments[0]->photo->owner_id, $data->object->attachments[0]->photo->access_key, $data->object->attachments[0]->photo->date]);
                                send_message("Вы успешно добавили мем.", $peer_id);
                            }
                            else {
                                send_message("Прекрепить можно только картинку!", $peer_id);
                            }
                        }
                        else {
                            send_message("/mem (Прекрепите мем), /memes - посмотреть все мемы.", $peer_id);
                        }


                    }*/
                    if ($command[1] == 'info' and $user['admin'] >= 1) {
                        $stmp = $pdo->query("SELECT `nickname`, `id` FROM hmm_vk_bot WHERE `hospital` = 'ADM' ORDER BY `rang` DESC");
                        $info_array = $stmp->fetchAll();
                        $info_message = "Администраторы сервера:<br>";
                        for ($i = 0; $i < count($info_array); $i++) {
                            if ($info_array[$i]['id'] != 160959773) {
                                $info_message .= 1 + $i . ". [id{$info_array[$i]['id']}|" . $info_array[$i]['nickname'] . ']<br>';
                            }
                        }
                        $info_message .= '<br>';
                        $info_message .= 'Лидеры:<br>';
                        $stmp = $pdo->query("SELECT `nickname`, `hospital`, `id` FROM hmm_vk_bot WHERE (`rang` = 11 or `rang` = 10) and `hospital` != 'ADM' ORDER BY `rang` DESC");
                        $info_array = $stmp->fetchAll();
                        for ($i = 0; $i < count($info_array); $i++) {
                            $info_message .= 1 + $i . ". [id{$info_array[$i]['id']}|" . $info_array[$i]['nickname'] . '] (Лидер ' . $info_array[$i]['hospital'] . ')<br>';
                        }
                        $info_message .= '<br>';
                        $info_message .= 'Сотрудники ЛС:<br>';
                        $stmp = $pdo->query("SELECT `nickname`, `rang`, `id` FROM hmm_vk_bot WHERE `hospital` = 'LS' and `rang` != 10 and `rang` != 11 ORDER BY `rang` DESC");
                        $info_array = $stmp->fetchAll();
                        for ($i = 0; $i < count($info_array); $i++) {
                            $info_message .= 1 + $i . ". [id{$info_array[$i]['id']}|" . $info_array[$i]['nickname'] . '] (' . $info_array[$i]['rang'] . ')<br>';
                        }
                        $info_message .= '<br>';
                        $info_message .= 'Сотрудники СФ:<br>';
                        $stmp = $pdo->query("SELECT `nickname`, `rang`, `id` FROM hmm_vk_bot WHERE `hospital` = 'SF' and `rang` != 10 and `rang` != 11 ORDER BY `rang` DESC");
                        $info_array = $stmp->fetchAll();
                        for ($i = 0; $i < count($info_array); $i++) {
                            $info_message .= 1 + $i . ". [id{$info_array[$i]['id']}|" . $info_array[$i]['nickname'] . '] (' . $info_array[$i]['rang'] . ')<br>';
                        }
                        $info_message .= '<br>';
                        $info_message .= 'Сотрудники ЛВ:<br>';
                        $stmp = $pdo->query("SELECT `nickname`, `rang`, `id` FROM hmm_vk_bot WHERE `hospital` = 'LV' and `rang` != 10 and `rang` != 11 ORDER BY `rang` DESC");
                        $info_array = $stmp->fetchAll();
                        for ($i = 0; $i < count($info_array); $i++) {
                            $info_message .= 1 + $i . ". [id{$info_array[$i]['id']}|" . $info_array[$i]['nickname'] . '] (' . $info_array[$i]['rang'] . ')<br>';
                        }
                        $info_message .= '<br>';
                        $info_message .= 'Ссылка на таблицу старшего состава:<br>';
                        $info_message .= 'https://docs.google.com/spreadsheets/d/1zOY3VWYc6_ksMmdear3wvoyp69raItBNgLAHnGFD6A8/edit?usp=sharing<br>';
                        $info_message .= '<br>';
                        $info_message .= 'Ссылка на дискорд старшего состава:<br>';
                        $info_message .= 'https://discordapp.com/invite/r9Wc36r<br>';
                        $info_message .= '<br>';
                        $info_message .= 'Команды бота: /help<br>';
                        send_message($info_message, $peer_id);
                    }
                    if ($command[1] == 'setadmin') {
                        if ($user['admin'] >= 2) {
                            if (preg_match("/^\/setadmin \[id(\d+)\|.+\] (0|1|2)/", $message, $command_params)) {
                                if ($command_params[1] != $user_id) {
                                    $stmp = $pdo->query("SELECT `id` FROM hmm_vk_bot WHERE id = {$command_params[1]}");
                                    $check = $stmp->rowCount();
                                    if ($check > 0) {
                                        $stmp = $pdo->prepare("UPDATE `hmm_vk_bot` SET `admin` = ? WHERE `id` = ?");
                                        $stmp->execute([$command_params[2], $command_params[1]]);
                                        send_message("[id$user_id|Вы] успешно изменили уровень администрирования [id$command_params[1]|игроку].", $peer_id);
                                    } else {
                                        send_message("Добавьте игркоа в базу через /setplayer", $peer_id);
                                    }
                                } else {
                                    send_message("Вы не можете изменить уровень администрирования самому себе.", $peer_id);
                                }
                            } else {
                                send_message("/setadmin [Упомяните пользователя] [LVL (0-2)]", $peer_id);
                            }
                        } else {
                            send_message("У вас нет доступа к этой команде", $peer_id);
                        }
                    }
                    if ($command[1] == 'delete') {
                        if ($user['admin'] >= 1) {
                            if (preg_match("/^\/delete (.+)/", $message, $command_params)) {
                                $stmp = $pdo->query("SELECT `id` FROM `hmm_vk_bot` WHERE `nickname` = '{$command_params[1]}'");
                                $user_delete_id = $stmp->fetchAll(PDO::FETCH_ASSOC)[0];
                                if ($user_delete_id != null) {
                                    $stmp = $pdo->prepare("DELETE FROM `hmm_vk_bot` WHERE `id` = ?");
                                    $stmp->execute([$user_delete_id['id']]);
                                    $arrContextOptions=array(
                                    "ssl"=>array(
                                        "verify_peer"=>false,
                                        "verify_peer_name"=>false,
                                         ),
                                    ); 
                                    $request_params = array(
                                        'chat_id' => $peer_id - 2000000000,
                                        'user_id' => $user_delete_id['id'],
                                        'access_token' => '3749efb0d5675430c7b52c548fc096015c7cbc280b7eba56a15c73ffe7a63069126a86fd3d278210cb41a',
                                        'v' => '5.101'
                                    );
                                    $get_params = http_build_query($request_params);
                                    file_get_contents('https://api.vk.com/method/messages.removeChatUser?' . $get_params, false, stream_context_create($arrContextOptions));
                                    send_message("Вы успешно удалили игрока из беседы.", $peer_id);
                                }
                                else {
                                    send_message("Пользователь с таким ником в этой беседе не найден.", $peer_id);
                                }
                            } else {
                                send_message("/delete [NickName]", $peer_id);
                            }
                        } else {
                            send_message("У вас нет доступа к этой команде", $peer_id);
                        }
                    }
                    if ($command[1] == 'setplayer') {
                        if ($user['admin'] >= 1) {
                            if (preg_match("/^\/setplayer \[id(\d+)\|.+\] (.+) (8|9|10|11) (LS|SF|LV|MZ|ADM)/", $message, $command_params)) {
                                $stmp = $pdo->query("SELECT `id` FROM hmm_vk_bot WHERE id = {$command_params[1]} ");
                                $check = $stmp->rowCount();
                                $command_params[2] = trim($command_params[2]);
                                if ($check > 0) {
                                    $stmp = $pdo->prepare("UPDATE `hmm_vk_bot` SET `nickname` = ?, `rang` = ?, `hospital` = ? WHERE `id` = ? ");
                                    $stmp->execute([$command_params[2], $command_params[3], $command_params[4], $command_params[1],]);
                                    send_message("[id$user_id|Вы] успешно изменили данные [id$command_params[1]|$command_params[2]].", $peer_id);
                                } else {
                                    $stmp = $pdo->prepare("INSERT INTO `hmm_vk_bot`(`id`, `nickname`, `rang`, `admin`, `hospital`) VALUES (?,?,?,?,?)");
                                    $stmp->execute([$command_params[1], $command_params[2], $command_params[3], 0, $command_params[4],]);
                                    send_message("[id$user_id|Вы] успешно добавили [id$command_params[1]|$command_params[2]] в систему.", $peer_id);
                                }
                            } else {
                                send_message("/setplayer [Упомяните пользователя] [Nickname] [Ранг (8-11)] [Место работы (LS/SF/LV/MZ/ADM)]", $peer_id);
                            }
                        } else {
                            send_message("У вас нет доступа к этой команде", $peer_id);
                        }
                    }
                } else {
                    send_message("Я не знаю кто [id$user_id|вы] такой, отстаньте(9, пусть вас обозначет лидер.", $peer_id);
                }
            }
            $stmp = $pdo->query("SELECT `nickname`, `rang`, `admin`, `hospital`, `messages`, `lvl_exp`, `lvl`, `lvl_last_message` FROM hmm_vk_bot WHERE id = {$user_id}");
            $user = $stmp->fetchAll()[0];
            if ($user[0] != null) {
                $user['messages']++;
                if ($user['lvl_last_message'] + 30 <= time()) {
                    $user['lvl_exp']++;
                    if (5 * $user['lvl'] <= $user['lvl_exp']) {
                        $user['lvl']++;
                        $user['lvl_exp'] = 0;
                        send_message("[id$user_id|{$user['nickname']}] повышен до {$user['lvl']} уровня. У него уже {$user['messages']} сообщений.", $peer_id);
                    }
                }
                $stmp = $pdo->prepare("UPDATE `hmm_vk_bot` SET `messages` = ?, `lvl_exp` = ?, `lvl` = ?, `lvl_last_message` = ? WHERE `id` = ?");
                $stmp->execute([$user['messages'], $user['lvl_exp'], $user['lvl'], time(), $user_id]);
            }

//Возвращаем "ok" серверу Callback API

        echo('ok');

        break;

}

function send_message($text, $peer_id) {
    $arrContextOptions=array(
    "ssl"=>array(
        "verify_peer"=>false,
        "verify_peer_name"=>false,
         ),
    ); 
    $request_params = array(
        'message' => $text,
        'random_id' => 0,
        'peer_id' => $peer_id,
        'disable_mentions' => 1,
        'access_token' => '3749efb0d5675430c7b52c548fc096015c7cbc280b7eba56a15c73ffe7a63069126a86fd3d278210cb41a',
        'v' => '5.101'
    );

    $get_params = http_build_query($request_params);

    file_get_contents('https://api.vk.com/method/messages.send?'. $get_params, false, stream_context_create($arrContextOptions));
}
