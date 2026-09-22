<?php
namespace App\Exception;

use DomainException;

final class OutOfStockException extends DomainException {}
?>

<?php
namespace App\Exception;

use DomainException;

final class PaymentFailedException extends DomainException {}
?>

<?php
namespace App\Exception;

use DomainException;

final class InvalidPromocodeException extends DomainException {}
?>

<?php
namespace App\DTO;

final readonly class ProcessData
{
    public function __construct(
        public Order $order,
        public User $user,
        public string $promoCode,
        public bool $isExpress,
        public bool $useBonus
    ) {}
}
?>

<?php

namespace App\Order;

use App\DTO\ProcessData;
use App\Dto\User;
use App\Exception\InvalidPromocodeException;
use App\Exception\OutOfStockException;
use App\Exception\PaymentFailedException;

class OrderProcessor
{
    private const DEFAULT_FEE = 15.00;
    public function __construct(
        private Warehouse $warehouse,
        private PaymentGateway $payment,
        private EmailClient $email,
        private BonusSystem $bonuses,
        private Logger $logger,
    ) {}

    public function processOrder(ProcessData $data): void
    {
        $this->itemsInStock($data->order);
        $this->applyPromocodeIfExists($data->order, $data->promoCode);
        $this->handleExpress($data->order, $data->isExpress);

        $bonus = $this->calculateBonus($data->user->getId(), $data->useBonus);
        $this->applyBonus($data->order, $bonus);

        $this->chargePayment($data->order, $data->user);
        $this->withdrawBonus($data->user->getId(), $bonus);

        $this->sendMessage($data->isExpress, $data->order->getId(), $data->user->getEmail());
    }
    private function itemsInStock(Order $order): void
    {
        foreach ($order->getItems() as $item) {
            if (!$this->warehouse->hasStock($item->getId(), $item->getQuantity())) {
                $this->logger->error("Item out of stock: " . $item->getId());
                throw new OutOfStockException('Item out of stock: ' . $item->getId());
            }
        }
    }

    private function applyPromocodeIfExists(Order $order, string $promoCode): void
    {
        if ($promoCode === ''){
            return;
        }

        if (!$order->applyPromoCode($promoCode)) {
            $this->logger->error("Invalid promo code");
            throw new InvalidPromocodeException('Invalid promo code');
        }
    }

    private function handleExpress(Order $order, bool $isExpress): void
    {
        if (!$isExpress){
            return;
        }

        $order->addFee(self::DEFAULT_FEE);
        $this->warehouse->markAsPriority($order->getId());
    }

    private function calculateBonus(int $userId, bool $useBonus): float
    {
        if (!$useBonus){
            return 0.00;
        }

        return $this->bonuses->getUserBonuses($userId);
    }

    private function applyBonus(Order $order, float $bonusAmount): void
    {
        $order->reduceTotal($bonusAmount);
    }

    private function chargePayment(Order $order, User $user): void
    {
        $paymentResult = $this->payment->charge($user->getPaymentToken(), $order->getTotal());

        if (!$paymentResult->isSuccess()) {
            $this->logger->error("Payment failed");
            throw new PaymentFailedException('Payment failed');
        }
    }

    private function withdrawBonus(int $userId, float $bonusAmount): void
    {
        $this->bonuses->withdraw($userId, $bonusAmount);
    }

    private function sendMessage(bool $isExpress, int $orderId, string $email): void
    {
        $typeOfDelivery = $isExpress ? "Express" : "Standard";

        $msg = sprintf("%s order %s confirmed for %s", strtoupper($typeOfDelivery), $orderId, $email);

        $this->email->send($email, $msg);

        $this->logger->info($typeOfDelivery . "order notification sent");
    }
}