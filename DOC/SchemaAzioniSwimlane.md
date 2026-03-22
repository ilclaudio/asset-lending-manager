# Swimlane azioni per ruolo — Asset Lending Manager

Versione grafica Mermaid del documento `DOC/SchemaPermessiPerRuolo.md`.
Se il tuo viewer non renderizza Mermaid, usa la versione testuale ASCII in fondo al file.

---

## Diagramma Mermaid

```mermaid
flowchart TD
  subgraph VIS["Visitatore"]
    V1["Visualizza elenco risorse"]
    V2["Visualizza dettaglio risorsa"]
    V1 --> V2
  end

  subgraph SOC["Socio (alm_member)"]
    S1["Consulta elenco e dettaglio"]
    S2["Invia richiesta prestito"]
    S3{"Proprietario corrente?"}
    S4["Approva / rifiuta richiesta"]
    S5["Attende esito richiesta"]
    S1 --> S2 --> S3
    S3 -->|Si| S4
    S3 -->|No| S5
  end

  subgraph OPE["Operatore / Amministratore"]
    O1["Inserisce o modifica risorsa"]
    O2["Gestisce tassonomie"]
    O3["Visualizza richieste prestito"]
    O4["Approva / rifiuta richiesta"]
    O5["Assegnamento diretto"]
    O6["Cambia stato\nmaintenance / retired\n+ location obbligatoria"]
    O7["Ripristina a disponibile\nda maintenance / retired\n+ location obbligatoria"]
    O8["Restituzione forzata\nda on-loan → disponibile\n+ location obbligatoria\n(chiude prestito, notifica socio)"]
    O1 --> O2
    O2 --> O3
    O3 --> O4
    O3 --> O5
    O3 --> O6
    O6 --> O7
    O3 --> O8
  end

  H1[("Storico aggiornato")]

  S2 --> O3
  S4 --> H1
  O4 --> H1
  O5 --> H1
  O6 --> H1
  O7 --> H1
  O8 --> H1
```

---

## Versione ASCII

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
    -> [Approva o rifiuta richiesta]
    -> [Assegnamento diretto]
    -> [Cambia stato: maintenance / retired] + location obbligatoria
         `-> [Ripristina a disponibile da maintenance/retired] + location obbligatoria
    -> [Restituzione forzata: on-loan -> disponibile] + location obbligatoria
         (chiude prestito aperto, notifica il socio)

COLLEGAMENTI TRA SWIMLANE
  Socio: [Invia richiesta prestito] ---------> Operatore: [Visualizza richieste prestito]
  Socio/Operatore: [Approva/rifiuta] \
  Operatore: [Assegnamento diretto]   +---> [Storico aggiornato]
  Operatore: [Cambia/Ripristina]     /
  Operatore: [Restituzione forzata] /
```

---

*Ultimo aggiornamento: 2026-03-22*
