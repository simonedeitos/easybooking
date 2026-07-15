# EasyBooking – Gestione Scuola di Musica

Sistema di gestione per scuole di musica: clienti, insegnanti, prenotazioni, pacchetti lezioni, acquisti e molto altro.

---

## Requisiti

- PHP 7.4+ con estensioni: `pdo`, `pdo_mysql`, `openssl`, `simplexml`, `mbstring`
- MySQL 5.7+ / MariaDB 10.3+
- Server web: Apache/Nginx con mod_rewrite (o equivalente)

---

## Struttura Cartelle

```
webapp/
├── adminsetup.php          # Setup iniziale (CF webmaster required)
├── index.php               # Login
├── logout.php              # Logout
├── dashboard.php           # Dashboard principale
├── clienti.php             # Gestione clienti
├── insegnanti.php          # Gestione insegnanti
├── prenotazioni.php        # Gestione prenotazioni/lezioni
├── calendario.php          # Calendario visivo (FullCalendar)
├── pacchetti.php           # Gestione pacchetti lezioni
├── acquisti.php            # Gestione acquisti/pagamenti
├── strumenti.php           # Gestione strumenti musicali
├── report.php              # Report e statistiche
├── impostazioni.php        # Impostazioni generali
├── notifiche.php           # Configurazione notifiche
├── backup.php              # Backup/Ripristino database
├── import-xml.php          # Importazione file XML da desktop app
├── database-schema.sql     # Schema database completo
├── config/
│   ├── database.php        # Connessione PDO (singleton)
│   ├── encryption.php      # Cifratura AES-256-CBC
│   └── functions.php       # Funzioni utility (CSRF, flash, sanitize…)
├── includes/
│   ├── auth.php            # Autenticazione sessione
│   ├── header.php          # Layout header + sidebar
│   └── footer.php          # Footer + script JS
└── assets/
    ├── css/
    │   ├── style.css       # CSS principale con variabili tema
    │   ├── dark-theme.css  # Tema scuro (default)
    │   └── light-theme.css # Tema chiaro
    └── js/
        ├── main.js         # JS principale (tema, sidebar, DataTables…)
        └── calendar.js     # FullCalendar integration
```

---

## Installazione

### 1. Caricare i file

Carica la cartella `webapp/` nella directory del tuo server web (es. `/var/www/html/easybooking/` o subdirectory).

### 2. Setup iniziale

Apri nel browser: `https://tuodominio.it/adminsetup.php`

1. **Verifica CF Webmaster**: inserisci il codice `DTSSMN93E20F471O`
2. **Tab "Setup Database"**:
   - Clicca "Crea Struttura DB" per eseguire lo schema SQL
   - Compila il form per creare il primo utente amministratore
   - Clicca "Genera Chiave Cifratura" e **salva la chiave mostrata** in un posto sicuro
3. **Tab "Importa XML"** (opzionale): se hai file XML dall'app desktop, caricali qui

### 3. Primo accesso

Vai su `https://tuodominio.it/index.php` e accedi con le credenziali create al punto 2.

---

## Importazione da App Desktop C#

L'app desktop cripta i file XML con:
- Algoritmo: AES-256-CBC
- Derivazione chiave: PBKDF2-SHA1, 1000 iterazioni
- Password: `EasyBooking!2025`
- Salt: `EasyBookingSalt` (UTF-8)

La webapp decripta automaticamente questi file nella pagina **Importa XML** (`import-xml.php`) e nella sezione Import di `adminsetup.php`.

Se i file XML non sono criptati (plain XML), vengono importati direttamente.

---

## Credenziali Database

Configurate in `config/database.php`:

| Parametro | Valore |
|-----------|--------|
| Host | `mysql` (fallback: `localhost`) |
| Database | `u362062795_easybooking` |
| Username | `u362062795_easybooking` |
| Password | `D4tabas3-EasyB00k1ng-vocefutura` |

---

## Sicurezza

- **Password**: hash con `password_hash()` (bcrypt)
- **CSRF**: token su tutti i form e richieste AJAX
- **XSS**: output sanitizzato con `htmlspecialchars()`
- **SQL Injection**: impossibile – solo PDO prepared statements
- **Sessioni**: cookie con flag `HttpOnly`, `SameSite=Strict`, `Secure` su HTTPS
- **Cifratura campi DB**: AES-256-CBC con chiave memorizzata in `system_config`
- **Dati sensibili**: telefoni, email, CF, indirizzi vengono cifrati nel DB

---

## Funzionalità

| Pagina | Funzionalità |
|--------|-------------|
| Dashboard | Statistiche, prossime lezioni, pacchetti in scadenza, grafici |
| Clienti | CRUD completo, link WhatsApp, MEGA links |
| Insegnanti | CRUD completo, strumenti, tariffe coppia |
| Prenotazioni | CRUD, filtri, bulk status update |
| Calendario | FullCalendar drag&drop, conflict detection |
| Pacchetti | CRUD pacchetti lezioni |
| Acquisti | CRUD acquisti, stato pagamento, alert scadenze |
| Strumenti | CRUD con orari per giorno |
| Report | Ore insegnanti, entrate, statistiche lezioni, export CSV |
| Impostazioni | Giorni lavorativi, orari, profilo utente, tema |
| Notifiche | Configurazione alert email |
| Backup | Export/Import SQL completo |
| Import XML | Importazione da app desktop C# |

---

## Librerie Utilizzate (CDN)

| Libreria | Versione | Uso |
|----------|----------|-----|
| Bootstrap | 5.3.2 | UI framework |
| FontAwesome | 6.5.0 | Icone |
| DataTables | 1.13.7 | Tabelle interattive |
| Chart.js | 4.4.1 | Grafici dashboard/report |
| FullCalendar | 6.1.10 | Calendario interattivo |
| jQuery | 3.7.1 | Richiesto da DataTables |

---

## Configurazione Apache (.htaccess)

Opzionale – per URL puliti:

```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]

# Security headers
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set X-XSS-Protection "1; mode=block"
```

---

## Supporto

Per assistenza tecnica o segnalazioni bug, contatta il webmaster.

**CF Webmaster**: `DTSSMN93E20F471O`
