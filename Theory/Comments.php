<?php

namespace App\Subscription;

/**
 * Class SubscriptionManager
 * Package App\Subscription
 *
 * @author John Doe <john@example.com>
 * @version 1.0.0
 */
class SubscriptionManager
{
    /**
     * The database instance.
     */
    private Database $db;

    /**
     * Logger instance.
     */
    private Logger $logger;

    /**
     * SubscriptionManager constructor.
     *
     * @param Database $db
     * @param Logger $logger
     */
    public function __construct(Database $db, Logger $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Process subscription renewal
     *
     * @param User $user
     * @param Subscription $sub
     * @return bool
     */
    public function renew(User $user, Subscription $sub): bool
    {
        // Check if user is active, subscription is active, and payment method is valid
        if ($user->getStatus() === 1 && $user->getAge() >= 18 && $sub->getIsActive() === true && $sub->getDaysRemaining() <= 3 && !$user->hasPaymentError()) {

            // $this->legacyBillingSystem->chargeUser($user->getId(), $sub->getPrice());
            // $this->logger->info("Legacy billing charged user: " . $user->getId());

            try {
                // INTENT: Retry logic is handled at the gateway level automatically
                $chargeResult = $this->db->charge($user, $sub->getAmount());

                if ($chargeResult === true) {
                    // Update subscription end date
                    $sub->extendDays(30);
                    $this->db->save($sub);

                    // $user->sendRenewalEmail(); // Moved to event listener in v2

                    return true;
                } else {
                    return false;
                } // end if
            } catch (\Exception $e) {
                // Failed charge
                $this->logger->logError("Failed: " . $e->getMessage());

                // TODO: Need to send SMS warning to user about payment failure

                return false;
            } // end try
        } else {
            return false;
        } // end if
    } // end renew
}