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
    'reflectionproperty::hastype' => 
    array (
      0 => 'bool',
    ),
    'reflectionproperty::isinitialized' => 
    array (
      0 => 'bool',
      'object=' => 'object',
    ),
    'sqlite3stmt::getsql' => 
    array (
      0 => 'string',
      'expanded=' => 'bool',
    ),
  ),
  'changed' => 
  array (
    'amqpbasicproperties::getappid' => 
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
    'amqpbasicproperties::getclusterid' => 
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
    'amqpbasicproperties::getcontentencoding' => 
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
    'amqpbasicproperties::getcontenttype' => 
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
    'amqpbasicproperties::getcorrelationid' => 
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
    'amqpbasicproperties::getexpiration' => 
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
    'amqpbasicproperties::getmessageid' => 
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
    'amqpbasicproperties::getreplyto' => 
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
    'amqpbasicproperties::gettimestamp' => 
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
    'amqpbasicproperties::gettype' => 
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
    'amqpbasicproperties::getuserid' => 
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
    'amqpchannel::basicrecover' => 
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
    'amqpchannel::committransaction' => 
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
        0 => 'void',
        'size' => 'int',
        'count' => 'int',
        'global=' => 'bool',
      ),
    ),
    'amqpchannel::rollbacktransaction' => 
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
        0 => 'void',
        'ackCallback' => 'callable|null',
        'nackCallback=' => 'callable|null',
      ),
    ),
    'amqpchannel::setprefetchcount' => 
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
    'amqpchannel::setprefetchsize' => 
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
    'amqpchannel::setreturncallback' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'return_callback' => 'callable|null',
      ),
      'new' => 
      array (
        0 => 'void',
        'returnCallback' => 'callable|null',
      ),
    ),
    'amqpchannel::starttransaction' => 
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
    'amqpchannel::waitforbasicreturn' => 
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
    'amqpchannel::waitforconfirm' => 
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
    'amqpconnection::connect' => 
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
    'amqpconnection::disconnect' => 
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
    'amqpconnection::getcacert' => 
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
    'amqpconnection::getcert' => 
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
    'amqpconnection::getkey' => 
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
    'amqpconnection::getmaxchannels' => 
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
    'amqpconnection::ispersistent' => 
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
    'amqpconnection::pconnect' => 
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
    'amqpconnection::pdisconnect' => 
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
    'amqpconnection::preconnect' => 
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
    'amqpconnection::reconnect' => 
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
    'amqpconnection::setcacert' => 
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
    'amqpconnection::setcert' => 
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
    'amqpconnection::sethost' => 
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
    'amqpconnection::setkey' => 
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
    'amqpconnection::setlogin' => 
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
    'amqpconnection::setpassword' => 
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
    'amqpconnection::setport' => 
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
    'amqpconnection::setreadtimeout' => 
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
    'amqpconnection::settimeout' => 
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
    'amqpconnection::setverify' => 
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
    'amqpconnection::setvhost' => 
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
    'amqpconnection::setwritetimeout' => 
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
    'amqpenvelope::getappid' => 
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
    'amqpenvelope::getclusterid' => 
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
    'amqpenvelope::getconsumertag' => 
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
    'amqpenvelope::getcontentencoding' => 
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
    'amqpenvelope::getcontenttype' => 
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
    'amqpenvelope::getcorrelationid' => 
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
    'amqpenvelope::getdeliverytag' => 
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
    'amqpenvelope::getexchangename' => 
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
    'amqpenvelope::getexpiration' => 
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
    'amqpenvelope::getmessageid' => 
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
    'amqpenvelope::getreplyto' => 
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
    'amqpenvelope::gettimestamp' => 
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
    'amqpenvelope::gettype' => 
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
    'amqpenvelope::getuserid' => 
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
        0 => 'void',
        'exchangeName' => 'string',
        'routingKey=' => 'null|string',
        'arguments=' => 'array<array-key, mixed>',
      ),
    ),
    'amqpexchange::declareexchange' => 
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
        0 => 'void',
        'exchangeName=' => 'null|string',
        'flags=' => 'int|null',
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
    'amqpexchange::getname' => 
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
    'amqpexchange::gettype' => 
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
        0 => 'void',
        'message' => 'string',
        'routingKey=' => 'null|string',
        'flags=' => 'int|null',
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
        0 => 'void',
        'argumentName' => 'string',
        'argumentValue' => 'int|string',
      ),
    ),
    'amqpexchange::setarguments' => 
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
    'amqpexchange::setflags' => 
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
    'amqpexchange::setname' => 
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
    'amqpexchange::settype' => 
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
        0 => 'void',
        'exchangeName' => 'string',
        'routingKey=' => 'null|string',
        'arguments=' => 'array<array-key, mixed>',
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
        0 => 'void',
        'deliveryTag' => 'int',
        'flags=' => 'int|null',
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
        0 => 'void',
        'exchangeName' => 'string',
        'routingKey=' => 'null|string',
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
        0 => 'void',
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
        'flags=' => 'int|null',
        'consumerTag=' => 'null|string',
      ),
    ),
    'amqpqueue::delete' => 
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
    'amqpqueue::get' => 
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
    'amqpqueue::getname' => 
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
        0 => 'void',
        'deliveryTag' => 'int',
        'flags=' => 'int|null',
      ),
    ),
    'amqpqueue::purge' => 
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
        0 => 'void',
        'deliveryTag' => 'int',
        'flags=' => 'int|null',
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
        0 => 'void',
        'argumentName' => 'string',
        'argumentValue' => 'mixed',
      ),
    ),
    'amqpqueue::setarguments' => 
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
    'amqpqueue::setflags' => 
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
    'amqpqueue::setname' => 
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
        0 => 'void',
        'exchangeName' => 'string',
        'routingKey=' => 'null|string',
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
        'timestamp' => 'float',
      ),
    ),
    'amqptimestamp::gettimestamp' => 
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
    'reflectionfunctionabstract::export' => 
    array (
      0 => 'null|string',
    ),
  ),
);