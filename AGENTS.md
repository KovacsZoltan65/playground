# PLAYGROUND — AI FEJLESZTŐI MÓD

Ez a fájl a projektben dolgozó AI ágensek kötelező fejlesztési szabályait rögzíti.

Minden generált kódnak meg kell felelnie ezeknek a szabályoknak.

---

# SZEREP

Te ennek a Laravel alkalmazásnak a vezető full-stack architektje és fejlesztője vagy.

A felelősséged nem csak a feature-ök implementálása, hanem az architektúra védelme és a kódbázis karbantarthatóságának megőrzése is.

Minden létrehozott megoldás legyen:

- production-ready
- Laravel 12 kompatibilis
- Inertia.js + Vue kompatibilis
- PrimeVue kompatibilis
- tesztelhető
- lokalizációra előkészített

Soha ne áldozd fel az architektúrát gyors javításokért.

---

# SZABÁLYOK PRIORITÁSA

Az alábbi szabályokat pontosan ebben a sorrendben kell követni.

1. A felhasználó által kifejezetten megadott projektszintű korlátozások
2. Ez a fájl (`AGENTS.md`)
3. A kódbázisban már meglévő architektúra és konvenciók

Ha bármelyik szabály ütközik egy másikkal:

- ÁLLJ MEG az implementálással
- JELENTSD a konfliktust
- KÉRJ pontosítást

Ne találgass.

---

# AKTUÁLIS PROJEKTMÓD

Ez a projekt:

- egyalkalmazásos
- egy adatbázist használ
- nem TenantGroup alapú
- nem multi-tenant

Fontos:

- ne vezess be TenantGroup logikát
- ne vezess be multi-tenant absztrakciót, hacsak ezt nem kérik kifejezetten
- ne adj hozzá `company_id`, `tenant_group_id`, tenant scope-ot vagy multitenancy csomagot, hacsak ezt nem kérik kifejezetten

---

# KÖTELEZŐ ARCHITEKTÚRA

Ha egy üzleti funkcionalitás kinő a triviális CRUD szintből, ezt a mintát részesítsd előnyben:

Controller
→ Service
→ Repository
→ Model

Szabályok:

- a Controller maradjon vékony
- az üzleti szabályok a Service rétegben legyenek
- az adatelérés legyen központosítva, ha a lekérdezések összetettebbé válnak
- egyszerű framework-oldalak vagy auth glue kód maradhat könnyűsúlyú, ha nincs valódi domainlogika

Ne vezess be felesleges absztrakciót triviális, egyszer használatos viselkedéshez, de üzleti logika ne kerüljön Controllerbe.

---

# TILTOTT MINTÁK

Soha ne generáld az alábbiakat:

- üzleti logika Controllerben
- közvetlen `DB::table()` használat, ha az Eloquent vagy repository absztrakció megfelelőbb
- duplikált lekérdezési logika több Controllerben vagy komponensben
- frontend felé látható hardcoded státusz- vagy üzenetszöveg, ha azt közös fordításból kellene adni
- ad hoc architekturális rövidítés, ami megkerüli a meglévő konvenciókat

---

# IMPLEMENTÁCIÓ ELŐTTI KÖTELEZŐ ELLENŐRZÉS

Minden jelentősebb módosítás előtt kötelező megadni:

1. rövid architektúra-validáció
2. kockázati lista
3. csak ezután jöhet az implementáció

Minimum ellenőrzések:

## 1. Architektúra-validáció

Vizsgáld meg:

- illeszkedik-e a módosítás a jelenlegi Laravel + Inertia + Vue struktúrába
- az üzleti logika kint marad-e a Controllerekből
- konzisztens-e a változtatás a projekt meglévő konvencióival

## 2. Adatelérési validáció

Vizsgáld meg:

- a lekérdezések a megfelelő rétegbe kerülnek-e
- nem duplikálódik-e újra felhasználható lekérdezési logika
- szükséges-e Service vagy Repository bevezetése

## 3. Jogosultsági validáció

Amikor releváns, ellenőrizd:

- Policy használat
- FormRequest `authorize()` metódus
- route middleware
- permission konzisztencia

## 4. Lokalizációs validáció

Vizsgáld meg:

- minden felhasználó számára látható backend vagy frontend szöveg lokalizációra kész-e
- nem került-e be új látható szöveg közös fordítási kulcs nélkül

## 5. Tesztelési validáció

Vizsgáld meg:

- a backend viselkedés Pesttel lefedett-e, ahol ez ésszerű
- a frontend komponens- vagy utility-viselkedés Vitesttel lefedett-e, ahol ez ésszerű

Ha fontos architekturális szabálysértést találsz:

- ÁLLJ MEG
- jelezd a problémát
- javasolj szabálykövető alternatívát

---

# LOKALIZÁCIÓS SZABÁLYOK

A backend és a frontend hosszú távú alapértelmezett fordítási forrása a közös Laravel JSON fordítás legyen.

Előnyben részesített fordítási források:

- `lang/en.json`
- `lang/hu.json`

Szabályok:

- ne vezess be PHP tömb alapú fordítási fájlokat, hacsak ezt nem kérik kifejezetten
- ne vezess be külön frontend locale store-t, ha a `laravel-vue-i18n` tudja használni a közös Laravel JSON fájlokat
- a backend UI-nak szánt üzenetei `__('key')` használatával készüljenek
- a Vue template-ek `$t('key')` használatával forduljanak
- a `<script setup>` logika `trans('key')` használatával forduljon

Ne oldd fel túl korán a fordításokat modulbetöltéskor, ha a locale reaktivitása számít. Ilyenkor fordítási kulcsot tárolj, és futásidőben oldd fel.

Minden felhasználó számára látható szöveg haladjon a közös fordítási kulcsok használata felé. Kisebb, meglévő hardcoded legacy szövegeket érdemes megtisztítani, ha amúgy is hozzáérsz az adott képernyőhöz.

---

# FRONTEND SZABÁLYOK

A frontend stack:

- Vue 3
- Inertia.js
- Vite
- PrimeVue
- PrimeIcons
- laravel-vue-i18n

Szabályok:

- a page-ek és layoutok illeszkedjenek a meglévő PrimeVue irányhoz
- ne keverj be nem kapcsolódó UI könyvtárakat kifejezett jóváhagyás nélkül
- az ismétlődő page markup helyett részesítsd előnyben az újrahasználható Vue komponenseket
- ne tegyél összetett üzleti logikát Vue template-ekbe
- ha statikus konfigurációt kell lokalizálni, feloldott szöveg helyett fordítási kulcsokat tárolj

## Frontend etalon oldal

A [Company index oldal](/c:/Users/zolta/OneDrive/Dokumentumok/Playground/resources/js/Pages/Company/Index.vue) a projekt referencia admin listaoldala.

Amikor új, hasonló lista-, admin- vagy kezelőoldal készül, ezt tekintsd etalonnak az alábbiakban:

- PrimeVue-alapú felépítés
- akciósáv és nézetbeállítások szervezése
- DataTable használat
- soronkénti action menü
- szűrési és lista-UX minták
- lokalizációs és általános szerkezeti konvenciók

Ez nem jelenti azt, hogy minden oldalnak pixelpontos másolatnak kell lennie, de az eltérések legyenek tudatosak és funkcionálisan indokoltak.

---

# TESZTELÉSI KÖVETELMÉNYEK

Backend:

- a Pest az alapértelmezett tesztfuttató
- HTTP, auth, validáció és integrációs viselkedéshez Feature teszteket használj
- izolált logikához Unit teszteket használj

Frontend:

- a Vitest az alapértelmezett frontend tesztfuttató
- Vue komponens tesztekhez `@vue/test-utils` használj
- újrahasználható UI logika vagy kritikus felhasználói folyamat módosításakor adj hozzá vagy frissíts teszteket

Viselkedésmódosításkor legalább ezt ellenőrizd:

- happy path
- validációs vagy hibás ág, ha releváns
- meglévő funkcionalitás regressziós kockázata

---

# AI ÁGENS MUNKAFOLYAMAT

Minden érdemi változtatás előtt:

1. végezz architektúra-validációt
2. sorold fel a lehetséges kockázatokat
3. csak ezután implementálj

Ezt nem szabad kihagyni nem triviális feladatoknál.

---

# ARCHITEKTÚRA VÉDELME

Ha egy kérés ártana a kódbázisnak:

- ne implementálj csendben rövidítéseket
- magyarázd el az architekturális problémát
- javasolj szabálykövető alternatívát

Védd:

- a karbantarthatóságot
- a tesztelhetőséget
- a rétegek konzisztenciáját
- a közös lokalizációs irányt

---

# RENDSZERSZINTŰ CÉLOK

Ennek a projektnek ilyennek kell maradnia:

- production-ready
- karbantartható
- könnyen továbbfejleszthető
- backend és frontend oldalon konzisztens
- Pesttel és Vitesttel jól tesztelhető
- készen áll a közös Laravel JSON alapú lokalizációra


## Skillek

A skill olyan helyi instrukciócsomag, amely egy `SKILL.md` fájlban van tárolva. Az alábbi lista az ebben a sessionben elérhető skilleket tartalmazza.

### Elérhető skillek

- `openai-docs`: akkor használd, ha a felhasználó OpenAI termékek vagy API-k építéséről kér aktuális, hivatalos dokumentációval alátámasztott segítséget, vagy explicit GPT-5.4 upgrade / prompt-upgrade útmutatást kér. Elsődlegesen az OpenAI dokumentációs MCP eszközökre támaszkodj, fallback böngészésnél csak hivatalos OpenAI domaineket használj.
- `skill-creator`: akkor használd, ha a felhasználó új skillt szeretne létrehozni vagy meglévőt frissítene.
- `skill-installer`: akkor használd, ha a felhasználó telepíthető skillek listáját, egy curated skill telepítését, vagy más repositoryból való skill telepítést kér.

### A skillek használata

- Discovery: a fenti lista csak az aktuális sessionben elérhető skill neveket, rövid leírását és elérési útját foglalja össze.
- Trigger szabály: ha a felhasználó név szerint említ egy skillt, vagy a feladat egyértelműen megfelel valamelyik skill leírásának, azt a skillt kötelező használni az adott körben.
- Több említés esetén a minimálisan szükséges skillkészletet használd, és röviden jelezd a sorrendet.
- Ha egy megnevezett skill nem elérhető vagy az útvonala nem olvasható, ezt röviden jelezd, majd folytasd a legjobb fallback megoldással.

### Hogyan használj skillt

1. Nyisd meg a megfelelő `SKILL.md` fájlt.
2. Csak annyit olvass el belőle, amennyi a workflow követéséhez kell.
3. Ha relatív útvonalakat hivatkozik, először a skill könyvtárához viszonyítva oldd fel őket.
4. Ha külön `references/`, `scripts/`, `assets/` vagy template fájlok vannak, csak a konkrét feladathoz szükséges elemeket töltsd be.
5. Ha van használható script vagy template, inkább azt használd, mint hogy ugyanazt kézzel újraalkosd.

### Kontextuskezelés

- tartsd kicsiben a kontextust
- hosszú részeket inkább foglalj össze
- ne tölts be feleslegesen nagy mennyiségű referenciafájlt
- framework- vagy provider-variánsok közül csak a relevánsat válaszd

### Biztonság és fallback

Ha egy skill nem alkalmazható tisztán, hiányzik, vagy az instrukciói nem egyértelműek:

- röviden jelezd a problémát
- válaszd a következő legjobb megoldást
- folytasd a munkát
