# FITkit autentizační server
Autentizační server generující JWT tokeny pro připojení na překladový server

## Požadavky
* PHP verze 5.6+ (testováno na verzi 7.4)
* Webserver s funkčním session storage

Před nasazením je nutné vytvořit konfigurační soubor `config.php`. Nejjednodušší cestou je zkopírovat soubor `config.example.php`, který obsahuje příklad konfigurace.

## Popis API

Api se skládá ze dvou částí - skriptu pro aplikaci a skriptu pro generování tokenu. Tyto části mezi sebou komunikují přes `session` funkcionalitu v PHP.

1. Externí aplikace zavolá skript `request-token.php?new` čím vytvoří nový požadavek a jako odpověď dostane ID nového požadavku (dále jako `$requestID`).
2. Aplikace požádá operační systém, aby otevřel stránku `generate-token.php?request=$requestID&appname=Nazev+Aplikace` ve výchozím prohlížeči. Tato stránka je dostupná pouze po přihlášení. Uživatel bude tedy vyzván k přihlášení, pokud již není na cílovém webu přihlášen.
3. Mezitím se na pozadí externí aplikace pravidelně dotazuje na vygenerovaný token `request-token.php?request=$requestID`.
4. Uživatel se po přihlášení dostane na stránku, kde bude dotázán, zda požadavek aplikace na vygenerování tokenu povolit či zakázat.
5. Po výběru možnosit se zobrazí potvrzení a uživatel může stránku opustit.
6. Externí aplikace dostane při dotazování `request-token.php?request=$requestID` v odpovědi vygenerovaný token obsahující uživatelské jméno. Pokud uživatel požadavek zamítnul, skript vrátí místo toho stavový kód `403 Forbidden`. Tím se celý proces ukončí.

### Rozhraní pro externí aplikaci (`request-token.php`)
Skript, který umožnuje externí aplikaci (vscode rozšíření) vytvořit nový požadavek na token a po autentizaci tento token získat.

Tento skript nemá žádné závislosti a měl by být veřejně dostupný (bez nutnosti přihlášení). Jediný požadavek je nutnost běže na stejné instanci webserveru jako `request-token.php`, aby tyto dva skripty měli společné PHP Session.

#### Argumenty (GET)
* `new` - Založení nového požadavku
* `request=[string]` - ID požadavku

#### Případ použití
Vytvoření nového požadavku. Jako odpověď je ID nového požadavku
* Dotaz: `request-token.php?new`
* Návratový stav: `202 Accepted`
* Odpověď: `50lar892n9jjv7ssmbd87psa5e`

#### Případ použití
Dotaz, zda již byl uživatel autentizován. V tomto případě stále nebyl a je vrácen stavový kód `204 No Content` s prázdnou odpovědí.
* Dotaz: `request-token.php?request=50lar892n9jjv7ssmbd87psa5e`
* Návratový stav: `204 No Content`
* Odpověď: *prázdná*

#### Případ použití
Dotaz, zda již byl uživatel autentizován. V tomto případě byl požadavek schválen a je vrácen vygenerovaný token.
* Dotaz: `request-token.php?request=50lar892n9jjv7ssmbd87psa5e`
* Návratový stav: `200 OK`
* Odpověď: `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJqYW5jaDMyIiwic3ViIjoieGxvZ2luMDAiLCJleHAiOjE1MTYyMzkwMjJ9.EwmyYGM89ONMTxMZ1ilLdojGiLNSjJu125a9qb2hLRo`

#### Případ použití
Dotaz, zda již byl uživatel autentizován. V tomto případě byl požadavek zamítnut.
* Dotaz: `request-token.php?request=50lar892n9jjv7ssmbd87psa5e`
* Návratový stav: `403 Forbidden`
* Odpověď: *prázdná*

### Generování tokenu (`generate-token.php`)
Skript pro generování autentizačního JWT tokenu. Token po vygenerování je dostupný v superglobální proměnné `$_SESSION["token"]`, kde jako session id je použita hodnota parametru `request`. Na výstupu je poté vygenerována HTML stránka informující uživatele o úspěšnosti této akce.

Tento skript neprovádí sám o sobě autentizaci uživatele, ale je závislý na existující autentizaci webserveru nebo framewroku. Proto je vhodné tento skript umístit tam, kde jej může načíst pouze přihlášený uživatel. Ve stejné složce musí být umístěn konfigurační skript `config.php` a složka `php-jwt` s PHP JWT knihovnou.

#### Argumenty (GET)
* `request=[string]` - ID požadavku **(povinné)**
* `appname=[string]` - Název aplikace,který se zobrazí na úvodní stránce
* `nonce=[string]`- Potvrzení požadavku. Hodnota je kryptograficky náhodný klíč, který je vygenerován při vytvoření požadavku. Zabraňuje automatickému potvrzení požadavku aplikací. Odkaz pro potvrzení obsahující tento parametr je vložen na stránce, pokud dosud nebyla uživatelem provedena žádná akce.
* `reject` - Zamítnout požadavek. Nekombinovat s parametrem `nonce`.

#### Případ použití
Vytvoření nového požadavku na získání tokenu. Uživatel bude vyzván o potvrzení nebo zamítnutí tohto požadavku
* Dotaz: `generate-token.php?request=50lar892n9jjv7ssmbd87psa5e&appname=Moje+Aplikce`
* Návratový stav: `200 OK`
* Odpověď: *HTML stránka*

#### Případ použití
Zamítnutí požadavku. Tento dotaz by neměl být volán automaticky, ale měl by být závislý na vstupu uživatele.

Jakmile se jednou požadavek zamítne, již ho není možné obnovit a musí se vytoviřt nový požadavek.
* Dotaz: `generate-token.php?request=50lar892n9jjv7ssmbd87psa5e&reject=1`
* Návratový stav: `200 OK`
* Odpověď: *HTML stránka*

#### Případ použití
Potvrzerní požadavku. Tento dotaz by neměl být volán automaticky, ale měl by být závislý na vstupu uživatele. Jakmile se jednou požadavek potvrdí, již ho není možné zamítnout.

**Pokud parametr `nonce` obsahuje chybný klíč, je požadavek zamítnut!**

Hodnota parametru nonce je vygenerována při prvním načtení stránky a je součástí potvrzovacího odkazu na stránce.

* Dotaz: `generate-token.php?request=50lar892n9jjv7ssmbd87psa5e&nonce=<unikátní_klíč>`
* Návratový stav: `200 OK`
* Odpověď: *HTML stránka*
