<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>форма для amocrm</title>
</head>
<body>
<style>
    body {background-color: #f5f5f5}
    fieldset {width: 40%;margin: 0 auto;text-align: center}
    input {margin: 1% 0;width: 40%}
</style>
<fieldset>
    <legend>ввод данных для amocrm</legend>
    <form method="post">
        <input type="text" name="name" placeholder="Укажите имя"><br>
        <input type="email" name="email" placeholder="Укажите e-mail"><br>
        <input type="submit" value="Отправить">
    </form>

</fieldset>
<?php
if ($_POST['name'] && $_POST['email']) {
//**************************************************************
// массивы с данными для авторизации, смотреть в Настройки->API
//**************************************************************
    $user = array(
        'USER_LOGIN' => 'setfrom@gmail.com',
        'USER_HASH' => '6ee56f63c7b4bdc03fc7bf28c4ae146e'
    );
    $subdomain = 'ztest123';
    $report_mail = 'test@mail.ru';// куда отправить отчет по сделкам без задач
//**************************************************************
// авторизуемся
//**************************************************************
    $options = array(
        CURLOPT_URL => 'https://' . $subdomain . '.amocrm.ru/private/api/auth.php?type=json',
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($user),
    );
    $resp = amoexch($options);
//**************************************************************
// получим данные аккаунта с интересующими нас полями
//**************************************************************
    $options = array(
        CURLOPT_URL => 'https://' . $subdomain . '.amocrm.ru/private/api/v2/json/accounts/current',
    );
    $resp = amoexch($options);
// print_r($resp);die;
// Можно посмотреть id полей, статусов.
// В целях упрощения и минимизации кода,
// в дальнейшем id подставляются напрямую.
//**************************************************************
// добавляем контакт
//**************************************************************
    $contact['add'] = array(
        array(
            'name' => $_POST['name'],
            'tags' => 'С сайта: ' . $_SERVER['HTTP_REFERER'],
            'custom_fields' => array(
                array(
                    'id' => 863, // уникальный для аккаунта, можно посмотреть выше в 'custom_fields'
                    'values' => array(
                        array(
                            'value' => $_POST['email'],
                            'enum' => 'WORK'
                        )
                    )
                )
            )
        )
    );
    $options = array(
        CURLOPT_URL => 'https://' . $subdomain . '.amocrm.ru/api/v2/contacts',
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($contact),
    );
    $resp = amoexch($options);
//**************************************************************
// создадим сделку
//**************************************************************
    $lead['add'] = array(
        array(
            'name' => 'Тестовая сделка',
            'status_id' => 17638474, // Первичный статус, id статусов смотреть в данных аккаунта
            'price' => 123456,
            'responsible_user_id' => 17638468, // id нашего аккаунта
            'tags' => 'для теста'
        )
    );
    $options = array(
        CURLOPT_URL => 'https://' . $subdomain . '.amocrm.ru/api/v2/leads',
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($lead),
    );
    $resp = amoexch($options);
// сохраним id созданной сделки, чтобы привязать потом к ней задачу
    $lead_id = $resp['_embedded']['items'][0]['id'];
//**************************************************************
// создадим задачу, привяжем к сделке
//**************************************************************
    $task['add'] = array(
        array(
            'element_id' => $lead_id,
            'element_type' => 2,
            'task_type' => 1,
            'text' => 'тут текст тестовой задачи',
            'responsible_user_id' => 17638468,// id нашего аккаунта
            'complete_till_at' => time() + (7 * 24 * 60 * 60),// время для завершения задачи (сейчас+неделя)
        )
    );
    $options = array(
        CURLOPT_URL => 'https://' . $subdomain . '.amocrm.ru/api/v2/tasks',
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($task),
    );
    $resp = amoexch($options);
//**************************************************************
// Запросим список всех сделок.
//**************************************************************
    $options = array(
        CURLOPT_URL => 'https://' . $subdomain . '.amocrm.ru/api/v2/leads',
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_POSTFIELDS => json_encode($task),
    );
    $resp = amoexch($options);
    $leads_url = array();
    foreach ($resp['_embedded']['items'] as $leads => $lead) {
        if (!$lead['closest_task_at']) { //отберем сделки без задач в ближайшее время и сразу создадим для них задачи
            $task['add'] = array(
                array(
                    'element_id' => $lead['id'],
                    'element_type' => 2,
                    'task_type' => 1,
                    'text' => 'Сделка без задачи',
                    'responsible_user_id' => 17638468,// id нашего аккаунта
                    'complete_till_at' => time() + (3 * 24 * 60 * 60),// время для завершения задачи (сейчас+3 дня)
                )
            );
            $options = array(
                CURLOPT_URL => 'https://' . $subdomain . '.amocrm.ru/api/v2/tasks',
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($task),
            );
            $resp = amoexch($options);
            $leads_url[] = 'https://' . $subdomain . '.amocrm.ru/leads/detail/' . $lead['id'];// соберём URL всех сделок
        }
    }
//**************************************************************
// Отправим письмо со списком сделок у которых не было задач.
//**************************************************************
    if ($leads_url) {
        foreach ($leads_url as $url) {
            $body .= '<a href=">' . $url . '">' . $url . '</a><br>';
        }
        mail($report_mail, 'Список сделок у которых не было задач', $body);
    }
}
//**************************************************************
// функция обращения к API amocrm
//**************************************************************
function amoexch($options)
{
    $options = $options + array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'amoCRM-API-client/1.0',
            CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
            CURLOPT_HEADER => false,
            CURLOPT_COOKIEFILE => dirname(__FILE__) . '/cookie.txt', #PHP>5.3.6 dirname(__FILE__) -> __DIR__
            CURLOPT_COOKIEJAR => dirname(__FILE__) . '/cookie.txt', #PHP>5.3.6 dirname(__FILE__) -> __DIR__
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_SSL_VERIFYHOST => 0
        );
    $curl = curl_init();
    curl_setopt_array($curl, $options);
    $out = curl_exec($curl);
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    $code = (int)$code;
    try {
        #Если код ответа не равен 200 или 204 - возвращаем сообщение об ошибке
        if ($code != 200 && $code != 204)
            throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error', $code);
    } catch (Exception $E) {
        die('Ошибка: ' . $E->getMessage() . PHP_EOL . 'Код ошибки: ' . $E->getCode());
    }
    return json_decode($out, true);
}
?>

</body>
</html>
