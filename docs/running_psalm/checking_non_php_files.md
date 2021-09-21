# Checking non-PHP files

Psalm supports the ability to check various PHPish files by extending the `FileChecker` class. For example, if you have a template where the variables are set elsewhere, Psalm can scrape those variables and check the template with those variables pre-populated.

An example TemplateChecker is provided [here](https://github.com/vimeo/psalm/blob/master/examples/TemplateChecker.php).

## Using `psalm.xml`

To ensure your custom `FileChecker` is used, you must update the Psalm `fileExtensions` config in psalm.xml:
```xml
<fileExtensions>
    <extension name=".php" />
    <extension name=".phpt" checker="path/to/TemplateChecker.php" />
</fileExtensions>
```

## Using custom plugin

Plugins can register their own custom scanner  and analyzer implementations for particular file extensions.

```php
<?php
namespace Psalm\Example;

use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;

class CustomPlugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, ?\SimpleXMLElement $config = null): void
    {
        $registration->addFileTypeScanner('phpt', TemplateScanner::class);
        $registration->addFileTypeAnalyzer('phpt', TemplateAnalyzer::class);
    }
}
```
