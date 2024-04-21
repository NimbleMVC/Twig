# <h1 align="center">NimblePHP - Twig</h1>
Ten pakiet dla frameworka NimblePHP rozszerza jego możliwości o obsługę widoków za pomocą silnika szablonów Twig. Dzięki
integracji z Twig, możesz łatwo tworzyć elastyczne i czytelne szablony, które uczynią Twój projekt bardziej modułowym i
łatwiejszym w utrzymaniu. Idealne rozwiązanie dla developerów szukających efektywnego narzędzia do zarządzania prezentacją
danych w aplikacjach PHP.

**Dokumentacja** projektu dostępna jest pod linkiem:
https://nimblemvc.github.io/documentation/extension/twig/start/#

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

## Współtworzenie
Zachęcamy do współtworzenia! Masz sugestie, znalazłeś błędy, chcesz pomóc w rozwoju? Otwórz issue lub prześlij pull request.

## Pomoc
Wszelkie problemy oraz pytania należy zadawać przez zakładkę discussions w github pod linkiem:
https://github.com/NimbleMVC/Twig/discussions