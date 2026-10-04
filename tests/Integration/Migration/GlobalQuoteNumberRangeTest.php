<?php declare(strict_types=1);

namespace Act\QuoteDocument\Tests\Integration\Migration;

use Act\QuoteDocument\Migration\Migration1791114328MakeQuoteNumberRangeGlobal;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\NumberRange\ValueGenerator\NumberRangeValueGeneratorInterface;

final class GlobalQuoteNumberRangeTest extends TestCase
{
    use IntegrationTestBehaviour;
    use KernelTestBehaviour;

    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = static::getContainer()->get(Connection::class);
        $this->connection->executeStatement(
            'UPDATE number_range SET `global` = 0 WHERE type_id = :typeId',
            ['typeId' => $this->quoteTypeId()]
        );
    }

    public function testSalesChannelWithoutAssignmentGetsAQuoteNumber(): void
    {
        (new Migration1791114328MakeQuoteNumberRangeGlobal())->update($this->connection);

        $number = static::getContainer()->get(NumberRangeValueGeneratorInterface::class)
            ->getValue('document_quote', Context::createDefaultContext(), Uuid::randomHex(), true);

        self::assertSame('1000', $number);
    }

    public function testExistingGlobalRangeIsKept(): void
    {
        $otherRangeId = Uuid::randomBytes();
        $this->connection->insert('number_range', [
            'id' => $otherRangeId,
            'type_id' => $this->quoteTypeId(),
            '`global`' => 1,
            'pattern' => 'Q{n}',
            'start' => 1,
            'created_at' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);

        (new Migration1791114328MakeQuoteNumberRangeGlobal())->update($this->connection);

        self::assertSame(
            [Uuid::fromBytesToHex($otherRangeId)],
            $this->connection->fetchFirstColumn(
                'SELECT LOWER(HEX(id)) FROM number_range WHERE type_id = :typeId AND `global` = 1',
                ['typeId' => $this->quoteTypeId()]
            )
        );
    }

    private function quoteTypeId(): string
    {
        return (string) $this->connection->fetchOne(
            "SELECT id FROM number_range_type WHERE technical_name = 'document_quote'"
        );
    }
}
