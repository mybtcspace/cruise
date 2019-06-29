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
	$block_count = shell_exec('bitcoin-cli -conf=/media/btc_bc/bitcoind/btc.conf getblockcount');
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
        fwrite($connect, "HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nConnection: keep-alive\r\n\r\n$block_count");
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
	fwrite($connect, $block_count);//"HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nConnection: close\r\n\r\n$block_count\r\n");
	fclose($connect);
	}

fclose($socket);
*/


?>
