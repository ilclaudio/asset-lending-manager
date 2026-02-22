# Schema notifiche email — Asset Lending Manager

Questo documento descrive quando vengono inviate le email di notifica, a chi
arrivano, quale template viene usato e con quale testo.

Le email vengono inviate tramite `wp_mail()` dalla classe `ALM_Notification_Manager`.
Il mittente è configurato con `ALM_EMAIL_FROM_NAME` e `ALM_EMAIL_FROM_ADDRESS`
(definiti in `plugin-config.php`). L'indirizzo di sistema (copia operatore) è
`ALM_EMAIL_SYSTEM_ADDRESS`.

---

## Tabella riassuntiva

| # | Evento | Attore | Destinatari | Template oggetto | Condizioni speciali |
|---|---|---|---|---|---|
| 1 | Richiesta di prestito inviata | Membro | Richiedente | `request_to_requester` | Sempre |
| 1 | Richiesta di prestito inviata | Membro | Proprietario corrente | `request_to_owner` | Solo se asset già assegnato |
| 1 | Richiesta di prestito inviata | Membro | Sistema | `request_to_owner` | Solo se `ALM_EMAIL_SYSTEM_ADDRESS` configurato |
| 2 | Richiesta approvata | Proprietario | Richiedente | `approved` | Sempre |
| 3 | Richiesta rifiutata | Proprietario | Richiedente | `rejected` | Sempre |
| 4 | Richiesta annullata automaticamente | Sistema | Richiedente (della richiesta cancellata) | `canceled` | Una email per ogni richiesta concorrente annullata |
| 5 | Assegnamento diretto | Operatore | Nuovo proprietario | `direct_assign` | Sempre |
| 5 | Assegnamento diretto | Operatore | Vecchio proprietario | `direct_assign_to_prev_owner` | Solo se esisteva un proprietario precedente diverso dal nuovo |
| 5 | Assegnamento diretto | Operatore | Sistema | `direct_assign_to_prev_owner` | Solo se `ALM_EMAIL_SYSTEM_ADDRESS` configurato |

---

### Email di copia all'indirizzo di sistema

- **A chi:** `ALM_EMAIL_SYSTEM_ADDRESS`
- **Condizione:** inviata solo se la costante è configurata
- **Oggetto e corpo:** identici all'email 5b (template `direct_assign_to_prev_owner`)
- **Nota:** se non c'era un proprietario precedente, il placeholder
  `{PREV_OWNER_NAME}` apparirà vuoto nel corpo

---

## Placeholder disponibili per template

| Placeholder | Disponibile in | Significato |
|---|---|---|
| `{ASSET_TITLE}` | tutti | Titolo del post asset |
| `{ASSET_URL}` | tutti | URL frontend dell'asset |
| `{REQUESTER_NAME}` | request_*, approved, rejected, canceled | Nome visualizzato del richiedente |
| `{REQUEST_MESSAGE}` | request_to_owner | Messaggio opzionale del richiedente |
| `{REJECTION_MESSAGE}` | rejected | Motivo del rifiuto |
| `{ASSIGNEE_NAME}` | direct_assign, direct_assign_to_prev_owner | Nome del nuovo proprietario |
| `{ACTOR_NAME}` | direct_assign, direct_assign_to_prev_owner | Nome dell'operatore che ha eseguito l'assegnamento |
| `{REASON}` | direct_assign, direct_assign_to_prev_owner | Motivo dell'assegnamento diretto |
| `{PREV_OWNER_NAME}` | direct_assign_to_prev_owner | Nome del precedente proprietario |

---

## Configurazione mittente e indirizzi

Costanti in `plugin-config.php`:

| Costante | Ruolo |
|---|---|
| `ALM_EMAIL_FROM_NAME` | Nome visualizzato del mittente |
| `ALM_EMAIL_FROM_ADDRESS` | Indirizzo email del mittente (fallback: admin email del sito) |
| `ALM_EMAIL_SYSTEM_ADDRESS` | Indirizzo di copia operatore/sistema (opzionale) |

---

*Ultimo aggiornamento: 2026-02-21*
