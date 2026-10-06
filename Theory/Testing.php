<?php

namespace App\Service;

class InvoiceService
{
    private \Vendor\SendGrid\SendGridClient $emailClient;

    public function __construct(\Vendor\SendGrid\SendGridClient $emailClient)
    {
        $this->emailClient = $emailClient;
    }

    public function processInvoice(array $items, string $customerEmail): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        if ($total > 100.0) {
            $total = $total * 0.9;
        }

        $this->emailClient->sendEmailRaw(
            $customerEmail,
            "Invoice Total: " . $total
        );

        return $total;
    }
}
?>

<?php

namespace App\Test;

class InvoiceServiceTest extends TestCase
{
    public function testInvoiceProcessing(): void
    {
        $sendGrid = new \Vendor\SendGrid\SendGridClient('real_api_key_xyz');
        $service = new InvoiceService($sendGrid);

        $itemsSmall = [
            ['name' => 'Book', 'price' => 20.0, 'quantity' => 2],
            ['name' => 'Pen', 'price' => 5.0, 'quantity' => 2],
        ];

        $totalSmall = $service->processInvoice($itemsSmall, 'user1@example.com');
        $this->assertSame(50.0, $totalSmall);

        $itemsLarge = [
            ['name' => 'Laptop', 'price' => 200.0, 'quantity' => 1],
        ];

        $totalLarge = $service->processInvoice($itemsLarge, 'user2@example.com');
        $this->assertSame(180.0, $totalLarge);

        $this->assertSame('user2@example.com', $sendGrid->lastSentTo);
        $this->assertStringContainsString('180', $sendGrid->lastBody);
    }
}