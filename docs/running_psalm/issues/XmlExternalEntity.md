# XmlExternalEntity

This is a **security issue**: it flags a potential vulnerability.

Emitted when XML is parsed with `LIBXML_NOENT` or `LIBXML_DTDLOAD`, which make libxml load the external entities and DTD of the document: `simplexml_load_string()`, `simplexml_load_file()`, `new SimpleXMLElement()`, `DOMDocument::load()` and `loadXML()`, `Dom\XMLDocument::createFromString()` and `createFromFile()`.

```php
<?php

function parse(string $xml): void {
    simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NOENT); // XmlExternalEntity
    simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET); // no issue
}
```

## Why this is bad

An entity such as `<!ENTITY x SYSTEM "file:///etc/passwd">` then puts a local file in the document, or makes the server request a URL (XML external entity injection, XXE).

## How to fix

Don't pass these options: since libxml 2.9 external entities aren't loaded otherwise.

## Further resources

- [CWE-611](https://cwe.mitre.org/data/definitions/611.html)
