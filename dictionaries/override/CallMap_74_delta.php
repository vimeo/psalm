<?php // phpcs:ignoreFile

return array (
  'added' => 
  array (
    'mb_str_split' => 
    array (
      0 => 'false|list<string>',
      'str' => 'string',
      'split_length=' => 'int<1, max>',
      'encoding=' => 'string',
    ),
    'openssl_x509_verify' => 
    array (
      0 => 'int',
      'cert' => 'resource|string',
      'key' => 'array<array-key, mixed>|resource|string',
    ),
    'reflectionproperty::gettype' => 
    array (
      0 => 'ReflectionType|null',
    ),
    'reflectionproperty::isinitialized' => 
    array (
      0 => 'bool',
      'object=' => 'object',
    ),
  ),
  'changed' => 
  array (
    'amqpbasicproperties::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'contentType=' => 'null|string',
        'contentEncoding=' => 'null|string',
        'headers=' => 'array<array-key, mixed>',
        'deliveryMode=' => 'int',
        'priority=' => 'int',
        'correlationId=' => 'null|string',
        'replyTo=' => 'null|string',
        'expiration=' => 'null|string',
        'messageId=' => 'null|string',
        'timestamp=' => 'int|null',
        'type=' => 'null|string',
        'userId=' => 'null|string',
        'appId=' => 'null|string',
        'clusterId=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'void',
        'contentType=' => 'string',
        'contentEncoding=' => 'string',
        'headers=' => 'array<array-key, mixed>',
        'deliveryMode=' => 'int',
        'priority=' => 'int',
        'correlationId=' => 'string',
        'replyTo=' => 'string',
        'expiration=' => 'string',
        'messageId=' => 'string',
        'timestamp=' => 'int',
        'type=' => 'string',
        'userId=' => 'string',
        'appId=' => 'string',
        'clusterId=' => 'string',
      ),
    ),
    'amqpchannel::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'amqp_connection' => 'AMQPConnection',
      ),
      'new' => 
      array (
        0 => 'void',
        'connection' => 'AMQPConnection',
      ),
    ),
    'amqpchannel::qos' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'size' => 'int',
        'count' => 'int',
        'global=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'size' => 'int',
        'count' => 'int',
        'global=' => 'bool',
      ),
    ),
    'amqpchannel::setconfirmcallback' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'ack_callback' => 'callable|null',
        'nack_callback=' => 'callable|null',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'ackCallback' => 'callable|null',
        'nackCallback=' => 'callable|null',
      ),
    ),
    'amqpchannel::setreturncallback' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'return_callback' => 'callable|null',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'returnCallback' => 'callable|null',
      ),
    ),
    'amqpenvelope::getheader' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'name' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'headerName' => 'string',
      ),
    ),
    'amqpenvelope::hasheader' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'name' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'headerName' => 'string',
      ),
    ),
    'amqpexchange::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'amqp_channel' => 'AMQPChannel',
      ),
      'new' => 
      array (
        0 => 'void',
        'channel' => 'AMQPChannel',
      ),
    ),
    'amqpexchange::bind' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'exchange_name' => 'string',
        'routing_key' => 'string',
        'flags=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'exchangeName' => 'string',
        'routingKey=' => 'string',
        'arguments=' => 'array<array-key, mixed>',
      ),
    ),
    'amqpexchange::delete' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'exchange_name=' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'exchangeName=' => 'string',
        'flags=' => 'int',
      ),
    ),
    'amqpexchange::getargument' => 
    array (
      'old' => 
      array (
        0 => 'false|int|string',
        'argument' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int|string',
        'argumentName' => 'string',
      ),
    ),
    'amqpexchange::hasargument' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'argument' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'argumentName' => 'string',
      ),
    ),
    'amqpexchange::publish' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'message' => 'string',
        'routing_key=' => 'string',
        'flags=' => 'int',
        'headers=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'message' => 'string',
        'routingKey=' => 'string',
        'flags=' => 'int',
        'headers=' => 'array<array-key, mixed>',
      ),
    ),
    'amqpexchange::setargument' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'value' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'argumentName' => 'string',
        'argumentValue' => 'int|string',
      ),
    ),
    'amqpexchange::setname' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'exchange_name' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'exchangeName' => 'string',
      ),
    ),
    'amqpexchange::settype' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'exchange_type' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'exchangeType' => 'string',
      ),
    ),
    'amqpexchange::unbind' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'exchange_name' => 'string',
        'routing_key' => 'string',
        'flags=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'exchangeName' => 'string',
        'routingKey=' => 'string',
        'arguments=' => 'array<array-key, mixed>',
      ),
    ),
    'amqpqueue::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'amqp_channel' => 'AMQPChannel',
      ),
      'new' => 
      array (
        0 => 'void',
        'channel' => 'AMQPChannel',
      ),
    ),
    'amqpqueue::ack' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'delivery_tag' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'deliveryTag' => 'string',
        'flags=' => 'int',
      ),
    ),
    'amqpqueue::bind' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'exchange_name' => 'string',
        'routing_key=' => 'string',
        'arguments=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'exchangeName' => 'string',
        'routingKey=' => 'string',
        'arguments=' => 'array<array-key, mixed>',
      ),
    ),
    'amqpqueue::cancel' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'consumer_tag=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'consumerTag=' => 'string',
      ),
    ),
    'amqpqueue::consume' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'callback' => 'callable|null',
        'flags=' => 'int',
        'consumer_tag=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'callback=' => 'callable|null',
        'flags=' => 'int',
        'consumerTag=' => 'string',
      ),
    ),
    'amqpqueue::getargument' => 
    array (
      'old' => 
      array (
        0 => 'false|int|string',
        'argument' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int|string',
        'argumentName' => 'string',
      ),
    ),
    'amqpqueue::hasargument' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'argumentName' => 'string',
      ),
    ),
    'amqpqueue::nack' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'delivery_tag' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'deliveryTag' => 'string',
        'flags=' => 'int',
      ),
    ),
    'amqpqueue::reject' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'delivery_tag' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'deliveryTag' => 'string',
        'flags=' => 'int',
      ),
    ),
    'amqpqueue::setargument' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'value' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'argumentName' => 'string',
        'argumentValue' => 'mixed',
      ),
    ),
    'amqpqueue::setname' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'queue_name' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'name' => 'string',
      ),
    ),
    'amqpqueue::unbind' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'exchange_name' => 'string',
        'routing_key=' => 'string',
        'arguments=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'exchangeName' => 'string',
        'routingKey=' => 'string',
        'arguments=' => 'array<array-key, mixed>',
      ),
    ),
    'amqptimestamp::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'timestamp=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'timestamp' => 'string',
      ),
    ),
    'array_merge' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'arr1' => 'array<array-key, mixed>',
        '...arrays=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        '...arrays=' => 'array<array-key, mixed>',
      ),
    ),
    'array_merge_recursive' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'arr1' => 'array<array-key, mixed>',
        '...arrays=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        '...arrays=' => 'array<array-key, mixed>',
      ),
    ),
    'arrayiterator::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'array=' => 'array<array-key, mixed>|object',
        'ar_flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'array=' => 'array<array-key, mixed>|object',
        'flags=' => 'int',
      ),
    ),
    'arrayobject::exchangearray' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
        'array' => 'array<array-key, mixed>|object',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
        'input' => 'array<array-key, mixed>|object',
      ),
    ),
    'domdocument::createprocessinginstruction' => 
    array (
      'old' => 
      array (
        0 => 'DOMProcessingInstruction|false',
        'target' => 'string',
        'data' => 'string',
      ),
      'new' => 
      array (
        0 => 'DOMProcessingInstruction|false',
        'target' => 'string',
        'data=' => 'string',
      ),
    ),
    'domdocument::importnode' => 
    array (
      'old' => 
      array (
        0 => 'DOMNode|false',
        'importedNode' => 'DOMNode',
        'deep' => 'bool',
      ),
      'new' => 
      array (
        0 => 'DOMNode|false',
        'importedNode' => 'DOMNode',
        'deep=' => 'bool',
      ),
    ),
    'domimplementation::createdocument' => 
    array (
      'old' => 
      array (
        0 => 'DOMDocument|false',
        'namespaceURI' => 'string',
        'qualifiedName' => 'string',
        'docType' => 'DOMDocumentType',
      ),
      'new' => 
      array (
        0 => 'DOMDocument|false',
        'namespaceURI' => 'string',
        'qualifiedName' => 'string',
        'docType=' => 'DOMDocumentType',
      ),
    ),
    'gzread' => 
    array (
      'old' => 
      array (
        0 => '0|string',
        'fp' => 'resource',
        'length' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'fp' => 'resource',
        'length' => 'int',
      ),
    ),
    'imagecopymerge' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'src_im' => 'resource',
        'dst_im' => 'resource',
        'dst_x' => 'int',
        'dst_y' => 'int',
        'src_x' => 'int',
        'src_y' => 'int',
        'src_w' => 'int',
        'src_h' => 'int',
        'pct' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'dst_im' => 'resource',
        'src_im' => 'resource',
        'dst_x' => 'int',
        'dst_y' => 'int',
        'src_x' => 'int',
        'src_y' => 'int',
        'src_w' => 'int',
        'src_h' => 'int',
        'pct' => 'int',
      ),
    ),
    'imagecopymergegray' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'src_im' => 'resource',
        'dst_im' => 'resource',
        'dst_x' => 'int',
        'dst_y' => 'int',
        'src_x' => 'int',
        'src_y' => 'int',
        'src_w' => 'int',
        'src_h' => 'int',
        'pct' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'dst_im' => 'resource',
        'src_im' => 'resource',
        'dst_x' => 'int',
        'dst_y' => 'int',
        'src_x' => 'int',
        'src_y' => 'int',
        'src_w' => 'int',
        'src_h' => 'int',
        'pct' => 'int',
      ),
    ),
    'locale::lookup' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'langtag' => 'array<array-key, mixed>',
        'locale' => 'string',
        'canonicalize=' => 'bool',
        'default=' => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'langtag' => 'array<array-key, mixed>',
        'locale' => 'string',
        'canonicalize=' => 'bool',
        'default=' => 'null|string',
      ),
    ),
    'locale_lookup' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'langtag' => 'array<array-key, mixed>',
        'locale' => 'string',
        'canonicalize=' => 'bool',
        'def=' => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'langtag' => 'array<array-key, mixed>',
        'locale' => 'string',
        'canonicalize=' => 'bool',
        'def=' => 'null|string',
      ),
    ),
    'mongodb\\driver\\cursor::getid' => 
    array (
      'old' => 
      array (
        0 => 'MongoDB\\Driver\\CursorId',
      ),
      'new' => 
      array (
        0 => 'MongoDB\\Driver\\CursorId',
        'asInt64=' => 'bool',
      ),
    ),
    'openssl_random_pseudo_bytes' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'length' => 'int',
        '&w_result_is_strong=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'string',
        'length' => 'int',
        '&w_result_is_strong=' => 'bool',
      ),
    ),
    'pack' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'format' => 'string',
        '...args' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'format' => 'string',
        '...args=' => 'mixed',
      ),
    ),
    'password_hash' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'password' => 'string',
        'algo' => 'int',
        'options=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'password' => 'string',
        'algo' => 'int|null|string',
        'options=' => 'array<array-key, mixed>',
      ),
    ),
    'password_needs_rehash' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'hash' => 'string',
        'algo' => 'int',
        'options=' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'bool',
        'hash' => 'string',
        'algo' => 'int|null|string',
        'options=' => 'array<array-key, mixed>',
      ),
    ),
    'preg_replace_callback' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'regex' => 'array<array-key, mixed>|string',
        'callback' => 'callable(array<array-key, string>):string',
        'subject' => 'string',
        'limit=' => 'int',
        '&w_count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'regex' => 'array<array-key, mixed>|string',
        'callback' => 'callable(array<array-key, string>):string',
        'subject' => 'string',
        'limit=' => 'int',
        '&w_count=' => 'int',
        'flags=' => 'int',
      ),
    ),
    'preg_replace_callback\'1' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, string>|null',
        'pattern' => 'array<array-key, mixed>|string',
        'callback' => 'callable(array<array-key, string>):string',
        'subject' => 'array<array-key, string>',
        'limit=' => 'int',
        '&w_count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, string>|null',
        'pattern' => 'array<array-key, mixed>|string',
        'callback' => 'callable(array<array-key, string>):string',
        'subject' => 'array<array-key, string>',
        'limit=' => 'int',
        '&w_count=' => 'int',
        'flags=' => 'int',
      ),
    ),
    'preg_replace_callback_array' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'pattern' => 'array<string, callable(array<array-key, mixed>):string>',
        'subject' => 'string',
        'limit=' => 'int',
        '&w_count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'pattern' => 'array<string, callable(array<array-key, mixed>):string>',
        'subject' => 'string',
        'limit=' => 'int',
        '&w_count=' => 'int',
        'flags=' => 'int',
      ),
    ),
    'preg_replace_callback_array\'1' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, string>|null',
        'pattern' => 'array<string, callable(array<array-key, mixed>):string>',
        'subject' => 'array<array-key, string>',
        'limit=' => 'int',
        '&w_count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, string>|null',
        'pattern' => 'array<string, callable(array<array-key, mixed>):string>',
        'subject' => 'array<array-key, string>',
        'limit=' => 'int',
        '&w_count=' => 'int',
        'flags=' => 'int',
      ),
    ),
    'proc_open' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'command' => 'string',
        'descriptorspec' => 'array<array-key, mixed>',
        '&pipes' => 'array<array-key, resource>',
        'cwd=' => 'null|string',
        'env=' => 'array<array-key, mixed>|null',
        'other_options=' => 'array<array-key, mixed>|null',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'command' => 'array<array-key, mixed>|string',
        'descriptorspec' => 'array<array-key, mixed>',
        '&pipes' => 'array<array-key, resource>',
        'cwd=' => 'null|string',
        'env=' => 'array<array-key, mixed>|null',
        'other_options=' => 'array<array-key, mixed>|null',
      ),
    ),
    'recursivearrayiterator::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'array=' => 'array<array-key, mixed>|object',
        'ar_flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'array=' => 'array<array-key, mixed>|object',
        'flags=' => 'int',
      ),
    ),
    'redis::hset' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        'member' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'key' => 'string',
        '...fields_and_vals=' => 'string',
      ),
    ),
    'reflectionmethod::getclosure' => 
    array (
      'old' => 
      array (
        0 => 'Closure|null',
        'object' => 'object',
      ),
      'new' => 
      array (
        0 => 'Closure|null',
        'object=' => 'object',
      ),
    ),
    'spldoublylinkedlist::setiteratormode' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'flags' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'mode' => 'int',
      ),
    ),
    'splfileobject::fwrite' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'str' => 'string',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'str' => 'string',
        'length=' => 'int',
      ),
    ),
    'splfixedarray::fromarray' => 
    array (
      'old' => 
      array (
        0 => 'SplFixedArray',
        'data' => 'array<array-key, mixed>',
        'save_indexes=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'SplFixedArray',
        'array' => 'array<array-key, mixed>',
        'save_indexes=' => 'bool',
      ),
    ),
    'splmaxheap::compare' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'a' => 'mixed',
        'b' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'int',
        'value1' => 'mixed',
        'value2' => 'mixed',
      ),
    ),
    'splminheap::compare' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'a' => 'mixed',
        'b' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'int',
        'value1' => 'mixed',
        'value2' => 'mixed',
      ),
    ),
    'splobjectstorage::attach' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'object' => 'object',
        'inf=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'object' => 'object',
        'data=' => 'mixed',
      ),
    ),
    'splobjectstorage::offsetset' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'object' => 'object',
        'inf=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'object' => 'object',
        'data=' => 'mixed',
      ),
    ),
    'splpriorityqueue::compare' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'a' => 'mixed',
        'b' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'int',
        'value1' => 'mixed',
        'value2' => 'mixed',
      ),
    ),
    'splqueue::setiteratormode' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'flags' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'mode' => 'int',
      ),
    ),
    'splstack::setiteratormode' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'flags' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'mode' => 'int',
      ),
    ),
    'spltempfileobject::fwrite' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'str' => 'string',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'str' => 'string',
        'length=' => 'int',
      ),
    ),
    'sqlite3stmt::getsql' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'expand=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'string',
        'expanded=' => 'bool',
      ),
    ),
    'stream_context_set_option' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'stream_or_context' => 'mixed',
        'wrappername' => 'string',
        'optionname' => 'string',
        'value' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'stream_or_context' => 'mixed',
        'wrappername' => 'string',
        'optionname=' => 'string',
        'value=' => 'mixed',
      ),
    ),
    'strip_tags' => 
    array (
      'old' => 
      array (
        0 => 'string',
        'str' => 'string',
        'allowable_tags=' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'str' => 'string',
        'allowable_tags=' => 'list<non-empty-string>|string',
      ),
    ),
  ),
  'removed' => 
  array (
  ),
);