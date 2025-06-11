<?php declare(strict_types=1);

namespace Act\QuoteDocument\Document\DocumentGenerator;

use Shopware\Core\Checkout\Document\DocumentConfiguration;
use Shopware\Core\Checkout\Document\DocumentGenerator\InvoiceGenerator;
use Shopware\Core\Checkout\Document\Twig\DocumentTemplateRenderer;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

class QuoteGenerator extends InvoiceGenerator
{
    public const QUOTE = 'quote';

    public function supports(): string
    {
        return self::QUOTE;
    }

    public function getFileName(DocumentConfiguration $config): string
    {
        return $config->getFilenamePrefix() . $config->getDocumentNumber() . $config->getFilenameSuffix();
    }

    protected function getDocumentTemplate(): string
    {
        return '@ActQuoteDocument/documents/quote.html.twig';
    }

    public function generate(
        OrderEntity $order,
        DocumentConfiguration $config,
        Context $context,
        ?string $templatePath = null
    ): string {
        // Use our custom template
        $templatePath = $this->getDocumentTemplate();
        
        return parent::generate($order, $config, $context, $templatePath);
    }
}