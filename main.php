<?php
$json_url = "http://api.ebulksms.com:8080/sendsms.json";
$token = '';
$keystore = "https://api.apify.com/v2/key-value-stores/{$_ENV['APIFY_DEFAULT_KEY_VALUE_STORE_ID']}/records/INPUT?token={$token}&disableRedirect=1";

$consoleinput = (doGetRequest($keystore));
$json_input = json_decode($consoleinput['body'], true);;

//print_r($_ENV);print_r($consoleinput);print_r($json_input);exit;
$flash = 0;
$time = strftime('%l:%M %p', time() + 3600);//GMT +1:00 Timezone, 0 DST
$max_usd_rate = $json_input['max_usd_rate'];
$min_usd_rate = $json_input['min_usd_rate'];
//$max_gbp_rate = $json_input['max_gbp_rate'];
//$min_gbp_rate = $json_input['min_gbp_rate'];
//$max_eur_rate = $json_input['max_eur_rate'];
//$min_eur_rate = $json_input['min_eur_rate'];
$username = $json_input['username'];
$apikey = $json_input['apikey'];

$sendername = substr($json_input['sender_name'], 0, 11);
$recipients = $json_input['telephone'];

$pagefunctionresult = json_decode(file_get_contents($_ENV['crawler_url']), true);

$message = substr("{$json_input['message']}{$pagefunctionresult[0]['pageFunctionResult']['rate']} on {$pagefunctionresult[0]['pageFunctionResult']['date']} at $time" , 0, 918);
if(!empty($pagefunctionresult[0]['pageFunctionResult']['rate'])){
    if(!empty($max_usd_rate) && $pagefunctionresult[0]['pageFunctionResult']['rate'] >= $max_usd_rate){
        $result = useJSON($json_url, $username, $apikey, $flash, $sendername, $message, $recipients);
        echo $result;
    }
    if(!empty($min_usd_rate) && $pagefunctionresult[0]['pageFunctionResult']['rate'] <= $min_usd_rate){
        $result = useJSON($json_url, $username, $apikey, $flash, $sendername, $message, $recipients);
        echo $result;
    }
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

function doGetRequest($url, $arr_params = array()) {
    $response = array();
    $str_params = $arr_params;
    if(!empty($arr_params) && is_array($arr_params)){
        $str_params = http_build_query($str_params);
    }
    $final_url = empty($str_params) ? $url : $url . '?' . implode('&', $str_params);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $final_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_ENCODING , "gzip");
    curl_setopt($ch, CURLOPT_ENCODING, "");
    $response['body'] = curl_exec($ch);
    $response['code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $response;
}
?>