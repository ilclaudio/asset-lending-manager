# Schema permessi per ruolo — Asset Lending Manager

Questo documento riassume le operazioni disponibili per ciascun ruolo nel plugin.

## Ruoli previsti dal sistema
* Amministratore
* Visitatore (utente anonimo)
* **Socio**: almgr_member
* **Operatore**: almgr_operator
  
	---



## Matrice ruoli x operazioni

| Operazione | Visitatore | Socio (`almgr_member`) | Operatore (`almgr_operator`) | Amministratore |
|---|---|---|---|---|
| Visualizzare elenco risorse | Si | Si | Si | Si |
| Visualizzare dettaglio risorsa | Si | Si | Si | Si |
| Filtrare per "solo mie risorse" | No | Si | No | No |
| Filtrare per proprietario specifico | No | No | Si | Si |
| Inserire/modificare risorse | No | No | Si | Si |
| Gestire tassonomie/stati/livelli | No | No | Si | Si |
| Richiedere prestito | No | Si | Si | Si |
| Approvare/rifiutare richiesta | No | Si (solo se proprietario corrente) | Si | Si |
| Vedere richieste di prestito asset | No | Si (solo se proprietario corrente) | Si (con azioni approva/rifiuta) | Si (con azioni approva/rifiuta) |
| Assegnamento diretto | No | No | Si | Si |
| Cambiare stato asset (→ maintenance / → retired) da frontend | No | No | Si | Si |
| Restituire forzatamente asset (→ available da on-loan) da frontend | No | No | Si | Si |
| Ripristinare asset a disponibile (da maintenance/retired) da frontend | No | No | Si | Si |
| Vedere storico prestiti (UI attuale) | No | No | Si | Si |

---

## Relazioni sintetiche (role -> azioni)

- Operatore -> Inserimento e modifica risorse; gestione tassonomie; richiesta prestito; gestione richieste prestito (approva/rifiuta consentito); assegnamento diretto; cambio stato asset (→ maintenance / → retired) da frontend con location obbligatoria; restituzione forzata (→ available da on-loan) con location obbligatoria e notifica al socio; ripristino asset a disponibile (da maintenance/retired) da frontend con location obbligatoria.
- Socio -> Richiesta prestito; approvazione/rifiuto solo se proprietario corrente.
- Amministratore -> Stesso perimetro operativo dell'operatore, inclusa approvazione/rifiuto richieste anche senza essere proprietario corrente. Privilegi WordPress generali aggiuntivi.

---

## Swimlane (ASCII)

Diagramma Mermaid separato: `DOC/SchemaAzioniSwimlane.md`.

```text
VISITATORE
  [Visualizza elenco risorse] -> [Visualizza dettaglio risorsa]

SOCIO (almgr_member)
  [Consulta elenco e dettaglio]
    -> [Invia richiesta prestito]
    -> <Proprietario corrente?>
         |-- Si --> [Approva o rifiuta richiesta]
         `-- No --> [Attende esito richiesta]

OPERATORE / AMMINISTRATORE
  [Inserisce o modifica risorsa]
    -> [Gestisce tassonomie]
    -> [Visualizza richieste prestito]
    -> [Approva o rifiuta richiesta]
    -> [Assegnamento diretto (asset non retired/maintenance)]
    -> [Cambia stato: → maintenance / → retired] + location obbligatoria
         `-> [Ripristina a disponibile da maintenance/retired] + location obbligatoria
    -> [Restituzione forzata: on-loan → disponibile] + location obbligatoria
         (chiude prestito aperto, notifica il socio)

COLLEGAMENTI TRA SWIMLANE
  Socio: [Invia richiesta prestito] -----------> Operatore: [Visualizza richieste prestito]
  Socio/Proprietario: [Approva/rifiuta]  \
  Operatore: [Assegnamento diretto]       +---> [Storico aggiornato]
  Operatore: [Cambia/Ripristina stato]   /
  Operatore: [Restituzione forzata]     /
```

---

## Note importanti

- Le azioni di approvazione/rifiuto sono consentite al socio solo se l'utente è il proprietario corrente dell'asset. Operatore e amministratore (capability `almgr_edit_asset`) possono approvare/rifiutare anche quando non sono proprietari correnti.
- L'assegnamento diretto e' consentito solo a chi ha capability `almgr_edit_asset` (operatore/amministratore).
- Nella UI corrente, lo storico prestiti e' mostrato solo a operatore/amministratore.

---

*Ultimo aggiornamento: 2026-04-14 (rev 5)*
