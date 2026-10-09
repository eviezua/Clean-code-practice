<?php

namespace App\ReportSystem;

class OrderReportSystem
{
    private string $dbHost = '127.0.0.1';
    private string $dbUser = 'root';
    private string $currencyApiUrl = 'https://api.exchangerate.host/latest';
    private array $lastExportedData = [];

    public function generateAndSendCsvReport(int $customerId, string $email): string
    {
        $pdo = new \PDO("mysql:host={$this->dbHost};dbname=shop", $this->dbUser, 'secret');

        $stmt = $pdo->prepare("SELECT id, total_amount, currency FROM orders WHERE customer_id = ?");
        $stmt->execute([$customerId]);
        $orders = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $ch = curl_init("{$this->currencyApiUrl}?base=USD");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $rates = json_decode($response, true)['rates'] ?? ['EUR' => 0.9, 'USD' => 1.0];

        $csvLines = ["Order ID;Total USD"];
        foreach ($orders as $order) {
            $rate = $rates[$order['currency']] ?? 1.0;
            $totalInUsd = $order['total_amount'] / $rate;
            $csvLines[] = "{$order['id']};{$totalInUsd}";
        }
        $csvContent = implode("\n", $csvLines);
        $this->lastExportedData = $csvLines;

        $mailer = new \Vendor\SmsAndEmail\MailerClient('api_key_999');
        $mailer->sendEmailWithAttachment($email, 'Your Sales Report', $csvContent);

        return $csvContent;
    }

    public function getLastExportedData(): array
    {
        return $this->lastExportedData;
    }
}