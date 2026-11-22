<!-- GEMEENSCHAPPELIJK DEEL: in alle 6 Simplify-repo's gelijk. De regels staan in docs/regels.md, niet hier (V40). -->
# Werkwijze Simplify

Simplify is één product: de kern `simplify` plus de add-ons `simplify-servermaker`, `-toolkit`, `-eggupdates`,
`-explain`, `-hosting` en `-themes`. De splitsing is alleen verpakking: elke add-on volgt dezelfde UI en dezelfde regels.
Schrijf met de eigenaar in het Nederlands; code, README, CHANGELOG, commits en PR's in het Engels.

## Lees eerst

De regels staan niet in dit bestand. Lees bij de start, in deze volgorde:

1. `docs/regels.md`: werkregels (WR) en productregels (P). De kernrepo is de bron; elke add-on heeft een identieke kopie.
2. `docs/besluiten.md`: zoek hier eerst voor je zelf iets kiest (kopie van `plannen/besluiten.md` in project-files).
3. `docs/bugs.md`: bekende bugs en hun tests. Raak je die code, draai die tests.
4. `docs/prompts/startbrieven.md`: het algemene deel en de brief van je rol.

Bij verschil tussen dit bestand, de chat en `docs/regels.md` geldt `docs/regels.md`.

## Testen

- `bash tests/run.sh` (draait ook `tests/hub-check.py` en `tests/lang.php`), `pint --test` en in CI `checks.yml`
  met panel-smoke op elke Pelican-versie in de matrix. Wat "klaar" is, staat in P-31.
- Geen `composer install` of `yarn install` in de cloudcontainer; het echte paneel draait op GitHub Actions.
- `tests/docs-sync.sh` (in CI) controleert dat `docs/regels.md`, `docs/besluiten.md` en `docs/prompts/` gelijk
  zijn aan de kern op `main`.

## Deze repo: Themes (`simplify-themes`)

- Een root admin maakt eigen thema's (kleuren licht/donker, achtergrond met eigen plaatje, lettertypen incl. upload,
  vormen en consolekleuren) met een live voorbeeld; één thema is de standaard, en met "Let people pick" kiest iedereen
  zelf onder My theme. Ontwerp: `plannen/rapporten/simplify-themes/2026-11-22-ontwerpvoorstel.md` (V64).
- Werkt zonder kern (Pelican-look: Filament-kleuren en lettertypen); vormen, ruimte, schaduwen, doorzichtige kaarten
  en consolekleuren alleen met kern (de `--ps-*`-tokens).
- Veiligheid: nooit vrije CSS; elke waarde door `Theme::clean`. Lettertypen alleen WOFF2/TTF, herkend op hun bytes;
  plaatjes opnieuw getekend als WebP. Bestanden op de privé-schijf onder een willekeurige naam, alleen via de route
  met vast patroon. Geen lettertype of bestand van een andere site (alles meegeleverd, OFL in `resources/fonts`).
- Unit-tests zonder Laravel: `php tests/themes.php` (in `tests/run.sh`). In CI: `tests/panel/smoke.php` en op rc.4
  `tests/panel/browser.cjs` (screenshots als artifact `screens-*`).
- Doorklikken: admin › Simplify › Themes, elk tabblad, een wijziging + Save, een lettertype en een plaatje uploaden,
  Make default for everyone, en My theme als gewone gebruiker. Met én zonder kern.
