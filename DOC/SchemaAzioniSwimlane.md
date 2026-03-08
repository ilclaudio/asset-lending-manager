# Swimlane schema permessi per ruolo — Asset Lending Manager

Questo file contiene la versione grafica Mermaid dello swimlane del documento `DOC/SchemaPermessiPerRuolo.md`.

```mermaid
flowchart TB
  subgraph L1[Visitatore]
    V1[Visualizza elenco risorse]
    V2[Visualizza dettaglio risorsa]
    V1 --> V2
  end

  subgraph L2[Socio alm_member]
    S1[Consulta elenco e dettaglio]
    S2[Invia richiesta prestito]
    S3{Proprietario corrente}
    S4[Approva o rifiuta richiesta]
    S1 --> S2 --> S3
    S3 -- Si --> S4
    S3 -- No --> S5[Attende esito richiesta]
  end

  subgraph L3[Operatore e Amministratore]
    O1[Inserisce o modifica risorsa]
    O2[Gestisce tassonomie]
    O3[Visualizza richieste prestito]
    O4{Proprietario corrente}
    O5[Approva o rifiuta richiesta]
    O6[Assegnamento diretto]
    O7[Cambia stato asset - maintenance o retired]
    O1 --> O2 --> O3 --> O4
    O4 -- Si --> O5
    O4 -- No --> O6
    O3 --> O7
  end

  S2 --> O3
  O5 --> H1[Storico aggiornato]
  O6 --> H1
  O7 --> H1
```

Se il tuo viewer Markdown non renderizza Mermaid, usa la versione testuale ASCII in `DOC/SchemaPermessiPerRuolo.md`.
