=== Asset Lending Manager ===
Contributors: ioclaudio
Author URI: https://www.claudiobattaglino.it/
Author: IoClaudio
Tags: gestione risorse, prestiti, biblioteca, attrezzature, organizzazione
Requires at least: 6.2
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.2.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin open source per gestire risorse fisiche condivise e flussi di prestito per associazioni, scuole, biblioteche e organizzazioni.

== Description ==
Asset Lending Manager è un plugin WordPress open source che aiuta una organizzazione a gestire risorse fisiche condivise e flussi interni di prestito.

È pensato per club, associazioni, scuole, enti pubblici, biblioteche, laboratori, makerspace e per qualunque gruppo che presta attrezzature o materiali ai propri membri.

I membri possono consultare le risorse disponibili e inviare richieste di prestito, mentre operatori e amministratori possono gestire assegnazioni e cronologia dei prestiti.

Nato all'interno di un'associazione di astrofili per gestire telescopi e attrezzature, è pubblicato come strumento generico utilizzabile da qualsiasi organizzazione.

**Richiede il plugin Advanced Custom Fields (ACF)**, nella versione gratuita, per salvare e gestire i dettagli delle risorse.


== Features ==
* Gestione di risorse e kit (un kit è un gruppo di elementi prestati insieme come insieme unico)
* Pagina pubblica di consultazione con ricerca e filtri per tassonomia
* Generazione di codici QR ed etichette stampabili dalla pagina di dettaglio della risorsa
* Scanner QR dall'elenco risorse per la ricerca rapida tramite fotocamera
* Flusso di richiesta prestito: i membri inviano richieste, il proprietario corrente della risorsa le approva o le rifiuta
* Assegnazione diretta da parte di operatori e amministratori (quando abilitata; il motivo è sempre obbligatorio)
* Per impostazione predefinita, quando una risorsa viene assegnata, tutte le altre richieste in sospeso per la stessa risorsa vengono annullate automaticamente (configurabile)
* Notifiche email per tutti gli eventi del flusso di prestito (richiesta, approvazione, rifiuto, annullamento, assegnazione diretta, rientro forzato), quando le notifiche sono abilitate
* Gestione dello stato delle risorse dal frontend: gli operatori possono impostare le risorse in manutenzione o ritirate, oppure riportare direttamente a disponibile una risorsa in prestito; a ogni cambio di stato è richiesta una posizione
* Cronologia completa dei prestiti per ogni risorsa
* Due ruoli utente inclusi: Membro (può consultare le risorse e richiedere prestiti) e Operatore (può gestire assegnazioni, stati e cronologia)
* API REST JSON in sola lettura su `/wp-json/almgr/v1/` per elenco risorse, dettaglio risorsa, elenco membri e risorse dei membri; autenticazione tramite WordPress core (sessione cookie, nonce REST, password applicative)
* Pagina Strumenti nel back-office (ALM → Strumenti) con schede Importa, Esporta e Utilità
* Importazione CSV utenti dalla pagina Strumenti (solo amministratori)
* Esportazione CSV utenti dalla pagina Strumenti (amministratori e operatori)
* Importazione CSV risorse dalla pagina Strumenti (amministratori e operatori)
* Esportazione CSV risorse dalla pagina Strumenti (amministratori e operatori)
* Pronto per la traduzione


== Requirements ==
Questo plugin richiede **Advanced Custom Fields** (è sufficiente la versione gratuita).
Puoi installarlo gratuitamente dalla directory dei plugin WordPress: https://wordpress.org/plugins/advanced-custom-fields/

ACF viene usato per salvare e recuperare tutti i campi personalizzati delle risorse (produttore, modello, posizione, numero di serie, ecc.).
Il plugin registra automaticamente il gruppo di campi ACF: non è necessaria alcuna configurazione manuale dei campi ACF.


== Loan Workflow ==
* Un membro consulta le risorse disponibili.
* Viene inviata una richiesta di prestito per una risorsa selezionata.
* Le email di notifica vengono inviate al richiedente e, quando applicabile, al proprietario corrente.
* Il proprietario corrente può approvare o rifiutare la richiesta.
* In caso di approvazione, la risorsa viene contrassegnata come in prestito e viene registrato il nuovo assegnatario.
* Operatori e amministratori possono anche assegnare direttamente qualsiasi risorsa che non sia ritirata o in manutenzione, senza una richiesta precedente (quando l'assegnazione diretta è abilitata).
* Tutte le decisioni e le assegnazioni vengono registrate nella cronologia dei prestiti.


== Installation ==
1. Installa e attiva il plugin **Advanced Custom Fields** (ACF); la versione gratuita è sufficiente: https://wordpress.org/plugins/advanced-custom-fields/
   Il plugin registra automaticamente il proprio gruppo di campi ACF. Non è richiesta alcuna configurazione manuale di ACF.
2. Nell'amministrazione di WordPress vai in Plugin > Aggiungi nuovo > Carica plugin.
3. Carica il file ZIP del plugin, fai clic su Installa ora, quindi su Attiva.
**Temi classici:**
4. Le pagine delle risorse vengono servite automaticamente, senza shortcode:
   * `/asset/` — catalogo risorse con ricerca e filtri
   * `/asset/nome-risorsa/` — pagina di dettaglio della singola risorsa
5. Se `/asset/` restituisce errore 404, vai in Impostazioni > Permalink e fai clic una volta su Salva modifiche.

**Temi a blocchi:**
4. I temi a blocchi non supportano gli override automatici dei template PHP. Crea manualmente due pagine:
   * Aggiungi `[almgr_asset_list]` a una pagina: sarà il catalogo delle risorse.
   * Aggiungi `[almgr_asset_view]` a una seconda pagina: sarà la vista di dettaglio della risorsa.
5. In **ALM > Impostazioni > Frontend**, imposta "Pagina archivio risorse" e "Pagina dettaglio risorsa" sulle pagine appena create. In questo modo tutti i link alle risorse punteranno alla pagina di dettaglio corretta.
6. Facoltativamente, configura le impostazioni del mittente email in wp-admin da **ALM > Impostazioni**.


== Frequently Asked Questions ==

= A chi è destinato questo plugin? =
A qualsiasi organizzazione che gestisce un insieme condiviso di oggetti fisici: associazioni, scuole, enti pubblici, biblioteche, laboratori, makerspace, società sportive e molto altro.
Il plugin è nato per un'associazione di astrofili, ma è progettato per essere generico e adatto a qualsiasi contesto.


= Questo plugin richiede Advanced Custom Fields? =
Sì. ACF (versione gratuita) è necessario per salvare e recuperare i campi personalizzati delle risorse. Se ACF non è attivo, il plugin mostra un avviso nell'area di amministrazione.

= Il plugin gestisce la consegna fisica delle risorse? =
No. Consegna e passaggio materiale delle risorse avvengono offline. Il plugin tiene traccia solo delle richieste e delle assegnazioni.

= Esiste una pagina impostazioni in wp-admin? =
Sì. Nel menu **ALM** di wp-admin puoi configurare il mittente email, le regole dei prestiti (numero massimo di prestiti attivi per membro, limiti di lunghezza dei messaggi) e altre opzioni del flusso di lavoro.

= Il plugin è pronto per la traduzione? =
Sì. Inglese e italiano sono inclusi. Altre lingue possono essere aggiunte tramite gli strumenti standard di traduzione di WordPress.

= Quali dati vengono rimossi quando il plugin viene disinstallato? =
La disinstallazione del plugin rimuove le impostazioni del plugin, la cronologia delle richieste di prestito, le richieste di prestito in sospeso e i ruoli utente personalizzati.
Per impostazione predefinita, l'inventario delle risorse (post e relativi dati) viene mantenuto.
Se vuoi rimuovere tutti i dati del plugin, definisci `ALMGR_REMOVE_ALL_DATA` come `true` in `wp-config.php` prima della disinstallazione.

= Qual è la differenza tra una risorsa e un kit? =
Una risorsa è un singolo oggetto fisico (per esempio un telescopio, un libro o una fotocamera). Un kit è una raccolta di elementi prestati insieme come gruppo (per esempio un telescopio con oculari e custodia). La gestione dei kit consente di tracciare tutti i componenti sotto un'unica richiesta di prestito.

= Più membri possono richiedere la stessa risorsa contemporaneamente? =
Sì. Più membri possono inviare richieste per la stessa risorsa nello stesso momento. Per impostazione predefinita, quando una richiesta viene approvata o la risorsa viene assegnata direttamente, tutte le altre richieste in sospeso per quella risorsa vengono annullate automaticamente (comportamento configurabile) e i richiedenti ricevono una notifica email se le notifiche sono abilitate.

= Serve uno sviluppatore per configurare questo plugin? =
Con i temi classici, la configurazione di base richiede solo l'installazione del plugin e l'attivazione di ACF: le pagine delle risorse vengono servite automaticamente, senza shortcode. Con i temi a blocchi, occorre creare manualmente due pagine con shortcode e configurarle nelle impostazioni del plugin. Alcune personalizzazioni avanzate, come modifiche ai ruoli utente, possono beneficiare del supporto di uno sviluppatore.


== Changelog ==

Per le note di rilascio complete consulta `CHANGELOG.md`.

= 0.2.3 =


= 0.2.2 =
* Sicurezza: migrazione dell'API REST alle route native della REST API di WordPress; rimossa la logica di autenticazione personalizzata (`wp_authenticate()`).
* Sicurezza: escape dell'output di `do_blocks()` con `wp_kses_post()` nei template di fallback.
* Corretto: gli operatori possono caricare/inserire immagini e modificare titolo/testo alternativo delle immagini dalla Libreria Media e dal flusso dell'immagine in evidenza.
* Modificato: rilascio di manutenzione successivo all'invio su WordPress.org.

= 0.2.1 =
* Modificato: refactoring interno; tutti gli identificatori del plugin sono stati migrati dal prefisso `alm_` al prefisso `almgr_` per maggiore sicurezza di namespace.
* Modificato: tutte le chiavi di salvataggio dei campi personalizzati ACF ora usano il prefisso `almgr_` per conformità al namespace richiesto da WordPress.org.
* Aggiunto: pagina Strumenti nel back-office (ALM → Strumenti) con schede Importa, Esporta e Utilità.
* Aggiunto: importazione CSV utenti (solo amministratori) ed esportazione CSV utenti (amministratori e operatori) negli Strumenti.
* Aggiunto: importazione CSV risorse (amministratori e operatori) ed esportazione CSV risorse (amministratori e operatori) negli Strumenti.
* Aggiunto: importazione ed esportazione dei kit: i componenti del kit e i relativi campi ACF sono inclusi nel CSV delle risorse.
* Aggiunto: impostazione della policy di notifica per controllare se/quando tutti gli operatori vengono notificati per una nuova richiesta di prestito (`never`, `no owner`, `always`).
* Aggiunto: costante `ALMGR_REMOVE_ALL_DATA`; definiscila come `true` in `wp-config.php` prima della disinstallazione per rimuovere tutti i dati del plugin, incluse le risorse.
* Corretto: gli operatori possono approvare/rifiutare richieste per risorse senza proprietario corrente.
* Sicurezza: correzioni e rafforzamenti derivanti da audit del codice.

= 0.1.1 =
* Aggiunto: API REST JSON in sola lettura su `/wp-json/almgr/v1/` (elenco risorse, dettaglio risorsa, elenco membri, risorse dei membri). Autenticazione tramite WordPress core (sessione cookie, nonce REST, password applicative).
* Aggiunto: scheda impostazioni API REST in wp-admin (solo amministratori) con toggle di abilitazione/disabilitazione, riferimento degli endpoint e guida all'autenticazione.
* Sicurezza: aggiunti controlli sullo stato della risorsa a tutti gli endpoint AJAX.

= 0.1.0 =
* Primo rilascio pubblico.
* Gestione di risorse e kit con flusso completo di prestito (richiesta, approvazione, rifiuto, assegnazione diretta).
* Controllo accessi basato su ruoli (`almgr_member`, `almgr_operator`).
* Notifiche email per tutti gli eventi del flusso di prestito.
* Tracciamento della cronologia dei prestiti, incluse voci per singolo componente nelle operazioni sui kit.
* Consultazione frontend delle risorse con filtri, generazione di codici QR e scanner QR.
* Gestione dello stato delle risorse (disponibile, in prestito, in manutenzione, ritirata) con propagazione sui kit; gli operatori possono riportare forzatamente a disponibile una risorsa in prestito dal frontend, chiudendo il prestito attivo e notificando il beneficiario.
* Campo posizione obbligatorio a ogni cambio di stato; propagato ai componenti del kit.
* Pronto per la traduzione, con inglese e italiano inclusi.
* Pagina impostazioni in wp-admin.


== Credits ==

Questo plugin include le seguenti librerie JavaScript di terze parti:

* **qrcode-generator** di Kazuhiko Arase (http://www.d-project.com/), licenza MIT
* **jsQR** di cozmo (https://github.com/cozmo/jsQR), licenza Apache License 2.0

Entrambe le licenze sono compatibili con GPLv2 o successiva. I file di licenza sono inclusi in `assets/js/vendor/`.


== Upgrade Notice ==

= 0.2.3 =

= 0.2.2 =
Rilascio di sicurezza e correzione. L'API REST è stata migrata alle route native della REST API di WordPress e l'escape dell'output dei template di fallback è stato rafforzato. Nessuna modifica al database; non è richiesto alcun intervento manuale.

= 0.2.1 =
Rilascio di refactoring interno. Tutte le tabelle database del plugin, le opzioni, gli identificatori e le chiavi di salvataggio dei campi ACF sono stati rinominati dalla forma `alm_` / senza prefisso al prefisso `almgr_`.

= 0.1.0 =
Primo rilascio pubblico.
