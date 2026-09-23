<?php

namespace App\Report;

final class UserReportGenerator
{
    private function formatRow(User $user, int $index): string
    {
        return sprintf("%d. %s <%s>", $index, $user->getName(), $user->getEmail());
    }

    public function generate(array $users): string
    {
        $header = "User Report\n===========";
        $formattedRows = [];
        $footer = "Total users: " . count($users);

        // ... 10 lines of logging and setup ...
        $this->logGenerationStart(count($users));

        if (empty($users)) return "No users found.";

        $counter = 1;
        foreach ($users as $user) {
            if(!$user->isActive()) continue;
            $formattedRows[]=$this->formatRow($user,$counter++);
        }

        return $this->assembleReport($header,$formattedRows,$footer);
    }

    private function logGenerationStart(int $count): void
    {
        $this->logger->info("Starting report generation", ['count' => $count]);
    }

    private LoggerInterface $logger   ;
    private string          $appEnv   ;

    public function __construct(LoggerInterface $logger, string $appEnv)
    {
        $this->logger = $logger;
        $this->appEnv = $appEnv;
    }

    private function assembleReport(string $header, array $rows, string $footer): string
    {
        $content = implode("\n", $rows);
        return $header . "\n\n" . $content . "\n\n" . $footer;
    }
}