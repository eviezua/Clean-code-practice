<?php
namespace App\Subscription;

use DateTimeImmutable;
use Exception;

class Subscription
{
    private string $id;
    private string $userId;
    private string $status; // 'active', 'paused', 'expired', 'canceled'
    private DateTimeImmutable $expiresAt;
    private int $remainingPauseDays = 30;

    public function getId(): string { return $this->id; }
    public function setId(string $id): void { $this->id = $id; }

    public function getUserId(): string { return $this->userId; }
    public function setUserId(string $userId): void { $this->userId = $userId; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }

    public function getExpiresAt(): DateTimeImmutable { return $this->expiresAt; }
    public function setExpiresAt(DateTimeImmutable $expiresAt): void { $this->expiresAt = $expiresAt; }

    public function getRemainingPauseDays(): int { return $this->remainingPauseDays; }
    public function setRemainingPauseDays(int $days): void { $this->remainingPauseDays = $days; }
}

class SubscriptionManager
{
    public function createSubscription(string $subscriptionId, string $userId, int $days): Subscription
    {
        $subscription = new Subscription();
        $subscription->setId($subscriptionId);
        $subscription->setUserId($userId);
        $subscription->setStatus('active');
        $subscription->setExpiresAt((new DateTimeImmutable())->modify("+{$days} days"));

        return $subscription;
    }

    public function renewSubscription(Subscription $subscription, int $days): void
    {
        $now = new DateTimeImmutable();

        if ($subscription->getExpiresAt() < $now) {
            $newExpiration = $now->modify("+{$days} days");
        } else {
            $newExpiration = $subscription->getExpiresAt()->modify("+{$days} days");
        }

        $subscription->setExpiresAt($newExpiration);
        $subscription->setStatus('active');
    }

    public function pauseSubscription(Subscription $subscription, int $pauseDays): void
    {
        if ($subscription->getStatus() !== 'active') {
            throw new Exception("Only active subscriptions can be paused");
        }

        if ($subscription->getRemainingPauseDays() < $pauseDays) {
            throw new Exception("Not enough pause days remaining");
        }

        $subscription->setRemainingPauseDays($subscription->getRemainingPauseDays() - $pauseDays);
        $subscription->setExpiresAt($subscription->getExpiresAt()->modify("+{$pauseDays} days"));
        $subscription->setStatus('paused');
    }

    public function isSubscriptionActive(Subscription $subscription): bool
    {
        return $subscription->getStatus() === 'active'
            && $subscription->getExpiresAt() > new DateTimeImmutable();
    }
}