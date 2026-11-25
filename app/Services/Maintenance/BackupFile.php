<?php

namespace App\Services\Maintenance;

use Carbon\CarbonImmutable;

/** One backup file in storage/app/private/backups. */
final class BackupFile
{
    public function __construct(
        public readonly string $name,
        public readonly CarbonImmutable $date,
        public readonly string $by,
        public readonly int $size,
        public readonly bool $auto,
    ) {}
}
