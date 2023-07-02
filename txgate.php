#!/usr/bin/php
<?php 

//универсальный скрипт принимает данные из блокчейнов
// параметрый передачи [имя блокчейна] [tx_hash]
$blockchain = $argv[1];
$txid = $argv[3];
$content = $argv[2];
$creds = explode(':', $content);
//добавляем данныйе в mongodb-like базу данных
$file = 'cruise_txs.txt';
//свитчем выбираем апи работы с блокчейном

function btc_node($method,$params, $creds) {
	
	$url = 'http://127.0.0.1:8332';
        echo "Run bc request...  \r\n";
	$request = json_encode(array(
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => 'curl'
                                    )
                    );

         $curl = curl_init($url);
            $options = array(
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            CURLOPT_USERPWD        => $creds[0] . ':' . $creds[1],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_HTTPHEADER     => array('Content-type: application/json'),
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $request
        );
	curl_setopt_array($curl, $options);// Отправка запроса
        $response = curl_exec($curl);

// Проверка наличия ошибок
        if ($response === false) {
            $error = curl_error($curl);
            echo 'cURL error: ' . $error;
        } else {
    // Обработка ответа
        $jsonResponse = json_decode($response, true);
    
    // Проверка наличия ошибки в ответе
    if (isset($jsonResponse['error'])) {
        $errorMessage = $jsonResponse['error']['message'];
        echo 'JSON-RPC error: ' . $errorMessage;
    } else {
        $result = $jsonResponse;
	    return $result;
    }
}

// Закрытие cURL
curl_close($curl);



	
}

switch ($blockchain){
	
	case 'btc':
		$def = TRUE;
                
		$txdata = btc_node('gettransaction',[$txid],$creds);// 'default';
                $block_height = $txdata['blockheight'];
                $payment_id = $txdata['details']['label'];
                $amount = $txdata['details']['amount'];
                
                break;
		
	case 'xmr':
		$def = TRUE;
		$creds = file_get_contents(__DIR__."/monero-wallet-rpc.8002.login");
		$shell_cmd = "curl -u $creds --digest -X POST http://127.0.0.1:8002/json_rpc -d '{\"jsonrpc\":\"2.0\",\"id\":\"0\",\"method\":\"get_transfer_by_txid\",\"params\":{\"txid\":\"$txid\"}}' -H 'Content-Type: application/json'";
		$json = json_decode(shell_exec($shell_cmd), TRUE);
		if (array_key_exists('error', $json)) {$payment_id = 'error';} else {
			$payment_id = $json['result']['transfer']['payment_id'];
			$amount = $json['result']['transfer']['amount'];
			$block_height = $json['result']['transfer']['height'];
		}
		break;
	
	default:
		$def = FALSE;
		$invoice_request = 'default';
}
	//var_dump($json);
	if ($def) {
		$uri = "http://ptback.mybtc.space/";
		$salt = md5($payment_id.$txid.$amount.'mybtc.space');
		$invoice_request = "invoices/approve/$blockchain/$payment_id/$txid/$amount/$block_height/$salt";
		$shell_cmd = "curl -X GET --data key $uri.$invoice_request";
		$curl_data = shell_exec($shell_cmd);
		var_dump($curl_data);
		}
	file_put_contents($file,"\r\n$invoice_request\r\n",FILE_APPEND);
	
	
//скрипт построен на shell_exec, поэтому внимательно следим за каждой командой и входящими параметрами
	
	
	
	




?>
