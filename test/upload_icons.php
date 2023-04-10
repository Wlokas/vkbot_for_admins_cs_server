<?php
require_once '../config.php'; // Подключение конфигурации
if(isset($_POST['confirm'])) {
    $response = api("appWidgets.saveGroupImage", ['hash' => $_POST['hash'], 'image' => $_POST['image']]);
    print_r($response);
    exit();
}
$response = api("appWidgets.getGroupImageUploadServer", ['image_type' => '24x24']);
$upload_url = $response['upload_url'];

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
?>

<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <style>
        div {
            padding: 10px;
        }
    </style>
    <title>Загрузка иконок</title>
</head>
<body>
<div>
<h2>Загрузка изображения</h2>
<form target="_blank" method="post" action="<?=$upload_url?>" enctype="multipart/form-data">
    <input type="file" name="image">
    <button>Загрузить</button>
</form>
</div>
<div>
    <h2>Подтверждение сохранения</h2>
    <form method="post">
        <label>Hash: </label><input type="text" name="hash"><br>
        <label>Image: </label><input type="text" name="image"><br>
        <button name="confirm">Загрузить</button>
    </form>
</div>
</body>
</html>
