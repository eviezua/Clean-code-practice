<?php

namespace App\Sender;

interface MessageSender
{
    public function send(string $to, string $message): void;
}
?>

<?php

namespace App\Sender;

class MockSender implements MessageSender
{
    public string $lastSentTo = '';
    public string $lastSentMessage = '';
    private array $messages = [];
    public function send(string $to, string $message): array
    {
        $this->messages[] = ['to' => $to, 'message' => $message];

        $this->lastSentTo = $to;
        $this->lastSentMessage = $message;

        return $this->messages;
    }
}
?>

<?php

namespace App\Sender;

class SendGridSender implements MessageSender
{
    private \Vendor\SendGrid\SendGridClient $client;

    public function __construct(
        \Vendor\SendGrid\SendGridClient $client,
    ) {
        $this->client = $client;
    }

    public function send(string $to, string $message): void
    {
        $this->client->sendEmailRaw($to, $message);
    }
}
?>

<?php

namespace App\Order;

abstract class OrderComponent
{
    abstract public function calculateTotal();
}
?>

<?php

namespace App\Order;

class MockOrder extends OrderComponent
{
    public function __construct(
        public float $total
    ) {
    }

    public function calculateTotal(): float
    {
        return $this->total;
    }
}
?>

<?php

namespace App\Order;

final class OrderList extends OrderComponent
{
    private const MIN_PRICE_TO_DISCOUNT = 100.0;
    private const DISCOUNT = 10;
    public function __construct(
        public array $items = []
    ) {
    }

    public function addItem(OrderItem $item): void
    {
        $this->items[] = $item;
    }

    public function removeItem(OrderItem $item): void
    {
        $filtered = array_filter($this->items, function (OrderItem $child) use ($item) {
            return $child !== $item;
        });

        $this->items = array_values($filtered);
    }

    public function calculateTotal(): float
    {
        $total = 0.0;

        foreach ($this->items as $item) {
            $total += $item->calculateTotal();
        }

        $total -= $this->calculateDiscount($total);

        return $total;
    }

    private function calculateDiscount(float $amount): float
    {
        if ($amount > self::MIN_PRICE_TO_DISCOUNT) {
            return $amount * self::DISCOUNT / 100;
        }

        return 0.0;
    }
}
?>

<?php

namespace App\Order;

final class OrderItem extends OrderComponent
{
    public function __construct(
        public string $name,
        public float $price,
        public int $quantity
    ) {
    }

    public function calculateTotal(): float
    {
        $total = 0.0;
        $total += $this->price * $this->quantity;

        return $total;
    }
}
?>

<?php

namespace App\Service;

use App\Order\OrderComponent;
use App\Sender\MessageSender;

class InvoiceService
{
    private MessageSender $sender;
    private OrderComponent $order;

    public function __construct(
        MessageSender $sender,
        OrderComponent $order
    ) {
        $this->sender = $sender;
        $this->order = $order;
    }

    public function sendInvoice(string $customerEmail): void
    {
        $this->sender->send(
            $customerEmail,
            "Invoice Total: " . $this->order->calculateTotal()
        );
    }
}
?>

<?php

namespace App\Test;

use App\Order\MockOrder;
use App\Order\OrderItem;
use App\Order\OrderList;
use App\Sender\MockSender;
use App\Service\InvoiceService;

class InvoiceServiceTest extends TestCase
{
    public function testInvoiceSending(): void
    {
        $sender = new MockSender();
        $order = new MockOrder(1000.0);

        $service = new InvoiceService($sender, $order);

        $service->sendInvoice('user1@example.com');

        $this->assertSame('user1@example.com', $sender->lastSentTo);
        $this->assertStringContainsString('1000.0', $sender->lastSentMessage);
    }

    public function testCalculateTotalWithoutDiscount(): void
    {
        $order = new OrderList();

        $order->addItem(new OrderItem('Book', 20.0, 2));
        $order->addItem(new OrderItem('Pen', 5.0, 2));

        $this->assertSame(50.0, $order->calculateTotal());
    }

    public function testCalculateTotalWithDiscount(): void
    {
        $order = new OrderList();

        $order->addItem(new OrderItem('Laptop', 200.0, 1));

        $this->assertSame(180.0, $order->calculateTotal());
    }
}