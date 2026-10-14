<?php

namespace App\DBConnection;

interface DBConnection
{
    public function getConnection(): PDO;
}
?>

<?php

namespace App\DBConnection;

use PDO;
class MysqlConnection implements DBConnection
{
    private PDO $connection;
    private static ?MysqlConnection $instance = null;
    private string $dbHost = '127.0.0.1';
    private string $dbName = 'shop';
    private string $dbUser = 'root';
    private string $dbPassword = 'secret';

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    public static function getInstance(): MysqlConnection
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->connection = new \PDO("mysql:host={$this->dbHost};dbname={$this->dbName}", $this->dbUser, $this->dbPassword);
    }

    private function __clone() {}

    private function __wakeup() {}
}
?>

<?php

namespace App\Repository;

interface OrdersRepository
{
    public function getOrders(int $customerId): array;
}
?>

<?php

namespace App\Repository;

use App\DBConnection\DBConnection;
use App\Entity\Order;

readonly class Orders implements OrdersRepository
{
    public function __construct(
        private DBConnection $connection,
    ) {
    }

    public function getOrders(int $customerId): array
    {
        $pdo = $this->connection->getConnection();

        $stmt = $pdo->prepare("SELECT id, total_amount, currency FROM orders WHERE customer_id = ?");
        $stmt->execute([$customerId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $orders = [];
        foreach ($rows as $row) {
            $orders[] = new Order(
                (int)$row['id'],
                $row['currency'],
                (float)$row['total_amount']
            );
        }

        return $orders;
    }
}
?>

<?php

namespace App\Currency;

interface Rates
{
    public function getCurrency(string $base): array;
}
?>

<?php

namespace App\Currency;

use App\Exception\CurrencyException;

class CurrencyAdapter implements Rates
{
    private string $currencyApiUrl = 'https://api.exchangerate.host/latest';

    public function getCurrency(string $base): array
    {
        try {
            $request = curl_init("{$this->currencyApiUrl}?base={$base}");

            curl_setopt($request, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($request);

            return json_decode($response, true)['rates'];
        } catch (\Throwable $exception) {
            throw new CurrencyException('Failed to get currency', previous: $exception);
        }
    }
}
?>

<?php

namespace App\Exception;

use RuntimeException;

class CurrencyException extends RuntimeException {}
?>

<?php

namespace App\Report;

interface Component
{
    public function accept(Visitor $visitor);
}
?>

<?php

namespace App\Entity;

use App\Report\Component;
use App\Report\Visitor;

class Order implements Component
{
    public function __construct(
        private int $id,
        private readonly string $currency,
        private readonly float $totalAmount
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function accept(Visitor $visitor): void
    {
        $visitor->visitOrder($this);
    }
}
?>

<?php

namespace App\Report;

use App\Entity\Order;

interface Visitor
{
    public function visitOrder(Order $order): void;
    public function getReportContent(): string;
}
?>

<?php

namespace App\Report;

use App\Currency\Rates;

class SCVOrderReport implements Visitor
{
    private array $lines = [];
    private ?array $cachedRates;
    public function __construct(
        private readonly Rates $rates,
        private readonly string $baseCurrency = 'USD'
    ){
        $this->lines[] = "Order ID;Total {$this->baseCurrency}";
    }

    public function visitOrder(Order $order): void
    {
        if (empty($this->cachedRates)) {
            $this->cachedRates = $this->rates->getCurrency($this->baseCurrency);;
        }

        $rate = $this->cachedRates[$order->getCurrency()] ?? 1.0;

        $total = $order->getTotalAmount() / $rate;

        $this->lines[] = "{$order->getId()};{$total}";
    }

    public function getReportContent(): string
    {
        return implode("\n", $this->lines);
    }
}
?>

<?php

namespace App\Notification;

interface Sender
{
    public function send($to, $message, $attachment = null): void;
}
?>

<?php

namespace App\Notification;

use Vendor\SmsAndEmail\MailerClient;

class VendorSmsAndEmail implements Sender
{
    public function send($to, $message, $attachment = null): void
    {
        $client = ClientFactory::create();

        $client->sendEmailWithAttachment($to, $message, $attachment);
    }
}
?>

<?php

namespace App\Notification;

class ClientFactory
{
    private static string $apiKey = 'api_key_999';
    public static function create(): object
    {
        return new MailerClient(self::$apiKey);
    }
}
?>

<?php

namespace App\ReportSystem;

use App\Notification\Sender;
use App\Report\Visitor;
use App\Repository\OrdersRepository;

class OrderReportSystem
{
    public function __construct(
        private readonly OrdersRepository $ordersRepository,
        private readonly Visitor $visitor,
        private readonly Sender $sender,
    ) {
    }

    public function generateAndSendReport(int $customerId, string $email): void
    {
        $orders = $this->ordersRepository->getOrders($customerId);

        foreach ($orders as $order) {
            $order->accept($this->visitor);
        }

        $this->sender->send($email, 'Your Sales Report', $this->visitor->getReportContent());
    }
}