#!/usr/bin/env php
<?php
// 4ROM B3Q WIZ L0V3 SSH
/* $file = 'creds'; // creds for node
$content = file_get_contents($file);
if ($content === false) {
    echo 'creds read err';
    exit();
} */
$content = $argv[1];
$creds = explode(':', $content);

function clean_search_string( $s ) {
    $s = preg_replace( "/[^a-zA-Z0-9\s]/", '', $s );

    return $s;
}

function btc_node($method,$params, $creds) {
	// URL JSON-RPC сервера
	$url = 'http://127.0.0.1:8332';
        echo "Run bc request...  \r\n";

	// JSON-RPC метод и параметры
	//$method = 'getaddressesbylabel';
	//$params = $p; //array('param1' => 'value1', 'param2' => 'value2');

	// Формирование JSON-RPC запроса
	$request = json_encode(array(
    	'jsonrpc' => '2.0',
    	'method' => $method,
   	'params' => $params,
    	'id' => 'curl'
	));

// Настройка cURL
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
	curl_setopt_array($curl, $options);
/*curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($curl, CURLOPT_USERPWD, $creds[0].':'.$creds[1]); */

// Отправка запроса
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
        $result = $jsonResponse['result'];
	    return $result;
    }
}

// Закрытие cURL
curl_close($curl);



	
}

echo "Run script and open socket on :8000\r\n";
$socket = stream_socket_server("tcp://0.0.0.0:8000", $errno, $errstr);

if (!$socket) { 

    die("$errstr ($errno)\n");
} else {

	echo "Begin socket listern:\r\n";

	$connects = array();
	
	while (true) {
	
	
	
	$read = $connects;
	$read []= $socket;
	$write = $except = null;
	$block_count = btc_node('getblockcount',[],$creds); //shell_exec('bitcoin-cli getblockcount');
	
	echo "Block count: $block_count \r\n";
	
	 if (!stream_select($read, $write, $except, null)) { //ожидаем сокеты доступные для чтения (таймаут нулл)
        	break;
    		}

    if (in_array($socket, $read)) { //есть новое соединение
        $connect = stream_socket_accept($socket, -1); //принимаем новое соединение
        $connects[] = $connect; //добавляем его в список необходимых для обработки
		unset($read[ array_search($socket, $read) ]);
    }

    foreach($read as $connect) { //обрабатываем все соединения
        $headers = '';
        while ($buffer = rtrim(fgets($connect))) {
            $headers .= $buffer;
        }   
            
	    $get_request = explode('/', trim(substr($headers,3,(strpos($headers,"HTTP",10))-4)));
            
            if (clean_search_string($get_request[1]) == 'mybtcspace') {
            $coin = preg_replace( "/[^a-zA-Z0-9\s]/", '', htmlentities(strip_tags($get_request[2])));
	    $phone_prefix = preg_replace( "/[^a-zA-Z0-9\s]/", '',htmlentities(strip_tags($get_request[3])));
		switch ($coin) {
			case "btc": 
				//$address = shell_exec("bitcoin-cli getaddressesbylabel $phone_prefix");
				$address = btc_node('getnewaddress',[$phone_prefix], $creds);
				/*
				if (!$address) {
					//$address = shell_exec("bitcoin-cli getnewaddress $phone_prefix");
				} else { $json = json_decode($address, true); foreach($json as $key => $value) { $address = $key; } } */
			
				echo trim($address)."\r\n";
				break;
			
			case "xmr": 
				$creds = (file_get_contents(__DIR__."/monero-wallet-rpc.8002.login"));
				$shell_cmd = "curl -u $creds --digest -X POST http://127.0.0.1:8002/json_rpc -d '{\"jsonrpc\":\"2.0\",\"id\":\"0\",\"method\":\"make_integrated_address\",\"params\":{\"payment_id\":\"$phone_prefix\"}}' -H 'Content-Type: application/json'";
				$json = json_decode(shell_exec($shell_cmd), TRUE);
				if (array_key_exists('error',$json)) {$address = 'error';} else {$address = $json['result']['integrated_address'];}
				echo trim($address)."\r\n";
				break;
			
			case "waves":
				$address = "WAVES";
				break;
				
			case "eth":
				$address = "ETH";
				break;
				
			default:
				$address = "NONE";
				
		}
            
        fwrite($connect, "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nConnection: keep-alive\r\n\r\n$address");
        }
        fclose($connect);
        unset($connects[ array_search($connect, $connects) ]);
    }


}
}
	fclose($socket);
	
	/*
	while ($connect = @stream_socket_accept($socket, -1)) {
	$block_count = shell_exec('bitcoin-cli -conf=/media/btc_bc/bitcoind/btc.conf getblockcount');
	echo "Get block: $block_count\r\n";
	echo "Echo block to http\r\n";
	fwrite($connect, $block_count);//"HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nConnection: close\r\n\r\n$block_count");
	fclose($connect);
	}
	
	case 'xmr' {
	$creds = (file_get_contents(DIR."/monero/1402/monero-wallet-rpc.18083.login"));
	$answer = shell_exec("curl -u ".$creds." --digest -X POST http://127.0.0.1:18083/json_rpc -d '{"jsonrpc":"2.0","id":"0","method":"make_integrated_address","params":'"{\"payment_id\":\"70b31710874c29fe\"}"'}' -H 'Content-Type: application/json'
	
	}

fclose($socket);
*/


?>
