<?php
require '../vendor/autoload.php';

use SWeb3\SWeb3;
use SWeb3\SWeb3_Contract;

$db = new PDO('sqlite:' . dirname(__DIR__) . '/transfer_events.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->exec("
    CREATE TABLE IF NOT EXISTS transfer (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        uniqueid TEXT NOT NULL,
        from_address TEXT NOT NULL,
        to_address TEXT NOT NULL,
        amount REAL NOT NULL,
        blocknumber INTEGER NOT NULL
    )
");

$abi = json_encode(json_decode(file_get_contents("../CQMToken.json"))->abi);
$sweb3 = new SWeb3('https://rpc.chiadochain.net');
$contract = new SWeb3_contract($sweb3, '0xF988A1b6d4C00832ed3570a4e50DdA4357a22F7D', $abi);

// Read the latest blocknumber from DB
$stmt = $db->query("SELECT MAX(blocknumber) FROM transfer");
$latestBlock = $stmt->fetchColumn();
if ($latestBlock === false) {
    $latestBlock = 0; // If no records exist, start from block 0
} else {
    $latestBlock = hexdec($latestBlock);
}
// Fetch logs from the contract for the Transfer event
if ($latestBlock > 0) {
    $latestBlockHex = dechex($latestBlock);
} else {
    $latestBlockHex = '0x0'; // Start from the genesis block if no records exist
}
$logs = $sweb3->getLogs('0xF988A1b6d4C00832ed3570a4e50DdA4357a22F7D', $latestBlock, 'latest', ['0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef']);

echo "<pre>";    

foreach ($logs->result as $log) {
    // Extract addresses from topics
    $from = '0x' . substr($log->topics[1], 26); // last 40 hex chars = 20 bytes
    $to = '0x' . substr($log->topics[2], 26);

    // Extract and convert value from hex to decimal
    $value = hexdec($log->data);

    $blockHash = $log->blockHash;
    $txHash = $log->transactionHash;
    $logIndex = hexdec($log->logIndex);

    $rawId = $blockHash . $txHash . $logIndex;
    $uniqueId = hash('sha256', $rawId);

    echo "From: $from\n";
    echo "To: $to\n";
    echo "Value (tokens): " . ($value / 1e18) . "\n"; // assuming 18 decimals
    echo "Block Number: " . hexdec($log->blockNumber) . "\n";
    echo "Unique ID: $uniqueId\n";

    // Insert into database if unique ID does not already exist
    $stmt = $db->prepare("SELECT COUNT(*) FROM transfer WHERE uniqueid = :uniqueid");
    $stmt->bindParam(':uniqueid', $uniqueId);
    $stmt->execute();
    $count = $stmt->fetchColumn();
    if ($count == 0) {
        $stmt = $db->prepare("INSERT INTO transfer (uniqueid, from_address, to_address, amount, blocknumber) VALUES (:uniqueid, :from_address, :to_address, :amount, :blocknumber)");
        $stmt->bindParam(':uniqueid', $uniqueId);
        $stmt->bindParam(':from_address', $from);
        $stmt->bindParam(':to_address', $to);
        $stmt->bindParam(':amount', $value);
        $stmt->bindParam(':blocknumber', hexdec($log->blockNumber));
        $stmt->execute();
    } else {
        echo "Duplicate entry for unique ID: $uniqueId\n";
    }
}

echo "</pre>";    

