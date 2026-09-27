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
    'ReflectionProperty::getType' => 
    array (
      0 => 'ReflectionType|null',
    ),
    'ReflectionProperty::hasType' => 
    array (
      0 => 'bool',
    ),
    'ReflectionProperty::isInitialized' => 
    array (
      0 => 'bool',
      'object=' => 'object',
    ),
    'SQLite3Stmt::getSQL' => 
    array (
      0 => 'string',
      'expanded=' => 'bool',
    ),
  ),
  'changed' => 
  array (
    'AMQPBasicProperties::getAppId' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPBasicProperties::getClusterId' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPBasicProperties::getContentEncoding' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPBasicProperties::getContentType' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPBasicProperties::getCorrelationId' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPBasicProperties::getExpiration' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPBasicProperties::getMessageId' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPBasicProperties::getReplyTo' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPBasicProperties::getTimestamp' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'int|null',
      ),
    ),
    'AMQPBasicProperties::getType' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPBasicProperties::getUserId' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPChannel::basicRecover' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'requeue=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
        'requeue=' => 'bool',
      ),
    ),
    'AMQPChannel::commitTransaction' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
      ),
    ),
    'AMQPChannel::qos' => 
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
        0 => 'void',
        'size' => 'int',
        'count' => 'int',
        'global=' => 'bool',
      ),
    ),
    'AMQPChannel::rollbackTransaction' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
      ),
    ),
    'AMQPChannel::setConfirmCallback' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'ack_callback' => 'impure-callable|null',
        'nack_callback=' => 'impure-callable|null',
      ),
      'new' => 
      array (
        0 => 'void',
        'ackCallback' => 'impure-callable|null',
        'nackCallback=' => 'impure-callable|null',
      ),
    ),
    'AMQPChannel::setPrefetchCount' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'count' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'count' => 'int',
      ),
    ),
    'AMQPChannel::setPrefetchSize' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'size' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'size' => 'int',
      ),
    ),
    'AMQPChannel::setReturnCallback' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'return_callback' => 'impure-callable|null',
      ),
      'new' => 
      array (
        0 => 'void',
        'returnCallback' => 'impure-callable|null',
      ),
    ),
    'AMQPChannel::startTransaction' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
      ),
    ),
    'AMQPChannel::waitForBasicReturn' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'timeout=' => 'float',
      ),
      'new' => 
      array (
        0 => 'void',
        'timeout=' => 'float',
      ),
    ),
    'AMQPChannel::waitForConfirm' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'timeout=' => 'float',
      ),
      'new' => 
      array (
        0 => 'void',
        'timeout=' => 'float',
      ),
    ),
    'AMQPConnection::connect' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
      ),
    ),
    'AMQPConnection::disconnect' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
      ),
    ),
    'AMQPConnection::getCACert' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPConnection::getCert' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPConnection::getKey' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPConnection::getMaxChannels' => 
    array (
      'old' => 
      array (
        0 => 'int|null',
      ),
      'new' => 
      array (
        0 => 'int',
      ),
    ),
    'AMQPConnection::isPersistent' => 
    array (
      'old' => 
      array (
        0 => 'bool|null',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'AMQPConnection::pconnect' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
      ),
    ),
    'AMQPConnection::pdisconnect' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
      ),
    ),
    'AMQPConnection::preconnect' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
      ),
    ),
    'AMQPConnection::reconnect' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
      ),
    ),
    'AMQPConnection::setCACert' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'cacert' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'cacert' => 'null|string',
      ),
    ),
    'AMQPConnection::setCert' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'cert' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'cert' => 'null|string',
      ),
    ),
    'AMQPConnection::setHost' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'host' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'host' => 'string',
      ),
    ),
    'AMQPConnection::setKey' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'key' => 'null|string',
      ),
    ),
    'AMQPConnection::setLogin' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'login' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'login' => 'string',
      ),
    ),
    'AMQPConnection::setPassword' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'password' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'password' => 'string',
      ),
    ),
    'AMQPConnection::setPort' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'port' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'port' => 'int',
      ),
    ),
    'AMQPConnection::setReadTimeout' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'timeout' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'timeout' => 'float',
      ),
    ),
    'AMQPConnection::setTimeout' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'timeout' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'timeout' => 'float',
      ),
    ),
    'AMQPConnection::setVerify' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'verify' => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
        'verify' => 'bool',
      ),
    ),
    'AMQPConnection::setVhost' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'vhost' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'vhost' => 'string',
      ),
    ),
    'AMQPConnection::setWriteTimeout' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'timeout' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'timeout' => 'float',
      ),
    ),
    'AMQPEnvelope::getAppId' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPEnvelope::getClusterId' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPEnvelope::getConsumerTag' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPEnvelope::getContentEncoding' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPEnvelope::getContentType' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPEnvelope::getCorrelationId' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPEnvelope::getDeliveryTag' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'int|null',
      ),
    ),
    'AMQPEnvelope::getExchangeName' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPEnvelope::getExpiration' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPEnvelope::getHeader' => 
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
    'AMQPEnvelope::getMessageId' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPEnvelope::getReplyTo' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPEnvelope::getTimestamp' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'int|null',
      ),
    ),
    'AMQPEnvelope::getType' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPEnvelope::getUserId' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPEnvelope::hasHeader' => 
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
    'AMQPExchange::bind' => 
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
        0 => 'void',
        'exchangeName' => 'string',
        'routingKey=' => 'null|string',
        'arguments=' => 'array<array-key, mixed>',
      ),
    ),
    'AMQPExchange::declareExchange' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
      ),
    ),
    'AMQPExchange::delete' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'exchange_name=' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'exchangeName=' => 'null|string',
        'flags=' => 'int|null',
      ),
    ),
    'AMQPExchange::getArgument' => 
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
    'AMQPExchange::getName' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPExchange::getType' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPExchange::hasArgument' => 
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
    'AMQPExchange::publish' => 
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
        0 => 'void',
        'message' => 'string',
        'routingKey=' => 'null|string',
        'flags=' => 'int|null',
        'headers=' => 'array<array-key, mixed>',
      ),
    ),
    'AMQPExchange::setArgument' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'value' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'void',
        'argumentName' => 'string',
        'argumentValue' => 'int|string',
      ),
    ),
    'AMQPExchange::setArguments' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'arguments' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'void',
        'arguments' => 'array<array-key, mixed>',
      ),
    ),
    'AMQPExchange::setFlags' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'flags' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'flags' => 'int|null',
      ),
    ),
    'AMQPExchange::setName' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'exchange_name' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'exchangeName' => 'null|string',
      ),
    ),
    'AMQPExchange::setType' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'exchange_type' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'exchangeType' => 'null|string',
      ),
    ),
    'AMQPExchange::unbind' => 
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
        0 => 'void',
        'exchangeName' => 'string',
        'routingKey=' => 'null|string',
        'arguments=' => 'array<array-key, mixed>',
      ),
    ),
    'AMQPQueue::ack' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'delivery_tag' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'deliveryTag' => 'int',
        'flags=' => 'int|null',
      ),
    ),
    'AMQPQueue::bind' => 
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
        0 => 'void',
        'exchangeName' => 'string',
        'routingKey=' => 'null|string',
        'arguments=' => 'array<array-key, mixed>',
      ),
    ),
    'AMQPQueue::cancel' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'consumer_tag=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'consumerTag=' => 'string',
      ),
    ),
    'AMQPQueue::consume' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'callback' => 'impure-callable|null',
        'flags=' => 'int',
        'consumer_tag=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'callback=' => 'impure-callable|null',
        'flags=' => 'int|null',
        'consumerTag=' => 'null|string',
      ),
    ),
    'AMQPQueue::delete' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'flags=' => 'int|null',
      ),
    ),
    'AMQPQueue::get' => 
    array (
      'old' => 
      array (
        0 => 'AMQPEnvelope|false',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'AMQPEnvelope|null',
        'flags=' => 'int|null',
      ),
    ),
    'AMQPQueue::getArgument' => 
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
    'AMQPQueue::getName' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'null|string',
      ),
    ),
    'AMQPQueue::hasArgument' => 
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
    'AMQPQueue::nack' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'delivery_tag' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'deliveryTag' => 'int',
        'flags=' => 'int|null',
      ),
    ),
    'AMQPQueue::purge' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'int',
      ),
    ),
    'AMQPQueue::reject' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'delivery_tag' => 'string',
        'flags=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'deliveryTag' => 'int',
        'flags=' => 'int|null',
      ),
    ),
    'AMQPQueue::setArgument' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'value' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'void',
        'argumentName' => 'string',
        'argumentValue' => 'mixed',
      ),
    ),
    'AMQPQueue::setArguments' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'arguments' => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'void',
        'arguments' => 'array<array-key, mixed>',
      ),
    ),
    'AMQPQueue::setFlags' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'flags' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'flags' => 'int|null',
      ),
    ),
    'AMQPQueue::setName' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'queue_name' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'name' => 'string',
      ),
    ),
    'AMQPQueue::unbind' => 
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
        0 => 'void',
        'exchangeName' => 'string',
        'routingKey=' => 'null|string',
        'arguments=' => 'array<array-key, mixed>',
      ),
    ),
    'AMQPTimestamp::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'timestamp=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'timestamp' => 'float',
      ),
    ),
    'AMQPTimestamp::getTimestamp' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'float',
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
    'ArrayIterator::__construct' => 
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
    'ArrayObject::exchangeArray' => 
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
    'DOMDocument::createProcessingInstruction' => 
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
    'DOMDocument::importNode' => 
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
    'DOMImplementation::createDocument' => 
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
    'Locale::lookup' => 
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
    'MongoDB\\Driver\\Cursor::getId' => 
    array (
      'old' => 
      array (
        0 => 'MongoDB\\Driver\\CursorId',
      ),
      'new' => 
      array (
        0 => 'MongoDB\\BSON\\Int64',
        'asInt64=' => 'bool',
      ),
    ),
    'openssl_random_pseudo_bytes' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'length' => 'int',
        '&w result_is_strong=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'string',
        'length' => 'int',
        '&w result_is_strong=' => 'bool',
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
        'callback' => 'impure-callable(array<array-key, string>):string',
        'subject' => 'string',
        'limit=' => 'int',
        '&w count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'regex' => 'array<array-key, mixed>|string',
        'callback' => 'impure-callable(array<array-key, string>):string',
        'subject' => 'string',
        'limit=' => 'int',
        '&w count=' => 'int',
        'flags=' => 'int',
      ),
    ),
    'preg_replace_callback\'1' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, string>|null',
        'pattern' => 'array<array-key, mixed>|string',
        'callback' => 'impure-callable(array<array-key, string>):string',
        'subject' => 'array<array-key, string>',
        'limit=' => 'int',
        '&w count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, string>|null',
        'pattern' => 'array<array-key, mixed>|string',
        'callback' => 'impure-callable(array<array-key, string>):string',
        'subject' => 'array<array-key, string>',
        'limit=' => 'int',
        '&w count=' => 'int',
        'flags=' => 'int',
      ),
    ),
    'preg_replace_callback_array' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'pattern' => 'array<string, impure-callable(array<array-key, mixed>):string>',
        'subject' => 'string',
        'limit=' => 'int',
        '&w count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'pattern' => 'array<string, impure-callable(array<array-key, mixed>):string>',
        'subject' => 'string',
        'limit=' => 'int',
        '&w count=' => 'int',
        'flags=' => 'int',
      ),
    ),
    'preg_replace_callback_array\'1' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, string>|null',
        'pattern' => 'array<string, impure-callable(array<array-key, mixed>):string>',
        'subject' => 'array<array-key, string>',
        'limit=' => 'int',
        '&w count=' => 'int',
      ),
      'new' => 
      array (
        0 => 'array<array-key, string>|null',
        'pattern' => 'array<string, impure-callable(array<array-key, mixed>):string>',
        'subject' => 'array<array-key, string>',
        'limit=' => 'int',
        '&w count=' => 'int',
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
    'RecursiveArrayIterator::__construct' => 
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
    'Redis::hSet' => 
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
    'ReflectionMethod::getClosure' => 
    array (
      'old' => 
      array (
        0 => 'impure-Closure|null',
        'object' => 'object',
      ),
      'new' => 
      array (
        0 => 'impure-Closure|null',
        'object=' => 'object',
      ),
    ),
    'SplDoublyLinkedList::setIteratorMode' => 
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
    'SplFileObject::fwrite' => 
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
    'SplFixedArray::fromArray' => 
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
    'SplMaxHeap::compare' => 
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
    'SplMinHeap::compare' => 
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
    'SplObjectStorage::attach' => 
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
    'SplObjectStorage::offsetSet' => 
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
    'SplPriorityQueue::compare' => 
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
    'SplQueue::setIteratorMode' => 
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
    'SplStack::setIteratorMode' => 
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
    'SplTempFileObject::fwrite' => 
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
    'unserialize' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'variable_representation' => 'string',
        'allowed_classes=' => 'array{allowed_classes?: array<array-key, class-string>|bool}',
      ),
      'new' => 
      array (
        0 => 'mixed',
        'variable_representation' => 'string',
        'allowed_classes=' => 'array{allowed_classes?: array<array-key, class-string>|bool, max_depth?: int}',
      ),
    ),
  ),
  'removed' => 
  array (
    'ReflectionFunctionAbstract::export' => 
    array (
      0 => 'null|string',
    ),
  ),
);