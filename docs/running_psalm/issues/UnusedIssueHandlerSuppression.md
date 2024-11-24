# UnusedIssueHandlerSuppression

Emitted when an issue type suppression in the configuration file is not being used to suppress an issue.

Enabled by [findUnusedIssueHandlerSuppression](../configuration.md#findunusedissuehandlersuppression) 

```php
<?php
$a = 'Hello, World!';
echo $a;
```
```xml
<?xml version="1.0" encoding="UTF-8"?>
<issueHandlers>
    <PossiblyNullOperand errorLevel="suppress"/>
</issueHandlers>
```
