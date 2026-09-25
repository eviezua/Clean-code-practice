<?php

namespace App\Report;

final class UserReportGenerator
{
    public function __construct(
        private LoggerInterface $logger,
        private string $appEnv
    ) {}

    public function generate(array $users): string
    {
        // ... 10 lines of logging and setup ...

        $totalUsers = count($users);

        $header = "User Report\n===========";
        $footer = "Total users: " . $totalUsers;

        if (empty($users)) {
            return "No users found.";
        }

        $this->logGenerationStart($totalUsers);

        $counter = 1;
        $formattedRows = [];

        foreach ($users as $user) {
            if (!$user->isActive()) {
                continue;
            }

            $formattedRows[] = $this->formatRow($user, $counter++);
        }

        return $this->assembleReport($header, $formattedRows, $footer);
    }

    private function logGenerationStart(int $count): void
    {
        $this->logger->info("Starting report generation", ['count' => $count]);
    }

    private function formatRow(User $user, int $index): string
    {
        return sprintf("%d. %s <%s>", $index, $user->getName(), $user->getEmail());
    }

    private function assembleReport(string $header, array $rows, string $footer): string
    {
        $content = implode("\n", $rows);

        return $header . "\n\n" . $content . "\n\n" . $footer;
    }
}