# TESTS_INTRODUCTION.md

## Obiettivo
Definire una strategia di test pratica per tenere sotto controllo un sistema con flussi multipli:
- richiesta prestito;
- approvazione/rifiuto;
- assegnazione diretta;
- cambio stato asset.

---

## Cosa si intende per test di integrazione in questo progetto

Un **test unitario** verifica una singola funzione in isolamento, sostituendo tutte le dipendenze esterne con oggetti finti (mock). È veloce ma non garantisce che il sistema funzioni davvero.

Un **test di integrazione** verifica che più componenti collaborino correttamente. In questo plugin significa:

1. WordPress viene avviato normalmente (le funzioni `wp_*`, `get_post_meta`, ecc. funzionano davvero).
2. Il database di test viene creato e le tabelle custom (`alm_loan_requests`, `alm_loan_requests_history`) vengono installate.
3. Il test crea utenti reali (con ruolo `alm_member`, `alm_operator`), asset reali (post `alm_asset` con tassonomie e meta), richieste di prestito reali.
4. Il test chiama il metodo AJAX come farebbe il browser: imposta `$_POST`, imposta l'utente corrente con `wp_set_current_user()`, chiama il metodo del plugin, legge il DB e verifica il risultato.
5. Alla fine ogni test ripulisce il DB (WordPress lo fa automaticamente con `WP_UnitTestCase`).

In pratica: **non si mocka nulla, si esegue il flusso reale contro un DB reale**. Questo è il livello di copertura più utile per un plugin come ALM, dove il rischio principale è che la logica di business interagisca male con meta, tassonomie e transazioni DB.

**Esempio concreto (T8 — guard conflitti kit):**
```
// Preparo l'ambiente
$operator  = $this->factory->create_operator();
$kit       = $this->factory->create_kit_with_components( ['stato_componente' => 'maintenance'] );
$request   = $this->factory->create_loan_request( $kit->ID );

// Chiamo il flusso di approvazione come farebbe il browser
$result = $this->loan_manager->approve_loan_request( $request->ID, $operator );

// Verifico che l'operazione sia fallita e il DB non sia stato modificato
$this->assertWPError( $result );
$this->assertEquals( 'available', $this->loan_manager->get_asset_state_slug( $kit->ID ) );
$this->assertEquals( 0, $this->get_history_count_for( $kit->ID, 'approved' ) );
```

---

## Stack tecnologico scelto

| Livello | Framework | Quando usarlo |
|---|---|---|
| Integrazione | PHPUnit + WP_UnitTestCase (stack ufficiale WP) | Flussi AJAX/DB, flussi principali |
| Unit | Brain Monkey (mocka le funzioni WP) | Logica pura estratta dalle classi |
| E2E | Playwright | Solo 5-6 percorsi critici, browser reale |

La suite di integrazione è la priorità assoluta. Le altre due si aggiungono in modo incrementale.

---

## Considerazioni chiave
1. Trattare il dominio come macchina a stati: ogni passaggio deve essere esplicito, consentito o bloccato.
2. In codice WordPress "innestato", partire dai test di integrazione sui punti di ingresso reali (AJAX/REST + DB/meta).
3. Introdurre unit test in modo incrementale, estraendo gradualmente le regole pure dal codice dipendente da WordPress.
4. Coprire con test end-to-end solo i percorsi critici, non tutti i dettagli.
5. Verificare sempre sicurezza applicativa: nonce, capability checks, sanitizzazione input, autorizzazioni ruolo.
6. Ogni bug corretto deve aggiungere almeno un test di regressione.
7. Eseguire i test in CI: suite veloce su PR, suite completa su schedule giornaliera/notturna.

---

## Introduzione graduale degli unit test (senza big refactor)
1. Prima copertura integrazione: `ajax_submit_loan_request`, `ajax_approve_loan_request`, `ajax_reject_loan_request`, `ajax_direct_assign_asset`, `ajax_change_asset_state`.
2. Poi estrazione progressiva della logica pura (transizioni, policy permessi, validazioni input) in metodi piccoli e testabili.
3. Isolamento delle dipendenze WordPress/globali tramite wrapper minimi, così i test unitari non richiedono bootstrap completo.
4. Niente riscritture massive: micro-step guidati dai test.

---

## Micro-rifattorizzazioni consigliate (proposte, non applicate)
1. Estrarre una funzione di transizioni: `is_transition_allowed($from, $event, $to)`.
2. Estrarre policy permessi: `can_approve()`, `can_reject()`, `can_direct_assign()`.
3. Estrarre parsing/validazione payload AJAX: metodi `parse_*_payload()`.
4. Isolare la persistenza in metodi dedicati (`create_loan_request`, `append_history`, `cancel_concurrent_requests`).
5. Introdurre codici errore di dominio stabili (`ERR_*`) per assert robusti.

---

## Tabella di controllo test (flussi implementati oggi)
| ID | Flusso | Transizione/Regola | Precondizioni minime | Attore | Esito atteso | Livello test |
|---|---|---|---|---|---|---|
| T1 | Richiesta prestito | `available -> pending_request` | Asset valido e loanable, nonce valido | `alm_member` | Riga `pending` in `alm_loan_requests` | Integrazione |
| T2 | Richiesta prestito | Richiesta su asset `on-loan` | Owner corrente presente | `alm_member` | Richiesta accettata (se policy lo consente) | Integrazione |
| T3 | Richiesta prestito | Blocco duplicato stesso asset | Esiste richiesta `pending` stesso utente/asset | `alm_member` | Errore "pending già presente" | Integrazione |
| T4 | Richiesta prestito | Blocco su asset non loanable | Stato `maintenance` o `retired` | `alm_member` | Errore e nessuna scrittura DB | Integrazione |
| T5 | Approvazione | `pending_request -> approved` | Richiesta pendente valida | Owner o Operatore | Owner aggiornato, stato `on-loan`, storico `approved` | Integrazione |
| T6 | Approvazione | Cancellazione richieste concorrenti | Più richieste `pending` stesso asset | Owner o Operatore | Richieste concorrenti cancellate + storico `canceled` | Integrazione |
| T7 | Approvazione kit | Propagazione a componenti | Asset kit con componenti assegnabili | Owner o Operatore | Owner/stato propagati ai componenti | Integrazione |
| T8 | Approvazione kit | Guard conflitti componenti | Componente in `maintenance`/`retired` o `on-loan` con owner diverso | Owner o Operatore | Operazione fallita e rollback | Integrazione |
| T9 | Rifiuto | `pending_request -> rejected` | Richiesta pendente valida, messaggio valido | Owner o Operatore | Riga richiesta rimossa + storico `rejected` | Integrazione |
| T10 | Assegnazione diretta | `* -> direct_assign` | Feature abilitata, assignee valido, nonce valido | `alm_operator` | Owner aggiornato, stato `on-loan`, storico `direct_assign` | Integrazione |
| T11 | Assegnazione diretta | Blocco su asset `retired` | Asset retired | `alm_operator` | Errore e rollback | Integrazione |
| T12 | Cambio stato | `available/on-loan -> maintenance` | Operatore, target valido | `alm_operator` | Stato aggiornato, owner azzerato, storico `to_maintenance` | Integrazione |
| T13 | Cambio stato | `available/on-loan -> retired` | Operatore, target valido | `alm_operator` | Stato aggiornato, owner azzerato, storico `to_retired` | Integrazione |
| T14 | Cambio stato kit | Propagazione ai componenti | Asset kit | `alm_operator` | Stato/owner propagati e storico componenti scritto | Integrazione |
| T15 | Cambio stato componente | Rimozione da kit padre | Asset componente presente in kit | `alm_operator` | Componente rimosso da ACF `components` del kit | Integrazione |
| T16 | Sicurezza | Nonce/capability/input validation | Nonce mancante o capability assente | Utente non autorizzato | Operazione negata, nessuna scrittura DB | Integrazione |
| T17 | Storico prestiti | Visibilità per ruolo | Storico presente | Operatore vs membro | Operatore vede tutto, membro solo eventi coinvolti (max 10) | Integrazione |
| T18 | Regressione | Bugfix coverage | Scenario bug noto | Dipende dal caso | Bug non riproducibile | Unit/Integra/E2E |

---

## Flussi narrativi end-to-end (A/B/C)
Legenda attori:
- `A`, `D`: membri richiedenti.
- `B`: owner corrente dell'asset.
- `C`: operatore (`alm_operator`).

1. `A` richiede componente di `B` -> `B` rifiuta.
2. `A` richiede componente di `B` -> `B` accetta.
3. `A` richiede componente di `B` -> `C` (operatore) accetta.
4. `A` richiede componente di `B` -> `C` (operatore) rifiuta.
5. `A` richiede componente di `B`, poi `B` accetta `D` -> richiesta di `A` viene cancellata automaticamente.
6. `A` richiede asset attualmente `on-loan` a `B` -> richiesta pendente valida.
7. `A` prova a richiedere un asset che possiede gia` (`B = A`) -> richiesta bloccata.
8. `A` prova a richiedere asset in `maintenance` -> richiesta bloccata.
9. `A` prova a richiedere asset `retired` -> richiesta bloccata.
10. `A` prova a fare due richieste pendenti (con `allow_multiple_requests = false`) -> seconda bloccata.
11. `A` supera il limite prestiti attivi (`max_active_per_user`) -> nuova richiesta bloccata.
12. `C` assegna direttamente un asset da `B` ad `A`.
13. `C` assegna direttamente un asset da `B` a se stesso.
14. `C` assegna direttamente un asset con richieste pendenti -> richieste pendenti cancellate automaticamente.
15. `C` prova assegnazione diretta su asset `retired` -> operazione bloccata.
16. `B` approva richiesta di `A` su kit -> owner/stato propagati ai componenti.
17. `B` approva richiesta di `A` su kit con componente in `maintenance`/`retired` -> operazione fallita (rollback).
18. `C` imposta componente `on-loan` da `B` a `maintenance` -> owner azzerato e storico scritto.
19. `C` imposta kit da `on-loan` a `retired` -> kit e componenti passano a `retired`, owner azzerati.
20. `C` imposta a `maintenance` un componente presente in kit -> componente rimosso dal kit padre e stato aggiornato.

---

## Rifattorizzazioni necessarie prima dei test

### Cosa blocca i test oggi

#### Problema 1 — I metodi di business logic sono `private`
`approve_loan_request`, `reject_loan_request`, `direct_assign_asset`, `change_asset_state`
sono tutti `private`. Per testare T5, T8, T12, T15 ecc. esistono due opzioni:

- **Opzione A** — testare attraverso i metodi AJAX pubblici (`ajax_approve_loan_request` ecc.).
  Funziona ma richiede di impostare `$_POST`, creare nonce validi, usare `_handleAjax()` di WP.
  Ogni test ha 10 righe di scaffolding invece di 3.
- **Opzione B** — rendere i metodi `protected` invece di `private`. Si crea una sottoclasse
  `ALM_Loan_Manager_Testable extends ALM_Loan_Manager` che espone solo quello che serve ai test.
  Zero impatto comportamentale, ma introduce un oggetto di test artificiale.

**Opzione C** — rendere i metodi `public` direttamente. La sicurezza sta nel layer AJAX
  (nonce + capability), non nella visibilità PHP. I metodi interni richiedono input già validati
  e non accettano richieste HTTP dirette. È l'approccio usato in WooCommerce e Jetpack.

**Opzione D** — estrarre la logica in una classe collaboratrice con interfaccia pubblica
  (es. `ALM_Loan_Approver`, `ALM_State_Changer`). È la soluzione architetturalmente più pulita,
  raccomandata da Human Made e WordPress VIP, ma richiede più lavoro e introduce più file.

**Ricerca su community WP (Human Made, Automattic, VIP, Delicious Brains, Carl Alexander):**
- Il consenso è che un metodo privato che ha bisogno di test diretti è un segnale di design,
  non un problema di testing. La soluzione preferita è l'estrazione in collaboratori (Opzione D).
- Quando non è fattibile: `protected` + sottoclasse è la via più pulita (Opzione B).
- `ReflectionMethod::setAccessible()` è tollerato per codice legacy ma esplicitamente
  marcato come code smell da Human Made e VIP.
- `_handleAjax()` è lo strumento giusto per testare il layer AJAX, non la logica interna.
- Rendere i metodi `public` (Opzione C) è accettabile quando la sicurezza è garantita
  dal layer superiore, come in questo plugin.

**Scelta consigliata per ALM:** Opzione C (rendere `public` i metodi di business logic) nel
breve termine, come passo più semplice che non introduce oggetti artificiali.
Opzione D (estrazione in collaboratori) nella Fase 5, quando si introducono gli unit test.

#### Problema 2 — `get_asset_state_slug` è `private`
I test devono verificare lo stato dell'asset dopo ogni operazione. Oggi dovrebbero chiamare
direttamente `get_the_terms()` di WordPress. Va reso almeno `protected` o `public`.
Questo cambiamento è a basso rischio e non ha controindicazioni.

#### Problema 3 — `can_user_approve_request` e `can_user_reject_request` sono `private`
I test di sicurezza (T16) devono verificare le regole di permesso. Va reso `public` o `protected`.
Anche questo cambiamento è chirurgico e a rischio zero.

### Cosa NON cambiare prima dei test

- **`parse_*_payload()`** — i test di integrazione coprono la validazione passando per i metodi
  AJAX o impostando `$_POST` direttamente. Non è un prerequisito.
- **`is_transition_allowed()`** — utile a lungo termine ma richiede di ridisegnare il flusso.
  Da fare nella Fase 5.
- **`ERR_*` codici errore** — comodi ma non bloccanti. I test possono assertare sulla struttura
  della risposta (`success = false`) senza codici stabili.
- **`is_asset_loanable()`** — la duplicazione è minima, non vale il refactoring ora.

---

## Timeline di implementazione

### Fase 1 — Setup ambiente (prerequisito tutto il resto)
- Installare `phpunit/phpunit` via Composer.
- Configurare il bootstrap WP con `wp scaffold plugin-tests` (crea `tests/`, `phpunit.xml`, `bin/install-wp-tests.sh`).
- Creare classe base `ALM_Test_Case` che estende `WP_UnitTestCase` e installa le tabelle custom del plugin.
- Creare `ALM_Test_Factory`: metodi helper per creare utenti con ruolo, asset con stato/struttura, kit con componenti, richieste di prestito.

### Fase 2 — Prima suite: i casi di blocco (T4, T8, T11, T16)
I test negativi (qualcosa deve fallire) sono i più facili da scrivere e i più preziosi:
- T4: richiesta su asset `maintenance`/`retired` → errore atteso, DB invariato.
- T8: approvazione kit con componente bloccato → rollback, owner invariato.
- T11: assegnazione diretta su `retired` → errore atteso.
- T16: chiamata AJAX senza nonce o senza capability → risposta di errore, nessuna scrittura DB.

### Fase 3 — Happy path principali (T1, T5, T7, T10, T12–T15)
I flussi che devono andare a buon fine:
- T1: richiesta prestito su asset disponibile → riga in `alm_loan_requests`.
- T5: approvazione → owner aggiornato, stato `on-loan`, storico scritto.
- T7: approvazione kit → propagazione a componenti.
- T10: assegnazione diretta → owner + stato + storico corretti.
- T12–T15: cambio stato maintenance/retired con e senza propagazione kit.

### Fase 4 — Regressioni e casi limite (T2, T3, T6, T9, T17, T18)
- T6: cancellazione richieste concorrenti all'approvazione.
- T3: blocco duplicato richiesta pendente.
- T2: richiesta su asset `on-loan`.
- T9: rifiuto con messaggio.
- T17: visibilità storico per ruolo.
- T18: un test per ogni bug già risolto (priorità: `cc6ad4c` e `a2f8060`).

### Fase 5 — Unit test incrementali (dopo le fasi 1–4)
Quando la suite di integrazione è stabile, estrarre gradualmente la logica pura nelle micro-rifattorizzazioni elencate sopra e coprirla con Brain Monkey. Nessuna riscrittura massiva: un metodo alla volta, guidato da un test che fallisce prima di estrarlo.

### Fase 6 — E2E con Playwright (opzionale, bassa priorità)
Massimo 5–6 scenari dal punto di vista del browser: i flussi narrativi 5 (cancellazione concorrente), 17 (kit con componente bloccato), 19 (kit on-loan → retired). Solo se le fasi precedenti sono consolidate.

---

## Note operative
- Prioritizzare copertura su `ALM_Loan_Manager` e sui punti di ingresso pubblici (AJAX/REST).
- Isolare fixture e factory utenti/asset per rendere i test ripetibili.
- Tenere i test E2E minimi ma rappresentativi dei flussi core.
