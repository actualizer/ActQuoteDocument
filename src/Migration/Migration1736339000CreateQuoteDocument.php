<?php declare(strict_types=1);

namespace Act\QuoteDocument\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1736339000CreateQuoteDocument extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1736339000;
    }

    public function update(Connection $connection): void
    {
        // Check if quote document type already exists
        $existingType = $connection->fetchOne(
            'SELECT id FROM document_type WHERE technical_name = :technicalName',
            ['technicalName' => 'quote']
        );
        
        if ($existingType) {
            // Document type already exists, skip migration
            return;
        }
        
        $documentTypeId = Uuid::randomBytes();
        $numberRangeId = Uuid::randomBytes();
        $numberRangeTypeId = Uuid::randomBytes();
        
        // Create number range type for quotes
        $connection->insert('number_range_type', [
            'id' => $numberRangeTypeId,
            'global' => 0,
            'technical_name' => 'document_quote',
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
        
        // Get German language ID
        $germanLanguageId = $connection->fetchOne(
            'SELECT language.id FROM language 
             INNER JOIN locale ON language.locale_id = locale.id 
             WHERE locale.code = :code',
            ['code' => 'de-DE']
        );
        
        // Get English language ID
        $englishLanguageId = $connection->fetchOne(
            'SELECT language.id FROM language 
             INNER JOIN locale ON language.locale_id = locale.id 
             WHERE locale.code = :code',
            ['code' => 'en-GB']
        );
        
        // Add German translation
        if ($germanLanguageId) {
            $connection->insert('number_range_type_translation', [
                'number_range_type_id' => $numberRangeTypeId,
                'language_id' => $germanLanguageId,
                'type_name' => 'Angebot',
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }
        
        // Add English translation
        if ($englishLanguageId) {
            $connection->insert('number_range_type_translation', [
                'number_range_type_id' => $numberRangeTypeId,
                'language_id' => $englishLanguageId,
                'type_name' => 'Quote',
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }
        
        // Create number range for quotes
        $connection->insert('number_range', [
            'id' => $numberRangeId,
            'type_id' => $numberRangeTypeId,
            'global' => 0,
            'pattern' => '{n}',
            'start' => 1000,
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
        
        // Add German translation for number range
        if ($germanLanguageId) {
            $connection->insert('number_range_translation', [
                'number_range_id' => $numberRangeId,
                'language_id' => $germanLanguageId,
                'name' => 'Angebote',
                'description' => 'Nummernkreis für Angebotsdokumente',
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }
        
        // Add English translation for number range
        if ($englishLanguageId) {
            $connection->insert('number_range_translation', [
                'number_range_id' => $numberRangeId,
                'language_id' => $englishLanguageId,
                'name' => 'Quotes',
                'description' => 'Number range for quote documents',
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }
        
        // Create document type
        $connection->insert('document_type', [
            'id' => $documentTypeId,
            'technical_name' => 'quote',
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
        
        // Add German translation for document type
        if ($germanLanguageId) {
            $connection->insert('document_type_translation', [
                'document_type_id' => $documentTypeId,
                'language_id' => $germanLanguageId,
                'name' => 'Angebot',
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }
        
        // Add English translation for document type
        if ($englishLanguageId) {
            $connection->insert('document_type_translation', [
                'document_type_id' => $documentTypeId,
                'language_id' => $englishLanguageId,
                'name' => 'Quote',
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }
        
        // Link document type to number range
        $documentBaseConfigId = Uuid::randomBytes();
        $connection->insert('document_base_config', [
            'id' => $documentBaseConfigId,
            'document_type_id' => $documentTypeId,
            'logo_id' => null,
            'name' => 'quote',
            'filename_prefix' => 'quote_',
            'filename_suffix' => '',
            'document_number' => null,
            'global' => 1,
            'config' => json_encode([
                'displayHeader' => true,
                'displayLineItems' => true,
                'displayPageCount' => true,
                'displayPrices' => true,
                'displayFooter' => true,
                'pageOrientation' => 'portrait',
                'pageSize' => 'a4',
                'itemsPerPage' => 10,
                'displayCompanyAddress' => true,
                'companyAddress' => '',
                'companyName' => '',
                'companyEmail' => '',
                'companyUrl' => '',
                'taxNumber' => '',
                'taxOffice' => '',
                'vatId' => '',
                'bankName' => '',
                'bankIban' => '',
                'bankBic' => '',
                'placeOfJurisdiction' => '',
                'placeOfFulfillment' => '',
                'executiveDirector' => '',
                'displayDivergentDeliveryAddress' => true,
                'displayAdditionalNoteDelivery' => true,
                'additionalNoteDelivery' => '',
                'displayLineItemPosition' => true,
                'displayPageNumber' => true,
                'displayPageCount' => true,
            ]),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ]);
        
        // Create sales channel assignment
        $salesChannels = $connection->fetchAllAssociative('SELECT id FROM sales_channel WHERE active = 1');
        
        foreach ($salesChannels as $salesChannel) {
            $connection->insert('document_base_config_sales_channel', [
                'id' => Uuid::randomBytes(),
                'document_base_config_id' => $documentBaseConfigId,
                'sales_channel_id' => $salesChannel['id'],
                'document_type_id' => $documentTypeId,
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
            
            // Assign number range to sales channel
            $connection->insert('number_range_sales_channel', [
                'id' => Uuid::randomBytes(),
                'number_range_id' => $numberRangeId,
                'sales_channel_id' => $salesChannel['id'],
                'number_range_type_id' => $numberRangeTypeId,
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]);
        }
    }

    
    public function updateDestructive(Connection $connection): void
    {
        // No destructive changes needed
    }
}