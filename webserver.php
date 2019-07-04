#!/usr/bin/env php

<?php

// FROM B3Q WITH L0V3 SSH


echo "Run script and open socket on :8000\r\n";
$socket = stream_socket_server("tcp://0.0.0.0:8000", $errno, $errstr);

if (!$socket) { 

    die("$errstr ($errno)\n");
} else {

	echo "Begin socket listern:\r\n";

	$connects = array();
	
	while (true) {
	
	echo "Run bc request...  ";
	
	$read = $connects;
	$read []= $socket;
	$write = $except = null;
	$block_count = shell_exec('bitcoin-cli getblockcount');
	
	echo ": $block_count";
	
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
		$coin = $get_request[1];
		$phone_prefix = $get_request[2];
		switch ($coin) {
			case "btc": 
				$address = shell_exec("bitcoin-cli getaddressesbylabel $phone_prefix");
				
				if (!$address) {
					$address = shell_exec("bitcoin-cli getnewaddress $phone_prefix");
				} else { $json = json_decode($address, true); foreach($json as $key => $value) { $address = $key; } }
				echo trim($address);
				break;
			
			case "xmr": 
				$creds = (file_get_contents(__DIR__."/monero/1402/monero-wallet-rpc.8002.login"));
				$shell_cmd = "curl -u $creds --digest -X POST http://127.0.0.1:8002/json_rpc -d \"{\"jsonrpc\":\"2.0\",\"id\":\"0\",\"method\":\"make_integrated_address\",\"params\":\"{\"payment_id\":\"$phone_prefix\"}\"}\" -H 'Content-Type: application/json'";
				echo $shell_cmd;
				$address = shell_exec($shell_cmd);
				var_dump($address);
			
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
