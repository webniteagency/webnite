# Webnite V2 — kontekst za nadaljevanje (handoff)

Stanje: junij 2026, **lokalno, NEOBJAVLJENO**. Ta mapa je celotna spletna stran webnite.si (vse datoteke, pripravljene za cPanel/Neoserv `public_html/`).

## Kako zagnati predogled
Iz te mape (`WEBNITE V2/`):
```
python -m http.server 8766
```
Nato odpri `http://localhost:8766/index.html#sl/home`.

## Arhitektura
- **Vanilla HTML + inline CSS/JS. NI React/Next.js.** `index.html` je single-file SPA z lastnim hash routerjem (`#sl/home`, `#en/video` …).
- **Blog je ločen**: `clanki/` (SLO) + `en/tips/` (EN) — vsak članek svoja HTML datoteka (pravi URL-ji za SEO). Uporabljajo `assets/webnite-common.css` (skupna glava/noga).
- Slike: `images/` (blog, reference, hero asseti). Mail handler: `posli.php`. `sitemap.xml`, `robots.txt`, favikone.

## ZAŠČITENA OBMOČJA (ne spreminjaj brez razloga)
- Google Ads: `AW-18206719745` (gtag.js + 2× config) in konverzija `AW-18206719745/l_7xCJ3m7bccEIH-0elD`.
- Consent Mode v2 logika + cookie banner.
- **10× `class="contact-form"`** (8 osnovnih + 2 na Video strani) → `/posli.php`. `data-key="video"` ×4. Prednapolnitev obrazca prek `data-pkg-vrsta`/`data-pkg-budget` + mapa `BUDGETS` v JS.
- Obstoječi hash URL-ji (že indeksirani).

## Kaj je narejeno v tej seji (V1 → V2)
1. **Glava** → lebdeča Apple-pill (sticky, prozoren ovoj, blur, `border-radius:9999px`), identična na vseh straneh (`index.html` inline + `assets/webnite-common.css`).
2. **Nasveti → Članki**: stara mapa `nasveti/` izbrisana; vse na `clanki/`.
3. **Mreže kartic**: à la carte 8 → 4+4; proces "Kako poteka" 5 → polni vrsti (`.svc-grid-fill`); domov 4 storitve → 4 v vrsto. Vse `.svc-grid` poenoteno.
4. **Video stran (`#sl/video` / `#en/video`)** — prenovljen cenik: à la carte ločen po namenu (AI video oglas=CTA/konverzije vs Reels=organic), paketi Start/Kreativa/Premium + **preklopnik Mesečno/Letno** (−16%), "Prvi mesec −50 %", gratis logo animacija, nov **full-funnel blok 999 €/mes** + **Brand kit 199 €**. Sekciji **Zakaj** (kartice) in **FAQ** (akordeon) prenovljeni. DDV: "Vse cene so v EUR in ne vključujejo DDV."
5. **Blog Članki**: prave 4K fotografije kot ozadja kartic (`images/clanki/*.jpg`, +`-1280` za srcset) s temnim scrimom; stebrni članek čez 2 stolpca, ostale kartice enako visoke (line-clamp); kotne ikone in oznaka "STEBRNI ČLANEK" odstranjene. **Hero v vsakem članku** = ista slika kot kartica (object-fit cover, naslov čez sliko, višina 500px). **Realni časi branja** (2–3 min). Jezikovni pregled (SLO "ti" + "proračun"; EN polish).
6. **WBN demo reference** v sekciji **"Izbor del"** (Video stran): 6 AI-izdelanih produktnih vizualov (`images/reference/wbn/*.jpg`), demo razkritje + značka "Ustvarjeno z AI · demo", lightbox (Esc/klik izven/X), CTA.
7. **Nova domača hero sekcija** (`#sl/home` + `#en/home`): ozadje s padalcem + veil, rotirajoči naslov (4 sporočila), glass gumb "Odkrij naše delo", drsni trak strank. Scopano pod `.hero-v2`. Asseti: `images/paraglider-bg.jpg`, `images/clients/*.png`. Pisave Space Grotesk/Space Mono dodane. Globalna pill-glava ohranjena. "Odkrij naše delo" → `#sl/video`.
8. **Stran Storitve (`#sl/services`)**: vse 4 storitve v kompaktni mreži 4 kartic (vse vidne brez skrolanja); 4→2→1 stolpci.

## Odprte / možne naslednje naloge
- **EN "Tips" (`en/tips/`)** še NIMA fotografij ne hero slik (gradienti, stara postavitev). Za polno SLO↔EN paritet: iste slike v EN kartice + featured-merge + hero slike v EN člankih.
- `images/webnite-logo.png` izvlečen iz hero datoteke, a neuporabljen (obdržali smo globalno glavo).
- CTA-slika za blok "Imate konkretno vprašanje?" (placeholder).
- Pred deployem: paziti, da datoteke nimajo `[1]` v imenu (cPanel doda, če Overwrite ni vklopljen).

## Podjetje / kontakt
Domena webnite.si (Neoserv) · info@webnite.si · Automotion d.o.o., Studenec 20, 1295 Ivančna Gorica · DŠ 63395649 (NE-zavezanec za DDV) · Ton: prijateljski "ti", brez napihovanja, transparentne cene.
