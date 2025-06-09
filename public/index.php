<?php
require '../vendor/autoload.php';

$db = new PDO('sqlite:' . dirname(__DIR__) . '/transfer_events.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$query = $db->query('SELECT * FROM transfer ORDER BY blocknumber DESC');
$events = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Transfer Events</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <h1>Transfer Events</h1>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Unique ID</th>
                <th>From Address</th>
                <th>To Address</th>
                <th>Amount</th>
                <th>Block Number</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($events as $event): ?>
                <tr>
                    <td><?= htmlspecialchars($event['id']) ?></td>
                    <td><?= htmlspecialchars($event['uniqueid']) ?></td>
                    <td><?= htmlspecialchars($event['from_address']) ?></td>
                    <td><?= htmlspecialchars($event['to_address']) ?></td>
                    <td><?= htmlspecialchars($event['amount'] / 1e18) ?></td>
                    <td><?= htmlspecialchars($event['blocknumber']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>