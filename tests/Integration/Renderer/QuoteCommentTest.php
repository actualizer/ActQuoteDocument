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
 * A comment entered when the quote is created is printed on the quote.
 */
final class QuoteCommentTest extends TestCase
{
    use IntegrationTestBehaviour;
    use KernelTestBehaviour;
    use OrderFixture;

    public function testDocumentCommentIsPrinted(): void
    {
        $html = $this->renderQuote(['documentComment' => 'Lieferung erst ab KW 48 möglich']);

        self::assertStringContainsString('document-comment-container"', $html);
        self::assertStringContainsString('Lieferung erst ab KW 48 möglich', $html);
    }

    public function testQuoteWithoutCommentHasNoCommentBlock(): void
    {
        $html = $this->renderQuote([]);

        self::assertStringContainsString('class="headline"', $html);
        self::assertStringNotContainsString('document-comment-container"', $html);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function renderQuote(array $config): string
    {
        $context = Context::createDefaultContext();
        $orderId = Uuid::randomHex();
        static::getContainer()->get('order.repository')->create($this->getOrderData($orderId, $context), $context);

        $document = static::getContainer()->get(DocumentGenerator::class)->preview(
            'quote',
            new DocumentGenerateOperation(
                $orderId,
                HtmlRenderer::FILE_EXTENSION,
                [...$config, 'fileTypes' => [HtmlRenderer::FILE_EXTENSION]],
                null,
                false,
                true
            ),
            '',
            $context
        );

        return $document->getContent();
    }
}
