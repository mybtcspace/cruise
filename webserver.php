#!/usr/bin/env php
<?php

echo "Run script and open socket on :8999\r\n";
$socket = stream_socket_server("tcp://0.0.0.0:8000", $errno, $errstr);

if (!$socket) {
	die("$errstr ($errno)\n");
} else {

	while ($connect = stream_socket_accept($socket, -1)) {
	$block_count = shell_exec('bitcoin-cli -conf=/media/btc_bc/bitcoind/btc.conf getblockcount');
	echo "Get block: $block_count\r\n";
	echo "Echo block to http\r\n";
	fwrite($connect, $block_count);//"HTTP/1.1 200 OK\r\nContent-Type: text/html\r\nConnection: close\r\n\r\n$block_count\r\n");
	//fclose($connect);
	}

fclose($socket);
}
?>
