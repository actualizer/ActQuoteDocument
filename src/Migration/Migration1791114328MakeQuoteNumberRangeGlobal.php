<?php declare(strict_types=1);

namespace Act\QuoteDocument\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1791114328MakeQuoteNumberRangeGlobal extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1791114328;
    }

    public function update(Connection $connection): void
    {
        $typeId = $connection->fetchOne(
            'SELECT id FROM number_range_type WHERE technical_name = :name',
            ['name' => 'document_quote']
        );
        if (!\is_string($typeId)) {
            return;
        }

        $hasGlobal = $connection->fetchOne(
            'SELECT 1 FROM number_range WHERE type_id = :typeId AND `global` = 1',
            ['typeId' => $typeId]
        );
        if ($hasGlobal !== false) {
            return;
        }

        // Sales channels without an own assignment fall back to the global range.
        $connection->executeStatement(
            'UPDATE number_range SET `global` = 1 WHERE type_id = :typeId ORDER BY created_at ASC LIMIT 1',
            ['typeId' => $typeId]
        );
    }
}
