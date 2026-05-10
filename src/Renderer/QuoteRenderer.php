<?php declare(strict_types=1);

namespace Act\QuoteDocument\Renderer;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Document\DocumentException;
use Shopware\Core\Checkout\Document\Renderer\AbstractDocumentRenderer;
use Shopware\Core\Checkout\Document\Renderer\DocumentRendererConfig;
use Shopware\Core\Checkout\Document\Renderer\OrderDocumentCriteriaFactory;
use Shopware\Core\Checkout\Document\Renderer\RenderedDocument;
use Shopware\Core\Checkout\Document\Renderer\RendererResult;
use Shopware\Core\Checkout\Document\Service\DocumentConfigLoader;
use Shopware\Core\Checkout\Document\Service\DocumentFileRendererRegistry;
use Shopware\Core\Checkout\Document\Struct\DocumentGenerateOperation;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\NumberRange\ValueGenerator\NumberRangeValueGeneratorInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class QuoteRenderer extends AbstractDocumentRenderer
{
    public const TYPE = 'quote';

    /**
     * @param EntityRepository<OrderCollection> $orderRepository
     */
    public function __construct(
        private readonly EntityRepository $orderRepository,
        private readonly DocumentConfigLoader $documentConfigLoader,
        private readonly NumberRangeValueGeneratorInterface $numberRangeValueGenerator,
        private readonly Connection $connection,
        private readonly DocumentFileRendererRegistry $fileRendererRegistry,
        private readonly SystemConfigService $systemConfigService,
    ) {
    }

    public function supports(): string
    {
        return self::TYPE;
    }

    public function render(array $operations, Context $context, DocumentRendererConfig $rendererConfig): RendererResult
    {
        $result = new RendererResult();

        $template = '@ActQuoteDocument/documents/quote.html.twig';

        $ids = \array_map(static fn (DocumentGenerateOperation $operation) => $operation->getOrderId(), $operations);

        if ($ids === []) {
            return $result;
        }

        $languageIdChain = $context->getLanguageIdChain();

        $chunk = $this->getOrdersLanguageId(array_values($ids), $context->getVersionId(), $this->connection);

        foreach ($chunk as ['language_id' => $languageId, 'ids' => $chunkIds]) {
            $criteria = OrderDocumentCriteriaFactory::create(\explode(',', (string) $chunkIds), $rendererConfig->deepLinkCode, self::TYPE);

            $context = $context->assign([
                'languageIdChain' => \array_values(\array_unique(\array_filter([$languageId, ...$languageIdChain]))),
            ]);

            $orders = $this->orderRepository->search($criteria, $context)->getEntities();

            foreach ($orders as $order) {
                $orderId = $order->getId();

                try {
                    if (!\array_key_exists($orderId, $operations)) {
                        continue;
                    }

                    /** @var DocumentGenerateOperation $operation */
                    $operation = $operations[$orderId];

                    $config = clone $this->documentConfigLoader->load(self::TYPE, $order->getSalesChannelId(), $context);

                    $config->merge($operation->getConfig());

                    $number = $config->getDocumentNumber() ?: $this->getNumber($context, $order, $operation);

                    $now = (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

                    $quoteDurationDays = $this->systemConfigService->getInt(
                        'ActQuoteDocument.config.quoteDurationDays',
                        $order->getSalesChannelId()
                    ) ?: 30;

                    $config->merge([
                        'documentDate' => $operation->getConfig()['documentDate'] ?? $now,
                        'documentNumber' => $number,
                        'custom' => [
                            'quoteNumber' => $number,
                            'quoteDurationDays' => $quoteDurationDays,
                        ],
                    ]);

                    // create version of order to ensure the document stays the same even if the order changes
                    $operation->setOrderVersionId($this->orderRepository->createVersion($orderId, $context, 'document'));

                    if ($operation->isStatic()) {
                        $doc = new RenderedDocument($number, $config->buildName(), $operation->getFileType(), $config->jsonSerialize());
                        $result->addSuccess($orderId, $doc);

                        continue;
                    }

                    if ($order->getLanguage() === null) {
                        throw DocumentException::generationError('Cannot generate quote document because no language is associated with the order. OrderId: ' . $operation->getOrderId());
                    }

                    $doc = new RenderedDocument(
                        $number,
                        $config->buildName(),
                        $operation->getFileType(),
                        $config->jsonSerialize(),
                    );

                    $doc->setTemplate($template);
                    $doc->setOrder($order);
                    $doc->setContext($context);

                    $doc->setContent($this->fileRendererRegistry->render($doc));

                    $result->addSuccess($orderId, $doc);
                } catch (\Throwable $exception) {
                    $result->addError($orderId, $exception);
                }
            }
        }

        return $result;
    }

    public function getDecorated(): AbstractDocumentRenderer
    {
        throw new DecorationPatternException(self::class);
    }

    private function getNumber(Context $context, OrderEntity $order, DocumentGenerateOperation $operation): string
    {
        return $this->numberRangeValueGenerator->getValue(
            'document_quote',
            $context,
            $order->getSalesChannelId(),
            $operation->isPreview()
        );
    }
}
