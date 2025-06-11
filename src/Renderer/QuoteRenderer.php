<?php declare(strict_types=1);

namespace Act\QuoteDocument\Renderer;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Document\DocumentException;
use Shopware\Core\Checkout\Document\Renderer\AbstractDocumentRenderer;
use Shopware\Core\Checkout\Document\Renderer\DocumentRendererConfig;
use Shopware\Core\Checkout\Document\Renderer\RenderedDocument;
use Shopware\Core\Checkout\Document\Renderer\RendererResult;
use Shopware\Core\Checkout\Document\Service\DocumentConfigLoader;
use Shopware\Core\Checkout\Document\Struct\DocumentGenerateOperation;
use Shopware\Core\Checkout\Document\Twig\DocumentTemplateRenderer;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Feature;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\NumberRange\ValueGenerator\NumberRangeValueGeneratorInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class QuoteRenderer extends AbstractDocumentRenderer
{
    public const TYPE = 'quote';

    public function __construct(
        private readonly EntityRepository $orderRepository,
        private readonly DocumentConfigLoader $documentConfigLoader,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly DocumentTemplateRenderer $documentTemplateRenderer,
        private readonly NumberRangeValueGeneratorInterface $numberRangeValueGenerator,
        private readonly string $rootDir,
        private readonly Connection $connection,
        private readonly SystemConfigService $systemConfigService
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

        $ids = \array_map(fn (DocumentGenerateOperation $operation) => $operation->getOrderId(), $operations);

        if (empty($ids)) {
            return $result;
        }

        $languageIdChain = $context->getLanguageIdChain();

        $chunk = $this->getOrdersLanguageId(array_values($ids), $context->getVersionId(), $this->connection);

        foreach ($chunk as ['language_id' => $languageId, 'ids' => $orderIds]) {
            $criteria = new Criteria(\explode(',', (string) $orderIds));
            $criteria->addAssociation('lineItems.cover')
                ->addAssociation('addresses.country')
                ->addAssociation('deliveries.shippingMethod')
                ->addAssociation('deliveries.positions.orderLineItem')
                ->addAssociation('deliveries.shippingOrderAddress.country')
                ->addAssociation('cartPrice.calculatedTaxes')
                ->addAssociation('transactions.paymentMethod')
                ->addAssociation('currency')
                ->addAssociation('orderCustomer.customer')
                ->addAssociation('orderCustomer.salutation')
                ->addAssociation('language.locale');

            $newContext = $context->assign([
                'languageIdChain' => \array_values(\array_unique(\array_filter([$languageId, ...$languageIdChain]))),
            ]);

            /** @var OrderCollection $orders */
            $orders = $this->orderRepository->search($criteria, $newContext)->getEntities();

            foreach ($orders as $order) {
                $orderId = $order->getId();

                try {
                    if (!\array_key_exists($orderId, $operations)) {
                        continue;
                    }

                    /** @var DocumentGenerateOperation $operation */
                    $operation = $operations[$orderId];

                    $config = clone $this->documentConfigLoader->load(self::TYPE, $order->getSalesChannelId(), $newContext);

                    $config->merge($operation->getConfig());

                    $number = $config->getDocumentNumber() ?: $this->getNumber($newContext, $order, $operation);

                    $now = (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

                    // Get quote duration from plugin config
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
                    $operation->setOrderVersionId($this->orderRepository->createVersion($orderId, $newContext, 'document'));

                    if ($operation->isStatic()) {
                        $doc = new RenderedDocument('', $number, $config->buildName(), $operation->getFileType(), $config->jsonSerialize());
                        $result->addSuccess($orderId, $doc);
                        continue;
                    }

                    $locale = $order->getLanguage()->getLocale();

                    $html = '';
                    if (!Feature::isActive('v6.7.0.0')) {
                        $html = $this->documentTemplateRenderer->render(
                            $template,
                            [
                                'order' => $order,
                                'config' => $config,
                                'rootDir' => $this->rootDir,
                                'context' => $newContext,
                                'currencyIsoCode' => $order->getCurrency()->getIsoCode(),
                                'locale' => $locale->getCode(),
                            ],
                            $newContext,
                            $order->getSalesChannelId(),
                            $languageId,
                            $locale->getCode()
                        );
                    }

                    $doc = new RenderedDocument(
                        $html,
                        $number,
                        $config->buildName(),
                        $operation->getFileType(),
                        $config->jsonSerialize(),
                    );

                    // These methods may not exist in all Shopware versions
                    if (method_exists($doc, 'setTemplate')) {
                        $doc->setTemplate($template);
                    }
                    if (method_exists($doc, 'setOrder')) {
                        $doc->setOrder($order);
                    }
                    if (method_exists($doc, 'setContext')) {
                        $doc->setContext($newContext);
                    }

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
        );
    }

}