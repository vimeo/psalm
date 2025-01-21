<?php // phpcs:ignoreFile

return array (
  'added' => 
  array (
  ),
  'changed' => 
  array (
    'imagick::getimageblob' => 
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
    'imagickpixel::ispixelsimilar' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'color' => 'ImagickPixel',
        'fuzz' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool|null',
        'color' => 'ImagickPixel',
        'fuzz' => 'float',
      ),
    ),
    'imagickpixel::ispixelsimilarquantum' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'color' => 'string',
        'fuzz=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool|null',
        'color' => 'string',
        'fuzz=' => 'string',
      ),
    ),
    'imagickpixel::issimilar' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'color' => 'ImagickPixel',
        'fuzz' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool|null',
        'color' => 'ImagickPixel',
        'fuzz' => 'float',
      ),
    ),
    'imagickpixeliterator::current' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|null',
      ),
    ),
    'imagickpixeliterator::getcurrentiteratorrow' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|null',
      ),
    ),
    'imagickpixeliterator::getnextiteratorrow' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|null',
      ),
    ),
    'resourcebundle::get' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'index' => 'int|string',
        'fallback=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'ResourceBundle|array<array-key, mixed>|int|null|string',
        'index' => 'int|string',
        'fallback=' => 'bool',
      ),
    ),
  ),
  'removed' => 
  array (
  ),
);