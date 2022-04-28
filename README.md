# PHP_GDWrapper

## Init

```
GDWrapper::init($options = [], $logger = null)
```

- `$options` - Опции передаются через ассоциативный массив (пустой массив означает опции по умолчанию)
  - `JPEG_COMPRESSION_QUALITY` - уровень сжатия оригинальных JPEG-ов, [1..100], по умолчанию - 92.
  - `WEBP_COMPRESSION_QUALITY` - уровень сжатия WEBP, [1..100], по умолчанию 80.
  - `PNG_COMPRESSION_QUALITY` - уровень сжатия PNG [0..9], по умолчанию 0 (без сжатия)
- `$logger` - логгер, реализующий интерфейс `Psr\Log\LoggerInterface`, например `Monolog` или `Arris\AppLogger::scope()` ИЛИ **null**

Используется один уровень логгирования - error - если обрабатываемый файл не существует. 

Пример инициализации:

```
use AJUR\Wrappers;
use Arris\AppLogger;

AppLogger::addScope('gdwrapper', [ 'gd_error.log' , Logger::ERROR, 'enabled' => getenv('LOGGING.GDWRAPPER_ERRORS'));

GDWrapper::init([], AppLogger::scope('gdwrapper'));
```

## GDImageInfo

Все методы `GDWrapper` возвращают экземпляр класса GDImageInfo, содержащий информацию о результирующем изображении:

```json
{
  "valid":true,
  "error_message":"",
  "width":200,
  "height":135,
  "type":2,
  "mime":"image\/jpeg",
  "mime_extension":".jpeg",
  "filename":"test_RIA.jpg",
  "extension":"jpg",
  "quality":40,
  "data":null
}
```

- `extension` - расширение, определенное на основании filename (без точки)
- `mime` - mime-тип файла
- `mime_extension` - расширение, определенное на основании mime-типа (с точкой)
- `quality` - качество сжатия изображения
- `type` - тип, см константы IMAGETYPE_BMP, IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_WEBP
- `valid` - успешность последней обработки (например, загрузки изображения из файла)
- `error_message` - сообщение об ошибке

Важно: этот класс НЕ КИДАЕТ исключения.

## Методы GDWrapper

### cropImage(string $fn_source, string $fn_target, array $xy_source, array $wh_dest, array $wh_source, $quality = null):GDImageInfo

CROP изображения с сохранением в файл
    
### resizeImageAspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):GDImageInfo

вписывает изображение в указанные размеры

### resizePictureAspect(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):GDImageInfo

Ресайзит картинку по большей из сторон

### verticalImage(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, $image_quality = null):GDImageInfo

???

### getFixedPicture(string $fn_source, string $fn_target, int $maxwidth, int $maxheight, int $image_quality = null):GDImageInfo

Ресайзит картинку в фиксированные размеры

### addWaterMark(string $fn_source, array $params, int $pos_index, $quality = null, string $fn_target = ''):GDImageInfo

Добавляет на изображение вотермарк

- `$fn_source` - файл изображения (будет заменён)
- `$params` - массив из двух опций [ 'watermark' => имя файла, 'margin' => отступ]
- `$pos_index` - позиция  WM_POSITION_LEFT_TOP = 1, WM_POSITION_RIGHT_TOP = 2, WM_POSITION_RIGHT_BOTTOM = 3 , WM_POSITION_LEFT_BOTTOM = 4
- `$quality` - качество изображения при сохранении
- `$fn_target` - целевой файл, если он указан - то `$fn_source` не будет перезаписан

### rotate(string $fn_source, string $roll_direction = "", $quality = null):GDImageInfo

TODO

### rotate2(string $fn_source, string $roll_direction = "", $quality = null):GDImageInfo

Враппер над rotate(), под таким именем используется на 47news, должно быть удалено отсюда и из 47news с заменой на Rotate
   
### flip(string $fn_source, int $mode, $quality = null, string $fn_target = ''):GDImageInfo

Переворачивает изображение, используя выбранный режим:

- `$fn_source`
- `$mode` - Режим переворота - одна из констант IMG_FLIP_*:
  - IMG_FLIP_HORIZONTAL 	Переворачивает изображение по горизонтали.
  - IMG_FLIP_VERTICAL 	Переворачивает изображение по вертикали.
  - IMG_FLIP_BOTH 	Переворачивает изображение и по горизонтали и по вертикали.


### imageFillColor(string $fn_target, int $width, int $height, array $color, $quality = null):GDImageInfo;

TODO


