<?php
namespace App\Subscription;

use DateTimeImmutable;
use Exception;

class Subscription
{
    private ?int $id = null;

    public function __construct(
        private readonly User $user,
        private DateTimeImmutable $expiresAt,
        private string $status = 'active', // 'active', 'paused', 'expired', 'canceled'
        private int $remainingPauseDays = 30
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function renew(int $days): void
    {
        $now = new DateTimeImmutable();

        if ($this->expiresAt < $now) {
            $newExpiresAt = $now->modify("+{$days} days");
        } else {
            $newExpiresAt = $this->expiresAt->modify("+{$days} days");
        }

        $this->expiresAt = $newExpiresAt;
        $this->status = 'active';
    }

    public function pause(int $pauseDays): void
    {
        if ($this->status !== 'active') {
            throw new Exception("Only active subscriptions can be paused");
        }

        if ($this->remainingPauseDays < $pauseDays) {
            throw new Exception("Not enough pause days remaining");
        }

        $this->remainingPauseDays -= $pauseDays;
        $this->expiresAt = $this->expiresAt->modify("+{$pauseDays} days");
        $this->status = 'paused';
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->expiresAt > new DateTimeImmutable();
    }
}