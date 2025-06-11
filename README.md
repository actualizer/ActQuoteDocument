# ActQuoteDocument - Shopware 6 Quote Document Plugin

This plugin adds a new document type "Quote" (German: "Angebot") to your Shopware 6 installation, complete with its own number range and customizable validity period.

## Features

- ✅ **New Document Type**: Adds "Quote" as a fully integrated document type
- ✅ **Dedicated Number Range**: Separate number sequence for quotes (starting at 1000)
- ✅ **Configurable Validity**: Set quote validity period via plugin configuration
- ✅ **Multi-language Support**: Full support for German and English
- ✅ **Customizable Text**: All texts manageable via Shopware's text module system
- ✅ **Professional Templates**: Based on Shopware's standard document templates
- ✅ **Safe Installation/Uninstallation**: No data loss, existing documents are protected

## Requirements

- Shopware 6.6.0 or higher
- PHP 8.3 or higher

## Installation

### Manual Installation

1. Copy the plugin folder to `custom/plugins/ActQuoteDocument`
2. Run the following commands from your Shopware root directory:
```bash
bin/console plugin:refresh
bin/console plugin:install --activate ActQuoteDocument
bin/console cache:clear
```

### Installation via Git (for development)

```bash
cd custom/plugins
git clone [repository-url] ActQuoteDocument
cd ../..
bin/console plugin:refresh
bin/console plugin:install --activate ActQuoteDocument
bin/console cache:clear
```

## Configuration

### Plugin Settings

Navigate to **Extensions → My Extensions → ActQuoteDocument** in your admin panel:

- **Quote validity in days**: Set how many days a quote should be valid (default: 30 days)

### Number Range

The plugin automatically creates a number range for quotes:
- Navigate to **Settings → Shop → Number ranges**
- Look for "Quotes" / "Angebote"
- Default pattern: Sequential numbering starting at 1000
- Can be customized per sales channel

### Text Customization

All texts can be customized via Shopware's text module system:
- Navigate to **Settings → Shop → Text modules**
- Search for keys starting with `act_quote_`
- Available text keys:
  - `act_quote_title`: Document title
  - `act_quote_headline`: Document headline
  - `act_quote_validUntil`: Validity text

## Usage

### Creating a Quote

1. Navigate to **Orders → Orders**
2. Select an order
3. Click on "Documents" tab
4. Click "Create document"
5. Select "Quote" as document type
6. Generate the document

### Customizing the Template

The quote template can be customized by overriding it in your theme:

```
themes/YourTheme/views/documents/quote.html.twig
```

The template extends Shopware's base document template and inherits all standard features like:
- Company information
- Customer addresses
- Line items with prices
- Tax calculations
- Payment and shipping information

## Technical Details

### File Structure

```
ActQuoteDocument/
├── composer.json
├── README.md
├── src/
│   ├── ActQuoteDocument.php
│   ├── Migration/
│   │   └── Migration1736339000CreateQuoteDocument.php
│   ├── Renderer/
│   │   └── QuoteRenderer.php
│   └── Resources/
│       ├── config/
│       │   ├── config.xml
│       │   └── services.xml
│       ├── snippet/
│       │   ├── de_DE/
│       │   │   └── documents.de-DE.json
│       │   └── en_GB/
│       │       └── documents.en-GB.json
│       └── views/
│           └── documents/
│               └── quote.html.twig
```

### Database Tables Affected

The plugin creates entries in the following tables:
- `document_type`: Quote document type
- `document_type_translation`: Translations for the document type
- `number_range_type`: Number range type for quotes
- `number_range_type_translation`: Translations
- `number_range`: The actual number range configuration
- `number_range_translation`: Translations
- `document_base_config`: Document configuration
- `document_base_config_sales_channel`: Sales channel assignments
- `number_range_sales_channel`: Number range sales channel assignments

### Safety Features

- **Installation Safety**: Checks if quote document type already exists before creating
- **Uninstallation Safety**: 
  - Keeps data by default when uninstalling
  - Checks for existing quote documents before removing configuration
  - Never deletes actual quote documents
- **Unique Identifiers**: Uses `act_quote_` prefix for all text keys to avoid conflicts

## Development

### Extending the Plugin

The plugin follows Shopware 6 best practices and can be extended:

1. **Custom Fields**: Add custom fields to the quote document
2. **Event Subscribers**: Hook into the document generation process
3. **Template Extensions**: Override blocks in the quote template
4. **Additional Configuration**: Add more configuration options

## Troubleshooting

### Quote documents not showing

1. Clear the cache: `bin/console cache:clear`
2. Check if the plugin is activated
3. Verify number range assignment in admin

### Wrong language displayed

Ensure your admin user has the correct language selected and that the locale is properly configured in Shopware.

### Custom styling not applied

When modifying templates, remember to:
1. Clear the cache
2. Compile the theme if using custom CSS

## Support

For issues and feature requests, please use the GitHub issue tracker.

## License

This plugin is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Credits

Developed by Actualize

---

Made with ❤️ for the Shopware Community