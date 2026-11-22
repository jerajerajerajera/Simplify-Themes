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
| TH-B3 | 22-11 | (Eerste CI-run.) De editor opent op het tabblad Colours in plaats van Themes, met en zonder kern | Beginwaarde `tab: 'colours'` in de Alpine-data was blijven staan | Eerste versie: `tab: 'themes'` | `tests/panel/browser.cjs`: "the editor opens on the Themes tab" en "a theme tile shows its preview across the tile" (rood op 09fd137: tegelbreedte 0, want het tabblad was verborgen) | 0× | P-31 |
| TH-B4 | 22-11 | (Testlab.) Na het uploaden van een lettertype of plaatje springt de editor naar een ander tabblad en meldt hij geen "Changes made", al is het nieuwe lettertype al gekozen; herladen zonder Save gooit de keuze stil weg | De bibliotheek (lettertypen, plaatjes) was een argument van `x-data`; na een upload veranderde dat attribuut en startte Alpine de editor opnieuw, met een nieuwe vergelijkingsbasis | Eerste versie: de bibliotheek staat in een JSON-blok dat de editor leest, `x-data` heeft alleen nog de kernvlag | `tests/panel/smoke.php` stap 1: "the editor's x-data holds more than the core flag" (rood op b3f85d7) | 0× | P-38 |
| TH-B5 | 22-11 | (Testlab.) In My theme hebben "Panel default" en "Simplify" hetzelfde zwart-witte vlakje | Alleen echte thema's kregen hun kleuren; de twee vaste keuzes vielen terug op een vast verloop | Eerste versie: Panel default toont de kleuren van het standaardthema, Simplify die van Simplify | `tests/panel/smoke.php`: "people can pick a theme only while the admin lets them" (regel over Panel default; rood op b3f85d7) | 0× | P-35 |

## Patronen

Bugs met dezelfde oorzaak, en de regel of check die het hele patroon voorkomt.
- **Pagina scrolt** (B9: kern 6×, Toolkit, Server Maker, Explain, Hosting): P-7. Elke instellingspagina van een add-on
  heeft `s-fit` en een smoke-stap die dat controleert.
- **Vrije CSS of een bestand van buiten** (nog niet gezien, voorkomen vanaf het begin): elke waarde van een thema gaat
  door `Theme::clean` (witte lijst); lettertypen en plaatjes worden op hun bytes herkend en plaatjes opnieuw getekend.
  Bewaakt door `tests/themes.php` en de smoke-stappen over uploads.
