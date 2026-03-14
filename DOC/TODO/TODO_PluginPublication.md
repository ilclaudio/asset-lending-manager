# TODO Plugin Publication (WordPress.org)

Data preparazione: 2026-03-14
Scopo: checklist operativa e modifiche necessarie per richiedere con successo la pubblicazione del plugin su WordPress.org.

## Riferimenti ufficiali da rispettare
1. Add Your Plugin: https://wordpress.org/plugins/developers/add
2. Plugin Developer FAQ: https://developer.wordpress.org/plugins/wordpress-org/plugin-developer-faq/
3. Detailed Plugin Guidelines: https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
4. Plugin Check: https://wordpress.org/plugins/plugin-check/
5. Security API (overview): https://developer.wordpress.org/apis/security/
6. Escaping Data: https://developer.wordpress.org/apis/security/escaping/
7. Sanitizing Data: https://developer.wordpress.org/apis/security/sanitizing/
8. Nonces: https://developer.wordpress.org/apis/security/nonces/

## Passi numerati per arrivare alla richiesta di pubblicazione
1. Congelare una release pubblicabile.
   Lavorare su una branch/release dedicata, evitando file temporanei o sperimentali.
2. Allineare la versione in tutti i punti.
   La versione deve essere coerente tra `asset-lending-manager.php`, `plugin-config.php`, `VERSION.txt`, `readme.txt` (Stable tag) e `CHANGELOG.md`.
3. Preparare metadati WordPress.org coerenti e completi.
   Nome plugin, slug, licenza GPL compatibile, campi readme standard, testo e branding non fuorvianti.
4. Verificare conformita alle Plugin Guidelines correnti.
   Le guidelines sono state aggiornate il 2026-03-13 e risultano 18 punti: vanno controllati uno per uno.
5. Mettere il plugin in sicurezza (security review finale).
   Validazione/sanitizzazione input, escaping output, nonce su azioni state-changing, capability checks, SQL con `$wpdb->prepare()`.
6. Eseguire Plugin Check e risolvere tutto cio che e bloccante.
   Correggere in priorita errori e warning di sicurezza/compliance.
7. Eseguire lint e test del progetto.
   Usare i comandi di progetto (`composer lint`, test disponibili) e risolvere i problemi prima dell'invio.
8. Preparare il pacchetto ZIP di submission pulito.
   Tenere il pacchetto sotto la soglia di upload (FAQ/Add page indicano limite 10MB) e includere solo file runtime/documentazione necessaria.
9. Fare QA funzionale su installazione pulita WordPress.
   Attivazione plugin, permessi, shortcode, AJAX/REST, i18n, flussi principali, fallback se dipendenze mancanti.
10. Inviare il plugin da Add Your Plugin.
   Compilare form, caricare zip, attendere review (indicativamente da 1 a 10 giorni secondo la pagina ufficiale), gestire eventuali richieste del team.
11. Dopo approvazione, pubblicare tramite SVN ufficiale WordPress.org.
   Creare trunk/tags, caricare codice e readme, poi verificare pagina plugin pubblica.

## Modifiche concrete consigliate per questo repository (priorita)
1. Uniformare la versione release.
   Stato attuale rilevato: `DEV-0.0.3` nel codice vs `Stable tag: 0.1.0` nel readme. Da rendere coerente prima dell'invio.
2. Ridurre i tag nel `readme.txt`.
   Le guidelines correnti indicano che superare 5 tag totali e considerato spam.
3. Aggiornare `Tested up to` nel readme alla versione WordPress stabile corrente al momento dell'invio.
   Stato attuale: `6.5`, probabilmente non aggiornato.
4. Creare una build di distribuzione senza file di sviluppo pesanti.
   In particolare escludere almeno: `.git/`, `vendor/`, `tests/`, `DOC/` interna non necessaria, file locali di tooling.
5. Aggiungere/controllare header plugin moderni.
   Verificare presenza e coerenza di `Requires at least`, `Requires PHP`, `Requires Plugins`, `Text Domain`, `License`.
6. Completare una security pass su tutte le entrypoint.
   Focus su AJAX, REST, form frontend/admin, query dinamiche, escaping HTML attributes/URLs.
7. Gestire in modo trasparente librerie terze incluse.
   Confermare attribuzione/licenze e, per file minificati di terze parti, mantenere riferimenti chiari a sorgente e licenza.
8. Rendere i default meno brand-specific per una distribuzione generica.
   Esempio: prefissi/costanti testuali legati ad AAGG come default (`ALM_ASSET_CODE_PREFIX`, sender name email), lasciando override semplice.
9. Ridurre output di debug lato frontend in release.
   Rimuovere o limitare `console.log` non indispensabili in produzione.
10. Verificare pagina readme e asset directory WordPress.org.
   Preparare screenshot/icon/banner per una scheda plugin completa e professionale.

## Definition of Done prima dell'invio
1. Plugin Check senza errori bloccanti.
2. Versione allineata ovunque e changelog aggiornato.
3. Pacchetto ZIP di submission entro limiti e senza file non necessari.
4. Readme validato e conforme (campi, tag, tested up to, stable tag).
5. Security checklist completata (sanitize, escape, nonces, capabilities).
6. Test manuale finale su WordPress pulito completato senza regressioni critiche.
