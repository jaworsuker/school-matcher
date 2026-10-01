# School Matcher

Moduł Symfony, który przy zakładaniu konta próbuje powiązać użytkownika ze szkołą na podstawie wpisanej
nazwy, nawet jeśli jest skrócona, potoczna albo z błędami (`XIV LO Staszica`, `14 LO`, `Zeromskego`, `ZSEiI`).

**Stack:** PHP 8.3, Symfony 7.4 (LTS), Doctrine ORM, MySQL 8.4, PHPUnit 12, Docker (php-fpm + nginx + MySQL).

## Uruchomienie

Wymagany jest tylko Docker z Compose oraz `make`.

```bash
make setup   # build obrazów, start kontenerów, composer install, migracje, import szkół z docs/schools.txt
make test    # testy jednostkowe i funkcjonalne (na osobnej bazie school_matcher_test)
```

API działa pod `http://localhost:8080`. Porty można zmienić: `HTTP_PORT=8081 DB_PORT=3308 make setup`.
Pozostałe komendy: `make help`. `make reset` usuwa kontenery razem z bazą.

Bez `make`:

```bash
docker compose up -d --build --wait
docker compose exec php composer install
docker compose exec php bin/console doctrine:migrations:migrate -n
docker compose exec php bin/console app:schools:import docs/schools.txt
```

## API

| Metoda | Ścieżka               | Opis                                                          |
|--------|-----------------------|---------------------------------------------------------------|
| POST   | `/api/users`          | Rejestracja użytkownika + dopasowanie i zapis przypisania szkoły |
| GET    | `/api/users/{id}`     | Użytkownik wraz z przypisaniem                                |
| POST   | `/api/schools/match`  | Podgląd dopasowania bez zapisu (np. pod podpowiedzi w formularzu) |
| GET    | `/api/schools`        | Lista szkół w katalogu                                        |

```bash
curl -X POST localhost:8080/api/users -H 'Content-Type: application/json' \
     -d '{"email": "ania@example.com", "schoolName": "XIV LO Staszica"}'
```

```json
{
  "id": 1,
  "email": "ania@example.com",
  "createdAt": "2026-10-01T12:00:00+00:00",
  "schoolAssignment": {
    "status": "matched",
    "rawInput": "XIV LO Staszica",
    "city": null,
    "score": 0.932,
    "school": { "id": 2, "name": "XIV Liceum Ogólnokształcące im. Stanisława Staszica", "city": "Warszawa", "type": "liceum", "aliases": ["Staszic", "XIV LO", "14 LO", "Staszica"] },
    "candidates": [ { "schoolId": 2, "name": "XIV Liceum Ogólnokształcące im. Stanisława Staszica", "city": "Warszawa", "score": 0.932 } ]
  }
}
```

Pole `city` w żądaniu jest opcjonalne. Pomaga rozstrzygnąć niejednoznaczne przypadki.

```bash
curl -X POST localhost:8080/api/schools/match -H 'Content-Type: application/json' -d '{"name": "LO", "city": "Kraków"}'
```

Błędy zwracane są jako zwięzły JSON (RFC 7807, bez śladu stosu): `422` przy walidacji (z listą `violations`), `409` gdy e-mail jest
zajęty, `404` dla nieistniejącego użytkownika lub endpointu, `405` przy złej metodzie HTTP, `400` przy niepoprawnym JSON.

## Założenia

- **Katalog szkół jest w bazie, a plik to tylko źródło importu.** Przypisanie użytkownika do szkoły trzeba
  gdzieś utrwalić, a relacja `user → school` w bazie jest naturalna. Import (`app:schools:import`) jest
  idempotentny: szkołę identyfikuje para (oficjalna nazwa, miasto), więc ponowny import aktualizuje
  istniejące rekordy zamiast tworzyć duplikaty.
- **Nie przypisujemy szkoły na siłę.** Wynik dopasowania ma jeden z trzech statusów:
  - `matched`: pewne dopasowanie, szkoła zostaje przypisana automatycznie,
  - `needs_review`: są kandydaci, ale dopasowanie jest niepewne albo niejednoznaczne, więc decyzję podejmuje człowiek,
  - `unmatched`: nic sensownego.

  Błędnie przypisana szkoła jest gorsza niż brak przypisania, bo nikt jej potem nie poprawi.
- **Rejestracja nie jest blokowana przez szkołę.** Użytkownik zostaje utworzony zawsze, a przypisanie
  (z oryginalnym wpisem i migawką kandydatów) jest zapisywane także przy `needs_review` i `unmatched`.
  Dzięki temu można je później zweryfikować.
- **Użytkownik to mock.** Ma tylko e-mail (unikalny, bez względu na wielkość liter). Hasło, uwierzytelnianie
  i potwierdzanie e-maila są poza zakresem zadania.
- Plik z zadania nie zawierał przykładowych wpisów użytkowników, więc przygotowałem własne. Są w testach
  (`tests/Unit/School/Domain/Matching/SchoolMatcherTest.php`) i dokumentują zachowanie algorytmu.

## Podejście do dopasowania

Kod dopasowania jest w `src/School/Domain/Matching` i nie zależy od Symfony ani Doctrine.

**1. Normalizacja** (`SchoolNameNormalizer`). Wpis użytkownika i każdy wariant nazwy szkoły są sprowadzane
do tej samej postaci:
- małe litery, usunięte polskie znaki i interpunkcja (`Żeromski!` → `zeromski`),
- numer szkoły w dowolnym zapisie jako jeden token: `XIV`, `14`, `nr 14` → `#14`; `Pierwsze`, `I` → `#1`.
  Rzymska liczba jest rozpoznawana tylko na pozycji numeru (początek, koniec, po „nr”), żeby spójnik
  „i” w „Elektronicznych i Informatycznych” nie stał się numerem 1,
- rozwinięte skróty: `LO` → `liceum ogolnoksztalcace`, `ZS` → `zespol szkol`,
- usunięte słowa bez znaczenia: `im.`, `nr`, `i`, `w`.

**2. Warianty szkoły.** Każda szkoła jest porównywana z oficjalną nazwą, aliasami z pliku oraz
automatycznie wygenerowanym skrótowcem (`Zespół Szkół Elektronicznych i Informatycznych` → `zseii`).

**3. Podobieństwo słów** (`TokenSimilarity`):
- odmiana: wspólny rdzeń, a różnica tylko w końcówce (`Mickiewicz`/`Mickiewicza`, `Kraków`/`Krakowie`),
- literówki: odległość Damerau-Levenshteina, w której przestawienie liter liczy się jako jeden błąd
  (`Zeromskego`, `Mickiewciza`),
- krótkie skróty (`TI`, `TM`) i numery muszą być identyczne.

**4. Ważone pokrycie** (`SchoolMatcher`). Wynik to ważona część słów wpisu, które mają odpowiednik
w wariancie nazwy (75%), plus ważona część słów wariantu pokryta przez wpis (25%). Wagi działają jak IDF:
słowo występujące w wielu szkołach („liceum”) waży mało, a charakterystyczne („staszica”, `#14`) dużo.
Dzięki temu „Liceum Mickiewicza” trafia w Mickiewicza, a nie w dowolne liceum. Wagi liczone są
automatycznie z katalogu, bez ręcznie utrzymywanej listy słów ważnych i nieważnych.

**5. Reguły dodatkowe:**
- **różny numer wyklucza szkołę.** `II LO` i `III LO` różnią się jedną literą, ale to różne szkoły,
- **miasto:** wykrywane z wpisu („Staszic Warszawa”, „LO w Krakowie”) albo podawane w osobnym polu.
  Szkoły z innego miasta dostają karę (×0.7), ale nie są odrzucane, bo użytkownik mógł się pomylić,
- **progi:** `matched` przy wyniku ≥ 0.8 i przewadze ≥ 0.1 nad drugim kandydatem, `needs_review` przy
  wyniku ≥ 0.5, poniżej tego `unmatched`. Wartości dobrałem na przykładach z testów, są stałymi w
  `SchoolMatcher`.

### Przykłady (z testów)

| Wpis                         | Wynik                                                  |
|------------------------------|--------------------------------------------------------|
| `14 LO`, `XIV LO Staszica`   | `matched` – XIV LO im. Staszica                        |
| `Pierwsze liceum`            | `matched` – I LO im. Mickiewicza                       |
| `Mickiewciza`, `Zeromskego`  | `matched` (literówki)                                  |
| `ZSEiI`, `Elektronik Warszawa` | `matched` – ZSEiI                                    |
| `Konopnickiej w Gdańsku`     | `matched`, wykryte miasto Gdańsk                       |
| `LO w Krakowie`              | `needs_review` – dwa licea w Krakowie z tym samym wynikiem |
| `II LO Kopernika`            | `needs_review` – numer wskazuje na inną szkołę niż patron |
| `Mickiewicz` + `city: Kraków`| `needs_review` – szkoła jest w Warszawie               |
| `IV LO`, `Szkoła Podstawowa nr 3` | `unmatched`                                       |

## Struktura

```
src/
  School/
    Domain/          Model (School, SchoolAlias, SchoolType), Matching (algorytm), Repository (interfejs)
    Application/     Import (handler importu, DTO)
    Infrastructure/  Doctrine (repozytorium), Import (parser pliku)
    UI/              Http (kontroler, request/response DTO), Console (komenda importu)
  User/
    Domain/          User, SchoolAssignment, wyjątki, interfejs repozytorium
    Application/     RegisterUser (command + handler)
    Infrastructure/  Doctrine
    UI/Http/         kontroler, request/response DTO
  Shared/UI/Http/    bazowy kontroler API
tests/
  Unit/              normalizacja, podobieństwo, matcher na danych z docs/schools.txt, parser
  Functional/        API na prawdziwej bazie testowej
  Double/            repozytorium in-memory
```

Moduły są rozdzielone wg kontekstu (`School`, `User`), a w środku podzielone na warstwy. `User` zależy od
`School`, ale nie odwrotnie. Logika domenowa korzysta z interfejsów repozytoriów, więc testy jednostkowe
matchera działają na repozytorium w pamięci, bez bazy.

Świadome uproszczenia:
- mapowanie Doctrine jest na atrybutach w klasach domenowych. To pragmatyczny kompromis, w czystszym
  wariancie byłoby mapowanie XML w warstwie infrastruktury,
- handlery są wywoływane bezpośrednio, bez Messengera ani command busa, bo przy tej skali byłby to narzut
  bez korzyści,
- katalog szkół jest ładowany i indeksowany w pamięci przy każdym dopasowaniu. Przy 12 (a nawet kilkuset)
  szkołach to pomijalny koszt, przy większym katalogu patrz niżej.

## Kierunki rozwoju

- **Prawdziwy katalog szkół**: import z Rejestru Szkół i Placówek Oświatowych (RSPO), który ma publiczne API.
  Wtedy dochodzi kilkadziesiąt tysięcy szkół, więc dopasowanie z pełnym przeglądem katalogu trzeba
  zastąpić wstępnym wyborem kandydatów (MySQL FULLTEXT, Elasticsearch/Meilisearch) i dopiero na nich
  liczyć dokładny wynik. Indeks (wagi, warianty) warto wtedy cache'ować i przeliczać po imporcie.
- **Panel weryfikacji** przypisań `needs_review`: `PATCH /api/users/{id}/school-assignment` z wybraną
  szkołą. Dane do tego już są: zapisany oryginalny wpis i kandydaci.
- **Uczenie się aliasów**: ręcznie zatwierdzony wpis (np. „Staszicówka” → XIV LO) może zostać dodany jako
  alias szkoły, więc następnym razem dopasuje się automatycznie.
- **Zapobieganie u źródła**: autocomplete w formularzu rejestracji oparte o `POST /api/schools/match`.
  Użytkownik wybiera szkołę z listy, a wpis ręczny jest tylko awaryjny.
- **Lepsza obsługa polszczyzny**: stemmer/lematyzator (np. Morfologik) zamiast heurystyki wspólnego rdzenia,
  słownik zdrobnień i nazw potocznych („ogólniak”, „Staszicówka”, „Elektronik”).
- **Strojenie progów na prawdziwych danych**: zbiór historycznych wpisów z poprawnymi odpowiedziami
  pozwoliłby mierzyć precyzję i kompletność dopasowań oraz dobierać progi.
- **Kontekst użytkownika**: miasto z adresu, geolokalizacja, typ szkoły (np. formularz tylko dla techników).
