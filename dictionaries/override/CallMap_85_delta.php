<?php // phpcs:ignoreFile

return array (
  'added' => 
  array (
  ),
  'changed' => 
  array (
    'array_multisort' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        '&array' => 'array<array-key, mixed>',
        '&...rest=' => 'array<array-key, mixed>|int',
      ),
      'new' => 
      array (
        0 => 'true',
        '&array' => 'array<array-key, mixed>',
        '&...rest=' => 'array<array-key, mixed>|int',
      ),
    ),
    'finfo_close' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'finfo' => 'finfo',
      ),
      'new' => 
      array (
        0 => 'true',
        'finfo' => 'finfo',
      ),
    ),
    'grapheme_stripos' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset=' => 'int',
        'locale=' => 'string',
      ),
    ),
    'grapheme_stristr' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'string',
        'beforeNeedle=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'string',
        'beforeNeedle=' => 'bool',
        'locale=' => 'string',
      ),
    ),
    'grapheme_strpos' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset=' => 'int',
        'locale=' => 'string',
      ),
    ),
    'grapheme_strripos' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset=' => 'int',
        'locale=' => 'string',
      ),
    ),
    'grapheme_strrpos' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'haystack' => 'string',
        'needle' => 'string',
        'offset=' => 'int',
        'locale=' => 'string',
      ),
    ),
    'grapheme_strstr' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'string',
        'beforeNeedle=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'haystack' => 'string',
        'needle' => 'string',
        'beforeNeedle=' => 'bool',
        'locale=' => 'string',
      ),
    ),
    'grapheme_substr' => 
    array (
      'old' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'offset' => 'int',
        'length=' => 'int|null',
      ),
      'new' => 
      array (
        0 => 'false|string',
        'string' => 'string',
        'offset' => 'int',
        'length=' => 'int|null',
        'locale=' => 'string',
      ),
    ),
    'gzfile' => 
    array (
      'old' => 
      array (
        0 => 'false|list<string>',
        'filename' => 'string',
        'use_include_path=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|list<string>',
        'filename' => 'string',
        'use_include_path=' => 'bool',
      ),
    ),
    'gzopen' => 
    array (
      'old' => 
      array (
        0 => 'false|resource',
        'filename' => 'string',
        'mode' => 'string',
        'use_include_path=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|resource',
        'filename' => 'string',
        'mode' => 'string',
        'use_include_path=' => 'bool',
      ),
    ),
    'imagealphablending' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'enable' => 'bool',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'enable' => 'bool',
      ),
    ),
    'imageantialias' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'enable' => 'bool',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'enable' => 'bool',
      ),
    ),
    'imagearc' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'center_x' => 'int',
        'center_y' => 'int',
        'width' => 'int',
        'height' => 'int',
        'start_angle' => 'int',
        'end_angle' => 'int',
        'color' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'center_x' => 'int',
        'center_y' => 'int',
        'width' => 'int',
        'height' => 'int',
        'start_angle' => 'int',
        'end_angle' => 'int',
        'color' => 'int',
      ),
    ),
    'imagechar' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'font' => 'int',
        'x' => 'int',
        'y' => 'int',
        'char' => 'string',
        'color' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'font' => 'int',
        'x' => 'int',
        'y' => 'int',
        'char' => 'string',
        'color' => 'int',
      ),
    ),
    'imagecharup' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'font' => 'int',
        'x' => 'int',
        'y' => 'int',
        'char' => 'string',
        'color' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'font' => 'int',
        'x' => 'int',
        'y' => 'int',
        'char' => 'string',
        'color' => 'int',
      ),
    ),
    'imagecolordeallocate' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'color' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'color' => 'int',
      ),
    ),
    'imagecolormatch' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image1' => 'GdImage',
        'image2' => 'GdImage',
      ),
      'new' => 
      array (
        0 => 'true',
        'image1' => 'GdImage',
        'image2' => 'GdImage',
      ),
    ),
    'imagecopy' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dst_image' => 'GdImage',
        'src_image' => 'GdImage',
        'dst_x' => 'int',
        'dst_y' => 'int',
        'src_x' => 'int',
        'src_y' => 'int',
        'src_width' => 'int',
        'src_height' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'dst_image' => 'GdImage',
        'src_image' => 'GdImage',
        'dst_x' => 'int',
        'dst_y' => 'int',
        'src_x' => 'int',
        'src_y' => 'int',
        'src_width' => 'int',
        'src_height' => 'int',
      ),
    ),
    'imagecopymerge' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dst_image' => 'GdImage',
        'src_image' => 'GdImage',
        'dst_x' => 'int',
        'dst_y' => 'int',
        'src_x' => 'int',
        'src_y' => 'int',
        'src_width' => 'int',
        'src_height' => 'int',
        'pct' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'dst_image' => 'GdImage',
        'src_image' => 'GdImage',
        'dst_x' => 'int',
        'dst_y' => 'int',
        'src_x' => 'int',
        'src_y' => 'int',
        'src_width' => 'int',
        'src_height' => 'int',
        'pct' => 'int',
      ),
    ),
    'imagecopymergegray' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dst_image' => 'GdImage',
        'src_image' => 'GdImage',
        'dst_x' => 'int',
        'dst_y' => 'int',
        'src_x' => 'int',
        'src_y' => 'int',
        'src_width' => 'int',
        'src_height' => 'int',
        'pct' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'dst_image' => 'GdImage',
        'src_image' => 'GdImage',
        'dst_x' => 'int',
        'dst_y' => 'int',
        'src_x' => 'int',
        'src_y' => 'int',
        'src_width' => 'int',
        'src_height' => 'int',
        'pct' => 'int',
      ),
    ),
    'imagecopyresampled' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dst_image' => 'GdImage',
        'src_image' => 'GdImage',
        'dst_x' => 'int',
        'dst_y' => 'int',
        'src_x' => 'int',
        'src_y' => 'int',
        'dst_width' => 'int',
        'dst_height' => 'int',
        'src_width' => 'int',
        'src_height' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'dst_image' => 'GdImage',
        'src_image' => 'GdImage',
        'dst_x' => 'int',
        'dst_y' => 'int',
        'src_x' => 'int',
        'src_y' => 'int',
        'dst_width' => 'int',
        'dst_height' => 'int',
        'src_width' => 'int',
        'src_height' => 'int',
      ),
    ),
    'imagecopyresized' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'dst_image' => 'GdImage',
        'src_image' => 'GdImage',
        'dst_x' => 'int',
        'dst_y' => 'int',
        'src_x' => 'int',
        'src_y' => 'int',
        'dst_width' => 'int',
        'dst_height' => 'int',
        'src_width' => 'int',
        'src_height' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'dst_image' => 'GdImage',
        'src_image' => 'GdImage',
        'dst_x' => 'int',
        'dst_y' => 'int',
        'src_x' => 'int',
        'src_y' => 'int',
        'dst_width' => 'int',
        'dst_height' => 'int',
        'src_width' => 'int',
        'src_height' => 'int',
      ),
    ),
    'imagedashedline' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'x1' => 'int',
        'y1' => 'int',
        'x2' => 'int',
        'y2' => 'int',
        'color' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'x1' => 'int',
        'y1' => 'int',
        'x2' => 'int',
        'y2' => 'int',
        'color' => 'int',
      ),
    ),
    'imagedestroy' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
      ),
    ),
    'imageellipse' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'center_x' => 'int',
        'center_y' => 'int',
        'width' => 'int',
        'height' => 'int',
        'color' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'center_x' => 'int',
        'center_y' => 'int',
        'width' => 'int',
        'height' => 'int',
        'color' => 'int',
      ),
    ),
    'imagefill' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'x' => 'int',
        'y' => 'int',
        'color' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'x' => 'int',
        'y' => 'int',
        'color' => 'int',
      ),
    ),
    'imagefilledarc' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'center_x' => 'int',
        'center_y' => 'int',
        'width' => 'int',
        'height' => 'int',
        'start_angle' => 'int',
        'end_angle' => 'int',
        'color' => 'int',
        'style' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'center_x' => 'int',
        'center_y' => 'int',
        'width' => 'int',
        'height' => 'int',
        'start_angle' => 'int',
        'end_angle' => 'int',
        'color' => 'int',
        'style' => 'int',
      ),
    ),
    'imagefilledellipse' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'center_x' => 'int',
        'center_y' => 'int',
        'width' => 'int',
        'height' => 'int',
        'color' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'center_x' => 'int',
        'center_y' => 'int',
        'width' => 'int',
        'height' => 'int',
        'color' => 'int',
      ),
    ),
    'imagefilledrectangle' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'x1' => 'int',
        'y1' => 'int',
        'x2' => 'int',
        'y2' => 'int',
        'color' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'x1' => 'int',
        'y1' => 'int',
        'x2' => 'int',
        'y2' => 'int',
        'color' => 'int',
      ),
    ),
    'imagefilltoborder' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'x' => 'int',
        'y' => 'int',
        'border_color' => 'int',
        'color' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'x' => 'int',
        'y' => 'int',
        'border_color' => 'int',
        'color' => 'int',
      ),
    ),
    'imageflip' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'mode' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'mode' => 'int',
      ),
    ),
    'imagegammacorrect' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'input_gamma' => 'float',
        'output_gamma' => 'float',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'input_gamma' => 'float',
        'output_gamma' => 'float',
      ),
    ),
    'imagelayereffect' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'effect' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'effect' => 'int',
      ),
    ),
    'imageline' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'x1' => 'int',
        'y1' => 'int',
        'x2' => 'int',
        'y2' => 'int',
        'color' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'x1' => 'int',
        'y1' => 'int',
        'x2' => 'int',
        'y2' => 'int',
        'color' => 'int',
      ),
    ),
    'imagerectangle' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'x1' => 'int',
        'y1' => 'int',
        'x2' => 'int',
        'y2' => 'int',
        'color' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'x1' => 'int',
        'y1' => 'int',
        'x2' => 'int',
        'y2' => 'int',
        'color' => 'int',
      ),
    ),
    'imageresolution' => 
    array (
      'old' => 
      array (
        0 => 'array<array-key, mixed>|bool',
        'image' => 'GdImage',
        'resolution_x=' => 'int|null',
        'resolution_y=' => 'int|null',
      ),
      'new' => 
      array (
        0 => 'array<array-key, mixed>|true',
        'image' => 'GdImage',
        'resolution_x=' => 'int|null',
        'resolution_y=' => 'int|null',
      ),
    ),
    'imagesavealpha' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'enable' => 'bool',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'enable' => 'bool',
      ),
    ),
    'imagesetbrush' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'brush' => 'GdImage',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'brush' => 'GdImage',
      ),
    ),
    'imagesetclip' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'x1' => 'int',
        'y1' => 'int',
        'x2' => 'int',
        'y2' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'x1' => 'int',
        'y1' => 'int',
        'x2' => 'int',
        'y2' => 'int',
      ),
    ),
    'imagesetpixel' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'x' => 'int',
        'y' => 'int',
        'color' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'x' => 'int',
        'y' => 'int',
        'color' => 'int',
      ),
    ),
    'imagesetthickness' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'thickness' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'thickness' => 'int',
      ),
    ),
    'imagesettile' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'tile' => 'GdImage',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'tile' => 'GdImage',
      ),
    ),
    'imagestring' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'font' => 'int',
        'x' => 'int',
        'y' => 'int',
        'string' => 'string',
        'color' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'font' => 'int',
        'x' => 'int',
        'y' => 'int',
        'string' => 'string',
        'color' => 'int',
      ),
    ),
    'imagestringup' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'image' => 'GdImage',
        'font' => 'int',
        'x' => 'int',
        'y' => 'int',
        'string' => 'string',
        'color' => 'int',
      ),
      'new' => 
      array (
        0 => 'true',
        'image' => 'GdImage',
        'font' => 'int',
        'x' => 'int',
        'y' => 'int',
        'string' => 'string',
        'color' => 'int',
      ),
    ),
    'intlcal_create_instance' => 
    array (
      'old' => 
      array (
        0 => 'IntlCalendar|null',
        'timezone=' => 'mixed',
        'locale=' => 'null|string',
      ),
      'new' => 
      array (
        0 => 'IntlCalendar|null',
        'timezone=' => 'DateTimeZone|IntlTimeZone|null|string',
        'locale=' => 'null|string',
      ),
    ),
    'intlcal_set_time_zone' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'timezone' => 'mixed',
      ),
      'new' => 
      array (
        0 => 'bool',
        'calendar' => 'IntlCalendar',
        'timezone' => 'DateTimeZone|IntlTimeZone|null|string',
      ),
    ),
    'intltimezone::createenumeration' => 
    array (
      'old' => 
      array (
        0 => 'IntlIterator|false',
        'countryOrRawOffset=' => 'IntlTimeZone|float|int|null|string',
      ),
      'new' => 
      array (
        0 => 'IntlIterator|false',
        'countryOrRawOffset=' => 'int|null|string',
      ),
    ),
    'intltz_create_enumeration' => 
    array (
      'old' => 
      array (
        0 => 'IntlIterator|false',
        'countryOrRawOffset=' => 'IntlTimeZone|float|int|null|string',
      ),
      'new' => 
      array (
        0 => 'IntlIterator|false',
        'countryOrRawOffset=' => 'int|null|string',
      ),
    ),
    'libxml_set_external_entity_loader' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'resolver_function' => 'callable(string, string, array{directory: null|string, extSubSystem: null|string, extSubURI: null|string, intSubName: null|string}):(null|resource|string)|null',
      ),
      'new' => 
      array (
        0 => 'true',
        'resolver_function' => 'callable(string, string, array{directory: null|string, extSubSystem: null|string, extSubURI: null|string, intSubName: null|string}):(null|resource|string)|null',
      ),
    ),
    'openssl_private_decrypt' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'data' => 'string',
        '&w decrypted_data' => 'string',
        'private_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'padding=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'data' => 'string',
        '&w decrypted_data' => 'string',
        'private_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'padding=' => 'int',
        'digest_algo=' => 'null|string',
      ),
    ),
    'openssl_public_encrypt' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'data' => 'string',
        '&w encrypted_data' => 'string',
        'public_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'padding=' => 'int',
      ),
      'new' => 
      array (
        0 => 'bool',
        'data' => 'string',
        '&w encrypted_data' => 'string',
        'public_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'padding=' => 'int',
        'digest_algo=' => 'null|string',
      ),
    ),
    'openssl_sign' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'data' => 'string',
        '&w signature' => 'string',
        'private_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'algorithm=' => 'int|string',
      ),
      'new' => 
      array (
        0 => 'bool',
        'data' => 'string',
        '&w signature' => 'string',
        'private_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'algorithm=' => 'int|string',
        'padding=' => 'int',
      ),
    ),
    'openssl_verify' => 
    array (
      'old' => 
      array (
        0 => '-1|0|1|false',
        'data' => 'string',
        'signature' => 'string',
        'public_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'algorithm=' => 'int|string',
      ),
      'new' => 
      array (
        0 => '-1|0|1|false',
        'data' => 'string',
        'signature' => 'string',
        'public_key' => 'OpenSSLAsymmetricKey|OpenSSLCertificate|list{OpenSSLAsymmetricKey|OpenSSLCertificate|string, string}|string',
        'algorithm=' => 'int|string',
        'padding=' => 'int',
      ),
    ),
    'readgzfile' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'filename' => 'string',
        'use_include_path=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'filename' => 'string',
        'use_include_path=' => 'bool',
      ),
    ),
    'readline_add_history' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'prompt' => 'string',
      ),
      'new' => 
      array (
        0 => 'true',
        'prompt' => 'string',
      ),
    ),
    'readline_callback_handler_install' => 
    array (
      'old' => 
      array (
        0 => 'bool',
        'prompt' => 'string',
        'callback' => 'callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'prompt' => 'string',
        'callback' => 'callable',
      ),
    ),
    'readline_clear_history' => 
    array (
      'old' => 
      array (
        0 => 'bool',
      ),
      'new' => 
      array (
        0 => 'true',
      ),
    ),
    'soapclient::__dorequest' => 
    array (
      'old' => 
      array (
        0 => 'null|string',
        'request' => 'string',
        'location' => 'string',
        'action' => 'string',
        'version' => 'int',
        'oneWay=' => 'bool',
      ),
      'new' => 
      array (
        0 => 'null|string',
        'request' => 'string',
        'location' => 'string',
        'action' => 'string',
        'version' => 'int',
        'oneWay=' => 'bool',
        'uriParserClass=' => 'null|string',
      ),
    ),
    'soapfault::__construct' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'code' => 'array<array-key, mixed>|null|string',
        'string' => 'string',
        'actor=' => 'null|string',
        'details=' => 'mixed|null',
        'name=' => 'null|string',
        'headerFault=' => 'mixed|null',
      ),
      'new' => 
      array (
        0 => 'void',
        'code' => 'array<array-key, mixed>|null|string',
        'string' => 'string',
        'actor=' => 'null|string',
        'details=' => 'mixed|null',
        'name=' => 'null|string',
        'headerFault=' => 'mixed|null',
        'lang=' => 'string',
      ),
    ),
    'soapserver::fault' => 
    array (
      'old' => 
      array (
        0 => 'void',
        'code' => 'string',
        'string' => 'string',
        'actor=' => 'string',
        'details=' => 'string',
        'name=' => 'string',
      ),
      'new' => 
      array (
        0 => 'void',
        'code' => 'string',
        'string' => 'string',
        'actor=' => 'string',
        'details=' => 'string',
        'name=' => 'string',
        'lang=' => 'string',
      ),
    ),
    'splfileobject::fwrite' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'data' => 'string',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'data' => 'string',
        'length=' => 'int|null',
      ),
    ),
    'spltempfileobject::fwrite' => 
    array (
      'old' => 
      array (
        0 => 'false|int',
        'data' => 'string',
        'length=' => 'int',
      ),
      'new' => 
      array (
        0 => 'false|int',
        'data' => 'string',
        'length=' => 'int|null',
      ),
    ),
  ),
  'removed' => 
  array (
    'amqpbasicproperties::getappid' => 
    array (
      0 => 'null|string',
    ),
    'amqpbasicproperties::getclusterid' => 
    array (
      0 => 'null|string',
    ),
    'amqpbasicproperties::getcontentencoding' => 
    array (
      0 => 'null|string',
    ),
    'amqpbasicproperties::getcontenttype' => 
    array (
      0 => 'null|string',
    ),
    'amqpbasicproperties::getcorrelationid' => 
    array (
      0 => 'null|string',
    ),
    'amqpbasicproperties::getdeliverymode' => 
    array (
      0 => 'int',
    ),
    'amqpbasicproperties::getexpiration' => 
    array (
      0 => 'null|string',
    ),
    'amqpbasicproperties::getheaders' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'amqpbasicproperties::getmessageid' => 
    array (
      0 => 'null|string',
    ),
    'amqpbasicproperties::getpriority' => 
    array (
      0 => 'int',
    ),
    'amqpbasicproperties::getreplyto' => 
    array (
      0 => 'null|string',
    ),
    'amqpbasicproperties::gettimestamp' => 
    array (
      0 => 'int|null',
    ),
    'amqpbasicproperties::gettype' => 
    array (
      0 => 'null|string',
    ),
    'amqpbasicproperties::getuserid' => 
    array (
      0 => 'null|string',
    ),
    'amqpchannel::basicrecover' => 
    array (
      0 => 'void',
      'requeue=' => 'bool',
    ),
    'amqpchannel::committransaction' => 
    array (
      0 => 'void',
    ),
    'amqpchannel::getchannelid' => 
    array (
      0 => 'int',
    ),
    'amqpchannel::getconnection' => 
    array (
      0 => 'AMQPConnection',
    ),
    'amqpchannel::getconsumers' => 
    array (
      0 => 'array<array-key, AMQPQueue>',
    ),
    'amqpchannel::getprefetchcount' => 
    array (
      0 => 'int',
    ),
    'amqpchannel::getprefetchsize' => 
    array (
      0 => 'int',
    ),
    'amqpchannel::isconnected' => 
    array (
      0 => 'bool',
    ),
    'amqpchannel::qos' => 
    array (
      0 => 'void',
      'size' => 'int',
      'count' => 'int',
      'global=' => 'bool',
    ),
    'amqpchannel::rollbacktransaction' => 
    array (
      0 => 'void',
    ),
    'amqpchannel::setconfirmcallback' => 
    array (
      0 => 'void',
      'ackCallback' => 'callable|null',
      'nackCallback=' => 'callable|null',
    ),
    'amqpchannel::setprefetchcount' => 
    array (
      0 => 'void',
      'count' => 'int',
    ),
    'amqpchannel::setprefetchsize' => 
    array (
      0 => 'void',
      'size' => 'int',
    ),
    'amqpchannel::setreturncallback' => 
    array (
      0 => 'void',
      'returnCallback' => 'callable|null',
    ),
    'amqpchannel::starttransaction' => 
    array (
      0 => 'void',
    ),
    'amqpchannel::waitforbasicreturn' => 
    array (
      0 => 'void',
      'timeout=' => 'float',
    ),
    'amqpchannel::waitforconfirm' => 
    array (
      0 => 'void',
      'timeout=' => 'float',
    ),
    'amqpconnection::connect' => 
    array (
      0 => 'void',
    ),
    'amqpconnection::disconnect' => 
    array (
      0 => 'void',
    ),
    'amqpconnection::getcacert' => 
    array (
      0 => 'null|string',
    ),
    'amqpconnection::getcert' => 
    array (
      0 => 'null|string',
    ),
    'amqpconnection::getheartbeatinterval' => 
    array (
      0 => 'int',
    ),
    'amqpconnection::gethost' => 
    array (
      0 => 'string',
    ),
    'amqpconnection::getkey' => 
    array (
      0 => 'null|string',
    ),
    'amqpconnection::getlogin' => 
    array (
      0 => 'string',
    ),
    'amqpconnection::getmaxchannels' => 
    array (
      0 => 'int',
    ),
    'amqpconnection::getmaxframesize' => 
    array (
      0 => 'int',
    ),
    'amqpconnection::getpassword' => 
    array (
      0 => 'string',
    ),
    'amqpconnection::getport' => 
    array (
      0 => 'int',
    ),
    'amqpconnection::getreadtimeout' => 
    array (
      0 => 'float',
    ),
    'amqpconnection::gettimeout' => 
    array (
      0 => 'float',
    ),
    'amqpconnection::getusedchannels' => 
    array (
      0 => 'int',
    ),
    'amqpconnection::getverify' => 
    array (
      0 => 'bool',
    ),
    'amqpconnection::getvhost' => 
    array (
      0 => 'string',
    ),
    'amqpconnection::getwritetimeout' => 
    array (
      0 => 'float',
    ),
    'amqpconnection::isconnected' => 
    array (
      0 => 'bool',
    ),
    'amqpconnection::ispersistent' => 
    array (
      0 => 'bool',
    ),
    'amqpconnection::pconnect' => 
    array (
      0 => 'void',
    ),
    'amqpconnection::pdisconnect' => 
    array (
      0 => 'void',
    ),
    'amqpconnection::preconnect' => 
    array (
      0 => 'void',
    ),
    'amqpconnection::reconnect' => 
    array (
      0 => 'void',
    ),
    'amqpconnection::setcacert' => 
    array (
      0 => 'void',
      'cacert' => 'null|string',
    ),
    'amqpconnection::setcert' => 
    array (
      0 => 'void',
      'cert' => 'null|string',
    ),
    'amqpconnection::sethost' => 
    array (
      0 => 'void',
      'host' => 'string',
    ),
    'amqpconnection::setkey' => 
    array (
      0 => 'void',
      'key' => 'null|string',
    ),
    'amqpconnection::setlogin' => 
    array (
      0 => 'void',
      'login' => 'string',
    ),
    'amqpconnection::setpassword' => 
    array (
      0 => 'void',
      'password' => 'string',
    ),
    'amqpconnection::setport' => 
    array (
      0 => 'void',
      'port' => 'int',
    ),
    'amqpconnection::setreadtimeout' => 
    array (
      0 => 'void',
      'timeout' => 'float',
    ),
    'amqpconnection::settimeout' => 
    array (
      0 => 'void',
      'timeout' => 'float',
    ),
    'amqpconnection::setverify' => 
    array (
      0 => 'void',
      'verify' => 'bool',
    ),
    'amqpconnection::setvhost' => 
    array (
      0 => 'void',
      'vhost' => 'string',
    ),
    'amqpconnection::setwritetimeout' => 
    array (
      0 => 'void',
      'timeout' => 'float',
    ),
    'amqpdecimal::getexponent' => 
    array (
      0 => 'int',
    ),
    'amqpdecimal::getsignificand' => 
    array (
      0 => 'int',
    ),
    'amqpenvelope::getappid' => 
    array (
      0 => 'null|string',
    ),
    'amqpenvelope::getbody' => 
    array (
      0 => 'string',
    ),
    'amqpenvelope::getclusterid' => 
    array (
      0 => 'null|string',
    ),
    'amqpenvelope::getconsumertag' => 
    array (
      0 => 'null|string',
    ),
    'amqpenvelope::getcontentencoding' => 
    array (
      0 => 'null|string',
    ),
    'amqpenvelope::getcontenttype' => 
    array (
      0 => 'null|string',
    ),
    'amqpenvelope::getcorrelationid' => 
    array (
      0 => 'null|string',
    ),
    'amqpenvelope::getdeliverymode' => 
    array (
      0 => 'int',
    ),
    'amqpenvelope::getdeliverytag' => 
    array (
      0 => 'int|null',
    ),
    'amqpenvelope::getexchangename' => 
    array (
      0 => 'null|string',
    ),
    'amqpenvelope::getexpiration' => 
    array (
      0 => 'null|string',
    ),
    'amqpenvelope::getheader' => 
    array (
      0 => 'false|string',
      'headerName' => 'string',
    ),
    'amqpenvelope::getheaders' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'amqpenvelope::getmessageid' => 
    array (
      0 => 'null|string',
    ),
    'amqpenvelope::getpriority' => 
    array (
      0 => 'int',
    ),
    'amqpenvelope::getreplyto' => 
    array (
      0 => 'null|string',
    ),
    'amqpenvelope::getroutingkey' => 
    array (
      0 => 'string',
    ),
    'amqpenvelope::gettimestamp' => 
    array (
      0 => 'int|null',
    ),
    'amqpenvelope::gettype' => 
    array (
      0 => 'null|string',
    ),
    'amqpenvelope::getuserid' => 
    array (
      0 => 'null|string',
    ),
    'amqpenvelope::hasheader' => 
    array (
      0 => 'bool',
      'headerName' => 'string',
    ),
    'amqpenvelope::isredelivery' => 
    array (
      0 => 'bool',
    ),
    'amqpexchange::bind' => 
    array (
      0 => 'void',
      'exchangeName' => 'string',
      'routingKey=' => 'null|string',
      'arguments=' => 'array<array-key, mixed>',
    ),
    'amqpexchange::declareexchange' => 
    array (
      0 => 'void',
    ),
    'amqpexchange::delete' => 
    array (
      0 => 'void',
      'exchangeName=' => 'null|string',
      'flags=' => 'int|null',
    ),
    'amqpexchange::getargument' => 
    array (
      0 => 'false|int|string',
      'argumentName' => 'string',
    ),
    'amqpexchange::getarguments' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'amqpexchange::getchannel' => 
    array (
      0 => 'AMQPChannel',
    ),
    'amqpexchange::getconnection' => 
    array (
      0 => 'AMQPConnection',
    ),
    'amqpexchange::getflags' => 
    array (
      0 => 'int',
    ),
    'amqpexchange::getname' => 
    array (
      0 => 'null|string',
    ),
    'amqpexchange::gettype' => 
    array (
      0 => 'null|string',
    ),
    'amqpexchange::hasargument' => 
    array (
      0 => 'bool',
      'argumentName' => 'string',
    ),
    'amqpexchange::publish' => 
    array (
      0 => 'void',
      'message' => 'string',
      'routingKey=' => 'null|string',
      'flags=' => 'int|null',
      'headers=' => 'array<array-key, mixed>',
    ),
    'amqpexchange::setargument' => 
    array (
      0 => 'void',
      'argumentName' => 'string',
      'argumentValue' => 'int|string',
    ),
    'amqpexchange::setarguments' => 
    array (
      0 => 'void',
      'arguments' => 'array<array-key, mixed>',
    ),
    'amqpexchange::setflags' => 
    array (
      0 => 'void',
      'flags' => 'int|null',
    ),
    'amqpexchange::setname' => 
    array (
      0 => 'void',
      'exchangeName' => 'null|string',
    ),
    'amqpexchange::settype' => 
    array (
      0 => 'void',
      'exchangeType' => 'null|string',
    ),
    'amqpexchange::unbind' => 
    array (
      0 => 'void',
      'exchangeName' => 'string',
      'routingKey=' => 'null|string',
      'arguments=' => 'array<array-key, mixed>',
    ),
    'amqpqueue::ack' => 
    array (
      0 => 'void',
      'deliveryTag' => 'int',
      'flags=' => 'int|null',
    ),
    'amqpqueue::bind' => 
    array (
      0 => 'void',
      'exchangeName' => 'string',
      'routingKey=' => 'null|string',
      'arguments=' => 'array<array-key, mixed>',
    ),
    'amqpqueue::cancel' => 
    array (
      0 => 'void',
      'consumerTag=' => 'string',
    ),
    'amqpqueue::consume' => 
    array (
      0 => 'void',
      'callback=' => 'callable|null',
      'flags=' => 'int|null',
      'consumerTag=' => 'null|string',
    ),
    'amqpqueue::declarequeue' => 
    array (
      0 => 'int',
    ),
    'amqpqueue::delete' => 
    array (
      0 => 'int',
      'flags=' => 'int|null',
    ),
    'amqpqueue::get' => 
    array (
      0 => 'AMQPEnvelope|null',
      'flags=' => 'int|null',
    ),
    'amqpqueue::getargument' => 
    array (
      0 => 'false|int|string',
      'argumentName' => 'string',
    ),
    'amqpqueue::getarguments' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'amqpqueue::getchannel' => 
    array (
      0 => 'AMQPChannel',
    ),
    'amqpqueue::getconnection' => 
    array (
      0 => 'AMQPConnection',
    ),
    'amqpqueue::getconsumertag' => 
    array (
      0 => 'null|string',
    ),
    'amqpqueue::getflags' => 
    array (
      0 => 'int',
    ),
    'amqpqueue::getname' => 
    array (
      0 => 'null|string',
    ),
    'amqpqueue::hasargument' => 
    array (
      0 => 'bool',
      'argumentName' => 'string',
    ),
    'amqpqueue::nack' => 
    array (
      0 => 'void',
      'deliveryTag' => 'int',
      'flags=' => 'int|null',
    ),
    'amqpqueue::purge' => 
    array (
      0 => 'int',
    ),
    'amqpqueue::reject' => 
    array (
      0 => 'void',
      'deliveryTag' => 'int',
      'flags=' => 'int|null',
    ),
    'amqpqueue::setargument' => 
    array (
      0 => 'void',
      'argumentName' => 'string',
      'argumentValue' => 'mixed',
    ),
    'amqpqueue::setarguments' => 
    array (
      0 => 'void',
      'arguments' => 'array<array-key, mixed>',
    ),
    'amqpqueue::setflags' => 
    array (
      0 => 'void',
      'flags' => 'int|null',
    ),
    'amqpqueue::setname' => 
    array (
      0 => 'void',
      'name' => 'string',
    ),
    'amqpqueue::unbind' => 
    array (
      0 => 'void',
      'exchangeName' => 'string',
      'routingKey=' => 'null|string',
      'arguments=' => 'array<array-key, mixed>',
    ),
    'amqptimestamp::__construct' => 
    array (
      0 => 'void',
      'timestamp' => 'float',
    ),
    'amqptimestamp::__tostring' => 
    array (
      0 => 'string',
    ),
    'amqptimestamp::gettimestamp' => 
    array (
      0 => 'float',
    ),
    'grpc\\call::__construct' => 
    array (
      0 => 'void',
      'channel' => 'Grpc\\Channel',
      'method' => 'string',
      'deadline' => 'Grpc\\Timeval',
      'host_override=' => 'mixed',
    ),
    'grpc\\call::getpeer' => 
    array (
      0 => 'string',
    ),
    'grpc\\call::setcredentials' => 
    array (
      0 => 'int',
      'credentials' => 'Grpc\\CallCredentials',
    ),
    'grpc\\call::startbatch' => 
    array (
      0 => 'object',
      'ops' => 'array<array-key, mixed>',
    ),
    'grpc\\callcredentials::createcomposite' => 
    array (
      0 => 'Grpc\\CallCredentials',
      'creds1' => 'Grpc\\CallCredentials',
      'creds2' => 'Grpc\\CallCredentials',
    ),
    'grpc\\callcredentials::createfromplugin' => 
    array (
      0 => 'Grpc\\CallCredentials',
      'callback' => 'Closure',
    ),
    'grpc\\channel::__construct' => 
    array (
      0 => 'void',
      'target' => 'string',
      'args' => 'array<array-key, mixed>',
    ),
    'grpc\\channel::getconnectivitystate' => 
    array (
      0 => 'int',
      'try_to_connect=' => 'bool',
    ),
    'grpc\\channel::gettarget' => 
    array (
      0 => 'string',
    ),
    'grpc\\channel::watchconnectivitystate' => 
    array (
      0 => 'bool',
      'last_state' => 'int',
      'deadline' => 'Grpc\\Timeval',
    ),
    'grpc\\channelcredentials::createcomposite' => 
    array (
      0 => 'Grpc\\ChannelCredentials',
      'channel_creds' => 'Grpc\\ChannelCredentials',
      'call_creds' => 'Grpc\\CallCredentials',
    ),
    'grpc\\channelcredentials::createdefault' => 
    array (
      0 => 'Grpc\\ChannelCredentials',
    ),
    'grpc\\channelcredentials::createinsecure' => 
    array (
      0 => 'null',
    ),
    'grpc\\channelcredentials::createssl' => 
    array (
      0 => 'Grpc\\ChannelCredentials',
      'pem_root_certs=' => 'string',
      'pem_private_key=' => 'string',
      'pem_cert_chain=' => 'string',
    ),
    'grpc\\channelcredentials::setdefaultrootspem' => 
    array (
      0 => 'mixed',
      'pem_roots' => 'string',
    ),
    'grpc\\server::__construct' => 
    array (
      0 => 'void',
      'args=' => 'array<array-key, mixed>',
    ),
    'grpc\\server::addhttp2port' => 
    array (
      0 => 'bool',
      'addr' => 'string',
    ),
    'grpc\\server::addsecurehttp2port' => 
    array (
      0 => 'bool',
      'addr' => 'string',
      'server_creds' => 'Grpc\\ServerCredentials',
    ),
    'grpc\\servercredentials::createssl' => 
    array (
      0 => 'object',
      'pem_root_certs' => 'string',
      'pem_private_key' => 'string',
      'pem_cert_chain' => 'string',
    ),
    'grpc\\timeval::__construct' => 
    array (
      0 => 'void',
      'microseconds' => 'int',
    ),
    'grpc\\timeval::add' => 
    array (
      0 => 'Grpc\\Timeval',
      'timeval' => 'Grpc\\Timeval',
    ),
    'grpc\\timeval::compare' => 
    array (
      0 => 'int',
      'a_timeval' => 'Grpc\\Timeval',
      'b_timeval' => 'Grpc\\Timeval',
    ),
    'grpc\\timeval::inffuture' => 
    array (
      0 => 'Grpc\\Timeval',
    ),
    'grpc\\timeval::infpast' => 
    array (
      0 => 'Grpc\\Timeval',
    ),
    'grpc\\timeval::now' => 
    array (
      0 => 'Grpc\\Timeval',
    ),
    'grpc\\timeval::similar' => 
    array (
      0 => 'bool',
      'a_timeval' => 'Grpc\\Timeval',
      'b_timeval' => 'Grpc\\Timeval',
      'threshold_timeval' => 'Grpc\\Timeval',
    ),
    'grpc\\timeval::subtract' => 
    array (
      0 => 'Grpc\\Timeval',
      'timeval' => 'Grpc\\Timeval',
    ),
    'grpc\\timeval::zero' => 
    array (
      0 => 'Grpc\\Timeval',
    ),
    'igbinary_serialize' => 
    array (
      0 => 'false|string',
      'value' => 'mixed',
    ),
    'igbinary_unserialize' => 
    array (
      0 => 'mixed',
      'str' => 'string',
    ),
    'imagick::__construct' => 
    array (
      0 => 'void',
      'files=' => 'array<array-key, string>|null|string',
    ),
    'imagick::__tostring' => 
    array (
      0 => 'string',
    ),
    'imagick::adaptiveblurimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'channel=' => 'int',
    ),
    'imagick::adaptiveresizeimage' => 
    array (
      0 => 'bool',
      'columns' => 'int',
      'rows' => 'int',
      'bestfit=' => 'bool',
      'legacy=' => 'bool',
    ),
    'imagick::adaptivesharpenimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'channel=' => 'int',
    ),
    'imagick::adaptivethresholdimage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'offset' => 'int',
    ),
    'imagick::addimage' => 
    array (
      0 => 'bool',
      'image' => 'Imagick',
    ),
    'imagick::addnoiseimage' => 
    array (
      0 => 'bool',
      'noise' => 'int',
      'channel=' => 'int',
    ),
    'imagick::affinetransformimage' => 
    array (
      0 => 'bool',
      'settings' => 'ImagickDraw',
    ),
    'imagick::animateimages' => 
    array (
      0 => 'bool',
      'x_server' => 'string',
    ),
    'imagick::annotateimage' => 
    array (
      0 => 'bool',
      'settings' => 'ImagickDraw',
      'x' => 'float',
      'y' => 'float',
      'angle' => 'float',
      'text' => 'string',
    ),
    'imagick::appendimages' => 
    array (
      0 => 'Imagick',
      'stack' => 'bool',
    ),
    'imagick::autogammaimage' => 
    array (
      0 => 'void',
      'channel=' => 'int|null',
    ),
    'imagick::autolevelimage' => 
    array (
      0 => 'bool',
      'channel=' => 'int',
    ),
    'imagick::autoorient' => 
    array (
      0 => 'void',
    ),
    'imagick::averageimages' => 
    array (
      0 => 'Imagick',
    ),
    'imagick::blackthresholdimage' => 
    array (
      0 => 'bool',
      'threshold_color' => 'ImagickPixel|string',
    ),
    'imagick::blueshiftimage' => 
    array (
      0 => 'bool',
      'factor=' => 'float',
    ),
    'imagick::blurimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'channel=' => 'int',
    ),
    'imagick::borderimage' => 
    array (
      0 => 'bool',
      'border_color' => 'ImagickPixel|string',
      'width' => 'int',
      'height' => 'int',
    ),
    'imagick::brightnesscontrastimage' => 
    array (
      0 => 'bool',
      'brightness' => 'float',
      'contrast' => 'float',
      'channel=' => 'int',
    ),
    'imagick::charcoalimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
    ),
    'imagick::chopimage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'x' => 'int',
      'y' => 'int',
    ),
    'imagick::clampimage' => 
    array (
      0 => 'bool',
      'channel=' => 'int',
    ),
    'imagick::clear' => 
    array (
      0 => 'bool',
    ),
    'imagick::clipimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::clipimagepath' => 
    array (
      0 => 'void',
      'pathname' => 'string',
      'inside' => 'bool',
    ),
    'imagick::clippathimage' => 
    array (
      0 => 'bool',
      'pathname' => 'string',
      'inside' => 'bool',
    ),
    'imagick::clone' => 
    array (
      0 => 'Imagick',
    ),
    'imagick::clutimage' => 
    array (
      0 => 'bool',
      'lookup_table' => 'Imagick',
      'channel=' => 'int',
    ),
    'imagick::coalesceimages' => 
    array (
      0 => 'Imagick',
    ),
    'imagick::colorizeimage' => 
    array (
      0 => 'bool',
      'colorize_color' => 'ImagickPixel|string',
      'opacity_color' => 'ImagickPixel|false|string',
      'legacy=' => 'bool|null',
    ),
    'imagick::colormatriximage' => 
    array (
      0 => 'bool',
      'color_matrix' => 'array<array-key, mixed>',
    ),
    'imagick::combineimages' => 
    array (
      0 => 'Imagick',
      'colorspace' => 'int',
    ),
    'imagick::commentimage' => 
    array (
      0 => 'bool',
      'comment' => 'string',
    ),
    'imagick::compareimagechannels' => 
    array (
      0 => 'list{Imagick, float}',
      'reference' => 'Imagick',
      'channel' => 'int',
      'metric' => 'int',
    ),
    'imagick::compareimagelayers' => 
    array (
      0 => 'Imagick',
      'metric' => 'int',
    ),
    'imagick::compareimages' => 
    array (
      0 => 'list{Imagick, float}',
      'reference' => 'Imagick',
      'metric' => 'int',
    ),
    'imagick::compositeimage' => 
    array (
      0 => 'bool',
      'composite_image' => 'Imagick',
      'composite' => 'int',
      'x' => 'int',
      'y' => 'int',
      'channel=' => 'int',
    ),
    'imagick::compositeimagegravity' => 
    array (
      0 => 'bool',
      'image' => 'Imagick',
      'composite_constant' => 'int',
      'gravity' => 'int',
    ),
    'imagick::contrastimage' => 
    array (
      0 => 'bool',
      'sharpen' => 'bool',
    ),
    'imagick::contraststretchimage' => 
    array (
      0 => 'bool',
      'black_point' => 'float',
      'white_point' => 'float',
      'channel=' => 'int',
    ),
    'imagick::convolveimage' => 
    array (
      0 => 'bool',
      'kernel' => 'ImagickKernel',
      'channel=' => 'int',
    ),
    'imagick::count' => 
    array (
      0 => 'int',
      'mode=' => 'int',
    ),
    'imagick::cropimage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'x' => 'int',
      'y' => 'int',
    ),
    'imagick::cropthumbnailimage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'legacy=' => 'bool',
    ),
    'imagick::current' => 
    array (
      0 => 'Imagick',
    ),
    'imagick::cyclecolormapimage' => 
    array (
      0 => 'bool',
      'displace' => 'int',
    ),
    'imagick::decipherimage' => 
    array (
      0 => 'bool',
      'passphrase' => 'string',
    ),
    'imagick::deconstructimages' => 
    array (
      0 => 'Imagick',
    ),
    'imagick::deleteimageartifact' => 
    array (
      0 => 'bool',
      'artifact' => 'string',
    ),
    'imagick::deleteimageproperty' => 
    array (
      0 => 'bool',
      'name' => 'string',
    ),
    'imagick::deskewimage' => 
    array (
      0 => 'bool',
      'threshold' => 'float',
    ),
    'imagick::despeckleimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::destroy' => 
    array (
      0 => 'bool',
    ),
    'imagick::displayimage' => 
    array (
      0 => 'bool',
      'servername' => 'string',
    ),
    'imagick::displayimages' => 
    array (
      0 => 'bool',
      'servername' => 'string',
    ),
    'imagick::distortimage' => 
    array (
      0 => 'bool',
      'distortion' => 'int',
      'arguments' => 'array<array-key, mixed>',
      'bestfit' => 'bool',
    ),
    'imagick::drawimage' => 
    array (
      0 => 'bool',
      'drawing' => 'ImagickDraw',
    ),
    'imagick::edgeimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
    ),
    'imagick::embossimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
    ),
    'imagick::encipherimage' => 
    array (
      0 => 'bool',
      'passphrase' => 'string',
    ),
    'imagick::enhanceimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::equalizeimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::evaluateimage' => 
    array (
      0 => 'bool',
      'evaluate' => 'int',
      'constant' => 'float',
      'channel=' => 'int',
    ),
    'imagick::evaluateimages' => 
    array (
      0 => 'Imagick',
      'evaluate' => 'int',
    ),
    'imagick::exportimagepixels' => 
    array (
      0 => 'list<int>',
      'x' => 'int',
      'y' => 'int',
      'width' => 'int',
      'height' => 'int',
      'map' => 'string',
      'pixelstorage' => 'int',
    ),
    'imagick::extentimage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'x' => 'int',
      'y' => 'int',
    ),
    'imagick::flattenimages' => 
    array (
      0 => 'Imagick',
    ),
    'imagick::flipimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::floodfillpaintimage' => 
    array (
      0 => 'bool',
      'fill_color' => 'ImagickPixel|string',
      'fuzz' => 'float',
      'border_color' => 'ImagickPixel|string',
      'x' => 'int',
      'y' => 'int',
      'invert' => 'bool',
      'channel=' => 'int|null',
    ),
    'imagick::flopimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::forwardfouriertransformimage' => 
    array (
      0 => 'bool',
      'magnitude' => 'bool',
    ),
    'imagick::frameimage' => 
    array (
      0 => 'bool',
      'matte_color' => 'ImagickPixel|string',
      'width' => 'int',
      'height' => 'int',
      'inner_bevel' => 'int',
      'outer_bevel' => 'int',
    ),
    'imagick::functionimage' => 
    array (
      0 => 'bool',
      'function' => 'int',
      'parameters' => 'array<array-key, mixed>',
      'channel=' => 'int',
    ),
    'imagick::fximage' => 
    array (
      0 => 'Imagick',
      'expression' => 'string',
      'channel=' => 'int',
    ),
    'imagick::gammaimage' => 
    array (
      0 => 'bool',
      'gamma' => 'float',
      'channel=' => 'int',
    ),
    'imagick::gaussianblurimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'channel=' => 'int',
    ),
    'imagick::getcolorspace' => 
    array (
      0 => 'int',
    ),
    'imagick::getcompression' => 
    array (
      0 => 'int',
    ),
    'imagick::getcompressionquality' => 
    array (
      0 => 'int',
    ),
    'imagick::getconfigureoptions' => 
    array (
      0 => 'array<array-key, mixed>',
      'pattern=' => 'string',
    ),
    'imagick::getcopyright' => 
    array (
      0 => 'string',
    ),
    'imagick::getfeatures' => 
    array (
      0 => 'string',
    ),
    'imagick::getfilename' => 
    array (
      0 => 'string',
    ),
    'imagick::getfont' => 
    array (
      0 => 'string',
    ),
    'imagick::getformat' => 
    array (
      0 => 'string',
    ),
    'imagick::getgravity' => 
    array (
      0 => 'int',
    ),
    'imagick::gethdrienabled' => 
    array (
      0 => 'bool',
    ),
    'imagick::gethomeurl' => 
    array (
      0 => 'string',
    ),
    'imagick::getimage' => 
    array (
      0 => 'Imagick',
    ),
    'imagick::getimagealphachannel' => 
    array (
      0 => 'bool',
    ),
    'imagick::getimageartifact' => 
    array (
      0 => 'null|string',
      'artifact' => 'string',
    ),
    'imagick::getimagebackgroundcolor' => 
    array (
      0 => 'ImagickPixel',
    ),
    'imagick::getimageblob' => 
    array (
      0 => 'string',
    ),
    'imagick::getimageblueprimary' => 
    array (
      0 => 'array{x: float, y: float}',
    ),
    'imagick::getimagebordercolor' => 
    array (
      0 => 'ImagickPixel',
    ),
    'imagick::getimagechanneldepth' => 
    array (
      0 => 'int',
      'channel' => 'int',
    ),
    'imagick::getimagechanneldistortion' => 
    array (
      0 => 'float',
      'reference' => 'Imagick',
      'channel' => 'int',
      'metric' => 'int',
    ),
    'imagick::getimagechanneldistortions' => 
    array (
      0 => 'float',
      'reference_image' => 'Imagick',
      'metric' => 'int',
      'channel=' => 'int',
    ),
    'imagick::getimagechannelkurtosis' => 
    array (
      0 => 'array{kurtosis: float, skewness: float}',
      'channel=' => 'int',
    ),
    'imagick::getimagechannelmean' => 
    array (
      0 => 'array{mean: float, standardDeviation: float}',
      'channel' => 'int',
    ),
    'imagick::getimagechannelrange' => 
    array (
      0 => 'array{maxima: float, minima: float}',
      'channel' => 'int',
    ),
    'imagick::getimagechannelstatistics' => 
    array (
      0 => 'array<int, array{depth: int, maxima: float, mean: float, minima: float, standardDeviation: float}>',
    ),
    'imagick::getimagecolormapcolor' => 
    array (
      0 => 'ImagickPixel',
      'index' => 'int',
    ),
    'imagick::getimagecolors' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagecolorspace' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagecompose' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagecompression' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagecompressionquality' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagedelay' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagedepth' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagedispose' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagedistortion' => 
    array (
      0 => 'float',
      'reference' => 'Imagick',
      'metric' => 'int',
    ),
    'imagick::getimagefilename' => 
    array (
      0 => 'string',
    ),
    'imagick::getimageformat' => 
    array (
      0 => 'string',
    ),
    'imagick::getimagegamma' => 
    array (
      0 => 'float',
    ),
    'imagick::getimagegeometry' => 
    array (
      0 => 'array{height: int, width: int}',
    ),
    'imagick::getimagegravity' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagegreenprimary' => 
    array (
      0 => 'array{x: float, y: float}',
    ),
    'imagick::getimageheight' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagehistogram' => 
    array (
      0 => 'list<ImagickPixel>',
    ),
    'imagick::getimageindex' => 
    array (
      0 => 'int',
    ),
    'imagick::getimageinterlacescheme' => 
    array (
      0 => 'int',
    ),
    'imagick::getimageinterpolatemethod' => 
    array (
      0 => 'int',
    ),
    'imagick::getimageiterations' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagelength' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagemimetype' => 
    array (
      0 => 'string',
    ),
    'imagick::getimageorientation' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagepage' => 
    array (
      0 => 'array{height: int, width: int, x: int, y: int}',
    ),
    'imagick::getimagepixelcolor' => 
    array (
      0 => 'ImagickPixel',
      'x' => 'int',
      'y' => 'int',
    ),
    'imagick::getimageprofile' => 
    array (
      0 => 'string',
      'name' => 'string',
    ),
    'imagick::getimageprofiles' => 
    array (
      0 => 'array<array-key, mixed>',
      'pattern=' => 'string',
      'include_values=' => 'bool',
    ),
    'imagick::getimageproperties' => 
    array (
      0 => 'array<int|string, string>',
      'pattern=' => 'string',
      'include_values=' => 'bool',
    ),
    'imagick::getimageproperty' => 
    array (
      0 => 'string',
      'name' => 'string',
    ),
    'imagick::getimageredprimary' => 
    array (
      0 => 'array{x: float, y: float}',
    ),
    'imagick::getimageregion' => 
    array (
      0 => 'Imagick',
      'width' => 'int',
      'height' => 'int',
      'x' => 'int',
      'y' => 'int',
    ),
    'imagick::getimagerenderingintent' => 
    array (
      0 => 'int',
    ),
    'imagick::getimageresolution' => 
    array (
      0 => 'array{x: float, y: float}',
    ),
    'imagick::getimagesblob' => 
    array (
      0 => 'string',
    ),
    'imagick::getimagescene' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagesignature' => 
    array (
      0 => 'string',
    ),
    'imagick::getimagesize' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagetickspersecond' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagetotalinkdensity' => 
    array (
      0 => 'float',
    ),
    'imagick::getimagetype' => 
    array (
      0 => 'int',
    ),
    'imagick::getimageunits' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagevirtualpixelmethod' => 
    array (
      0 => 'int',
    ),
    'imagick::getimagewhitepoint' => 
    array (
      0 => 'array{x: float, y: float}',
    ),
    'imagick::getimagewidth' => 
    array (
      0 => 'int',
    ),
    'imagick::getinterlacescheme' => 
    array (
      0 => 'int',
    ),
    'imagick::getiteratorindex' => 
    array (
      0 => 'int',
    ),
    'imagick::getnumberimages' => 
    array (
      0 => 'int',
    ),
    'imagick::getoption' => 
    array (
      0 => 'string',
      'key' => 'string',
    ),
    'imagick::getpackagename' => 
    array (
      0 => 'string',
    ),
    'imagick::getpage' => 
    array (
      0 => 'array{height: int, width: int, x: int, y: int}',
    ),
    'imagick::getpixeliterator' => 
    array (
      0 => 'ImagickPixelIterator',
    ),
    'imagick::getpixelregioniterator' => 
    array (
      0 => 'ImagickPixelIterator',
      'x' => 'int',
      'y' => 'int',
      'columns' => 'int',
      'rows' => 'int',
    ),
    'imagick::getpointsize' => 
    array (
      0 => 'float',
    ),
    'imagick::getquantum' => 
    array (
      0 => 'int',
    ),
    'imagick::getquantumdepth' => 
    array (
      0 => 'array{quantumDepthLong: int, quantumDepthString: string}',
    ),
    'imagick::getquantumrange' => 
    array (
      0 => 'array{quantumRangeLong: int, quantumRangeString: string}',
    ),
    'imagick::getregistry' => 
    array (
      0 => 'false|string',
      'key' => 'string',
    ),
    'imagick::getreleasedate' => 
    array (
      0 => 'string',
    ),
    'imagick::getresource' => 
    array (
      0 => 'int',
      'type' => 'int',
    ),
    'imagick::getresourcelimit' => 
    array (
      0 => 'float',
      'type' => 'int',
    ),
    'imagick::getsamplingfactors' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'imagick::getsize' => 
    array (
      0 => 'array{columns: int, rows: int}',
    ),
    'imagick::getsizeoffset' => 
    array (
      0 => 'int',
    ),
    'imagick::getversion' => 
    array (
      0 => 'array{versionNumber: int, versionString: string}',
    ),
    'imagick::haldclutimage' => 
    array (
      0 => 'bool',
      'clut' => 'Imagick',
      'channel=' => 'int',
    ),
    'imagick::hasnextimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::haspreviousimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::identifyformat' => 
    array (
      0 => 'string',
      'format' => 'string',
    ),
    'imagick::identifyimage' => 
    array (
      0 => 'array<string, mixed>',
      'append_raw_output=' => 'bool',
    ),
    'imagick::identifyimagetype' => 
    array (
      0 => 'int',
    ),
    'imagick::implodeimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
    ),
    'imagick::importimagepixels' => 
    array (
      0 => 'bool',
      'x' => 'int',
      'y' => 'int',
      'width' => 'int',
      'height' => 'int',
      'map' => 'string',
      'pixelstorage' => 'int',
      'pixels' => 'list<int>',
    ),
    'imagick::inversefouriertransformimage' => 
    array (
      0 => 'bool',
      'complement' => 'Imagick',
      'magnitude' => 'bool',
    ),
    'imagick::key' => 
    array (
      0 => 'int',
    ),
    'imagick::labelimage' => 
    array (
      0 => 'bool',
      'label' => 'string',
    ),
    'imagick::levelimage' => 
    array (
      0 => 'bool',
      'black_point' => 'float',
      'gamma' => 'float',
      'white_point' => 'float',
      'channel=' => 'int',
    ),
    'imagick::linearstretchimage' => 
    array (
      0 => 'bool',
      'black_point' => 'float',
      'white_point' => 'float',
    ),
    'imagick::liquidrescaleimage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'delta_x' => 'float',
      'rigidity' => 'float',
    ),
    'imagick::listregistry' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'imagick::localcontrastimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'strength' => 'float',
    ),
    'imagick::magnifyimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::mergeimagelayers' => 
    array (
      0 => 'Imagick',
      'layermethod' => 'int',
    ),
    'imagick::minifyimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::modulateimage' => 
    array (
      0 => 'bool',
      'brightness' => 'float',
      'saturation' => 'float',
      'hue' => 'float',
    ),
    'imagick::montageimage' => 
    array (
      0 => 'Imagick',
      'settings' => 'ImagickDraw',
      'tile_geometry' => 'string',
      'thumbnail_geometry' => 'string',
      'monatgemode' => 'int',
      'frame' => 'string',
    ),
    'imagick::morphimages' => 
    array (
      0 => 'Imagick',
      'number_frames' => 'int',
    ),
    'imagick::morphology' => 
    array (
      0 => 'bool',
      'morphology' => 'int',
      'iterations' => 'int',
      'kernel' => 'ImagickKernel',
      'channel=' => 'int',
    ),
    'imagick::motionblurimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'angle' => 'float',
      'channel=' => 'int',
    ),
    'imagick::negateimage' => 
    array (
      0 => 'bool',
      'gray' => 'bool',
      'channel=' => 'int',
    ),
    'imagick::newimage' => 
    array (
      0 => 'bool',
      'columns' => 'int',
      'rows' => 'int',
      'background_color' => 'ImagickPixel|string',
      'format=' => 'null|string',
    ),
    'imagick::newpseudoimage' => 
    array (
      0 => 'bool',
      'columns' => 'int',
      'rows' => 'int',
      'pseudo_format' => 'string',
    ),
    'imagick::next' => 
    array (
      0 => 'void',
    ),
    'imagick::nextimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::normalizeimage' => 
    array (
      0 => 'bool',
      'channel=' => 'int',
    ),
    'imagick::oilpaintimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
    ),
    'imagick::opaquepaintimage' => 
    array (
      0 => 'bool',
      'target_color' => 'ImagickPixel|string',
      'fill_color' => 'ImagickPixel|string',
      'fuzz' => 'float',
      'invert' => 'bool',
      'channel=' => 'int',
    ),
    'imagick::optimizeimagelayers' => 
    array (
      0 => 'Imagick',
    ),
    'imagick::pingimage' => 
    array (
      0 => 'bool',
      'filename' => 'string',
    ),
    'imagick::pingimageblob' => 
    array (
      0 => 'bool',
      'image' => 'string',
    ),
    'imagick::pingimagefile' => 
    array (
      0 => 'bool',
      'filehandle' => 'resource',
      'filename=' => 'null|string',
    ),
    'imagick::polaroidimage' => 
    array (
      0 => 'bool',
      'settings' => 'ImagickDraw',
      'angle' => 'float',
    ),
    'imagick::posterizeimage' => 
    array (
      0 => 'bool',
      'levels' => 'int',
      'dither' => 'bool',
    ),
    'imagick::previewimages' => 
    array (
      0 => 'bool',
      'preview' => 'int',
    ),
    'imagick::previousimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::profileimage' => 
    array (
      0 => 'bool',
      'name' => 'string',
      'profile' => 'null|string',
    ),
    'imagick::quantizeimage' => 
    array (
      0 => 'bool',
      'number_colors' => 'int',
      'colorspace' => 'int',
      'tree_depth' => 'int',
      'dither' => 'bool',
      'measure_error' => 'bool',
    ),
    'imagick::quantizeimages' => 
    array (
      0 => 'bool',
      'number_colors' => 'int',
      'colorspace' => 'int',
      'tree_depth' => 'int',
      'dither' => 'bool',
      'measure_error' => 'bool',
    ),
    'imagick::queryfontmetrics' => 
    array (
      0 => 'array<array-key, mixed>',
      'settings' => 'ImagickDraw',
      'text' => 'string',
      'multiline=' => 'bool|null',
    ),
    'imagick::queryfonts' => 
    array (
      0 => 'array<array-key, mixed>',
      'pattern=' => 'string',
    ),
    'imagick::queryformats' => 
    array (
      0 => 'list<string>',
      'pattern=' => 'string',
    ),
    'imagick::raiseimage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'x' => 'int',
      'y' => 'int',
      'raise' => 'bool',
    ),
    'imagick::randomthresholdimage' => 
    array (
      0 => 'bool',
      'low' => 'float',
      'high' => 'float',
      'channel=' => 'int',
    ),
    'imagick::readimage' => 
    array (
      0 => 'bool',
      'filename' => 'string',
    ),
    'imagick::readimageblob' => 
    array (
      0 => 'bool',
      'image' => 'string',
      'filename=' => 'null|string',
    ),
    'imagick::readimagefile' => 
    array (
      0 => 'bool',
      'filehandle' => 'resource',
      'filename=' => 'null|string',
    ),
    'imagick::readimages' => 
    array (
      0 => 'bool',
      'filenames' => 'array<array-key, mixed>',
    ),
    'imagick::remapimage' => 
    array (
      0 => 'bool',
      'replacement' => 'Imagick',
      'dither_method' => 'int',
    ),
    'imagick::removeimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::removeimageprofile' => 
    array (
      0 => 'string',
      'name' => 'string',
    ),
    'imagick::resampleimage' => 
    array (
      0 => 'bool',
      'x_resolution' => 'float',
      'y_resolution' => 'float',
      'filter' => 'int',
      'blur' => 'float',
    ),
    'imagick::resetimagepage' => 
    array (
      0 => 'bool',
      'page' => 'string',
    ),
    'imagick::resizeimage' => 
    array (
      0 => 'bool',
      'columns' => 'int',
      'rows' => 'int',
      'filter' => 'int',
      'blur' => 'float',
      'bestfit=' => 'bool',
      'legacy=' => 'bool',
    ),
    'imagick::rewind' => 
    array (
      0 => 'void',
    ),
    'imagick::rollimage' => 
    array (
      0 => 'bool',
      'x' => 'int',
      'y' => 'int',
    ),
    'imagick::rotateimage' => 
    array (
      0 => 'bool',
      'background_color' => 'ImagickPixel|string',
      'degrees' => 'float',
    ),
    'imagick::rotationalblurimage' => 
    array (
      0 => 'bool',
      'angle' => 'float',
      'channel=' => 'int',
    ),
    'imagick::roundcorners' => 
    array (
      0 => 'bool',
      'x_rounding' => 'float',
      'y_rounding' => 'float',
      'stroke_width=' => 'float',
      'displace=' => 'float',
      'size_correction=' => 'float',
    ),
    'imagick::sampleimage' => 
    array (
      0 => 'bool',
      'columns' => 'int',
      'rows' => 'int',
    ),
    'imagick::scaleimage' => 
    array (
      0 => 'bool',
      'columns' => 'int',
      'rows' => 'int',
      'bestfit=' => 'bool',
      'legacy=' => 'bool',
    ),
    'imagick::segmentimage' => 
    array (
      0 => 'bool',
      'colorspace' => 'int',
      'cluster_threshold' => 'float',
      'smooth_threshold' => 'float',
      'verbose=' => 'bool',
    ),
    'imagick::selectiveblurimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'threshold' => 'float',
      'channel=' => 'int',
    ),
    'imagick::separateimagechannel' => 
    array (
      0 => 'bool',
      'channel' => 'int',
    ),
    'imagick::sepiatoneimage' => 
    array (
      0 => 'bool',
      'threshold' => 'float',
    ),
    'imagick::setantialias' => 
    array (
      0 => 'void',
      'antialias' => 'bool',
    ),
    'imagick::setbackgroundcolor' => 
    array (
      0 => 'bool',
      'background_color' => 'ImagickPixel|string',
    ),
    'imagick::setcolorspace' => 
    array (
      0 => 'bool',
      'colorspace' => 'int',
    ),
    'imagick::setcompression' => 
    array (
      0 => 'bool',
      'compression' => 'int',
    ),
    'imagick::setcompressionquality' => 
    array (
      0 => 'bool',
      'quality' => 'int',
    ),
    'imagick::setfilename' => 
    array (
      0 => 'bool',
      'filename' => 'string',
    ),
    'imagick::setfirstiterator' => 
    array (
      0 => 'bool',
    ),
    'imagick::setfont' => 
    array (
      0 => 'bool',
      'font' => 'string',
    ),
    'imagick::setformat' => 
    array (
      0 => 'bool',
      'format' => 'string',
    ),
    'imagick::setgravity' => 
    array (
      0 => 'bool',
      'gravity' => 'int',
    ),
    'imagick::setimage' => 
    array (
      0 => 'bool',
      'image' => 'Imagick',
    ),
    'imagick::setimagealpha' => 
    array (
      0 => 'bool',
      'alpha' => 'float',
    ),
    'imagick::setimagealphachannel' => 
    array (
      0 => 'bool',
      'alphachannel' => 'int',
    ),
    'imagick::setimageartifact' => 
    array (
      0 => 'bool',
      'artifact' => 'string',
      'value' => 'null|string',
    ),
    'imagick::setimagebackgroundcolor' => 
    array (
      0 => 'bool',
      'background_color' => 'ImagickPixel|string',
    ),
    'imagick::setimageblueprimary' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
      'z' => 'float',
    ),
    'imagick::setimagebordercolor' => 
    array (
      0 => 'bool',
      'border_color' => 'ImagickPixel|string',
    ),
    'imagick::setimagechanneldepth' => 
    array (
      0 => 'bool',
      'channel' => 'int',
      'depth' => 'int',
    ),
    'imagick::setimagechannelmask' => 
    array (
      0 => 'int',
      'channel' => 'int',
    ),
    'imagick::setimagecolormapcolor' => 
    array (
      0 => 'bool',
      'index' => 'int',
      'color' => 'ImagickPixel',
    ),
    'imagick::setimagecolorspace' => 
    array (
      0 => 'bool',
      'colorspace' => 'int',
    ),
    'imagick::setimagecompose' => 
    array (
      0 => 'bool',
      'compose' => 'int',
    ),
    'imagick::setimagecompression' => 
    array (
      0 => 'bool',
      'compression' => 'int',
    ),
    'imagick::setimagecompressionquality' => 
    array (
      0 => 'bool',
      'quality' => 'int',
    ),
    'imagick::setimagedelay' => 
    array (
      0 => 'bool',
      'delay' => 'int',
    ),
    'imagick::setimagedepth' => 
    array (
      0 => 'bool',
      'depth' => 'int',
    ),
    'imagick::setimagedispose' => 
    array (
      0 => 'bool',
      'dispose' => 'int',
    ),
    'imagick::setimageextent' => 
    array (
      0 => 'bool',
      'columns' => 'int',
      'rows' => 'int',
    ),
    'imagick::setimagefilename' => 
    array (
      0 => 'bool',
      'filename' => 'string',
    ),
    'imagick::setimageformat' => 
    array (
      0 => 'bool',
      'format' => 'string',
    ),
    'imagick::setimagegamma' => 
    array (
      0 => 'bool',
      'gamma' => 'float',
    ),
    'imagick::setimagegravity' => 
    array (
      0 => 'bool',
      'gravity' => 'int',
    ),
    'imagick::setimagegreenprimary' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
      'z' => 'float',
    ),
    'imagick::setimageindex' => 
    array (
      0 => 'bool',
      'index' => 'int',
    ),
    'imagick::setimageinterlacescheme' => 
    array (
      0 => 'bool',
      'interlace' => 'int',
    ),
    'imagick::setimageinterpolatemethod' => 
    array (
      0 => 'bool',
      'method' => 'int',
    ),
    'imagick::setimageiterations' => 
    array (
      0 => 'bool',
      'iterations' => 'int',
    ),
    'imagick::setimagematte' => 
    array (
      0 => 'bool',
      'matte' => 'bool',
    ),
    'imagick::setimagemattecolor' => 
    array (
      0 => 'bool',
      'matte_color' => 'ImagickPixel|string',
    ),
    'imagick::setimageorientation' => 
    array (
      0 => 'bool',
      'orientation' => 'int',
    ),
    'imagick::setimagepage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'x' => 'int',
      'y' => 'int',
    ),
    'imagick::setimageprofile' => 
    array (
      0 => 'bool',
      'name' => 'string',
      'profile' => 'string',
    ),
    'imagick::setimageproperty' => 
    array (
      0 => 'bool',
      'name' => 'string',
      'value' => 'string',
    ),
    'imagick::setimageredprimary' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
      'z' => 'float',
    ),
    'imagick::setimagerenderingintent' => 
    array (
      0 => 'bool',
      'rendering_intent' => 'int',
    ),
    'imagick::setimageresolution' => 
    array (
      0 => 'bool',
      'x_resolution' => 'float',
      'y_resolution' => 'float',
    ),
    'imagick::setimagescene' => 
    array (
      0 => 'bool',
      'scene' => 'int',
    ),
    'imagick::setimagetickspersecond' => 
    array (
      0 => 'bool',
      'ticks_per_second' => 'int',
    ),
    'imagick::setimagetype' => 
    array (
      0 => 'bool',
      'image_type' => 'int',
    ),
    'imagick::setimageunits' => 
    array (
      0 => 'bool',
      'units' => 'int',
    ),
    'imagick::setimagevirtualpixelmethod' => 
    array (
      0 => 'bool',
      'method' => 'int',
    ),
    'imagick::setimagewhitepoint' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
      'z' => 'float',
    ),
    'imagick::setinterlacescheme' => 
    array (
      0 => 'bool',
      'interlace' => 'int',
    ),
    'imagick::setiteratorindex' => 
    array (
      0 => 'bool',
      'index' => 'int',
    ),
    'imagick::setlastiterator' => 
    array (
      0 => 'bool',
    ),
    'imagick::setoption' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'string',
    ),
    'imagick::setpage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'x' => 'int',
      'y' => 'int',
    ),
    'imagick::setpointsize' => 
    array (
      0 => 'bool',
      'point_size' => 'float',
    ),
    'imagick::setprogressmonitor' => 
    array (
      0 => 'bool',
      'callback' => 'callable',
    ),
    'imagick::setregistry' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'string',
    ),
    'imagick::setresolution' => 
    array (
      0 => 'bool',
      'x_resolution' => 'float',
      'y_resolution' => 'float',
    ),
    'imagick::setresourcelimit' => 
    array (
      0 => 'bool',
      'type' => 'int',
      'limit' => 'int',
    ),
    'imagick::setsamplingfactors' => 
    array (
      0 => 'bool',
      'factors' => 'list<string>',
    ),
    'imagick::setsize' => 
    array (
      0 => 'bool',
      'columns' => 'int',
      'rows' => 'int',
    ),
    'imagick::setsizeoffset' => 
    array (
      0 => 'bool',
      'columns' => 'int',
      'rows' => 'int',
      'offset' => 'int',
    ),
    'imagick::settype' => 
    array (
      0 => 'bool',
      'imgtype' => 'int',
    ),
    'imagick::shadeimage' => 
    array (
      0 => 'bool',
      'gray' => 'bool',
      'azimuth' => 'float',
      'elevation' => 'float',
    ),
    'imagick::shadowimage' => 
    array (
      0 => 'bool',
      'opacity' => 'float',
      'sigma' => 'float',
      'x' => 'int',
      'y' => 'int',
    ),
    'imagick::sharpenimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'channel=' => 'int',
    ),
    'imagick::shaveimage' => 
    array (
      0 => 'bool',
      'columns' => 'int',
      'rows' => 'int',
    ),
    'imagick::shearimage' => 
    array (
      0 => 'bool',
      'background_color' => 'ImagickPixel|string',
      'x_shear' => 'float',
      'y_shear' => 'float',
    ),
    'imagick::sigmoidalcontrastimage' => 
    array (
      0 => 'bool',
      'sharpen' => 'bool',
      'alpha' => 'float',
      'beta' => 'float',
      'channel=' => 'int',
    ),
    'imagick::similarityimage' => 
    array (
      0 => 'Imagick',
      'image' => 'Imagick',
      '&offset=' => 'array<array-key, mixed>',
      '&similarity=' => 'float',
      'threshold=' => 'float',
      'metric=' => 'int',
    ),
    'imagick::sketchimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'angle' => 'float',
    ),
    'imagick::smushimages' => 
    array (
      0 => 'Imagick',
      'stack' => 'bool',
      'offset' => 'int',
    ),
    'imagick::solarizeimage' => 
    array (
      0 => 'bool',
      'threshold' => 'int',
    ),
    'imagick::sparsecolorimage' => 
    array (
      0 => 'bool',
      'sparsecolormethod' => 'int',
      'arguments' => 'array<array-key, mixed>',
      'channel=' => 'int',
    ),
    'imagick::spliceimage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'x' => 'int',
      'y' => 'int',
    ),
    'imagick::spreadimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
    ),
    'imagick::statisticimage' => 
    array (
      0 => 'bool',
      'type' => 'int',
      'width' => 'int',
      'height' => 'int',
      'channel=' => 'int',
    ),
    'imagick::steganoimage' => 
    array (
      0 => 'Imagick',
      'watermark' => 'Imagick',
      'offset' => 'int',
    ),
    'imagick::stereoimage' => 
    array (
      0 => 'bool',
      'offset_image' => 'Imagick',
    ),
    'imagick::stripimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::subimagematch' => 
    array (
      0 => 'Imagick',
      'image' => 'Imagick',
      '&w offset=' => 'array<array-key, mixed>',
      '&w similarity=' => 'float',
      'threshold=' => 'float',
      'metric=' => 'int',
    ),
    'imagick::swirlimage' => 
    array (
      0 => 'bool',
      'degrees' => 'float',
    ),
    'imagick::textureimage' => 
    array (
      0 => 'Imagick',
      'texture' => 'Imagick',
    ),
    'imagick::thresholdimage' => 
    array (
      0 => 'bool',
      'threshold' => 'float',
      'channel=' => 'int',
    ),
    'imagick::thumbnailimage' => 
    array (
      0 => 'bool',
      'columns' => 'int|null',
      'rows' => 'int|null',
      'bestfit=' => 'bool',
      'fill=' => 'bool',
      'legacy=' => 'bool',
    ),
    'imagick::tintimage' => 
    array (
      0 => 'bool',
      'tint_color' => 'ImagickPixel|string',
      'opacity_color' => 'ImagickPixel|string',
      'legacy=' => 'bool',
    ),
    'imagick::transformimagecolorspace' => 
    array (
      0 => 'bool',
      'colorspace' => 'int',
    ),
    'imagick::transparentpaintimage' => 
    array (
      0 => 'bool',
      'target_color' => 'ImagickPixel|string',
      'alpha' => 'float',
      'fuzz' => 'float',
      'invert' => 'bool',
    ),
    'imagick::transposeimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::transverseimage' => 
    array (
      0 => 'bool',
    ),
    'imagick::trimimage' => 
    array (
      0 => 'bool',
      'fuzz' => 'float',
    ),
    'imagick::uniqueimagecolors' => 
    array (
      0 => 'bool',
    ),
    'imagick::unsharpmaskimage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'amount' => 'float',
      'threshold' => 'float',
      'channel=' => 'int',
    ),
    'imagick::valid' => 
    array (
      0 => 'bool',
    ),
    'imagick::vignetteimage' => 
    array (
      0 => 'bool',
      'black_point' => 'float',
      'white_point' => 'float',
      'x' => 'int',
      'y' => 'int',
    ),
    'imagick::waveimage' => 
    array (
      0 => 'bool',
      'amplitude' => 'float',
      'length' => 'float',
    ),
    'imagick::whitethresholdimage' => 
    array (
      0 => 'bool',
      'threshold_color' => 'ImagickPixel|string',
    ),
    'imagick::writeimage' => 
    array (
      0 => 'bool',
      'filename=' => 'null|string',
    ),
    'imagick::writeimagefile' => 
    array (
      0 => 'bool',
      'filehandle' => 'resource',
      'format=' => 'null|string',
    ),
    'imagick::writeimages' => 
    array (
      0 => 'bool',
      'filename' => 'string',
      'adjoin' => 'bool',
    ),
    'imagick::writeimagesfile' => 
    array (
      0 => 'bool',
      'filehandle' => 'resource',
      'format=' => 'null|string',
    ),
    'imagickdraw::affine' => 
    array (
      0 => 'bool',
      'affine' => 'array<string, float>',
    ),
    'imagickdraw::annotation' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
      'text' => 'string',
    ),
    'imagickdraw::arc' => 
    array (
      0 => 'bool',
      'start_x' => 'float',
      'start_y' => 'float',
      'end_x' => 'float',
      'end_y' => 'float',
      'start_angle' => 'float',
      'end_angle' => 'float',
    ),
    'imagickdraw::bezier' => 
    array (
      0 => 'bool',
      'coordinates' => 'list<array{x: float, y: float}>',
    ),
    'imagickdraw::circle' => 
    array (
      0 => 'bool',
      'origin_x' => 'float',
      'origin_y' => 'float',
      'perimeter_x' => 'float',
      'perimeter_y' => 'float',
    ),
    'imagickdraw::clear' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::clone' => 
    array (
      0 => 'ImagickDraw',
    ),
    'imagickdraw::color' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
      'paint' => 'int',
    ),
    'imagickdraw::comment' => 
    array (
      0 => 'bool',
      'comment' => 'string',
    ),
    'imagickdraw::composite' => 
    array (
      0 => 'bool',
      'composite' => 'int',
      'x' => 'float',
      'y' => 'float',
      'width' => 'float',
      'height' => 'float',
      'image' => 'Imagick',
    ),
    'imagickdraw::destroy' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::ellipse' => 
    array (
      0 => 'bool',
      'origin_x' => 'float',
      'origin_y' => 'float',
      'radius_x' => 'float',
      'radius_y' => 'float',
      'angle_start' => 'float',
      'angle_end' => 'float',
    ),
    'imagickdraw::getbordercolor' => 
    array (
      0 => 'ImagickPixel',
    ),
    'imagickdraw::getclippath' => 
    array (
      0 => 'false|string',
    ),
    'imagickdraw::getcliprule' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::getclipunits' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::getdensity' => 
    array (
      0 => 'null|string',
    ),
    'imagickdraw::getfillcolor' => 
    array (
      0 => 'ImagickPixel',
    ),
    'imagickdraw::getfillopacity' => 
    array (
      0 => 'float',
    ),
    'imagickdraw::getfillrule' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::getfont' => 
    array (
      0 => 'string',
    ),
    'imagickdraw::getfontfamily' => 
    array (
      0 => 'string',
    ),
    'imagickdraw::getfontresolution' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'imagickdraw::getfontsize' => 
    array (
      0 => 'float',
    ),
    'imagickdraw::getfontstretch' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::getfontstyle' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::getfontweight' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::getgravity' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::getopacity' => 
    array (
      0 => 'float',
    ),
    'imagickdraw::getstrokeantialias' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::getstrokecolor' => 
    array (
      0 => 'ImagickPixel',
    ),
    'imagickdraw::getstrokedasharray' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'imagickdraw::getstrokedashoffset' => 
    array (
      0 => 'float',
    ),
    'imagickdraw::getstrokelinecap' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::getstrokelinejoin' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::getstrokemiterlimit' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::getstrokeopacity' => 
    array (
      0 => 'float',
    ),
    'imagickdraw::getstrokewidth' => 
    array (
      0 => 'float',
    ),
    'imagickdraw::gettextalignment' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::gettextantialias' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::gettextdecoration' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::gettextdirection' => 
    array (
      0 => 'int',
    ),
    'imagickdraw::gettextencoding' => 
    array (
      0 => 'string',
    ),
    'imagickdraw::gettextinterlinespacing' => 
    array (
      0 => 'float',
    ),
    'imagickdraw::gettextinterwordspacing' => 
    array (
      0 => 'float',
    ),
    'imagickdraw::gettextkerning' => 
    array (
      0 => 'float',
    ),
    'imagickdraw::gettextundercolor' => 
    array (
      0 => 'ImagickPixel',
    ),
    'imagickdraw::getvectorgraphics' => 
    array (
      0 => 'string',
    ),
    'imagickdraw::line' => 
    array (
      0 => 'bool',
      'start_x' => 'float',
      'start_y' => 'float',
      'end_x' => 'float',
      'end_y' => 'float',
    ),
    'imagickdraw::pathclose' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::pathcurvetoabsolute' => 
    array (
      0 => 'bool',
      'x1' => 'float',
      'y1' => 'float',
      'x2' => 'float',
      'y2' => 'float',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::pathcurvetoquadraticbezierabsolute' => 
    array (
      0 => 'bool',
      'x1' => 'float',
      'y1' => 'float',
      'x_end' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::pathcurvetoquadraticbezierrelative' => 
    array (
      0 => 'bool',
      'x1' => 'float',
      'y1' => 'float',
      'x_end' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::pathcurvetoquadraticbeziersmoothabsolute' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::pathcurvetoquadraticbeziersmoothrelative' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::pathcurvetorelative' => 
    array (
      0 => 'bool',
      'x1' => 'float',
      'y1' => 'float',
      'x2' => 'float',
      'y2' => 'float',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::pathcurvetosmoothabsolute' => 
    array (
      0 => 'bool',
      'x2' => 'float',
      'y2' => 'float',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::pathcurvetosmoothrelative' => 
    array (
      0 => 'bool',
      'x2' => 'float',
      'y2' => 'float',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::pathellipticarcabsolute' => 
    array (
      0 => 'bool',
      'rx' => 'float',
      'ry' => 'float',
      'x_axis_rotation' => 'float',
      'large_arc' => 'bool',
      'sweep' => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::pathellipticarcrelative' => 
    array (
      0 => 'bool',
      'rx' => 'float',
      'ry' => 'float',
      'x_axis_rotation' => 'float',
      'large_arc' => 'bool',
      'sweep' => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::pathfinish' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::pathlinetoabsolute' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::pathlinetohorizontalabsolute' => 
    array (
      0 => 'bool',
      'x' => 'float',
    ),
    'imagickdraw::pathlinetohorizontalrelative' => 
    array (
      0 => 'bool',
      'x' => 'float',
    ),
    'imagickdraw::pathlinetorelative' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::pathlinetoverticalabsolute' => 
    array (
      0 => 'bool',
      'y' => 'float',
    ),
    'imagickdraw::pathlinetoverticalrelative' => 
    array (
      0 => 'bool',
      'y' => 'float',
    ),
    'imagickdraw::pathmovetoabsolute' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::pathmovetorelative' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::pathstart' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::point' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::polygon' => 
    array (
      0 => 'bool',
      'coordinates' => 'list<array{x: float, y: float}>',
    ),
    'imagickdraw::polyline' => 
    array (
      0 => 'bool',
      'coordinates' => 'list<array{x: float, y: float}>',
    ),
    'imagickdraw::pop' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::popclippath' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::popdefs' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::poppattern' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::push' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::pushclippath' => 
    array (
      0 => 'bool',
      'clip_mask_id' => 'string',
    ),
    'imagickdraw::pushdefs' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::pushpattern' => 
    array (
      0 => 'bool',
      'pattern_id' => 'string',
      'x' => 'float',
      'y' => 'float',
      'width' => 'float',
      'height' => 'float',
    ),
    'imagickdraw::rectangle' => 
    array (
      0 => 'bool',
      'top_left_x' => 'float',
      'top_left_y' => 'float',
      'bottom_right_x' => 'float',
      'bottom_right_y' => 'float',
    ),
    'imagickdraw::render' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::resetvectorgraphics' => 
    array (
      0 => 'bool',
    ),
    'imagickdraw::rotate' => 
    array (
      0 => 'bool',
      'degrees' => 'float',
    ),
    'imagickdraw::roundrectangle' => 
    array (
      0 => 'bool',
      'top_left_x' => 'float',
      'top_left_y' => 'float',
      'bottom_right_x' => 'float',
      'bottom_right_y' => 'float',
      'rounding_x' => 'float',
      'rounding_y' => 'float',
    ),
    'imagickdraw::scale' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::setbordercolor' => 
    array (
      0 => 'bool',
      'color' => 'ImagickPixel|string',
    ),
    'imagickdraw::setclippath' => 
    array (
      0 => 'bool',
      'clip_mask' => 'string',
    ),
    'imagickdraw::setcliprule' => 
    array (
      0 => 'bool',
      'fillrule' => 'int',
    ),
    'imagickdraw::setclipunits' => 
    array (
      0 => 'bool',
      'pathunits' => 'int',
    ),
    'imagickdraw::setdensity' => 
    array (
      0 => 'bool',
      'density' => 'string',
    ),
    'imagickdraw::setfillalpha' => 
    array (
      0 => 'bool',
      'alpha' => 'float',
    ),
    'imagickdraw::setfillcolor' => 
    array (
      0 => 'bool',
      'fill_color' => 'ImagickPixel|string',
    ),
    'imagickdraw::setfillopacity' => 
    array (
      0 => 'bool',
      'opacity' => 'float',
    ),
    'imagickdraw::setfillpatternurl' => 
    array (
      0 => 'bool',
      'fill_url' => 'string',
    ),
    'imagickdraw::setfillrule' => 
    array (
      0 => 'bool',
      'fillrule' => 'int',
    ),
    'imagickdraw::setfont' => 
    array (
      0 => 'bool',
      'font_name' => 'string',
    ),
    'imagickdraw::setfontfamily' => 
    array (
      0 => 'bool',
      'font_family' => 'string',
    ),
    'imagickdraw::setfontresolution' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickdraw::setfontsize' => 
    array (
      0 => 'bool',
      'point_size' => 'float',
    ),
    'imagickdraw::setfontstretch' => 
    array (
      0 => 'bool',
      'stretch' => 'int',
    ),
    'imagickdraw::setfontstyle' => 
    array (
      0 => 'bool',
      'style' => 'int',
    ),
    'imagickdraw::setfontweight' => 
    array (
      0 => 'bool',
      'weight' => 'int',
    ),
    'imagickdraw::setgravity' => 
    array (
      0 => 'bool',
      'gravity' => 'int',
    ),
    'imagickdraw::setopacity' => 
    array (
      0 => 'bool',
      'opacity' => 'float',
    ),
    'imagickdraw::setresolution' => 
    array (
      0 => 'bool',
      'resolution_x' => 'float',
      'resolution_y' => 'float',
    ),
    'imagickdraw::setstrokealpha' => 
    array (
      0 => 'bool',
      'alpha' => 'float',
    ),
    'imagickdraw::setstrokeantialias' => 
    array (
      0 => 'bool',
      'enabled' => 'bool',
    ),
    'imagickdraw::setstrokecolor' => 
    array (
      0 => 'bool',
      'color' => 'ImagickPixel|string',
    ),
    'imagickdraw::setstrokedasharray' => 
    array (
      0 => 'bool',
      'dashes' => 'list<float|int>|null',
    ),
    'imagickdraw::setstrokedashoffset' => 
    array (
      0 => 'bool',
      'dash_offset' => 'float',
    ),
    'imagickdraw::setstrokelinecap' => 
    array (
      0 => 'bool',
      'linecap' => 'int',
    ),
    'imagickdraw::setstrokelinejoin' => 
    array (
      0 => 'bool',
      'linejoin' => 'int',
    ),
    'imagickdraw::setstrokemiterlimit' => 
    array (
      0 => 'bool',
      'miterlimit' => 'int',
    ),
    'imagickdraw::setstrokeopacity' => 
    array (
      0 => 'bool',
      'opacity' => 'float',
    ),
    'imagickdraw::setstrokepatternurl' => 
    array (
      0 => 'bool',
      'stroke_url' => 'string',
    ),
    'imagickdraw::setstrokewidth' => 
    array (
      0 => 'bool',
      'width' => 'float',
    ),
    'imagickdraw::settextalignment' => 
    array (
      0 => 'bool',
      'align' => 'int',
    ),
    'imagickdraw::settextantialias' => 
    array (
      0 => 'bool',
      'antialias' => 'bool',
    ),
    'imagickdraw::settextdecoration' => 
    array (
      0 => 'bool',
      'decoration' => 'int',
    ),
    'imagickdraw::settextdirection' => 
    array (
      0 => 'bool',
      'direction' => 'int',
    ),
    'imagickdraw::settextencoding' => 
    array (
      0 => 'bool',
      'encoding' => 'string',
    ),
    'imagickdraw::settextinterlinespacing' => 
    array (
      0 => 'bool',
      'spacing' => 'float',
    ),
    'imagickdraw::settextinterwordspacing' => 
    array (
      0 => 'bool',
      'spacing' => 'float',
    ),
    'imagickdraw::settextkerning' => 
    array (
      0 => 'bool',
      'kerning' => 'float',
    ),
    'imagickdraw::settextundercolor' => 
    array (
      0 => 'bool',
      'under_color' => 'ImagickPixel|string',
    ),
    'imagickdraw::setvectorgraphics' => 
    array (
      0 => 'bool',
      'xml' => 'string',
    ),
    'imagickdraw::setviewbox' => 
    array (
      0 => 'bool',
      'left_x' => 'int',
      'top_y' => 'int',
      'right_x' => 'int',
      'bottom_y' => 'int',
    ),
    'imagickdraw::skewx' => 
    array (
      0 => 'bool',
      'degrees' => 'float',
    ),
    'imagickdraw::skewy' => 
    array (
      0 => 'bool',
      'degrees' => 'float',
    ),
    'imagickdraw::translate' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'imagickkernel::addkernel' => 
    array (
      0 => 'void',
      'kernel' => 'ImagickKernel',
    ),
    'imagickkernel::addunitykernel' => 
    array (
      0 => 'void',
      'scale' => 'float',
    ),
    'imagickkernel::frombuiltin' => 
    array (
      0 => 'ImagickKernel',
      'kernel' => 'int',
      'shape' => 'string',
    ),
    'imagickkernel::frommatrix' => 
    array (
      0 => 'ImagickKernel',
      'matrix' => 'list<list<float>>',
      'origin=' => 'array<array-key, mixed>|null',
    ),
    'imagickkernel::getmatrix' => 
    array (
      0 => 'list<list<false|float>>',
    ),
    'imagickkernel::scale' => 
    array (
      0 => 'void',
      'scale' => 'float',
      'normalize_kernel=' => 'int|null',
    ),
    'imagickkernel::separate' => 
    array (
      0 => 'array<array-key, ImagickKernel>',
    ),
    'imagickpixel::__construct' => 
    array (
      0 => 'void',
      'color=' => 'null|string',
    ),
    'imagickpixel::clear' => 
    array (
      0 => 'bool',
    ),
    'imagickpixel::destroy' => 
    array (
      0 => 'bool',
    ),
    'imagickpixel::getcolor' => 
    array (
      0 => 'array{a: float|int, b: float|int, g: float|int, r: float|int}',
      'normalized=' => '0|1|2',
    ),
    'imagickpixel::getcolorasstring' => 
    array (
      0 => 'string',
    ),
    'imagickpixel::getcolorcount' => 
    array (
      0 => 'int',
    ),
    'imagickpixel::getcolorvalue' => 
    array (
      0 => 'float',
      'color' => 'int',
    ),
    'imagickpixel::gethsl' => 
    array (
      0 => 'array{hue: float, luminosity: float, saturation: float}',
    ),
    'imagickpixel::getindex' => 
    array (
      0 => 'int',
    ),
    'imagickpixel::ispixelsimilar' => 
    array (
      0 => 'bool|null',
      'color' => 'ImagickPixel',
      'fuzz' => 'float',
    ),
    'imagickpixel::ispixelsimilarquantum' => 
    array (
      0 => 'bool|null',
      'color' => 'string',
      'fuzz_quantum_range_scaled_by_square_root_of_three' => 'float',
    ),
    'imagickpixel::issimilar' => 
    array (
      0 => 'bool|null',
      'color' => 'ImagickPixel',
      'fuzz_quantum_range_scaled_by_square_root_of_three' => 'float',
    ),
    'imagickpixel::setcolor' => 
    array (
      0 => 'bool',
      'color' => 'string',
    ),
    'imagickpixel::setcolorcount' => 
    array (
      0 => 'bool',
      'color_count' => 'int',
    ),
    'imagickpixel::setcolorfrompixel' => 
    array (
      0 => 'bool',
      'pixel' => 'ImagickPixel',
    ),
    'imagickpixel::setcolorvalue' => 
    array (
      0 => 'bool',
      'color' => 'int',
      'value' => 'float',
    ),
    'imagickpixel::setcolorvaluequantum' => 
    array (
      0 => 'bool',
      'color' => 'int',
      'value' => 'IMAGICK_QUANTUM_TYPE',
    ),
    'imagickpixel::sethsl' => 
    array (
      0 => 'bool',
      'hue' => 'float',
      'saturation' => 'float',
      'luminosity' => 'float',
    ),
    'imagickpixel::setindex' => 
    array (
      0 => 'bool',
      'index' => 'IMAGICK_QUANTUM_TYPE',
    ),
    'imagickpixeliterator::clear' => 
    array (
      0 => 'bool',
    ),
    'imagickpixeliterator::destroy' => 
    array (
      0 => 'bool',
    ),
    'imagickpixeliterator::getcurrentiteratorrow' => 
    array (
      0 => 'array<array-key, mixed>|null',
    ),
    'imagickpixeliterator::getiteratorrow' => 
    array (
      0 => 'int',
    ),
    'imagickpixeliterator::getnextiteratorrow' => 
    array (
      0 => 'array<array-key, mixed>|null',
    ),
    'imagickpixeliterator::getpreviousiteratorrow' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'imagickpixeliterator::key' => 
    array (
      0 => 'int',
    ),
    'imagickpixeliterator::newpixeliterator' => 
    array (
      0 => 'bool',
      'imagick' => 'Imagick',
    ),
    'imagickpixeliterator::newpixelregioniterator' => 
    array (
      0 => 'bool',
      'imagick' => 'Imagick',
      'x' => 'int',
      'y' => 'int',
      'columns' => 'int',
      'rows' => 'int',
    ),
    'imagickpixeliterator::next' => 
    array (
      0 => 'void',
    ),
    'imagickpixeliterator::resetiterator' => 
    array (
      0 => 'bool',
    ),
    'imagickpixeliterator::rewind' => 
    array (
      0 => 'void',
    ),
    'imagickpixeliterator::setiteratorfirstrow' => 
    array (
      0 => 'bool',
    ),
    'imagickpixeliterator::setiteratorlastrow' => 
    array (
      0 => 'bool',
    ),
    'imagickpixeliterator::setiteratorrow' => 
    array (
      0 => 'bool',
      'row' => 'int',
    ),
    'imagickpixeliterator::synciterator' => 
    array (
      0 => 'bool',
    ),
    'imagickpixeliterator::valid' => 
    array (
      0 => 'bool',
    ),
    'memcached::__construct' => 
    array (
      0 => 'void',
      'persistent_id=' => 'null|string',
      'callback=' => 'callable|null',
      'connection_str=' => 'null|string',
    ),
    'memcached::add' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'mixed',
      'expiration=' => 'int',
    ),
    'memcached::addbykey' => 
    array (
      0 => 'bool',
      'server_key' => 'string',
      'key' => 'string',
      'value' => 'mixed',
      'expiration=' => 'int',
    ),
    'memcached::addserver' => 
    array (
      0 => 'bool',
      'host' => 'string',
      'port' => 'int',
      'weight=' => 'int',
    ),
    'memcached::addservers' => 
    array (
      0 => 'bool',
      'servers' => 'array<array-key, mixed>',
    ),
    'memcached::append' => 
    array (
      0 => 'bool|null',
      'key' => 'string',
      'value' => 'string',
    ),
    'memcached::appendbykey' => 
    array (
      0 => 'bool|null',
      'server_key' => 'string',
      'key' => 'string',
      'value' => 'string',
    ),
    'memcached::cas' => 
    array (
      0 => 'bool',
      'cas_token' => 'float|int|string',
      'key' => 'string',
      'value' => 'mixed',
      'expiration=' => 'int',
    ),
    'memcached::casbykey' => 
    array (
      0 => 'bool',
      'cas_token' => 'float|int|string',
      'server_key' => 'string',
      'key' => 'string',
      'value' => 'mixed',
      'expiration=' => 'int',
    ),
    'memcached::decrement' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'offset=' => 'int',
      'initial_value=' => 'int',
      'expiry=' => 'int',
    ),
    'memcached::decrementbykey' => 
    array (
      0 => 'false|int',
      'server_key' => 'string',
      'key' => 'string',
      'offset=' => 'int',
      'initial_value=' => 'int',
      'expiry=' => 'int',
    ),
    'memcached::delete' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'time=' => 'int',
    ),
    'memcached::deletebykey' => 
    array (
      0 => 'bool',
      'server_key' => 'string',
      'key' => 'string',
      'time=' => 'int',
    ),
    'memcached::deletemulti' => 
    array (
      0 => 'array<array-key, mixed>',
      'keys' => 'array<array-key, mixed>',
      'time=' => 'int',
    ),
    'memcached::deletemultibykey' => 
    array (
      0 => 'array<array-key, mixed>',
      'server_key' => 'string',
      'keys' => 'array<array-key, mixed>',
      'time=' => 'int',
    ),
    'memcached::fetch' => 
    array (
      0 => 'array<array-key, mixed>|false',
    ),
    'memcached::fetchall' => 
    array (
      0 => 'array<array-key, mixed>|false',
    ),
    'memcached::flush' => 
    array (
      0 => 'bool',
      'delay=' => 'int',
    ),
    'memcached::flushbuffers' => 
    array (
      0 => 'bool',
    ),
    'memcached::get' => 
    array (
      0 => 'false|mixed',
      'key' => 'string',
      'cache_cb=' => 'callable|null',
      'get_flags=' => 'int',
    ),
    'memcached::getallkeys' => 
    array (
      0 => 'array<array-key, mixed>|false',
    ),
    'memcached::getbykey' => 
    array (
      0 => 'false|mixed',
      'server_key' => 'string',
      'key' => 'string',
      'cache_cb=' => 'callable|null',
      'get_flags=' => 'int',
    ),
    'memcached::getdelayed' => 
    array (
      0 => 'bool',
      'keys' => 'array<array-key, mixed>',
      'with_cas=' => 'bool',
      'value_cb=' => 'callable|null',
    ),
    'memcached::getdelayedbykey' => 
    array (
      0 => 'bool',
      'server_key' => 'string',
      'keys' => 'array<array-key, mixed>',
      'with_cas=' => 'bool',
      'value_cb=' => 'callable|null',
    ),
    'memcached::getlastdisconnectedserver' => 
    array (
      0 => 'array<array-key, mixed>|false',
    ),
    'memcached::getlasterrorcode' => 
    array (
      0 => 'int',
    ),
    'memcached::getlasterrorerrno' => 
    array (
      0 => 'int',
    ),
    'memcached::getlasterrormessage' => 
    array (
      0 => 'string',
    ),
    'memcached::getmulti' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'keys' => 'array<array-key, mixed>',
      'get_flags=' => 'int',
    ),
    'memcached::getmultibykey' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'server_key' => 'string',
      'keys' => 'array<array-key, mixed>',
      'get_flags=' => 'int',
    ),
    'memcached::getoption' => 
    array (
      0 => 'false|mixed',
      'option' => 'int',
    ),
    'memcached::getresultcode' => 
    array (
      0 => 'int',
    ),
    'memcached::getresultmessage' => 
    array (
      0 => 'string',
    ),
    'memcached::getserverbykey' => 
    array (
      0 => 'array<array-key, mixed>',
      'server_key' => 'string',
    ),
    'memcached::getserverlist' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'memcached::getstats' => 
    array (
      0 => 'array<string, array<string, int|string>|false>|false',
      'type=' => 'null|string',
    ),
    'memcached::getversion' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'memcached::increment' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'offset=' => 'int',
      'initial_value=' => 'int',
      'expiry=' => 'int',
    ),
    'memcached::incrementbykey' => 
    array (
      0 => 'false|int',
      'server_key' => 'string',
      'key' => 'string',
      'offset=' => 'int',
      'initial_value=' => 'int',
      'expiry=' => 'int',
    ),
    'memcached::ispersistent' => 
    array (
      0 => 'bool',
    ),
    'memcached::ispristine' => 
    array (
      0 => 'bool',
    ),
    'memcached::prepend' => 
    array (
      0 => 'bool|null',
      'key' => 'string',
      'value' => 'string',
    ),
    'memcached::prependbykey' => 
    array (
      0 => 'bool|null',
      'server_key' => 'string',
      'key' => 'string',
      'value' => 'string',
    ),
    'memcached::quit' => 
    array (
      0 => 'bool',
    ),
    'memcached::replace' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'mixed',
      'expiration=' => 'int',
    ),
    'memcached::replacebykey' => 
    array (
      0 => 'bool',
      'server_key' => 'string',
      'key' => 'string',
      'value' => 'mixed',
      'expiration=' => 'int',
    ),
    'memcached::resetserverlist' => 
    array (
      0 => 'bool',
    ),
    'memcached::set' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'mixed',
      'expiration=' => 'int',
    ),
    'memcached::setbucket' => 
    array (
      0 => 'bool',
      'host_map' => 'array<array-key, mixed>',
      'forward_map' => 'array<array-key, mixed>|null',
      'replicas' => 'int',
    ),
    'memcached::setbykey' => 
    array (
      0 => 'bool',
      'server_key' => 'string',
      'key' => 'string',
      'value' => 'mixed',
      'expiration=' => 'int',
    ),
    'memcached::setencodingkey' => 
    array (
      0 => 'bool',
      'key' => 'string',
    ),
    'memcached::setmulti' => 
    array (
      0 => 'bool',
      'items' => 'array<array-key, mixed>',
      'expiration=' => 'int',
    ),
    'memcached::setmultibykey' => 
    array (
      0 => 'bool',
      'server_key' => 'string',
      'items' => 'array<array-key, mixed>',
      'expiration=' => 'int',
    ),
    'memcached::setoption' => 
    array (
      0 => 'bool',
      'option' => 'int',
      'value' => 'mixed',
    ),
    'memcached::setoptions' => 
    array (
      0 => 'bool',
      'options' => 'array<array-key, mixed>',
    ),
    'memcached::setsaslauthdata' => 
    array (
      0 => 'bool',
      'username' => 'string',
      'password' => 'string',
    ),
    'memcached::touch' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'expiration=' => 'int',
    ),
    'memcached::touchbykey' => 
    array (
      0 => 'bool',
      'server_key' => 'string',
      'key' => 'string',
      'expiration=' => 'int',
    ),
    'redis::__destruct' => 
    array (
      0 => 'void',
    ),
    'redis::_prefix' => 
    array (
      0 => 'string',
      'key' => 'string',
    ),
    'redis::_unserialize' => 
    array (
      0 => 'mixed',
      'value' => 'string',
    ),
    'redis::append' => 
    array (
      0 => 'int',
      'key' => 'string',
      'value' => 'string',
    ),
    'redis::auth' => 
    array (
      0 => 'bool',
      'credentials' => 'string',
    ),
    'redis::bgrewriteaof' => 
    array (
      0 => 'bool',
    ),
    'redis::bgsave' => 
    array (
      0 => 'bool',
    ),
    'redis::bitcount' => 
    array (
      0 => 'int',
      'key' => 'string',
      'start=' => 'int',
      'end=' => 'int',
      'bybit=' => 'bool',
    ),
    'redis::bitop' => 
    array (
      0 => 'int',
      'operation' => 'string',
      'deskey' => 'string',
      'srckey' => 'string',
      '...other_keys=' => 'string',
    ),
    'redis::bitpos' => 
    array (
      0 => 'int',
      'key' => 'string',
      'bit' => 'bool',
      'start=' => 'int',
      'end=' => 'int',
      'bybit=' => 'bool',
    ),
    'redis::blpop' => 
    array (
      0 => 'array<array-key, mixed>|null',
      'key_or_keys' => 'array<array-key, string>',
      'timeout_or_key' => 'int',
      '...extra_args=' => 'mixed',
    ),
    'redis::brpop' => 
    array (
      0 => 'array<array-key, mixed>|null',
      'key_or_keys' => 'array<array-key, string>',
      'timeout_or_key' => 'int',
      '...extra_args=' => 'mixed',
    ),
    'redis::brpoplpush' => 
    array (
      0 => 'false|string',
      'src' => 'string',
      'dst' => 'string',
      'timeout' => 'int',
    ),
    'redis::clearlasterror' => 
    array (
      0 => 'bool',
    ),
    'redis::client' => 
    array (
      0 => 'mixed',
      'opt' => 'string',
      '...args=' => 'string',
    ),
    'redis::close' => 
    array (
      0 => 'bool',
    ),
    'redis::config' => 
    array (
      0 => 'string',
      'operation' => 'string',
      'key_or_settings=' => 'null|string',
      'value=' => 'null|string',
    ),
    'redis::connect' => 
    array (
      0 => 'bool',
      'host' => 'string',
      'port=' => 'int',
      'timeout=' => 'float',
      'persistent_id=' => 'null',
      'retry_interval=' => 'int',
      'read_timeout=' => 'float',
      'context=' => 'array<array-key, mixed>|null',
    ),
    'redis::dbsize' => 
    array (
      0 => 'int',
    ),
    'redis::decr' => 
    array (
      0 => 'int',
      'key' => 'string',
      'by=' => 'int',
    ),
    'redis::decrby' => 
    array (
      0 => 'int',
      'key' => 'string',
      'value' => 'int',
    ),
    'redis::del' => 
    array (
      0 => 'int',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'redis::delete' => 
    array (
      0 => 'int',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'redis::dump' => 
    array (
      0 => 'false|string',
      'key' => 'string',
    ),
    'redis::echo' => 
    array (
      0 => 'string',
      'str' => 'string',
    ),
    'redis::evalsha' => 
    array (
      0 => 'mixed',
      'sha1' => 'string',
      'args=' => 'array<array-key, mixed>',
      'num_keys=' => 'int',
    ),
    'redis::exec' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'redis::exists' => 
    array (
      0 => 'int',
      'key' => 'array<array-key, string>|string',
      '...other_keys=' => 'mixed',
    ),
    'redis::expire' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'timeout' => 'int',
      'mode=' => 'null|string',
    ),
    'redis::expireat' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'timestamp' => 'int',
      'mode=' => 'null|string',
    ),
    'redis::flushall' => 
    array (
      0 => 'bool',
      'sync=' => 'bool|null',
    ),
    'redis::flushdb' => 
    array (
      0 => 'bool',
      'sync=' => 'bool|null',
    ),
    'redis::geoadd' => 
    array (
      0 => 'int',
      'key' => 'string',
      'lng' => 'float',
      'lat' => 'float',
      'member' => 'string',
      '...other_triples_and_options=' => 'float|int|string',
    ),
    'redis::geodist' => 
    array (
      0 => 'float',
      'key' => 'string',
      'src' => 'string',
      'dst' => 'string',
      'unit=' => 'null|string',
    ),
    'redis::geohash' => 
    array (
      0 => 'array<int, string>',
      'key' => 'string',
      'member' => 'string',
      '...other_members=' => 'string',
    ),
    'redis::geopos' => 
    array (
      0 => 'array<int, array{0: string, 1: string}>',
      'key' => 'string',
      'member' => 'string',
      '...other_members=' => 'string',
    ),
    'redis::georadius' => 
    array (
      0 => 'array<int, mixed>|int',
      'key' => 'string',
      'lng' => 'float',
      'lat' => 'float',
      'radius' => 'float',
      'unit' => 'string',
      'options=' => 'array<string, mixed>',
    ),
    'redis::georadiusbymember' => 
    array (
      0 => 'array<int, mixed>|int',
      'key' => 'string',
      'member' => 'string',
      'radius' => 'float',
      'unit' => 'string',
      'options=' => 'array<string, mixed>',
    ),
    'redis::get' => 
    array (
      0 => 'false|string',
      'key' => 'string',
    ),
    'redis::getauth' => 
    array (
      0 => 'false|null|string',
    ),
    'redis::getbit' => 
    array (
      0 => 'int',
      'key' => 'string',
      'idx' => 'int',
    ),
    'redis::getdbnum' => 
    array (
      0 => 'int',
    ),
    'redis::gethost' => 
    array (
      0 => 'string',
    ),
    'redis::getlasterror' => 
    array (
      0 => 'null|string',
    ),
    'redis::getmode' => 
    array (
      0 => 'int',
    ),
    'redis::getoption' => 
    array (
      0 => 'int',
      'option' => 'int',
    ),
    'redis::getpersistentid' => 
    array (
      0 => 'null|string',
    ),
    'redis::getport' => 
    array (
      0 => 'int',
    ),
    'redis::getrange' => 
    array (
      0 => 'false|string',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
    ),
    'redis::getreadtimeout' => 
    array (
      0 => 'float',
    ),
    'redis::getset' => 
    array (
      0 => 'string',
      'key' => 'string',
      'value' => 'string',
    ),
    'redis::gettimeout' => 
    array (
      0 => 'false|float',
    ),
    'redis::hdel' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'field' => 'string',
      '...other_fields=' => 'string',
    ),
    'redis::hexists' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'field' => 'string',
    ),
    'redis::hget' => 
    array (
      0 => 'false|string',
      'key' => 'string',
      'member' => 'string',
    ),
    'redis::hgetall' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
    ),
    'redis::hincrby' => 
    array (
      0 => 'int',
      'key' => 'string',
      'field' => 'string',
      'value' => 'int',
    ),
    'redis::hincrbyfloat' => 
    array (
      0 => 'float',
      'key' => 'string',
      'field' => 'string',
      'value' => 'float',
    ),
    'redis::hkeys' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
    ),
    'redis::hlen' => 
    array (
      0 => 'false|int',
      'key' => 'string',
    ),
    'redis::hmget' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'fields' => 'array<array-key, mixed>',
    ),
    'redis::hmset' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'fieldvals' => 'array<array-key, mixed>',
    ),
    'redis::hscan' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      '&iterator' => 'int|null',
      'pattern=' => 'null|string',
      'count=' => 'int',
    ),
    'redis::hset' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      '...fields_and_vals=' => 'string',
    ),
    'redis::hsetnx' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'field' => 'string',
      'value' => 'string',
    ),
    'redis::hvals' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
    ),
    'redis::incr' => 
    array (
      0 => 'int',
      'key' => 'string',
      'by=' => 'int',
    ),
    'redis::incrby' => 
    array (
      0 => 'int',
      'key' => 'string',
      'value' => 'int',
    ),
    'redis::incrbyfloat' => 
    array (
      0 => 'float',
      'key' => 'string',
      'value' => 'float',
    ),
    'redis::info' => 
    array (
      0 => 'array<array-key, mixed>',
      '...sections=' => 'string',
    ),
    'redis::isconnected' => 
    array (
      0 => 'bool',
    ),
    'redis::keys' => 
    array (
      0 => 'array<int, string>',
      'pattern' => 'string',
    ),
    'redis::lastsave' => 
    array (
      0 => 'int',
    ),
    'redis::lindex' => 
    array (
      0 => 'false|string',
      'key' => 'string',
      'index' => 'int',
    ),
    'redis::linsert' => 
    array (
      0 => 'int',
      'key' => 'string',
      'pos' => 'string',
      'pivot' => 'string',
      'value' => 'string',
    ),
    'redis::llen' => 
    array (
      0 => 'false|int',
      'key' => 'string',
    ),
    'redis::lpop' => 
    array (
      0 => 'false|string',
      'key' => 'string',
      'count=' => 'int',
    ),
    'redis::lpush' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      '...elements=' => 'string',
    ),
    'redis::lpushx' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'value' => 'string',
    ),
    'redis::lrange' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
    ),
    'redis::lrem' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'value' => 'string',
      'count=' => 'int',
    ),
    'redis::lset' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'index' => 'int',
      'value' => 'string',
    ),
    'redis::ltrim' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
    ),
    'redis::mget' => 
    array (
      0 => 'array<array-key, mixed>',
      'keys' => 'array<array-key, string>',
    ),
    'redis::migrate' => 
    array (
      0 => 'bool',
      'host' => 'string',
      'port' => 'int',
      'key' => 'array<array-key, string>|string',
      'dstdb' => 'int',
      'timeout' => 'int',
      'copy=' => 'bool',
      'replace=' => 'bool',
      'credentials=' => 'mixed',
    ),
    'redis::move' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'index' => 'int',
    ),
    'redis::mset' => 
    array (
      0 => 'bool',
      'key_values' => 'array<array-key, mixed>',
    ),
    'redis::msetnx' => 
    array (
      0 => 'bool',
      'key_values' => 'array<array-key, mixed>',
    ),
    'redis::multi' => 
    array (
      0 => 'Redis',
      'value=' => 'int',
    ),
    'redis::object' => 
    array (
      0 => 'false|int|string',
      'subcommand' => 'string',
      'key' => 'string',
    ),
    'redis::open' => 
    array (
      0 => 'bool',
      'host' => 'string',
      'port=' => 'int',
      'timeout=' => 'float',
      'persistent_id=' => 'null',
      'retry_interval=' => 'int',
      'read_timeout=' => 'float',
      'context=' => 'array<array-key, mixed>|null',
    ),
    'redis::pconnect' => 
    array (
      0 => 'bool',
      'host' => 'string',
      'port=' => 'int',
      'timeout=' => 'float',
      'persistent_id=' => 'null|string',
      'retry_interval=' => 'int',
      'read_timeout=' => 'float',
      'context=' => 'array<array-key, mixed>|null',
    ),
    'redis::persist' => 
    array (
      0 => 'bool',
      'key' => 'string',
    ),
    'redis::pexpire' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'timeout' => 'int',
      'mode=' => 'null|string',
    ),
    'redis::pexpireat' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'timestamp' => 'int',
      'mode=' => 'null|string',
    ),
    'redis::pfadd' => 
    array (
      0 => 'int',
      'key' => 'string',
      'elements' => 'array<array-key, mixed>',
    ),
    'redis::pfcount' => 
    array (
      0 => 'int',
      'key_or_keys' => 'array<array-key, mixed>|string',
    ),
    'redis::pfmerge' => 
    array (
      0 => 'bool',
      'dst' => 'string',
      'srckeys' => 'array<array-key, mixed>',
    ),
    'redis::ping' => 
    array (
      0 => 'string',
      'message=' => 'null|string',
    ),
    'redis::pipeline' => 
    array (
      0 => 'Redis',
    ),
    'redis::popen' => 
    array (
      0 => 'bool',
      'host' => 'string',
      'port=' => 'int',
      'timeout=' => 'float',
      'persistent_id=' => 'null|string',
      'retry_interval=' => 'int',
      'read_timeout=' => 'float',
      'context=' => 'array<array-key, mixed>|null',
    ),
    'redis::psetex' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'expire' => 'int',
      'value' => 'string',
    ),
    'redis::psubscribe' => 
    array (
      0 => 'bool',
      'patterns' => 'array<array-key, mixed>',
      'cb' => 'callable',
    ),
    'redis::pttl' => 
    array (
      0 => 'false|int',
      'key' => 'string',
    ),
    'redis::publish' => 
    array (
      0 => 'int',
      'channel' => 'string',
      'message' => 'string',
    ),
    'redis::pubsub' => 
    array (
      0 => 'array<array-key, mixed>|int',
      'command' => 'string',
      'arg=' => 'array<array-key, mixed>|string',
    ),
    'redis::punsubscribe' => 
    array (
      0 => 'array<array-key, mixed>|bool',
      'patterns' => 'array<array-key, mixed>',
    ),
    'redis::randomkey' => 
    array (
      0 => 'string',
    ),
    'redis::rawcommand' => 
    array (
      0 => 'mixed',
      'command' => 'string',
      '...args=' => 'mixed',
    ),
    'redis::rename' => 
    array (
      0 => 'bool',
      'old_name' => 'string',
      'new_name' => 'string',
    ),
    'redis::renamenx' => 
    array (
      0 => 'bool',
      'key_src' => 'string',
      'key_dst' => 'string',
    ),
    'redis::restore' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'ttl' => 'int',
      'value' => 'string',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'redis::role' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'redis::rpop' => 
    array (
      0 => 'false|string',
      'key' => 'string',
      'count=' => 'int',
    ),
    'redis::rpoplpush' => 
    array (
      0 => 'string',
      'srckey' => 'string',
      'dstkey' => 'string',
    ),
    'redis::rpush' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      '...elements=' => 'string',
    ),
    'redis::rpushx' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'value' => 'string',
    ),
    'redis::sadd' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'value' => 'string',
      '...other_values=' => 'string',
    ),
    'redis::saddarray' => 
    array (
      0 => 'int',
      'key' => 'string',
      'values' => 'array<array-key, mixed>',
    ),
    'redis::save' => 
    array (
      0 => 'bool',
    ),
    'redis::scan' => 
    array (
      0 => 'array<int, string>|false',
      '&iterator' => 'int|null',
      'pattern=' => 'null|string',
      'count=' => 'int',
      'type=' => 'null|string',
    ),
    'redis::scard' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'redis::script' => 
    array (
      0 => 'mixed',
      'command' => 'string',
      '...args=' => 'mixed',
    ),
    'redis::sdiff' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'redis::sdiffstore' => 
    array (
      0 => 'false|int',
      'dst' => 'string',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'redis::select' => 
    array (
      0 => 'bool',
      'db' => 'int',
    ),
    'redis::set' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'mixed',
      'options=' => 'array<array-key, mixed>',
    ),
    'redis::setbit' => 
    array (
      0 => 'int',
      'key' => 'string',
      'idx' => 'int',
      'value' => 'bool',
    ),
    'redis::setex' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'expire' => 'int',
      'value' => 'string',
    ),
    'redis::setnx' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'string',
    ),
    'redis::setoption' => 
    array (
      0 => 'bool',
      'option' => 'int',
      'value' => 'mixed',
    ),
    'redis::setrange' => 
    array (
      0 => 'int',
      'key' => 'string',
      'index' => 'int',
      'value' => 'string',
    ),
    'redis::sinter' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'redis::sinterstore' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'redis::sismember' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'string',
    ),
    'redis::slaveof' => 
    array (
      0 => 'bool',
      'host=' => 'null|string',
      'port=' => 'int',
    ),
    'redis::slowlog' => 
    array (
      0 => 'mixed',
      'operation' => 'string',
      'length=' => 'int',
    ),
    'redis::smembers' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
    ),
    'redis::smove' => 
    array (
      0 => 'bool',
      'src' => 'string',
      'dst' => 'string',
      'value' => 'string',
    ),
    'redis::sort' => 
    array (
      0 => 'array<array-key, mixed>|int',
      'key' => 'string',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'redis::sortasc' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'pattern=' => 'null|string',
      'get=' => 'string',
      'offset=' => 'int',
      'count=' => 'int',
      'store=' => 'null|string',
    ),
    'redis::sortascalpha' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'pattern=' => 'null|string',
      'get=' => 'string',
      'offset=' => 'int',
      'count=' => 'int',
      'store=' => 'null|string',
    ),
    'redis::sortdesc' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'pattern=' => 'null|string',
      'get=' => 'string',
      'offset=' => 'int',
      'count=' => 'int',
      'store=' => 'null|string',
    ),
    'redis::sortdescalpha' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'pattern=' => 'null|string',
      'get=' => 'string',
      'offset=' => 'int',
      'count=' => 'int',
      'store=' => 'null|string',
    ),
    'redis::spop' => 
    array (
      0 => 'false|string',
      'key' => 'string',
      'count=' => 'int',
    ),
    'redis::srandmember' => 
    array (
      0 => 'array<array-key, mixed>|false|string',
      'key' => 'string',
      'count=' => 'int',
    ),
    'redis::srem' => 
    array (
      0 => 'int',
      'key' => 'string',
      'value' => 'string',
      '...other_values=' => 'string',
    ),
    'redis::sscan' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'key' => 'string',
      '&iterator' => 'int|null',
      'pattern=' => 'null|string',
      'count=' => 'int',
    ),
    'redis::strlen' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'redis::subscribe' => 
    array (
      0 => 'bool',
      'channels' => 'array<array-key, mixed>',
      'cb' => 'callable',
    ),
    'redis::sunion' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'redis::sunionstore' => 
    array (
      0 => 'int',
      'dst' => 'string',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'redis::swapdb' => 
    array (
      0 => 'bool',
      'src' => 'int',
      'dst' => 'int',
    ),
    'redis::time' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'redis::ttl' => 
    array (
      0 => 'false|int',
      'key' => 'string',
    ),
    'redis::type' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'redis::unlink' => 
    array (
      0 => 'int',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'redis::unsubscribe' => 
    array (
      0 => 'array<array-key, mixed>|bool',
      'channels' => 'array<array-key, mixed>',
    ),
    'redis::wait' => 
    array (
      0 => 'int',
      'numreplicas' => 'int',
      'timeout' => 'int',
    ),
    'redis::watch' => 
    array (
      0 => 'bool',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'redis::xack' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'group' => 'string',
      'ids' => 'array<array-key, mixed>',
    ),
    'redis::xadd' => 
    array (
      0 => 'false|string',
      'key' => 'string',
      'id' => 'string',
      'values' => 'array<array-key, mixed>',
      'maxlen=' => 'int',
      'approx=' => 'bool',
      'nomkstream=' => 'bool',
    ),
    'redis::xclaim' => 
    array (
      0 => 'array<array-key, mixed>|bool',
      'key' => 'string',
      'group' => 'string',
      'consumer' => 'string',
      'min_idle' => 'int',
      'ids' => 'array<array-key, mixed>',
      'options' => 'array<array-key, mixed>',
    ),
    'redis::xdel' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'ids' => 'array<array-key, mixed>',
    ),
    'redis::xgroup' => 
    array (
      0 => 'mixed',
      'operation' => 'string',
      'key=' => 'null|string',
      'group=' => 'null|string',
      'id_or_consumer=' => 'null|string',
      'mkstream=' => 'bool',
      'entries_read=' => 'int',
    ),
    'redis::xinfo' => 
    array (
      0 => 'mixed',
      'operation' => 'string',
      'arg1=' => 'null|string',
      'arg2=' => 'null|string',
      'count=' => 'int',
    ),
    'redis::xpending' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'key' => 'string',
      'group' => 'string',
      'start=' => 'null|string',
      'end=' => 'null|string',
      'count=' => 'int',
      'consumer=' => 'null|string',
    ),
    'redis::xrange' => 
    array (
      0 => 'array<array-key, mixed>|bool',
      'key' => 'string',
      'start' => 'string',
      'end' => 'string',
      'count=' => 'int',
    ),
    'redis::xread' => 
    array (
      0 => 'array<array-key, mixed>|bool',
      'streams' => 'array<array-key, mixed>',
      'count=' => 'int',
      'block=' => 'int',
    ),
    'redis::xreadgroup' => 
    array (
      0 => 'array<array-key, mixed>|bool',
      'group' => 'string',
      'consumer' => 'string',
      'streams' => 'array<array-key, mixed>',
      'count=' => 'int',
      'block=' => 'int',
    ),
    'redis::xrevrange' => 
    array (
      0 => 'array<array-key, mixed>|bool',
      'key' => 'string',
      'end' => 'string',
      'start' => 'string',
      'count=' => 'int',
    ),
    'redis::xtrim' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'threshold' => 'string',
      'approx=' => 'bool',
      'minid=' => 'bool',
      'limit=' => 'int',
    ),
    'redis::zadd' => 
    array (
      0 => 'int',
      'key' => 'string',
      'score_or_options' => 'float',
      '...more_scores_and_mems=' => 'string',
    ),
    'redis::zcard' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'redis::zcount' => 
    array (
      0 => 'int',
      'key' => 'string',
      'start' => 'string',
      'end' => 'string',
    ),
    'redis::zincrby' => 
    array (
      0 => 'float',
      'key' => 'string',
      'value' => 'float',
      'member' => 'string',
    ),
    'redis::zinter' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'keys' => 'array<array-key, mixed>',
      'weights=' => 'array<array-key, mixed>|null',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'redis::zinterstore' => 
    array (
      0 => 'int',
      'dst' => 'string',
      'keys' => 'array<array-key, mixed>',
      'weights=' => 'array<array-key, mixed>|null',
      'aggregate=' => 'null|string',
    ),
    'redis::zlexcount' => 
    array (
      0 => 'int',
      'key' => 'string',
      'min' => 'string',
      'max' => 'string',
    ),
    'redis::zrange' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
      'options=' => 'bool|null',
    ),
    'redis::zrangebylex' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'key' => 'string',
      'min' => 'string',
      'max' => 'string',
      'offset=' => 'int',
      'count=' => 'int',
    ),
    'redis::zrangebyscore' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'start' => 'string',
      'end' => 'string',
      'options=' => 'array<array-key, mixed>',
    ),
    'redis::zrank' => 
    array (
      0 => 'int',
      'key' => 'string',
      'member' => 'string',
    ),
    'redis::zrem' => 
    array (
      0 => 'int',
      'key' => 'string',
      'member' => 'string',
      '...other_members=' => 'string',
    ),
    'redis::zremrangebylex' => 
    array (
      0 => 'int',
      'key' => 'string',
      'min' => 'string',
      'max' => 'string',
    ),
    'redis::zremrangebyrank' => 
    array (
      0 => 'int',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
    ),
    'redis::zremrangebyscore' => 
    array (
      0 => 'int',
      'key' => 'string',
      'start' => 'string',
      'end' => 'string',
    ),
    'redis::zrevrange' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
      'scores=' => 'bool',
    ),
    'redis::zrevrangebylex' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'max' => 'string',
      'min' => 'string',
      'offset=' => 'int',
      'count=' => 'int',
    ),
    'redis::zrevrangebyscore' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'max' => 'string',
      'min' => 'string',
      'options=' => 'array<array-key, mixed>',
    ),
    'redis::zrevrank' => 
    array (
      0 => 'int',
      'key' => 'string',
      'member' => 'string',
    ),
    'redis::zscan' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'key' => 'string',
      '&iterator' => 'int|null',
      'pattern=' => 'null|string',
      'count=' => 'int',
    ),
    'redis::zscore' => 
    array (
      0 => 'false|float',
      'key' => 'string',
      'member' => 'string',
    ),
    'redis::zunion' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'keys' => 'array<array-key, mixed>',
      'weights=' => 'array<array-key, mixed>|null',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'redis::zunionstore' => 
    array (
      0 => 'int',
      'dst' => 'string',
      'keys' => 'array<array-key, mixed>',
      'weights=' => 'array<array-key, mixed>|null',
      'aggregate=' => 'null|string',
    ),
    'redisarray::__call' => 
    array (
      0 => 'mixed',
      'function_name' => 'string',
      'arguments' => 'array<array-key, mixed>',
    ),
    'redisarray::__construct' => 
    array (
      0 => 'void',
      'name_or_hosts' => 'string',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'redisarray::_function' => 
    array (
      0 => 'bool|callable',
    ),
    'redisarray::_hosts' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'redisarray::_rehash' => 
    array (
      0 => 'bool|null',
      'fn=' => 'callable|null',
    ),
    'redisarray::_target' => 
    array (
      0 => 'null|string',
      'key' => 'string',
    ),
    'redisarray::del' => 
    array (
      0 => 'bool',
      'key' => 'string',
      '...otherkeys=' => 'string',
    ),
    'redisarray::exec' => 
    array (
      0 => 'array<array-key, mixed>|null',
    ),
    'redisarray::flushall' => 
    array (
      0 => 'bool',
    ),
    'redisarray::flushdb' => 
    array (
      0 => 'bool',
    ),
    'redisarray::info' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'redisarray::keys' => 
    array (
      0 => 'array<int, string>',
      'pattern' => 'string',
    ),
    'redisarray::mget' => 
    array (
      0 => 'array<array-key, mixed>',
      'keys' => 'array<array-key, string>',
    ),
    'redisarray::mset' => 
    array (
      0 => 'bool',
      'pairs' => 'array<array-key, mixed>',
    ),
    'redisarray::multi' => 
    array (
      0 => 'RedisArray',
      'host' => 'string',
      'mode=' => 'int|null',
    ),
    'redisarray::ping' => 
    array (
      0 => 'array<array-key, mixed>|bool',
    ),
    'redisarray::save' => 
    array (
      0 => 'bool',
    ),
    'redisarray::unlink' => 
    array (
      0 => 'int',
      'key' => 'string',
      '...otherkeys=' => 'string',
    ),
    'rediscluster::__construct' => 
    array (
      0 => 'void',
      'name' => 'null|string',
      'seeds=' => 'array<array-key, string>|null',
      'timeout=' => 'float',
      'read_timeout=' => 'float',
      'persistent=' => 'bool',
      'auth=' => 'null|string',
      'context=' => 'array<array-key, mixed>|null',
    ),
    'rediscluster::_masters' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'rediscluster::_prefix' => 
    array (
      0 => 'string',
      'key' => 'string',
    ),
    'rediscluster::_unserialize' => 
    array (
      0 => 'mixed',
      'value' => 'string',
    ),
    'rediscluster::append' => 
    array (
      0 => 'int',
      'key' => 'string',
      'value' => 'string',
    ),
    'rediscluster::bgrewriteaof' => 
    array (
      0 => 'bool',
      'key_or_address' => 'array{0: string, 1: int}|string',
    ),
    'rediscluster::bgsave' => 
    array (
      0 => 'bool',
      'key_or_address' => 'array{0: string, 1: int}|string',
    ),
    'rediscluster::bitcount' => 
    array (
      0 => 'int',
      'key' => 'string',
      'start=' => 'int',
      'end=' => 'int',
      'bybit=' => 'bool',
    ),
    'rediscluster::bitop' => 
    array (
      0 => 'int',
      'operation' => 'string',
      'deskey' => 'string',
      'srckey' => 'string',
      '...otherkeys=' => 'string',
    ),
    'rediscluster::bitpos' => 
    array (
      0 => 'int',
      'key' => 'string',
      'bit' => 'bool',
      'start=' => 'int',
      'end=' => 'int',
      'bybit=' => 'bool',
    ),
    'rediscluster::blpop' => 
    array (
      0 => 'array<array-key, mixed>|null',
      'key' => 'array<array-key, mixed>',
      'timeout_or_key' => 'int',
      '...extra_args=' => 'mixed',
    ),
    'rediscluster::brpop' => 
    array (
      0 => 'array<array-key, mixed>|null',
      'key' => 'array<array-key, mixed>',
      'timeout_or_key' => 'int',
      '...extra_args=' => 'mixed',
    ),
    'rediscluster::brpoplpush' => 
    array (
      0 => 'false|string',
      'srckey' => 'string',
      'deskey' => 'string',
      'timeout' => 'int',
    ),
    'rediscluster::clearlasterror' => 
    array (
      0 => 'bool',
    ),
    'rediscluster::client' => 
    array (
      0 => 'array<array-key, mixed>|bool|string',
      'key_or_address' => 'array{0: string, 1: int}|string',
      'subcommand' => 'string',
      'arg=' => 'null|string',
    ),
    'rediscluster::cluster' => 
    array (
      0 => 'mixed',
      'key_or_address' => 'array{0: string, 1: int}|string',
      'command' => 'string',
      '...extra_args=' => 'mixed',
    ),
    'rediscluster::command' => 
    array (
      0 => 'array<array-key, mixed>|bool',
      '...extra_args=' => 'mixed',
    ),
    'rediscluster::config' => 
    array (
      0 => 'array<array-key, mixed>|bool',
      'key_or_address' => 'array{0: string, 1: int}|string',
      'subcommand' => 'string',
      '...extra_args=' => 'string',
    ),
    'rediscluster::dbsize' => 
    array (
      0 => 'int',
      'key_or_address' => 'array{0: string, 1: int}|string',
    ),
    'rediscluster::decr' => 
    array (
      0 => 'int',
      'key' => 'string',
      'by=' => 'int',
    ),
    'rediscluster::decrby' => 
    array (
      0 => 'int',
      'key' => 'string',
      'value' => 'int',
    ),
    'rediscluster::del' => 
    array (
      0 => 'int',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'rediscluster::dump' => 
    array (
      0 => 'false|string',
      'key' => 'string',
    ),
    'rediscluster::echo' => 
    array (
      0 => 'string',
      'key_or_address' => 'array{0: string, 1: int}|string',
      'msg' => 'string',
    ),
    'rediscluster::evalsha' => 
    array (
      0 => 'mixed',
      'script_sha' => 'string',
      'args=' => 'array<array-key, mixed>',
      'num_keys=' => 'int',
    ),
    'rediscluster::exec' => 
    array (
      0 => 'array<array-key, mixed>|false',
    ),
    'rediscluster::exists' => 
    array (
      0 => 'bool',
      'key' => 'string',
      '...other_keys=' => 'mixed',
    ),
    'rediscluster::expire' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'timeout' => 'int',
      'mode=' => 'null|string',
    ),
    'rediscluster::expireat' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'timestamp' => 'int',
      'mode=' => 'null|string',
    ),
    'rediscluster::flushall' => 
    array (
      0 => 'bool',
      'key_or_address' => 'array{0: string, 1: int}|string',
      'async=' => 'bool',
    ),
    'rediscluster::flushdb' => 
    array (
      0 => 'bool',
      'key_or_address' => 'array{0: string, 1: int}|string',
      'async=' => 'bool',
    ),
    'rediscluster::geoadd' => 
    array (
      0 => 'int',
      'key' => 'string',
      'lng' => 'float',
      'lat' => 'float',
      'member' => 'string',
      '...other_triples_and_options=' => 'float|string',
    ),
    'rediscluster::geodist' => 
    array (
      0 => 'RedisCluster|false|float',
      'key' => 'string',
      'src' => 'string',
      'dest' => 'string',
      'unit=' => 'null|string',
    ),
    'rediscluster::geohash' => 
    array (
      0 => 'array<int, string>',
      'key' => 'string',
      'member' => 'string',
      '...other_members=' => 'string',
    ),
    'rediscluster::geopos' => 
    array (
      0 => 'array<int, array{0: string, 1: string}>',
      'key' => 'string',
      'member' => 'string',
      '...other_members=' => 'string',
    ),
    'rediscluster::georadius' => 
    array (
      0 => 'mixed',
      'key' => 'string',
      'lng' => 'float',
      'lat' => 'float',
      'radius' => 'float',
      'unit' => 'string',
      'options=' => 'array<array-key, mixed>',
    ),
    'rediscluster::georadiusbymember' => 
    array (
      0 => 'array<array-key, string>',
      'key' => 'string',
      'member' => 'string',
      'radius' => 'float',
      'unit' => 'string',
      'options=' => 'array<array-key, mixed>',
    ),
    'rediscluster::get' => 
    array (
      0 => 'false|string',
      'key' => 'string',
    ),
    'rediscluster::getbit' => 
    array (
      0 => 'int',
      'key' => 'string',
      'value' => 'int',
    ),
    'rediscluster::getlasterror' => 
    array (
      0 => 'null|string',
    ),
    'rediscluster::getmode' => 
    array (
      0 => 'int',
    ),
    'rediscluster::getoption' => 
    array (
      0 => 'int',
      'option' => 'int',
    ),
    'rediscluster::getrange' => 
    array (
      0 => 'string',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
    ),
    'rediscluster::getset' => 
    array (
      0 => 'string',
      'key' => 'string',
      'value' => 'string',
    ),
    'rediscluster::hdel' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'member' => 'string',
      '...other_members=' => 'string',
    ),
    'rediscluster::hexists' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'member' => 'string',
    ),
    'rediscluster::hget' => 
    array (
      0 => 'false|string',
      'key' => 'string',
      'member' => 'string',
    ),
    'rediscluster::hgetall' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
    ),
    'rediscluster::hincrby' => 
    array (
      0 => 'int',
      'key' => 'string',
      'member' => 'string',
      'value' => 'int',
    ),
    'rediscluster::hincrbyfloat' => 
    array (
      0 => 'float',
      'key' => 'string',
      'member' => 'string',
      'value' => 'float',
    ),
    'rediscluster::hkeys' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
    ),
    'rediscluster::hlen' => 
    array (
      0 => 'false|int',
      'key' => 'string',
    ),
    'rediscluster::hmget' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'keys' => 'array<array-key, mixed>',
    ),
    'rediscluster::hmset' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'key_values' => 'array<array-key, mixed>',
    ),
    'rediscluster::hscan' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      '&iterator' => 'int|null',
      'pattern=' => 'null|string',
      'count=' => 'int',
    ),
    'rediscluster::hset' => 
    array (
      0 => 'int',
      'key' => 'string',
      'member' => 'string',
      'value' => 'string',
    ),
    'rediscluster::hsetnx' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'member' => 'string',
      'value' => 'string',
    ),
    'rediscluster::hstrlen' => 
    array (
      0 => 'int',
      'key' => 'string',
      'field' => 'string',
    ),
    'rediscluster::hvals' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
    ),
    'rediscluster::incr' => 
    array (
      0 => 'int',
      'key' => 'string',
      'by=' => 'int',
    ),
    'rediscluster::incrby' => 
    array (
      0 => 'int',
      'key' => 'string',
      'value' => 'int',
    ),
    'rediscluster::incrbyfloat' => 
    array (
      0 => 'float',
      'key' => 'string',
      'value' => 'float',
    ),
    'rediscluster::info' => 
    array (
      0 => 'array<array-key, mixed>',
      'key_or_address' => 'array{0: string, 1: int}|string',
      '...sections=' => 'string',
    ),
    'rediscluster::keys' => 
    array (
      0 => 'array<array-key, mixed>',
      'pattern' => 'string',
    ),
    'rediscluster::lastsave' => 
    array (
      0 => 'int',
      'key_or_address' => 'array{0: string, 1: int}|string',
    ),
    'rediscluster::lget' => 
    array (
      0 => 'RedisCluster|bool|string',
      'key' => 'string',
      'index' => 'int',
    ),
    'rediscluster::lindex' => 
    array (
      0 => 'false|string',
      'key' => 'string',
      'index' => 'int',
    ),
    'rediscluster::linsert' => 
    array (
      0 => 'int',
      'key' => 'string',
      'pos' => 'string',
      'pivot' => 'string',
      'value' => 'string',
    ),
    'rediscluster::llen' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'rediscluster::lpop' => 
    array (
      0 => 'false|string',
      'key' => 'string',
      'count=' => 'int',
    ),
    'rediscluster::lpush' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'value' => 'string',
      '...other_values=' => 'string',
    ),
    'rediscluster::lpushx' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'value' => 'string',
    ),
    'rediscluster::lrange' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
    ),
    'rediscluster::lrem' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'value' => 'string',
      'count=' => 'int',
    ),
    'rediscluster::lset' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'index' => 'int',
      'value' => 'string',
    ),
    'rediscluster::ltrim' => 
    array (
      0 => 'RedisCluster|bool',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
    ),
    'rediscluster::mget' => 
    array (
      0 => 'array<array-key, mixed>',
      'keys' => 'array<array-key, mixed>',
    ),
    'rediscluster::mset' => 
    array (
      0 => 'bool',
      'key_values' => 'array<array-key, mixed>',
    ),
    'rediscluster::msetnx' => 
    array (
      0 => 'RedisCluster|array<array-key, mixed>|false',
      'key_values' => 'array<array-key, mixed>',
    ),
    'rediscluster::multi' => 
    array (
      0 => 'RedisCluster|bool',
      'value=' => 'int',
    ),
    'rediscluster::object' => 
    array (
      0 => 'false|int|string',
      'subcommand' => 'string',
      'key' => 'string',
    ),
    'rediscluster::persist' => 
    array (
      0 => 'bool',
      'key' => 'string',
    ),
    'rediscluster::pexpire' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'timeout' => 'int',
      'mode=' => 'null|string',
    ),
    'rediscluster::pexpireat' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'timestamp' => 'int',
      'mode=' => 'null|string',
    ),
    'rediscluster::pfadd' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'elements' => 'array<array-key, mixed>',
    ),
    'rediscluster::pfcount' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'rediscluster::pfmerge' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'keys' => 'array<array-key, mixed>',
    ),
    'rediscluster::ping' => 
    array (
      0 => 'string',
      'key_or_address' => 'array{0: string, 1: int}|string',
      'message=' => 'null|string',
    ),
    'rediscluster::psetex' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'timeout' => 'int',
      'value' => 'string',
    ),
    'rediscluster::psubscribe' => 
    array (
      0 => 'void',
      'patterns' => 'array<array-key, mixed>',
      'callback' => 'callable',
    ),
    'rediscluster::pttl' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'rediscluster::publish' => 
    array (
      0 => 'int',
      'channel' => 'string',
      'message' => 'string',
    ),
    'rediscluster::pubsub' => 
    array (
      0 => 'array<array-key, mixed>',
      'key_or_address' => 'string',
      '...values=' => 'string',
    ),
    'rediscluster::randomkey' => 
    array (
      0 => 'string',
      'key_or_address' => 'array{0: string, 1: int}|string',
    ),
    'rediscluster::rawcommand' => 
    array (
      0 => 'mixed',
      'key_or_address' => 'array{0: string, 1: int}|string',
      'command' => 'string',
      '...args=' => 'mixed',
    ),
    'rediscluster::rename' => 
    array (
      0 => 'bool',
      'key_src' => 'string',
      'key_dst' => 'string',
    ),
    'rediscluster::renamenx' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'newkey' => 'string',
    ),
    'rediscluster::restore' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'timeout' => 'int',
      'value' => 'string',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'rediscluster::role' => 
    array (
      0 => 'array<array-key, mixed>',
      'key_or_address' => 'array<array-key, mixed>|string',
    ),
    'rediscluster::rpop' => 
    array (
      0 => 'false|string',
      'key' => 'string',
      'count=' => 'int',
    ),
    'rediscluster::rpoplpush' => 
    array (
      0 => 'false|string',
      'src' => 'string',
      'dst' => 'string',
    ),
    'rediscluster::rpush' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      '...elements=' => 'string',
    ),
    'rediscluster::rpushx' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'value' => 'string',
    ),
    'rediscluster::sadd' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'value' => 'string',
      '...other_values=' => 'string',
    ),
    'rediscluster::saddarray' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'values' => 'array<array-key, mixed>',
    ),
    'rediscluster::save' => 
    array (
      0 => 'bool',
      'key_or_address' => 'array{0: string, 1: int}|string',
    ),
    'rediscluster::scan' => 
    array (
      0 => 'array<array-key, mixed>|false',
      '&iterator' => 'int|null',
      'key_or_address' => 'array{0: string, 1: int}|string',
      'pattern=' => 'null|string',
      'count=' => 'int',
    ),
    'rediscluster::scard' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'rediscluster::script' => 
    array (
      0 => 'array<array-key, mixed>|bool|string',
      'key_or_address' => 'array{0: string, 1: int}|string',
      '...args=' => 'string',
    ),
    'rediscluster::sdiff' => 
    array (
      0 => 'list<string>',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'rediscluster::sdiffstore' => 
    array (
      0 => 'int',
      'dst' => 'string',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'rediscluster::set' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'string',
      'options=' => 'array<array-key, mixed>|int',
    ),
    'rediscluster::setbit' => 
    array (
      0 => 'int',
      'key' => 'string',
      'offset' => 'int',
      'onoff' => 'bool',
    ),
    'rediscluster::setex' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'expire' => 'int',
      'value' => 'string',
    ),
    'rediscluster::setnx' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'string',
    ),
    'rediscluster::setoption' => 
    array (
      0 => 'bool',
      'option' => 'int',
      'value' => 'int|string',
    ),
    'rediscluster::setrange' => 
    array (
      0 => 'RedisCluster|false|int',
      'key' => 'string',
      'offset' => 'int',
      'value' => 'string',
    ),
    'rediscluster::sinter' => 
    array (
      0 => 'list<string>',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'rediscluster::sinterstore' => 
    array (
      0 => 'int',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'rediscluster::sismember' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'string',
    ),
    'rediscluster::slowlog' => 
    array (
      0 => 'array<array-key, mixed>|bool|int',
      'key_or_address' => 'array{0: string, 1: int}|string',
      '...args=' => 'string',
    ),
    'rediscluster::smembers' => 
    array (
      0 => 'list<string>',
      'key' => 'string',
    ),
    'rediscluster::smove' => 
    array (
      0 => 'bool',
      'src' => 'string',
      'dst' => 'string',
      'member' => 'string',
    ),
    'rediscluster::sort' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'rediscluster::spop' => 
    array (
      0 => 'string',
      'key' => 'string',
      'count=' => 'int',
    ),
    'rediscluster::srandmember' => 
    array (
      0 => 'array<array-key, mixed>|string',
      'key' => 'string',
      'count=' => 'int',
    ),
    'rediscluster::srem' => 
    array (
      0 => 'int',
      'key' => 'string',
      'value' => 'string',
      '...other_values=' => 'string',
    ),
    'rediscluster::sscan' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'key' => 'string',
      '&iterator' => 'int|null',
      'pattern=' => 'null',
      'count=' => 'int',
    ),
    'rediscluster::strlen' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'rediscluster::subscribe' => 
    array (
      0 => 'void',
      'channels' => 'array<array-key, mixed>',
      'cb' => 'callable',
    ),
    'rediscluster::sunion' => 
    array (
      0 => 'list<string>',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'rediscluster::sunionstore' => 
    array (
      0 => 'int',
      'dst' => 'string',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'rediscluster::time' => 
    array (
      0 => 'array<array-key, mixed>',
      'key_or_address' => 'array<array-key, mixed>|string',
    ),
    'rediscluster::ttl' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'rediscluster::type' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'rediscluster::unlink' => 
    array (
      0 => 'int',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'rediscluster::watch' => 
    array (
      0 => 'RedisCluster|bool',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'rediscluster::xack' => 
    array (
      0 => 'RedisCluster|false|int',
      'key' => 'string',
      'group' => 'string',
      'ids' => 'array<array-key, mixed>',
    ),
    'rediscluster::xadd' => 
    array (
      0 => 'RedisCluster|false|string',
      'key' => 'string',
      'id' => 'string',
      'values' => 'array<array-key, mixed>',
      'maxlen=' => 'int',
      'approx=' => 'bool',
    ),
    'rediscluster::xclaim' => 
    array (
      0 => 'RedisCluster|array<array-key, mixed>|false|string',
      'key' => 'string',
      'group' => 'string',
      'consumer' => 'string',
      'min_iddle' => 'int',
      'ids' => 'array<array-key, mixed>',
      'options' => 'array<array-key, mixed>',
    ),
    'rediscluster::xdel' => 
    array (
      0 => 'RedisCluster|false|int',
      'key' => 'string',
      'ids' => 'array<array-key, mixed>',
    ),
    'rediscluster::xgroup' => 
    array (
      0 => 'mixed',
      'operation' => 'string',
      'key=' => 'null|string',
      'group=' => 'null|string',
      'id_or_consumer=' => 'null|string',
      'mkstream=' => 'bool',
      'entries_read=' => 'int',
    ),
    'rediscluster::xinfo' => 
    array (
      0 => 'mixed',
      'operation' => 'string',
      'arg1=' => 'null|string',
      'arg2=' => 'null|string',
      'count=' => 'int',
    ),
    'rediscluster::xpending' => 
    array (
      0 => 'RedisCluster|array<array-key, mixed>|false',
      'key' => 'string',
      'group' => 'string',
      'start=' => 'null|string',
      'end=' => 'null|string',
      'count=' => 'int',
      'consumer=' => 'null|string',
    ),
    'rediscluster::xrange' => 
    array (
      0 => 'RedisCluster|array<array-key, mixed>|bool',
      'key' => 'string',
      'start' => 'string',
      'end' => 'string',
      'count=' => 'int',
    ),
    'rediscluster::xread' => 
    array (
      0 => 'RedisCluster|array<array-key, mixed>|bool',
      'streams' => 'array<array-key, mixed>',
      'count=' => 'int',
      'block=' => 'int',
    ),
    'rediscluster::xreadgroup' => 
    array (
      0 => 'RedisCluster|array<array-key, mixed>|bool',
      'group' => 'string',
      'consumer' => 'string',
      'streams' => 'array<array-key, mixed>',
      'count=' => 'int',
      'block=' => 'int',
    ),
    'rediscluster::xrevrange' => 
    array (
      0 => 'RedisCluster|array<array-key, mixed>|bool',
      'key' => 'string',
      'start' => 'string',
      'end' => 'string',
      'count=' => 'int',
    ),
    'rediscluster::xtrim' => 
    array (
      0 => 'RedisCluster|false|int',
      'key' => 'string',
      'maxlen' => 'int',
      'approx=' => 'bool',
      'minid=' => 'bool',
      'limit=' => 'int',
    ),
    'rediscluster::zadd' => 
    array (
      0 => 'int',
      'key' => 'string',
      'score_or_options' => 'float',
      '...more_scores_and_mems=' => 'string',
    ),
    'rediscluster::zcard' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'rediscluster::zcount' => 
    array (
      0 => 'int',
      'key' => 'string',
      'start' => 'string',
      'end' => 'string',
    ),
    'rediscluster::zincrby' => 
    array (
      0 => 'float',
      'key' => 'string',
      'value' => 'float',
      'member' => 'string',
    ),
    'rediscluster::zinterstore' => 
    array (
      0 => 'int',
      'dst' => 'string',
      'keys' => 'array<array-key, mixed>',
      'weights=' => 'array<array-key, mixed>|null',
      'aggregate=' => 'null|string',
    ),
    'rediscluster::zlexcount' => 
    array (
      0 => 'int',
      'key' => 'string',
      'min' => 'string',
      'max' => 'string',
    ),
    'rediscluster::zrange' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
      'options=' => 'bool|null',
    ),
    'rediscluster::zrangebylex' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'min' => 'string',
      'max' => 'string',
      'offset=' => 'int',
      'count=' => 'int',
    ),
    'rediscluster::zrangebyscore' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'start' => 'string',
      'end' => 'string',
      'options=' => 'array<array-key, mixed>',
    ),
    'rediscluster::zrank' => 
    array (
      0 => 'int',
      'key' => 'string',
      'member' => 'string',
    ),
    'rediscluster::zrem' => 
    array (
      0 => 'int',
      'key' => 'string',
      'value' => 'string',
      '...other_values=' => 'string',
    ),
    'rediscluster::zremrangebylex' => 
    array (
      0 => 'RedisCluster|false|int',
      'key' => 'string',
      'min' => 'string',
      'max' => 'string',
    ),
    'rediscluster::zremrangebyrank' => 
    array (
      0 => 'int',
      'key' => 'string',
      'min' => 'string',
      'max' => 'string',
    ),
    'rediscluster::zremrangebyscore' => 
    array (
      0 => 'int',
      'key' => 'string',
      'min' => 'string',
      'max' => 'string',
    ),
    'rediscluster::zrevrange' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'min' => 'string',
      'max' => 'string',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'rediscluster::zrevrangebylex' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'min' => 'string',
      'max' => 'string',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'rediscluster::zrevrangebyscore' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'min' => 'string',
      'max' => 'string',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'rediscluster::zrevrank' => 
    array (
      0 => 'int',
      'key' => 'string',
      'member' => 'string',
    ),
    'rediscluster::zscan' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'key' => 'string',
      '&iterator' => 'int|null',
      'pattern=' => 'null|string',
      'count=' => 'int',
    ),
    'rediscluster::zscore' => 
    array (
      0 => 'float',
      'key' => 'string',
      'member' => 'string',
    ),
    'rediscluster::zunionstore' => 
    array (
      0 => 'int',
      'dst' => 'string',
      'keys' => 'array<array-key, mixed>',
      'weights=' => 'array<array-key, mixed>|null',
      'aggregate=' => 'null|string',
    ),
    'swoole\\atomic::add' => 
    array (
      0 => 'int',
      'add_value=' => 'int',
    ),
    'swoole\\atomic::cmpset' => 
    array (
      0 => 'bool',
      'cmp_value' => 'int',
      'new_value' => 'int',
    ),
    'swoole\\atomic::get' => 
    array (
      0 => 'int',
    ),
    'swoole\\atomic::set' => 
    array (
      0 => 'void',
      'value' => 'int',
    ),
    'swoole\\atomic::sub' => 
    array (
      0 => 'int',
      'sub_value=' => 'int',
    ),
    'swoole\\client::__destruct' => 
    array (
      0 => 'void',
    ),
    'swoole\\client::close' => 
    array (
      0 => 'bool',
      'force=' => 'bool',
    ),
    'swoole\\client::connect' => 
    array (
      0 => 'bool',
      'host' => 'string',
      'port=' => 'int',
      'timeout=' => 'float',
      'sock_flag=' => 'int',
    ),
    'swoole\\client::getpeername' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'swoole\\client::getsockname' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'swoole\\client::isconnected' => 
    array (
      0 => 'bool',
    ),
    'swoole\\client::recv' => 
    array (
      0 => 'false|string',
      'size=' => 'int',
      'flag=' => 'int',
    ),
    'swoole\\client::send' => 
    array (
      0 => 'int',
      'data' => 'string',
      'flag=' => 'int',
    ),
    'swoole\\client::sendfile' => 
    array (
      0 => 'bool',
      'filename' => 'string',
      'offset=' => 'int',
      'length=' => 'int',
    ),
    'swoole\\client::sendto' => 
    array (
      0 => 'bool',
      'ip' => 'string',
      'port' => 'int',
      'data' => 'string',
    ),
    'swoole\\client::set' => 
    array (
      0 => 'bool',
      'settings' => 'array<array-key, mixed>',
    ),
    'swoole\\connection\\iterator::count' => 
    array (
      0 => 'int',
    ),
    'swoole\\connection\\iterator::current' => 
    array (
      0 => 'Connection',
    ),
    'swoole\\connection\\iterator::key' => 
    array (
      0 => 'int',
    ),
    'swoole\\connection\\iterator::next' => 
    array (
      0 => 'void',
    ),
    'swoole\\connection\\iterator::offsetexists' => 
    array (
      0 => 'bool',
      'fd' => 'int',
    ),
    'swoole\\connection\\iterator::offsetget' => 
    array (
      0 => 'Connection',
      'fd' => 'string',
    ),
    'swoole\\connection\\iterator::offsetset' => 
    array (
      0 => 'void',
      'fd' => 'int',
      'value' => 'mixed',
    ),
    'swoole\\connection\\iterator::offsetunset' => 
    array (
      0 => 'void',
      'fd' => 'int',
    ),
    'swoole\\connection\\iterator::rewind' => 
    array (
      0 => 'void',
    ),
    'swoole\\connection\\iterator::valid' => 
    array (
      0 => 'bool',
    ),
    'swoole\\coroutine::create' => 
    array (
      0 => 'false|int',
      'func' => 'callable',
      '...param=' => 'mixed',
    ),
    'swoole\\coroutine::getuid' => 
    array (
      0 => 'int',
    ),
    'swoole\\coroutine::resume' => 
    array (
      0 => 'bool',
      'cid' => 'int',
    ),
    'swoole\\coroutine::suspend' => 
    array (
      0 => 'bool',
    ),
    'swoole\\coroutine\\client::__destruct' => 
    array (
      0 => 'ReturnType',
    ),
    'swoole\\coroutine\\client::close' => 
    array (
      0 => 'bool',
    ),
    'swoole\\coroutine\\client::connect' => 
    array (
      0 => 'bool',
      'host' => 'string',
      'port=' => 'int',
      'timeout=' => 'float',
      'sock_flag=' => 'int',
    ),
    'swoole\\coroutine\\client::getpeername' => 
    array (
      0 => 'array<array-key, mixed>|false',
    ),
    'swoole\\coroutine\\client::getsockname' => 
    array (
      0 => 'array<array-key, mixed>|false',
    ),
    'swoole\\coroutine\\client::isconnected' => 
    array (
      0 => 'bool',
    ),
    'swoole\\coroutine\\client::recv' => 
    array (
      0 => 'false|string',
      'timeout=' => 'float',
    ),
    'swoole\\coroutine\\client::send' => 
    array (
      0 => 'false|int',
      'data' => 'string',
      'timeout=' => 'float',
    ),
    'swoole\\coroutine\\client::sendfile' => 
    array (
      0 => 'bool',
      'filename' => 'string',
      'offset=' => 'int',
      'length=' => 'int',
    ),
    'swoole\\coroutine\\client::sendto' => 
    array (
      0 => 'bool',
      'address' => 'string',
      'port' => 'int',
      'data' => 'string',
    ),
    'swoole\\coroutine\\client::set' => 
    array (
      0 => 'bool',
      'settings' => 'array<array-key, mixed>',
    ),
    'swoole\\coroutine\\http\\client::__destruct' => 
    array (
      0 => 'ReturnType',
    ),
    'swoole\\coroutine\\http\\client::addfile' => 
    array (
      0 => 'bool',
      'path' => 'string',
      'name' => 'string',
      'type=' => 'null|string',
      'filename=' => 'null|string',
      'offset=' => 'int',
      'length=' => 'int',
    ),
    'swoole\\coroutine\\http\\client::close' => 
    array (
      0 => 'bool',
    ),
    'swoole\\coroutine\\http\\client::execute' => 
    array (
      0 => 'bool',
      'path' => 'string',
    ),
    'swoole\\coroutine\\http\\client::get' => 
    array (
      0 => 'bool',
      'path' => 'string',
    ),
    'swoole\\coroutine\\http\\client::getdefer' => 
    array (
      0 => 'bool',
    ),
    'swoole\\coroutine\\http\\client::post' => 
    array (
      0 => 'bool',
      'path' => 'string',
      'data' => 'mixed',
    ),
    'swoole\\coroutine\\http\\client::recv' => 
    array (
      0 => 'Swoole\\WebSocket\\Frame|bool',
      'timeout=' => 'float',
    ),
    'swoole\\coroutine\\http\\client::set' => 
    array (
      0 => 'bool',
      'settings' => 'array<array-key, mixed>',
    ),
    'swoole\\coroutine\\http\\client::setcookies' => 
    array (
      0 => 'bool',
      'cookies' => 'array<array-key, mixed>',
    ),
    'swoole\\coroutine\\http\\client::setdata' => 
    array (
      0 => 'bool',
      'data' => 'array<array-key, mixed>|string',
    ),
    'swoole\\coroutine\\http\\client::setdefer' => 
    array (
      0 => 'bool',
      'defer=' => 'bool',
    ),
    'swoole\\coroutine\\http\\client::setheaders' => 
    array (
      0 => 'bool',
      'headers' => 'array<array-key, mixed>',
    ),
    'swoole\\coroutine\\http\\client::setmethod' => 
    array (
      0 => 'bool',
      'method' => 'string',
    ),
    'swoole\\event::add' => 
    array (
      0 => 'false|int',
      'fd' => 'int',
      'read_callback=' => 'callable|null',
      'write_callback=' => 'callable|null',
      'events=' => 'int',
    ),
    'swoole\\event::defer' => 
    array (
      0 => 'bool',
      'callback' => 'callable',
    ),
    'swoole\\event::del' => 
    array (
      0 => 'bool',
      'fd' => 'string',
    ),
    'swoole\\event::exit' => 
    array (
      0 => 'void',
    ),
    'swoole\\event::set' => 
    array (
      0 => 'bool',
      'fd' => 'int',
      'read_callback=' => 'callable|null',
      'write_callback=' => 'callable|null',
      'events=' => 'int',
    ),
    'swoole\\event::wait' => 
    array (
      0 => 'void',
    ),
    'swoole\\event::write' => 
    array (
      0 => 'bool',
      'fd' => 'string',
      'data' => 'string',
    ),
    'swoole\\http\\request::rawcontent' => 
    array (
      0 => 'string',
    ),
    'swoole\\http\\response::cookie' => 
    array (
      0 => 'bool',
      'name_or_object' => 'string',
      'value=' => 'string',
      'expires=' => 'int',
      'path=' => 'string',
      'domain=' => 'string',
      'secure=' => 'bool',
      'httponly=' => 'bool',
      'samesite=' => 'string',
      'priority=' => 'string',
      'partitioned=' => 'bool',
    ),
    'swoole\\http\\response::end' => 
    array (
      0 => 'bool',
      'content=' => 'null|string',
    ),
    'swoole\\http\\response::header' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'string',
      'format=' => 'bool',
    ),
    'swoole\\http\\response::initheader' => 
    array (
      0 => 'bool',
    ),
    'swoole\\http\\response::rawcookie' => 
    array (
      0 => 'bool',
      'name_or_object' => 'string',
      'value=' => 'string',
      'expires=' => 'int',
      'path=' => 'string',
      'domain=' => 'string',
      'secure=' => 'bool',
      'httponly=' => 'bool',
      'samesite=' => 'string',
      'priority=' => 'string',
      'partitioned=' => 'bool',
    ),
    'swoole\\http\\response::sendfile' => 
    array (
      0 => 'bool',
      'filename' => 'string',
      'offset=' => 'int',
      'length=' => 'int',
    ),
    'swoole\\http\\response::status' => 
    array (
      0 => 'bool',
      'http_code' => 'int',
      'reason=' => 'string',
    ),
    'swoole\\http\\response::write' => 
    array (
      0 => 'bool',
      'content' => 'string',
    ),
    'swoole\\http\\server::on' => 
    array (
      0 => 'bool',
      'event_name' => 'string',
      'callback' => 'callable',
    ),
    'swoole\\http\\server::start' => 
    array (
      0 => 'bool',
    ),
    'swoole\\lock::lock' => 
    array (
      0 => 'bool',
      'operation=' => 'int',
      'timeout=' => 'float',
    ),
    'swoole\\lock::unlock' => 
    array (
      0 => 'bool',
    ),
    'swoole\\process::__destruct' => 
    array (
      0 => 'void',
    ),
    'swoole\\process::alarm' => 
    array (
      0 => 'bool',
      'usec' => 'int',
      'type=' => 'int',
    ),
    'swoole\\process::close' => 
    array (
      0 => 'bool',
      'which=' => 'int',
    ),
    'swoole\\process::daemon' => 
    array (
      0 => 'bool',
      'nochdir=' => 'bool',
      'noclose=' => 'bool',
      'pipes=' => 'array<array-key, mixed>',
    ),
    'swoole\\process::exec' => 
    array (
      0 => 'bool',
      'exec_file' => 'string',
      'args' => 'array<array-key, mixed>',
    ),
    'swoole\\process::exit' => 
    array (
      0 => 'void',
      'exit_code=' => 'int',
    ),
    'swoole\\process::freequeue' => 
    array (
      0 => 'bool',
    ),
    'swoole\\process::kill' => 
    array (
      0 => 'bool',
      'pid' => 'int',
      'signal_no=' => 'int',
    ),
    'swoole\\process::name' => 
    array (
      0 => 'bool',
      'process_name' => 'string',
    ),
    'swoole\\process::pop' => 
    array (
      0 => 'false|string',
      'size=' => 'int',
    ),
    'swoole\\process::push' => 
    array (
      0 => 'bool',
      'data' => 'string',
    ),
    'swoole\\process::read' => 
    array (
      0 => 'string',
      'size=' => 'int',
    ),
    'swoole\\process::signal' => 
    array (
      0 => 'bool',
      'signal_no' => 'int',
      'callback=' => 'callable|null',
    ),
    'swoole\\process::start' => 
    array (
      0 => 'bool|int',
    ),
    'swoole\\process::statqueue' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'swoole\\process::usequeue' => 
    array (
      0 => 'bool',
      'key=' => 'int',
      'mode=' => 'int',
      'capacity=' => 'int',
    ),
    'swoole\\process::wait' => 
    array (
      0 => 'array<array-key, mixed>',
      'blocking=' => 'bool',
    ),
    'swoole\\process::write' => 
    array (
      0 => 'int',
      'data' => 'string',
    ),
    'swoole\\redis\\server::format' => 
    array (
      0 => 'false|string',
      'type' => 'int',
      'value=' => 'string',
    ),
    'swoole\\redis\\server::sethandler' => 
    array (
      0 => 'bool',
      'command' => 'string',
      'callback' => 'callable',
    ),
    'swoole\\redis\\server::start' => 
    array (
      0 => 'bool',
    ),
    'swoole\\server::addlistener' => 
    array (
      0 => 'Swoole\\Server\\Port|false',
      'host' => 'string',
      'port' => 'int',
      'sock_type' => 'int',
    ),
    'swoole\\server::addprocess' => 
    array (
      0 => 'false|int',
      'process' => 'Swoole\\Process',
    ),
    'swoole\\server::bind' => 
    array (
      0 => 'bool',
      'fd' => 'int',
      'uid' => 'int',
    ),
    'swoole\\server::close' => 
    array (
      0 => 'bool',
      'fd' => 'int',
      'reset=' => 'bool',
    ),
    'swoole\\server::confirm' => 
    array (
      0 => 'bool',
      'fd' => 'int',
    ),
    'swoole\\server::connection_info' => 
    array (
      0 => 'array<array-key, mixed>',
      'fd' => 'int',
      'reactor_id=' => 'int',
      'ignoreError=' => 'bool',
    ),
    'swoole\\server::connection_list' => 
    array (
      0 => 'array<array-key, mixed>',
      'start_fd=' => 'int',
      'find_count=' => 'int',
    ),
    'swoole\\server::exist' => 
    array (
      0 => 'bool',
      'fd' => 'int',
    ),
    'swoole\\server::finish' => 
    array (
      0 => 'bool',
      'data' => 'string',
    ),
    'swoole\\server::getclientinfo' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'fd' => 'int',
      'reactor_id=' => 'int',
      'ignoreError=' => 'bool',
    ),
    'swoole\\server::getclientlist' => 
    array (
      0 => 'array<array-key, mixed>',
      'start_fd=' => 'int',
      'find_count=' => 'int',
    ),
    'swoole\\server::getlasterror' => 
    array (
      0 => 'int',
    ),
    'swoole\\server::heartbeat' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'ifCloseConnection=' => 'bool',
    ),
    'swoole\\server::listen' => 
    array (
      0 => 'Swoole\\Server\\Port|false',
      'host' => 'string',
      'port' => 'int',
      'sock_type' => 'int',
    ),
    'swoole\\server::on' => 
    array (
      0 => 'bool',
      'event_name' => 'string',
      'callback' => 'callable',
    ),
    'swoole\\server::pause' => 
    array (
      0 => 'bool',
      'fd' => 'int',
    ),
    'swoole\\server::protect' => 
    array (
      0 => 'bool',
      'fd' => 'int',
      'is_protected=' => 'bool',
    ),
    'swoole\\server::reload' => 
    array (
      0 => 'bool',
      'only_reload_taskworker=' => 'bool',
    ),
    'swoole\\server::resume' => 
    array (
      0 => 'bool',
      'fd' => 'int',
    ),
    'swoole\\server::send' => 
    array (
      0 => 'bool',
      'fd' => 'int',
      'send_data' => 'string',
      'serverSocket=' => 'int',
    ),
    'swoole\\server::sendfile' => 
    array (
      0 => 'bool',
      'conn_fd' => 'int',
      'filename' => 'string',
      'offset=' => 'int',
      'length=' => 'int',
    ),
    'swoole\\server::sendmessage' => 
    array (
      0 => 'bool',
      'message' => 'int',
      'dst_worker_id' => 'int',
    ),
    'swoole\\server::sendto' => 
    array (
      0 => 'bool',
      'ip' => 'string',
      'port' => 'int',
      'send_data' => 'string',
      'server_socket=' => 'int',
    ),
    'swoole\\server::sendwait' => 
    array (
      0 => 'bool',
      'conn_fd' => 'int',
      'send_data' => 'string',
    ),
    'swoole\\server::set' => 
    array (
      0 => 'bool',
      'settings' => 'array<array-key, mixed>',
    ),
    'swoole\\server::shutdown' => 
    array (
      0 => 'bool',
    ),
    'swoole\\server::start' => 
    array (
      0 => 'bool',
    ),
    'swoole\\server::stats' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'swoole\\server::stop' => 
    array (
      0 => 'bool',
      'workerId=' => 'int',
    ),
    'swoole\\server::task' => 
    array (
      0 => 'false|int',
      'data' => 'string',
      'taskWorkerIndex=' => 'int',
      'finishCallback=' => 'callable|null',
    ),
    'swoole\\server::taskwait' => 
    array (
      0 => 'void',
      'data' => 'string',
      'timeout=' => 'float',
      'taskWorkerIndex=' => 'int',
    ),
    'swoole\\server::taskwaitmulti' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'tasks' => 'array<array-key, mixed>',
      'timeout=' => 'float',
    ),
    'swoole\\server\\port::__destruct' => 
    array (
      0 => 'void',
    ),
    'swoole\\server\\port::on' => 
    array (
      0 => 'bool',
      'event_name' => 'string',
      'callback' => 'callable',
    ),
    'swoole\\server\\port::set' => 
    array (
      0 => 'void',
      'settings' => 'array<array-key, mixed>',
    ),
    'swoole\\table::column' => 
    array (
      0 => 'bool',
      'name' => 'string',
      'type' => 'int',
      'size=' => 'int',
    ),
    'swoole\\table::count' => 
    array (
      0 => 'int',
    ),
    'swoole\\table::create' => 
    array (
      0 => 'bool',
    ),
    'swoole\\table::current' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'swoole\\table::decr' => 
    array (
      0 => 'float|int',
      'key' => 'string',
      'column' => 'string',
      'incrby=' => 'int',
    ),
    'swoole\\table::del' => 
    array (
      0 => 'bool',
      'key' => 'string',
    ),
    'swoole\\table::destroy' => 
    array (
      0 => 'bool',
    ),
    'swoole\\table::exist' => 
    array (
      0 => 'bool',
      'key' => 'string',
    ),
    'swoole\\table::get' => 
    array (
      0 => 'int',
      'key' => 'string',
      'field=' => 'null|string',
    ),
    'swoole\\table::incr' => 
    array (
      0 => 'float|int',
      'key' => 'string',
      'column' => 'string',
      'incrby=' => 'int',
    ),
    'swoole\\table::key' => 
    array (
      0 => 'string',
    ),
    'swoole\\table::next' => 
    array (
      0 => 'void',
    ),
    'swoole\\table::rewind' => 
    array (
      0 => 'void',
    ),
    'swoole\\table::set' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'array<array-key, mixed>',
    ),
    'swoole\\table::valid' => 
    array (
      0 => 'bool',
    ),
    'swoole\\timer::after' => 
    array (
      0 => 'false|int',
      'ms' => 'int',
      'callback' => 'callable',
      '...params=' => 'mixed',
    ),
    'swoole\\timer::clear' => 
    array (
      0 => 'bool',
      'timer_id' => 'int',
    ),
    'swoole\\timer::exists' => 
    array (
      0 => 'bool',
      'timer_id' => 'int',
    ),
    'swoole\\timer::tick' => 
    array (
      0 => 'false|int',
      'ms' => 'int',
      'callback' => 'callable',
      '...params=' => 'string',
    ),
    'swoole\\websocket\\server::exist' => 
    array (
      0 => 'bool',
      'fd' => 'int',
    ),
    'swoole\\websocket\\server::on' => 
    array (
      0 => 'bool',
      'event_name' => 'string',
      'callback' => 'callable',
    ),
    'swoole\\websocket\\server::pack' => 
    array (
      0 => 'string',
      'data' => 'string',
      'opcode=' => 'int',
      'flags=' => 'int',
    ),
    'swoole\\websocket\\server::push' => 
    array (
      0 => 'bool',
      'fd' => 'int',
      'data' => 'string',
      'opcode=' => 'int',
      'flags=' => 'int',
    ),
    'swoole\\websocket\\server::unpack' => 
    array (
      0 => 'Swoole\\WebSocket\\Frame',
      'data' => 'string',
    ),
    'swoole_async_set' => 
    array (
      0 => 'bool',
      'settings' => 'array<array-key, mixed>',
    ),
    'swoole_client_select' => 
    array (
      0 => 'int',
      '&read' => 'array<array-key, mixed>|null',
      '&write' => 'array<array-key, mixed>|null',
      '&except' => 'array<array-key, mixed>|null',
      'timeout=' => 'float|null',
    ),
    'swoole_cpu_num' => 
    array (
      0 => 'int',
    ),
    'swoole_errno' => 
    array (
      0 => 'int',
    ),
    'swoole_event_add' => 
    array (
      0 => 'int',
      'fd' => 'int',
      'read_callback=' => 'callable|null',
      'write_callback=' => 'callable|null',
      'events=' => 'int',
    ),
    'swoole_event_defer' => 
    array (
      0 => 'bool',
      'callback' => 'callable',
    ),
    'swoole_event_del' => 
    array (
      0 => 'bool',
      'fd' => 'int',
    ),
    'swoole_event_exit' => 
    array (
      0 => 'void',
    ),
    'swoole_event_set' => 
    array (
      0 => 'bool',
      'fd' => 'int',
      'read_callback=' => 'callable|null',
      'write_callback=' => 'callable|null',
      'events=' => 'int',
    ),
    'swoole_event_wait' => 
    array (
      0 => 'void',
    ),
    'swoole_event_write' => 
    array (
      0 => 'bool',
      'fd' => 'int',
      'data' => 'string',
    ),
    'swoole_get_local_ip' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'swoole_last_error' => 
    array (
      0 => 'int',
    ),
    'swoole_select' => 
    array (
      0 => 'int',
      '&read' => 'array<array-key, mixed>|null',
      '&write' => 'array<array-key, mixed>|null',
      '&except' => 'array<array-key, mixed>|null',
      'timeout=' => 'float|null',
    ),
    'swoole_set_process_name' => 
    array (
      0 => 'bool',
      'process_name' => 'string',
    ),
    'swoole_strerror' => 
    array (
      0 => 'string',
      'errno' => 'int',
      'error_type=' => 'int',
    ),
    'swoole_timer_after' => 
    array (
      0 => 'int',
      'ms' => 'int',
      'callback' => 'callable',
    ),
    'swoole_timer_exists' => 
    array (
      0 => 'bool',
      'timer_id' => 'int',
    ),
    'swoole_timer_tick' => 
    array (
      0 => 'int',
      'ms' => 'int',
      'callback' => 'callable',
    ),
    'swoole_version' => 
    array (
      0 => 'string',
    ),
    'zmqcontext::__construct' => 
    array (
      0 => 'void',
      'io_threads=' => 'int',
      'persistent=' => 'bool',
    ),
    'zmqcontext::getopt' => 
    array (
      0 => 'int|string',
      'option' => 'string',
    ),
    'zmqcontext::getsocket' => 
    array (
      0 => 'ZMQSocket',
      'type' => 'int',
      'dsn' => 'string',
      'on_new_socket=' => 'callable',
    ),
    'zmqcontext::ispersistent' => 
    array (
      0 => 'bool',
    ),
    'zmqcontext::setopt' => 
    array (
      0 => 'ZMQContext',
      'option' => 'int',
      'value' => 'mixed',
    ),
    'zmqdevice::getidletimeout' => 
    array (
      0 => 'ZMQDevice',
    ),
    'zmqdevice::gettimertimeout' => 
    array (
      0 => 'ZMQDevice',
    ),
    'zmqdevice::run' => 
    array (
      0 => 'void',
    ),
    'zmqdevice::setidlecallback' => 
    array (
      0 => 'ZMQDevice',
      'idle_callback' => 'callable',
      'timeout' => 'int',
      'user_data=' => 'mixed',
    ),
    'zmqdevice::setidletimeout' => 
    array (
      0 => 'ZMQDevice',
      'timeout' => 'int',
    ),
    'zmqdevice::settimercallback' => 
    array (
      0 => 'ZMQDevice',
      'idle_callback' => 'callable',
      'timeout' => 'int',
      'user_data=' => 'mixed',
    ),
    'zmqdevice::settimertimeout' => 
    array (
      0 => 'ZMQDevice',
      'timeout' => 'int',
    ),
    'zmqpoll::add' => 
    array (
      0 => 'string',
      'entry' => 'mixed',
      'type' => 'int',
    ),
    'zmqpoll::clear' => 
    array (
      0 => 'ZMQPoll',
    ),
    'zmqpoll::count' => 
    array (
      0 => 'int',
    ),
    'zmqpoll::getlasterrors' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'zmqpoll::poll' => 
    array (
      0 => 'int',
      '&w readable' => 'array<array-key, mixed>',
      '&w writable' => 'array<array-key, mixed>',
      'timeout=' => 'int',
    ),
    'zmqpoll::remove' => 
    array (
      0 => 'bool',
      'remove' => 'mixed',
    ),
    'zmqsocket::__construct' => 
    array (
      0 => 'void',
      'ZMQContext' => 'ZMQContext',
      'type' => 'int',
      'persistent_id=' => 'string',
      'on_new_socket=' => 'callable',
    ),
    'zmqsocket::bind' => 
    array (
      0 => 'ZMQSocket',
      'dsn' => 'string',
      'force=' => 'bool',
    ),
    'zmqsocket::connect' => 
    array (
      0 => 'ZMQSocket',
      'dsn' => 'string',
      'force=' => 'bool',
    ),
    'zmqsocket::disconnect' => 
    array (
      0 => 'ZMQSocket',
      'dsn' => 'string',
    ),
    'zmqsocket::getendpoints' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'zmqsocket::getpersistentid' => 
    array (
      0 => 'null|string',
    ),
    'zmqsocket::getsockettype' => 
    array (
      0 => 'int',
    ),
    'zmqsocket::getsockopt' => 
    array (
      0 => 'int|string',
      'key' => 'string',
    ),
    'zmqsocket::ispersistent' => 
    array (
      0 => 'bool',
    ),
    'zmqsocket::recv' => 
    array (
      0 => 'string',
      'mode=' => 'int',
    ),
    'zmqsocket::recvmulti' => 
    array (
      0 => 'array<array-key, string>',
      'mode=' => 'int',
    ),
    'zmqsocket::send' => 
    array (
      0 => 'ZMQSocket',
      'message' => 'array<array-key, mixed>',
      'mode=' => 'int',
    ),
    'zmqsocket::sendmulti' => 
    array (
      0 => 'ZMQSocket',
      'message' => 'array<array-key, mixed>',
      'mode=' => 'int',
    ),
    'zmqsocket::setsockopt' => 
    array (
      0 => 'ZMQSocket',
      'key' => 'int',
      'value' => 'mixed',
    ),
    'zmqsocket::unbind' => 
    array (
      0 => 'ZMQSocket',
      'dsn' => 'string',
    ),
    'zookeeper::addauth' => 
    array (
      0 => 'bool',
      'scheme' => 'string',
      'cert' => 'string',
      'completion_cb=' => 'callable',
    ),
    'zookeeper::close' => 
    array (
      0 => 'void',
    ),
    'zookeeper::connect' => 
    array (
      0 => 'void',
      'host' => 'string',
      'watcher_cb=' => 'callable',
      'recv_timeout=' => 'int',
    ),
    'zookeeper::create' => 
    array (
      0 => 'string',
      'path' => 'string',
      'value=' => 'string',
      'acl=' => 'array<array-key, mixed>',
      'flags=' => 'int',
    ),
    'zookeeper::delete' => 
    array (
      0 => 'bool',
      'path' => 'string',
      'version=' => 'int',
    ),
    'zookeeper::exists' => 
    array (
      0 => 'bool',
      'path' => 'string',
      'watcher_cb=' => 'callable',
    ),
    'zookeeper::get' => 
    array (
      0 => 'string',
      'path' => 'string',
      'watcher_cb=' => 'callable',
      '&stat_info=' => 'array<array-key, mixed>',
      'max_size=' => 'int',
    ),
    'zookeeper::getacl' => 
    array (
      0 => 'array<array-key, mixed>',
      'path' => 'string',
    ),
    'zookeeper::getchildren' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'path' => 'string',
      'watcher_cb=' => 'callable',
    ),
    'zookeeper::getclientid' => 
    array (
      0 => 'int',
    ),
    'zookeeper::getconfig' => 
    array (
      0 => 'ZookeeperConfig',
    ),
    'zookeeper::getrecvtimeout' => 
    array (
      0 => 'int',
    ),
    'zookeeper::getstate' => 
    array (
      0 => 'int',
    ),
    'zookeeper::isrecoverable' => 
    array (
      0 => 'bool',
    ),
    'zookeeper::set' => 
    array (
      0 => 'bool',
      'path' => 'string',
      'value=' => 'string',
      'version=' => 'int',
      '&stat_info=' => 'array<array-key, mixed>',
    ),
    'zookeeper::setacl' => 
    array (
      0 => 'bool',
      'path' => 'string',
      'version' => 'int',
      'acl' => 'array<array-key, mixed>',
    ),
    'zookeeper::setdebuglevel' => 
    array (
      0 => 'bool',
      'level' => 'int',
    ),
    'zookeeper::setdeterministicconnorder' => 
    array (
      0 => 'bool',
      'trueOrFalse' => 'bool',
    ),
    'zookeeper::setlogstream' => 
    array (
      0 => 'bool',
      'stream' => 'resource',
    ),
    'zookeeper::setwatcher' => 
    array (
      0 => 'bool',
      'watcher_cb' => 'callable',
    ),
    'zookeeper_dispatch' => 
    array (
      0 => 'void',
    ),
    'zookeeperconfig::add' => 
    array (
      0 => 'void',
      'members' => 'string',
      'version=' => 'int',
      '&stat_info=' => 'array<array-key, mixed>',
    ),
    'zookeeperconfig::get' => 
    array (
      0 => 'string',
      'watcher_cb' => 'callable',
      '&stat_info' => 'array<array-key, mixed>',
    ),
    'zookeeperconfig::remove' => 
    array (
      0 => 'void',
      'members' => 'string',
      'version=' => 'int',
      '&stat_info=' => 'array<array-key, mixed>',
    ),
    'zookeeperconfig::set' => 
    array (
      0 => 'void',
      'members' => 'string',
      'version=' => 'int',
      '&stat_info=' => 'array<array-key, mixed>',
    ),
  ),
);