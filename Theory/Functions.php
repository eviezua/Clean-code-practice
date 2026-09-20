<?php
namespace App\Dto;

readonly class User
{
    public function __construct(
        public string $email,
        public string $firstName,
        public string $lastName,
        public string $type
    )
    {}

    public function getUserFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }
}
?>

<?php
namespace App\Rates;

interface RateInterface
{
    public function getName(): string;

    public function getPayoutAmount(): float;

}
?>

<?php
namespace App\Rates;

use App\Dto\User;

class RateFactory
{
    public static function createForUser(User $user, float $baseRate, int $unitsCount): RateInterface
    {
        return match (strtolower($user->type)) {
            'hourly' => new Hourly($baseRate, $unitsCount),
            'fixed' => new Fixed($baseRate),
            'royalty' => new Royalty($baseRate, $unitsCount),
            default => throw new \InvalidArgumentException("Unknown user type: {$user->type}"),
        };
    }
}
?>

<?php
namespace App\Rates;

class Hourly implements RateInterface
{
    private const STANDARD_WORKING_HOURS_MONTHLY = 160;
    private const OVERTIME_RATE_FOR_UNIT = 0.5;

    public function __construct(
        private float $base,
        private int $unitsCount
    ) {}

    public function getName(): string
    {
        return strtolower(get_class($this));
    }

    public function getPayoutAmount(): float
    {
        $payoutAmount = $this->unitsCount * $this->base;

        if ($this->unitsCount > self::STANDARD_WORKING_HOURS_MONTHLY) {
            $payoutAmount += ($this->unitsCount - self::STANDARD_WORKING_HOURS_MONTHLY) * ($this->base * self::OVERTIME_RATE_FOR_UNIT);
        }

        return $payoutAmount;
    }

}
?>

<?php
namespace App\Rates;

class Fixed implements RateInterface
{
    public function __construct(
        protected float $base
    ){}

    public function getName(): string
    {
        return strtolower(get_class($this));
    }

    public function getPayoutAmount(): float
    {
        return $this->base;
    }

}
?>

<?php
namespace App\Rates;

class Royalty implements RateInterface
{
    private const OVERTIME_RATE_FOR_UNIT = 0.15;

    public function __construct(
        protected float $base,
        protected int $unitsCount
    ){}

    public function getName(): string
    {
        return strtolower(get_class($this));
    }

    public function getPayoutAmount(): float
    {
        return $this->base + ($this->unitsCount * self::OVERTIME_RATE_FOR_UNIT);
    }

}
?>

<?php
namespace App\Payout;

use App\Dto\User;
use App\Rates\RateFactory;

class ReportHandler
{
    public function __construct(
        private User $user,
        private float $baseRate,
        private int $unitsCount,
        private bool $isPriorityPayout,
        private bool $sendNotificationEmail,
    ){}

    public function process(): string
    {
        $rate = RateFactory::createForUser($this->user, $this->baseRate, $this->unitsCount);

        $reportBuffer = "=== PAYOUT REPORT ===\n";
        $reportBuffer .= "Author: " . $this->user->getUserFullName() . "\n";
        $reportBuffer .= "Type: " . $this->user->type . "\n";
        $reportBuffer .= "Amount: $" . number_format($rate->getPayoutAmount(), 2) . "\n";

        if ($this->sendNotificationEmail) {
            $reportBuffer .= $this->getStatus() . "\n";
        }

        $reportBuffer .= "=====================\n";

        return $reportBuffer;
    }

    public function getStatus(): string
    {
        if ($this->isPriorityPayout) {
            return "Status: PRIORITY_SENT";
        }
        else {
            return "Status: STANDARD_SENT";
        }
    }

    public function handle(): void
    {
        // ...
    }
}