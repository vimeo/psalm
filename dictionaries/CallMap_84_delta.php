<?php // phpcs:ignoreFile
namespace Phan\Language\Internal;

return array (
  'added' => 
  array (
  ),
  'removed' => 
  array (
  ),
  'changed' => 
  array (
    'locale_set_default' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'locale' => 'string',
      ),
      'new' => 
      array (
        0 => 'true',
        'locale' => 'string',
      ),
    ),
    'DateInterval::createFromDateString' => 
    array (
      'old' => 
      array (
        0 => 'DateInterval|false',
        'datetime' => 'string',
      ),
      'new' => 
      array (
        0 => 'DateInterval',
        'datetime' => 'string',
      ),
    ),
    'array_walk' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        '&rw_array' => 'array',
        'callback' => 'callable',
        'arg=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'true',
        '&rw_array' => 'array',
        'callback' => 'callable',
        'arg=' => 'mixed',
      ),
    ),
    'array_walk_recursive' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        '&rw_array' => 'array',
        'callback' => 'callable',
        'arg=' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'true',
        '&rw_array' => 'array',
        'callback' => 'callable',
        'arg=' => 'mixed',
      ),
    ),
    'Fiber::getCurrent' => 
    array (
      'old' => 
      array (
        0 => '?self',
      ),
      'new' => 
      array (
        0 => 'Fiber|null',
      ),
    ),
    'Imagick::autoGammaImage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'channel=' => 'int',
      ),
      'new' => 
      array (
        0 => 'void',
        'channel=' => 'int',
      ),
    ),
    'Imagick::autoLevelImage' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'CHANNEL=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'CHANNEL=' => 'string',
      ),
    ),
    'Imagick::autoOrient' => 
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
    'Imagick::blueShiftImage' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'factor=' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'factor=' => 'float',
      ),
    ),
    'Imagick::brightnessContrastImage' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'brightness' => 'string',
        'contrast' => 'string',
        'CHANNEL=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'brightness' => 'string',
        'contrast' => 'string',
        'CHANNEL=' => 'string',
      ),
    ),
    'Imagick::clampImage' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'CHANNEL=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'CHANNEL=' => 'string',
      ),
    ),
    'Imagick::colorMatrixImage' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'color_matrix' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'color_matrix' => 'string',
      ),
    ),
    'Imagick::count' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'mode=' => 'string',
      ),
      'new' => 
      array (
        0 => 'int',
        'mode=' => 'string',
      ),
    ),
    'Imagick::deleteImageProperty' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'name' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'name' => 'string',
      ),
    ),
    'Imagick::forwardFourierTransformimage' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'magnitude' => 'bool',
      ),
      'new' => 
      array (
        0 => 'bool',
        'magnitude' => 'bool',
      ),
    ),
    'Imagick::getConfigureOptions' => 
    array (
      'old' => 
      array (
        0 => 'string',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
      ),
    ),
    'Imagick::getFont' => 
    array (
      'old' => 
      array (
        0 => 'string|false',
      ),
      'new' => 
      array (
        0 => 'string',
      ),
    ),
    'Imagick::getHDRIEnabled' => 
    array (
      'old' => 
      array (
        0 => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'Imagick::getImageAlphaChannel' => 
    array (
      'old' => 
      array (
        0 => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'Imagick::getImageProperty' => 
    array (
      'old' => 
      array (
        0 => 'string|false',
        'name' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'name' => 'string',
      ),
    ),
    'Imagick::getRegistry' => 
    array (
      'old' => 
      array (
        0 => 'string|false',
        'key' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'key' => 'string',
      ),
    ),
    'Imagick::identifyFormat' => 
    array (
      'old' => 
      array (
        0 => 'string|false',
        'embedText' => 'string',
      ),
      'new' => 
      array (
        0 => 'string',
        'embedText' => 'string',
      ),
    ),
    'Imagick::inverseFourierTransformImage' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'complement' => 'string',
        'magnitude' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'complement' => 'string',
        'magnitude' => 'string',
      ),
    ),
    'Imagick::key' => 
    array (
      'old' => 
      array (
        0 => 'int|string',
      ),
      'new' => 
      array (
        0 => 'int',
      ),
    ),
    'Imagick::localContrastImage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'radius' => 'float',
        'strength' => 'float',
      ),
      'new' => 
      array (
        0 => 'void',
        'radius' => 'float',
        'strength' => 'float',
      ),
    ),
    'Imagick::morphology' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'morphologyMethod' => 'int',
        'iterations' => 'int',
        'ImagickKernel' => 'ImagickKernel',
        'CHANNEL=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'morphologyMethod' => 'int',
        'iterations' => 'int',
        'ImagickKernel' => 'ImagickKernel',
        'CHANNEL=' => 'string',
      ),
    ),
    'Imagick::readImages' => 
    array (
      'old' => 
      array (
        0 => 'Imagick',
        'filenames' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filenames' => 'string',
      ),
    ),
    'Imagick::resetIterator' => 
    array (
      'old' => 
      array (
        0 => '',
      ),
      'new' => 
      array (
        0 => 'void',
      ),
    ),
    'Imagick::rotationalBlurImage' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'angle' => 'string',
        'CHANNEL=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'angle' => 'string',
        'CHANNEL=' => 'string',
      ),
    ),
    'Imagick::roundCornersImage' => 
    array (
      'old' => 
      array (
        0 => '',
        'xRounding' => '',
        'yRounding' => '',
        'strokeWidth' => '',
        'displace' => '',
        'sizeCorrection' => '',
      ),
      'new' => 
      array (
        0 => 'bool',
        'xRounding' => '',
        'yRounding' => '',
        'strokeWidth' => '',
        'displace' => '',
        'sizeCorrection' => '',
      ),
    ),
    'Imagick::selectiveBlurImage' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'radius' => 'float',
        'sigma' => 'float',
        'threshold' => 'float',
        'CHANNEL' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'radius' => 'float',
        'sigma' => 'float',
        'threshold' => 'float',
        'CHANNEL' => 'int',
      ),
    ),
    'Imagick::setAntiAlias' => 
    array (
      'old' => 
      array (
        0 => 'int',
        'antialias' => 'bool',
      ),
      'new' => 
      array (
        0 => 'void',
        'antialias' => 'bool',
      ),
    ),
    'Imagick::setImageChannelMask' => 
    array (
      'old' => 
      array (
        0 => '',
        'channel' => 'int',
      ),
      'new' => 
      array (
        0 => 'int',
        'channel' => 'int',
      ),
    ),
    'Imagick::setImageProgressMonitor' => 
    array (
      'old' => 
      array (
        0 => '',
        'filename' => '',
      ),
      'new' => 
      array (
        0 => 'bool',
        'filename' => '',
      ),
    ),
    'Imagick::setProgressMonitor' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'callback' => 'callable',
      ),
      'new' => 
      array (
        0 => 'bool',
        'callback' => 'callable',
      ),
    ),
    'Imagick::setRegistry' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'key' => 'string',
        'value' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'key' => 'string',
        'value' => 'string',
      ),
    ),
    'Imagick::statisticImage' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'type' => 'int',
        'width' => 'int',
        'height' => 'int',
        'CHANNEL=' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'type' => 'int',
        'width' => 'int',
        'height' => 'int',
        'CHANNEL=' => 'string',
      ),
    ),
    'Imagick::textureImage' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'texture_wand' => 'Imagick',
      ),
      'new' => 
      array (
        0 => 'Imagick',
        'texture_wand' => 'Imagick',
      ),
    ),
    'ImagickDraw::getClipPath' => 
    array (
      'old' => 
      array (
        0 => 'string|false',
      ),
      'new' => 
      array (
        0 => 'string',
      ),
    ),
    'ImagickDraw::getFont' => 
    array (
      'old' => 
      array (
        0 => 'string|false',
      ),
      'new' => 
      array (
        0 => 'string',
      ),
    ),
    'ImagickDraw::getFontFamily' => 
    array (
      'old' => 
      array (
        0 => 'string|false',
      ),
      'new' => 
      array (
        0 => 'string',
      ),
    ),
    'ImagickDraw::getTextDirection' => 
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
    'ImagickDraw::resetVectorGraphics' => 
    array (
      'old' => 
      array (
        0 => 'void',
      ),
      'new' => 
      array (
        0 => 'bool',
      ),
    ),
    'ImagickDraw::setOpacity' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'opacity' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'opacity' => 'float',
      ),
    ),
    'ImagickDraw::setResolution' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'x_resolution' => 'float',
        'y_resolution' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'x_resolution' => 'float',
        'y_resolution' => 'float',
      ),
    ),
    'ImagickDraw::setTextInterlineSpacing' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'spacing' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'spacing' => 'float',
      ),
    ),
    'ImagickDraw::setTextInterwordSpacing' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'spacing' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'spacing' => 'float',
      ),
    ),
    'ImagickDraw::setTextKerning' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'kerning' => 'float',
      ),
      'new' => 
      array (
        0 => 'bool',
        'kerning' => 'float',
      ),
    ),
    'ImagickPixel::getColorQuantum' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
      ),
    ),
    'ImagickPixel::getColorValueQuantum' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
      ),
      'new' => 
      array (
        0 => 'float',
      ),
    ),
    'ImagickPixel::setcolorcount' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'colorCount' => 'string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'colorCount' => 'string',
      ),
    ),
    'ImagickPixel::setColorValueQuantum' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'color' => 'int',
        'value' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'color' => 'int',
        'value' => 'mixed',
      ),
    ),
    'ImagickPixel::setIndex' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'index' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'index' => 'int',
      ),
    ),
    'ImagickPixelIterator::current' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
      ),
    ),
    'ImagickPixelIterator::getpixeliterator' => 
    array (
      'old' => 
      array (
        0 => '',
        'Imagick' => 'Imagick',
      ),
      'new' => 
      array (
        0 => 'ImagickPixelIterator',
        'Imagick' => 'Imagick',
      ),
    ),
    'ImagickPixelIterator::getpixelregioniterator' => 
    array (
      'old' => 
      array (
        0 => '',
        'Imagick' => 'Imagick',
        'x' => '',
        'y' => '',
        'columns' => '',
        'rows' => '',
      ),
      'new' => 
      array (
        0 => 'ImagickPixelIterator',
        'Imagick' => 'Imagick',
        'x' => '',
        'y' => '',
        'columns' => '',
        'rows' => '',
      ),
    ),
    'ImagickPixelIterator::key' => 
    array (
      'old' => 
      array (
        0 => 'int|string',
      ),
      'new' => 
      array (
        0 => 'int',
      ),
    ),
    'MultipleIterator::current' => 
    array (
      'old' => 
      array (
        0 => 'array|false',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>',
      ),
    ),
    'mysqli_stmt::get_warnings' => 
    array (
      'old' => 
      array (
        0 => 'object',
      ),
      'new' => 
      array (
        0 => 'false|mysqli_warning',
      ),
    ),
    'mysqli_stmt_get_warnings' => 
    array (
      'old' => 
      array (
        0 => 'object',
        'statement' => 'mysqli_stmt',
      ),
      'new' => 
      array (
        0 => 'false|mysqli_warning',
        'statement' => 'mysqli_stmt',
      ),
    ),
    'mysqli_stmt_insert_id' => 
    array (
      'old' => 
      array (
        0 => 'mixed',
        'statement' => 'mysqli_stmt',
      ),
      'new' => 
      array (
        0 => 'int|string',
        'statement' => 'mysqli_stmt',
      ),
    ),
    'passthru' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'command' => 'string',
        '&w_result_code=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|null',
        'command' => 'string',
        '&w_result_code=' => 'int',
      ),
    ),
  ),
);