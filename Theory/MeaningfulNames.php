<?php

namespace App\Loyalty;

class CashbackCalculator
{
    private array $cart;
    private float $percent;

    private int $thresholdPrice;

    public function __construct(array $cart, float $percent = 0.05, int $thresholdPrice = 0)
    {
        $this->cart = $cart;
        $this->percent = $percent;
        $this->thresholdPrice = $thresholdPrice;
    }

    public function filterOrdersForCashback(): array
    {
        $filteredCart = [];

        foreach ($this->cart as $item) {
            if ($this->isDone($item['status']) && $this->reachesThresholdPrice($item['totalPrice']) && $this->lessThanMonthAgo($item['date'])) {
                $filteredCart[] = $item;
            }
        }

        return $filteredCart;
    }

    public function calculateCashback(array $orders): int
    {
        $totalBonus = 0;

        foreach ($orders as $item) {
            $totalBonus += (int)($item['totalPrice'] * $this->percent);
        }
        return $totalBonus;
    }

    private function isDone(string $status): bool
    {
        if ($status === 'Done') {
            return true;
        }

        return false;
    }

    private function reachesThresholdPrice(int $price): bool
    {
        if ($price > $this->thresholdPrice) {
            return true;
        }

        return false;
    }

    private function lessThanMonthAgo(string $date): bool
    {
        $orderDate = new \DateTimeImmutable($date);
        $currentDate = new \DateTimeImmutable();

        if ($orderDate->diff($currentDate)->days <= 30) {
            return true;
        }

        return false;
    }
}