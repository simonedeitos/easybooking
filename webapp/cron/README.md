# Cron Jobs – EasyBooking

Questa cartella contiene gli script PHP per i job periodici (cron).

## Configurazione su Hostinger

Accedi al pannello Hostinger → **Hosting** → **Cron Jobs** e aggiungi i seguenti job:

---

### 1. Segna lezioni come "Svolta" (ogni giorno alle 00:05)

```
5 0 * * *   php /home/utente/public_html/webapp/cron/mark-lessons-done.php >> /home/utente/public_html/webapp/cron/mark-lessons-done.log 2>&1
```

**Cosa fa:** Tutte le lezioni del giorno precedente con stato *Programmata*, *Rimandata* o *Riprogrammata* vengono automaticamente aggiornate a **Svolta**.

---

### 2. Notifiche email (ogni ora)

```
0 * * * *   php /home/utente/public_html/webapp/cron/send-notifications.php >> /home/utente/public_html/webapp/cron/send-notifications.log 2>&1
```

**Cosa fa:** Lo script verifica ogni ora se è il momento giusto per inviare le notifiche configurate da ogni utente:

| Tipo | Quando viene inviata |
|---|---|
| **Promemoria lezioni** | Il giorno e orario configurato (es. ogni lunedì alle 09:00) |
| **Report settimanale** | Il giorno e orario configurato (es. ogni venerdì alle 18:00) |
| **Report mensile** | Il giorno del mese e orario configurato (es. il 1° di ogni mese alle 18:00) |
| **Avviso scadenza pacchetti** | Ogni ora (verifica se ci sono pacchetti in esaurimento) |
| **Avviso lezioni non confermate** | Ogni ora (verifica lezioni non confermate entro N giorni) |

> **Nota:** Le notifiche vengono inviate solo se l'utente ha abilitato le email in *Impostazioni → Notifiche*.

---

### Sicurezza (opzionale)

Se vuoi chiamare i cron via HTTP (es. da un servizio esterno), imposta la variabile d'ambiente `CRON_SECRET` nel file `.env`:

```
CRON_SECRET=una_stringa_segreta_lunga
```

E poi chiama gli script con:
```
https://tuo-dominio.it/webapp/cron/mark-lessons-done.php?cron_token=una_stringa_segreta_lunga
```

> ⚠️ La cartella `cron/` è bloccata ad Apache via `.htaccess`. Se usi Nginx, aggiungi manualmente il blocco.

---

### Percorsi da sostituire

Sostituisci `/home/utente/public_html/` con il percorso reale del tuo account Hostinger. Lo puoi trovare in:
**Hostinger → File Manager → barra di navigazione in cima**.
