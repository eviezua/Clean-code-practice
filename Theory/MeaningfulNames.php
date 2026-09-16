<?php

namespace App\Loyalty;

class ProcessData
{
    private array $lst; //user list

    public function __construct(array $lst)
    {
        $this->lst = $lst;
    }

    public function getIt(): array
    {
        $resList = [];

        foreach ($this->lst as $item) {
            // if status done and sum greater than 100
            if ($item[1] === 5 && $item[2] > 100) {
                $dt = new \DateTimeImmutable($item[3]);
                $now = new \DateTimeImmutable();

                // Less than 30 days ago
                $diffInDays = $now->diff($dt)->days;

                if ($diffInDays <= 30) {
                    $resList[] = $item;
                }
            }
        }

        return $resList;
    }

    public function calcPnts(array $dataInfo): int
    {
        $p = 0;
        foreach ($dataInfo as $subItem) {
            $p += (int)($subItem[2] * 0.05);
        }
        return $p;
    }
}