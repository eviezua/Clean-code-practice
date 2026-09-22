<?php

namespace App\Order;

class OrderProcessor
{
    private const ERROR_OUT_OF_STOCK = 101;
    private const ERROR_PAYMENT_FAILED = 102;
    private const ERROR_INVALID_DISCOUNT = 103;
    private const SUCCESS = 0;

    public function __construct(
        private Warehouse $warehouse,
        private PaymentGateway $payment,
        private EmailClient $email,
        private BonusSystem $bonuses,
        private Logger $logger,
    ) {}

    public function processOrder(Order $order, User $user, string $promoCode, bool $isExpress, bool $useBonus): int
    {
        foreach ($order->getItems() as $item) {
            if (!$this->warehouse->hasStock($item->getId(), $item->getQuantity())) {
                $this->logger->error("Item out of stock: " . $item->getId());
                return self::ERROR_OUT_OF_STOCK;
            }
        }

        if ($promoCode !== '') {
            if (!$order->applyPromoCode($promoCode)) {
                $this->logger->error("Invalid promo code");
                return self::ERROR_INVALID_DISCOUNT;
            }
        }

        if ($useBonus) {
            $bonusAmount = $this->bonuses->getUserBonuses($user->getId());
            $order->reduceTotal($bonusAmount);
            $this->bonuses->withdraw($user->getId(), $bonusAmount);
        }

        if ($isExpress) {
            $order->addFee(15.00);
            $this->warehouse->markAsPriority($order->getId());
        }

        $paymentResult = $this->payment->charge($user->getPaymentToken(), $order->getTotal());
        if (!$paymentResult->isSuccess()) {
            $this->logger->error("Payment failed");
            return self::ERROR_PAYMENT_FAILED;
        }

        if ($isExpress) {
            $msg = sprintf("EXPRESS Order %s confirmed for %s", $order->getId(), $user->getEmail());
            $this->email->send($user->getEmail(), $msg);
            $this->logger->info("Express order notification sent");
        } else {
            $msg = sprintf("STANDARD Order %s confirmed for %s", $order->getId(), $user->getEmail());
            $this->email->send($user->getEmail(), $msg);
            $this->logger->info("Standard order notification sent");
        }

        return self::SUCCESS;
    }
}