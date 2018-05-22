<?php
$json_url = "http://api.ebulksms.com:8080/sendsms.json";
//$json_input = json_decode(file('php://input'), true);
$json_input = file("php://input");
print_r($_ENV);print_r($argv);print_r($json_input);exit;
$flash = 0;
$time = strftime('%l:%M %p', time());
$max_usd_rate = $json_input['max_usd_rate'];
$username = $json_input['username'];
$apikey = $json_input['apikey'];

$sendername = substr($json_input['sender_name'], 0, 11);
$recipients = $json_input['telephone'];

$pagefunctionresult = json_decode(file_get_contents($_ENV['crawler_url']), true);

$message = substr("{$json_input['message']} {$pagefunctionresult[0]['pageFunctionResult']['rate']} on {$pagefunctionresult[0]['pageFunctionResult']['date']} at $time" , 0, 918);
if($pagefunctionresult[0]['pageFunctionResult']['rate'] >= $max_usd_rate){
    $result = useJSON($json_url, $username, $apikey, $flash, $sendername, $message, $recipients);
    echo $result;
}


function useJSON($url, $username, $apikey, $flash, $sendername, $messagetext, $recipients) {
    $gsm = array();
    $country_code = '234';
    $arr_recipient = explode(',', $recipients);
    foreach ($arr_recipient as $recipient) {
        $mobilenumber = trim($recipient);
        if (substr($mobilenumber, 0, 1) == '0'){
            $mobilenumber = $country_code . substr($mobilenumber, 1);
        }
        elseif (substr($mobilenumber, 0, 1) == '+'){
            $mobilenumber = substr($mobilenumber, 1);
        }
        $generated_id = uniqid('int_', false);
        $generated_id = substr($generated_id, 0, 30);
        $gsm['gsm'][] = array('msidn' => $mobilenumber, 'msgid' => $generated_id);
    }
    $message = array(
        'sender' => $sendername,
        'messagetext' => $messagetext,
        'flash' => "{$flash}",
    );

    $request = array('SMS' => array(
            'auth' => array(
                'username' => $username,
                'apikey' => $apikey
            ),
            'message' => $message,
            'recipients' => $gsm
    ));
    $json_data = json_encode($request);
    if ($json_data) {
        $response = doPostRequest($url, $json_data, array('Content-Type: application/json'));
        $result = json_decode($response);
        return $result->response->status;
    } else {
        return false;
    }
}

function doPostRequest($url, $arr_params, $headers = array('Content-Type: application/x-www-form-urlencoded')) {
    $response = array();
    $final_url_data = $arr_params;
    if (is_array($arr_params)) {
        $final_url_data = http_build_query($arr_params, '', '&');
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $final_url_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response['body'] = curl_exec($ch);
    $response['code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $response['body'];
}
?>