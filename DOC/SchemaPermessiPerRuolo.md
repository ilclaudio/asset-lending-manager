# Schema permessi per ruolo — Asset Lending Manager

Questo documento riassume le operazioni disponibili per ciascun ruolo nel plugin.

## Ruoli previsti dal sistema
* Amministratore
* Visitatore (utente anonimo)
* **Socio**: alm_member
* **Operatore**: alm_operator
  
	---



## Matrice ruoli x operazioni

| Operazione | Visitatore | Socio (`alm_member`) | Operatore (`alm_operator`) | Amministratore |
|---|---|---|---|---|
| Visualizzare elenco risorse | Si | Si | Si | Si |
| Visualizzare dettaglio risorsa | Si | Si | Si | Si |
| Filtrare per "solo mie risorse" | No | Si | No | No |
| Filtrare per proprietario specifico | No | No | Si | Si |
| Inserire/modificare risorse | No | No | Si | Si |
| Gestire tassonomie/stati/livelli | No | No | Si | Si |
| Richiedere prestito | No | Si | Si | Si |
| Approvare/rifiutare richiesta | No | Si (solo se proprietario corrente) | Si (solo se proprietario corrente) | Si (solo se proprietario corrente) |
| Vedere richieste di prestito asset | No | Si (solo se proprietario corrente) | Si (anche monitoraggio read-only se non proprietario) | Si |
| Assegnamento diretto | No | No | Si | Si |
| Cambiare stato asset (maintenance/retired) da frontend | No | No | Si | Si |
| Vedere storico prestiti (UI attuale) | No | No | Si | Si |

---

## Relazioni sintetiche (role -> azioni)

- Operatore -> Inserimento e modifica risorse; gestione tassonomie; richiesta prestito; monitoraggio richieste; assegnamento diretto; cambio stato asset (maintenance/retired) da frontend.
- Socio -> Richiesta prestito; approvazione/rifiuto solo se proprietario corrente.
- Amministratore -> Stesso perimetro operativo dell'operatore (piu' privilegi WordPress generali).

---

## Swimlane (ASCII)

Diagramma Mermaid separato: `DOC/SchemaAzioniSwimlane.md`.

```text
VISITATORE
[Visualizza elenco risorse] -> [Visualizza dettaglio risorsa]

SOCIO (alm_member)
[Consulta elenco e dettaglio]
  -> [Invia richiesta prestito]
  -> <Proprietario corrente?>
       |-- Si --> [Approva o rifiuta richiesta]
       `-- No --> [Attende esito richiesta]

OPERATORE / AMMINISTRATORE
[Inserisce o modifica risorsa]
  -> [Gestisce tassonomie]
  -> [Visualizza richieste prestito]
  -> <Proprietario corrente?>
       |-- Si --> [Approva o rifiuta richiesta]
       `-- No --> [Assegnamento diretto]

COLLEGAMENTI TRA SWIMLANE
Socio: [Invia richiesta prestito] ------------> Operatore/Admin: [Visualizza richieste prestito]
Operatore/Admin: [Approva o rifiuta richiesta] \
                                                 +--> [Storico aggiornato]
Operatore/Admin: [Assegnamento diretto]        /
```

---

## Note importanti

- Le azioni di approvazione/rifiuto sono consentite al proprietario corrente dell'asset, non in base al ruolo puro.
- L'assegnamento diretto e' consentito solo a chi ha capability `alm_edit_asset` (operatore/amministratore).
- Nella UI corrente, lo storico prestiti e' mostrato solo a operatore/amministratore.

---

*Ultimo aggiornamento: 2026-03-08*
