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
    'error_get_last' => 
    array (
      'old' => 
      array (
        0 => 'array{file: string, line: int, message: string, type: int}|null',
      ),
      'new' => 
      array (
        0 => 'array{file: string, line: int, message: string, trace?: list<array{args?: list<mixed>, class?: class-string, file?: string, function: string, line?: int, object?: object, type?: string}>, type: int}|null',
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
    'IntlTimeZone::createEnumeration' => 
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
        'resolver_function' => 'impure-callable(string, string, array{directory: null|string, extSubSystem: null|string, extSubURI: null|string, intSubName: null|string}):(null|resource|string)|null',
      ),
      'new' => 
      array (
        0 => 'true',
        'resolver_function' => 'impure-callable(string, string, array{directory: null|string, extSubSystem: null|string, extSubURI: null|string, intSubName: null|string}):(null|resource|string)|null',
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
        'callback' => 'impure-callable',
      ),
      'new' => 
      array (
        0 => 'true',
        'prompt' => 'string',
        'callback' => 'impure-callable',
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
    'SoapClient::__doRequest' => 
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
    'SoapFault::__construct' => 
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
    'SoapServer::fault' => 
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
    'SplFileObject::fwrite' => 
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
    'SplTempFileObject::fwrite' => 
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
    'AMQPBasicProperties::getAppId' => 
    array (
      0 => 'null|string',
    ),
    'AMQPBasicProperties::getClusterId' => 
    array (
      0 => 'null|string',
    ),
    'AMQPBasicProperties::getContentEncoding' => 
    array (
      0 => 'null|string',
    ),
    'AMQPBasicProperties::getContentType' => 
    array (
      0 => 'null|string',
    ),
    'AMQPBasicProperties::getCorrelationId' => 
    array (
      0 => 'null|string',
    ),
    'AMQPBasicProperties::getDeliveryMode' => 
    array (
      0 => 'int',
    ),
    'AMQPBasicProperties::getExpiration' => 
    array (
      0 => 'null|string',
    ),
    'AMQPBasicProperties::getHeaders' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'AMQPBasicProperties::getMessageId' => 
    array (
      0 => 'null|string',
    ),
    'AMQPBasicProperties::getPriority' => 
    array (
      0 => 'int',
    ),
    'AMQPBasicProperties::getReplyTo' => 
    array (
      0 => 'null|string',
    ),
    'AMQPBasicProperties::getTimestamp' => 
    array (
      0 => 'int|null',
    ),
    'AMQPBasicProperties::getType' => 
    array (
      0 => 'null|string',
    ),
    'AMQPBasicProperties::getUserId' => 
    array (
      0 => 'null|string',
    ),
    'AMQPChannel::basicRecover' => 
    array (
      0 => 'void',
      'requeue=' => 'bool',
    ),
    'AMQPChannel::commitTransaction' => 
    array (
      0 => 'void',
    ),
    'AMQPChannel::getChannelId' => 
    array (
      0 => 'int',
    ),
    'AMQPChannel::getConnection' => 
    array (
      0 => 'AMQPConnection',
    ),
    'AMQPChannel::getConsumers' => 
    array (
      0 => 'array<array-key, AMQPQueue>',
    ),
    'AMQPChannel::getPrefetchCount' => 
    array (
      0 => 'int',
    ),
    'AMQPChannel::getPrefetchSize' => 
    array (
      0 => 'int',
    ),
    'AMQPChannel::isConnected' => 
    array (
      0 => 'bool',
    ),
    'AMQPChannel::qos' => 
    array (
      0 => 'void',
      'size' => 'int',
      'count' => 'int',
      'global=' => 'bool',
    ),
    'AMQPChannel::rollbackTransaction' => 
    array (
      0 => 'void',
    ),
    'AMQPChannel::setConfirmCallback' => 
    array (
      0 => 'void',
      'ackCallback' => 'impure-callable|null',
      'nackCallback=' => 'impure-callable|null',
    ),
    'AMQPChannel::setPrefetchCount' => 
    array (
      0 => 'void',
      'count' => 'int',
    ),
    'AMQPChannel::setPrefetchSize' => 
    array (
      0 => 'void',
      'size' => 'int',
    ),
    'AMQPChannel::setReturnCallback' => 
    array (
      0 => 'void',
      'returnCallback' => 'impure-callable|null',
    ),
    'AMQPChannel::startTransaction' => 
    array (
      0 => 'void',
    ),
    'AMQPChannel::waitForBasicReturn' => 
    array (
      0 => 'void',
      'timeout=' => 'float',
    ),
    'AMQPChannel::waitForConfirm' => 
    array (
      0 => 'void',
      'timeout=' => 'float',
    ),
    'AMQPConnection::connect' => 
    array (
      0 => 'void',
    ),
    'AMQPConnection::disconnect' => 
    array (
      0 => 'void',
    ),
    'AMQPConnection::getCACert' => 
    array (
      0 => 'null|string',
    ),
    'AMQPConnection::getCert' => 
    array (
      0 => 'null|string',
    ),
    'AMQPConnection::getHeartbeatInterval' => 
    array (
      0 => 'int',
    ),
    'AMQPConnection::getHost' => 
    array (
      0 => 'string',
    ),
    'AMQPConnection::getKey' => 
    array (
      0 => 'null|string',
    ),
    'AMQPConnection::getLogin' => 
    array (
      0 => 'string',
    ),
    'AMQPConnection::getMaxChannels' => 
    array (
      0 => 'int',
    ),
    'AMQPConnection::getMaxFrameSize' => 
    array (
      0 => 'int',
    ),
    'AMQPConnection::getPassword' => 
    array (
      0 => 'string',
    ),
    'AMQPConnection::getPort' => 
    array (
      0 => 'int',
    ),
    'AMQPConnection::getReadTimeout' => 
    array (
      0 => 'float',
    ),
    'AMQPConnection::getTimeout' => 
    array (
      0 => 'float',
    ),
    'AMQPConnection::getUsedChannels' => 
    array (
      0 => 'int',
    ),
    'AMQPConnection::getVerify' => 
    array (
      0 => 'bool',
    ),
    'AMQPConnection::getVhost' => 
    array (
      0 => 'string',
    ),
    'AMQPConnection::getWriteTimeout' => 
    array (
      0 => 'float',
    ),
    'AMQPConnection::isConnected' => 
    array (
      0 => 'bool',
    ),
    'AMQPConnection::isPersistent' => 
    array (
      0 => 'bool',
    ),
    'AMQPConnection::pconnect' => 
    array (
      0 => 'void',
    ),
    'AMQPConnection::pdisconnect' => 
    array (
      0 => 'void',
    ),
    'AMQPConnection::preconnect' => 
    array (
      0 => 'void',
    ),
    'AMQPConnection::reconnect' => 
    array (
      0 => 'void',
    ),
    'AMQPConnection::setCACert' => 
    array (
      0 => 'void',
      'cacert' => 'null|string',
    ),
    'AMQPConnection::setCert' => 
    array (
      0 => 'void',
      'cert' => 'null|string',
    ),
    'AMQPConnection::setHost' => 
    array (
      0 => 'void',
      'host' => 'string',
    ),
    'AMQPConnection::setKey' => 
    array (
      0 => 'void',
      'key' => 'null|string',
    ),
    'AMQPConnection::setLogin' => 
    array (
      0 => 'void',
      'login' => 'string',
    ),
    'AMQPConnection::setPassword' => 
    array (
      0 => 'void',
      'password' => 'string',
    ),
    'AMQPConnection::setPort' => 
    array (
      0 => 'void',
      'port' => 'int',
    ),
    'AMQPConnection::setReadTimeout' => 
    array (
      0 => 'void',
      'timeout' => 'float',
    ),
    'AMQPConnection::setTimeout' => 
    array (
      0 => 'void',
      'timeout' => 'float',
    ),
    'AMQPConnection::setVerify' => 
    array (
      0 => 'void',
      'verify' => 'bool',
    ),
    'AMQPConnection::setVhost' => 
    array (
      0 => 'void',
      'vhost' => 'string',
    ),
    'AMQPConnection::setWriteTimeout' => 
    array (
      0 => 'void',
      'timeout' => 'float',
    ),
    'AMQPDecimal::getExponent' => 
    array (
      0 => 'int',
    ),
    'AMQPDecimal::getSignificand' => 
    array (
      0 => 'int',
    ),
    'AMQPEnvelope::getAppId' => 
    array (
      0 => 'null|string',
    ),
    'AMQPEnvelope::getBody' => 
    array (
      0 => 'string',
    ),
    'AMQPEnvelope::getClusterId' => 
    array (
      0 => 'null|string',
    ),
    'AMQPEnvelope::getConsumerTag' => 
    array (
      0 => 'null|string',
    ),
    'AMQPEnvelope::getContentEncoding' => 
    array (
      0 => 'null|string',
    ),
    'AMQPEnvelope::getContentType' => 
    array (
      0 => 'null|string',
    ),
    'AMQPEnvelope::getCorrelationId' => 
    array (
      0 => 'null|string',
    ),
    'AMQPEnvelope::getDeliveryMode' => 
    array (
      0 => 'int',
    ),
    'AMQPEnvelope::getDeliveryTag' => 
    array (
      0 => 'int|null',
    ),
    'AMQPEnvelope::getExchangeName' => 
    array (
      0 => 'null|string',
    ),
    'AMQPEnvelope::getExpiration' => 
    array (
      0 => 'null|string',
    ),
    'AMQPEnvelope::getHeader' => 
    array (
      0 => 'false|string',
      'headerName' => 'string',
    ),
    'AMQPEnvelope::getHeaders' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'AMQPEnvelope::getMessageId' => 
    array (
      0 => 'null|string',
    ),
    'AMQPEnvelope::getPriority' => 
    array (
      0 => 'int',
    ),
    'AMQPEnvelope::getReplyTo' => 
    array (
      0 => 'null|string',
    ),
    'AMQPEnvelope::getRoutingKey' => 
    array (
      0 => 'string',
    ),
    'AMQPEnvelope::getTimestamp' => 
    array (
      0 => 'int|null',
    ),
    'AMQPEnvelope::getType' => 
    array (
      0 => 'null|string',
    ),
    'AMQPEnvelope::getUserId' => 
    array (
      0 => 'null|string',
    ),
    'AMQPEnvelope::hasHeader' => 
    array (
      0 => 'bool',
      'headerName' => 'string',
    ),
    'AMQPEnvelope::isRedelivery' => 
    array (
      0 => 'bool',
    ),
    'AMQPExchange::bind' => 
    array (
      0 => 'void',
      'exchangeName' => 'string',
      'routingKey=' => 'null|string',
      'arguments=' => 'array<array-key, mixed>',
    ),
    'AMQPExchange::declareExchange' => 
    array (
      0 => 'void',
    ),
    'AMQPExchange::delete' => 
    array (
      0 => 'void',
      'exchangeName=' => 'null|string',
      'flags=' => 'int|null',
    ),
    'AMQPExchange::getArgument' => 
    array (
      0 => 'false|int|string',
      'argumentName' => 'string',
    ),
    'AMQPExchange::getArguments' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'AMQPExchange::getChannel' => 
    array (
      0 => 'AMQPChannel',
    ),
    'AMQPExchange::getConnection' => 
    array (
      0 => 'AMQPConnection',
    ),
    'AMQPExchange::getFlags' => 
    array (
      0 => 'int',
    ),
    'AMQPExchange::getName' => 
    array (
      0 => 'null|string',
    ),
    'AMQPExchange::getType' => 
    array (
      0 => 'null|string',
    ),
    'AMQPExchange::hasArgument' => 
    array (
      0 => 'bool',
      'argumentName' => 'string',
    ),
    'AMQPExchange::publish' => 
    array (
      0 => 'void',
      'message' => 'string',
      'routingKey=' => 'null|string',
      'flags=' => 'int|null',
      'headers=' => 'array<array-key, mixed>',
    ),
    'AMQPExchange::setArgument' => 
    array (
      0 => 'void',
      'argumentName' => 'string',
      'argumentValue' => 'int|string',
    ),
    'AMQPExchange::setArguments' => 
    array (
      0 => 'void',
      'arguments' => 'array<array-key, mixed>',
    ),
    'AMQPExchange::setFlags' => 
    array (
      0 => 'void',
      'flags' => 'int|null',
    ),
    'AMQPExchange::setName' => 
    array (
      0 => 'void',
      'exchangeName' => 'null|string',
    ),
    'AMQPExchange::setType' => 
    array (
      0 => 'void',
      'exchangeType' => 'null|string',
    ),
    'AMQPExchange::unbind' => 
    array (
      0 => 'void',
      'exchangeName' => 'string',
      'routingKey=' => 'null|string',
      'arguments=' => 'array<array-key, mixed>',
    ),
    'AMQPQueue::ack' => 
    array (
      0 => 'void',
      'deliveryTag' => 'int',
      'flags=' => 'int|null',
    ),
    'AMQPQueue::bind' => 
    array (
      0 => 'void',
      'exchangeName' => 'string',
      'routingKey=' => 'null|string',
      'arguments=' => 'array<array-key, mixed>',
    ),
    'AMQPQueue::cancel' => 
    array (
      0 => 'void',
      'consumerTag=' => 'string',
    ),
    'AMQPQueue::consume' => 
    array (
      0 => 'void',
      'callback=' => 'impure-callable|null',
      'flags=' => 'int|null',
      'consumerTag=' => 'null|string',
    ),
    'AMQPQueue::declareQueue' => 
    array (
      0 => 'int',
    ),
    'AMQPQueue::delete' => 
    array (
      0 => 'int',
      'flags=' => 'int|null',
    ),
    'AMQPQueue::get' => 
    array (
      0 => 'AMQPEnvelope|null',
      'flags=' => 'int|null',
    ),
    'AMQPQueue::getArgument' => 
    array (
      0 => 'false|int|string',
      'argumentName' => 'string',
    ),
    'AMQPQueue::getArguments' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'AMQPQueue::getChannel' => 
    array (
      0 => 'AMQPChannel',
    ),
    'AMQPQueue::getConnection' => 
    array (
      0 => 'AMQPConnection',
    ),
    'AMQPQueue::getConsumerTag' => 
    array (
      0 => 'null|string',
    ),
    'AMQPQueue::getFlags' => 
    array (
      0 => 'int',
    ),
    'AMQPQueue::getName' => 
    array (
      0 => 'null|string',
    ),
    'AMQPQueue::hasArgument' => 
    array (
      0 => 'bool',
      'argumentName' => 'string',
    ),
    'AMQPQueue::nack' => 
    array (
      0 => 'void',
      'deliveryTag' => 'int',
      'flags=' => 'int|null',
    ),
    'AMQPQueue::purge' => 
    array (
      0 => 'int',
    ),
    'AMQPQueue::reject' => 
    array (
      0 => 'void',
      'deliveryTag' => 'int',
      'flags=' => 'int|null',
    ),
    'AMQPQueue::setArgument' => 
    array (
      0 => 'void',
      'argumentName' => 'string',
      'argumentValue' => 'mixed',
    ),
    'AMQPQueue::setArguments' => 
    array (
      0 => 'void',
      'arguments' => 'array<array-key, mixed>',
    ),
    'AMQPQueue::setFlags' => 
    array (
      0 => 'void',
      'flags' => 'int|null',
    ),
    'AMQPQueue::setName' => 
    array (
      0 => 'void',
      'name' => 'string',
    ),
    'AMQPQueue::unbind' => 
    array (
      0 => 'void',
      'exchangeName' => 'string',
      'routingKey=' => 'null|string',
      'arguments=' => 'array<array-key, mixed>',
    ),
    'AMQPTimestamp::__construct' => 
    array (
      0 => 'void',
      'timestamp' => 'float',
    ),
    'AMQPTimestamp::__toString' => 
    array (
      0 => 'string',
    ),
    'AMQPTimestamp::getTimestamp' => 
    array (
      0 => 'float',
    ),
    'Grpc\\Call::__construct' => 
    array (
      0 => 'void',
      'channel' => 'Grpc\\Channel',
      'method' => 'string',
      'deadline' => 'Grpc\\Timeval',
      'host_override=' => 'mixed',
    ),
    'Grpc\\Call::getPeer' => 
    array (
      0 => 'string',
    ),
    'Grpc\\Call::setCredentials' => 
    array (
      0 => 'int',
      'credentials' => 'Grpc\\CallCredentials',
    ),
    'Grpc\\Call::startBatch' => 
    array (
      0 => 'object',
      'ops' => 'array<array-key, mixed>',
    ),
    'Grpc\\CallCredentials::createComposite' => 
    array (
      0 => 'Grpc\\CallCredentials',
      'creds1' => 'Grpc\\CallCredentials',
      'creds2' => 'Grpc\\CallCredentials',
    ),
    'Grpc\\CallCredentials::createFromPlugin' => 
    array (
      0 => 'Grpc\\CallCredentials',
      'callback' => 'impure-Closure',
    ),
    'Grpc\\Channel::__construct' => 
    array (
      0 => 'void',
      'target' => 'string',
      'args' => 'array<array-key, mixed>',
    ),
    'Grpc\\Channel::getConnectivityState' => 
    array (
      0 => 'int',
      'try_to_connect=' => 'bool',
    ),
    'Grpc\\Channel::getTarget' => 
    array (
      0 => 'string',
    ),
    'Grpc\\Channel::watchConnectivityState' => 
    array (
      0 => 'bool',
      'last_state' => 'int',
      'deadline' => 'Grpc\\Timeval',
    ),
    'Grpc\\ChannelCredentials::createComposite' => 
    array (
      0 => 'Grpc\\ChannelCredentials',
      'channel_creds' => 'Grpc\\ChannelCredentials',
      'call_creds' => 'Grpc\\CallCredentials',
    ),
    'Grpc\\ChannelCredentials::createDefault' => 
    array (
      0 => 'Grpc\\ChannelCredentials',
    ),
    'Grpc\\ChannelCredentials::createInsecure' => 
    array (
      0 => 'null',
    ),
    'Grpc\\ChannelCredentials::createSsl' => 
    array (
      0 => 'Grpc\\ChannelCredentials',
      'pem_root_certs=' => 'string',
      'pem_private_key=' => 'string',
      'pem_cert_chain=' => 'string',
    ),
    'Grpc\\ChannelCredentials::setDefaultRootsPem' => 
    array (
      0 => 'mixed',
      'pem_roots' => 'string',
    ),
    'Grpc\\Server::__construct' => 
    array (
      0 => 'void',
      'args=' => 'array<array-key, mixed>',
    ),
    'Grpc\\Server::addHttp2Port' => 
    array (
      0 => 'bool',
      'addr' => 'string',
    ),
    'Grpc\\Server::addSecureHttp2Port' => 
    array (
      0 => 'bool',
      'addr' => 'string',
      'server_creds' => 'Grpc\\ServerCredentials',
    ),
    'Grpc\\ServerCredentials::createSsl' => 
    array (
      0 => 'object',
      'pem_root_certs' => 'string',
      'pem_private_key' => 'string',
      'pem_cert_chain' => 'string',
    ),
    'Grpc\\Timeval::__construct' => 
    array (
      0 => 'void',
      'microseconds' => 'int',
    ),
    'Grpc\\Timeval::add' => 
    array (
      0 => 'Grpc\\Timeval',
      'timeval' => 'Grpc\\Timeval',
    ),
    'Grpc\\Timeval::compare' => 
    array (
      0 => 'int',
      'a_timeval' => 'Grpc\\Timeval',
      'b_timeval' => 'Grpc\\Timeval',
    ),
    'Grpc\\Timeval::infFuture' => 
    array (
      0 => 'Grpc\\Timeval',
    ),
    'Grpc\\Timeval::infPast' => 
    array (
      0 => 'Grpc\\Timeval',
    ),
    'Grpc\\Timeval::now' => 
    array (
      0 => 'Grpc\\Timeval',
    ),
    'Grpc\\Timeval::similar' => 
    array (
      0 => 'bool',
      'a_timeval' => 'Grpc\\Timeval',
      'b_timeval' => 'Grpc\\Timeval',
      'threshold_timeval' => 'Grpc\\Timeval',
    ),
    'Grpc\\Timeval::subtract' => 
    array (
      0 => 'Grpc\\Timeval',
      'timeval' => 'Grpc\\Timeval',
    ),
    'Grpc\\Timeval::zero' => 
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
    'Imagick::__construct' => 
    array (
      0 => 'void',
      'files=' => 'array<array-key, string>|null|string',
    ),
    'Imagick::__toString' => 
    array (
      0 => 'string',
    ),
    'Imagick::adaptiveBlurImage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'channel=' => 'int',
    ),
    'Imagick::adaptiveResizeImage' => 
    array (
      0 => 'bool',
      'columns' => 'int',
      'rows' => 'int',
      'bestfit=' => 'bool',
      'legacy=' => 'bool',
    ),
    'Imagick::adaptiveSharpenImage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'channel=' => 'int',
    ),
    'Imagick::adaptiveThresholdImage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'offset' => 'int',
    ),
    'Imagick::addImage' => 
    array (
      0 => 'bool',
      'image' => 'Imagick',
    ),
    'Imagick::addNoiseImage' => 
    array (
      0 => 'bool',
      'noise' => 'int',
      'channel=' => 'int',
    ),
    'Imagick::affineTransformImage' => 
    array (
      0 => 'bool',
      'settings' => 'ImagickDraw',
    ),
    'Imagick::animateImages' => 
    array (
      0 => 'bool',
      'x_server' => 'string',
    ),
    'Imagick::annotateImage' => 
    array (
      0 => 'bool',
      'settings' => 'ImagickDraw',
      'x' => 'float',
      'y' => 'float',
      'angle' => 'float',
      'text' => 'string',
    ),
    'Imagick::appendImages' => 
    array (
      0 => 'Imagick',
      'stack' => 'bool',
    ),
    'Imagick::autoGammaImage' => 
    array (
      0 => 'void',
      'channel=' => 'int|null',
    ),
    'Imagick::autoLevelImage' => 
    array (
      0 => 'bool',
      'channel=' => 'int',
    ),
    'Imagick::autoOrient' => 
    array (
      0 => 'void',
    ),
    'Imagick::averageImages' => 
    array (
      0 => 'Imagick',
    ),
    'Imagick::blackThresholdImage' => 
    array (
      0 => 'bool',
      'threshold_color' => 'ImagickPixel|string',
    ),
    'Imagick::blueShiftImage' => 
    array (
      0 => 'bool',
      'factor=' => 'float',
    ),
    'Imagick::blurImage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'channel=' => 'int',
    ),
    'Imagick::borderImage' => 
    array (
      0 => 'bool',
      'border_color' => 'ImagickPixel|string',
      'width' => 'int',
      'height' => 'int',
    ),
    'Imagick::brightnessContrastImage' => 
    array (
      0 => 'bool',
      'brightness' => 'float',
      'contrast' => 'float',
      'channel=' => 'int',
    ),
    'Imagick::charcoalImage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
    ),
    'Imagick::chopImage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'x' => 'int',
      'y' => 'int',
    ),
    'Imagick::clampImage' => 
    array (
      0 => 'bool',
      'channel=' => 'int',
    ),
    'Imagick::clear' => 
    array (
      0 => 'bool',
    ),
    'Imagick::clipImage' => 
    array (
      0 => 'bool',
    ),
    'Imagick::clipImagePath' => 
    array (
      0 => 'void',
      'pathname' => 'string',
      'inside' => 'bool',
    ),
    'Imagick::clipPathImage' => 
    array (
      0 => 'bool',
      'pathname' => 'string',
      'inside' => 'bool',
    ),
    'Imagick::clone' => 
    array (
      0 => 'Imagick',
    ),
    'Imagick::clutImage' => 
    array (
      0 => 'bool',
      'lookup_table' => 'Imagick',
      'channel=' => 'int',
    ),
    'Imagick::coalesceImages' => 
    array (
      0 => 'Imagick',
    ),
    'Imagick::colorizeImage' => 
    array (
      0 => 'bool',
      'colorize_color' => 'ImagickPixel|string',
      'opacity_color' => 'ImagickPixel|false|string',
      'legacy=' => 'bool|null',
    ),
    'Imagick::colorMatrixImage' => 
    array (
      0 => 'bool',
      'color_matrix' => 'array<array-key, mixed>',
    ),
    'Imagick::combineImages' => 
    array (
      0 => 'Imagick',
      'colorspace' => 'int',
    ),
    'Imagick::commentImage' => 
    array (
      0 => 'bool',
      'comment' => 'string',
    ),
    'Imagick::compareImageChannels' => 
    array (
      0 => 'list{Imagick, float}',
      'reference' => 'Imagick',
      'channel' => 'int',
      'metric' => 'int',
    ),
    'Imagick::compareImageLayers' => 
    array (
      0 => 'Imagick',
      'metric' => 'int',
    ),
    'Imagick::compareImages' => 
    array (
      0 => 'list{Imagick, float}',
      'reference' => 'Imagick',
      'metric' => 'int',
    ),
    'Imagick::compositeImage' => 
    array (
      0 => 'bool',
      'composite_image' => 'Imagick',
      'composite' => 'int',
      'x' => 'int',
      'y' => 'int',
      'channel=' => 'int',
    ),
    'Imagick::compositeImageGravity' => 
    array (
      0 => 'bool',
      'image' => 'Imagick',
      'composite_constant' => 'int',
      'gravity' => 'int',
    ),
    'Imagick::contrastImage' => 
    array (
      0 => 'bool',
      'sharpen' => 'bool',
    ),
    'Imagick::contrastStretchImage' => 
    array (
      0 => 'bool',
      'black_point' => 'float',
      'white_point' => 'float',
      'channel=' => 'int',
    ),
    'Imagick::convolveImage' => 
    array (
      0 => 'bool',
      'kernel' => 'ImagickKernel',
      'channel=' => 'int',
    ),
    'Imagick::count' => 
    array (
      0 => 'int',
      'mode=' => 'int',
    ),
    'Imagick::cropImage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'x' => 'int',
      'y' => 'int',
    ),
    'Imagick::cropThumbnailImage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'legacy=' => 'bool',
    ),
    'Imagick::current' => 
    array (
      0 => 'Imagick',
    ),
    'Imagick::cycleColormapImage' => 
    array (
      0 => 'bool',
      'displace' => 'int',
    ),
    'Imagick::decipherImage' => 
    array (
      0 => 'bool',
      'passphrase' => 'string',
    ),
    'Imagick::deconstructImages' => 
    array (
      0 => 'Imagick',
    ),
    'Imagick::deleteImageArtifact' => 
    array (
      0 => 'bool',
      'artifact' => 'string',
    ),
    'Imagick::deleteImageProperty' => 
    array (
      0 => 'bool',
      'name' => 'string',
    ),
    'Imagick::deskewImage' => 
    array (
      0 => 'bool',
      'threshold' => 'float',
    ),
    'Imagick::despeckleImage' => 
    array (
      0 => 'bool',
    ),
    'Imagick::destroy' => 
    array (
      0 => 'bool',
    ),
    'Imagick::displayImage' => 
    array (
      0 => 'bool',
      'servername' => 'string',
    ),
    'Imagick::displayImages' => 
    array (
      0 => 'bool',
      'servername' => 'string',
    ),
    'Imagick::distortImage' => 
    array (
      0 => 'bool',
      'distortion' => 'int',
      'arguments' => 'array<array-key, mixed>',
      'bestfit' => 'bool',
    ),
    'Imagick::drawImage' => 
    array (
      0 => 'bool',
      'drawing' => 'ImagickDraw',
    ),
    'Imagick::edgeImage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
    ),
    'Imagick::embossImage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
    ),
    'Imagick::encipherImage' => 
    array (
      0 => 'bool',
      'passphrase' => 'string',
    ),
    'Imagick::enhanceImage' => 
    array (
      0 => 'bool',
    ),
    'Imagick::equalizeImage' => 
    array (
      0 => 'bool',
    ),
    'Imagick::evaluateImage' => 
    array (
      0 => 'bool',
      'evaluate' => 'int',
      'constant' => 'float',
      'channel=' => 'int',
    ),
    'Imagick::evaluateImages' => 
    array (
      0 => 'Imagick',
      'evaluate' => 'int',
    ),
    'Imagick::exportImagePixels' => 
    array (
      0 => 'list<int>',
      'x' => 'int',
      'y' => 'int',
      'width' => 'int',
      'height' => 'int',
      'map' => 'string',
      'pixelstorage' => 'int',
    ),
    'Imagick::extentImage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'x' => 'int',
      'y' => 'int',
    ),
    'Imagick::flattenImages' => 
    array (
      0 => 'Imagick',
    ),
    'Imagick::flipImage' => 
    array (
      0 => 'bool',
    ),
    'Imagick::floodfillPaintImage' => 
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
    'Imagick::flopImage' => 
    array (
      0 => 'bool',
    ),
    'Imagick::forwardFourierTransformImage' => 
    array (
      0 => 'bool',
      'magnitude' => 'bool',
    ),
    'Imagick::frameImage' => 
    array (
      0 => 'bool',
      'matte_color' => 'ImagickPixel|string',
      'width' => 'int',
      'height' => 'int',
      'inner_bevel' => 'int',
      'outer_bevel' => 'int',
    ),
    'Imagick::functionImage' => 
    array (
      0 => 'bool',
      'function' => 'int',
      'parameters' => 'array<array-key, mixed>',
      'channel=' => 'int',
    ),
    'Imagick::fxImage' => 
    array (
      0 => 'Imagick',
      'expression' => 'string',
      'channel=' => 'int',
    ),
    'Imagick::gammaImage' => 
    array (
      0 => 'bool',
      'gamma' => 'float',
      'channel=' => 'int',
    ),
    'Imagick::gaussianBlurImage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'channel=' => 'int',
    ),
    'Imagick::getColorspace' => 
    array (
      0 => 'int',
    ),
    'Imagick::getCompression' => 
    array (
      0 => 'int',
    ),
    'Imagick::getCompressionQuality' => 
    array (
      0 => 'int',
    ),
    'Imagick::getConfigureOptions' => 
    array (
      0 => 'array<array-key, mixed>',
      'pattern=' => 'string',
    ),
    'Imagick::getCopyright' => 
    array (
      0 => 'string',
    ),
    'Imagick::getFeatures' => 
    array (
      0 => 'string',
    ),
    'Imagick::getFilename' => 
    array (
      0 => 'string',
    ),
    'Imagick::getFont' => 
    array (
      0 => 'string',
    ),
    'Imagick::getFormat' => 
    array (
      0 => 'string',
    ),
    'Imagick::getGravity' => 
    array (
      0 => 'int',
    ),
    'Imagick::getHdriEnabled' => 
    array (
      0 => 'bool',
    ),
    'Imagick::getHomeURL' => 
    array (
      0 => 'string',
    ),
    'Imagick::getImage' => 
    array (
      0 => 'Imagick',
    ),
    'Imagick::getImageAlphaChannel' => 
    array (
      0 => 'bool',
    ),
    'Imagick::getImageArtifact' => 
    array (
      0 => 'null|string',
      'artifact' => 'string',
    ),
    'Imagick::getImageBackgroundColor' => 
    array (
      0 => 'ImagickPixel',
    ),
    'Imagick::getImageBlob' => 
    array (
      0 => 'string',
    ),
    'Imagick::getImageBluePrimary' => 
    array (
      0 => 'array{x: float, y: float}',
    ),
    'Imagick::getImageBorderColor' => 
    array (
      0 => 'ImagickPixel',
    ),
    'Imagick::getImageChannelDepth' => 
    array (
      0 => 'int',
      'channel' => 'int',
    ),
    'Imagick::getImageChannelDistortion' => 
    array (
      0 => 'float',
      'reference' => 'Imagick',
      'channel' => 'int',
      'metric' => 'int',
    ),
    'Imagick::getImageChannelDistortions' => 
    array (
      0 => 'float',
      'reference_image' => 'Imagick',
      'metric' => 'int',
      'channel=' => 'int',
    ),
    'Imagick::getImageChannelKurtosis' => 
    array (
      0 => 'array{kurtosis: float, skewness: float}',
      'channel=' => 'int',
    ),
    'Imagick::getImageChannelMean' => 
    array (
      0 => 'array{mean: float, standardDeviation: float}',
      'channel' => 'int',
    ),
    'Imagick::getImageChannelRange' => 
    array (
      0 => 'array{maxima: float, minima: float}',
      'channel' => 'int',
    ),
    'Imagick::getImageChannelStatistics' => 
    array (
      0 => 'array<int, array{depth: int, maxima: float, mean: float, minima: float, standardDeviation: float}>',
    ),
    'Imagick::getImageColormapColor' => 
    array (
      0 => 'ImagickPixel',
      'index' => 'int',
    ),
    'Imagick::getImageColors' => 
    array (
      0 => 'int',
    ),
    'Imagick::getImageColorspace' => 
    array (
      0 => 'int',
    ),
    'Imagick::getImageCompose' => 
    array (
      0 => 'int',
    ),
    'Imagick::getImageCompression' => 
    array (
      0 => 'int',
    ),
    'Imagick::getImageCompressionQuality' => 
    array (
      0 => 'int',
    ),
    'Imagick::getImageDelay' => 
    array (
      0 => 'int',
    ),
    'Imagick::getImageDepth' => 
    array (
      0 => 'int',
    ),
    'Imagick::getImageDispose' => 
    array (
      0 => 'int',
    ),
    'Imagick::getImageDistortion' => 
    array (
      0 => 'float',
      'reference' => 'Imagick',
      'metric' => 'int',
    ),
    'Imagick::getImageFilename' => 
    array (
      0 => 'string',
    ),
    'Imagick::getImageFormat' => 
    array (
      0 => 'string',
    ),
    'Imagick::getImageGamma' => 
    array (
      0 => 'float',
    ),
    'Imagick::getImageGeometry' => 
    array (
      0 => 'array{height: int, width: int}',
    ),
    'Imagick::getImageGravity' => 
    array (
      0 => 'int',
    ),
    'Imagick::getImageGreenPrimary' => 
    array (
      0 => 'array{x: float, y: float}',
    ),
    'Imagick::getImageHeight' => 
    array (
      0 => 'int',
    ),
    'Imagick::getImageHistogram' => 
    array (
      0 => 'list<ImagickPixel>',
    ),
    'Imagick::getImageIndex' => 
    array (
      0 => 'int',
    ),
    'Imagick::getImageInterlaceScheme' => 
    array (
      0 => 'int',
    ),
    'Imagick::getImageInterpolateMethod' => 
    array (
      0 => 'int',
    ),
    'Imagick::getImageIterations' => 
    array (
      0 => 'int',
    ),
    'Imagick::getImageLength' => 
    array (
      0 => 'int',
    ),
    'Imagick::getImageMimeType' => 
    array (
      0 => 'string',
    ),
    'Imagick::getImageOrientation' => 
    array (
      0 => 'int',
    ),
    'Imagick::getImagePage' => 
    array (
      0 => 'array{height: int, width: int, x: int, y: int}',
    ),
    'Imagick::getImagePixelColor' => 
    array (
      0 => 'ImagickPixel',
      'x' => 'int',
      'y' => 'int',
    ),
    'Imagick::getImageProfile' => 
    array (
      0 => 'string',
      'name' => 'string',
    ),
    'Imagick::getImageProfiles' => 
    array (
      0 => 'array<array-key, mixed>',
      'pattern=' => 'string',
      'include_values=' => 'bool',
    ),
    'Imagick::getImageProperties' => 
    array (
      0 => 'array<int|string, string>',
      'pattern=' => 'string',
      'include_values=' => 'bool',
    ),
    'Imagick::getImageProperty' => 
    array (
      0 => 'string',
      'name' => 'string',
    ),
    'Imagick::getImageRedPrimary' => 
    array (
      0 => 'array{x: float, y: float}',
    ),
    'Imagick::getImageRegion' => 
    array (
      0 => 'Imagick',
      'width' => 'int',
      'height' => 'int',
      'x' => 'int',
      'y' => 'int',
    ),
    'Imagick::getImageRenderingIntent' => 
    array (
      0 => 'int',
    ),
    'Imagick::getImageResolution' => 
    array (
      0 => 'array{x: float, y: float}',
    ),
    'Imagick::getImagesBlob' => 
    array (
      0 => 'string',
    ),
    'Imagick::getImageScene' => 
    array (
      0 => 'int',
    ),
    'Imagick::getImageSignature' => 
    array (
      0 => 'string',
    ),
    'Imagick::getImageSize' => 
    array (
      0 => 'int',
    ),
    'Imagick::getImageTicksPerSecond' => 
    array (
      0 => 'int',
    ),
    'Imagick::getImageTotalInkDensity' => 
    array (
      0 => 'float',
    ),
    'Imagick::getImageType' => 
    array (
      0 => 'int',
    ),
    'Imagick::getImageUnits' => 
    array (
      0 => 'int',
    ),
    'Imagick::getImageVirtualPixelMethod' => 
    array (
      0 => 'int',
    ),
    'Imagick::getImageWhitePoint' => 
    array (
      0 => 'array{x: float, y: float}',
    ),
    'Imagick::getImageWidth' => 
    array (
      0 => 'int',
    ),
    'Imagick::getInterlaceScheme' => 
    array (
      0 => 'int',
    ),
    'Imagick::getIteratorIndex' => 
    array (
      0 => 'int',
    ),
    'Imagick::getNumberImages' => 
    array (
      0 => 'int',
    ),
    'Imagick::getOption' => 
    array (
      0 => 'string',
      'key' => 'string',
    ),
    'Imagick::getPackageName' => 
    array (
      0 => 'string',
    ),
    'Imagick::getPage' => 
    array (
      0 => 'array{height: int, width: int, x: int, y: int}',
    ),
    'Imagick::getPixelIterator' => 
    array (
      0 => 'ImagickPixelIterator',
    ),
    'Imagick::getPixelRegionIterator' => 
    array (
      0 => 'ImagickPixelIterator',
      'x' => 'int',
      'y' => 'int',
      'columns' => 'int',
      'rows' => 'int',
    ),
    'Imagick::getPointSize' => 
    array (
      0 => 'float',
    ),
    'Imagick::getQuantum' => 
    array (
      0 => 'int',
    ),
    'Imagick::getQuantumDepth' => 
    array (
      0 => 'array{quantumDepthLong: int, quantumDepthString: string}',
    ),
    'Imagick::getQuantumRange' => 
    array (
      0 => 'array{quantumRangeLong: int, quantumRangeString: string}',
    ),
    'Imagick::getRegistry' => 
    array (
      0 => 'false|string',
      'key' => 'string',
    ),
    'Imagick::getReleaseDate' => 
    array (
      0 => 'string',
    ),
    'Imagick::getResource' => 
    array (
      0 => 'int',
      'type' => 'int',
    ),
    'Imagick::getResourceLimit' => 
    array (
      0 => 'float',
      'type' => 'int',
    ),
    'Imagick::getSamplingFactors' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'Imagick::getSize' => 
    array (
      0 => 'array{columns: int, rows: int}',
    ),
    'Imagick::getSizeOffset' => 
    array (
      0 => 'int',
    ),
    'Imagick::getVersion' => 
    array (
      0 => 'array{versionNumber: int, versionString: string}',
    ),
    'Imagick::haldClutImage' => 
    array (
      0 => 'bool',
      'clut' => 'Imagick',
      'channel=' => 'int',
    ),
    'Imagick::hasNextImage' => 
    array (
      0 => 'bool',
    ),
    'Imagick::hasPreviousImage' => 
    array (
      0 => 'bool',
    ),
    'Imagick::identifyFormat' => 
    array (
      0 => 'string',
      'format' => 'string',
    ),
    'Imagick::identifyImage' => 
    array (
      0 => 'array<string, mixed>',
      'append_raw_output=' => 'bool',
    ),
    'Imagick::identifyImageType' => 
    array (
      0 => 'int',
    ),
    'Imagick::implodeImage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
    ),
    'Imagick::importImagePixels' => 
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
    'Imagick::inverseFourierTransformImage' => 
    array (
      0 => 'bool',
      'complement' => 'Imagick',
      'magnitude' => 'bool',
    ),
    'Imagick::key' => 
    array (
      0 => 'int',
    ),
    'Imagick::labelImage' => 
    array (
      0 => 'bool',
      'label' => 'string',
    ),
    'Imagick::levelImage' => 
    array (
      0 => 'bool',
      'black_point' => 'float',
      'gamma' => 'float',
      'white_point' => 'float',
      'channel=' => 'int',
    ),
    'Imagick::linearStretchImage' => 
    array (
      0 => 'bool',
      'black_point' => 'float',
      'white_point' => 'float',
    ),
    'Imagick::liquidRescaleImage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'delta_x' => 'float',
      'rigidity' => 'float',
    ),
    'Imagick::listRegistry' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'Imagick::localContrastImage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'strength' => 'float',
    ),
    'Imagick::magnifyImage' => 
    array (
      0 => 'bool',
    ),
    'Imagick::mergeImageLayers' => 
    array (
      0 => 'Imagick',
      'layermethod' => 'int',
    ),
    'Imagick::minifyImage' => 
    array (
      0 => 'bool',
    ),
    'Imagick::modulateImage' => 
    array (
      0 => 'bool',
      'brightness' => 'float',
      'saturation' => 'float',
      'hue' => 'float',
    ),
    'Imagick::montageImage' => 
    array (
      0 => 'Imagick',
      'settings' => 'ImagickDraw',
      'tile_geometry' => 'string',
      'thumbnail_geometry' => 'string',
      'monatgemode' => 'int',
      'frame' => 'string',
    ),
    'Imagick::morphImages' => 
    array (
      0 => 'Imagick',
      'number_frames' => 'int',
    ),
    'Imagick::morphology' => 
    array (
      0 => 'bool',
      'morphology' => 'int',
      'iterations' => 'int',
      'kernel' => 'ImagickKernel',
      'channel=' => 'int',
    ),
    'Imagick::motionBlurImage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'angle' => 'float',
      'channel=' => 'int',
    ),
    'Imagick::negateImage' => 
    array (
      0 => 'bool',
      'gray' => 'bool',
      'channel=' => 'int',
    ),
    'Imagick::newImage' => 
    array (
      0 => 'bool',
      'columns' => 'int',
      'rows' => 'int',
      'background_color' => 'ImagickPixel|string',
      'format=' => 'null|string',
    ),
    'Imagick::newPseudoImage' => 
    array (
      0 => 'bool',
      'columns' => 'int',
      'rows' => 'int',
      'pseudo_format' => 'string',
    ),
    'Imagick::next' => 
    array (
      0 => 'void',
    ),
    'Imagick::nextImage' => 
    array (
      0 => 'bool',
    ),
    'Imagick::normalizeImage' => 
    array (
      0 => 'bool',
      'channel=' => 'int',
    ),
    'Imagick::oilPaintImage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
    ),
    'Imagick::opaquePaintImage' => 
    array (
      0 => 'bool',
      'target_color' => 'ImagickPixel|string',
      'fill_color' => 'ImagickPixel|string',
      'fuzz' => 'float',
      'invert' => 'bool',
      'channel=' => 'int',
    ),
    'Imagick::optimizeImageLayers' => 
    array (
      0 => 'Imagick',
    ),
    'Imagick::pingImage' => 
    array (
      0 => 'bool',
      'filename' => 'string',
    ),
    'Imagick::pingImageBlob' => 
    array (
      0 => 'bool',
      'image' => 'string',
    ),
    'Imagick::pingImageFile' => 
    array (
      0 => 'bool',
      'filehandle' => 'resource',
      'filename=' => 'null|string',
    ),
    'Imagick::polaroidImage' => 
    array (
      0 => 'bool',
      'settings' => 'ImagickDraw',
      'angle' => 'float',
    ),
    'Imagick::posterizeImage' => 
    array (
      0 => 'bool',
      'levels' => 'int',
      'dither' => 'bool',
    ),
    'Imagick::previewImages' => 
    array (
      0 => 'bool',
      'preview' => 'int',
    ),
    'Imagick::previousImage' => 
    array (
      0 => 'bool',
    ),
    'Imagick::profileImage' => 
    array (
      0 => 'bool',
      'name' => 'string',
      'profile' => 'null|string',
    ),
    'Imagick::quantizeImage' => 
    array (
      0 => 'bool',
      'number_colors' => 'int',
      'colorspace' => 'int',
      'tree_depth' => 'int',
      'dither' => 'bool',
      'measure_error' => 'bool',
    ),
    'Imagick::quantizeImages' => 
    array (
      0 => 'bool',
      'number_colors' => 'int',
      'colorspace' => 'int',
      'tree_depth' => 'int',
      'dither' => 'bool',
      'measure_error' => 'bool',
    ),
    'Imagick::queryFontMetrics' => 
    array (
      0 => 'array<array-key, mixed>',
      'settings' => 'ImagickDraw',
      'text' => 'string',
      'multiline=' => 'bool|null',
    ),
    'Imagick::queryFonts' => 
    array (
      0 => 'array<array-key, mixed>',
      'pattern=' => 'string',
    ),
    'Imagick::queryFormats' => 
    array (
      0 => 'list<string>',
      'pattern=' => 'string',
    ),
    'Imagick::raiseImage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'x' => 'int',
      'y' => 'int',
      'raise' => 'bool',
    ),
    'Imagick::randomThresholdImage' => 
    array (
      0 => 'bool',
      'low' => 'float',
      'high' => 'float',
      'channel=' => 'int',
    ),
    'Imagick::readImage' => 
    array (
      0 => 'bool',
      'filename' => 'string',
    ),
    'Imagick::readImageBlob' => 
    array (
      0 => 'bool',
      'image' => 'string',
      'filename=' => 'null|string',
    ),
    'Imagick::readImageFile' => 
    array (
      0 => 'bool',
      'filehandle' => 'resource',
      'filename=' => 'null|string',
    ),
    'Imagick::readImages' => 
    array (
      0 => 'bool',
      'filenames' => 'array<array-key, mixed>',
    ),
    'Imagick::remapImage' => 
    array (
      0 => 'bool',
      'replacement' => 'Imagick',
      'dither_method' => 'int',
    ),
    'Imagick::removeImage' => 
    array (
      0 => 'bool',
    ),
    'Imagick::removeImageProfile' => 
    array (
      0 => 'string',
      'name' => 'string',
    ),
    'Imagick::resampleImage' => 
    array (
      0 => 'bool',
      'x_resolution' => 'float',
      'y_resolution' => 'float',
      'filter' => 'int',
      'blur' => 'float',
    ),
    'Imagick::resetImagePage' => 
    array (
      0 => 'bool',
      'page' => 'string',
    ),
    'Imagick::resizeImage' => 
    array (
      0 => 'bool',
      'columns' => 'int',
      'rows' => 'int',
      'filter' => 'int',
      'blur' => 'float',
      'bestfit=' => 'bool',
      'legacy=' => 'bool',
    ),
    'Imagick::rewind' => 
    array (
      0 => 'void',
    ),
    'Imagick::rollImage' => 
    array (
      0 => 'bool',
      'x' => 'int',
      'y' => 'int',
    ),
    'Imagick::rotateImage' => 
    array (
      0 => 'bool',
      'background_color' => 'ImagickPixel|string',
      'degrees' => 'float',
    ),
    'Imagick::rotationalBlurImage' => 
    array (
      0 => 'bool',
      'angle' => 'float',
      'channel=' => 'int',
    ),
    'Imagick::roundCorners' => 
    array (
      0 => 'bool',
      'x_rounding' => 'float',
      'y_rounding' => 'float',
      'stroke_width=' => 'float',
      'displace=' => 'float',
      'size_correction=' => 'float',
    ),
    'Imagick::sampleImage' => 
    array (
      0 => 'bool',
      'columns' => 'int',
      'rows' => 'int',
    ),
    'Imagick::scaleImage' => 
    array (
      0 => 'bool',
      'columns' => 'int',
      'rows' => 'int',
      'bestfit=' => 'bool',
      'legacy=' => 'bool',
    ),
    'Imagick::segmentImage' => 
    array (
      0 => 'bool',
      'colorspace' => 'int',
      'cluster_threshold' => 'float',
      'smooth_threshold' => 'float',
      'verbose=' => 'bool',
    ),
    'Imagick::selectiveBlurImage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'threshold' => 'float',
      'channel=' => 'int',
    ),
    'Imagick::separateImageChannel' => 
    array (
      0 => 'bool',
      'channel' => 'int',
    ),
    'Imagick::sepiaToneImage' => 
    array (
      0 => 'bool',
      'threshold' => 'float',
    ),
    'Imagick::setAntialias' => 
    array (
      0 => 'void',
      'antialias' => 'bool',
    ),
    'Imagick::setBackgroundColor' => 
    array (
      0 => 'bool',
      'background_color' => 'ImagickPixel|string',
    ),
    'Imagick::setColorspace' => 
    array (
      0 => 'bool',
      'colorspace' => 'int',
    ),
    'Imagick::setCompression' => 
    array (
      0 => 'bool',
      'compression' => 'int',
    ),
    'Imagick::setCompressionQuality' => 
    array (
      0 => 'bool',
      'quality' => 'int',
    ),
    'Imagick::setFilename' => 
    array (
      0 => 'bool',
      'filename' => 'string',
    ),
    'Imagick::setFirstIterator' => 
    array (
      0 => 'bool',
    ),
    'Imagick::setFont' => 
    array (
      0 => 'bool',
      'font' => 'string',
    ),
    'Imagick::setFormat' => 
    array (
      0 => 'bool',
      'format' => 'string',
    ),
    'Imagick::setGravity' => 
    array (
      0 => 'bool',
      'gravity' => 'int',
    ),
    'Imagick::setImage' => 
    array (
      0 => 'bool',
      'image' => 'Imagick',
    ),
    'Imagick::setImageAlpha' => 
    array (
      0 => 'bool',
      'alpha' => 'float',
    ),
    'Imagick::setImageAlphaChannel' => 
    array (
      0 => 'bool',
      'alphachannel' => 'int',
    ),
    'Imagick::setImageArtifact' => 
    array (
      0 => 'bool',
      'artifact' => 'string',
      'value' => 'null|string',
    ),
    'Imagick::setImageBackgroundColor' => 
    array (
      0 => 'bool',
      'background_color' => 'ImagickPixel|string',
    ),
    'Imagick::setImageBluePrimary' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
      'z' => 'float',
    ),
    'Imagick::setImageBorderColor' => 
    array (
      0 => 'bool',
      'border_color' => 'ImagickPixel|string',
    ),
    'Imagick::setImageChannelDepth' => 
    array (
      0 => 'bool',
      'channel' => 'int',
      'depth' => 'int',
    ),
    'Imagick::setImageChannelMask' => 
    array (
      0 => 'int',
      'channel' => 'int',
    ),
    'Imagick::setImageColormapColor' => 
    array (
      0 => 'bool',
      'index' => 'int',
      'color' => 'ImagickPixel',
    ),
    'Imagick::setImageColorspace' => 
    array (
      0 => 'bool',
      'colorspace' => 'int',
    ),
    'Imagick::setImageCompose' => 
    array (
      0 => 'bool',
      'compose' => 'int',
    ),
    'Imagick::setImageCompression' => 
    array (
      0 => 'bool',
      'compression' => 'int',
    ),
    'Imagick::setImageCompressionQuality' => 
    array (
      0 => 'bool',
      'quality' => 'int',
    ),
    'Imagick::setImageDelay' => 
    array (
      0 => 'bool',
      'delay' => 'int',
    ),
    'Imagick::setImageDepth' => 
    array (
      0 => 'bool',
      'depth' => 'int',
    ),
    'Imagick::setImageDispose' => 
    array (
      0 => 'bool',
      'dispose' => 'int',
    ),
    'Imagick::setImageExtent' => 
    array (
      0 => 'bool',
      'columns' => 'int',
      'rows' => 'int',
    ),
    'Imagick::setImageFilename' => 
    array (
      0 => 'bool',
      'filename' => 'string',
    ),
    'Imagick::setImageFormat' => 
    array (
      0 => 'bool',
      'format' => 'string',
    ),
    'Imagick::setImageGamma' => 
    array (
      0 => 'bool',
      'gamma' => 'float',
    ),
    'Imagick::setImageGravity' => 
    array (
      0 => 'bool',
      'gravity' => 'int',
    ),
    'Imagick::setImageGreenPrimary' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
      'z' => 'float',
    ),
    'Imagick::setImageIndex' => 
    array (
      0 => 'bool',
      'index' => 'int',
    ),
    'Imagick::setImageInterlaceScheme' => 
    array (
      0 => 'bool',
      'interlace' => 'int',
    ),
    'Imagick::setImageInterpolateMethod' => 
    array (
      0 => 'bool',
      'method' => 'int',
    ),
    'Imagick::setImageIterations' => 
    array (
      0 => 'bool',
      'iterations' => 'int',
    ),
    'Imagick::setImageMatte' => 
    array (
      0 => 'bool',
      'matte' => 'bool',
    ),
    'Imagick::setImageMatteColor' => 
    array (
      0 => 'bool',
      'matte_color' => 'ImagickPixel|string',
    ),
    'Imagick::setImageOrientation' => 
    array (
      0 => 'bool',
      'orientation' => 'int',
    ),
    'Imagick::setImagePage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'x' => 'int',
      'y' => 'int',
    ),
    'Imagick::setImageProfile' => 
    array (
      0 => 'bool',
      'name' => 'string',
      'profile' => 'string',
    ),
    'Imagick::setImageProperty' => 
    array (
      0 => 'bool',
      'name' => 'string',
      'value' => 'string',
    ),
    'Imagick::setImageRedPrimary' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
      'z' => 'float',
    ),
    'Imagick::setImageRenderingIntent' => 
    array (
      0 => 'bool',
      'rendering_intent' => 'int',
    ),
    'Imagick::setImageResolution' => 
    array (
      0 => 'bool',
      'x_resolution' => 'float',
      'y_resolution' => 'float',
    ),
    'Imagick::setImageScene' => 
    array (
      0 => 'bool',
      'scene' => 'int',
    ),
    'Imagick::setImageTicksPerSecond' => 
    array (
      0 => 'bool',
      'ticks_per_second' => 'int',
    ),
    'Imagick::setImageType' => 
    array (
      0 => 'bool',
      'image_type' => 'int',
    ),
    'Imagick::setImageUnits' => 
    array (
      0 => 'bool',
      'units' => 'int',
    ),
    'Imagick::setImageVirtualPixelMethod' => 
    array (
      0 => 'bool',
      'method' => 'int',
    ),
    'Imagick::setImageWhitePoint' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
      'z' => 'float',
    ),
    'Imagick::setInterlaceScheme' => 
    array (
      0 => 'bool',
      'interlace' => 'int',
    ),
    'Imagick::setIteratorIndex' => 
    array (
      0 => 'bool',
      'index' => 'int',
    ),
    'Imagick::setLastIterator' => 
    array (
      0 => 'bool',
    ),
    'Imagick::setOption' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'string',
    ),
    'Imagick::setPage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'x' => 'int',
      'y' => 'int',
    ),
    'Imagick::setPointSize' => 
    array (
      0 => 'bool',
      'point_size' => 'float',
    ),
    'Imagick::setProgressMonitor' => 
    array (
      0 => 'bool',
      'callback' => 'impure-callable',
    ),
    'Imagick::setRegistry' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'string',
    ),
    'Imagick::setResolution' => 
    array (
      0 => 'bool',
      'x_resolution' => 'float',
      'y_resolution' => 'float',
    ),
    'Imagick::setResourceLimit' => 
    array (
      0 => 'bool',
      'type' => 'int',
      'limit' => 'int',
    ),
    'Imagick::setSamplingFactors' => 
    array (
      0 => 'bool',
      'factors' => 'list<string>',
    ),
    'Imagick::setSize' => 
    array (
      0 => 'bool',
      'columns' => 'int',
      'rows' => 'int',
    ),
    'Imagick::setSizeOffset' => 
    array (
      0 => 'bool',
      'columns' => 'int',
      'rows' => 'int',
      'offset' => 'int',
    ),
    'Imagick::setType' => 
    array (
      0 => 'bool',
      'imgtype' => 'int',
    ),
    'Imagick::shadeImage' => 
    array (
      0 => 'bool',
      'gray' => 'bool',
      'azimuth' => 'float',
      'elevation' => 'float',
    ),
    'Imagick::shadowImage' => 
    array (
      0 => 'bool',
      'opacity' => 'float',
      'sigma' => 'float',
      'x' => 'int',
      'y' => 'int',
    ),
    'Imagick::sharpenImage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'channel=' => 'int',
    ),
    'Imagick::shaveImage' => 
    array (
      0 => 'bool',
      'columns' => 'int',
      'rows' => 'int',
    ),
    'Imagick::shearImage' => 
    array (
      0 => 'bool',
      'background_color' => 'ImagickPixel|string',
      'x_shear' => 'float',
      'y_shear' => 'float',
    ),
    'Imagick::sigmoidalContrastImage' => 
    array (
      0 => 'bool',
      'sharpen' => 'bool',
      'alpha' => 'float',
      'beta' => 'float',
      'channel=' => 'int',
    ),
    'Imagick::similarityImage' => 
    array (
      0 => 'Imagick',
      'image' => 'Imagick',
      '&offset=' => 'array<array-key, mixed>',
      '&similarity=' => 'float',
      'threshold=' => 'float',
      'metric=' => 'int',
    ),
    'Imagick::sketchImage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'angle' => 'float',
    ),
    'Imagick::smushImages' => 
    array (
      0 => 'Imagick',
      'stack' => 'bool',
      'offset' => 'int',
    ),
    'Imagick::solarizeImage' => 
    array (
      0 => 'bool',
      'threshold' => 'int',
    ),
    'Imagick::sparseColorImage' => 
    array (
      0 => 'bool',
      'sparsecolormethod' => 'int',
      'arguments' => 'array<array-key, mixed>',
      'channel=' => 'int',
    ),
    'Imagick::spliceImage' => 
    array (
      0 => 'bool',
      'width' => 'int',
      'height' => 'int',
      'x' => 'int',
      'y' => 'int',
    ),
    'Imagick::spreadImage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
    ),
    'Imagick::statisticImage' => 
    array (
      0 => 'bool',
      'type' => 'int',
      'width' => 'int',
      'height' => 'int',
      'channel=' => 'int',
    ),
    'Imagick::steganoImage' => 
    array (
      0 => 'Imagick',
      'watermark' => 'Imagick',
      'offset' => 'int',
    ),
    'Imagick::stereoImage' => 
    array (
      0 => 'bool',
      'offset_image' => 'Imagick',
    ),
    'Imagick::stripImage' => 
    array (
      0 => 'bool',
    ),
    'Imagick::subimageMatch' => 
    array (
      0 => 'Imagick',
      'image' => 'Imagick',
      '&w offset=' => 'array<array-key, mixed>',
      '&w similarity=' => 'float',
      'threshold=' => 'float',
      'metric=' => 'int',
    ),
    'Imagick::swirlImage' => 
    array (
      0 => 'bool',
      'degrees' => 'float',
    ),
    'Imagick::textureImage' => 
    array (
      0 => 'Imagick',
      'texture' => 'Imagick',
    ),
    'Imagick::thresholdImage' => 
    array (
      0 => 'bool',
      'threshold' => 'float',
      'channel=' => 'int',
    ),
    'Imagick::thumbnailImage' => 
    array (
      0 => 'bool',
      'columns' => 'int|null',
      'rows' => 'int|null',
      'bestfit=' => 'bool',
      'fill=' => 'bool',
      'legacy=' => 'bool',
    ),
    'Imagick::tintImage' => 
    array (
      0 => 'bool',
      'tint_color' => 'ImagickPixel|string',
      'opacity_color' => 'ImagickPixel|string',
      'legacy=' => 'bool',
    ),
    'Imagick::transformImageColorspace' => 
    array (
      0 => 'bool',
      'colorspace' => 'int',
    ),
    'Imagick::transparentPaintImage' => 
    array (
      0 => 'bool',
      'target_color' => 'ImagickPixel|string',
      'alpha' => 'float',
      'fuzz' => 'float',
      'invert' => 'bool',
    ),
    'Imagick::transposeImage' => 
    array (
      0 => 'bool',
    ),
    'Imagick::transverseImage' => 
    array (
      0 => 'bool',
    ),
    'Imagick::trimImage' => 
    array (
      0 => 'bool',
      'fuzz' => 'float',
    ),
    'Imagick::uniqueImageColors' => 
    array (
      0 => 'bool',
    ),
    'Imagick::unsharpMaskImage' => 
    array (
      0 => 'bool',
      'radius' => 'float',
      'sigma' => 'float',
      'amount' => 'float',
      'threshold' => 'float',
      'channel=' => 'int',
    ),
    'Imagick::valid' => 
    array (
      0 => 'bool',
    ),
    'Imagick::vignetteImage' => 
    array (
      0 => 'bool',
      'black_point' => 'float',
      'white_point' => 'float',
      'x' => 'int',
      'y' => 'int',
    ),
    'Imagick::waveImage' => 
    array (
      0 => 'bool',
      'amplitude' => 'float',
      'length' => 'float',
    ),
    'Imagick::whiteThresholdImage' => 
    array (
      0 => 'bool',
      'threshold_color' => 'ImagickPixel|string',
    ),
    'Imagick::writeImage' => 
    array (
      0 => 'bool',
      'filename=' => 'null|string',
    ),
    'Imagick::writeImageFile' => 
    array (
      0 => 'bool',
      'filehandle' => 'resource',
      'format=' => 'null|string',
    ),
    'Imagick::writeImages' => 
    array (
      0 => 'bool',
      'filename' => 'string',
      'adjoin' => 'bool',
    ),
    'Imagick::writeImagesFile' => 
    array (
      0 => 'bool',
      'filehandle' => 'resource',
      'format=' => 'null|string',
    ),
    'ImagickDraw::affine' => 
    array (
      0 => 'bool',
      'affine' => 'array<string, float>',
    ),
    'ImagickDraw::annotation' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
      'text' => 'string',
    ),
    'ImagickDraw::arc' => 
    array (
      0 => 'bool',
      'start_x' => 'float',
      'start_y' => 'float',
      'end_x' => 'float',
      'end_y' => 'float',
      'start_angle' => 'float',
      'end_angle' => 'float',
    ),
    'ImagickDraw::bezier' => 
    array (
      0 => 'bool',
      'coordinates' => 'list<array{x: float, y: float}>',
    ),
    'ImagickDraw::circle' => 
    array (
      0 => 'bool',
      'origin_x' => 'float',
      'origin_y' => 'float',
      'perimeter_x' => 'float',
      'perimeter_y' => 'float',
    ),
    'ImagickDraw::clear' => 
    array (
      0 => 'bool',
    ),
    'ImagickDraw::clone' => 
    array (
      0 => 'ImagickDraw',
    ),
    'ImagickDraw::color' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
      'paint' => 'int',
    ),
    'ImagickDraw::comment' => 
    array (
      0 => 'bool',
      'comment' => 'string',
    ),
    'ImagickDraw::composite' => 
    array (
      0 => 'bool',
      'composite' => 'int',
      'x' => 'float',
      'y' => 'float',
      'width' => 'float',
      'height' => 'float',
      'image' => 'Imagick',
    ),
    'ImagickDraw::destroy' => 
    array (
      0 => 'bool',
    ),
    'ImagickDraw::ellipse' => 
    array (
      0 => 'bool',
      'origin_x' => 'float',
      'origin_y' => 'float',
      'radius_x' => 'float',
      'radius_y' => 'float',
      'angle_start' => 'float',
      'angle_end' => 'float',
    ),
    'ImagickDraw::getBorderColor' => 
    array (
      0 => 'ImagickPixel',
    ),
    'ImagickDraw::getClipPath' => 
    array (
      0 => 'false|string',
    ),
    'ImagickDraw::getClipRule' => 
    array (
      0 => 'int',
    ),
    'ImagickDraw::getClipUnits' => 
    array (
      0 => 'int',
    ),
    'ImagickDraw::getDensity' => 
    array (
      0 => 'null|string',
    ),
    'ImagickDraw::getFillColor' => 
    array (
      0 => 'ImagickPixel',
    ),
    'ImagickDraw::getFillOpacity' => 
    array (
      0 => 'float',
    ),
    'ImagickDraw::getFillRule' => 
    array (
      0 => 'int',
    ),
    'ImagickDraw::getFont' => 
    array (
      0 => 'string',
    ),
    'ImagickDraw::getFontFamily' => 
    array (
      0 => 'string',
    ),
    'ImagickDraw::getFontResolution' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'ImagickDraw::getFontSize' => 
    array (
      0 => 'float',
    ),
    'ImagickDraw::getFontStretch' => 
    array (
      0 => 'int',
    ),
    'ImagickDraw::getFontStyle' => 
    array (
      0 => 'int',
    ),
    'ImagickDraw::getFontWeight' => 
    array (
      0 => 'int',
    ),
    'ImagickDraw::getGravity' => 
    array (
      0 => 'int',
    ),
    'ImagickDraw::getOpacity' => 
    array (
      0 => 'float',
    ),
    'ImagickDraw::getStrokeAntialias' => 
    array (
      0 => 'bool',
    ),
    'ImagickDraw::getStrokeColor' => 
    array (
      0 => 'ImagickPixel',
    ),
    'ImagickDraw::getStrokeDashArray' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'ImagickDraw::getStrokeDashOffset' => 
    array (
      0 => 'float',
    ),
    'ImagickDraw::getStrokeLineCap' => 
    array (
      0 => 'int',
    ),
    'ImagickDraw::getStrokeLineJoin' => 
    array (
      0 => 'int',
    ),
    'ImagickDraw::getStrokeMiterLimit' => 
    array (
      0 => 'int',
    ),
    'ImagickDraw::getStrokeOpacity' => 
    array (
      0 => 'float',
    ),
    'ImagickDraw::getStrokeWidth' => 
    array (
      0 => 'float',
    ),
    'ImagickDraw::getTextAlignment' => 
    array (
      0 => 'int',
    ),
    'ImagickDraw::getTextAntialias' => 
    array (
      0 => 'bool',
    ),
    'ImagickDraw::getTextDecoration' => 
    array (
      0 => 'int',
    ),
    'ImagickDraw::getTextDirection' => 
    array (
      0 => 'int',
    ),
    'ImagickDraw::getTextEncoding' => 
    array (
      0 => 'string',
    ),
    'ImagickDraw::getTextInterlineSpacing' => 
    array (
      0 => 'float',
    ),
    'ImagickDraw::getTextInterwordSpacing' => 
    array (
      0 => 'float',
    ),
    'ImagickDraw::getTextKerning' => 
    array (
      0 => 'float',
    ),
    'ImagickDraw::getTextUnderColor' => 
    array (
      0 => 'ImagickPixel',
    ),
    'ImagickDraw::getVectorGraphics' => 
    array (
      0 => 'string',
    ),
    'ImagickDraw::line' => 
    array (
      0 => 'bool',
      'start_x' => 'float',
      'start_y' => 'float',
      'end_x' => 'float',
      'end_y' => 'float',
    ),
    'ImagickDraw::pathClose' => 
    array (
      0 => 'bool',
    ),
    'ImagickDraw::pathCurveToAbsolute' => 
    array (
      0 => 'bool',
      'x1' => 'float',
      'y1' => 'float',
      'x2' => 'float',
      'y2' => 'float',
      'x' => 'float',
      'y' => 'float',
    ),
    'ImagickDraw::pathCurveToQuadraticBezierAbsolute' => 
    array (
      0 => 'bool',
      'x1' => 'float',
      'y1' => 'float',
      'x_end' => 'float',
      'y' => 'float',
    ),
    'ImagickDraw::pathCurveToQuadraticBezierRelative' => 
    array (
      0 => 'bool',
      'x1' => 'float',
      'y1' => 'float',
      'x_end' => 'float',
      'y' => 'float',
    ),
    'ImagickDraw::pathCurveToQuadraticBezierSmoothAbsolute' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'ImagickDraw::pathCurveToQuadraticBezierSmoothRelative' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'ImagickDraw::pathCurveToRelative' => 
    array (
      0 => 'bool',
      'x1' => 'float',
      'y1' => 'float',
      'x2' => 'float',
      'y2' => 'float',
      'x' => 'float',
      'y' => 'float',
    ),
    'ImagickDraw::pathCurveToSmoothAbsolute' => 
    array (
      0 => 'bool',
      'x2' => 'float',
      'y2' => 'float',
      'x' => 'float',
      'y' => 'float',
    ),
    'ImagickDraw::pathCurveToSmoothRelative' => 
    array (
      0 => 'bool',
      'x2' => 'float',
      'y2' => 'float',
      'x' => 'float',
      'y' => 'float',
    ),
    'ImagickDraw::pathEllipticArcAbsolute' => 
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
    'ImagickDraw::pathEllipticArcRelative' => 
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
    'ImagickDraw::pathFinish' => 
    array (
      0 => 'bool',
    ),
    'ImagickDraw::pathLineToAbsolute' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'ImagickDraw::pathLineToHorizontalAbsolute' => 
    array (
      0 => 'bool',
      'x' => 'float',
    ),
    'ImagickDraw::pathLineToHorizontalRelative' => 
    array (
      0 => 'bool',
      'x' => 'float',
    ),
    'ImagickDraw::pathLineToRelative' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'ImagickDraw::pathLineToVerticalAbsolute' => 
    array (
      0 => 'bool',
      'y' => 'float',
    ),
    'ImagickDraw::pathLineToVerticalRelative' => 
    array (
      0 => 'bool',
      'y' => 'float',
    ),
    'ImagickDraw::pathMoveToAbsolute' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'ImagickDraw::pathMoveToRelative' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'ImagickDraw::pathStart' => 
    array (
      0 => 'bool',
    ),
    'ImagickDraw::point' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'ImagickDraw::polygon' => 
    array (
      0 => 'bool',
      'coordinates' => 'list<array{x: float, y: float}>',
    ),
    'ImagickDraw::polyline' => 
    array (
      0 => 'bool',
      'coordinates' => 'list<array{x: float, y: float}>',
    ),
    'ImagickDraw::pop' => 
    array (
      0 => 'bool',
    ),
    'ImagickDraw::popClipPath' => 
    array (
      0 => 'bool',
    ),
    'ImagickDraw::popDefs' => 
    array (
      0 => 'bool',
    ),
    'ImagickDraw::popPattern' => 
    array (
      0 => 'bool',
    ),
    'ImagickDraw::push' => 
    array (
      0 => 'bool',
    ),
    'ImagickDraw::pushClipPath' => 
    array (
      0 => 'bool',
      'clip_mask_id' => 'string',
    ),
    'ImagickDraw::pushDefs' => 
    array (
      0 => 'bool',
    ),
    'ImagickDraw::pushPattern' => 
    array (
      0 => 'bool',
      'pattern_id' => 'string',
      'x' => 'float',
      'y' => 'float',
      'width' => 'float',
      'height' => 'float',
    ),
    'ImagickDraw::rectangle' => 
    array (
      0 => 'bool',
      'top_left_x' => 'float',
      'top_left_y' => 'float',
      'bottom_right_x' => 'float',
      'bottom_right_y' => 'float',
    ),
    'ImagickDraw::render' => 
    array (
      0 => 'bool',
    ),
    'ImagickDraw::resetVectorGraphics' => 
    array (
      0 => 'bool',
    ),
    'ImagickDraw::rotate' => 
    array (
      0 => 'bool',
      'degrees' => 'float',
    ),
    'ImagickDraw::roundRectangle' => 
    array (
      0 => 'bool',
      'top_left_x' => 'float',
      'top_left_y' => 'float',
      'bottom_right_x' => 'float',
      'bottom_right_y' => 'float',
      'rounding_x' => 'float',
      'rounding_y' => 'float',
    ),
    'ImagickDraw::scale' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'ImagickDraw::setBorderColor' => 
    array (
      0 => 'bool',
      'color' => 'ImagickPixel|string',
    ),
    'ImagickDraw::setClipPath' => 
    array (
      0 => 'bool',
      'clip_mask' => 'string',
    ),
    'ImagickDraw::setClipRule' => 
    array (
      0 => 'bool',
      'fillrule' => 'int',
    ),
    'ImagickDraw::setClipUnits' => 
    array (
      0 => 'bool',
      'pathunits' => 'int',
    ),
    'ImagickDraw::setDensity' => 
    array (
      0 => 'bool',
      'density' => 'string',
    ),
    'ImagickDraw::setFillAlpha' => 
    array (
      0 => 'bool',
      'alpha' => 'float',
    ),
    'ImagickDraw::setFillColor' => 
    array (
      0 => 'bool',
      'fill_color' => 'ImagickPixel|string',
    ),
    'ImagickDraw::setFillOpacity' => 
    array (
      0 => 'bool',
      'opacity' => 'float',
    ),
    'ImagickDraw::setFillPatternUrl' => 
    array (
      0 => 'bool',
      'fill_url' => 'string',
    ),
    'ImagickDraw::setFillRule' => 
    array (
      0 => 'bool',
      'fillrule' => 'int',
    ),
    'ImagickDraw::setFont' => 
    array (
      0 => 'bool',
      'font_name' => 'string',
    ),
    'ImagickDraw::setFontFamily' => 
    array (
      0 => 'bool',
      'font_family' => 'string',
    ),
    'ImagickDraw::setFontResolution' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'ImagickDraw::setFontSize' => 
    array (
      0 => 'bool',
      'point_size' => 'float',
    ),
    'ImagickDraw::setFontStretch' => 
    array (
      0 => 'bool',
      'stretch' => 'int',
    ),
    'ImagickDraw::setFontStyle' => 
    array (
      0 => 'bool',
      'style' => 'int',
    ),
    'ImagickDraw::setFontWeight' => 
    array (
      0 => 'bool',
      'weight' => 'int',
    ),
    'ImagickDraw::setGravity' => 
    array (
      0 => 'bool',
      'gravity' => 'int',
    ),
    'ImagickDraw::setOpacity' => 
    array (
      0 => 'bool',
      'opacity' => 'float',
    ),
    'ImagickDraw::setResolution' => 
    array (
      0 => 'bool',
      'resolution_x' => 'float',
      'resolution_y' => 'float',
    ),
    'ImagickDraw::setStrokeAlpha' => 
    array (
      0 => 'bool',
      'alpha' => 'float',
    ),
    'ImagickDraw::setStrokeAntialias' => 
    array (
      0 => 'bool',
      'enabled' => 'bool',
    ),
    'ImagickDraw::setStrokeColor' => 
    array (
      0 => 'bool',
      'color' => 'ImagickPixel|string',
    ),
    'ImagickDraw::setStrokeDashArray' => 
    array (
      0 => 'bool',
      'dashes' => 'list<float|int>|null',
    ),
    'ImagickDraw::setStrokeDashOffset' => 
    array (
      0 => 'bool',
      'dash_offset' => 'float',
    ),
    'ImagickDraw::setStrokeLineCap' => 
    array (
      0 => 'bool',
      'linecap' => 'int',
    ),
    'ImagickDraw::setStrokeLineJoin' => 
    array (
      0 => 'bool',
      'linejoin' => 'int',
    ),
    'ImagickDraw::setStrokeMiterLimit' => 
    array (
      0 => 'bool',
      'miterlimit' => 'int',
    ),
    'ImagickDraw::setStrokeOpacity' => 
    array (
      0 => 'bool',
      'opacity' => 'float',
    ),
    'ImagickDraw::setStrokePatternUrl' => 
    array (
      0 => 'bool',
      'stroke_url' => 'string',
    ),
    'ImagickDraw::setStrokeWidth' => 
    array (
      0 => 'bool',
      'width' => 'float',
    ),
    'ImagickDraw::setTextAlignment' => 
    array (
      0 => 'bool',
      'align' => 'int',
    ),
    'ImagickDraw::setTextAntialias' => 
    array (
      0 => 'bool',
      'antialias' => 'bool',
    ),
    'ImagickDraw::setTextDecoration' => 
    array (
      0 => 'bool',
      'decoration' => 'int',
    ),
    'ImagickDraw::setTextDirection' => 
    array (
      0 => 'bool',
      'direction' => 'int',
    ),
    'ImagickDraw::setTextEncoding' => 
    array (
      0 => 'bool',
      'encoding' => 'string',
    ),
    'ImagickDraw::setTextInterlineSpacing' => 
    array (
      0 => 'bool',
      'spacing' => 'float',
    ),
    'ImagickDraw::setTextInterwordSpacing' => 
    array (
      0 => 'bool',
      'spacing' => 'float',
    ),
    'ImagickDraw::setTextKerning' => 
    array (
      0 => 'bool',
      'kerning' => 'float',
    ),
    'ImagickDraw::setTextUnderColor' => 
    array (
      0 => 'bool',
      'under_color' => 'ImagickPixel|string',
    ),
    'ImagickDraw::setVectorGraphics' => 
    array (
      0 => 'bool',
      'xml' => 'string',
    ),
    'ImagickDraw::setViewbox' => 
    array (
      0 => 'bool',
      'left_x' => 'int',
      'top_y' => 'int',
      'right_x' => 'int',
      'bottom_y' => 'int',
    ),
    'ImagickDraw::skewX' => 
    array (
      0 => 'bool',
      'degrees' => 'float',
    ),
    'ImagickDraw::skewY' => 
    array (
      0 => 'bool',
      'degrees' => 'float',
    ),
    'ImagickDraw::translate' => 
    array (
      0 => 'bool',
      'x' => 'float',
      'y' => 'float',
    ),
    'ImagickKernel::addKernel' => 
    array (
      0 => 'void',
      'kernel' => 'ImagickKernel',
    ),
    'ImagickKernel::addUnityKernel' => 
    array (
      0 => 'void',
      'scale' => 'float',
    ),
    'ImagickKernel::fromBuiltin' => 
    array (
      0 => 'ImagickKernel',
      'kernel' => 'int',
      'shape' => 'string',
    ),
    'ImagickKernel::fromMatrix' => 
    array (
      0 => 'ImagickKernel',
      'matrix' => 'list<list<float>>',
      'origin=' => 'array<array-key, mixed>|null',
    ),
    'ImagickKernel::getMatrix' => 
    array (
      0 => 'list<list<false|float>>',
    ),
    'ImagickKernel::scale' => 
    array (
      0 => 'void',
      'scale' => 'float',
      'normalize_kernel=' => 'int|null',
    ),
    'ImagickKernel::separate' => 
    array (
      0 => 'array<array-key, ImagickKernel>',
    ),
    'ImagickPixel::__construct' => 
    array (
      0 => 'void',
      'color=' => 'null|string',
    ),
    'ImagickPixel::clear' => 
    array (
      0 => 'bool',
    ),
    'ImagickPixel::destroy' => 
    array (
      0 => 'bool',
    ),
    'ImagickPixel::getColor' => 
    array (
      0 => 'array{a: float|int, b: float|int, g: float|int, r: float|int}',
      'normalized=' => '0|1|2',
    ),
    'ImagickPixel::getColorAsString' => 
    array (
      0 => 'string',
    ),
    'ImagickPixel::getColorCount' => 
    array (
      0 => 'int',
    ),
    'ImagickPixel::getColorValue' => 
    array (
      0 => 'float',
      'color' => 'int',
    ),
    'ImagickPixel::getHSL' => 
    array (
      0 => 'array{hue: float, luminosity: float, saturation: float}',
    ),
    'ImagickPixel::getIndex' => 
    array (
      0 => 'int',
    ),
    'ImagickPixel::isPixelSimilar' => 
    array (
      0 => 'bool|null',
      'color' => 'ImagickPixel',
      'fuzz' => 'float',
    ),
    'ImagickPixel::isPixelSimilarQuantum' => 
    array (
      0 => 'bool|null',
      'color' => 'string',
      'fuzz_quantum_range_scaled_by_square_root_of_three' => 'float',
    ),
    'ImagickPixel::isSimilar' => 
    array (
      0 => 'bool|null',
      'color' => 'ImagickPixel',
      'fuzz_quantum_range_scaled_by_square_root_of_three' => 'float',
    ),
    'ImagickPixel::setColor' => 
    array (
      0 => 'bool',
      'color' => 'string',
    ),
    'ImagickPixel::setColorCount' => 
    array (
      0 => 'bool',
      'color_count' => 'int',
    ),
    'ImagickPixel::setColorFromPixel' => 
    array (
      0 => 'bool',
      'pixel' => 'ImagickPixel',
    ),
    'ImagickPixel::setColorValue' => 
    array (
      0 => 'bool',
      'color' => 'int',
      'value' => 'float',
    ),
    'ImagickPixel::setColorValueQuantum' => 
    array (
      0 => 'bool',
      'color' => 'int',
      'value' => 'IMAGICK_QUANTUM_TYPE',
    ),
    'ImagickPixel::setHSL' => 
    array (
      0 => 'bool',
      'hue' => 'float',
      'saturation' => 'float',
      'luminosity' => 'float',
    ),
    'ImagickPixel::setIndex' => 
    array (
      0 => 'bool',
      'index' => 'IMAGICK_QUANTUM_TYPE',
    ),
    'ImagickPixelIterator::clear' => 
    array (
      0 => 'bool',
    ),
    'ImagickPixelIterator::destroy' => 
    array (
      0 => 'bool',
    ),
    'ImagickPixelIterator::getCurrentIteratorRow' => 
    array (
      0 => 'array<array-key, mixed>|null',
    ),
    'ImagickPixelIterator::getIteratorRow' => 
    array (
      0 => 'int',
    ),
    'ImagickPixelIterator::getNextIteratorRow' => 
    array (
      0 => 'array<array-key, mixed>|null',
    ),
    'ImagickPixelIterator::getPreviousIteratorRow' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'ImagickPixelIterator::key' => 
    array (
      0 => 'int',
    ),
    'ImagickPixelIterator::newPixelIterator' => 
    array (
      0 => 'bool',
      'imagick' => 'Imagick',
    ),
    'ImagickPixelIterator::newPixelRegionIterator' => 
    array (
      0 => 'bool',
      'imagick' => 'Imagick',
      'x' => 'int',
      'y' => 'int',
      'columns' => 'int',
      'rows' => 'int',
    ),
    'ImagickPixelIterator::next' => 
    array (
      0 => 'void',
    ),
    'ImagickPixelIterator::resetIterator' => 
    array (
      0 => 'bool',
    ),
    'ImagickPixelIterator::rewind' => 
    array (
      0 => 'void',
    ),
    'ImagickPixelIterator::setIteratorFirstRow' => 
    array (
      0 => 'bool',
    ),
    'ImagickPixelIterator::setIteratorLastRow' => 
    array (
      0 => 'bool',
    ),
    'ImagickPixelIterator::setIteratorRow' => 
    array (
      0 => 'bool',
      'row' => 'int',
    ),
    'ImagickPixelIterator::syncIterator' => 
    array (
      0 => 'bool',
    ),
    'ImagickPixelIterator::valid' => 
    array (
      0 => 'bool',
    ),
    'Memcached::__construct' => 
    array (
      0 => 'void',
      'persistent_id=' => 'null|string',
      'callback=' => 'impure-callable|null',
      'connection_str=' => 'null|string',
    ),
    'Memcached::add' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'mixed',
      'expiration=' => 'int',
    ),
    'Memcached::addByKey' => 
    array (
      0 => 'bool',
      'server_key' => 'string',
      'key' => 'string',
      'value' => 'mixed',
      'expiration=' => 'int',
    ),
    'Memcached::addServer' => 
    array (
      0 => 'bool',
      'host' => 'string',
      'port' => 'int',
      'weight=' => 'int',
    ),
    'Memcached::addServers' => 
    array (
      0 => 'bool',
      'servers' => 'array<array-key, mixed>',
    ),
    'Memcached::append' => 
    array (
      0 => 'bool|null',
      'key' => 'string',
      'value' => 'string',
    ),
    'Memcached::appendByKey' => 
    array (
      0 => 'bool|null',
      'server_key' => 'string',
      'key' => 'string',
      'value' => 'string',
    ),
    'Memcached::cas' => 
    array (
      0 => 'bool',
      'cas_token' => 'float|int|string',
      'key' => 'string',
      'value' => 'mixed',
      'expiration=' => 'int',
    ),
    'Memcached::casByKey' => 
    array (
      0 => 'bool',
      'cas_token' => 'float|int|string',
      'server_key' => 'string',
      'key' => 'string',
      'value' => 'mixed',
      'expiration=' => 'int',
    ),
    'Memcached::decrement' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'offset=' => 'int',
      'initial_value=' => 'int',
      'expiry=' => 'int',
    ),
    'Memcached::decrementByKey' => 
    array (
      0 => 'false|int',
      'server_key' => 'string',
      'key' => 'string',
      'offset=' => 'int',
      'initial_value=' => 'int',
      'expiry=' => 'int',
    ),
    'Memcached::delete' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'time=' => 'int',
    ),
    'Memcached::deleteByKey' => 
    array (
      0 => 'bool',
      'server_key' => 'string',
      'key' => 'string',
      'time=' => 'int',
    ),
    'Memcached::deleteMulti' => 
    array (
      0 => 'array<array-key, mixed>',
      'keys' => 'array<array-key, mixed>',
      'time=' => 'int',
    ),
    'Memcached::deleteMultiByKey' => 
    array (
      0 => 'array<array-key, mixed>',
      'server_key' => 'string',
      'keys' => 'array<array-key, mixed>',
      'time=' => 'int',
    ),
    'Memcached::fetch' => 
    array (
      0 => 'array<array-key, mixed>|false',
    ),
    'Memcached::fetchAll' => 
    array (
      0 => 'array<array-key, mixed>|false',
    ),
    'Memcached::flush' => 
    array (
      0 => 'bool',
      'delay=' => 'int',
    ),
    'Memcached::flushBuffers' => 
    array (
      0 => 'bool',
    ),
    'Memcached::get' => 
    array (
      0 => 'false|mixed',
      'key' => 'string',
      'cache_cb=' => 'impure-callable|null',
      'get_flags=' => 'int',
    ),
    'Memcached::getAllKeys' => 
    array (
      0 => 'array<array-key, mixed>|false',
    ),
    'Memcached::getByKey' => 
    array (
      0 => 'false|mixed',
      'server_key' => 'string',
      'key' => 'string',
      'cache_cb=' => 'impure-callable|null',
      'get_flags=' => 'int',
    ),
    'Memcached::getDelayed' => 
    array (
      0 => 'bool',
      'keys' => 'array<array-key, mixed>',
      'with_cas=' => 'bool',
      'value_cb=' => 'impure-callable|null',
    ),
    'Memcached::getDelayedByKey' => 
    array (
      0 => 'bool',
      'server_key' => 'string',
      'keys' => 'array<array-key, mixed>',
      'with_cas=' => 'bool',
      'value_cb=' => 'impure-callable|null',
    ),
    'Memcached::getLastDisconnectedServer' => 
    array (
      0 => 'array<array-key, mixed>|false',
    ),
    'Memcached::getLastErrorCode' => 
    array (
      0 => 'int',
    ),
    'Memcached::getLastErrorErrno' => 
    array (
      0 => 'int',
    ),
    'Memcached::getLastErrorMessage' => 
    array (
      0 => 'string',
    ),
    'Memcached::getMulti' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'keys' => 'array<array-key, mixed>',
      'get_flags=' => 'int',
    ),
    'Memcached::getMultiByKey' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'server_key' => 'string',
      'keys' => 'array<array-key, mixed>',
      'get_flags=' => 'int',
    ),
    'Memcached::getOption' => 
    array (
      0 => 'false|mixed',
      'option' => 'int',
    ),
    'Memcached::getResultCode' => 
    array (
      0 => 'int',
    ),
    'Memcached::getResultMessage' => 
    array (
      0 => 'string',
    ),
    'Memcached::getServerByKey' => 
    array (
      0 => 'array<array-key, mixed>',
      'server_key' => 'string',
    ),
    'Memcached::getServerList' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'Memcached::getStats' => 
    array (
      0 => 'array<string, array<string, int|string>|false>|false',
      'type=' => 'null|string',
    ),
    'Memcached::getVersion' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'Memcached::increment' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'offset=' => 'int',
      'initial_value=' => 'int',
      'expiry=' => 'int',
    ),
    'Memcached::incrementByKey' => 
    array (
      0 => 'false|int',
      'server_key' => 'string',
      'key' => 'string',
      'offset=' => 'int',
      'initial_value=' => 'int',
      'expiry=' => 'int',
    ),
    'Memcached::isPersistent' => 
    array (
      0 => 'bool',
    ),
    'Memcached::isPristine' => 
    array (
      0 => 'bool',
    ),
    'Memcached::prepend' => 
    array (
      0 => 'bool|null',
      'key' => 'string',
      'value' => 'string',
    ),
    'Memcached::prependByKey' => 
    array (
      0 => 'bool|null',
      'server_key' => 'string',
      'key' => 'string',
      'value' => 'string',
    ),
    'Memcached::quit' => 
    array (
      0 => 'bool',
    ),
    'Memcached::replace' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'mixed',
      'expiration=' => 'int',
    ),
    'Memcached::replaceByKey' => 
    array (
      0 => 'bool',
      'server_key' => 'string',
      'key' => 'string',
      'value' => 'mixed',
      'expiration=' => 'int',
    ),
    'Memcached::resetServerList' => 
    array (
      0 => 'bool',
    ),
    'Memcached::set' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'mixed',
      'expiration=' => 'int',
    ),
    'Memcached::setBucket' => 
    array (
      0 => 'bool',
      'host_map' => 'array<array-key, mixed>',
      'forward_map' => 'array<array-key, mixed>|null',
      'replicas' => 'int',
    ),
    'Memcached::setByKey' => 
    array (
      0 => 'bool',
      'server_key' => 'string',
      'key' => 'string',
      'value' => 'mixed',
      'expiration=' => 'int',
    ),
    'Memcached::setEncodingKey' => 
    array (
      0 => 'bool',
      'key' => 'string',
    ),
    'Memcached::setMulti' => 
    array (
      0 => 'bool',
      'items' => 'array<array-key, mixed>',
      'expiration=' => 'int',
    ),
    'Memcached::setMultiByKey' => 
    array (
      0 => 'bool',
      'server_key' => 'string',
      'items' => 'array<array-key, mixed>',
      'expiration=' => 'int',
    ),
    'Memcached::setOption' => 
    array (
      0 => 'bool',
      'option' => 'int',
      'value' => 'mixed',
    ),
    'Memcached::setOptions' => 
    array (
      0 => 'bool',
      'options' => 'array<array-key, mixed>',
    ),
    'Memcached::setSaslAuthData' => 
    array (
      0 => 'bool',
      'username' => 'string',
      'password' => 'string',
    ),
    'Memcached::touch' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'expiration=' => 'int',
    ),
    'Memcached::touchByKey' => 
    array (
      0 => 'bool',
      'server_key' => 'string',
      'key' => 'string',
      'expiration=' => 'int',
    ),
    'Redis::__destruct' => 
    array (
      0 => 'void',
    ),
    'Redis::_prefix' => 
    array (
      0 => 'string',
      'key' => 'string',
    ),
    'Redis::_unserialize' => 
    array (
      0 => 'mixed',
      'value' => 'string',
    ),
    'Redis::append' => 
    array (
      0 => 'int',
      'key' => 'string',
      'value' => 'string',
    ),
    'Redis::auth' => 
    array (
      0 => 'bool',
      'credentials' => 'string',
    ),
    'Redis::bgrewriteaof' => 
    array (
      0 => 'bool',
    ),
    'Redis::bgSave' => 
    array (
      0 => 'bool',
    ),
    'Redis::bitcount' => 
    array (
      0 => 'int',
      'key' => 'string',
      'start=' => 'int',
      'end=' => 'int',
      'bybit=' => 'bool',
    ),
    'Redis::bitop' => 
    array (
      0 => 'int',
      'operation' => 'string',
      'deskey' => 'string',
      'srckey' => 'string',
      '...other_keys=' => 'string',
    ),
    'Redis::bitpos' => 
    array (
      0 => 'int',
      'key' => 'string',
      'bit' => 'bool',
      'start=' => 'int',
      'end=' => 'int',
      'bybit=' => 'bool',
    ),
    'Redis::blPop' => 
    array (
      0 => 'array<array-key, mixed>|null',
      'key_or_keys' => 'array<array-key, string>',
      'timeout_or_key' => 'int',
      '...extra_args=' => 'mixed',
    ),
    'Redis::brPop' => 
    array (
      0 => 'array<array-key, mixed>|null',
      'key_or_keys' => 'array<array-key, string>',
      'timeout_or_key' => 'int',
      '...extra_args=' => 'mixed',
    ),
    'Redis::brpoplpush' => 
    array (
      0 => 'false|string',
      'src' => 'string',
      'dst' => 'string',
      'timeout' => 'int',
    ),
    'Redis::clearLastError' => 
    array (
      0 => 'bool',
    ),
    'Redis::client' => 
    array (
      0 => 'mixed',
      'opt' => 'string',
      '...args=' => 'string',
    ),
    'Redis::close' => 
    array (
      0 => 'bool',
    ),
    'Redis::config' => 
    array (
      0 => 'string',
      'operation' => 'string',
      'key_or_settings=' => 'null|string',
      'value=' => 'null|string',
    ),
    'Redis::connect' => 
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
    'Redis::dbSize' => 
    array (
      0 => 'int',
    ),
    'Redis::decr' => 
    array (
      0 => 'int',
      'key' => 'string',
      'by=' => 'int',
    ),
    'Redis::decrBy' => 
    array (
      0 => 'int',
      'key' => 'string',
      'value' => 'int',
    ),
    'Redis::del' => 
    array (
      0 => 'int',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'Redis::delete' => 
    array (
      0 => 'int',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'Redis::dump' => 
    array (
      0 => 'false|string',
      'key' => 'string',
    ),
    'Redis::echo' => 
    array (
      0 => 'string',
      'str' => 'string',
    ),
    'Redis::evalsha' => 
    array (
      0 => 'mixed',
      'sha1' => 'string',
      'args=' => 'array<array-key, mixed>',
      'num_keys=' => 'int',
    ),
    'Redis::exec' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'Redis::exists' => 
    array (
      0 => 'int',
      'key' => 'array<array-key, string>|string',
      '...other_keys=' => 'mixed',
    ),
    'Redis::expire' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'timeout' => 'int',
      'mode=' => 'null|string',
    ),
    'Redis::expireAt' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'timestamp' => 'int',
      'mode=' => 'null|string',
    ),
    'Redis::flushAll' => 
    array (
      0 => 'bool',
      'sync=' => 'bool|null',
    ),
    'Redis::flushDB' => 
    array (
      0 => 'bool',
      'sync=' => 'bool|null',
    ),
    'Redis::geoadd' => 
    array (
      0 => 'int',
      'key' => 'string',
      'lng' => 'float',
      'lat' => 'float',
      'member' => 'string',
      '...other_triples_and_options=' => 'float|int|string',
    ),
    'Redis::geodist' => 
    array (
      0 => 'float',
      'key' => 'string',
      'src' => 'string',
      'dst' => 'string',
      'unit=' => 'null|string',
    ),
    'Redis::geohash' => 
    array (
      0 => 'array<int, string>',
      'key' => 'string',
      'member' => 'string',
      '...other_members=' => 'string',
    ),
    'Redis::geopos' => 
    array (
      0 => 'array<int, array{0: string, 1: string}>',
      'key' => 'string',
      'member' => 'string',
      '...other_members=' => 'string',
    ),
    'Redis::georadius' => 
    array (
      0 => 'array<int, mixed>|int',
      'key' => 'string',
      'lng' => 'float',
      'lat' => 'float',
      'radius' => 'float',
      'unit' => 'string',
      'options=' => 'array<string, mixed>',
    ),
    'Redis::georadiusbymember' => 
    array (
      0 => 'array<int, mixed>|int',
      'key' => 'string',
      'member' => 'string',
      'radius' => 'float',
      'unit' => 'string',
      'options=' => 'array<string, mixed>',
    ),
    'Redis::get' => 
    array (
      0 => 'false|string',
      'key' => 'string',
    ),
    'Redis::getAuth' => 
    array (
      0 => 'false|null|string',
    ),
    'Redis::getBit' => 
    array (
      0 => 'int',
      'key' => 'string',
      'idx' => 'int',
    ),
    'Redis::getDBNum' => 
    array (
      0 => 'int',
    ),
    'Redis::getHost' => 
    array (
      0 => 'string',
    ),
    'Redis::getLastError' => 
    array (
      0 => 'null|string',
    ),
    'Redis::getMode' => 
    array (
      0 => 'int',
    ),
    'Redis::getOption' => 
    array (
      0 => 'int',
      'option' => 'int',
    ),
    'Redis::getPersistentID' => 
    array (
      0 => 'null|string',
    ),
    'Redis::getPort' => 
    array (
      0 => 'int',
    ),
    'Redis::getRange' => 
    array (
      0 => 'false|string',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
    ),
    'Redis::getReadTimeout' => 
    array (
      0 => 'float',
    ),
    'Redis::getset' => 
    array (
      0 => 'string',
      'key' => 'string',
      'value' => 'string',
    ),
    'Redis::getTimeout' => 
    array (
      0 => 'false|float',
    ),
    'Redis::hDel' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'field' => 'string',
      '...other_fields=' => 'string',
    ),
    'Redis::hExists' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'field' => 'string',
    ),
    'Redis::hGet' => 
    array (
      0 => 'false|string',
      'key' => 'string',
      'member' => 'string',
    ),
    'Redis::hGetAll' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
    ),
    'Redis::hIncrBy' => 
    array (
      0 => 'int',
      'key' => 'string',
      'field' => 'string',
      'value' => 'int',
    ),
    'Redis::hIncrByFloat' => 
    array (
      0 => 'float',
      'key' => 'string',
      'field' => 'string',
      'value' => 'float',
    ),
    'Redis::hKeys' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
    ),
    'Redis::hLen' => 
    array (
      0 => 'false|int',
      'key' => 'string',
    ),
    'Redis::hMget' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'fields' => 'array<array-key, mixed>',
    ),
    'Redis::hMset' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'fieldvals' => 'array<array-key, mixed>',
    ),
    'Redis::hscan' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      '&iterator' => 'int|null',
      'pattern=' => 'null|string',
      'count=' => 'int',
    ),
    'Redis::hSet' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      '...fields_and_vals=' => 'string',
    ),
    'Redis::hSetNx' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'field' => 'string',
      'value' => 'string',
    ),
    'Redis::hVals' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
    ),
    'Redis::incr' => 
    array (
      0 => 'int',
      'key' => 'string',
      'by=' => 'int',
    ),
    'Redis::incrBy' => 
    array (
      0 => 'int',
      'key' => 'string',
      'value' => 'int',
    ),
    'Redis::incrByFloat' => 
    array (
      0 => 'float',
      'key' => 'string',
      'value' => 'float',
    ),
    'Redis::info' => 
    array (
      0 => 'array<array-key, mixed>',
      '...sections=' => 'string',
    ),
    'Redis::isConnected' => 
    array (
      0 => 'bool',
    ),
    'Redis::keys' => 
    array (
      0 => 'array<int, string>',
      'pattern' => 'string',
    ),
    'Redis::lastSave' => 
    array (
      0 => 'int',
    ),
    'Redis::lindex' => 
    array (
      0 => 'false|string',
      'key' => 'string',
      'index' => 'int',
    ),
    'Redis::lInsert' => 
    array (
      0 => 'int',
      'key' => 'string',
      'pos' => 'string',
      'pivot' => 'string',
      'value' => 'string',
    ),
    'Redis::lLen' => 
    array (
      0 => 'false|int',
      'key' => 'string',
    ),
    'Redis::lPop' => 
    array (
      0 => 'false|string',
      'key' => 'string',
      'count=' => 'int',
    ),
    'Redis::lPush' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      '...elements=' => 'string',
    ),
    'Redis::lPushx' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'value' => 'string',
    ),
    'Redis::lrange' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
    ),
    'Redis::lrem' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'value' => 'string',
      'count=' => 'int',
    ),
    'Redis::lSet' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'index' => 'int',
      'value' => 'string',
    ),
    'Redis::ltrim' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
    ),
    'Redis::mget' => 
    array (
      0 => 'array<array-key, mixed>',
      'keys' => 'array<array-key, string>',
    ),
    'Redis::migrate' => 
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
    'Redis::move' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'index' => 'int',
    ),
    'Redis::mset' => 
    array (
      0 => 'bool',
      'key_values' => 'array<array-key, mixed>',
    ),
    'Redis::msetnx' => 
    array (
      0 => 'bool',
      'key_values' => 'array<array-key, mixed>',
    ),
    'Redis::multi' => 
    array (
      0 => 'Redis',
      'value=' => 'int',
    ),
    'Redis::object' => 
    array (
      0 => 'false|int|string',
      'subcommand' => 'string',
      'key' => 'string',
    ),
    'Redis::open' => 
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
    'Redis::pconnect' => 
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
    'Redis::persist' => 
    array (
      0 => 'bool',
      'key' => 'string',
    ),
    'Redis::pexpire' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'timeout' => 'int',
      'mode=' => 'null|string',
    ),
    'Redis::pexpireAt' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'timestamp' => 'int',
      'mode=' => 'null|string',
    ),
    'Redis::pfadd' => 
    array (
      0 => 'int',
      'key' => 'string',
      'elements' => 'array<array-key, mixed>',
    ),
    'Redis::pfcount' => 
    array (
      0 => 'int',
      'key_or_keys' => 'array<array-key, mixed>|string',
    ),
    'Redis::pfmerge' => 
    array (
      0 => 'bool',
      'dst' => 'string',
      'srckeys' => 'array<array-key, mixed>',
    ),
    'Redis::ping' => 
    array (
      0 => 'string',
      'message=' => 'null|string',
    ),
    'Redis::pipeline' => 
    array (
      0 => 'Redis',
    ),
    'Redis::popen' => 
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
    'Redis::psetex' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'expire' => 'int',
      'value' => 'string',
    ),
    'Redis::psubscribe' => 
    array (
      0 => 'bool',
      'patterns' => 'array<array-key, mixed>',
      'cb' => 'impure-callable',
    ),
    'Redis::pttl' => 
    array (
      0 => 'false|int',
      'key' => 'string',
    ),
    'Redis::publish' => 
    array (
      0 => 'int',
      'channel' => 'string',
      'message' => 'string',
    ),
    'Redis::pubsub' => 
    array (
      0 => 'array<array-key, mixed>|int',
      'command' => 'string',
      'arg=' => 'array<array-key, mixed>|string',
    ),
    'Redis::punsubscribe' => 
    array (
      0 => 'array<array-key, mixed>|bool',
      'patterns' => 'array<array-key, mixed>',
    ),
    'Redis::randomKey' => 
    array (
      0 => 'string',
    ),
    'Redis::rawcommand' => 
    array (
      0 => 'mixed',
      'command' => 'string',
      '...args=' => 'mixed',
    ),
    'Redis::rename' => 
    array (
      0 => 'bool',
      'old_name' => 'string',
      'new_name' => 'string',
    ),
    'Redis::renameNx' => 
    array (
      0 => 'bool',
      'key_src' => 'string',
      'key_dst' => 'string',
    ),
    'Redis::restore' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'ttl' => 'int',
      'value' => 'string',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'Redis::role' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'Redis::rPop' => 
    array (
      0 => 'false|string',
      'key' => 'string',
      'count=' => 'int',
    ),
    'Redis::rpoplpush' => 
    array (
      0 => 'string',
      'srckey' => 'string',
      'dstkey' => 'string',
    ),
    'Redis::rPush' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      '...elements=' => 'string',
    ),
    'Redis::rPushx' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'value' => 'string',
    ),
    'Redis::sAdd' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'value' => 'string',
      '...other_values=' => 'string',
    ),
    'Redis::sAddArray' => 
    array (
      0 => 'int',
      'key' => 'string',
      'values' => 'array<array-key, mixed>',
    ),
    'Redis::save' => 
    array (
      0 => 'bool',
    ),
    'Redis::scan' => 
    array (
      0 => 'array<int, string>|false',
      '&iterator' => 'int|null',
      'pattern=' => 'null|string',
      'count=' => 'int',
      'type=' => 'null|string',
    ),
    'Redis::scard' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'Redis::script' => 
    array (
      0 => 'mixed',
      'command' => 'string',
      '...args=' => 'mixed',
    ),
    'Redis::sDiff' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'Redis::sDiffStore' => 
    array (
      0 => 'false|int',
      'dst' => 'string',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'Redis::select' => 
    array (
      0 => 'bool',
      'db' => 'int',
    ),
    'Redis::set' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'mixed',
      'options=' => 'array<array-key, mixed>',
    ),
    'Redis::setBit' => 
    array (
      0 => 'int',
      'key' => 'string',
      'idx' => 'int',
      'value' => 'bool',
    ),
    'Redis::setex' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'expire' => 'int',
      'value' => 'string',
    ),
    'Redis::setnx' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'string',
    ),
    'Redis::setOption' => 
    array (
      0 => 'bool',
      'option' => 'int',
      'value' => 'mixed',
    ),
    'Redis::setRange' => 
    array (
      0 => 'int',
      'key' => 'string',
      'index' => 'int',
      'value' => 'string',
    ),
    'Redis::sInter' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'Redis::sInterStore' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'Redis::sismember' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'string',
    ),
    'Redis::slaveof' => 
    array (
      0 => 'bool',
      'host=' => 'null|string',
      'port=' => 'int',
    ),
    'Redis::slowlog' => 
    array (
      0 => 'mixed',
      'operation' => 'string',
      'length=' => 'int',
    ),
    'Redis::sMembers' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
    ),
    'Redis::sMove' => 
    array (
      0 => 'bool',
      'src' => 'string',
      'dst' => 'string',
      'value' => 'string',
    ),
    'Redis::sort' => 
    array (
      0 => 'array<array-key, mixed>|int',
      'key' => 'string',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'Redis::sortAsc' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'pattern=' => 'null|string',
      'get=' => 'string',
      'offset=' => 'int',
      'count=' => 'int',
      'store=' => 'null|string',
    ),
    'Redis::sortAscAlpha' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'pattern=' => 'null|string',
      'get=' => 'string',
      'offset=' => 'int',
      'count=' => 'int',
      'store=' => 'null|string',
    ),
    'Redis::sortDesc' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'pattern=' => 'null|string',
      'get=' => 'string',
      'offset=' => 'int',
      'count=' => 'int',
      'store=' => 'null|string',
    ),
    'Redis::sortDescAlpha' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'pattern=' => 'null|string',
      'get=' => 'string',
      'offset=' => 'int',
      'count=' => 'int',
      'store=' => 'null|string',
    ),
    'Redis::sPop' => 
    array (
      0 => 'false|string',
      'key' => 'string',
      'count=' => 'int',
    ),
    'Redis::sRandMember' => 
    array (
      0 => 'array<array-key, mixed>|false|string',
      'key' => 'string',
      'count=' => 'int',
    ),
    'Redis::srem' => 
    array (
      0 => 'int',
      'key' => 'string',
      'value' => 'string',
      '...other_values=' => 'string',
    ),
    'Redis::sscan' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'key' => 'string',
      '&iterator' => 'int|null',
      'pattern=' => 'null|string',
      'count=' => 'int',
    ),
    'Redis::strlen' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'Redis::subscribe' => 
    array (
      0 => 'bool',
      'channels' => 'array<array-key, mixed>',
      'cb' => 'impure-callable',
    ),
    'Redis::sUnion' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'Redis::sUnionStore' => 
    array (
      0 => 'int',
      'dst' => 'string',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'Redis::swapdb' => 
    array (
      0 => 'bool',
      'src' => 'int',
      'dst' => 'int',
    ),
    'Redis::time' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'Redis::ttl' => 
    array (
      0 => 'false|int',
      'key' => 'string',
    ),
    'Redis::type' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'Redis::unlink' => 
    array (
      0 => 'int',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'Redis::unsubscribe' => 
    array (
      0 => 'array<array-key, mixed>|bool',
      'channels' => 'array<array-key, mixed>',
    ),
    'Redis::wait' => 
    array (
      0 => 'int',
      'numreplicas' => 'int',
      'timeout' => 'int',
    ),
    'Redis::watch' => 
    array (
      0 => 'bool',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'Redis::xack' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'group' => 'string',
      'ids' => 'array<array-key, mixed>',
    ),
    'Redis::xadd' => 
    array (
      0 => 'false|string',
      'key' => 'string',
      'id' => 'string',
      'values' => 'array<array-key, mixed>',
      'maxlen=' => 'int',
      'approx=' => 'bool',
      'nomkstream=' => 'bool',
    ),
    'Redis::xclaim' => 
    array (
      0 => 'array<array-key, mixed>|bool',
      'key' => 'string',
      'group' => 'string',
      'consumer' => 'string',
      'min_idle' => 'int',
      'ids' => 'array<array-key, mixed>',
      'options' => 'array<array-key, mixed>',
    ),
    'Redis::xdel' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'ids' => 'array<array-key, mixed>',
    ),
    'Redis::xgroup' => 
    array (
      0 => 'mixed',
      'operation' => 'string',
      'key=' => 'null|string',
      'group=' => 'null|string',
      'id_or_consumer=' => 'null|string',
      'mkstream=' => 'bool',
      'entries_read=' => 'int',
    ),
    'Redis::xinfo' => 
    array (
      0 => 'mixed',
      'operation' => 'string',
      'arg1=' => 'null|string',
      'arg2=' => 'null|string',
      'count=' => 'int',
    ),
    'Redis::xpending' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'key' => 'string',
      'group' => 'string',
      'start=' => 'null|string',
      'end=' => 'null|string',
      'count=' => 'int',
      'consumer=' => 'null|string',
    ),
    'Redis::xrange' => 
    array (
      0 => 'array<array-key, mixed>|bool',
      'key' => 'string',
      'start' => 'string',
      'end' => 'string',
      'count=' => 'int',
    ),
    'Redis::xread' => 
    array (
      0 => 'array<array-key, mixed>|bool',
      'streams' => 'array<array-key, mixed>',
      'count=' => 'int',
      'block=' => 'int',
    ),
    'Redis::xreadgroup' => 
    array (
      0 => 'array<array-key, mixed>|bool',
      'group' => 'string',
      'consumer' => 'string',
      'streams' => 'array<array-key, mixed>',
      'count=' => 'int',
      'block=' => 'int',
    ),
    'Redis::xrevrange' => 
    array (
      0 => 'array<array-key, mixed>|bool',
      'key' => 'string',
      'end' => 'string',
      'start' => 'string',
      'count=' => 'int',
    ),
    'Redis::xtrim' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'threshold' => 'string',
      'approx=' => 'bool',
      'minid=' => 'bool',
      'limit=' => 'int',
    ),
    'Redis::zAdd' => 
    array (
      0 => 'int',
      'key' => 'string',
      'score_or_options' => 'float',
      '...more_scores_and_mems=' => 'string',
    ),
    'Redis::zCard' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'Redis::zCount' => 
    array (
      0 => 'int',
      'key' => 'string',
      'start' => 'string',
      'end' => 'string',
    ),
    'Redis::zIncrBy' => 
    array (
      0 => 'float',
      'key' => 'string',
      'value' => 'float',
      'member' => 'string',
    ),
    'Redis::zinter' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'keys' => 'array<array-key, mixed>',
      'weights=' => 'array<array-key, mixed>|null',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'Redis::zinterstore' => 
    array (
      0 => 'int',
      'dst' => 'string',
      'keys' => 'array<array-key, mixed>',
      'weights=' => 'array<array-key, mixed>|null',
      'aggregate=' => 'null|string',
    ),
    'Redis::zLexCount' => 
    array (
      0 => 'int',
      'key' => 'string',
      'min' => 'string',
      'max' => 'string',
    ),
    'Redis::zRange' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
      'options=' => 'bool|null',
    ),
    'Redis::zRangeByLex' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'key' => 'string',
      'min' => 'string',
      'max' => 'string',
      'offset=' => 'int',
      'count=' => 'int',
    ),
    'Redis::zRangeByScore' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'start' => 'string',
      'end' => 'string',
      'options=' => 'array<array-key, mixed>',
    ),
    'Redis::zRank' => 
    array (
      0 => 'int',
      'key' => 'string',
      'member' => 'string',
    ),
    'Redis::zRem' => 
    array (
      0 => 'int',
      'key' => 'string',
      'member' => 'string',
      '...other_members=' => 'string',
    ),
    'Redis::zRemRangeByLex' => 
    array (
      0 => 'int',
      'key' => 'string',
      'min' => 'string',
      'max' => 'string',
    ),
    'Redis::zRemRangeByRank' => 
    array (
      0 => 'int',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
    ),
    'Redis::zRemRangeByScore' => 
    array (
      0 => 'int',
      'key' => 'string',
      'start' => 'string',
      'end' => 'string',
    ),
    'Redis::zRevRange' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
      'scores=' => 'bool',
    ),
    'Redis::zRevRangeByLex' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'max' => 'string',
      'min' => 'string',
      'offset=' => 'int',
      'count=' => 'int',
    ),
    'Redis::zRevRangeByScore' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'max' => 'string',
      'min' => 'string',
      'options=' => 'array<array-key, mixed>',
    ),
    'Redis::zRevRank' => 
    array (
      0 => 'int',
      'key' => 'string',
      'member' => 'string',
    ),
    'Redis::zscan' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'key' => 'string',
      '&iterator' => 'int|null',
      'pattern=' => 'null|string',
      'count=' => 'int',
    ),
    'Redis::zScore' => 
    array (
      0 => 'false|float',
      'key' => 'string',
      'member' => 'string',
    ),
    'Redis::zunion' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'keys' => 'array<array-key, mixed>',
      'weights=' => 'array<array-key, mixed>|null',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'Redis::zunionstore' => 
    array (
      0 => 'int',
      'dst' => 'string',
      'keys' => 'array<array-key, mixed>',
      'weights=' => 'array<array-key, mixed>|null',
      'aggregate=' => 'null|string',
    ),
    'RedisArray::__call' => 
    array (
      0 => 'mixed',
      'function_name' => 'string',
      'arguments' => 'array<array-key, mixed>',
    ),
    'RedisArray::__construct' => 
    array (
      0 => 'void',
      'name_or_hosts' => 'string',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'RedisArray::_function' => 
    array (
      0 => 'bool|impure-callable',
    ),
    'RedisArray::_hosts' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'RedisArray::_rehash' => 
    array (
      0 => 'bool|null',
      'fn=' => 'impure-callable|null',
    ),
    'RedisArray::_target' => 
    array (
      0 => 'null|string',
      'key' => 'string',
    ),
    'RedisArray::del' => 
    array (
      0 => 'bool',
      'key' => 'string',
      '...otherkeys=' => 'string',
    ),
    'RedisArray::exec' => 
    array (
      0 => 'array<array-key, mixed>|null',
    ),
    'RedisArray::flushall' => 
    array (
      0 => 'bool',
    ),
    'RedisArray::flushdb' => 
    array (
      0 => 'bool',
    ),
    'RedisArray::info' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'RedisArray::keys' => 
    array (
      0 => 'array<int, string>',
      'pattern' => 'string',
    ),
    'RedisArray::mget' => 
    array (
      0 => 'array<array-key, mixed>',
      'keys' => 'array<array-key, string>',
    ),
    'RedisArray::mset' => 
    array (
      0 => 'bool',
      'pairs' => 'array<array-key, mixed>',
    ),
    'RedisArray::multi' => 
    array (
      0 => 'RedisArray',
      'host' => 'string',
      'mode=' => 'int|null',
    ),
    'RedisArray::ping' => 
    array (
      0 => 'array<array-key, mixed>|bool',
    ),
    'RedisArray::save' => 
    array (
      0 => 'bool',
    ),
    'RedisArray::unlink' => 
    array (
      0 => 'int',
      'key' => 'string',
      '...otherkeys=' => 'string',
    ),
    'RedisCluster::__construct' => 
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
    'RedisCluster::_masters' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'RedisCluster::_prefix' => 
    array (
      0 => 'string',
      'key' => 'string',
    ),
    'RedisCluster::_unserialize' => 
    array (
      0 => 'mixed',
      'value' => 'string',
    ),
    'RedisCluster::append' => 
    array (
      0 => 'int',
      'key' => 'string',
      'value' => 'string',
    ),
    'RedisCluster::bgrewriteaof' => 
    array (
      0 => 'bool',
      'key_or_address' => 'array{0: string, 1: int}|string',
    ),
    'RedisCluster::bgsave' => 
    array (
      0 => 'bool',
      'key_or_address' => 'array{0: string, 1: int}|string',
    ),
    'RedisCluster::bitcount' => 
    array (
      0 => 'int',
      'key' => 'string',
      'start=' => 'int',
      'end=' => 'int',
      'bybit=' => 'bool',
    ),
    'RedisCluster::bitop' => 
    array (
      0 => 'int',
      'operation' => 'string',
      'deskey' => 'string',
      'srckey' => 'string',
      '...otherkeys=' => 'string',
    ),
    'RedisCluster::bitpos' => 
    array (
      0 => 'int',
      'key' => 'string',
      'bit' => 'bool',
      'start=' => 'int',
      'end=' => 'int',
      'bybit=' => 'bool',
    ),
    'RedisCluster::blpop' => 
    array (
      0 => 'array<array-key, mixed>|null',
      'key' => 'array<array-key, mixed>',
      'timeout_or_key' => 'int',
      '...extra_args=' => 'mixed',
    ),
    'RedisCluster::brpop' => 
    array (
      0 => 'array<array-key, mixed>|null',
      'key' => 'array<array-key, mixed>',
      'timeout_or_key' => 'int',
      '...extra_args=' => 'mixed',
    ),
    'RedisCluster::brpoplpush' => 
    array (
      0 => 'false|string',
      'srckey' => 'string',
      'deskey' => 'string',
      'timeout' => 'int',
    ),
    'RedisCluster::clearlasterror' => 
    array (
      0 => 'bool',
    ),
    'RedisCluster::client' => 
    array (
      0 => 'array<array-key, mixed>|bool|string',
      'key_or_address' => 'array{0: string, 1: int}|string',
      'subcommand' => 'string',
      'arg=' => 'null|string',
    ),
    'RedisCluster::cluster' => 
    array (
      0 => 'mixed',
      'key_or_address' => 'array{0: string, 1: int}|string',
      'command' => 'string',
      '...extra_args=' => 'mixed',
    ),
    'RedisCluster::command' => 
    array (
      0 => 'array<array-key, mixed>|bool',
      '...extra_args=' => 'mixed',
    ),
    'RedisCluster::config' => 
    array (
      0 => 'array<array-key, mixed>|bool',
      'key_or_address' => 'array{0: string, 1: int}|string',
      'subcommand' => 'string',
      '...extra_args=' => 'string',
    ),
    'RedisCluster::dbsize' => 
    array (
      0 => 'int',
      'key_or_address' => 'array{0: string, 1: int}|string',
    ),
    'RedisCluster::decr' => 
    array (
      0 => 'int',
      'key' => 'string',
      'by=' => 'int',
    ),
    'RedisCluster::decrby' => 
    array (
      0 => 'int',
      'key' => 'string',
      'value' => 'int',
    ),
    'RedisCluster::del' => 
    array (
      0 => 'int',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'RedisCluster::dump' => 
    array (
      0 => 'false|string',
      'key' => 'string',
    ),
    'RedisCluster::echo' => 
    array (
      0 => 'string',
      'key_or_address' => 'array{0: string, 1: int}|string',
      'msg' => 'string',
    ),
    'RedisCluster::evalsha' => 
    array (
      0 => 'mixed',
      'script_sha' => 'string',
      'args=' => 'array<array-key, mixed>',
      'num_keys=' => 'int',
    ),
    'RedisCluster::exec' => 
    array (
      0 => 'array<array-key, mixed>|false',
    ),
    'RedisCluster::exists' => 
    array (
      0 => 'bool',
      'key' => 'string',
      '...other_keys=' => 'mixed',
    ),
    'RedisCluster::expire' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'timeout' => 'int',
      'mode=' => 'null|string',
    ),
    'RedisCluster::expireat' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'timestamp' => 'int',
      'mode=' => 'null|string',
    ),
    'RedisCluster::flushall' => 
    array (
      0 => 'bool',
      'key_or_address' => 'array{0: string, 1: int}|string',
      'async=' => 'bool',
    ),
    'RedisCluster::flushdb' => 
    array (
      0 => 'bool',
      'key_or_address' => 'array{0: string, 1: int}|string',
      'async=' => 'bool',
    ),
    'RedisCluster::geoadd' => 
    array (
      0 => 'int',
      'key' => 'string',
      'lng' => 'float',
      'lat' => 'float',
      'member' => 'string',
      '...other_triples_and_options=' => 'float|string',
    ),
    'RedisCluster::geodist' => 
    array (
      0 => 'RedisCluster|false|float',
      'key' => 'string',
      'src' => 'string',
      'dest' => 'string',
      'unit=' => 'null|string',
    ),
    'RedisCluster::geohash' => 
    array (
      0 => 'array<int, string>',
      'key' => 'string',
      'member' => 'string',
      '...other_members=' => 'string',
    ),
    'RedisCluster::geopos' => 
    array (
      0 => 'array<int, array{0: string, 1: string}>',
      'key' => 'string',
      'member' => 'string',
      '...other_members=' => 'string',
    ),
    'RedisCluster::georadius' => 
    array (
      0 => 'mixed',
      'key' => 'string',
      'lng' => 'float',
      'lat' => 'float',
      'radius' => 'float',
      'unit' => 'string',
      'options=' => 'array<array-key, mixed>',
    ),
    'RedisCluster::georadiusbymember' => 
    array (
      0 => 'array<array-key, string>',
      'key' => 'string',
      'member' => 'string',
      'radius' => 'float',
      'unit' => 'string',
      'options=' => 'array<array-key, mixed>',
    ),
    'RedisCluster::get' => 
    array (
      0 => 'false|string',
      'key' => 'string',
    ),
    'RedisCluster::getbit' => 
    array (
      0 => 'int',
      'key' => 'string',
      'value' => 'int',
    ),
    'RedisCluster::getlasterror' => 
    array (
      0 => 'null|string',
    ),
    'RedisCluster::getmode' => 
    array (
      0 => 'int',
    ),
    'RedisCluster::getoption' => 
    array (
      0 => 'int',
      'option' => 'int',
    ),
    'RedisCluster::getrange' => 
    array (
      0 => 'string',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
    ),
    'RedisCluster::getset' => 
    array (
      0 => 'string',
      'key' => 'string',
      'value' => 'string',
    ),
    'RedisCluster::hdel' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'member' => 'string',
      '...other_members=' => 'string',
    ),
    'RedisCluster::hexists' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'member' => 'string',
    ),
    'RedisCluster::hget' => 
    array (
      0 => 'false|string',
      'key' => 'string',
      'member' => 'string',
    ),
    'RedisCluster::hgetall' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
    ),
    'RedisCluster::hincrby' => 
    array (
      0 => 'int',
      'key' => 'string',
      'member' => 'string',
      'value' => 'int',
    ),
    'RedisCluster::hincrbyfloat' => 
    array (
      0 => 'float',
      'key' => 'string',
      'member' => 'string',
      'value' => 'float',
    ),
    'RedisCluster::hkeys' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
    ),
    'RedisCluster::hlen' => 
    array (
      0 => 'false|int',
      'key' => 'string',
    ),
    'RedisCluster::hmget' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'keys' => 'array<array-key, mixed>',
    ),
    'RedisCluster::hmset' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'key_values' => 'array<array-key, mixed>',
    ),
    'RedisCluster::hscan' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      '&iterator' => 'int|null',
      'pattern=' => 'null|string',
      'count=' => 'int',
    ),
    'RedisCluster::hset' => 
    array (
      0 => 'int',
      'key' => 'string',
      'member' => 'string',
      'value' => 'string',
    ),
    'RedisCluster::hsetnx' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'member' => 'string',
      'value' => 'string',
    ),
    'RedisCluster::hstrlen' => 
    array (
      0 => 'int',
      'key' => 'string',
      'field' => 'string',
    ),
    'RedisCluster::hvals' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
    ),
    'RedisCluster::incr' => 
    array (
      0 => 'int',
      'key' => 'string',
      'by=' => 'int',
    ),
    'RedisCluster::incrby' => 
    array (
      0 => 'int',
      'key' => 'string',
      'value' => 'int',
    ),
    'RedisCluster::incrbyfloat' => 
    array (
      0 => 'float',
      'key' => 'string',
      'value' => 'float',
    ),
    'RedisCluster::info' => 
    array (
      0 => 'array<array-key, mixed>',
      'key_or_address' => 'array{0: string, 1: int}|string',
      '...sections=' => 'string',
    ),
    'RedisCluster::keys' => 
    array (
      0 => 'array<array-key, mixed>',
      'pattern' => 'string',
    ),
    'RedisCluster::lastsave' => 
    array (
      0 => 'int',
      'key_or_address' => 'array{0: string, 1: int}|string',
    ),
    'RedisCluster::lget' => 
    array (
      0 => 'RedisCluster|bool|string',
      'key' => 'string',
      'index' => 'int',
    ),
    'RedisCluster::lindex' => 
    array (
      0 => 'false|string',
      'key' => 'string',
      'index' => 'int',
    ),
    'RedisCluster::linsert' => 
    array (
      0 => 'int',
      'key' => 'string',
      'pos' => 'string',
      'pivot' => 'string',
      'value' => 'string',
    ),
    'RedisCluster::llen' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'RedisCluster::lpop' => 
    array (
      0 => 'false|string',
      'key' => 'string',
      'count=' => 'int',
    ),
    'RedisCluster::lpush' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'value' => 'string',
      '...other_values=' => 'string',
    ),
    'RedisCluster::lpushx' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'value' => 'string',
    ),
    'RedisCluster::lrange' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
    ),
    'RedisCluster::lrem' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'value' => 'string',
      'count=' => 'int',
    ),
    'RedisCluster::lset' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'index' => 'int',
      'value' => 'string',
    ),
    'RedisCluster::ltrim' => 
    array (
      0 => 'RedisCluster|bool',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
    ),
    'RedisCluster::mget' => 
    array (
      0 => 'array<array-key, mixed>',
      'keys' => 'array<array-key, mixed>',
    ),
    'RedisCluster::mset' => 
    array (
      0 => 'bool',
      'key_values' => 'array<array-key, mixed>',
    ),
    'RedisCluster::msetnx' => 
    array (
      0 => 'RedisCluster|array<array-key, mixed>|false',
      'key_values' => 'array<array-key, mixed>',
    ),
    'RedisCluster::multi' => 
    array (
      0 => 'RedisCluster|bool',
      'value=' => 'int',
    ),
    'RedisCluster::object' => 
    array (
      0 => 'false|int|string',
      'subcommand' => 'string',
      'key' => 'string',
    ),
    'RedisCluster::persist' => 
    array (
      0 => 'bool',
      'key' => 'string',
    ),
    'RedisCluster::pexpire' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'timeout' => 'int',
      'mode=' => 'null|string',
    ),
    'RedisCluster::pexpireat' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'timestamp' => 'int',
      'mode=' => 'null|string',
    ),
    'RedisCluster::pfadd' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'elements' => 'array<array-key, mixed>',
    ),
    'RedisCluster::pfcount' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'RedisCluster::pfmerge' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'keys' => 'array<array-key, mixed>',
    ),
    'RedisCluster::ping' => 
    array (
      0 => 'string',
      'key_or_address' => 'array{0: string, 1: int}|string',
      'message=' => 'null|string',
    ),
    'RedisCluster::psetex' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'timeout' => 'int',
      'value' => 'string',
    ),
    'RedisCluster::psubscribe' => 
    array (
      0 => 'void',
      'patterns' => 'array<array-key, mixed>',
      'callback' => 'impure-callable',
    ),
    'RedisCluster::pttl' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'RedisCluster::publish' => 
    array (
      0 => 'int',
      'channel' => 'string',
      'message' => 'string',
    ),
    'RedisCluster::pubsub' => 
    array (
      0 => 'array<array-key, mixed>',
      'key_or_address' => 'string',
      '...values=' => 'string',
    ),
    'RedisCluster::randomkey' => 
    array (
      0 => 'string',
      'key_or_address' => 'array{0: string, 1: int}|string',
    ),
    'RedisCluster::rawcommand' => 
    array (
      0 => 'mixed',
      'key_or_address' => 'array{0: string, 1: int}|string',
      'command' => 'string',
      '...args=' => 'mixed',
    ),
    'RedisCluster::rename' => 
    array (
      0 => 'bool',
      'key_src' => 'string',
      'key_dst' => 'string',
    ),
    'RedisCluster::renamenx' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'newkey' => 'string',
    ),
    'RedisCluster::restore' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'timeout' => 'int',
      'value' => 'string',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'RedisCluster::role' => 
    array (
      0 => 'array<array-key, mixed>',
      'key_or_address' => 'array<array-key, mixed>|string',
    ),
    'RedisCluster::rpop' => 
    array (
      0 => 'false|string',
      'key' => 'string',
      'count=' => 'int',
    ),
    'RedisCluster::rpoplpush' => 
    array (
      0 => 'false|string',
      'src' => 'string',
      'dst' => 'string',
    ),
    'RedisCluster::rpush' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      '...elements=' => 'string',
    ),
    'RedisCluster::rpushx' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'value' => 'string',
    ),
    'RedisCluster::sadd' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'value' => 'string',
      '...other_values=' => 'string',
    ),
    'RedisCluster::saddarray' => 
    array (
      0 => 'false|int',
      'key' => 'string',
      'values' => 'array<array-key, mixed>',
    ),
    'RedisCluster::save' => 
    array (
      0 => 'bool',
      'key_or_address' => 'array{0: string, 1: int}|string',
    ),
    'RedisCluster::scan' => 
    array (
      0 => 'array<array-key, mixed>|false',
      '&iterator' => 'int|null',
      'key_or_address' => 'array{0: string, 1: int}|string',
      'pattern=' => 'null|string',
      'count=' => 'int',
    ),
    'RedisCluster::scard' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'RedisCluster::script' => 
    array (
      0 => 'array<array-key, mixed>|bool|string',
      'key_or_address' => 'array{0: string, 1: int}|string',
      '...args=' => 'string',
    ),
    'RedisCluster::sdiff' => 
    array (
      0 => 'list<string>',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'RedisCluster::sdiffstore' => 
    array (
      0 => 'int',
      'dst' => 'string',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'RedisCluster::set' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'string',
      'options=' => 'array<array-key, mixed>|int',
    ),
    'RedisCluster::setbit' => 
    array (
      0 => 'int',
      'key' => 'string',
      'offset' => 'int',
      'onoff' => 'bool',
    ),
    'RedisCluster::setex' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'expire' => 'int',
      'value' => 'string',
    ),
    'RedisCluster::setnx' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'string',
    ),
    'RedisCluster::setoption' => 
    array (
      0 => 'bool',
      'option' => 'int',
      'value' => 'int|string',
    ),
    'RedisCluster::setrange' => 
    array (
      0 => 'RedisCluster|false|int',
      'key' => 'string',
      'offset' => 'int',
      'value' => 'string',
    ),
    'RedisCluster::sinter' => 
    array (
      0 => 'list<string>',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'RedisCluster::sinterstore' => 
    array (
      0 => 'int',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'RedisCluster::sismember' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'string',
    ),
    'RedisCluster::slowlog' => 
    array (
      0 => 'array<array-key, mixed>|bool|int',
      'key_or_address' => 'array{0: string, 1: int}|string',
      '...args=' => 'string',
    ),
    'RedisCluster::smembers' => 
    array (
      0 => 'list<string>',
      'key' => 'string',
    ),
    'RedisCluster::smove' => 
    array (
      0 => 'bool',
      'src' => 'string',
      'dst' => 'string',
      'member' => 'string',
    ),
    'RedisCluster::sort' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'RedisCluster::spop' => 
    array (
      0 => 'string',
      'key' => 'string',
      'count=' => 'int',
    ),
    'RedisCluster::srandmember' => 
    array (
      0 => 'array<array-key, mixed>|string',
      'key' => 'string',
      'count=' => 'int',
    ),
    'RedisCluster::srem' => 
    array (
      0 => 'int',
      'key' => 'string',
      'value' => 'string',
      '...other_values=' => 'string',
    ),
    'RedisCluster::sscan' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'key' => 'string',
      '&iterator' => 'int|null',
      'pattern=' => 'null',
      'count=' => 'int',
    ),
    'RedisCluster::strlen' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'RedisCluster::subscribe' => 
    array (
      0 => 'void',
      'channels' => 'array<array-key, mixed>',
      'cb' => 'impure-callable',
    ),
    'RedisCluster::sunion' => 
    array (
      0 => 'list<string>',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'RedisCluster::sunionstore' => 
    array (
      0 => 'int',
      'dst' => 'string',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'RedisCluster::time' => 
    array (
      0 => 'array<array-key, mixed>',
      'key_or_address' => 'array<array-key, mixed>|string',
    ),
    'RedisCluster::ttl' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'RedisCluster::type' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'RedisCluster::unlink' => 
    array (
      0 => 'int',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'RedisCluster::watch' => 
    array (
      0 => 'RedisCluster|bool',
      'key' => 'string',
      '...other_keys=' => 'string',
    ),
    'RedisCluster::xack' => 
    array (
      0 => 'RedisCluster|false|int',
      'key' => 'string',
      'group' => 'string',
      'ids' => 'array<array-key, mixed>',
    ),
    'RedisCluster::xadd' => 
    array (
      0 => 'RedisCluster|false|string',
      'key' => 'string',
      'id' => 'string',
      'values' => 'array<array-key, mixed>',
      'maxlen=' => 'int',
      'approx=' => 'bool',
    ),
    'RedisCluster::xclaim' => 
    array (
      0 => 'RedisCluster|array<array-key, mixed>|false|string',
      'key' => 'string',
      'group' => 'string',
      'consumer' => 'string',
      'min_iddle' => 'int',
      'ids' => 'array<array-key, mixed>',
      'options' => 'array<array-key, mixed>',
    ),
    'RedisCluster::xdel' => 
    array (
      0 => 'RedisCluster|false|int',
      'key' => 'string',
      'ids' => 'array<array-key, mixed>',
    ),
    'RedisCluster::xgroup' => 
    array (
      0 => 'mixed',
      'operation' => 'string',
      'key=' => 'null|string',
      'group=' => 'null|string',
      'id_or_consumer=' => 'null|string',
      'mkstream=' => 'bool',
      'entries_read=' => 'int',
    ),
    'RedisCluster::xinfo' => 
    array (
      0 => 'mixed',
      'operation' => 'string',
      'arg1=' => 'null|string',
      'arg2=' => 'null|string',
      'count=' => 'int',
    ),
    'RedisCluster::xpending' => 
    array (
      0 => 'RedisCluster|array<array-key, mixed>|false',
      'key' => 'string',
      'group' => 'string',
      'start=' => 'null|string',
      'end=' => 'null|string',
      'count=' => 'int',
      'consumer=' => 'null|string',
    ),
    'RedisCluster::xrange' => 
    array (
      0 => 'RedisCluster|array<array-key, mixed>|bool',
      'key' => 'string',
      'start' => 'string',
      'end' => 'string',
      'count=' => 'int',
    ),
    'RedisCluster::xread' => 
    array (
      0 => 'RedisCluster|array<array-key, mixed>|bool',
      'streams' => 'array<array-key, mixed>',
      'count=' => 'int',
      'block=' => 'int',
    ),
    'RedisCluster::xreadgroup' => 
    array (
      0 => 'RedisCluster|array<array-key, mixed>|bool',
      'group' => 'string',
      'consumer' => 'string',
      'streams' => 'array<array-key, mixed>',
      'count=' => 'int',
      'block=' => 'int',
    ),
    'RedisCluster::xrevrange' => 
    array (
      0 => 'RedisCluster|array<array-key, mixed>|bool',
      'key' => 'string',
      'start' => 'string',
      'end' => 'string',
      'count=' => 'int',
    ),
    'RedisCluster::xtrim' => 
    array (
      0 => 'RedisCluster|false|int',
      'key' => 'string',
      'maxlen' => 'int',
      'approx=' => 'bool',
      'minid=' => 'bool',
      'limit=' => 'int',
    ),
    'RedisCluster::zadd' => 
    array (
      0 => 'int',
      'key' => 'string',
      'score_or_options' => 'float',
      '...more_scores_and_mems=' => 'string',
    ),
    'RedisCluster::zcard' => 
    array (
      0 => 'int',
      'key' => 'string',
    ),
    'RedisCluster::zcount' => 
    array (
      0 => 'int',
      'key' => 'string',
      'start' => 'string',
      'end' => 'string',
    ),
    'RedisCluster::zincrby' => 
    array (
      0 => 'float',
      'key' => 'string',
      'value' => 'float',
      'member' => 'string',
    ),
    'RedisCluster::zinterstore' => 
    array (
      0 => 'int',
      'dst' => 'string',
      'keys' => 'array<array-key, mixed>',
      'weights=' => 'array<array-key, mixed>|null',
      'aggregate=' => 'null|string',
    ),
    'RedisCluster::zlexcount' => 
    array (
      0 => 'int',
      'key' => 'string',
      'min' => 'string',
      'max' => 'string',
    ),
    'RedisCluster::zrange' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'start' => 'int',
      'end' => 'int',
      'options=' => 'bool|null',
    ),
    'RedisCluster::zrangebylex' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'min' => 'string',
      'max' => 'string',
      'offset=' => 'int',
      'count=' => 'int',
    ),
    'RedisCluster::zrangebyscore' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'start' => 'string',
      'end' => 'string',
      'options=' => 'array<array-key, mixed>',
    ),
    'RedisCluster::zrank' => 
    array (
      0 => 'int',
      'key' => 'string',
      'member' => 'string',
    ),
    'RedisCluster::zrem' => 
    array (
      0 => 'int',
      'key' => 'string',
      'value' => 'string',
      '...other_values=' => 'string',
    ),
    'RedisCluster::zremrangebylex' => 
    array (
      0 => 'RedisCluster|false|int',
      'key' => 'string',
      'min' => 'string',
      'max' => 'string',
    ),
    'RedisCluster::zremrangebyrank' => 
    array (
      0 => 'int',
      'key' => 'string',
      'min' => 'string',
      'max' => 'string',
    ),
    'RedisCluster::zremrangebyscore' => 
    array (
      0 => 'int',
      'key' => 'string',
      'min' => 'string',
      'max' => 'string',
    ),
    'RedisCluster::zrevrange' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'min' => 'string',
      'max' => 'string',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'RedisCluster::zrevrangebylex' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'min' => 'string',
      'max' => 'string',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'RedisCluster::zrevrangebyscore' => 
    array (
      0 => 'array<array-key, mixed>',
      'key' => 'string',
      'min' => 'string',
      'max' => 'string',
      'options=' => 'array<array-key, mixed>|null',
    ),
    'RedisCluster::zrevrank' => 
    array (
      0 => 'int',
      'key' => 'string',
      'member' => 'string',
    ),
    'RedisCluster::zscan' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'key' => 'string',
      '&iterator' => 'int|null',
      'pattern=' => 'null|string',
      'count=' => 'int',
    ),
    'RedisCluster::zscore' => 
    array (
      0 => 'float',
      'key' => 'string',
      'member' => 'string',
    ),
    'RedisCluster::zunionstore' => 
    array (
      0 => 'int',
      'dst' => 'string',
      'keys' => 'array<array-key, mixed>',
      'weights=' => 'array<array-key, mixed>|null',
      'aggregate=' => 'null|string',
    ),
    'ssh2_auth_agent' => 
    array (
      0 => 'bool',
      'session' => 'resource',
      'username' => 'string',
    ),
    'ssh2_auth_hostbased_file' => 
    array (
      0 => 'bool',
      'session' => 'resource',
      'username' => 'string',
      'hostname' => 'string',
      'pubkeyfile' => 'string',
      'privkeyfile' => 'string',
      'passphrase=' => 'null|string',
      'local_username=' => 'null|string',
    ),
    'ssh2_auth_none' => 
    array (
      0 => 'bool',
      'session' => 'resource',
      'username' => 'string',
    ),
    'ssh2_auth_password' => 
    array (
      0 => 'bool',
      'session' => 'resource',
      'username' => 'string',
      'password' => 'string',
    ),
    'ssh2_auth_pubkey_file' => 
    array (
      0 => 'bool',
      'session' => 'resource',
      'username' => 'string',
      'pubkeyfile' => 'string',
      'privkeyfile' => 'string',
      'passphrase=' => 'null|string',
    ),
    'ssh2_connect' => 
    array (
      0 => 'false|resource',
      'host' => 'string',
      'port=' => 'int',
      'methods=' => 'array<array-key, mixed>|null',
      'callbacks=' => 'array<array-key, mixed>|null',
    ),
    'ssh2_disconnect' => 
    array (
      0 => 'bool',
      'session' => 'resource',
    ),
    'ssh2_exec' => 
    array (
      0 => 'false|resource',
      'session' => 'resource',
      'command' => 'string',
      'pty=' => 'bool',
      'env=' => 'array<array-key, mixed>|null',
      'width=' => 'int',
      'height=' => 'int',
      'width_height_type=' => 'int',
    ),
    'ssh2_fetch_stream' => 
    array (
      0 => 'false|resource',
      'channel' => 'resource',
      'streamid' => 'int',
    ),
    'ssh2_fingerprint' => 
    array (
      0 => 'false|string',
      'session' => 'resource',
      'flags=' => 'int',
    ),
    'ssh2_forward_listen' => 
    array (
      0 => 'false|resource',
      'session' => 'resource',
      'port' => 'int',
      'host=' => 'string',
      'max_connections=' => 'int',
    ),
    'ssh2_methods_negotiated' => 
    array (
      0 => 'array<array-key, mixed>',
      'session' => 'resource',
    ),
    'ssh2_publickey_add' => 
    array (
      0 => 'bool',
      'pkey' => 'resource',
      'algoname' => 'string',
      'blob' => 'string',
      'overwrite=' => 'bool',
      'attributes=' => 'array<array-key, mixed>|null',
    ),
    'ssh2_publickey_init' => 
    array (
      0 => 'false|resource',
      'session' => 'resource',
    ),
    'ssh2_publickey_list' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'pkey' => 'resource',
    ),
    'ssh2_publickey_remove' => 
    array (
      0 => 'bool',
      'pkey' => 'resource',
      'algoname' => 'string',
      'blob' => 'string',
    ),
    'ssh2_scp_recv' => 
    array (
      0 => 'bool',
      'session' => 'resource',
      'remote_file' => 'string',
      'local_file' => 'string',
    ),
    'ssh2_scp_send' => 
    array (
      0 => 'bool',
      'session' => 'resource',
      'local_file' => 'string',
      'remote_file' => 'string',
      'create_mode=' => 'int',
    ),
    'ssh2_sftp' => 
    array (
      0 => 'false|resource',
      'session' => 'resource',
    ),
    'ssh2_sftp_chmod' => 
    array (
      0 => 'bool',
      'sftp' => 'resource',
      'filename' => 'string',
      'mode' => 'int',
    ),
    'ssh2_sftp_lstat' => 
    array (
      0 => 'array{0: int, 10: int, 11: int, 12: int, 1: int, 2: int, 3: int, 4: int, 5: int, 6: int, 7: int, 8: int, 9: int, atime: int, blksize: int, blocks: int, ctime: int, dev: int, gid: int, ino: int, mode: int, mtime: int, nlink: int, rdev: int, size: int, uid: int}|false',
      'sftp' => 'resource',
      'path' => 'string',
    ),
    'ssh2_sftp_mkdir' => 
    array (
      0 => 'bool',
      'sftp' => 'resource',
      'dirname' => 'string',
      'mode=' => 'int',
      'recursive=' => 'bool',
    ),
    'ssh2_sftp_readlink' => 
    array (
      0 => 'false|non-falsy-string',
      'sftp' => 'resource',
      'link' => 'string',
    ),
    'ssh2_sftp_realpath' => 
    array (
      0 => 'false|non-falsy-string',
      'sftp' => 'resource',
      'filename' => 'string',
    ),
    'ssh2_sftp_rename' => 
    array (
      0 => 'bool',
      'sftp' => 'resource',
      'from' => 'string',
      'to' => 'string',
    ),
    'ssh2_sftp_rmdir' => 
    array (
      0 => 'bool',
      'sftp' => 'resource',
      'dirname' => 'string',
    ),
    'ssh2_sftp_stat' => 
    array (
      0 => 'array{0: int, 10: int, 11: int, 12: int, 1: int, 2: int, 3: int, 4: int, 5: int, 6: int, 7: int, 8: int, 9: int, atime: int, blksize: int, blocks: int, ctime: int, dev: int, gid: int, ino: int, mode: int, mtime: int, nlink: int, rdev: int, size: int, uid: int}|false',
      'sftp' => 'resource',
      'path' => 'string',
    ),
    'ssh2_sftp_symlink' => 
    array (
      0 => 'bool',
      'sftp' => 'resource',
      'target' => 'string',
      'link' => 'string',
    ),
    'ssh2_sftp_unlink' => 
    array (
      0 => 'bool',
      'sftp' => 'resource',
      'filename' => 'string',
    ),
    'ssh2_shell' => 
    array (
      0 => 'false|resource',
      'session' => 'resource',
      'termtype=' => 'string',
      'env=' => 'array<array-key, mixed>|null',
      'width=' => 'int',
      'height=' => 'int',
      'width_height_type=' => 'int',
    ),
    'ssh2_tunnel' => 
    array (
      0 => 'false|resource',
      'session' => 'resource',
      'host' => 'string',
      'port' => 'int',
    ),
    'Swoole\\Atomic::add' => 
    array (
      0 => 'int',
      'add_value=' => 'int',
    ),
    'Swoole\\Atomic::cmpset' => 
    array (
      0 => 'bool',
      'cmp_value' => 'int',
      'new_value' => 'int',
    ),
    'Swoole\\Atomic::get' => 
    array (
      0 => 'int',
    ),
    'Swoole\\Atomic::set' => 
    array (
      0 => 'void',
      'value' => 'int',
    ),
    'Swoole\\Atomic::sub' => 
    array (
      0 => 'int',
      'sub_value=' => 'int',
    ),
    'Swoole\\Client::__destruct' => 
    array (
      0 => 'void',
    ),
    'Swoole\\Client::close' => 
    array (
      0 => 'bool',
      'force=' => 'bool',
    ),
    'Swoole\\Client::connect' => 
    array (
      0 => 'bool',
      'host' => 'string',
      'port=' => 'int',
      'timeout=' => 'float',
      'sock_flag=' => 'int',
    ),
    'Swoole\\Client::getpeername' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'Swoole\\Client::getsockname' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'Swoole\\Client::isConnected' => 
    array (
      0 => 'bool',
    ),
    'Swoole\\Client::recv' => 
    array (
      0 => 'false|string',
      'size=' => 'int',
      'flag=' => 'int',
    ),
    'Swoole\\Client::send' => 
    array (
      0 => 'int',
      'data' => 'string',
      'flag=' => 'int',
    ),
    'Swoole\\Client::sendfile' => 
    array (
      0 => 'bool',
      'filename' => 'string',
      'offset=' => 'int',
      'length=' => 'int',
    ),
    'Swoole\\Client::sendto' => 
    array (
      0 => 'bool',
      'ip' => 'string',
      'port' => 'int',
      'data' => 'string',
    ),
    'Swoole\\Client::set' => 
    array (
      0 => 'bool',
      'settings' => 'array<array-key, mixed>',
    ),
    'Swoole\\Connection\\Iterator::count' => 
    array (
      0 => 'int',
    ),
    'Swoole\\Connection\\Iterator::current' => 
    array (
      0 => 'Connection',
    ),
    'Swoole\\Connection\\Iterator::key' => 
    array (
      0 => 'int',
    ),
    'Swoole\\Connection\\Iterator::next' => 
    array (
      0 => 'void',
    ),
    'Swoole\\Connection\\Iterator::offsetExists' => 
    array (
      0 => 'bool',
      'fd' => 'int',
    ),
    'Swoole\\Connection\\Iterator::offsetGet' => 
    array (
      0 => 'Connection',
      'fd' => 'string',
    ),
    'Swoole\\Connection\\Iterator::offsetSet' => 
    array (
      0 => 'void',
      'fd' => 'int',
      'value' => 'mixed',
    ),
    'Swoole\\Connection\\Iterator::offsetUnset' => 
    array (
      0 => 'void',
      'fd' => 'int',
    ),
    'Swoole\\Connection\\Iterator::rewind' => 
    array (
      0 => 'void',
    ),
    'Swoole\\Connection\\Iterator::valid' => 
    array (
      0 => 'bool',
    ),
    'Swoole\\Coroutine::create' => 
    array (
      0 => 'false|int',
      'func' => 'impure-callable',
      '...param=' => 'mixed',
    ),
    'Swoole\\Coroutine::getuid' => 
    array (
      0 => 'int',
    ),
    'Swoole\\Coroutine::resume' => 
    array (
      0 => 'bool',
      'cid' => 'int',
    ),
    'Swoole\\Coroutine::suspend' => 
    array (
      0 => 'bool',
    ),
    'Swoole\\Coroutine\\Client::__destruct' => 
    array (
      0 => 'ReturnType',
    ),
    'Swoole\\Coroutine\\Client::close' => 
    array (
      0 => 'bool',
    ),
    'Swoole\\Coroutine\\Client::connect' => 
    array (
      0 => 'bool',
      'host' => 'string',
      'port=' => 'int',
      'timeout=' => 'float',
      'sock_flag=' => 'int',
    ),
    'Swoole\\Coroutine\\Client::getpeername' => 
    array (
      0 => 'array<array-key, mixed>|false',
    ),
    'Swoole\\Coroutine\\Client::getsockname' => 
    array (
      0 => 'array<array-key, mixed>|false',
    ),
    'Swoole\\Coroutine\\Client::isConnected' => 
    array (
      0 => 'bool',
    ),
    'Swoole\\Coroutine\\Client::recv' => 
    array (
      0 => 'false|string',
      'timeout=' => 'float',
    ),
    'Swoole\\Coroutine\\Client::send' => 
    array (
      0 => 'false|int',
      'data' => 'string',
      'timeout=' => 'float',
    ),
    'Swoole\\Coroutine\\Client::sendfile' => 
    array (
      0 => 'bool',
      'filename' => 'string',
      'offset=' => 'int',
      'length=' => 'int',
    ),
    'Swoole\\Coroutine\\Client::sendto' => 
    array (
      0 => 'bool',
      'address' => 'string',
      'port' => 'int',
      'data' => 'string',
    ),
    'Swoole\\Coroutine\\Client::set' => 
    array (
      0 => 'bool',
      'settings' => 'array<array-key, mixed>',
    ),
    'Swoole\\Coroutine\\Http\\Client::__destruct' => 
    array (
      0 => 'ReturnType',
    ),
    'Swoole\\Coroutine\\Http\\Client::addFile' => 
    array (
      0 => 'bool',
      'path' => 'string',
      'name' => 'string',
      'type=' => 'null|string',
      'filename=' => 'null|string',
      'offset=' => 'int',
      'length=' => 'int',
    ),
    'Swoole\\Coroutine\\Http\\Client::close' => 
    array (
      0 => 'bool',
    ),
    'Swoole\\Coroutine\\Http\\Client::execute' => 
    array (
      0 => 'bool',
      'path' => 'string',
    ),
    'Swoole\\Coroutine\\Http\\Client::get' => 
    array (
      0 => 'bool',
      'path' => 'string',
    ),
    'Swoole\\Coroutine\\Http\\Client::getDefer' => 
    array (
      0 => 'bool',
    ),
    'Swoole\\Coroutine\\Http\\Client::post' => 
    array (
      0 => 'bool',
      'path' => 'string',
      'data' => 'mixed',
    ),
    'Swoole\\Coroutine\\Http\\Client::recv' => 
    array (
      0 => 'Swoole\\WebSocket\\Frame|bool',
      'timeout=' => 'float',
    ),
    'Swoole\\Coroutine\\Http\\Client::set' => 
    array (
      0 => 'bool',
      'settings' => 'array<array-key, mixed>',
    ),
    'Swoole\\Coroutine\\Http\\Client::setCookies' => 
    array (
      0 => 'bool',
      'cookies' => 'array<array-key, mixed>',
    ),
    'Swoole\\Coroutine\\Http\\Client::setData' => 
    array (
      0 => 'bool',
      'data' => 'array<array-key, mixed>|string',
    ),
    'Swoole\\Coroutine\\Http\\Client::setDefer' => 
    array (
      0 => 'bool',
      'defer=' => 'bool',
    ),
    'Swoole\\Coroutine\\Http\\Client::setHeaders' => 
    array (
      0 => 'bool',
      'headers' => 'array<array-key, mixed>',
    ),
    'Swoole\\Coroutine\\Http\\Client::setMethod' => 
    array (
      0 => 'bool',
      'method' => 'string',
    ),
    'Swoole\\Event::add' => 
    array (
      0 => 'false|int',
      'fd' => 'int',
      'read_callback=' => 'impure-callable|null',
      'write_callback=' => 'impure-callable|null',
      'events=' => 'int',
    ),
    'Swoole\\Event::defer' => 
    array (
      0 => 'bool',
      'callback' => 'impure-callable',
    ),
    'Swoole\\Event::del' => 
    array (
      0 => 'bool',
      'fd' => 'string',
    ),
    'Swoole\\Event::exit' => 
    array (
      0 => 'void',
    ),
    'Swoole\\Event::set' => 
    array (
      0 => 'bool',
      'fd' => 'int',
      'read_callback=' => 'impure-callable|null',
      'write_callback=' => 'impure-callable|null',
      'events=' => 'int',
    ),
    'Swoole\\Event::wait' => 
    array (
      0 => 'void',
    ),
    'Swoole\\Event::write' => 
    array (
      0 => 'bool',
      'fd' => 'string',
      'data' => 'string',
    ),
    'Swoole\\Http\\Request::rawContent' => 
    array (
      0 => 'string',
    ),
    'Swoole\\Http\\Response::cookie' => 
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
    'Swoole\\Http\\Response::end' => 
    array (
      0 => 'bool',
      'content=' => 'null|string',
    ),
    'Swoole\\Http\\Response::header' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'string',
      'format=' => 'bool',
    ),
    'Swoole\\Http\\Response::initHeader' => 
    array (
      0 => 'bool',
    ),
    'Swoole\\Http\\Response::rawcookie' => 
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
    'Swoole\\Http\\Response::sendfile' => 
    array (
      0 => 'bool',
      'filename' => 'string',
      'offset=' => 'int',
      'length=' => 'int',
    ),
    'Swoole\\Http\\Response::status' => 
    array (
      0 => 'bool',
      'http_code' => 'int',
      'reason=' => 'string',
    ),
    'Swoole\\Http\\Response::write' => 
    array (
      0 => 'bool',
      'content' => 'string',
    ),
    'Swoole\\Http\\Server::on' => 
    array (
      0 => 'bool',
      'event_name' => 'string',
      'callback' => 'impure-callable',
    ),
    'Swoole\\Http\\Server::start' => 
    array (
      0 => 'bool',
    ),
    'Swoole\\Lock::lock' => 
    array (
      0 => 'bool',
      'operation=' => 'int',
      'timeout=' => 'float',
    ),
    'Swoole\\Lock::unlock' => 
    array (
      0 => 'bool',
    ),
    'Swoole\\Process::__destruct' => 
    array (
      0 => 'void',
    ),
    'Swoole\\Process::alarm' => 
    array (
      0 => 'bool',
      'usec' => 'int',
      'type=' => 'int',
    ),
    'Swoole\\Process::close' => 
    array (
      0 => 'bool',
      'which=' => 'int',
    ),
    'Swoole\\Process::daemon' => 
    array (
      0 => 'bool',
      'nochdir=' => 'bool',
      'noclose=' => 'bool',
      'pipes=' => 'array<array-key, mixed>',
    ),
    'Swoole\\Process::exec' => 
    array (
      0 => 'bool',
      'exec_file' => 'string',
      'args' => 'array<array-key, mixed>',
    ),
    'Swoole\\Process::exit' => 
    array (
      0 => 'void',
      'exit_code=' => 'int',
    ),
    'Swoole\\Process::freeQueue' => 
    array (
      0 => 'bool',
    ),
    'Swoole\\Process::kill' => 
    array (
      0 => 'bool',
      'pid' => 'int',
      'signal_no=' => 'int',
    ),
    'Swoole\\Process::name' => 
    array (
      0 => 'bool',
      'process_name' => 'string',
    ),
    'Swoole\\Process::pop' => 
    array (
      0 => 'false|string',
      'size=' => 'int',
    ),
    'Swoole\\Process::push' => 
    array (
      0 => 'bool',
      'data' => 'string',
    ),
    'Swoole\\Process::read' => 
    array (
      0 => 'string',
      'size=' => 'int',
    ),
    'Swoole\\Process::signal' => 
    array (
      0 => 'bool',
      'signal_no' => 'int',
      'callback=' => 'impure-callable|null',
    ),
    'Swoole\\Process::start' => 
    array (
      0 => 'bool|int',
    ),
    'Swoole\\Process::statQueue' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'Swoole\\Process::useQueue' => 
    array (
      0 => 'bool',
      'key=' => 'int',
      'mode=' => 'int',
      'capacity=' => 'int',
    ),
    'Swoole\\Process::wait' => 
    array (
      0 => 'array<array-key, mixed>',
      'blocking=' => 'bool',
    ),
    'Swoole\\Process::write' => 
    array (
      0 => 'int',
      'data' => 'string',
    ),
    'Swoole\\Redis\\Server::format' => 
    array (
      0 => 'false|string',
      'type' => 'int',
      'value=' => 'string',
    ),
    'Swoole\\Redis\\Server::setHandler' => 
    array (
      0 => 'bool',
      'command' => 'string',
      'callback' => 'impure-callable',
    ),
    'Swoole\\Redis\\Server::start' => 
    array (
      0 => 'bool',
    ),
    'Swoole\\Server::addlistener' => 
    array (
      0 => 'Swoole\\Server\\Port|false',
      'host' => 'string',
      'port' => 'int',
      'sock_type' => 'int',
    ),
    'Swoole\\Server::addProcess' => 
    array (
      0 => 'false|int',
      'process' => 'Swoole\\Process',
    ),
    'Swoole\\Server::bind' => 
    array (
      0 => 'bool',
      'fd' => 'int',
      'uid' => 'int',
    ),
    'Swoole\\Server::close' => 
    array (
      0 => 'bool',
      'fd' => 'int',
      'reset=' => 'bool',
    ),
    'Swoole\\Server::confirm' => 
    array (
      0 => 'bool',
      'fd' => 'int',
    ),
    'Swoole\\Server::connection_info' => 
    array (
      0 => 'array<array-key, mixed>',
      'fd' => 'int',
      'reactor_id=' => 'int',
      'ignoreError=' => 'bool',
    ),
    'Swoole\\Server::connection_list' => 
    array (
      0 => 'array<array-key, mixed>',
      'start_fd=' => 'int',
      'find_count=' => 'int',
    ),
    'Swoole\\Server::exist' => 
    array (
      0 => 'bool',
      'fd' => 'int',
    ),
    'Swoole\\Server::finish' => 
    array (
      0 => 'bool',
      'data' => 'string',
    ),
    'Swoole\\Server::getClientInfo' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'fd' => 'int',
      'reactor_id=' => 'int',
      'ignoreError=' => 'bool',
    ),
    'Swoole\\Server::getClientList' => 
    array (
      0 => 'array<array-key, mixed>',
      'start_fd=' => 'int',
      'find_count=' => 'int',
    ),
    'Swoole\\Server::getLastError' => 
    array (
      0 => 'int',
    ),
    'Swoole\\Server::heartbeat' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'ifCloseConnection=' => 'bool',
    ),
    'Swoole\\Server::listen' => 
    array (
      0 => 'Swoole\\Server\\Port|false',
      'host' => 'string',
      'port' => 'int',
      'sock_type' => 'int',
    ),
    'Swoole\\Server::on' => 
    array (
      0 => 'bool',
      'event_name' => 'string',
      'callback' => 'impure-callable',
    ),
    'Swoole\\Server::pause' => 
    array (
      0 => 'bool',
      'fd' => 'int',
    ),
    'Swoole\\Server::protect' => 
    array (
      0 => 'bool',
      'fd' => 'int',
      'is_protected=' => 'bool',
    ),
    'Swoole\\Server::reload' => 
    array (
      0 => 'bool',
      'only_reload_taskworker=' => 'bool',
    ),
    'Swoole\\Server::resume' => 
    array (
      0 => 'bool',
      'fd' => 'int',
    ),
    'Swoole\\Server::send' => 
    array (
      0 => 'bool',
      'fd' => 'int',
      'send_data' => 'string',
      'serverSocket=' => 'int',
    ),
    'Swoole\\Server::sendfile' => 
    array (
      0 => 'bool',
      'conn_fd' => 'int',
      'filename' => 'string',
      'offset=' => 'int',
      'length=' => 'int',
    ),
    'Swoole\\Server::sendMessage' => 
    array (
      0 => 'bool',
      'message' => 'int',
      'dst_worker_id' => 'int',
    ),
    'Swoole\\Server::sendto' => 
    array (
      0 => 'bool',
      'ip' => 'string',
      'port' => 'int',
      'send_data' => 'string',
      'server_socket=' => 'int',
    ),
    'Swoole\\Server::sendwait' => 
    array (
      0 => 'bool',
      'conn_fd' => 'int',
      'send_data' => 'string',
    ),
    'Swoole\\Server::set' => 
    array (
      0 => 'bool',
      'settings' => 'array<array-key, mixed>',
    ),
    'Swoole\\Server::shutdown' => 
    array (
      0 => 'bool',
    ),
    'Swoole\\Server::start' => 
    array (
      0 => 'bool',
    ),
    'Swoole\\Server::stats' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'Swoole\\Server::stop' => 
    array (
      0 => 'bool',
      'workerId=' => 'int',
    ),
    'Swoole\\Server::task' => 
    array (
      0 => 'false|int',
      'data' => 'string',
      'taskWorkerIndex=' => 'int',
      'finishCallback=' => 'impure-callable|null',
    ),
    'Swoole\\Server::taskwait' => 
    array (
      0 => 'void',
      'data' => 'string',
      'timeout=' => 'float',
      'taskWorkerIndex=' => 'int',
    ),
    'Swoole\\Server::taskWaitMulti' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'tasks' => 'array<array-key, mixed>',
      'timeout=' => 'float',
    ),
    'Swoole\\Server\\Port::__destruct' => 
    array (
      0 => 'void',
    ),
    'Swoole\\Server\\Port::on' => 
    array (
      0 => 'bool',
      'event_name' => 'string',
      'callback' => 'impure-callable',
    ),
    'Swoole\\Server\\Port::set' => 
    array (
      0 => 'void',
      'settings' => 'array<array-key, mixed>',
    ),
    'Swoole\\Table::column' => 
    array (
      0 => 'bool',
      'name' => 'string',
      'type' => 'int',
      'size=' => 'int',
    ),
    'Swoole\\Table::count' => 
    array (
      0 => 'int',
    ),
    'Swoole\\Table::create' => 
    array (
      0 => 'bool',
    ),
    'Swoole\\Table::current' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'Swoole\\Table::decr' => 
    array (
      0 => 'float|int',
      'key' => 'string',
      'column' => 'string',
      'incrby=' => 'int',
    ),
    'Swoole\\Table::del' => 
    array (
      0 => 'bool',
      'key' => 'string',
    ),
    'Swoole\\Table::destroy' => 
    array (
      0 => 'bool',
    ),
    'Swoole\\Table::exist' => 
    array (
      0 => 'bool',
      'key' => 'string',
    ),
    'Swoole\\Table::get' => 
    array (
      0 => 'int',
      'key' => 'string',
      'field=' => 'null|string',
    ),
    'Swoole\\Table::incr' => 
    array (
      0 => 'float|int',
      'key' => 'string',
      'column' => 'string',
      'incrby=' => 'int',
    ),
    'Swoole\\Table::key' => 
    array (
      0 => 'string',
    ),
    'Swoole\\Table::next' => 
    array (
      0 => 'void',
    ),
    'Swoole\\Table::rewind' => 
    array (
      0 => 'void',
    ),
    'Swoole\\Table::set' => 
    array (
      0 => 'bool',
      'key' => 'string',
      'value' => 'array<array-key, mixed>',
    ),
    'Swoole\\Table::valid' => 
    array (
      0 => 'bool',
    ),
    'Swoole\\Timer::after' => 
    array (
      0 => 'false|int',
      'ms' => 'int',
      'callback' => 'impure-callable',
      '...params=' => 'mixed',
    ),
    'Swoole\\Timer::clear' => 
    array (
      0 => 'bool',
      'timer_id' => 'int',
    ),
    'Swoole\\Timer::exists' => 
    array (
      0 => 'bool',
      'timer_id' => 'int',
    ),
    'Swoole\\Timer::tick' => 
    array (
      0 => 'false|int',
      'ms' => 'int',
      'callback' => 'impure-callable',
      '...params=' => 'string',
    ),
    'Swoole\\WebSocket\\Server::exist' => 
    array (
      0 => 'bool',
      'fd' => 'int',
    ),
    'Swoole\\WebSocket\\Server::on' => 
    array (
      0 => 'bool',
      'event_name' => 'string',
      'callback' => 'impure-callable',
    ),
    'Swoole\\WebSocket\\Server::pack' => 
    array (
      0 => 'string',
      'data' => 'string',
      'opcode=' => 'int',
      'flags=' => 'int',
    ),
    'Swoole\\WebSocket\\Server::push' => 
    array (
      0 => 'bool',
      'fd' => 'int',
      'data' => 'string',
      'opcode=' => 'int',
      'flags=' => 'int',
    ),
    'Swoole\\WebSocket\\Server::unpack' => 
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
      'read_callback=' => 'impure-callable|null',
      'write_callback=' => 'impure-callable|null',
      'events=' => 'int',
    ),
    'swoole_event_defer' => 
    array (
      0 => 'bool',
      'callback' => 'impure-callable',
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
      'read_callback=' => 'impure-callable|null',
      'write_callback=' => 'impure-callable|null',
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
      'family=' => 'int',
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
      'callback' => 'impure-callable',
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
      'callback' => 'impure-callable',
    ),
    'swoole_version' => 
    array (
      0 => 'string',
    ),
    'ZMQContext::__construct' => 
    array (
      0 => 'void',
      'io_threads=' => 'int',
      'persistent=' => 'bool',
    ),
    'ZMQContext::getOpt' => 
    array (
      0 => 'int|string',
      'option' => 'string',
    ),
    'ZMQContext::getsocket' => 
    array (
      0 => 'ZMQSocket',
      'type' => 'int',
      'dsn' => 'string',
      'on_new_socket=' => 'impure-callable',
    ),
    'ZMQContext::ispersistent' => 
    array (
      0 => 'bool',
    ),
    'ZMQContext::setOpt' => 
    array (
      0 => 'ZMQContext',
      'option' => 'int',
      'value' => 'mixed',
    ),
    'ZMQDevice::getidletimeout' => 
    array (
      0 => 'ZMQDevice',
    ),
    'ZMQDevice::gettimertimeout' => 
    array (
      0 => 'ZMQDevice',
    ),
    'ZMQDevice::run' => 
    array (
      0 => 'void',
    ),
    'ZMQDevice::setidlecallback' => 
    array (
      0 => 'ZMQDevice',
      'idle_callback' => 'impure-callable',
      'timeout' => 'int',
      'user_data=' => 'mixed',
    ),
    'ZMQDevice::setidletimeout' => 
    array (
      0 => 'ZMQDevice',
      'timeout' => 'int',
    ),
    'ZMQDevice::settimercallback' => 
    array (
      0 => 'ZMQDevice',
      'idle_callback' => 'impure-callable',
      'timeout' => 'int',
      'user_data=' => 'mixed',
    ),
    'ZMQDevice::settimertimeout' => 
    array (
      0 => 'ZMQDevice',
      'timeout' => 'int',
    ),
    'ZMQPoll::add' => 
    array (
      0 => 'string',
      'entry' => 'mixed',
      'type' => 'int',
    ),
    'ZMQPoll::clear' => 
    array (
      0 => 'ZMQPoll',
    ),
    'ZMQPoll::count' => 
    array (
      0 => 'int',
    ),
    'ZMQPoll::getlasterrors' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'ZMQPoll::poll' => 
    array (
      0 => 'int',
      '&w readable' => 'array<array-key, mixed>',
      '&w writable' => 'array<array-key, mixed>',
      'timeout=' => 'int',
    ),
    'ZMQPoll::remove' => 
    array (
      0 => 'bool',
      'remove' => 'mixed',
    ),
    'ZMQSocket::__construct' => 
    array (
      0 => 'void',
      'ZMQContext' => 'ZMQContext',
      'type' => 'int',
      'persistent_id=' => 'string',
      'on_new_socket=' => 'impure-callable',
    ),
    'ZMQSocket::bind' => 
    array (
      0 => 'ZMQSocket',
      'dsn' => 'string',
      'force=' => 'bool',
    ),
    'ZMQSocket::connect' => 
    array (
      0 => 'ZMQSocket',
      'dsn' => 'string',
      'force=' => 'bool',
    ),
    'ZMQSocket::disconnect' => 
    array (
      0 => 'ZMQSocket',
      'dsn' => 'string',
    ),
    'ZMQSocket::getendpoints' => 
    array (
      0 => 'array<array-key, mixed>',
    ),
    'ZMQSocket::getpersistentid' => 
    array (
      0 => 'null|string',
    ),
    'ZMQSocket::getsockettype' => 
    array (
      0 => 'int',
    ),
    'ZMQSocket::getsockopt' => 
    array (
      0 => 'int|string',
      'key' => 'string',
    ),
    'ZMQSocket::ispersistent' => 
    array (
      0 => 'bool',
    ),
    'ZMQSocket::recv' => 
    array (
      0 => 'string',
      'mode=' => 'int',
    ),
    'ZMQSocket::recvmulti' => 
    array (
      0 => 'array<array-key, string>',
      'mode=' => 'int',
    ),
    'ZMQSocket::send' => 
    array (
      0 => 'ZMQSocket',
      'message' => 'array<array-key, mixed>',
      'mode=' => 'int',
    ),
    'ZMQSocket::sendmulti' => 
    array (
      0 => 'ZMQSocket',
      'message' => 'array<array-key, mixed>',
      'mode=' => 'int',
    ),
    'ZMQSocket::setsockopt' => 
    array (
      0 => 'ZMQSocket',
      'key' => 'int',
      'value' => 'mixed',
    ),
    'ZMQSocket::unbind' => 
    array (
      0 => 'ZMQSocket',
      'dsn' => 'string',
    ),
    'Zookeeper::addAuth' => 
    array (
      0 => 'bool',
      'scheme' => 'string',
      'cert' => 'string',
      'completion_cb=' => 'impure-callable',
    ),
    'Zookeeper::close' => 
    array (
      0 => 'void',
    ),
    'Zookeeper::connect' => 
    array (
      0 => 'void',
      'host' => 'string',
      'watcher_cb=' => 'impure-callable',
      'recv_timeout=' => 'int',
    ),
    'Zookeeper::create' => 
    array (
      0 => 'string',
      'path' => 'string',
      'value=' => 'string',
      'acl=' => 'array<array-key, mixed>',
      'flags=' => 'int',
    ),
    'Zookeeper::delete' => 
    array (
      0 => 'bool',
      'path' => 'string',
      'version=' => 'int',
    ),
    'Zookeeper::exists' => 
    array (
      0 => 'bool',
      'path' => 'string',
      'watcher_cb=' => 'impure-callable',
    ),
    'Zookeeper::get' => 
    array (
      0 => 'string',
      'path' => 'string',
      'watcher_cb=' => 'impure-callable',
      '&stat_info=' => 'array<array-key, mixed>',
      'max_size=' => 'int',
    ),
    'Zookeeper::getAcl' => 
    array (
      0 => 'array<array-key, mixed>',
      'path' => 'string',
    ),
    'Zookeeper::getChildren' => 
    array (
      0 => 'array<array-key, mixed>|false',
      'path' => 'string',
      'watcher_cb=' => 'impure-callable',
    ),
    'Zookeeper::getClientId' => 
    array (
      0 => 'int',
    ),
    'Zookeeper::getConfig' => 
    array (
      0 => 'ZookeeperConfig',
    ),
    'Zookeeper::getRecvTimeout' => 
    array (
      0 => 'int',
    ),
    'Zookeeper::getState' => 
    array (
      0 => 'int',
    ),
    'Zookeeper::isRecoverable' => 
    array (
      0 => 'bool',
    ),
    'Zookeeper::set' => 
    array (
      0 => 'bool',
      'path' => 'string',
      'value=' => 'string',
      'version=' => 'int',
      '&stat_info=' => 'array<array-key, mixed>',
    ),
    'Zookeeper::setAcl' => 
    array (
      0 => 'bool',
      'path' => 'string',
      'version' => 'int',
      'acl' => 'array<array-key, mixed>',
    ),
    'Zookeeper::setDebugLevel' => 
    array (
      0 => 'bool',
      'level' => 'int',
    ),
    'Zookeeper::setDeterministicConnOrder' => 
    array (
      0 => 'bool',
      'trueOrFalse' => 'bool',
    ),
    'Zookeeper::setLogStream' => 
    array (
      0 => 'bool',
      'stream' => 'resource',
    ),
    'Zookeeper::setWatcher' => 
    array (
      0 => 'bool',
      'watcher_cb' => 'impure-callable',
    ),
    'zookeeper_dispatch' => 
    array (
      0 => 'void',
    ),
    'ZookeeperConfig::add' => 
    array (
      0 => 'void',
      'members' => 'string',
      'version=' => 'int',
      '&stat_info=' => 'array<array-key, mixed>',
    ),
    'ZookeeperConfig::get' => 
    array (
      0 => 'string',
      'watcher_cb=' => 'impure-callable',
      '&stat_info=' => 'array<array-key, mixed>',
    ),
    'ZookeeperConfig::remove' => 
    array (
      0 => 'void',
      'members' => 'string',
      'version=' => 'int',
      '&stat_info=' => 'array<array-key, mixed>',
    ),
    'ZookeeperConfig::set' => 
    array (
      0 => 'void',
      'members' => 'string',
      'version=' => 'int',
      '&stat_info=' => 'array<array-key, mixed>',
    ),
  ),
);