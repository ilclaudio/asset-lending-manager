# Schema notifiche email — Asset Lending Manager

Questo documento descrive quando vengono inviate le email di notifica, a chi
arrivano, quale template viene usato e con quale testo.

Le email vengono inviate tramite `wp_mail()` dalla classe `ALMGR_Notification_Manager`.
Il mittente e i destinatari di copia sono letti dai settings runtime:
`email.from_name`, `email.from_address`, `email.system_email`.
Fallback attuali: nome sito (`get_bloginfo('name')`) e admin email (`get_bloginfo('admin_email')`) quando i campi mittente sono vuoti.

---

## Tabella riassuntiva

| # | Evento | Attore | Destinatari | Template oggetto | Condizioni speciali |
|---|---|---|---|---|---|
| 1 | Richiesta di prestito inviata | Membro | Richiedente | `request_to_requester` | Sempre |
| 1 | Richiesta di prestito inviata | Membro | Proprietario corrente | `request_to_owner` | Solo se asset già assegnato |
| 1 | Richiesta di prestito inviata | Membro | Sistema | `request_to_owner` | Solo se `email.system_email` configurato |
| 2 | Richiesta approvata | Proprietario corrente | Richiedente | `approved` | Sempre |
| 3 | Richiesta rifiutata | Proprietario corrente | Richiedente | `rejected` | Sempre |
| 4 | Richiesta annullata automaticamente | Sistema | Richiedente (della richiesta cancellata) | `canceled` | Una email per ogni richiesta concorrente annullata |
| 5 | Assegnamento diretto | Operatore | Nuovo proprietario | `direct_assign` | Sempre |
| 5 | Assegnamento diretto | Operatore | Vecchio proprietario | `direct_assign_to_prev_owner` | Solo se esisteva un proprietario precedente diverso dal nuovo |
| 5 | Assegnamento diretto | Operatore | Sistema | `direct_assign_to_prev_owner` | Solo se `email.system_email` configurato |
| 6 | Restituzione forzata (on-loan → available) | Operatore | Socio che aveva il prestito | `force_return` | Sempre (se notifiche abilitate) |

---

### Email di copia all'indirizzo di sistema

- **A chi:** `email.system_email`
- **Condizione:** inviata solo se il setting è configurato
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
| `{BORROWER_NAME}` | force_return | Nome del socio che aveva il prestito |
| `{ACTOR_NAME}` | force_return | Nome dell'operatore che ha eseguito la restituzione forzata |
| `{NOTES}` | force_return | Note opzionali dell'operatore (— se assenti) |

---

## Configurazione mittente e indirizzi

Settings runtime in `almgr_settings`:

| Setting | Ruolo |
|---|---|
| `email.from_name` | Nome visualizzato del mittente (fallback: nome sito) |
| `email.from_address` | Indirizzo email del mittente (fallback: admin email del sito) |
| `email.system_email` | Indirizzo di copia operatore/sistema (opzionale) |

Nota: nel flusso corrente `ALMGR_Notification_Manager` usa i settings runtime; le costanti `ALMGR_EMAIL_*` in `plugin-config.php` non vengono lette direttamente dal codice di invio.

---

*Ultimo aggiornamento: 2026-04-07*
