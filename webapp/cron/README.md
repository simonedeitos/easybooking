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

### 2. Notifiche email (ogni 5 minuti consigliato)

```
*/5 * * * *   php /home/utente/public_html/webapp/cron/send-notifications.php >> /home/utente/public_html/webapp/cron/send-notifications.log 2>&1
```

**Cosa fa:** Lo script verifica ad ogni esecuzione se è il momento giusto per inviare le notifiche configurate da ogni utente. Il controllo sull'orario è tollerante di 60 secondi, così l'invio funziona anche se il cron parte con un leggero ritardo.

| Tipo | Quando viene inviata |
|---|---|
| **Promemoria lezioni** | Il giorno e orario configurato (es. ogni lunedì alle 09:00) |
| **Report settimanale** | Il giorno e orario configurato (es. ogni venerdì alle 18:00) |
| **Report mensile** | Il giorno del mese e orario configurato (es. il 1° di ogni mese alle 18:00) |
| **Avviso scadenza pacchetti** | Ogni esecuzione del cron (verifica se ci sono pacchetti in esaurimento) |
| **Avviso lezioni non confermate** | Ogni esecuzione del cron (verifica lezioni non confermate entro N giorni) |

> **Nota:** Le notifiche vengono inviate solo se l'utente ha abilitato le email in *Impostazioni → Notifiche*.

### Modalità test / anteprima HTML

Per generare le email senza inviarle davvero puoi usare la modalità test:

```bash
php /home/utente/public_html/webapp/cron/email-test-mode.php
```

Oppure via browser/HTTP:

```text
https://tuo-dominio.it/webapp/cron/send-notifications.php?cron_token=<CRON_SECRET>&test_mode=1
```

In test mode lo script **non invia email**, ma salva l'HTML generato in una cartella temporanea del server e registra il percorso nei log.

---

### Sicurezza (opzionale)

Se vuoi chiamare i cron via HTTP (es. da un servizio esterno), imposta la variabile d'ambiente `CRON_SECRET` nel file `.env`:

```
CRON_SECRET=una_stringa_segreta_lunga
```

Genera una stringa sicura con: `openssl rand -hex 32`

E poi chiama gli script con:
```
https://tuo-dominio.it/webapp/cron/mark-lessons-done.php?cron_token=una_stringa_segreta_lunga
```

> ⚠️ La cartella `cron/` è bloccata ad Apache via `.htaccess`. Se usi Nginx, aggiungi manualmente il blocco.

---

### Test via browser (debug)

Per testare gli script via browser senza CLI:

1. **Disabilita temporaneamente** il file `cron/.htaccess` (rinominalo in `.htaccess.bak`)
2. Assicurati che `webapp/.env` esista e contenga `CRON_SECRET=<valore>` e le credenziali DB corrette
3. Accedi via browser:
   ```
   http://localhost/webapp/cron/mark-lessons-done.php?cron_token=<valore di CRON_SECRET>
   http://localhost/webapp/cron/send-notifications.php?cron_token=<valore di CRON_SECRET>
   ```
4. **Ripristina** `.htaccess` dopo il test

> ⚠️ Non lasciare `.htaccess` disabilitato in produzione.

---

### Risoluzione problemi

#### Errore 500 via browser
La causa più comune è una configurazione mancante o errata. Controllare:

1. **`webapp/.env` esiste?** Se non esiste, copiare `.env.example` in `.env` e compilare i valori.
2. **`CRON_SECRET` è impostato** in `.env` e il valore coincide con il parametro `cron_token` nell'URL.
3. **Credenziali database corrette** (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).
4. **Log di errore PHP:** controllare `webapp/cron/php-error.log` per i dettagli tecnici.
   Questo file viene creato automaticamente quando gli script vengono chiamati via HTTP.

#### Risposta 403 Accesso negato
- `CRON_SECRET` non è nel `.env` oppure il `cron_token` nell'URL non corrisponde.

#### Nessun output / pagina bianca
- Il file `.env` non esiste: lo script si interrompe prima di produrre output.
- Controllare `webapp/cron/php-error.log`.

---

### Percorsi da sostituire

Sostituisci `/home/utente/public_html/` con il percorso reale del tuo account Hostinger. Lo puoi trovare in:
**Hostinger → File Manager → barra di navigazione in cima**.
