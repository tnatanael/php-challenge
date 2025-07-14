<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Stock Quote Information</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        ul {
            padding-left: 20px;
        }
        li {
            margin-bottom: 8px;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #7f8c8d;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <h1>Stock Quote Information</h1>
    <p>Here is the stock quote you requested:</p>
    <ul>
        <li><strong>Symbol:</strong> <?= $stockData['symbol'] ?></li>
        <li><strong>Name:</strong> <?= $stockData['name'] ?></li>
        <li><strong>Date:</strong> <?= $stockData['date'] ?></li>
        <li><strong>Open:</strong> <?= $stockData['open'] ?></li>
        <li><strong>High:</strong> <?= $stockData['high'] ?></li>
        <li><strong>Low:</strong> <?= $stockData['low'] ?></li>
        <li><strong>Close:</strong> <?= $stockData['close'] ?></li>
    </ul>
    <div class="footer">
        <p>This is an automated message from the Stock API service.</p>
    </div>
</body>
</html>