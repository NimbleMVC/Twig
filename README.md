# Obsługa Twig
Szczegółowa instrukcja wkrótce w:
https://nimblemvc.github.io/documentation/

## Instalacja
```shell
composer require nimblephp/twig
```

## Użycie
1. Tworzymi plik w folderze `View` o rozszerzeniu `.twig` np. `test.twig`
2. W metodzie kontrolera dajemy:
```php
$view = new View(new Twig());
$view->render('test');
```

## Konfiguracja
- **TWIG_CACHE** (false) - czy twig ma tworzyć cache
