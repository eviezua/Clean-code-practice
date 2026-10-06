<?php

namespace App\Delivery;

interface DeliveryService {
    public function dispatchOrder(Order $order): string;
}
?>

<?php

namespace App\Delivery\Adapter;

use App\Delivery\DeliveryService;
use App\Delivery\Order;
use App\Exception\DeliveryException;

final readonly class GuzzleCourierAdapter implements DeliveryService
{
    public function __construct(
        private GuzzleClient $httpClient
    ){}

    public function dispatchOrder(Order $order): string
    {
        $orderId = $order->getId();
        $address = $order->getAddress();

        try {
            //Example API, not existing
            $response = $this->httpClient->post('https://api.courier.com/v1/dispatch', [
                'json' => ['order_id' => $orderId, 'address' => $address]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['tracking_number'];
        } catch (\Throwable $e) {
            throw new DeliveryException("Failed to dispatch order", previous: $e);
        }
    }
}
?>

<?php

namespace App\Exception;

use RuntimeException;

class DeliveryException extends RuntimeException {}

?>

<?php

namespace App\Shipment;

use App\Delivery\Order;

readonly class RatesCalculator
{
    private const BASE_RATE = 50.0;

    public function calculateShippingCost(Order $order): float
    {
        $zipCode = $order->getZipCode();
        $weight = $order->getWeight();

        $discount = $this->findRegionalDiscount($zipCode);

        return (self::BASE_RATE - $discount) * $weight;
    }

    private function findRegionalDiscount(string $zipCode): float
    {
        if ($zipCode === '01001') {
            return 10.0;
        }

        return 0.00;
    }
}
?>

<?php

namespace App\Notification;

interface Notifier
{
    public function send(string $phone, string $trackingNumber): void;
}

?>

<?php

namespace App\Notification;

use \Vendor\SmsSdk\Client as Client;

readonly class SmsNotifier implements Notifier
{
    public function __construct(
        private Client $client
    ) {}
    public function send(string $phone, string $trackingNumber): void
    {
        $this->client->sendSmsRaw($phone, "Tracking: " . $trackingNumber);
    }
}
?>

<?php

namespace App\Service;

use App\Notification\Notifier;
use App\Shipment\RatesCalculator;
use App\Delivery\DeliveryService;
use App\Delivery\Order;

class ShippingService
{
    public function __construct(
        private RatesCalculator $calculator,
        private DeliveryService $delivery,
        private Notifier $notifier
    ) {}

    public function getActiveShipments(): array
    {
        return [];
    }

    public function calculateCost(Order $order): float
    {
        return $this->calculator->calculateShippingCost($order);
    }

    public function process(Order $order): string
    {
        $trackingNumber = $this->delivery->dispatchOrder($order);

        $this->notifier->send($order->getCustomerPhone(), $trackingNumber);

        return $trackingNumber;
    }
}