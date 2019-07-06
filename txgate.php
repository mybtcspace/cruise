<?php 

//скрипт принимает данные из блокчейнов
$blockchain = $argv[1];
$txid = $argv[2];
$file = 'cruise_txs.txt';
switch ($blockchain){
	
	case 'btc':
		echo "none";
		break;
		
	case 'xmr':
		$creds = file_get_contents(__DIR__."/monero-wallet-rpc.8002.login");
		$shell_cmd = "curl -u $creds --digest -X POST http://127.0.0.1:8002/json_rpc -d '{\"jsonrpc\":\"2.0\",\"id\":\"0\",\"method\":\"get_transfer_by_txid\",\"params\":{\"txid\":\"$txid\"}}' -H 'Content-Type: application/json'";
		$json = json_decode(shell_exec($shell_cmd), TRUE);
		if (array_key_exists('error', $json)) {$payment_id = 'error';} else {
			$payment_id = $json['result']['transfer']['payment_id'];
			$amount = $json['result']['transfer']['amount'];
			$block_height = $json['result']['transfer']['confirmations'];
		}
		break;
	
	default:
		$def = 0;
		$invoice_request = 'default';
}
	var_dump($json);
	if (!$def) {$invoice_request = "invoice_approve/$blockchain/$payment_id/$txid/$amount/$block_height";}
	file_put_contents($file,$invoice_request);
	
	




?>