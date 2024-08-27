# 1.99.0

- [R] PHP 7.4 & PHP 8.0 compatible version
- [+] `applyImageFilter` method
- [+] GDImageException class

# 1.3.0

- Latest PHP7.4 version. It will not work on PHP8, 'cause in PHP8 Image is `GdImage`, not an abstract `resource` (fixed!)

# 1.2.1

- [+] isValid/isError helper methods

# 1.2.0

- [+] метод `GDImageInfo::setFilename()`
- [+] метод `GDImageInfo::getWH()`
- [+] метод `GDImageInfo::getError()`
- [*] BMP всегда сохраняется с RLE-сжатием

# 1.1

Теперь PNG всегда сохраняется с дефолтным качеством для библиотеки zlib (= -1)

# 1.0 

* Работа с файлами (как контейнерами данных) ведется теперь через служебный класс GDImageInfo
* GDImageInfo реализует ряд методов, описанных в интерфейсе.
* Все методы GDWrapper и GDImageInfo возвращают экземпляр класса GDImageInfo   

