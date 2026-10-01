<?php

namespace App\Service;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;

class ShippingService
{
    private GuzzleClient $httpClient;
    private array $shipmentRates = [];

    public function __construct(GuzzleClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function getActiveShipments(): ?array
    {
        $hasShipments = false;

        if (!$hasShipments) {
            return null;
        }

        return $this->shipmentRates;
    }

    public function calculateShippingCost(string $zipCode, float $weight): float
    {
        try {
            $discount = $this->findRegionalDiscount($zipCode);
            $baseRate = 50.0;

            return ($baseRate - $discount) * $weight;
        } catch (Exception $e) {
            return 50.0 * $weight;
        }
    }

    private function findRegionalDiscount(string $zipCode): float
    {
        if ($zipCode !== '01001') {
            throw new Exception("Discount not found for zip");
        }

        return 10.0;
    }

    public function dispatchOrder(int $orderId, string $address): string
    {
        try {
            //Example API, not existing
            $response = $this->httpClient->post('https://api.courier.com/v1/dispatch', [
                'json' => ['order_id' => $orderId, 'address' => $address]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            return $data['tracking_number'];
        } catch (RequestException $e) {
            throw $e;
        }
    }

    public function notifyCustomerBySms(string $phone, string $trackingNumber): void
    {
        $vendorSdk = new \Vendor\SmsSdk\Client('api_key_123');
        $vendorSdk->sendSmsRaw($phone, "Tracking: " . $trackingNumber);
    }
}