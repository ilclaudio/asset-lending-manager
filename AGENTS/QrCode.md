# QRCode.md

Documento di progetto per la funzionalità QR code in Asset Lending Manager.
Last update: 2026-03-10

---

## Obiettivo

Dotare ogni asset (kit o componente) di un QR code stabile da stampare su etichetta fisica.
Il QR identifica l'asset e permette tre operazioni: stampa etichetta, ricerca rapida, assegnamento diretto.

---

## Decisioni Architetturali

### Contenuto del QR

Ogni QR codifica un URL nel formato:
```
https://sito-aagg.it/?alm_scan=AAGG-00000052
```

Il codice `AAGG-00000052` è generato da `ALM_Asset_Manager::get_asset_code( $asset_id )` e
deriva dal WordPress post ID — non cambia mai, nemmeno rinominando l'asset.

L'URL `?alm_scan=` è un redirect gestito dal plugin: oggi porta al dettaglio asset, in futuro
potrà puntare alla pagina scanner senza dover ristampare le etichette.

### Come funziona il redirect `alm_scan`

Il plugin aggancia `template_redirect` (hook WordPress che si esegue prima di qualsiasi output):
legge `$_GET['alm_scan']`, estrae il post ID dal codice, fa `wp_safe_redirect()` al permalink
dell'asset. Se il codice non è valido, redirect alla home. Nessuna query var custom necessaria.

### Librerie

- **Generazione QR** — `qrcodejs` (davidshimjs, MIT, ~50 KB): JS puro, zero dipendenze, funziona offline.
- **Lettura QR da fotocamera** — `jsQR` (MIT, ~25 KB): necessaria solo per Fase 2.
- Entrambe vengono scaricate in `assets/js/vendor/` e enqueued tramite `wp_enqueue_script()`.
- **HTTPS obbligatorio** per la lettura da fotocamera (API `getUserMedia` del browser).

---

## Tre Funzionalità da Implementare

### F1 — Visualizzazione e Stampa QR (Fase 1)

Nel dettaglio di ogni asset: QR code visibile con pulsante **"Stampa QR"**.
Clic sul pulsante → `window.print()` → CSS `@media print` mostra solo la card etichetta
(QR + titolo asset + codice `AAGG-00000052`), nasconde il resto della pagina.

### F2 — Ricerca Tramite QR (Fase 2a)

Nella pagina lista asset: pulsante **"Scansiona QR"** che apre un overlay con la fotocamera.
`jsQR` legge il QR in tempo reale → il browser naviga all'URL decodificato → redirect al dettaglio.
Il campo di ricerca testuale rimane disponibile come alternativa.

### F3 — Assegnamento Diretto Tramite QR (Fase 2b)

Pagina scanner dedicata (`[alm_scanner]`, solo operatori):
1. Scansiona uno o più QR code → lista asset accumulata in memoria JS.
2. Seleziona l'utente destinatario (autocomplete esistente).
3. Conferma → AJAX verso `alm_direct_assign_asset` per ogni asset.
4. Riepilogo esito: successi, saltati (maintenance/retired), errori.

Asset in `maintenance` o `retired` vengono segnalati e saltati senza bloccare gli altri.
L'endpoint `alm_direct_assign_asset` è già transazionale: riusarlo in loop è sicuro.

---

## Piano di Implementazione

### Fase 1 — Visualizzazione e Stampa QR

| Step | Cosa fare | File |
|---|---|---|
| 1 | Scaricare `qrcode.min.js` in `assets/js/vendor/` | nuovo file |
| 2 | Enqueue libreria + localizzare `assetScanUrl` e `assetCode` in `almFrontend` | `class-alm-frontend-manager.php` |
| 3 | Aggiungere sezione QR nel template (dopo metadati, prima delle sezioni operative) | `asset-view.php` |
| 4 | Aggiungere `initQrCode()` in `frontend-assets.js`: genera QR, gestisce pulsante stampa | `frontend-assets.js` |
| 5 | Stili sezione QR + `@media print` per card etichetta (50×30 mm) | `frontend-assets.css` |
| 6 | Aggiungere hook `template_redirect` per redirect `alm_scan` | `class-alm-frontend-manager.php` |
| 7 | Traduzioni nuove stringhe | `.pot`, `it_IT.po` |

### Fase 2a — Ricerca Tramite QR

| Step | Cosa fare | File |
|---|---|---|
| 1 | Scaricare `jsqr.min.js` in `assets/js/vendor/` | nuovo file |
| 2 | Enqueue `jsQR` sulle pagine lista asset | `class-alm-frontend-manager.php` |
| 3 | Aggiungere pulsante "Scansiona QR" nella barra di ricerca | `asset-list.php` |
| 4 | Aggiungere `initQrScanner()` in `frontend-assets.js`: overlay fotocamera, decodifica, redirect | `frontend-assets.js` |
| 5 | Stili overlay scanner | `frontend-assets.css` |

### Fase 2b — Assegnamento Diretto Tramite QR

| Step | Cosa fare | File |
|---|---|---|
| 1 | Creare template pagina scanner | `templates/shortcodes/scanner.php` (nuovo) |
| 2 | Registrare shortcode `[alm_scanner]` (solo operatori) | `class-alm-frontend-manager.php` |
| 3 | JS pagina scanner: accumulo lista asset, autocomplete utente, conferma batch | `assets/js/scanner.js` (nuovo) |
| 4 | Stili pagina scanner | `assets/css/scanner.css` (nuovo) |
| 5 | Traduzioni | `.pot`, `it_IT.po` |

---

## Sicurezza

Il QR è solo un identificativo. Ogni operazione innescata dalla scansione passa le stesse
validazioni del flusso normale: nonce (`check_ajax_referer`), capability (`current_user_can`),
stato asset, regole di transizione, storico.
