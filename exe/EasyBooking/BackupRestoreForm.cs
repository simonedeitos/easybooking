using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.IO;
using System.Linq;
using System.Windows.Forms;
using System.IO.Compression;
using System.Threading;
using System.Threading.Tasks;
using System.Security.Cryptography;
using System.Diagnostics;

namespace EasyBooking
{
    public partial class BackupRestoreForm : Form
    {
        private string dataPath;
        private List<string> filesToBackup;
        private string backupPath;
        private CancellationTokenSource cts;

        // Struttura dati per memorizzare le informazioni sui backup
        private List<BackupInfo> backupList;

        public BackupRestoreForm(string dataPath, string backupPath)
        {
            InitializeComponent();

            this.dataPath = dataPath;
            this.backupPath = backupPath;

            // Verifica che la cartella di backup esista, altrimenti la crea
            if (!Directory.Exists(backupPath))
            {
                try
                {
                    Directory.CreateDirectory(backupPath);
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"Impossibile creare la cartella di backup: {ex.Message}",
                        "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                    this.Close();
                    return;
                }
            }

            // Inizializza la lista dei file da includere nel backup
            filesToBackup = new List<string>
            {
                "clienti.xml",
                "insegnanti.xml",
                "prenotazioni.xml",
                "acquisti.xml",
                "pacchetti.xml",
                "impostazioni-generali.xml",
                "strumenti.xml"
            };

            // Inizializza la lista dei backup
            backupList = new List<BackupInfo>();

            // Configura il timer per eseguire il backup automatico
            timerBackupAutomatico.Interval = 3600000; // 1 ora in millisecondi
            timerBackupAutomatico.Tick += TimerBackupAutomatico_Tick;

            // Aggiorna l'elenco dei backup disponibili
            UpdateBackupList();

            // Setta l'ora del backup automatico alle 23:00 di default
            dtpOraBackup.Value = DateTime.Today.AddHours(23);
        }

        private void UpdateBackupList()
        {
            try
            {
                // Pulisce la lista dei backup
                backupList.Clear();

                // Ottiene tutti i file di backup nella cartella di backup
                string[] backupFiles = Directory.GetFiles(backupPath, "*.ebak");

                foreach (string backupFile in backupFiles)
                {
                    // Ottiene il nome del file e la data di creazione
                    FileInfo fileInfo = new FileInfo(backupFile);
                    string fileName = fileInfo.Name;
                    DateTime creationTime = fileInfo.CreationTime;
                    long fileSize = fileInfo.Length;

                    // Estrae la data dal nome del file (assumendo un formato specifico)
                    DateTime backupDate;
                    // Prova ad estrarre la data dal nome del file (formato: backup_yyyyMMddHHmmss.ebak)
                    if (!TryExtractDateFromFileName(fileName, out backupDate))
                    {
                        backupDate = creationTime; // Usa la data di creazione del file se non riesce a estrarre la data dal nome
                    }

                    // Crea un oggetto BackupInfo
                    BackupInfo backupInfo = new BackupInfo
                    {
                        FilePath = backupFile,
                        Date = backupDate,
                        Size = fileSize,
                        Description = GetBackupDescription(backupFile)
                    };

                    // Aggiunge il backup alla lista
                    backupList.Add(backupInfo);
                }

                // Ordina la lista per data decrescente (più recenti prima)
                backupList = backupList.OrderByDescending(b => b.Date).ToList();

                // Aggiorna la ListBox
                lstBackup.Items.Clear();
                foreach (BackupInfo backup in backupList)
                {
                    string sizeFormatted = FormatSize(backup.Size);
                    string description = string.IsNullOrEmpty(backup.Description) ? "Backup manuale" : backup.Description;
                    string displayText = $"{backup.Date:dd/MM/yyyy HH:mm:ss} - {sizeFormatted} - {description}";
                    lstBackup.Items.Add(displayText);
                }

                // Seleziona il backup più recente se ce ne sono
                if (lstBackup.Items.Count > 0)
                {
                    lstBackup.SelectedIndex = 0;
                    UpdateSelectedBackupInfo();
                }
                else
                {
                    // Nessun backup disponibile
                    txtInfo.Text = "Nessun backup disponibile.";
                    btnRestore.Enabled = false;
                    btnDelete.Enabled = false;
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante l'aggiornamento della lista dei backup: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private bool TryExtractDateFromFileName(string fileName, out DateTime date)
        {
            // Prova ad estrarre la data dal nome del file (formato: backup_yyyyMMddHHmmss.ebak)
            date = DateTime.MinValue;

            try
            {
                string dateStr = fileName.Replace("backup_", "").Replace(".ebak", "");
                if (dateStr.Length >= 14)
                {
                    int year = int.Parse(dateStr.Substring(0, 4));
                    int month = int.Parse(dateStr.Substring(4, 2));
                    int day = int.Parse(dateStr.Substring(6, 2));
                    int hour = int.Parse(dateStr.Substring(8, 2));
                    int minute = int.Parse(dateStr.Substring(10, 2));
                    int second = int.Parse(dateStr.Substring(12, 2));

                    date = new DateTime(year, month, day, hour, minute, second);
                    return true;
                }
            }
            catch
            {
                // Ignora gli errori e usa la data di creazione del file
            }

            return false;
        }

        private string FormatSize(long bytes)
        {
            string[] suffixes = { "B", "KB", "MB", "GB", "TB" };
            int counter = 0;
            decimal number = (decimal)bytes;

            while (Math.Round(number / 1024) >= 1)
            {
                number = number / 1024;
                counter++;
            }

            return string.Format("{0:n2} {1}", number, suffixes[counter]);
        }

        private string GetBackupDescription(string backupFile)
        {
            // Prova a leggere la descrizione dal file di backup
            try
            {
                using (ZipArchive archive = ZipFile.OpenRead(backupFile))
                {
                    ZipArchiveEntry entry = archive.GetEntry("description.txt");
                    if (entry != null)
                    {
                        using (StreamReader reader = new StreamReader(entry.Open()))
                        {
                            return reader.ReadToEnd();
                        }
                    }
                }
            }
            catch
            {
                // Ignora gli errori e restituisci una descrizione predefinita
            }

            return string.Empty;
        }

        private void UpdateSelectedBackupInfo()
        {
            if (lstBackup.SelectedIndex >= 0 && lstBackup.SelectedIndex < backupList.Count)
            {
                BackupInfo selectedBackup = backupList[lstBackup.SelectedIndex];

                // Aggiorna le informazioni sul backup selezionato
                txtInfo.Text = $"Data: {selectedBackup.Date:dd/MM/yyyy HH:mm:ss}\r\n" +
                               $"Dimensione: {FormatSize(selectedBackup.Size)}\r\n" +
                               $"Descrizione: {(string.IsNullOrEmpty(selectedBackup.Description) ? "Backup manuale" : selectedBackup.Description)}";

                // Abilita i pulsanti Ripristina e Elimina
                btnRestore.Enabled = true;
                btnDelete.Enabled = true;
            }
            else
            {
                // Nessun backup selezionato
                txtInfo.Text = "Nessun backup selezionato.";
                btnRestore.Enabled = false;
                btnDelete.Enabled = false;
            }
        }

        private void lstBackup_SelectedIndexChanged(object sender, EventArgs e)
        {
            UpdateSelectedBackupInfo();
        }

        private void btnBackup_Click(object sender, EventArgs e)
        {
            // Chiedi una descrizione per il backup
            string description = string.Empty;

            using (var inputDialog = new InputDialog("Descrizione Backup", "Inserisci una descrizione per questo backup (opzionale):"))
            {
                if (inputDialog.ShowDialog() == DialogResult.OK)
                {
                    description = inputDialog.InputText;
                }
            }

            // Avvia il processo di backup
            PerformBackup(description);
        }

        private async void PerformBackup(string description)
        {
            try
            {
                // Disabilita i controlli durante il backup
                SetControlsEnabled(false);

                // Mostra la progress bar e prepara la cancellazione
                progressBar.Visible = true;
                progressBar.Value = 0;
                lblStatus.Text = "Preparazione backup...";
                lblStatus.Visible = true;
                btnCancel.Visible = true;
                btnCancel.Enabled = true;

                // Crea un token di cancellazione
                cts = new CancellationTokenSource();

                // Crea il nome del file di backup
                string timestamp = DateTime.Now.ToString("yyyyMMddHHmmss");
                string backupFileName = $"backup_{timestamp}.ebak";
                string backupFilePath = Path.Combine(backupPath, backupFileName);

                // Esegui il backup in un task separato
                bool success = await Task.Run(() => CreateBackupFile(backupFilePath, description, cts.Token), cts.Token);

                if (success)
                {
                    // Aggiorna l'elenco dei backup
                    UpdateBackupList();

                    MessageBox.Show("Backup completato con successo!",
                        "Backup", MessageBoxButtons.OK, MessageBoxIcon.Information);
                }
                else if (!cts.Token.IsCancellationRequested)
                {
                    // Se non è stato cancellato dall'utente, c'è stato un errore
                    MessageBox.Show("Errore durante la creazione del backup.",
                        "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante la creazione del backup: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
            finally
            {
                // Ripristina i controlli
                SetControlsEnabled(true);
                progressBar.Visible = false;
                lblStatus.Visible = false;
                btnCancel.Visible = false;

                // Elimina il token di cancellazione
                cts?.Dispose();
                cts = null;
            }
        }

        private bool CreateBackupFile(string backupFilePath, string description, CancellationToken cancellationToken)
        {
            try
            {
                // Crea un file temporaneo ZIP
                string tempFile = Path.GetTempFileName();

                using (FileStream fs = new FileStream(tempFile, FileMode.Create))
                using (ZipArchive archive = new ZipArchive(fs, ZipArchiveMode.Create, true))
                {
                    // Aggiungi una descrizione se fornita
                    if (!string.IsNullOrEmpty(description))
                    {
                        ZipArchiveEntry descEntry = archive.CreateEntry("description.txt");
                        using (StreamWriter writer = new StreamWriter(descEntry.Open()))
                        {
                            writer.Write(description);
                        }
                    }

                    // Aggiungi un file di metadati con la data e la versione
                    ZipArchiveEntry metaEntry = archive.CreateEntry("metadata.txt");
                    using (StreamWriter writer = new StreamWriter(metaEntry.Open()))
                    {
                        writer.WriteLine($"EasyBooking Backup");
                        writer.WriteLine($"Date: {DateTime.Now:yyyy-MM-dd HH:mm:ss}");
                        writer.WriteLine($"Version: 1.0");
                    }

                    // Conta i file da processare
                    int totalFiles = filesToBackup.Count;
                    int processedFiles = 0;

                    // Aggiungi ogni file al backup
                    foreach (string fileName in filesToBackup)
                    {
                        // Verifica la cancellazione
                        if (cancellationToken.IsCancellationRequested)
                        {
                            return false;
                        }

                        string filePath = Path.Combine(dataPath, fileName);

                        // Verifica se il file esiste
                        if (File.Exists(filePath))
                        {
                            // Aggiorna lo stato
                            ReportProgress((int)((float)processedFiles / totalFiles * 100), $"Backup di {fileName}...");

                            // Aggiungi il file all'archivio
                            ZipArchiveEntry entry = archive.CreateEntry(fileName);

                            using (FileStream fileStream = new FileStream(filePath, FileMode.Open, FileAccess.Read))
                            using (Stream entryStream = entry.Open())
                            {
                                fileStream.CopyTo(entryStream);
                            }
                        }

                        // Incrementa il contatore dei file processati
                        processedFiles++;
                    }

                    // Aggiorna lo stato
                    ReportProgress(100, "Finalizzando il backup...");
                }

                // Verifica la cancellazione
                if (cancellationToken.IsCancellationRequested)
                {
                    // Elimina il file temporaneo
                    try { File.Delete(tempFile); } catch { }
                    return false;
                }

                // Sposta il file temporaneo nella posizione finale
                File.Copy(tempFile, backupFilePath, true);
                File.Delete(tempFile);

                return true;
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Errore durante la creazione del backup: {ex.Message}");
                return false;
            }
        }

        private void ReportProgress(int progressValue, string statusText)
        {
            // Aggiorna l'interfaccia utente dal thread di background
            if (progressBar.InvokeRequired)
            {
                progressBar.Invoke(new Action(() =>
                {
                    progressBar.Value = progressValue;
                    lblStatus.Text = statusText;
                }));
            }
            else
            {
                progressBar.Value = progressValue;
                lblStatus.Text = statusText;
            }
        }

        private void btnRestore_Click(object sender, EventArgs e)
        {
            // Verifica che ci sia un backup selezionato
            if (lstBackup.SelectedIndex < 0 || lstBackup.SelectedIndex >= backupList.Count)
            {
                MessageBox.Show("Seleziona un backup da ripristinare.",
                    "Ripristino", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            // Chiedi conferma all'utente
            DialogResult result = MessageBox.Show(
                "Sei sicuro di voler ripristinare questo backup?\n\n" +
                "I dati attuali verranno sovrascritti.",
                "Conferma Ripristino",
                MessageBoxButtons.YesNo,
                MessageBoxIcon.Warning);

            if (result == DialogResult.Yes)
            {
                // Avvia il processo di ripristino
                BackupInfo selectedBackup = backupList[lstBackup.SelectedIndex];
                PerformRestore(selectedBackup);
            }
        }

        private async void PerformRestore(BackupInfo backup)
        {
            try
            {
                // Disabilita i controlli durante il ripristino
                SetControlsEnabled(false);

                // Mostra la progress bar e prepara la cancellazione
                progressBar.Visible = true;
                progressBar.Value = 0;
                lblStatus.Text = "Preparazione ripristino...";
                lblStatus.Visible = true;
                btnCancel.Visible = true;
                btnCancel.Enabled = true;

                // Crea un token di cancellazione
                cts = new CancellationTokenSource();

                // Esegui il ripristino in un task separato
                bool success = await Task.Run(() => RestoreBackupFile(backup.FilePath, cts.Token), cts.Token);

                if (success)
                {
                    MessageBox.Show("Ripristino completato con successo!\n\n" +
                        "L'applicazione verrà riavviata per applicare le modifiche.",
                        "Ripristino", MessageBoxButtons.OK, MessageBoxIcon.Information);

                    // Riavvia l'applicazione
                    Application.Restart();
                }
                else if (!cts.Token.IsCancellationRequested)
                {
                    // Se non è stato cancellato dall'utente, c'è stato un errore
                    MessageBox.Show("Errore durante il ripristino del backup.",
                        "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante il ripristino del backup: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
            finally
            {
                // Ripristina i controlli
                SetControlsEnabled(true);
                progressBar.Visible = false;
                lblStatus.Visible = false;
                btnCancel.Visible = false;

                // Elimina il token di cancellazione
                cts?.Dispose();
                cts = null;
            }
        }

        private bool RestoreBackupFile(string backupFilePath, CancellationToken cancellationToken)
        {
            try
            {
                // Apri il file di backup
                using (ZipArchive archive = ZipFile.OpenRead(backupFilePath))
                {
                    // Conta i file da ripristinare
                    int totalEntries = archive.Entries.Count(e => filesToBackup.Contains(e.Name));
                    int processedEntries = 0;

                    // Ripristina ogni file
                    foreach (ZipArchiveEntry entry in archive.Entries)
                    {
                        // Salta i file che non sono nell'elenco dei file da ripristinare
                        if (!filesToBackup.Contains(entry.Name))
                        {
                            continue;
                        }

                        // Verifica la cancellazione
                        if (cancellationToken.IsCancellationRequested)
                        {
                            return false;
                        }

                        // Aggiorna lo stato
                        ReportProgress((int)((float)processedEntries / totalEntries * 100), $"Ripristino di {entry.Name}...");

                        // Percorso del file da ripristinare
                        string filePath = Path.Combine(dataPath, entry.Name);

                        // Crea una copia di backup del file corrente (se esiste)
                        if (File.Exists(filePath))
                        {
                            File.Copy(filePath, filePath + ".bak", true);
                        }

                        // Estrai il file dall'archivio
                        entry.ExtractToFile(filePath, true);

                        // Incrementa il contatore dei file processati
                        processedEntries++;
                    }

                    // Aggiorna lo stato
                    ReportProgress(100, "Finalizzando il ripristino...");
                }

                return true;
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Errore durante il ripristino del backup: {ex.Message}");
                return false;
            }
        }

        private void btnDelete_Click(object sender, EventArgs e)
        {
            // Verifica che ci sia un backup selezionato
            if (lstBackup.SelectedIndex < 0 || lstBackup.SelectedIndex >= backupList.Count)
            {
                MessageBox.Show("Seleziona un backup da eliminare.",
                    "Elimina", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            // Chiedi conferma all'utente
            DialogResult result = MessageBox.Show(
                "Sei sicuro di voler eliminare questo backup?",
                "Conferma Eliminazione",
                MessageBoxButtons.YesNo,
                MessageBoxIcon.Question);

            if (result == DialogResult.Yes)
            {
                // Elimina il backup
                try
                {
                    BackupInfo selectedBackup = backupList[lstBackup.SelectedIndex];
                    File.Delete(selectedBackup.FilePath);

                    // Aggiorna l'elenco dei backup
                    UpdateBackupList();

                    MessageBox.Show("Backup eliminato con successo!",
                        "Elimina", MessageBoxButtons.OK, MessageBoxIcon.Information);
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"Errore durante l'eliminazione del backup: {ex.Message}",
                        "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
        }

        private void btnCancel_Click(object sender, EventArgs e)
        {
            // Cancella l'operazione corrente
            cts?.Cancel();
            btnCancel.Enabled = false;
            lblStatus.Text = "Annullamento in corso...";
        }

        private void SetControlsEnabled(bool enabled)
        {
            // Abilita o disabilita i controlli durante un'operazione
            lstBackup.Enabled = enabled;
            btnBackup.Enabled = enabled;
            btnRestore.Enabled = enabled && lstBackup.SelectedIndex >= 0;
            btnDelete.Enabled = enabled && lstBackup.SelectedIndex >= 0;
            chkBackupAutomatico.Enabled = enabled;
            dtpOraBackup.Enabled = enabled && chkBackupAutomatico.Checked;
        }

        private void chkBackupAutomatico_CheckedChanged(object sender, EventArgs e)
        {
            // Abilita o disabilita il timer e il controllo dell'ora
            dtpOraBackup.Enabled = chkBackupAutomatico.Checked;

            if (chkBackupAutomatico.Checked)
            {
                // Calcola il tempo fino al prossimo backup
                ScheduleNextAutomaticBackup();

                // Avvia il timer
                timerBackupAutomatico.Start();
            }
            else
            {
                // Ferma il timer
                timerBackupAutomatico.Stop();
            }
        }

        private void dtpOraBackup_ValueChanged(object sender, EventArgs e)
        {
            // Se il backup automatico è abilitato, ricalcola il timer
            if (chkBackupAutomatico.Checked)
            {
                ScheduleNextAutomaticBackup();
            }
        }

        private void ScheduleNextAutomaticBackup()
        {
            // Ottiene l'ora selezionata
            DateTime selectedTime = dtpOraBackup.Value;

            // Calcola la data e ora del prossimo backup
            DateTime now = DateTime.Now;
            DateTime nextBackup = new DateTime(now.Year, now.Month, now.Day, selectedTime.Hour, selectedTime.Minute, 0);

            // Se l'ora è già passata, imposta il backup per domani
            if (nextBackup <= now)
            {
                nextBackup = nextBackup.AddDays(1);
            }

            // Calcola il tempo in millisecondi fino al prossimo backup
            double millisecondsUntilBackup = (nextBackup - now).TotalMilliseconds;

            // Imposta l'intervallo del timer (massimo Int32.MaxValue)
            timerBackupAutomatico.Interval = millisecondsUntilBackup <= Int32.MaxValue ? (int)millisecondsUntilBackup : Int32.MaxValue;

            // Aggiorna il testo dell'ora
            lblBackupAutomatico.Text = $"Prossimo backup automatico: {nextBackup:dd/MM/yyyy HH:mm:ss}";
            lblBackupAutomatico.Visible = true;
        }

        private void TimerBackupAutomatico_Tick(object sender, EventArgs e)
        {
            // Esegue il backup automatico
            PerformBackup("Backup automatico");

            // Reimposta il timer per il prossimo giorno
            ScheduleNextAutomaticBackup();
        }

        private void BackupRestoreForm_FormClosing(object sender, FormClosingEventArgs e)
        {
            // Annulla qualsiasi operazione in corso
            if (cts != null && !cts.IsCancellationRequested)
            {
                // Se c'è un'operazione in corso, chiedi conferma all'utente
                DialogResult result = MessageBox.Show(
                    "C'è un'operazione in corso. Sei sicuro di voler uscire?",
                    "Conferma",
                    MessageBoxButtons.YesNo,
                    MessageBoxIcon.Question);

                if (result == DialogResult.Yes)
                {
                    // Annulla l'operazione
                    cts.Cancel();
                }
                else
                {
                    // Annulla la chiusura del form
                    e.Cancel = true;
                }
            }
        }

        private void btnOpenBackupFolder_Click(object sender, EventArgs e)
        {
            try
            {
                // Apre la cartella di backup in Explorer
                Process.Start(backupPath);
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Impossibile aprire la cartella dei backup: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }
    }

    // Classe per memorizzare le informazioni sui backup
    public class BackupInfo
    {
        public string FilePath { get; set; }
        public DateTime Date { get; set; }
        public long Size { get; set; }
        public string Description { get; set; }
    }

    // Dialog personalizzato per l'input di testo
    public class InputDialog : Form
    {
        // Risolve l'ambiguità qualificando pienamente i tipi
        private System.Windows.Forms.TextBox txtInput;
        private System.Windows.Forms.Button btnOK;
        private System.Windows.Forms.Button btnCancel;
        private System.Windows.Forms.Label lblPrompt;

        public string InputText { get { return txtInput.Text; } }

        public InputDialog(string title, string prompt)
        {
            this.Text = title;
            this.FormBorderStyle = FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.MinimizeBox = false;
            this.StartPosition = FormStartPosition.CenterParent;
            this.Size = new Size(400, 150);
            this.Font = new Font("Segoe UI", 9F);

            lblPrompt = new System.Windows.Forms.Label();
            lblPrompt.Text = prompt;
            lblPrompt.Location = new Point(10, 10);
            lblPrompt.Size = new Size(380, 20);

            txtInput = new System.Windows.Forms.TextBox();
            txtInput.Location = new Point(10, 40);
            txtInput.Size = new Size(365, 23);

            btnOK = new System.Windows.Forms.Button();
            btnOK.DialogResult = DialogResult.OK;
            btnOK.Text = "OK";
            btnOK.Location = new Point(200, 75);
            btnOK.Size = new Size(80, 25);

            btnCancel = new System.Windows.Forms.Button();
            btnCancel.DialogResult = DialogResult.Cancel;
            btnCancel.Text = "Annulla";
            btnCancel.Location = new Point(290, 75);
            btnCancel.Size = new Size(80, 25);

            this.Controls.Add(lblPrompt);
            this.Controls.Add(txtInput);
            this.Controls.Add(btnOK);
            this.Controls.Add(btnCancel);

            this.AcceptButton = btnOK;
            this.CancelButton = btnCancel;
        }
    }
}