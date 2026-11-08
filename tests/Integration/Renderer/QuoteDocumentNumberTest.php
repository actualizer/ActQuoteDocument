<?php declare(strict_types=1);

namespace Act\QuoteDocument\Tests\Integration\Renderer;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Document\Service\DocumentGenerator;
use Shopware\Core\Checkout\Document\Service\HtmlRenderer;
use Shopware\Core\Checkout\Document\Struct\DocumentGenerateOperation;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Integration\Traits\OrderFixture;

/**
 * A document number entered when the quote is created is used as given.
 */
final class QuoteDocumentNumberTest extends TestCase
{
    use IntegrationTestBehaviour;
    use KernelTestBehaviour;
    use OrderFixture;

    public function testGivenDocumentNumberIsKept(): void
    {
        self::assertSame('A-100', $this->renderQuoteNumber('A-100'));
    }

    public function testGivenDocumentNumberZeroIsKept(): void
    {
        self::assertSame('0', $this->renderQuoteNumber('0'));
    }

    private function renderQuoteNumber(string $documentNumber): string
    {
        $context = Context::createDefaultContext();
        $orderId = Uuid::randomHex();
        static::getContainer()->get('order.repository')->create($this->getOrderData($orderId, $context), $context);

        $document = static::getContainer()->get(DocumentGenerator::class)->preview(
            'quote',
            new DocumentGenerateOperation(
                $orderId,
                HtmlRenderer::FILE_EXTENSION,
                ['documentNumber' => $documentNumber, 'fileTypes' => [HtmlRenderer::FILE_EXTENSION]],
                null,
                false,
                true
            ),
            '',
            $context
        );

        return $document->getNumber();
    }
}
