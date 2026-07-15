using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.IO;
using System.Linq;
using System.Windows.Forms;
using System.Xml.Serialization;
using Microsoft.Win32;

namespace EasyBooking
{
    public partial class ImpostazioniControl : UserControl
    {
        private string dataPath;
        private StrumentiList strumenti;
        private ImpostazioniGenerali impostazioni;
        private BindingSource bindingSourceStrumenti;
        private Strumento selectedStrumento;

        // Percorsi dei file XML
        private string strumentiFilePath;
        private string impostazioniFilePath;

        public ImpostazioniControl(string dataPath)
        {
            InitializeComponent();
            this.dataPath = dataPath;
            strumentiFilePath = Path.Combine(dataPath, "strumenti.xml");
            impostazioniFilePath = Path.Combine(dataPath, "impostazioni-generali.xml");
            LoadData();
            SetupDataGridViews();
            LoadPercorsi();
            SetupEsportaMenu();
        }

        private void LoadData()
        {
            // Carica i dati degli strumenti dal file XML
            strumenti = MainForm.LoadEncryptedXml<StrumentiList>(strumentiFilePath);

            if (strumenti == null || strumenti.Items == null)
            {
                strumenti = new StrumentiList();
            }

            // Carica le impostazioni generali dal file XML
            impostazioni = MainForm.LoadEncryptedXml<ImpostazioniGenerali>(impostazioniFilePath);

            if (impostazioni == null)
            {
                // Crea impostazioni predefinite
                impostazioni = new ImpostazioniGenerali
                {
                    LunAttivo = true,
                    MarAttivo = true,
                    MerAttivo = true,
                    GioAttivo = true,
                    VenAttivo = true,
                    SabAttivo = false,
                    DomAttivo = false,
                    MattInizio = 9,
                    MattFine = 13,
                    PomInizio = 15,
                    PomFine = 21,
                    DurataLezioneDefault = 60,
                    ProgrammazioneFreePath = "programmazione-free",
                    CalendariInsegnantiPath = "calendari insegnanti",
                    GiorniRetenzionBackup = 30 // Valore predefinito per la retention dei backup
                };
            }
        }

        private void SetupDataGridViews()
        {
            try
            {
                // Verifica che strumenti e items non siano null prima di configurare il DataGridView
                if (strumenti == null)
                {
                    strumenti = new StrumentiList();
                }

                if (strumenti.Items == null)
                {
                    strumenti.Items = new List<Strumento>();
                }

                // Configura la griglia degli strumenti
                if (bindingSourceStrumenti == null)
                {
                    bindingSourceStrumenti = new BindingSource();
                }

                bindingSourceStrumenti.DataSource = strumenti.Items;

                // Verifica che dgvStrumenti non sia null
                if (dgvStrumenti == null)
                {
                    MessageBox.Show("Il controllo DataGridView non è inizializzato correttamente.",
                        "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                    return;
                }

                dgvStrumenti.DataSource = bindingSourceStrumenti;

                // Verifica che le colonne esistano prima di configurarle
                if (dgvStrumenti.Columns.Count > 0)
                {
                    // Verifica che ogni colonna esista prima di configurarla
                    if (dgvStrumenti.Columns.Contains("Id"))
                        dgvStrumenti.Columns["Id"].Visible = false;

                    if (dgvStrumenti.Columns.Contains("Strumenti"))
                        dgvStrumenti.Columns["Strumenti"].Visible = false;

                    if (dgvStrumenti.Columns.Contains("Nome"))
                    {
                        dgvStrumenti.Columns["Nome"].HeaderText = "Nome";
                        dgvStrumenti.Columns["Nome"].Width = 100;
                    }

                    if (dgvStrumenti.Columns.Contains("Cognome"))
                    {
                        dgvStrumenti.Columns["Cognome"].HeaderText = "Cognome";
                        dgvStrumenti.Columns["Cognome"].Width = 100;
                    }

                    if (dgvStrumenti.Columns.Contains("Telefono"))
                    {
                        dgvStrumenti.Columns["Telefono"].HeaderText = "Telefono";
                        dgvStrumenti.Columns["Telefono"].Width = 100;
                    }

                    if (dgvStrumenti.Columns.Contains("Email"))
                    {
                        dgvStrumenti.Columns["Email"].HeaderText = "Email";
                        dgvStrumenti.Columns["Email"].Width = 150;
                    }

                    if (dgvStrumenti.Columns.Contains("TariffaOraria"))
                    {
                        dgvStrumenti.Columns["TariffaOraria"].HeaderText = "Tariffa Oraria";
                        dgvStrumenti.Columns["TariffaOraria"].Width = 80;
                        dgvStrumenti.Columns["TariffaOraria"].DefaultCellStyle.Format = "c";
                    }
                }

                // Disabilita il form di dettaglio inizialmente
                DisableDetailForm();
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante la configurazione della griglia: {ex.Message}\nStackTrace: {ex.StackTrace}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void LoadMesi()
        {
            // Metodo vuoto per compatibilità
            // In questa classe non è necessario
        }

        private void SetupEsportaMenu()
        {
            // Metodo vuoto per compatibilità
            // In questa classe non è necessario
        }

        private void LoadPercorsi()
        {
            try
            {
                // Carica i percorsi dal registro
                using (RegistryKey key = Registry.CurrentUser.OpenSubKey(@"SOFTWARE\EasyBooking", false))
                {
                    if (key != null)
                    {
                        string dataPath = (string)key.GetValue("DataPath", "");
                        string backupPath = (string)key.GetValue("BackupPath", "");
                        string activityName = (string)key.GetValue("ActivityName", "Scuola di Musica");
                        string logoPath = (string)key.GetValue("LogoPath", "");
                        string programmazioneFreePath = (string)key.GetValue("ProgrammazioneFreePath", "programmazione-free");
                        string calendariInsegnantiPath = (string)key.GetValue("CalendariInsegnantiPath", "calendari insegnanti");
                        int giorniRetenzionBackup = (int)key.GetValue("GiorniRetenzionBackup", 30);

                        txtDataPath.Text = dataPath;
                        txtBackupPath.Text = backupPath;
                        txtActivityName.Text = activityName;
                        txtLogoPath.Text = logoPath;
                        txtProgrammazioneFreePath.Text = programmazioneFreePath;
                        txtCalendariInsegnantiPath.Text = calendariInsegnantiPath;
                        nudGiorniRetenzionBackup.Value = giorniRetenzionBackup;

                        // Visualizza l'immagine del logo
                        if (!string.IsNullOrEmpty(logoPath) && File.Exists(logoPath))
                        {
                            try
                            {
                                picLogo.Image = Image.FromFile(logoPath);
                                picLogo.SizeMode = PictureBoxSizeMode.Zoom;
                            }
                            catch
                            {
                                picLogo.Image = null;
                            }
                        }
                        else
                        {
                            picLogo.Image = null;
                        }
                    }
                }

                // Carica i valori delle impostazioni generali
                chkLun.Checked = impostazioni.LunAttivo;
                chkMar.Checked = impostazioni.MarAttivo;
                chkMer.Checked = impostazioni.MerAttivo;
                chkGio.Checked = impostazioni.GioAttivo;
                chkVen.Checked = impostazioni.VenAttivo;
                chkSab.Checked = impostazioni.SabAttivo;
                chkDom.Checked = impostazioni.DomAttivo;

                nudMattInizio.Value = impostazioni.MattInizio;
                nudMattFine.Value = impostazioni.MattFine;
                nudPomInizio.Value = impostazioni.PomInizio;
                nudPomFine.Value = impostazioni.PomFine;
                nudDurataDefault.Value = impostazioni.DurataLezioneDefault;
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante il caricamento delle impostazioni: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void DisableDetailForm()
        {
            txtNomeStrumento.Text = string.Empty;

            chkLunStrumento.Checked = false;
            chkMarStrumento.Checked = false;
            chkMerStrumento.Checked = false;
            chkGioStrumento.Checked = false;
            chkVenStrumento.Checked = false;
            chkSabStrumento.Checked = false;
            chkDomStrumento.Checked = false;

            nudMattInizioStrumento.Value = 9;
            nudMattFineStrumento.Value = 13;
            nudPomInizioStrumento.Value = 15;
            nudPomFineStrumento.Value = 21;

            txtNomeStrumento.Enabled = false;
            chkLunStrumento.Enabled = false;
            chkMarStrumento.Enabled = false;
            chkMerStrumento.Enabled = false;
            chkGioStrumento.Enabled = false;
            chkVenStrumento.Enabled = false;
            chkSabStrumento.Enabled = false;
            chkDomStrumento.Enabled = false;
            nudMattInizioStrumento.Enabled = false;
            nudMattFineStrumento.Enabled = false;
            nudPomInizioStrumento.Enabled = false;
            nudPomFineStrumento.Enabled = false;

            btnSalvaStrumento.Enabled = false;
            btnEliminaStrumento.Enabled = false;

            selectedStrumento = null;
        }

        private void EnableDetailForm()
        {
            txtNomeStrumento.Enabled = true;
            chkLunStrumento.Enabled = true;
            chkMarStrumento.Enabled = true;
            chkMerStrumento.Enabled = true;
            chkGioStrumento.Enabled = true;
            chkVenStrumento.Enabled = true;
            chkSabStrumento.Enabled = true;
            chkDomStrumento.Enabled = true;
            nudMattInizioStrumento.Enabled = true;
            nudMattFineStrumento.Enabled = true;
            nudPomInizioStrumento.Enabled = true;
            nudPomFineStrumento.Enabled = true;

            btnSalvaStrumento.Enabled = true;
            btnEliminaStrumento.Enabled = true;
        }

        private void LoadStrumentoDetails(Strumento strumento)
        {
            selectedStrumento = strumento;

            txtNomeStrumento.Text = strumento.Nome ?? string.Empty;

            chkLunStrumento.Checked = strumento.LunAttivo;
            chkMarStrumento.Checked = strumento.MarAttivo;
            chkMerStrumento.Checked = strumento.MerAttivo;
            chkGioStrumento.Checked = strumento.GioAttivo;
            chkVenStrumento.Checked = strumento.VenAttivo;
            chkSabStrumento.Checked = strumento.SabAttivo;
            chkDomStrumento.Checked = strumento.DomAttivo;

            nudMattInizioStrumento.Value = strumento.MattInizio;
            nudMattFineStrumento.Value = strumento.MattFine;
            nudPomInizioStrumento.Value = strumento.PomInizio;
            nudPomFineStrumento.Value = strumento.PomFine;

            EnableDetailForm();
        }

        private void btnBrowseProgrammazioneFreePath_Click(object sender, EventArgs e)
        {
            using (FolderBrowserDialog fbd = new FolderBrowserDialog())
            {
                fbd.Description = "Seleziona percorso per programmazione free";
                fbd.SelectedPath = txtProgrammazioneFreePath.Text;

                if (fbd.ShowDialog() == DialogResult.OK)
                {
                    txtProgrammazioneFreePath.Text = fbd.SelectedPath;
                }
            }
        }

        private void btnBrowseCalendariInsegnantiPath_Click(object sender, EventArgs e)
        {
            using (FolderBrowserDialog fbd = new FolderBrowserDialog())
            {
                fbd.Description = "Seleziona percorso per calendari insegnanti";
                fbd.SelectedPath = txtCalendariInsegnantiPath.Text;

                if (fbd.ShowDialog() == DialogResult.OK)
                {
                    txtCalendariInsegnantiPath.Text = fbd.SelectedPath;
                }
            }
        }

        private void btnSalvaPercorsi_Click(object sender, EventArgs e)
        {
            try
            {
                // Verifica che i percorsi esistano o creali
                string dataPath = txtDataPath.Text.Trim();
                string backupPath = txtBackupPath.Text.Trim();
                string programmazioneFreePath = txtProgrammazioneFreePath.Text.Trim();
                string calendariInsegnantiPath = txtCalendariInsegnantiPath.Text.Trim();
                int giorniRetenzionBackup = (int)nudGiorniRetenzionBackup.Value;

                if (!string.IsNullOrEmpty(dataPath) && !Directory.Exists(dataPath))
                {
                    try
                    {
                        Directory.CreateDirectory(dataPath);
                    }
                    catch (Exception ex)
                    {
                        MessageBox.Show($"Impossibile creare la directory dei dati: {ex.Message}",
                            "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                        return;
                    }
                }

                if (!string.IsNullOrEmpty(backupPath) && !Directory.Exists(backupPath))
                {
                    try
                    {
                        Directory.CreateDirectory(backupPath);
                    }
                    catch (Exception ex)
                    {
                        MessageBox.Show($"Impossibile creare la directory di backup: {ex.Message}",
                            "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                        return;
                    }
                }

                // Crea le cartelle per i nuovi path se necessario
                string programmingFreeFolderPath = Path.Combine(dataPath, programmazioneFreePath);
                if (!string.IsNullOrEmpty(programmazioneFreePath) && !Directory.Exists(programmingFreeFolderPath))
                {
                    try
                    {
                        Directory.CreateDirectory(programmingFreeFolderPath);
                    }
                    catch (Exception ex)
                    {
                        MessageBox.Show($"Impossibile creare la directory per la programmazione free: {ex.Message}",
                            "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                        return;
                    }
                }

                string teachersCalendarFolderPath = Path.Combine(dataPath, calendariInsegnantiPath);
                if (!string.IsNullOrEmpty(calendariInsegnantiPath) && !Directory.Exists(teachersCalendarFolderPath))
                {
                    try
                    {
                        Directory.CreateDirectory(teachersCalendarFolderPath);
                    }
                    catch (Exception ex)
                    {
                        MessageBox.Show($"Impossibile creare la directory per i calendari insegnanti: {ex.Message}",
                            "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                        return;
                    }
                }

                // Verifica che il logo esista
                string logoPath = txtLogoPath.Text.Trim();
                if (!string.IsNullOrEmpty(logoPath) && !File.Exists(logoPath))
                {
                    MessageBox.Show("Il file del logo specificato non esiste.",
                        "Errore", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    return;
                }

                // Salva i percorsi nel registro
                using (RegistryKey key = Registry.CurrentUser.OpenSubKey(@"SOFTWARE\EasyBooking", true) ??
                                        Registry.CurrentUser.CreateSubKey(@"SOFTWARE\EasyBooking"))
                {
                    key.SetValue("DataPath", dataPath);
                    key.SetValue("BackupPath", backupPath);
                    key.SetValue("ActivityName", txtActivityName.Text.Trim());
                    key.SetValue("LogoPath", logoPath);
                    key.SetValue("ProgrammazioneFreePath", programmazioneFreePath);
                    key.SetValue("CalendariInsegnantiPath", calendariInsegnantiPath);
                    key.SetValue("GiorniRetenzionBackup", giorniRetenzionBackup);
                }

                // Aggiorna le impostazioni
                impostazioni.ProgrammazioneFreePath = programmazioneFreePath;
                impostazioni.CalendariInsegnantiPath = calendariInsegnantiPath;
                impostazioni.GiorniRetenzionBackup = giorniRetenzionBackup;

                // Salva le impostazioni nel file XML
                MainForm.SaveEncryptedXml(impostazioni, impostazioniFilePath);

                // Pulisci i vecchi backup se la retention è attiva
                if (giorniRetenzionBackup > 0 && !string.IsNullOrEmpty(backupPath) && Directory.Exists(backupPath))
                {
                    PulisciVecchiBackup(backupPath, giorniRetenzionBackup);
                }

                MessageBox.Show("Percorsi salvati con successo. Riavvia l'applicazione per applicare le modifiche.",
                    "Salvataggio", MessageBoxButtons.OK, MessageBoxIcon.Information);
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante il salvataggio dei percorsi: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void PulisciVecchiBackup(string backupPath, int giorniRetention)
        {
            try
            {
                DateTime dataLimite = DateTime.Now.AddDays(-giorniRetention);
                DirectoryInfo dirInfo = new DirectoryInfo(backupPath);

                // Cerca tutti i file di backup (con pattern EasyBooking_backup_*.zip)
                FileInfo[] backupFiles = dirInfo.GetFiles("EasyBooking_backup_*.zip");

                int filesEliminati = 0;
                foreach (FileInfo file in backupFiles)
                {
                    if (file.CreationTime < dataLimite)
                    {
                        try
                        {
                            file.Delete();
                            filesEliminati++;
                        }
                        catch
                        {
                            // Ignora errori su singoli file
                        }
                    }
                }

                if (filesEliminati > 0)
                {
                    MessageBox.Show($"Eliminati {filesEliminati} backup più vecchi di {giorniRetention} giorni.",
                        "Pulizia Backup", MessageBoxButtons.OK, MessageBoxIcon.Information);
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante la pulizia dei vecchi backup: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Warning);
            }
        }

        private void btnSalvaImpostazioni_Click(object sender, EventArgs e)
        {
            // Verifica che gli orari siano validi
            if (nudMattInizio.Value >= nudMattFine.Value)
            {
                MessageBox.Show("L'ora di inizio mattino deve essere inferiore all'ora di fine mattino.",
                    "Validazione", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            if (nudPomInizio.Value >= nudPomFine.Value)
            {
                MessageBox.Show("L'ora di inizio pomeriggio deve essere inferiore all'ora di fine pomeriggio.",
                    "Validazione", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            // Aggiorna le impostazioni generali
            impostazioni.LunAttivo = chkLun.Checked;
            impostazioni.MarAttivo = chkMar.Checked;
            impostazioni.MerAttivo = chkMer.Checked;
            impostazioni.GioAttivo = chkGio.Checked;
            impostazioni.VenAttivo = chkVen.Checked;
            impostazioni.SabAttivo = chkSab.Checked;
            impostazioni.DomAttivo = chkDom.Checked;

            impostazioni.MattInizio = (int)nudMattInizio.Value;
            impostazioni.MattFine = (int)nudMattFine.Value;
            impostazioni.PomInizio = (int)nudPomInizio.Value;
            impostazioni.PomFine = (int)nudPomFine.Value;
            impostazioni.DurataLezioneDefault = (int)nudDurataDefault.Value;

            // Salva le impostazioni nel file XML
            MainForm.SaveEncryptedXml(impostazioni, impostazioniFilePath);

            MessageBox.Show("Impostazioni salvate con successo.",
                "Salvataggio", MessageBoxButtons.OK, MessageBoxIcon.Information);
        }

        private void dgvStrumenti_CellClick(object sender, DataGridViewCellEventArgs e)
        {
            if (e.RowIndex >= 0 && e.RowIndex < strumenti.Items.Count)
            {
                var strumento = strumenti.Items[e.RowIndex];
                LoadStrumentoDetails(strumento);
            }
        }

        private void btnNuovoStrumento_Click(object sender, EventArgs e)
        {
            // Crea un nuovo strumento con valori predefiniti
            var nuovoStrumento = new Strumento
            {
                Id = strumenti.Items.Count > 0 ? strumenti.Items.Max(s => s.Id) + 1 : 1,
                // Copia le impostazioni predefinite dai giorni di lavoro generali
                LunAttivo = impostazioni.LunAttivo,
                MarAttivo = impostazioni.MarAttivo,
                MerAttivo = impostazioni.MerAttivo,
                GioAttivo = impostazioni.GioAttivo,
                VenAttivo = impostazioni.VenAttivo,
                SabAttivo = impostazioni.SabAttivo,
                DomAttivo = impostazioni.DomAttivo,
                MattInizio = impostazioni.MattInizio,
                MattFine = impostazioni.MattFine,
                PomInizio = impostazioni.PomInizio,
                PomFine = impostazioni.PomFine
            };

            // Aggiungi alla lista
            strumenti.Items.Add(nuovoStrumento);

            // Aggiorna la griglia
            bindingSourceStrumenti.ResetBindings(false);

            // Seleziona il nuovo strumento
            dgvStrumenti.ClearSelection();
            int lastIndex = dgvStrumenti.Rows.Count - 1;
            if (lastIndex >= 0)
            {
                dgvStrumenti.Rows[lastIndex].Selected = true;
                LoadStrumentoDetails(nuovoStrumento);
                txtNomeStrumento.Focus();
            }
        }

        private void btnSalvaStrumento_Click(object sender, EventArgs e)
        {
            if (selectedStrumento == null)
                return;

            // Verifica campi obbligatori
            if (string.IsNullOrWhiteSpace(txtNomeStrumento.Text))
            {
                MessageBox.Show("Il nome dello strumento è obbligatorio.",
                    "Validazione", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            // Verifica che gli orari siano validi
            if (nudMattInizioStrumento.Value >= nudMattFineStrumento.Value)
            {
                MessageBox.Show("L'ora di inizio mattino deve essere inferiore all'ora di fine mattino.",
                    "Validazione", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            if (nudPomInizioStrumento.Value >= nudPomFineStrumento.Value)
            {
                MessageBox.Show("L'ora di inizio pomeriggio deve essere inferiore all'ora di fine pomeriggio.",
                    "Validazione", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            // Aggiorna l'oggetto strumento
            selectedStrumento.Nome = txtNomeStrumento.Text.Trim();

            selectedStrumento.LunAttivo = chkLunStrumento.Checked;
            selectedStrumento.MarAttivo = chkMarStrumento.Checked;
            selectedStrumento.MerAttivo = chkMerStrumento.Checked;
            selectedStrumento.GioAttivo = chkGioStrumento.Checked;
            selectedStrumento.VenAttivo = chkVenStrumento.Checked;
            selectedStrumento.SabAttivo = chkSabStrumento.Checked;
            selectedStrumento.DomAttivo = chkDomStrumento.Checked;

            selectedStrumento.MattInizio = (int)nudMattInizioStrumento.Value;
            selectedStrumento.MattFine = (int)nudMattFineStrumento.Value;
            selectedStrumento.PomInizio = (int)nudPomInizioStrumento.Value;
            selectedStrumento.PomFine = (int)nudPomFineStrumento.Value;

            // Salva nel file
            MainForm.SaveEncryptedXml(strumenti, strumentiFilePath);

            // Aggiorna la griglia
            bindingSourceStrumenti.ResetBindings(false);

            MessageBox.Show("Strumento salvato con successo.",
                "Salvataggio", MessageBoxButtons.OK, MessageBoxIcon.Information);
        }

        private void btnEliminaStrumento_Click(object sender, EventArgs e)
        {
            if (selectedStrumento == null)
                return;

            // Chiede conferma
            var result = MessageBox.Show(
                $"Sei sicuro di voler eliminare lo strumento '{selectedStrumento.Nome}'?",
                "Conferma eliminazione",
                MessageBoxButtons.YesNo,
                MessageBoxIcon.Question);

            if (result == DialogResult.Yes)
            {
                // Verifica se ci sono pacchetti associati
                var pacchettiFilePath = Path.Combine(dataPath, "pacchetti.xml");
                var pacchetti = MainForm.LoadEncryptedXml<PacchettiList>(pacchettiFilePath);

                if (pacchetti != null && pacchetti.Items != null &&
                    pacchetti.Items.Any(p => p.Strumento == selectedStrumento.Nome))
                {
                    // Chiede conferma per eliminare uno strumento con pacchetti associati
                    var confirmResult = MessageBox.Show(
                        "Questo strumento ha dei pacchetti associati. L'eliminazione potrebbe causare errori nel sistema. Continuare?",
                        "Attenzione",
                        MessageBoxButtons.YesNo,
                        MessageBoxIcon.Warning);

                    if (confirmResult != DialogResult.Yes)
                    {
                        return; // Annulla l'eliminazione
                    }
                }

                // Verifica se ci sono insegnanti associati
                var insegnantiFilePath = Path.Combine(dataPath, "insegnanti.xml");
                var insegnanti = MainForm.LoadEncryptedXml<InsegnantiList>(insegnantiFilePath);

                if (insegnanti != null && insegnanti.Items != null)
                {
                    bool anyAssociation = false;
                    foreach (var insegnante in insegnanti.Items)
                    {
                        if (insegnante.Strumenti != null && insegnante.Strumenti.Contains(selectedStrumento.Nome))
                        {
                            anyAssociation = true;
                            break;
                        }
                    }

                    if (anyAssociation)
                    {
                        // Chiede conferma per eliminare uno strumento con insegnanti associati
                        var confirmResult = MessageBox.Show(
                            "Questo strumento è associato ad alcuni insegnanti. L'eliminazione potrebbe causare errori nel sistema. Continuare?",
                            "Attenzione",
                            MessageBoxButtons.YesNo,
                            MessageBoxIcon.Warning);

                        if (confirmResult != DialogResult.Yes)
                        {
                            return; // Annulla l'eliminazione
                        }
                    }
                }

                // Rimuove lo strumento
                strumenti.Items.Remove(selectedStrumento);
                MainForm.SaveEncryptedXml(strumenti, strumentiFilePath);

                // Aggiorna la griglia
                bindingSourceStrumenti.ResetBindings(false);

                // Disabilita il form di dettaglio
                DisableDetailForm();

                MessageBox.Show("Strumento eliminato con successo.",
                    "Eliminazione", MessageBoxButtons.OK, MessageBoxIcon.Information);
            }
        }

        private void btnBrowseLogo_Click(object sender, EventArgs e)
        {
            using (OpenFileDialog ofd = new OpenFileDialog())
            {
                ofd.Filter = "Immagini|*.jpg;*.jpeg;*.png;*.gif;*.bmp|Tutti i file|*.*";
                ofd.Title = "Seleziona logo";

                if (ofd.ShowDialog() == DialogResult.OK)
                {
                    txtLogoPath.Text = ofd.FileName;
                    try
                    {
                        picLogo.Image = Image.FromFile(ofd.FileName);
                        picLogo.SizeMode = PictureBoxSizeMode.Zoom;
                    }
                    catch (Exception ex)
                    {
                        MessageBox.Show($"Errore durante il caricamento dell'immagine: {ex.Message}",
                            "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                        picLogo.Image = null;
                    }
                }
            }
        }

        private void btnBrowseDataPath_Click(object sender, EventArgs e)
        {
            using (FolderBrowserDialog fbd = new FolderBrowserDialog())
            {
                fbd.Description = "Seleziona percorso per i dati";
                fbd.SelectedPath = txtDataPath.Text;

                if (fbd.ShowDialog() == DialogResult.OK)
                {
                    txtDataPath.Text = fbd.SelectedPath;
                }
            }
        }

        private void btnBrowseBackupPath_Click(object sender, EventArgs e)
        {
            using (FolderBrowserDialog fbd = new FolderBrowserDialog())
            {
                fbd.Description = "Seleziona percorso per i backup";
                fbd.SelectedPath = txtBackupPath.Text;

                if (fbd.ShowDialog() == DialogResult.OK)
                {
                    txtBackupPath.Text = fbd.SelectedPath;
                }
            }
        }

        private void txtRicerca_TextChanged(object sender, EventArgs e)
        {
            // Filtra la lista strumenti in base al testo di ricerca
            string searchText = txtRicerca.Text.ToLower();

            if (string.IsNullOrWhiteSpace(searchText))
            {
                bindingSourceStrumenti.DataSource = strumenti.Items;
            }
            else
            {
                bindingSourceStrumenti.DataSource = strumenti.Items.Where(s =>
                    (s.Nome != null && s.Nome.ToLower().Contains(searchText))
                ).ToList();
            }
        }
    }

    [Serializable]
    public class ImpostazioniGenerali
    {
        // Giorni lavorativi
        public bool LunAttivo { get; set; } = true;
        public bool MarAttivo { get; set; } = true;
        public bool MerAttivo { get; set; } = true;
        public bool GioAttivo { get; set; } = true;
        public bool VenAttivo { get; set; } = true;
        public bool SabAttivo { get; set; } = false;
        public bool DomAttivo { get; set; } = false;

        // Orari di lavoro
        public int MattInizio { get; set; } = 9;
        public int MattFine { get; set; } = 13;
        public int PomInizio { get; set; } = 15;
        public int PomFine { get; set; } = 21;

        // Altre impostazioni
        public int DurataLezioneDefault { get; set; } = 60;

        // Nuovi percorsi
        public string ProgrammazioneFreePath { get; set; } = "programmazione-free";
        public string CalendariInsegnantiPath { get; set; } = "calendari insegnanti";

        // Retention backup
        public int GiorniRetenzionBackup { get; set; } = 30;
    }

    // Definizione della classe per le liste già presenti nell'applicazione
    public class PacchettiList
    {
        public List<Pacchetto> Items { get; set; } = new List<Pacchetto>();
    }

    public class Pacchetto
    {
        public int Id { get; set; }
        public string Nome { get; set; }
        public string Descrizione { get; set; }
        public string Strumento { get; set; }
    }
}