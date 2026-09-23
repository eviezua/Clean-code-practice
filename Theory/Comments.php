<?php

namespace App\Subscription;

class SubscriptionManager
{
    private const ALLOWED_USER_AGE = 18;
    private const REMAINING_DAYS_MIN_TO_RENEW = 3;
    private const DEFAULT_MONTH_DAYS = 30;

    public function __construct(
        private Database $db,
        private Logger $logger
    ) {}

    public function renew(User $user, Subscription $sub): bool
    {
        if ($user->getStatus() !== 'Active') {
            return false;
        }

        if ($user->getAge() < self::ALLOWED_USER_AGE) {
            return false;
        }

        if ($sub->getIsActive() !== true) {
            return false;
        }

        if ($sub->getDaysRemaining() > self::REMAINING_DAYS_MIN_TO_RENEW) {
            return false;
        }

        if ($user->hasPaymentError()) {
            return false;
        }

        try {
            // INTENT: Retry logic is handled at the gateway level automatically
            $chargeResult = $this->db->charge($user, $sub->getAmount());

            if ($chargeResult === true) {
                $sub->extendDays(self::DEFAULT_MONTH_DAYS);
                $this->db->save($sub);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            $this->logger->logError("Failed: " . $e->getMessage());

            // TODO: Need to send SMS warning to user about payment failure

            return false;
        }
    }
}