<?php declare(strict_types=1);

namespace Act\QuoteDocument;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class ActQuoteDocument extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);
        
        // Keep data on uninstall by default
        if ($uninstallContext->keepUserData()) {
            return;
        }
        
        // Only delete data if user explicitly wants to remove all data
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);
        $this->removeQuoteDocumentData($connection);
    }
    
    private function removeQuoteDocumentData(Connection $connection): void
    {
        // Check if any quote documents exist
        $documentCount = $connection->fetchOne(
            'SELECT COUNT(*) FROM document 
             INNER JOIN document_type ON document.document_type_id = document_type.id 
             WHERE document_type.technical_name = :technicalName',
            ['technicalName' => 'quote']
        );
        
        if ($documentCount > 0) {
            // Documents exist, don't delete the configuration
            // Just log a warning or handle as needed
            return;
        }
        
        // Safe to delete configuration as no documents exist
        try {
            // Delete in correct order to respect foreign key constraints
            $connection->executeStatement("
                DELETE dbcsc FROM document_base_config_sales_channel dbcsc
                INNER JOIN document_type dt ON dbcsc.document_type_id = dt.id
                WHERE dt.technical_name = 'quote'
            ");
            
            $connection->executeStatement("
                DELETE dbc FROM document_base_config dbc
                INNER JOIN document_type dt ON dbc.document_type_id = dt.id
                WHERE dt.technical_name = 'quote'
            ");
            
            $connection->executeStatement("
                DELETE nrsc FROM number_range_sales_channel nrsc
                INNER JOIN number_range_type nrt ON nrsc.number_range_type_id = nrt.id
                WHERE nrt.technical_name = 'document_quote'
            ");
            
            $connection->executeStatement("DELETE FROM document_type_translation WHERE document_type_id IN (SELECT id FROM document_type WHERE technical_name = 'quote')");
            $connection->executeStatement("DELETE FROM document_type WHERE technical_name = 'quote'");
            
            $connection->executeStatement("DELETE FROM number_range_translation WHERE number_range_id IN (SELECT id FROM number_range WHERE type_id IN (SELECT id FROM number_range_type WHERE technical_name = 'document_quote'))");
            $connection->executeStatement("DELETE FROM number_range WHERE type_id IN (SELECT id FROM number_range_type WHERE technical_name = 'document_quote')");
            
            $connection->executeStatement("DELETE FROM number_range_type_translation WHERE number_range_type_id IN (SELECT id FROM number_range_type WHERE technical_name = 'document_quote')");
            $connection->executeStatement("DELETE FROM number_range_type WHERE technical_name = 'document_quote'");
        } catch (\Exception $e) {
            // Log error but don't fail the uninstall
        }
    }
}