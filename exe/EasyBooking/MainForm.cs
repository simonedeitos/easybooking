using System;
using System.Drawing;
using System.IO;
using System.IO.Compression;
using System.Windows.Forms;
using System.Xml;
using System.Xml.Serialization;
using System.Security.Cryptography;
using System.Text;
using Microsoft.Win32;
using System.Linq;
using System.Collections.Generic;
using iTextSharp.text;
using iTextSharp.text.pdf;
using System.Globalization;
using System.Diagnostics;

namespace EasyBooking
{
    public partial class MainForm : Form
    {
        // Percorsi dei file
        private string dataPath;
        private string backupPath;
        private string activityName;
        private string logoPath;
        private string programmazioneFreePath; // Percorso della programmazione free
        private string calendariInsegnantiPath; // Percorso dei calendari insegnanti
        private int giorniRetenzionBackup; // Numero di giorni per la retention dei backup

        // Controlli utente correntemente visualizzati
        private UserControl currentControl;

        public MainForm()
        {
            InitializeComponent();
            InitVerificaAggiornamenti();
            LoadSettingsFromRegistry();
            EnsureDirectoriesExist();

            // Aggiungi il gestore dell'evento Load
            this.Load += MainForm_Load;
        }

        private void MainForm_Load(object sender, EventArgs e)
        {
            // Mostra la Dashboard all'avvio dell'applicazione
            var dashboardControl = new DashboardControl(dataPath);
            ShowControl(dashboardControl);

            // NUOVA FUNZIONALITÀ: Aggiorna le lezioni passate come "Svolta"
            AggiornaLezioniPassate();

            // NUOVA FUNZIONALITÀ: Pulisci i vecchi backup all'avvio
            PulisciVecchiBackup();
        }

        private void LoadSettingsFromRegistry()
        {
            try
            {
                // Utilizziamo il registro per salvare i percorsi come richiesto
                using (RegistryKey key = Registry.CurrentUser.OpenSubKey(@"SOFTWARE\EasyBooking", true) ??
                                         Registry.CurrentUser.CreateSubKey(@"SOFTWARE\EasyBooking"))
                {
                    dataPath = (string)key.GetValue("DataPath", Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.MyDocuments), "EasyBooking", "Data"));
                    backupPath = (string)key.GetValue("BackupPath", Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.MyDocuments), "EasyBooking", "Backup"));
                    activityName = (string)key.GetValue("ActivityName", "Scuola di Musica");
                    logoPath = (string)key.GetValue("LogoPath", "");

                    // Carica i percorsi per programmazione free e calendari insegnanti
                    programmazioneFreePath = (string)key.GetValue("ProgrammazioneFreePath", "programmazione-free");
                    calendariInsegnantiPath = (string)key.GetValue("CalendariInsegnantiPath", "calendari insegnanti");

                    // Carica il numero di giorni per la retention dei backup
                    giorniRetenzionBackup = (int)key.GetValue("GiorniRetenzionBackup", 30);

                    // Se è la prima esecuzione, salviamo i valori predefiniti
                    if (key.GetValue("DataPath") == null)
                    {
                        key.SetValue("DataPath", dataPath);
                        key.SetValue("BackupPath", backupPath);
                        key.SetValue("ActivityName", activityName);
                        key.SetValue("LogoPath", logoPath);
                        key.SetValue("ProgrammazioneFreePath", programmazioneFreePath);
                        key.SetValue("CalendariInsegnantiPath", calendariInsegnantiPath);
                        key.SetValue("GiorniRetenzionBackup", giorniRetenzionBackup);
                    }
                }

                // Aggiorniamo l'etichetta con il nome attività
                if (lblActivityName != null)
                {
                    lblActivityName.Text = activityName;
                }

                // Carichiamo il logo se disponibile
                LoadLogo();
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante il caricamento delle impostazioni dal registro: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);

                // Utilizziamo valori predefiniti in caso di errore
                dataPath = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.MyDocuments), "EasyBooking", "Data");
                backupPath = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.MyDocuments), "EasyBooking", "Backup");
                activityName = "Scuola di Musica";
                logoPath = "";
                programmazioneFreePath = "programmazione-free";
                calendariInsegnantiPath = "calendari insegnanti";
                giorniRetenzionBackup = 30;
            }
        }

        private void PulisciVecchiBackup()
        {
            try
            {
                // Se la retention è disattivata (0 giorni), non facciamo nulla
                if (giorniRetenzionBackup <= 0)
                    return;

                // Se la directory di backup non esiste, non c'è nulla da pulire
                if (!Directory.Exists(backupPath))
                    return;

                DateTime dataLimite = DateTime.Now.AddDays(-giorniRetenzionBackup);
                DirectoryInfo dirInfo = new DirectoryInfo(backupPath);

                // Cerca tutti i file di backup con pattern "Backup_*.zip"
                FileInfo[] backupFiles = dirInfo.GetFiles("Backup_*.zip");

                int filesEliminati = 0;
                long spazioLiberato = 0;

                foreach (FileInfo file in backupFiles)
                {
                    if (file.CreationTime < dataLimite)
                    {
                        try
                        {
                            spazioLiberato += file.Length;
                            file.Delete();
                            filesEliminati++;
                            Debug.WriteLine($"Eliminato backup: {file.Name} (creato il {file.CreationTime:dd/MM/yyyy})");
                        }
                        catch (Exception ex)
                        {
                            Debug.WriteLine($"Errore nell'eliminazione del file {file.Name}: {ex.Message}");
                        }
                    }
                }

                if (filesEliminati > 0)
                {
                    string messaggioSpazio = spazioLiberato > 0 ?
                        $" liberando {FormatBytes(spazioLiberato)}" : "";

                    Debug.WriteLine($"Pulizia backup completata: eliminati {filesEliminati} file più vecchi di {giorniRetenzionBackup} giorni{messaggioSpazio}.");

                    // Non mostriamo un messaggio all'utente per non disturbare all'avvio,
                    // ma logghiamo l'operazione nel debug
                }
            }
            catch (Exception ex)
            {
                // Non mostriamo messaggi di errore all'avvio per non disturbare l'utente
                Debug.WriteLine($"Errore durante la pulizia dei vecchi backup: {ex.Message}");
            }
        }

        private string FormatBytes(long bytes)
        {
            string[] suffix = { "B", "KB", "MB", "GB" };
            int i;
            double dblBytes = bytes;
            for (i = 0; i < suffix.Length && bytes >= 1024; i++, bytes /= 1024)
            {
                dblBytes = bytes / 1024.0;
            }
            return string.Format("{0:0.##} {1}", dblBytes, suffix[i]);
        }

        private void LoadLogo()
        {
            if (panelLogo != null && !string.IsNullOrEmpty(logoPath) && File.Exists(logoPath))
            {
                try
                {
                    // Utilizzo esplicito del namespace System.Drawing per evitare ambiguità
                    panelLogo.BackgroundImage = System.Drawing.Image.FromFile(logoPath);
                    panelLogo.BackgroundImageLayout = ImageLayout.Zoom;
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"Errore nel caricamento del logo: {ex.Message}",
                        "Errore", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                }
            }
        }

        private void EnsureDirectoriesExist()
        {
            Directory.CreateDirectory(dataPath);
            Directory.CreateDirectory(backupPath);

            // Assicuriamo che esistano anche le directory per programmazione free e calendari insegnanti
            string progFreeFullPath = Path.Combine(dataPath, programmazioneFreePath);
            string calInsFullPath = Path.Combine(dataPath, calendariInsegnantiPath);

            Directory.CreateDirectory(progFreeFullPath);
            Directory.CreateDirectory(calInsFullPath);
        }

        // Funzioni per mostrare i vari controlli utente nel pannello principale
        private void ShowControl(UserControl control)
        {
            if (currentControl != null)
            {
                panelContent.Controls.Remove(currentControl);
            }

            currentControl = control;
            control.Dock = DockStyle.Fill;
            panelContent.Controls.Add(control);
            control.BringToFront();

            // Aggiorniamo il colore del pulsante selezionato
            ResetButtonColors();

            if (control is ClientiControl)
                btnClienti.BackColor = Color.FromArgb(0, 122, 204);
            else if (control is InsegnantiControl)
                btnInsegnanti.BackColor = Color.FromArgb(0, 122, 204);
            else if (control is AcquistiControl)
                btnAcquisti.BackColor = Color.FromArgb(0, 122, 204);
            else if (control is ImpostazioniControl)
                btnImpostazioni.BackColor = Color.FromArgb(0, 122, 204);
            else if (control is DashboardControl)
                btnDashboard.BackColor = Color.FromArgb(0, 122, 204);
            else if (control is ReportControl)
                btnReport.BackColor = Color.FromArgb(0, 122, 204);
        }

        private void ResetButtonColors()
        {
            // Colore standard per i pulsanti non selezionati
            Color defaultColor = Color.FromArgb(50, 50, 50);

            btnClienti.BackColor = defaultColor;
            btnInsegnanti.BackColor = defaultColor;
            btnAcquisti.BackColor = defaultColor;
            btnImpostazioni.BackColor = defaultColor;
            btnPlanning.BackColor = defaultColor;
            btnDashboard.BackColor = defaultColor;
            btnReport.BackColor = defaultColor;
        }

        private void btnClienti_Click(object sender, EventArgs e)
        {
            var clientiControl = new ClientiControl(dataPath);
            ShowControl(clientiControl);
        }

        private void btnInsegnanti_Click(object sender, EventArgs e)
        {
            var insegnantiControl = new InsegnantiControl(dataPath);
            ShowControl(insegnantiControl);
        }

        private void btnAcquisti_Click(object sender, EventArgs e)
        {
            var acquistiControl = new AcquistiControl(dataPath);
            ShowControl(acquistiControl);
        }

        private void btnImpostazioni_Click(object sender, EventArgs e)
        {
            var impostazioniControl = new ImpostazioniControl(dataPath);
            ShowControl(impostazioniControl);
        }

        private void btnPlanning_Click(object sender, EventArgs e)
        {
            // Evidenziamo il pulsante Planning
            ResetButtonColors();
            btnPlanning.BackColor = Color.FromArgb(0, 122, 204);

            // Apriamo il Planning in una finestra NON MODALE
            PlanningForm planningForm = new PlanningForm(dataPath);

            // CAMBIA DA ShowDialog() A Show()
            planningForm.Show();

            // NON ripristiniamo immediatamente il colore del pulsante Planning
            // perché la finestra è ancora aperta

            // Gestiamo l'evento di chiusura per ripristinare i colori
            planningForm.FormClosed += (s, args) =>
            {
                // Ripristiniamo il colore del pulsante Planning quando viene chiuso
                btnPlanning.BackColor = Color.FromArgb(50, 50, 50);

                // Ripristiniamo il colore del pulsante della scheda corrente
                if (currentControl is ClientiControl)
                    btnClienti.BackColor = Color.FromArgb(0, 122, 204);
                else if (currentControl is InsegnantiControl)
                    btnInsegnanti.BackColor = Color.FromArgb(0, 122, 204);
                else if (currentControl is AcquistiControl)
                    btnAcquisti.BackColor = Color.FromArgb(0, 122, 204);
                else if (currentControl is ImpostazioniControl)
                    btnImpostazioni.BackColor = Color.FromArgb(0, 122, 204);
                else if (currentControl is DashboardControl)
                    btnDashboard.BackColor = Color.FromArgb(0, 122, 204);
                else if (currentControl is ReportControl)
                    btnReport.BackColor = Color.FromArgb(0, 122, 204);
            };
        }

        private void btnDashboard_Click(object sender, EventArgs e)
        {
            var dashboardControl = new DashboardControl(dataPath);
            ShowControl(dashboardControl);
        }

        private void btnReport_Click(object sender, EventArgs e)
        {
            var reportControl = new ReportControl(dataPath);
            ShowControl(reportControl);
        }

        private void MainForm_FormClosing(object sender, FormClosingEventArgs e)
        {
            // NUOVA FUNZIONALITÀ: Aggiorna le lezioni in corso come "Svolta" se non sono "Assente"
            AggiornaLezioniInCorso();

            // Creiamo un backup automatico alla chiusura dell'applicazione
            CreateBackupArchive();

            // Salva una copia decriptata del file prenotazioni.xml
            SaveDecryptedPrenotazioniFile();

            // Genera i PDF dei calendari degli insegnanti
            GeneraCalendariInsegnantiPDF();
        }

        // NUOVO METODO: Aggiorna le lezioni passate come "Svolta"
        private void AggiornaLezioniPassate()
        {
            try
            {
                string prenotazioniFilePath = Path.Combine(dataPath, "prenotazioni.xml");

                if (File.Exists(prenotazioniFilePath))
                {
                    var prenotazioniList = LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);

                    if (prenotazioniList?.Items != null)
                    {
                        DateTime ieri = DateTime.Today.AddDays(-1);
                        bool modifiche = false;

                        foreach (var prenotazione in prenotazioniList.Items)
                        {
                            // Se la data è fino a ieri compreso e lo stato è "Programmata", "Riprogrammata" o "Rimandata"
                            if (prenotazione.Data.Date <= ieri &&
                                (prenotazione.Stato == StatoLezione.Programmata ||
                                 prenotazione.Stato == StatoLezione.Riprogrammata ||
                                 prenotazione.Stato == StatoLezione.Rimandata))
                            {
                                prenotazione.Stato = StatoLezione.Svolta;
                                modifiche = true;
                            }
                        }

                        // Salva solo se ci sono state modifiche
                        if (modifiche)
                        {
                            SaveEncryptedXml(prenotazioniList, prenotazioniFilePath);
                        }
                    }
                }
            }
            catch (Exception ex)
            {
                // Non mostriamo il messaggio di errore all'avvio per non disturbare l'utente
                Debug.WriteLine($"Errore durante l'aggiornamento delle lezioni passate: {ex.Message}");
            }
        }

        // NUOVO METODO: Aggiorna le lezioni in corso come "Svolta" se non sono "Assente"
        private void AggiornaLezioniInCorso()
        {
            try
            {
                string prenotazioniFilePath = Path.Combine(dataPath, "prenotazioni.xml");

                if (File.Exists(prenotazioniFilePath))
                {
                    var prenotazioniList = LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);

                    if (prenotazioniList?.Items != null)
                    {
                        DateTime oggi = DateTime.Today;
                        TimeSpan oraAttuale = DateTime.Now.TimeOfDay;
                        bool modifiche = false;

                        foreach (var prenotazione in prenotazioniList.Items)
                        {
                            // Se la data è oggi
                            if (prenotazione.Data.Date == oggi)
                            {
                                // Se l'ora di inizio è precedente all'ora attuale
                                // e lo stato è "Programmata", "Riprogrammata" o "Rimandata" (non è già "Assente" o "Svolta")
                                if (prenotazione.OraInizio < oraAttuale &&
                                    (prenotazione.Stato == StatoLezione.Programmata ||
                                     prenotazione.Stato == StatoLezione.Riprogrammata ||
                                     prenotazione.Stato == StatoLezione.Rimandata))
                                {
                                    prenotazione.Stato = StatoLezione.Svolta;
                                    modifiche = true;
                                }
                            }
                        }

                        // Salva solo se ci sono state modifiche
                        if (modifiche)
                        {
                            SaveEncryptedXml(prenotazioniList, prenotazioniFilePath);
                        }
                    }
                }
            }
            catch (Exception ex)
            {
                // Non mostriamo il messaggio di errore alla chiusura per non disturbare l'utente
                Debug.WriteLine($"Errore durante l'aggiornamento delle lezioni in corso: {ex.Message}");
            }
        }

        private void SaveDecryptedPrenotazioniFile()
        {
            try
            {
                string prenotazioniFilePath = Path.Combine(dataPath, "prenotazioni.xml");
                string clientiFilePath = Path.Combine(dataPath, "clienti.xml");
                string insegnantiFilePath = Path.Combine(dataPath, "insegnanti.xml");

                // Utilizziamo il percorso configurato per il file prenotazioni-free
                string prenotazioniFreeFilePath = Path.Combine(dataPath, programmazioneFreePath, "prenotazioni-free.xml");

                // Verifica se i file esistono
                if (File.Exists(prenotazioniFilePath) && File.Exists(clientiFilePath) && File.Exists(insegnantiFilePath))
                {
                    // Assicuriamoci che la directory esista
                    string prenotazioniFreeDir = Path.GetDirectoryName(prenotazioniFreeFilePath);
                    if (!Directory.Exists(prenotazioniFreeDir))
                    {
                        Directory.CreateDirectory(prenotazioniFreeDir);
                    }

                    // MODIFICA: Ricarica esplicitamente i file per assicurarsi di avere i dati più aggiornati
                    var prenotazioniComplete = LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);
                    var clientiList = LoadEncryptedXml<ClientiList>(clientiFilePath);
                    var insegnantiList = LoadEncryptedXml<InsegnantiList>(insegnantiFilePath);

                    // Crea una nuova lista contenente solo le prenotazioni dei prossimi 6 giorni
                    var prenotazioniFiltrate = new List<PrenotazioneExport>();

                    if (prenotazioniComplete.Items != null)
                    {
                        DateTime oggi = DateTime.Today;
                        DateTime limiteFuturo = oggi.AddDays(6);

                        // Filtra le prenotazioni per il periodo richiesto (oggi + 6 giorni)
                        foreach (var prenotazione in prenotazioniComplete.Items.Where(p => p.Data.Date >= oggi && p.Data.Date <= limiteFuturo))
                        {
                            // Ottieni i nomi di cliente e insegnante
                            var cliente = clientiList.Items?.FirstOrDefault(c => c.Id == prenotazione.ClienteId);
                            var insegnante = insegnantiList.Items?.FirstOrDefault(i => i.Id == prenotazione.InsegnanteId);

                            string nomeCliente = cliente != null ? $"{cliente.Nome} {cliente.Cognome}" : "Cliente non trovato";
                            string nomeInsegnante = insegnante != null ? $"{insegnante.Nome} {insegnante.Cognome}" : "Insegnante non trovato";

                            // MODIFICA IMPORTANTE: Converti orari in stringhe in un formato specifico
                            // Usa una notazione sicura che eviti problemi di formattazione
                            string oraInizioStr = "";
                            string oraFineStr = "";

                            // Gestione sicura della conversione TimeSpan->String
                            if (prenotazione.OraInizio != default(TimeSpan))
                            {
                                int ore = prenotazione.OraInizio.Hours;
                                int minuti = prenotazione.OraInizio.Minutes;
                                oraInizioStr = $"{ore:D2}:{minuti:D2}";
                            }

                            if (prenotazione.OraFine != default(TimeSpan))
                            {
                                int ore = prenotazione.OraFine.Hours;
                                int minuti = prenotazione.OraFine.Minutes;
                                oraFineStr = $"{ore:D2}:{minuti:D2}";
                            }

                            // Se, per qualsiasi motivo, gli orari sono ancora vuoti, assegna valori predefiniti
                            if (string.IsNullOrEmpty(oraInizioStr))
                            {
                                oraInizioStr = "10:00";
                            }
                            if (string.IsNullOrEmpty(oraFineStr))
                            {
                                oraFineStr = "11:00";
                            }

                            // Crea una prenotazione con i nomi al posto degli ID
                            var prenotazioneExport = new PrenotazioneExport
                            {
                                Id = prenotazione.Id,
                                Data = prenotazione.Data,
                                OraInizio = oraInizioStr, // MODIFICA: Assegna la stringa formattata
                                OraFine = oraFineStr,     // MODIFICA: Assegna la stringa formattata
                                NomeCliente = nomeCliente,
                                NomeInsegnante = nomeInsegnante,
                                Strumento = prenotazione.Strumento,
                                Stato = prenotazione.Stato,
                                ClienteId = prenotazione.ClienteId,
                                InsegnanteId = prenotazione.InsegnanteId
                            };

                            prenotazioniFiltrate.Add(prenotazioneExport);
                        }
                    }

                    // Creiamo un contenitore per la serializzazione
                    var exportContainer = new PrenotazioniExportList { Items = prenotazioniFiltrate };

                    // Salva una versione decriptata con solo le prenotazioni filtrate e nomi invece di ID
                    // Assicuriamoci che la serializzazione funzioni correttamente
                    XmlSerializer serializer = new XmlSerializer(typeof(PrenotazioniExportList));

                    // MODIFICA: Usiamo impostazioni specifiche per la serializzazione XML
                    XmlWriterSettings settings = new XmlWriterSettings
                    {
                        Indent = true,
                        IndentChars = "  ",
                        NewLineHandling = NewLineHandling.Entitize,
                        Encoding = Encoding.UTF8
                    };

                    using (XmlWriter writer = XmlWriter.Create(prenotazioniFreeFilePath, settings))
                    {
                        // Rimuove il namespace XML predefinito che a volte causa problemi
                        XmlSerializerNamespaces namespaces = new XmlSerializerNamespaces();
                        namespaces.Add("", ""); // Usa namespace vuoto

                        serializer.Serialize(writer, exportContainer, namespaces);
                    }

                    // Verifica che il file sia stato scritto correttamente
                    if (!File.Exists(prenotazioniFreeFilePath) || new FileInfo(prenotazioniFreeFilePath).Length == 0)
                    {
                        throw new Exception("Il file è stato creato ma sembra essere vuoto o danneggiato.");
                    }
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante il salvataggio del file decriptato: {ex.Message}\n\nStack Trace: {ex.StackTrace}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        // Metodo per generare i PDF dei calendari degli insegnanti
        // Metodo per generare i PDF dei calendari degli insegnanti
        private void GeneraCalendariInsegnantiPDF()
        {
            try
            {
                string prenotazioniFilePath = Path.Combine(dataPath, "prenotazioni.xml");
                string clientiFilePath = Path.Combine(dataPath, "clienti.xml");
                string insegnantiFilePath = Path.Combine(dataPath, "insegnanti.xml");
                string calendariPath = Path.Combine(dataPath, calendariInsegnantiPath);

                // Verifica se i file esistono
                if (File.Exists(prenotazioniFilePath) && File.Exists(clientiFilePath) && File.Exists(insegnantiFilePath))
                {
                    // Assicuriamoci che la directory esista
                    if (!Directory.Exists(calendariPath))
                    {
                        Directory.CreateDirectory(calendariPath);
                    }

                    // Carica i dati
                    var prenotazioniList = LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);
                    var clientiList = LoadEncryptedXml<ClientiList>(clientiFilePath);
                    var insegnantiList = LoadEncryptedXml<InsegnantiList>(insegnantiFilePath);

                    // Ottieni la data di oggi e calcola le date delle tre settimane
                    DateTime oggi = DateTime.Today;
                    DateTime inizioSettimanaCorrente = GetStartOfWeek(oggi);
                    DateTime inizioSettimanaProssima = inizioSettimanaCorrente.AddDays(7);
                    DateTime inizioSettimanaDopo = inizioSettimanaProssima.AddDays(7);

                    // Per ogni insegnante, crea un PDF
                    if (insegnantiList.Items != null)
                    {
                        foreach (var insegnante in insegnantiList.Items)
                        {
                            try // Aggiungi un try-catch interno per gestire ogni singolo calendario
                            {
                                string nomeInsegnanteCompleto = $"{insegnante.Nome} {insegnante.Cognome}";
                                string insegnanteFolderPath = Path.Combine(calendariPath, GetSafeFileName(nomeInsegnanteCompleto));

                                // Crea una cartella per l'insegnante se non esiste
                                if (!Directory.Exists(insegnanteFolderPath))
                                {
                                    Directory.CreateDirectory(insegnanteFolderPath);
                                }

                                string pdfPath = Path.Combine(insegnanteFolderPath, $"Calendario_{GetSafeFileName(nomeInsegnanteCompleto)}.pdf");

                                // Crea il PDF
                                CreaPdfPerInsegnante(
                                    pdfPath,
                                    insegnante,
                                    prenotazioniList.Items,
                                    clientiList.Items,
                                    inizioSettimanaCorrente,
                                    inizioSettimanaProssima,
                                    inizioSettimanaDopo
                                );
                            }
                            catch (Exception)
                            {
                                // Ignora gli errori per il singolo insegnante
                                // e continua con gli altri
                                Debug.WriteLine($"Impossibile generare il calendario per l'insegnante {insegnante.Nome} {insegnante.Cognome}");
                            }
                        }
                    }
                }
            }
            catch (Exception ex)
            {
                // Registra l'errore nel log, ma NON mostrare all'utente
                Debug.WriteLine($"Errore durante la generazione dei calendari degli insegnanti: {ex.Message}\n\nStack Trace: {ex.StackTrace}");

                // Commenta la seguente linea per evitare che il messaggio di errore venga mostrato
                // MessageBox.Show($"Errore durante la generazione dei calendari degli insegnanti: {ex.Message}\n\nStack Trace: {ex.StackTrace}",
                //    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void CreaPdfPerInsegnante(
            string pdfPath,
            Insegnante insegnante,
            List<Prenotazione> prenotazioni,
            List<Cliente> clienti,
            DateTime inizioSettimanaCorrente,
            DateTime inizioSettimanaProssima,
            DateTime inizioSettimanaDopo)
        {
            // Filtra le prenotazioni dell'insegnante per le tre settimane
            var prenotazioniInsegnante = prenotazioni
                .Where(p => p.InsegnanteId == insegnante.Id &&
                           p.Data >= inizioSettimanaCorrente &&
                           p.Data < inizioSettimanaDopo.AddDays(7))
                .ToList();

            // Creazione del documento PDF
            Document document = new Document(PageSize.A4.Rotate()); // Orizzontale
            PdfWriter writer = PdfWriter.GetInstance(document, new FileStream(pdfPath, FileMode.Create));
            document.Open();

            // Aggiungi l'intestazione
            string nomeInsegnanteCompleto = $"{insegnante.Nome} {insegnante.Cognome}";
            document.Add(new Paragraph($"Calendario di {nomeInsegnanteCompleto}",
                new iTextSharp.text.Font(iTextSharp.text.Font.FontFamily.HELVETICA, 16, iTextSharp.text.Font.BOLD)));


            // Genera le tre tabelle per le tre settimane
            CreaTabellaPagina(document, insegnante, prenotazioniInsegnante, clienti, inizioSettimanaCorrente, "Settimana Corrente");
            document.NewPage();
            CreaTabellaPagina(document, insegnante, prenotazioniInsegnante, clienti, inizioSettimanaProssima, "Settimana Prossima");
            document.NewPage();
            CreaTabellaPagina(document, insegnante, prenotazioniInsegnante, clienti, inizioSettimanaDopo, "Settimana Dopo");

            document.Close();
        }

        private void CreaTabellaPagina(Document document, Insegnante insegnante, List<Prenotazione> prenotazioni, List<Cliente> clienti, DateTime inizioSettimana, string titoloSettimana)
        {
            // Aggiungi il titolo della settimana
            DateTime fineSettimana = inizioSettimana.AddDays(6);
            string rangeDate = $"{inizioSettimana:dd/MM/yyyy} - {fineSettimana:dd/MM/yyyy}";
            document.Add(new Paragraph($"{titoloSettimana}: {rangeDate}",
                new iTextSharp.text.Font(iTextSharp.text.Font.FontFamily.HELVETICA, 14, iTextSharp.text.Font.BOLD)));
            document.Add(new Paragraph(" ")); // Aggiunge spazio

            // Crea la tabella del calendario
            PdfPTable table = new PdfPTable(8); // 1 colonna per l'ora + 7 colonne per i giorni
            table.WidthPercentage = 80; // Ridotto all'80% della larghezza disponibile

            // Imposta le larghezze relative delle colonne
            float[] widths = { 0.5f, 1f, 1f, 1f, 1f, 1f, 1f, 1f };
            table.SetWidths(widths);

            // Imposta lo spazio extra sopra e sotto la tabella per centrarla
            table.SpacingBefore = 5f;
            table.SpacingAfter = 5f;

            // Riduzione della dimensione del font per adattarsi meglio alla pagina
            iTextSharp.text.Font headerFont = new iTextSharp.text.Font(iTextSharp.text.Font.FontFamily.HELVETICA, 9, iTextSharp.text.Font.BOLD);
            iTextSharp.text.Font cellFont = new iTextSharp.text.Font(iTextSharp.text.Font.FontFamily.HELVETICA, 8);
            iTextSharp.text.Font smallFont = new iTextSharp.text.Font(iTextSharp.text.Font.FontFamily.HELVETICA, 7);

            // Intestazione della tabella
            PdfPCell cellOrario = new PdfPCell(new Phrase("Orario", headerFont));
            cellOrario.BackgroundColor = new BaseColor(220, 220, 220);
            cellOrario.HorizontalAlignment = Element.ALIGN_CENTER;
            cellOrario.VerticalAlignment = Element.ALIGN_MIDDLE;
            table.AddCell(cellOrario);

            // Intestazioni con i nomi dei giorni
            string[] giorni = { "Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato", "Domenica" };

            for (int i = 0; i < 7; i++)
            {
                DateTime dataGiorno = inizioSettimana.AddDays(i);
                string intestazione = $"{giorni[i]}\n{dataGiorno:dd/MM}";
                PdfPCell cell = new PdfPCell(new Phrase(intestazione, headerFont));
                cell.BackgroundColor = new BaseColor(220, 220, 220);
                cell.HorizontalAlignment = Element.ALIGN_CENTER;
                cell.VerticalAlignment = Element.ALIGN_MIDDLE;
                table.AddCell(cell);
            }

            // Righe della tabella per ogni ora (assumiamo dalle 9 alle 21)
            for (int ora = 9; ora <= 22; ora++)
            {
                // Cella dell'ora
                PdfPCell cellOra = new PdfPCell(new Phrase($"{ora}:00", cellFont));
                cellOra.HorizontalAlignment = Element.ALIGN_CENTER;
                cellOra.VerticalAlignment = Element.ALIGN_MIDDLE;
                table.AddCell(cellOra);

                // Celle per i giorni della settimana
                for (int giorno = 0; giorno < 7; giorno++)
                {
                    DateTime dataGiorno = inizioSettimana.AddDays(giorno);

                    // Trova le prenotazioni per questo giorno e ora
                    var prenotazioniCella = prenotazioni.Where(p =>
                        p.Data.Date == dataGiorno.Date &&
                        p.OraInizio.Hours == ora).ToList();

                    string testo = "";
                    foreach (var prenotazione in prenotazioniCella)
                    {
                        // Trova il nome del cliente
                        var cliente = clienti.FirstOrDefault(c => c.Id == prenotazione.ClienteId);
                        string nomeCliente = cliente != null ? $"{cliente.Nome} {cliente.Cognome}" : "Cliente non trovato";

                        // Aggiungi i dettagli della prenotazione
                        testo += $"{nomeCliente} - {prenotazione.Strumento}\n{prenotazione.OraInizio.ToString(@"hh\:mm")} - {prenotazione.OraFine.ToString(@"hh\:mm")}\n";

                        testo += "\n";
                    }

                    PdfPCell cellGiorno = new PdfPCell(new Phrase(testo, smallFont)); // Font più piccolo
                    cellGiorno.MinimumHeight = 30; // Celle un po' più compatte

                    // Se è un giorno festivo o weekend, colora la cella di grigio chiaro
                    if (giorno >= 5) // Sabato e Domenica
                    {
                        cellGiorno.BackgroundColor = new BaseColor(240, 240, 240);
                    }

                    table.AddCell(cellGiorno);
                }
            }

            // Aggiungi la tabella al documento
            document.Add(table);
        }

        // Funzione di utilità per ottenere il primo giorno della settimana (lunedì) per una data specificata
        private DateTime GetStartOfWeek(DateTime date)
        {
            int diff = (7 + (date.DayOfWeek - DayOfWeek.Monday)) % 7;
            return date.AddDays(-1 * diff).Date;
        }

        // Funzione di utilità per ottenere un nome file valido
        private string GetSafeFileName(string fileName)
        {
            return string.Join("_", fileName.Split(Path.GetInvalidFileNameChars()));
        }

        // Classi per il formato di esportazione
        [Serializable]
        public class PrenotazioniExportList
        {
            public List<PrenotazioneExport> Items { get; set; } = new List<PrenotazioneExport>();
        }

        [Serializable]
        public class PrenotazioneExport
        {
            public int Id { get; set; }
            public DateTime Data { get; set; }

            // IMPORTANTE: Usa stringhe invece di TimeSpan per essere sicuri che vengano serializzate correttamente
            public string OraInizio { get; set; }
            public string OraFine { get; set; }

            public string NomeCliente { get; set; }
            public string NomeInsegnante { get; set; }
            public string Strumento { get; set; }
            public string Note { get; set; }
            public StatoLezione Stato { get; set; }
            public int ClienteId { get; set; }
            public int InsegnanteId { get; set; }
        }

        private void CreateBackupArchive()
        {
            try
            {
                string timestamp = DateTime.Now.ToString("yyyyMMdd_HHmmss");
                string backupFileName = $"Backup_{timestamp}.zip";
                string backupFilePath = Path.Combine(backupPath, backupFileName);

                // Creiamo la directory di backup se non esiste
                if (!Directory.Exists(backupPath))
                {
                    Directory.CreateDirectory(backupPath);
                }

                // Creiamo una directory temporanea per i file di backup
                string tempDir = Path.Combine(Path.GetTempPath(), Guid.NewGuid().ToString());
                Directory.CreateDirectory(tempDir);

                try
                {
                    // Copiamo tutti i file XML nella directory temporanea
                    foreach (var file in Directory.GetFiles(dataPath, "*.xml"))
                    {
                        File.Copy(file, Path.Combine(tempDir, Path.GetFileName(file)), true);
                    }

                    // Creiamo un file ZIP
                    if (File.Exists(backupFilePath))
                        File.Delete(backupFilePath);

                    ZipFile.CreateFromDirectory(tempDir, backupFilePath);
                }
                finally
                {
                    // Puliamo la directory temporanea
                    try
                    {
                        if (Directory.Exists(tempDir))
                            Directory.Delete(tempDir, true);
                    }
                    catch { /* Ignoriamo errori di pulizia */ }
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante la creazione del backup: {ex.Message}",
                    "Errore Backup", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        // Metodi statici di utilità per la gestione dei file XML criptati

        public static void SaveEncryptedXml<T>(T data, string filePath, string password = "EasyBooking!2025")
        {
            try
            {
                // Creiamo la directory se non esiste
                string directory = Path.GetDirectoryName(filePath);
                if (directory != null && !Directory.Exists(directory))
                    Directory.CreateDirectory(directory);

                // Serializziamo in stringa
                XmlSerializer serializer = new XmlSerializer(typeof(T));
                string xmlString;

                using (StringWriter writer = new StringWriter())
                {
                    serializer.Serialize(writer, data);
                    xmlString = writer.ToString();
                }

                // Criptiamo e salviamo
                byte[] encryptedData = EncryptStringToBytes(xmlString, password);
                File.WriteAllBytes(filePath, encryptedData);
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante il salvataggio del file XML: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        public static T LoadEncryptedXml<T>(string filePath, string password = "EasyBooking!2025") where T : new()
        {
            if (!File.Exists(filePath))
            {
                // Crea una nuova istanza dell'oggetto
                T newObj = new T();

                try
                {
                    // Crea la directory se non esiste
                    string directory = Path.GetDirectoryName(filePath);
                    if (directory != null && !Directory.Exists(directory))
                    {
                        Directory.CreateDirectory(directory);
                    }

                    // Salva il file vuoto
                    SaveEncryptedXml(newObj, filePath, password);
                }
                catch (Exception ex)
                {
                    // Solo log dell'errore, non mostriamo messagebox qui perché 
                    // questo metodo potrebbe essere chiamato durante il caricamento dell'UI
                    Console.WriteLine($"Errore durante la creazione del file XML vuoto: {ex.Message}");
                }

                return newObj;
            }

            try
            {
                // Leggiamo il file criptato
                byte[] encryptedData = File.ReadAllBytes(filePath);

                // Decriptiamo
                string xmlString = DecryptStringFromBytes(encryptedData, password);

                // Deserializziamo
                XmlSerializer serializer = new XmlSerializer(typeof(T));
                using (StringReader reader = new StringReader(xmlString))
                {
                    return (T)serializer.Deserialize(reader);
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante la lettura del file XML: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                return new T();
            }
        }

        private static byte[] EncryptStringToBytes(string plainText, string password)
        {
            byte[] encrypted;

            using (Aes aesAlg = Aes.Create())
            {
                // Generiamo la chiave e l'IV dalla password
                using (var derive = new Rfc2898DeriveBytes(password, Encoding.UTF8.GetBytes("EasyBookingSalt")))
                {
                    aesAlg.Key = derive.GetBytes(32);
                    aesAlg.IV = derive.GetBytes(16);
                }

                ICryptoTransform encryptor = aesAlg.CreateEncryptor(aesAlg.Key, aesAlg.IV);

                using (MemoryStream msEncrypt = new MemoryStream())
                {
                    using (CryptoStream csEncrypt = new CryptoStream(msEncrypt, encryptor, CryptoStreamMode.Write))
                    {
                        using (StreamWriter swEncrypt = new StreamWriter(csEncrypt))
                        {
                            swEncrypt.Write(plainText);
                        }
                        encrypted = msEncrypt.ToArray();
                    }
                }
            }

            return encrypted;
        }

        private static string DecryptStringFromBytes(byte[] cipherText, string password)
        {
            string plaintext = null;

            using (Aes aesAlg = Aes.Create())
            {
                // Generiamo la chiave e l'IV dalla password
                using (var derive = new Rfc2898DeriveBytes(password, Encoding.UTF8.GetBytes("EasyBookingSalt")))
                {
                    aesAlg.Key = derive.GetBytes(32);
                    aesAlg.IV = derive.GetBytes(16);
                }

                ICryptoTransform decryptor = aesAlg.CreateDecryptor(aesAlg.Key, aesAlg.IV);

                using (MemoryStream msDecrypt = new MemoryStream(cipherText))
                {
                    using (CryptoStream csDecrypt = new CryptoStream(msDecrypt, decryptor, CryptoStreamMode.Read))
                    {
                        using (StreamReader srDecrypt = new StreamReader(csDecrypt))
                        {
                            plaintext = srDecrypt.ReadToEnd();
                        }
                    }
                }
            }

            return plaintext;
        }

        private void lblActivityName_Click(object sender, EventArgs e)
        {

        }

        private void panelLogo_Paint(object sender, PaintEventArgs e)
        {

        }

        // Aggiungi questa variabile di classe nel MainForm
        private Button btnVerificaAggiornamenti;

        // Aggiungi questo metodo che inizializza il pulsante nel costruttore MainForm() 
        // dopo InitializeComponent();
        private void InitVerificaAggiornamenti()
        {
            btnVerificaAggiornamenti = new Button();
            btnVerificaAggiornamenti.Text = "Verifica Aggiornamenti";
            btnVerificaAggiornamenti.Size = new Size(150, 30);
            btnVerificaAggiornamenti.BackColor = Color.FromArgb(50, 50, 50);
            btnVerificaAggiornamenti.ForeColor = Color.White;
            btnVerificaAggiornamenti.FlatStyle = FlatStyle.Flat;
            btnVerificaAggiornamenti.Anchor = AnchorStyles.Bottom | AnchorStyles.Left;
            btnVerificaAggiornamenti.Location = new Point(30, this.ClientSize.Height - 40);
            btnVerificaAggiornamenti.Click += BtnVerificaAggiornamenti_Click;


            // Aggiunge il pulsante al form
            this.Controls.Add(btnVerificaAggiornamenti);

            // Assicura che il pulsante sia sempre visibile
            btnVerificaAggiornamenti.BringToFront();
        }

        // Aggiungi questo metodo per gestire il click del pulsante
        private void BtnVerificaAggiornamenti_Click(object sender, EventArgs e)
        {
            try
            {
                // Ottiene il percorso dell'eseguibile corrente
                string applicationPath = Application.ExecutablePath;
                string applicationDir = Path.GetDirectoryName(applicationPath);
                string updaterPath = Path.Combine(applicationDir, "EB-Updater.exe");

                // Verifica se il file esiste
                if (File.Exists(updaterPath))
                {
                    Process.Start(updaterPath);
                }
                else
                {
                    MessageBox.Show("Aggiornamento non disponibile. File EB-Updater.exe non trovato.",
                        "Aggiornamento", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante l'avvio dell'aggiornamento: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }
    }
}