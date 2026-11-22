# Bugregister Simplify Themes

> Elke gefikste bug staat hier, met de test die hem bewaakt (WR-11). Een terugkerende bug is een regressie: zoek
> eerst uit waarom de test hem niet ving. Een rij weghalen mag niet, de test weghalen ook niet. Nieuwe nummers geeft de
> repo-thread zelf met het prefix van deze repo (WR-8): `TH-B<n>`. De add-on is nieuw (22-11); B9 staat er vanaf
> het begin in omdat het patroon bij elke andere add-on terugkwam.

| Nr | Datum | Wat zag de gebruiker | Oorzaak | Fix (versie) | Regressietest | Teruggekomen | Regel |
|---|---|---|---|---|---|---|---|
| B9 | 22-11 | (Patroon van andere add-ons.) Instellingspagina scrolt als geheel met Simplify | Formulier zonder `s-fit` | Eerste versie | `tests/panel/smoke.php` stap "the Themes page renders for a root admin…" (controleert `s-fit`) en `tests/panel/browser.cjs` "the page never scrolls (P-7)" | 0× | P-7 |
| TH-B1 | 22-11 | (Eerste CI-screenshots.) De themategels tonen hun mini-voorbeeld als smalle strook links in plaats van over de hele tegel, met en zonder kern | De kinderen van de knop rekten niet mee: de breedte van `.st-mini` en `.st-tile-info` volgde hun inhoud | Eerste versie: tegel `align-items: stretch`, kinderen `width: 100%` | `tests/panel/browser.cjs`: "a theme tile shows its preview across the tile" (rood te zien op de screenshots van 492c701) | 0× | P-31 |
| TH-B2 | 22-11 | (Eerste CI-screenshots.) Op een telefoon staat het live voorbeeld half over de editor terwijl het dicht hoort te zijn, met en zonder kern | Het blad is `position: fixed` met `translateY(105%)`; binnen een voorouder met transform of eigen scrollvak valt het dan niet buiten beeld | Eerste versie: dicht blad ook `visibility: hidden` | `tests/panel/browser.cjs`: "the preview sheet is hidden until opened" (rood te zien op de screenshots van 492c701) | 0× | P-9 |

## Patronen

Bugs met dezelfde oorzaak, en de regel of check die het hele patroon voorkomt.
- **Pagina scrolt** (B9: kern 6×, Toolkit, Server Maker, Explain, Hosting): P-7. Elke instellingspagina van een add-on
  heeft `s-fit` en een smoke-stap die dat controleert.
- **Vrije CSS of een bestand van buiten** (nog niet gezien, voorkomen vanaf het begin): elke waarde van een thema gaat
  door `Theme::clean` (witte lijst); lettertypen en plaatjes worden op hun bytes herkend en plaatjes opnieuw getekend.
  Bewaakt door `tests/themes.php` en de smoke-stappen over uploads.
