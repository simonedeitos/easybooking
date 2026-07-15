using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.IO;
using System.Linq;
using System.Windows.Forms;
using System.Xml.Serialization;
using System.Diagnostics;
using System.Globalization;
using iTextSharp.text;
using iTextSharp.text.pdf;
using Microsoft.Win32;
using System.Reflection;
using System.Xml;
using System.Text;

namespace EasyBooking
{
    public partial class InsegnantiControl : UserControl
    {
        private string dataPath;
        private InsegnantiList insegnanti;
        private BindingSource bindingSource;
        private Insegnante selectedInsegnante;

        // Percorsi dei file XML
        private string insegnantiFilePath;
        private string prenotazioniFilePath;
        private string strumentiFilePath;
        private string clientiFilePath;
        private string acquistiFilePath;
        private string pacchettiFilePath;
        private string tariffeCoppiaFilePath;

        // Dictionary per memorizzare le tariffe di coppia se non supportate dalla classe Insegnante
        private Dictionary<int, decimal> tariffeDiCoppia = new Dictionary<int, decimal>();

        // Context menu per le lezioni future
        private ContextMenuStrip contextMenuLezioniFuture;

        public InsegnantiControl(string dataPath)
        {
            InitializeComponent();
            this.dataPath = dataPath;
            insegnantiFilePath = Path.Combine(dataPath, "insegnanti.xml");
            prenotazioniFilePath = Path.Combine(dataPath, "prenotazioni.xml");
            strumentiFilePath = Path.Combine(dataPath, "strumenti.xml");
            clientiFilePath = Path.Combine(dataPath, "clienti.xml");
            acquistiFilePath = Path.Combine(dataPath, "acquisti.xml");
            pacchettiFilePath = Path.Combine(dataPath, "pacchetti.xml");
            tariffeCoppiaFilePath = Path.Combine(dataPath, "tariffe_coppia.xml");
            LoadData();
            SetupDataGridView();
            LoadMesi();
            SetupEsportaMenu();
            SetupContextMenuLezioniFuture();
        }

        private void SetupContextMenuLezioniFuture()
        {
            // Crea un nuovo menu contestuale
            contextMenuLezioniFuture = new ContextMenuStrip();

            // Elemento Modifica Lezione (per selezione singola)
            ToolStripMenuItem modificaItem = new ToolStripMenuItem("Modifica Lezione");
            modificaItem.Click += ModificaLezione_Click;

            // Elemento Assegna ad altro Insegnante (per selezione multipla)
            ToolStripMenuItem assegnaItem = new ToolStripMenuItem("Assegna ad altro Insegnante");
            assegnaItem.Click += AssegnaAdAltroInsegnante_Click;

            // Aggiungi gli elementi al menu
            contextMenuLezioniFuture.Items.Add(modificaItem);
            contextMenuLezioniFuture.Items.Add(assegnaItem);

            // Evento per gestire la visualizzazione dinamica delle voci di menu
            contextMenuLezioniFuture.Opening += (sender, e) => {
                // Se non ci sono righe selezionate, non mostrare il menu
                if (dgvLezioniFuture.SelectedRows.Count == 0)
                {
                    e.Cancel = true;
                    return;
                }

                // Gestisci le voci di menu in base alla selezione
                bool selezioneSingola = dgvLezioniFuture.SelectedRows.Count == 1;
                bool selezioneMultipla = dgvLezioniFuture.SelectedRows.Count > 1;

                // Mostra "Modifica Lezione" solo con selezione singola
                modificaItem.Visible = selezioneSingola;

                // Mostra "Assegna ad altro Insegnante" solo con selezione multipla
                assegnaItem.Visible = selezioneMultipla;
            };

            // Assegna il menu alla griglia
            dgvLezioniFuture.ContextMenuStrip = contextMenuLezioniFuture;
        }

        private void ModificaLezione_Click(object sender, EventArgs e)
        {
            try
            {
                // Carica i dati necessari
                var prenotazioniList = MainForm.LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);

                if (prenotazioniList?.Items == null)
                {
                    MessageBox.Show("Errore nel caricamento dei dati.", "Errore",
                        MessageBoxButtons.OK, MessageBoxIcon.Error);
                    return;
                }

                // Ottieni l'ID della prenotazione dalla riga selezionata
                var row = dgvLezioniFuture.SelectedRows[0];
                dynamic lezione = row.DataBoundItem;
                int prenotazioneId = lezione.Id;

                // Trova la prenotazione completa
                var prenotazione = prenotazioniList.Items.FirstOrDefault(p => p.Id == prenotazioneId);
                if (prenotazione == null)
                {
                    MessageBox.Show("Prenotazione non trovata.", "Errore",
                        MessageBoxButtons.OK, MessageBoxIcon.Error);
                    return;
                }

                // Apri il form di modifica rapida
                using (var editForm = new QuickEditLessonForm(prenotazione, dataPath))
                {
                    if (editForm.ShowDialog() == DialogResult.OK)
                    {
                        // Salva le modifiche
                        MainForm.SaveEncryptedXml(prenotazioniList, prenotazioniFilePath);

                        // Se l'insegnante è stato cambiato e non è più quello corrente
                        if (selectedInsegnante != null && prenotazione.InsegnanteId != selectedInsegnante.Id)
                        {
                            MessageBox.Show("Lezione modificata e riassegnata con successo.", "Successo",
                                MessageBoxButtons.OK, MessageBoxIcon.Information);
                        }
                        else
                        {
                            MessageBox.Show("Lezione modificata con successo.", "Successo",
                                MessageBoxButtons.OK, MessageBoxIcon.Information);
                        }

                        // Ricarica le lezioni future
                        LoadLezioniFuture();
                    }
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante la modifica della lezione: {ex.Message}", "Errore",
                    MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void AssegnaAdAltroInsegnante_Click(object sender, EventArgs e)
        {
            // Ottieni l'elenco degli altri insegnanti disponibili
            var altriInsegnanti = insegnanti.Items.Where(i => i.Id != selectedInsegnante.Id).ToList();

            if (altriInsegnanti.Count == 0)
            {
                MessageBox.Show("Non ci sono altri insegnanti disponibili.", "Attenzione",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            // Crea un form di selezione insegnante
            using (var form = new Form())
            {
                form.Text = "Seleziona Insegnante";
                form.Size = new System.Drawing.Size(400, 240);
                form.StartPosition = FormStartPosition.CenterParent;
                form.ShowIcon = false;
                form.ShowInTaskbar = false;
                form.FormBorderStyle = FormBorderStyle.FixedDialog;
                form.MaximizeBox = false;
                form.MinimizeBox = false;

                Label lblInsegnante = new Label
                {
                    Text = "Seleziona l'insegnante a cui assegnare le lezioni:",
                    Location = new System.Drawing.Point(12, 12),
                    Size = new System.Drawing.Size(300, 20)
                };

                Label lblAvviso = new Label
                {
                    Text = "Assicurati che le lezioni passate al nuovo insegnante\nnon vengano sovrapposte a lezioni a lui già programmate.",
                    Location = new System.Drawing.Point(12, 95),
                    Size = new System.Drawing.Size(370, 40),
                    ForeColor = System.Drawing.Color.Firebrick,
                    Font = new System.Drawing.Font(lblInsegnante.Font, System.Drawing.FontStyle.Bold)
                };

                ComboBox cmbInsegnanti = new ComboBox
                {
                    Location = new System.Drawing.Point(12, 40),
                    Size = new System.Drawing.Size(300, 23),
                    DropDownStyle = ComboBoxStyle.DropDownList,
                    DisplayMember = "DisplayName"
                };

                // Popola la combo con gli altri insegnanti
                var insegnantiItems = altriInsegnanti.Select(i => new
                {
                    i.Id,
                    DisplayName = $"{i.Cognome} {i.Nome}",
                    Insegnante = i
                }).ToList();

                cmbInsegnanti.DataSource = insegnantiItems;

                Button btnOk = new Button
                {
                    Text = "OK",
                    DialogResult = DialogResult.OK,
                    Location = new System.Drawing.Point(190, 140),
                    Size = new System.Drawing.Size(75, 30)
                };

                Button btnAnnulla = new Button
                {
                    Text = "Annulla",
                    DialogResult = DialogResult.Cancel,
                    Location = new System.Drawing.Point(280, 140),
                    Size = new System.Drawing.Size(75, 30)
                };

                form.Controls.AddRange(new Control[] { lblInsegnante, lblAvviso, cmbInsegnanti, btnOk, btnAnnulla });

                if (form.ShowDialog() == DialogResult.OK && cmbInsegnanti.SelectedItem != null)
                {
                    dynamic selectedItem = cmbInsegnanti.SelectedItem;
                    int nuovoInsegnanteId = selectedItem.Id;

                    // Aggiorna le lezioni selezionate
                    var prenotazioniList = MainForm.LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);

                    if (prenotazioniList != null && prenotazioniList.Items != null)
                    {
                        int lezioniAggiornate = 0;

                        foreach (DataGridViewRow row in dgvLezioniFuture.SelectedRows)
                        {
                            if (row.DataBoundItem != null)
                            {
                                // Ottieni l'ID della prenotazione dalla riga selezionata
                                dynamic lezione = row.DataBoundItem;
                                int prenotazioneId = lezione.Id;

                                var prenotazione = prenotazioniList.Items.FirstOrDefault(p => p.Id == prenotazioneId);
                                if (prenotazione != null)
                                {
                                    prenotazione.InsegnanteId = nuovoInsegnanteId;
                                    lezioniAggiornate++;
                                }
                            }
                        }

                        if (lezioniAggiornate > 0)
                        {
                            // Salva le modifiche
                            MainForm.SaveEncryptedXml(prenotazioniList, prenotazioniFilePath);

                            MessageBox.Show($"{lezioniAggiornate} lezioni sono state riassegnate con successo.",
                                "Operazione completata", MessageBoxButtons.OK, MessageBoxIcon.Information);

                            // Ricarica le lezioni future
                            LoadLezioniFuture();
                        }
                    }
                }
            }
        }

        [Serializable]
        public class TariffaCoppiaEntry
        {
            public int InsegnanteId { get; set; }
            public decimal Tariffa { get; set; }

            public TariffaCoppiaEntry() { }

            public TariffaCoppiaEntry(int insegnanteId, decimal tariffa)
            {
                InsegnanteId = insegnanteId;
                Tariffa = tariffa;
            }
        }

        [Serializable]
        [XmlRoot("TariffeDiCoppia")]
        public class TariffeDiCoppiaList
        {
            [XmlElement("Tariffa")]
            public List<TariffaCoppiaEntry> Items { get; set; } = new List<TariffaCoppiaEntry>();
        }

        // Metodi per gestire le tariffe di coppia con reflection
        private bool HasTariffaOrariaCoppia(Insegnante insegnante)
        {
            if (insegnante == null) return false;
            PropertyInfo property = insegnante.GetType().GetProperty("TariffaOrariaCoppia");
            return property != null;
        }

        private decimal GetTariffaOrariaCoppia(Insegnante insegnante)
        {
            if (insegnante == null) return 0;

            try
            {
                // Prova a usare reflection per accedere alla proprietà TariffaOrariaCoppia
                PropertyInfo property = insegnante.GetType().GetProperty("TariffaOrariaCoppia");
                if (property != null)
                {
                    return (decimal)property.GetValue(insegnante, null);
                }

                // Se non esiste la proprietà, usa il dizionario come fallback
                if (tariffeDiCoppia.ContainsKey(insegnante.Id))
                {
                    return tariffeDiCoppia[insegnante.Id];
                }
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in GetTariffaOrariaCoppia: {ex.Message}");
            }

            // Se non è memorizzata, usa la tariffa normale
            return insegnante.TariffaOraria;
        }

        private void SetTariffaOrariaCoppia(Insegnante insegnante, decimal value)
        {
            if (insegnante == null) return;

            try
            {
                // Prova a usare reflection per impostare la proprietà TariffaOrariaCoppia
                PropertyInfo property = insegnante.GetType().GetProperty("TariffaOrariaCoppia");
                if (property != null)
                {
                    property.SetValue(insegnante, value, null);
                    return;
                }

                // Se non esiste la proprietà, usa il dizionario come fallback
                if (tariffeDiCoppia.ContainsKey(insegnante.Id))
                {
                    tariffeDiCoppia[insegnante.Id] = value;
                }
                else
                {
                    tariffeDiCoppia.Add(insegnante.Id, value);
                }
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in SetTariffaOrariaCoppia: {ex.Message}");
            }
        }

        private void LoadData()
        {
            try
            {
                // Carica i dati degli insegnanti dal file XML
                insegnanti = MainForm.LoadEncryptedXml<InsegnantiList>(insegnantiFilePath);

                // Verifica che insegnanti non sia null
                if (insegnanti == null)
                {
                    insegnanti = new InsegnantiList();
                }

                // Verifica che insegnanti.Items non sia null
                if (insegnanti.Items == null)
                {
                    insegnanti.Items = new List<Insegnante>();
                    // Salva una lista vuota per creare il file se non esiste
                    MainForm.SaveEncryptedXml(insegnanti, insegnantiFilePath);
                }

                // Carica le tariffe di coppia da un file separato se necessario
                LoadTariffeDiCoppia();
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante il caricamento dei dati degli insegnanti: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void LoadTariffeDiCoppia()
        {
            // Se gli insegnanti supportano già la proprietà TariffaOrariaCoppia, non serve fare nulla
            if (insegnanti.Items.Count > 0 && HasTariffaOrariaCoppia(insegnanti.Items[0]))
            {
                Debug.WriteLine("La classe Insegnante supporta già la proprietà TariffaOrariaCoppia");
                return;
            }

            // Pulisci il dizionario esistente
            tariffeDiCoppia.Clear();

            // Se il file non esiste, non c'è nulla da caricare
            if (!File.Exists(tariffeCoppiaFilePath))
            {
                Debug.WriteLine($"File tariffe coppia non trovato: {tariffeCoppiaFilePath}");
                return;
            }

            try
            {
                // Carica la lista di tariffe
                using (FileStream fs = new FileStream(tariffeCoppiaFilePath, FileMode.Open))
                {
                    XmlSerializer serializer = new XmlSerializer(typeof(TariffeDiCoppiaList));
                    var tariffeDiCoppiaList = (TariffeDiCoppiaList)serializer.Deserialize(fs);

                    if (tariffeDiCoppiaList != null && tariffeDiCoppiaList.Items != null)
                    {
                        // Converti la lista nel dizionario
                        foreach (var entry in tariffeDiCoppiaList.Items)
                        {
                            tariffeDiCoppia[entry.InsegnanteId] = entry.Tariffa;
                        }
                        Debug.WriteLine($"Caricate {tariffeDiCoppia.Count} tariffe di coppia dal file {tariffeCoppiaFilePath}");
                    }
                }
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore caricamento tariffe di coppia: {ex.Message}");
                MessageBox.Show($"Errore durante la lettura del file XML delle tariffe di coppia: {ex.Message}\n" +
                    $"Il file verrà ricreato al prossimo salvataggio.",
                    "Avviso", MessageBoxButtons.OK, MessageBoxIcon.Warning);
            }
        }

        private void SaveTariffeDiCoppia()
        {
            // Se gli insegnanti supportano già la proprietà TariffaOrariaCoppia, non serve fare nulla
            if (insegnanti.Items.Count > 0 && HasTariffaOrariaCoppia(insegnanti.Items[0]))
            {
                Debug.WriteLine("La classe Insegnante supporta già la proprietà TariffaOrariaCoppia, non serve salvare");
                return;
            }

            try
            {
                // Crea un oggetto serializzabile dalla nostra dictionary
                var tariffeDiCoppiaList = new TariffeDiCoppiaList();

                foreach (var kvp in tariffeDiCoppia)
                {
                    tariffeDiCoppiaList.Items.Add(new TariffaCoppiaEntry(kvp.Key, kvp.Value));
                }

                // Salva l'oggetto usando XmlSerializer standard
                using (FileStream fs = new FileStream(tariffeCoppiaFilePath, FileMode.Create))
                {
                    XmlSerializer serializer = new XmlSerializer(typeof(TariffeDiCoppiaList));
                    serializer.Serialize(fs, tariffeDiCoppiaList);
                }

                Debug.WriteLine($"Salvate {tariffeDiCoppia.Count} tariffe di coppia nel file {tariffeCoppiaFilePath}");
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore salvataggio tariffe di coppia: {ex.Message}");
                MessageBox.Show($"Impossibile salvare le tariffe di coppia: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void SetupDataGridView()
        {
            try
            {
                // Verifica che insegnanti e items non siano null prima di configurare il DataGridView
                if (insegnanti == null)
                {
                    insegnanti = new InsegnantiList();
                }

                if (insegnanti.Items == null)
                {
                    insegnanti.Items = new List<Insegnante>();
                }

                // Assicurati che ogni insegnante abbia la lista Strumenti inizializzata
                foreach (var insegnante in insegnanti.Items)
                {
                    if (insegnante.Strumenti == null)
                    {
                        insegnante.Strumenti = new List<string>();
                    }
                }

                // Configura la griglia degli insegnanti
                if (bindingSource == null)
                {
                    bindingSource = new BindingSource();
                }

                bindingSource.DataSource = insegnanti.Items;

                // Verifica che dgvInsegnanti non sia null
                if (dgvInsegnanti == null)
                {
                    MessageBox.Show("Il controllo DataGridView non è inizializzato correttamente.",
                        "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                    return;
                }

                dgvInsegnanti.DataSource = bindingSource;

                // Verifica che le colonne esistano prima di configurarle
                if (dgvInsegnanti.Columns.Count > 0)
                {
                    // Verifica che ogni colonna esista prima di configurarla
                    if (dgvInsegnanti.Columns.Contains("Id"))
                        dgvInsegnanti.Columns["Id"].Visible = false;

                    if (dgvInsegnanti.Columns.Contains("Strumenti"))
                        dgvInsegnanti.Columns["Strumenti"].Visible = false;

                    // Nascondi TariffaOraria
                    if (dgvInsegnanti.Columns.Contains("TariffaOraria"))
                        dgvInsegnanti.Columns["TariffaOraria"].Visible = false;

                    // Nascondi NomeCompleto se esiste
                    if (dgvInsegnanti.Columns.Contains("NomeCompleto"))
                        dgvInsegnanti.Columns["NomeCompleto"].Visible = false;

                    if (dgvInsegnanti.Columns.Contains("Nome"))
                    {
                        dgvInsegnanti.Columns["Nome"].HeaderText = "Nome";
                        dgvInsegnanti.Columns["Nome"].Width = 100;
                    }

                    if (dgvInsegnanti.Columns.Contains("Cognome"))
                    {
                        dgvInsegnanti.Columns["Cognome"].HeaderText = "Cognome";
                        dgvInsegnanti.Columns["Cognome"].Width = 100;
                    }

                    if (dgvInsegnanti.Columns.Contains("Telefono"))
                    {
                        dgvInsegnanti.Columns["Telefono"].HeaderText = "Telefono";
                        dgvInsegnanti.Columns["Telefono"].Width = 100;
                    }

                    if (dgvInsegnanti.Columns.Contains("Email"))
                    {
                        dgvInsegnanti.Columns["Email"].HeaderText = "Email";
                        dgvInsegnanti.Columns["Email"].Width = 150;
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
            // Configura l'elenco dei mesi nella combobox
            cmbMese.Items.Clear();
            cmbMese.Items.AddRange(new string[] {
                "Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno",
                "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre"
            });

            // Imposta il mese corrente come selezione predefinita
            int meseCorrente = DateTime.Now.Month - 1; // 0-based index
            cmbMese.SelectedIndex = meseCorrente;

            // Configura l'elenco degli anni nella combobox
            cmbAnno.Items.Clear();
            int annoCorrente = DateTime.Now.Year;
            for (int i = annoCorrente - 2; i <= annoCorrente + 2; i++)
            {
                cmbAnno.Items.Add(i);
            }
            cmbAnno.SelectedItem = annoCorrente;
        }

        private void SetupEsportaMenu()
        {
            // Crea il menu contestuale per il pulsante Esporta
            ContextMenuStrip esportaMenu = new ContextMenuStrip();

            ToolStripMenuItem esportaFiltro = new ToolStripMenuItem("Esporta da Filtro");
            esportaFiltro.Click += (s, e) => EsportaFiltro();
            esportaMenu.Items.Add(esportaFiltro);

            ToolStripMenuItem esportaStorico = new ToolStripMenuItem("Esporta Storico");
            esportaStorico.Click += (s, e) => EsportaStorico();
            esportaMenu.Items.Add(esportaStorico);

            // Assegna il menu al pulsante (questo verrà fatto nel designer)
            btnEsporta.ContextMenuStrip = esportaMenu;
        }

        private void btnEsporta_Click(object sender, EventArgs e)
        {
            if (selectedInsegnante == null)
            {
                MessageBox.Show("Seleziona un insegnante.", "Attenzione",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            // Mostra il menu contestuale sotto il pulsante
            Point location = new Point(0, btnEsporta.Height);
            btnEsporta.ContextMenuStrip.Show(btnEsporta, location);
        }

        private void EsportaFiltro()
        {
            if (selectedInsegnante == null)
            {
                MessageBox.Show("Seleziona un insegnante.", "Attenzione",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            if (cmbMese.SelectedIndex < 0 || cmbAnno.SelectedIndex < 0)
            {
                MessageBox.Show("Seleziona mese e anno.", "Attenzione",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            int mese = cmbMese.SelectedIndex + 1;
            int anno = Convert.ToInt32(cmbAnno.SelectedItem);

            // Verifica se il mese/anno selezionato è il mese corrente
            bool isMeseCorrente = (mese == DateTime.Now.Month && anno == DateTime.Now.Year);
            bool includereLezioniPreviste = false;

            if (isMeseCorrente)
            {
                DialogResult result = MessageBox.Show(
                    $"Vuoi includere le lezioni previste per il resto del mese di {cmbMese.SelectedItem} {anno}?\n\n" +
                    "• Sì: Includerà tutte le lezioni fino alla fine del mese\n" +
                    "• No: Includerà solo le lezioni già svolte",
                    "Includere lezioni previste?",
                    MessageBoxButtons.YesNo,
                    MessageBoxIcon.Question);

                includereLezioniPreviste = (result == DialogResult.Yes);
            }

            var lezioni = GetLezioniInsegnante(selectedInsegnante.Id, mese, anno, includereLezioniPreviste);

            if (lezioni.Count == 0)
            {
                MessageBox.Show("Nessuna lezione trovata per il periodo selezionato.", "Informazione",
                    MessageBoxButtons.OK, MessageBoxIcon.Information);
                return;
            }

            string titoloRiepilogo = $"Riepilogo Compensi {cmbMese.SelectedItem} {anno}";
            if (isMeseCorrente && includereLezioniPreviste)
            {
                titoloRiepilogo += " (incluse lezioni previste)";
            }

            CreatePDF(lezioni, titoloRiepilogo);
        }

        private void EsportaStorico()
        {
            if (selectedInsegnante == null)
            {
                MessageBox.Show("Seleziona un insegnante.", "Attenzione",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            // Verifica se ci sono lezioni future nel mese corrente
            int meseCorrente = DateTime.Now.Month;
            int annoCorrente = DateTime.Now.Year;
            bool hasLezioniMeseCorrente = HasLezioniInMese(selectedInsegnante.Id, meseCorrente, annoCorrente);
            bool includereLezioniPreviste = false;

            if (hasLezioniMeseCorrente)
            {
                string nomeMese = new DateTime(annoCorrente, meseCorrente, 1).ToString("MMMM", new CultureInfo("it-IT"));
                DialogResult result = MessageBox.Show(
                    $"Vuoi includere le lezioni previste per il resto del mese corrente ({nomeMese} {annoCorrente})?\n\n" +
                    "• Sì: Includerà tutte le lezioni fino alla fine del mese corrente\n" +
                    "• No: Includerà solo le lezioni già svolte",
                    "Includere lezioni previste del mese corrente?",
                    MessageBoxButtons.YesNo,
                    MessageBoxIcon.Question);

                includereLezioniPreviste = (result == DialogResult.Yes);
            }

            var tutteLeLezioni = GetAllLezioniInsegnante(selectedInsegnante.Id, includereLezioniPreviste);

            if (tutteLeLezioni.Count == 0)
            {
                MessageBox.Show("Nessuna lezione trovata per questo insegnante.", "Informazione",
                    MessageBoxButtons.OK, MessageBoxIcon.Information);
                return;
            }

            string titoloRiepilogo = "Riepilogo Storico Compensi";
            if (hasLezioniMeseCorrente && includereLezioniPreviste)
            {
                titoloRiepilogo += " (incluse lezioni previste del mese corrente)";
            }

            CreatePDF(tutteLeLezioni, titoloRiepilogo);
        }

        // Metodo helper per verificare se ci sono lezioni in un determinato mese
        private bool HasLezioniInMese(int insegnanteId, int mese, int anno)
        {
            try
            {
                var prenotazioniList = MainForm.LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);
                if (prenotazioniList?.Items == null)
                    return false;

                DateTime oggi = DateTime.Now.Date;
                DateTime inizioMese = new DateTime(anno, mese, 1);
                DateTime fineMese = inizioMese.AddMonths(1).AddDays(-1);

                // Controlla se ci sono lezioni future nel mese corrente
                return prenotazioniList.Items.Any(p =>
                    p.InsegnanteId == insegnanteId &&
                    p.Data >= inizioMese &&
                    p.Data <= fineMese &&
                    p.Data > oggi); // Solo lezioni future
            }
            catch
            {
                return false;
            }
        }

        private bool IsLezioneDiCoppia(Prenotazione prenotazione, List<Models.Acquisto> acquisti, List<AcquistiModels.AcquistiPacchetto> pacchetti)
        {
            try
            {
                // Se non ci sono dati completi, assumiamo sia lezione singola
                if (prenotazione == null || acquisti == null || pacchetti == null)
                    return false;

                // Cerca l'acquisto associato alla prenotazione
                var acquisto = acquisti.FirstOrDefault(a => a.Id == prenotazione.AcquistoId);
                if (acquisto == null)
                    return false;

                // Cerca il pacchetto associato all'acquisto
                var pacchetto = pacchetti.FirstOrDefault(p => p.Id == acquisto.PacchettoId);
                if (pacchetto == null)
                    return false;

                // Verifica se è una lezione di coppia
                // Prima cerca la proprietà IsLezioneDiCoppia usando reflection
                var property = pacchetto.GetType().GetProperty("IsLezioneDiCoppia");
                if (property != null)
                {
                    return (bool)property.GetValue(pacchetto, null);
                }

                // Se la proprietà non esiste, cerca il tag nella descrizione
                string descrizione = pacchetto.Descrizione ?? string.Empty;
                return descrizione.Contains("[LEZIONE_COPPIA]");
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in IsLezioneDiCoppia: {ex.Message}");
                return false; // In caso di errore, assumiamo sia lezione singola
            }
        }

        // METODI MODIFICATI - Aggiunto il parametro includereLezioniPreviste
        private List<dynamic> GetLezioniInsegnante(int insegnanteId, int mese, int anno, bool includereLezioniPreviste = true)
        {
            try
            {
                var prenotazioniList = MainForm.LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);
                var acquisti = MainForm.LoadEncryptedXml<Models.AcquistiList>(acquistiFilePath);
                var pacchetti = MainForm.LoadEncryptedXml<AcquistiModels.AcquistiPacchettiList>(pacchettiFilePath);

                if (prenotazioniList == null || prenotazioniList.Items == null)
                    return new List<dynamic>();

                DateTime oggi = DateTime.Now.Date;
                DateTime inizioMese = new DateTime(anno, mese, 1);
                DateTime fineMese = inizioMese.AddMonths(1).AddDays(-1);

                // PRIMA filtra per insegnante, mese e anno
                var insegnanteLezioni = prenotazioniList.Items.Where(p =>
                    p.InsegnanteId == insegnanteId &&
                    p.Data >= inizioMese &&
                    p.Data <= fineMese
                ).ToList();

                // POI applica il filtro per le lezioni previste se necessario
                if (!includereLezioniPreviste && mese == DateTime.Now.Month && anno == DateTime.Now.Year)
                {
                    // Se non includere lezioni previste, prendi solo quelle già svolte o del passato
                    insegnanteLezioni = insegnanteLezioni.Where(p =>
                        p.Data <= oggi ||
                        p.Stato == StatoLezione.Svolta ||
                        p.Stato == StatoLezione.Assente
                    ).ToList();
                }
                // Se includereLezioniPreviste è true, include TUTTE le lezioni del mese (svolte e future)

                var insegnante = insegnanti.Items.FirstOrDefault(i => i.Id == insegnanteId);
                if (insegnante == null)
                    return new List<dynamic>();

                return insegnanteLezioni.Select(p =>
                {
                    double durataOre = p.OraFine.TotalMinutes - p.OraInizio.TotalMinutes;
                    durataOre = durataOre / 60.0;

                    // Verifica se la lezione è di coppia o singola
                    bool isLezioneDiCoppia = IsLezioneDiCoppia(p, acquisti?.Items, pacchetti?.Items);

                    // Scegli la tariffa appropriata in base al tipo di lezione
                    decimal tariffaApplicata = isLezioneDiCoppia ?
                        GetTariffaOrariaCoppia(insegnante) :
                        insegnante.TariffaOraria;

                    decimal compenso = tariffaApplicata * (decimal)durataOre;

                    return new
                    {
                        Id = p.Id,
                        Data = p.Data,
                        DataStr = p.Data.ToString("dd/MM/yyyy"),
                        Ora = $"{p.OraInizio.ToString(@"hh\:mm")} - {p.OraFine.ToString(@"hh\:mm")}",
                        Cliente = GetClienteName(p.ClienteId),
                        Strumento = p.Strumento,
                        TipoLezione = isLezioneDiCoppia ? "Coppia" : "Singola",
                        TariffaApplicata = tariffaApplicata,
                        TariffaApplicataStr = tariffaApplicata.ToString("C"),
                        Durata = $"{durataOre:F1} ore",
                        DurataOre = durataOre,
                        Compenso = compenso,
                        CompensoStr = compenso.ToString("C"),
                        StatoLezione = GetStatusText(p.Stato),
                        Stato = p.Data <= oggi ? "Svolta" : "Prevista"
                    };
                }).Cast<dynamic>()
                  .OrderBy(l => l.Data)
                  .ThenBy(l => l.Ora)
                  .ToList();
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore nel recupero delle lezioni: {ex.Message}");
                return new List<dynamic>();
            }
        }

        private List<dynamic> GetAllLezioniInsegnante(int insegnanteId, bool includereLezioniPreviste = true)
        {
            try
            {
                var prenotazioniList = MainForm.LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);
                var acquisti = MainForm.LoadEncryptedXml<Models.AcquistiList>(acquistiFilePath);
                var pacchetti = MainForm.LoadEncryptedXml<AcquistiModels.AcquistiPacchettiList>(pacchettiFilePath);

                if (prenotazioniList == null || prenotazioniList.Items == null)
                    return new List<dynamic>();

                DateTime oggi = DateTime.Now.Date;
                DateTime fineMeseCorrente = new DateTime(oggi.Year, oggi.Month, DateTime.DaysInMonth(oggi.Year, oggi.Month));

                // PRIMA filtra per insegnante
                var insegnanteLezioni = prenotazioniList.Items.Where(p =>
                    p.InsegnanteId == insegnanteId
                ).ToList();

                // POI applica il filtro per le lezioni previste se necessario
                if (!includereLezioniPreviste)
                {
                    // Se non includere lezioni previste, prendi solo:
                    // 1. Tutte le lezioni fino a ieri (compreso)
                    // 2. Esclude tutte le lezioni future (da oggi in poi)
                    insegnanteLezioni = insegnanteLezioni.Where(p => p.Data <= oggi).ToList();
                }
                else
                {
                    // Se includere lezioni previste, prendi:
                    // 1. Tutte le lezioni fino alla fine del mese corrente
                    // 2. Esclude le lezioni dei mesi futuri
                    insegnanteLezioni = insegnanteLezioni.Where(p => p.Data <= fineMeseCorrente).ToList();
                }

                var insegnante = insegnanti.Items.FirstOrDefault(i => i.Id == insegnanteId);
                if (insegnante == null)
                    return new List<dynamic>();

                return insegnanteLezioni.Select(p =>
                {
                    double durataOre = p.OraFine.TotalMinutes - p.OraInizio.TotalMinutes;
                    durataOre = durataOre / 60.0;

                    // Verifica se la lezione è di coppia o singola
                    bool isLezioneDiCoppia = IsLezioneDiCoppia(p, acquisti?.Items, pacchetti?.Items);

                    // Scegli la tariffa appropriata in base al tipo di lezione
                    decimal tariffaApplicata = isLezioneDiCoppia ?
                        GetTariffaOrariaCoppia(insegnante) :
                        insegnante.TariffaOraria;

                    decimal compenso = tariffaApplicata * (decimal)durataOre;

                    return new
                    {
                        Id = p.Id,
                        Data = p.Data,
                        DataStr = p.Data.ToString("dd/MM/yyyy"),
                        Ora = $"{p.OraInizio.ToString(@"hh\:mm")} - {p.OraFine.ToString(@"hh\:mm")}",
                        Cliente = GetClienteName(p.ClienteId),
                        Strumento = p.Strumento,
                        TipoLezione = isLezioneDiCoppia ? "Coppia" : "Singola",
                        TariffaApplicata = tariffaApplicata,
                        TariffaApplicataStr = tariffaApplicata.ToString("C"),
                        Durata = $"{durataOre:F1} ore",
                        DurataOre = durataOre,
                        Compenso = compenso,
                        CompensoStr = compenso.ToString("C"),
                        StatoLezione = GetStatusText(p.Stato),
                        Stato = p.Data <= oggi ? "Svolta" : "Prevista"
                    };
                }).Cast<dynamic>()
                  .OrderBy(l => l.Data)
                  .ThenBy(l => l.Ora)
                  .ToList();
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore nel recupero di tutte le lezioni: {ex.Message}");
                return new List<dynamic>();
            }
        }

        private string GetSchoolName()
        {
            try
            {
                using (RegistryKey key = Registry.CurrentUser.OpenSubKey(@"SOFTWARE\EasyBooking"))
                {
                    if (key != null)
                    {
                        object value = key.GetValue("ActivityName");
                        if (value != null)
                        {
                            return value.ToString();
                        }
                    }
                }
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore nel leggere il nome scuola dal registro: {ex.Message}");
            }
            return "Scuola di Musica";
        }

        private void CreatePDF(List<dynamic> lezioni, string titoloRiepilogo)
        {
            SaveFileDialog saveFileDialog = new SaveFileDialog();
            saveFileDialog.Filter = "PDF files (*.pdf)|*.pdf";
            saveFileDialog.Title = "Salva riepilogo compensi PDF";
            saveFileDialog.FileName = $"Compensi_{selectedInsegnante.Cognome}_{selectedInsegnante.Nome}_{DateTime.Now:yyyyMMdd}.pdf";

            if (saveFileDialog.ShowDialog() == DialogResult.OK)
            {
                try
                {
                    Document document = new Document(PageSize.A4, 50, 50, 50, 50);
                    PdfWriter writer = PdfWriter.GetInstance(document, new FileStream(saveFileDialog.FileName, FileMode.Create));
                    document.Open();

                    // Font
                    BaseFont baseFont = BaseFont.CreateFont(BaseFont.HELVETICA, BaseFont.CP1252, BaseFont.NOT_EMBEDDED);
                    iTextSharp.text.Font titleFont = new iTextSharp.text.Font(baseFont, 18, iTextSharp.text.Font.BOLD);
                    iTextSharp.text.Font subtitleFont = new iTextSharp.text.Font(baseFont, 14, iTextSharp.text.Font.BOLD);
                    iTextSharp.text.Font headerFont = new iTextSharp.text.Font(baseFont, 12, iTextSharp.text.Font.BOLD);
                    iTextSharp.text.Font normalFont = new iTextSharp.text.Font(baseFont, 10, iTextSharp.text.Font.NORMAL);
                    iTextSharp.text.Font boldFont = new iTextSharp.text.Font(baseFont, 10, iTextSharp.text.Font.BOLD);
                    iTextSharp.text.Font smallFont = new iTextSharp.text.Font(baseFont, 8, iTextSharp.text.Font.NORMAL);

                    // Titolo su due righe
                    string schoolName = GetSchoolName();

                    // Prima riga: Nome della scuola
                    Paragraph schoolTitle = new Paragraph(schoolName, titleFont);
                    schoolTitle.Alignment = Element.ALIGN_CENTER;
                    document.Add(schoolTitle);

                    // Seconda riga: Oggetto del riepilogo
                    Paragraph reportTitle = new Paragraph(titoloRiepilogo, subtitleFont);
                    reportTitle.Alignment = Element.ALIGN_CENTER;
                    reportTitle.SpacingBefore = 5f; // Piccolo spazio tra le due righe
                    document.Add(reportTitle);

                    document.Add(new Paragraph(" ")); // 

                    // Dati Insegnante
                    document.Add(new Paragraph("DATI INSEGNANTE", headerFont));
                    document.Add(new Paragraph($"Nome: {selectedInsegnante.Nome} {selectedInsegnante.Cognome}", normalFont));
                    if (!string.IsNullOrEmpty(selectedInsegnante.Telefono))
                        document.Add(new Paragraph($"Telefono: {selectedInsegnante.Telefono}", normalFont));
                    if (!string.IsNullOrEmpty(selectedInsegnante.Email))
                        document.Add(new Paragraph($"Email: {selectedInsegnante.Email}", normalFont));

                    // Tariffe orarie
                    document.Add(new Paragraph($"Tariffa Oraria: {selectedInsegnante.TariffaOraria:C}", normalFont));

                    // Aggiungi la tariffa oraria coppia solo se diversa dalla tariffa oraria standard
                    decimal tariffaOrariaCoppia = GetTariffaOrariaCoppia(selectedInsegnante);
                    if (tariffaOrariaCoppia != selectedInsegnante.TariffaOraria)
                    {
                        document.Add(new Paragraph($"Tariffa Oraria Coppia: {tariffaOrariaCoppia:C}", normalFont));
                    }

                    // Strumenti insegnati
                    if (selectedInsegnante.Strumenti != null && selectedInsegnante.Strumenti.Count > 0)
                    {
                        document.Add(new Paragraph($"Strumenti: {string.Join(", ", selectedInsegnante.Strumenti)}", normalFont));
                    }
                    document.Add(new Paragraph(" ")); // Spazio

                    // Tabella Lezioni
                    document.Add(new Paragraph("DETTAGLIO LEZIONI", headerFont));
                    document.Add(new Paragraph(" ", new iTextSharp.text.Font(baseFont, 6))); // Spazio piccolo

                    if (lezioni.Count > 0)
                    {
                        PdfPTable lezioniTable = new PdfPTable(7); // 7 colonne con l'aggiunta del tipo di lezione
                        lezioniTable.WidthPercentage = 100;
                        lezioniTable.SetWidths(new float[] { 2f, 2f, 2.5f, 2f, 1.5f, 1.5f, 2f });

                        // Header tabella lezioni
                        lezioniTable.AddCell(new PdfPCell(new Phrase("Data", headerFont)));
                        lezioniTable.AddCell(new PdfPCell(new Phrase("Orario", headerFont)));
                        lezioniTable.AddCell(new PdfPCell(new Phrase("Cliente", headerFont)));
                        lezioniTable.AddCell(new PdfPCell(new Phrase("Strumento", headerFont)));
                        lezioniTable.AddCell(new PdfPCell(new Phrase("Tipo", headerFont))); // Nuova colonna per il tipo di lezione
                        lezioniTable.AddCell(new PdfPCell(new Phrase("Durata", headerFont)));
                        lezioniTable.AddCell(new PdfPCell(new Phrase("Compenso", headerFont)));

                        // Righe della tabella lezioni
                        decimal totaleCompenso = 0;
                        double totaleDurataOre = 0;

                        foreach (dynamic lezione in lezioni)
                        {
                            lezioniTable.AddCell(new PdfPCell(new Phrase(lezione.DataStr, normalFont)));
                            lezioniTable.AddCell(new PdfPCell(new Phrase(lezione.Ora, normalFont)));
                            lezioniTable.AddCell(new PdfPCell(new Phrase(lezione.Cliente ?? "N/A", normalFont)));
                            lezioniTable.AddCell(new PdfPCell(new Phrase(lezione.Strumento ?? "N/A", normalFont)));
                            lezioniTable.AddCell(new PdfPCell(new Phrase(lezione.TipoLezione, normalFont))); // Tipo di lezione
                            lezioniTable.AddCell(new PdfPCell(new Phrase(lezione.Durata, normalFont)));
                            lezioniTable.AddCell(new PdfPCell(new Phrase(lezione.CompensoStr, normalFont)));

                            totaleCompenso += lezione.Compenso;
                            totaleDurataOre += lezione.DurataOre;
                        }

                        document.Add(lezioniTable);

                        // Riepilogo totali
                        document.Add(new Paragraph(" ")); // Spazio
                        document.Add(new Paragraph("RIEPILOGO", headerFont));
                        document.Add(new Paragraph($"Totale lezioni: {lezioni.Count}", normalFont));
                        document.Add(new Paragraph($"Totale ore: {totaleDurataOre:F1}", normalFont));
                        document.Add(new Paragraph($"Totale compenso: {totaleCompenso:C}", normalFont));

                        // Riepilogo per tipo di lezione
                        document.Add(new Paragraph(" ")); // Spazio
                        document.Add(new Paragraph("RIEPILOGO PER TIPOLOGIA", headerFont));
                        document.Add(new Paragraph(" ", new iTextSharp.text.Font(baseFont, 6))); // Spazio piccolo

                        // Group by tipologia
                        var perTipologia = new Dictionary<string, Tuple<int, double, decimal>>();

                        foreach (var lezione in lezioni)
                        {
                            string tipoLezione = lezione.TipoLezione;
                            if (!perTipologia.ContainsKey(tipoLezione))
                            {
                                perTipologia[tipoLezione] = new Tuple<int, double, decimal>(0, 0, 0);
                            }

                            var current = perTipologia[tipoLezione];
                            perTipologia[tipoLezione] = new Tuple<int, double, decimal>(
                                current.Item1 + 1,
                                current.Item2 + lezione.DurataOre,
                                current.Item3 + lezione.Compenso);
                        }

                        PdfPTable tipologiaTable = new PdfPTable(4);
                        tipologiaTable.WidthPercentage = 80;
                        tipologiaTable.SetWidths(new float[] { 2f, 1.5f, 1.5f, 2f });

                        tipologiaTable.AddCell(new PdfPCell(new Phrase("Tipologia", headerFont)));
                        tipologiaTable.AddCell(new PdfPCell(new Phrase("Lezioni", headerFont)));
                        tipologiaTable.AddCell(new PdfPCell(new Phrase("Ore", headerFont)));
                        tipologiaTable.AddCell(new PdfPCell(new Phrase("Compenso", headerFont)));

                        foreach (var item in perTipologia)
                        {
                            tipologiaTable.AddCell(new PdfPCell(new Phrase(item.Key, normalFont)));
                            tipologiaTable.AddCell(new PdfPCell(new Phrase(item.Value.Item1.ToString(), normalFont)));
                            tipologiaTable.AddCell(new PdfPCell(new Phrase($"{item.Value.Item2:F1}", normalFont)));
                            tipologiaTable.AddCell(new PdfPCell(new Phrase(item.Value.Item3.ToString("C"), normalFont)));
                        }

                        // Aggiunta riga Totale
                        PdfPCell totaleTipologiaCell = new PdfPCell(new Phrase("TOTALE", boldFont));
                        totaleTipologiaCell.BackgroundColor = new BaseColor(240, 240, 240);
                        tipologiaTable.AddCell(totaleTipologiaCell);

                        tipologiaTable.AddCell(new PdfPCell(new Phrase(lezioni.Count.ToString(), boldFont)) { BackgroundColor = new BaseColor(240, 240, 240) });
                        tipologiaTable.AddCell(new PdfPCell(new Phrase($"{totaleDurataOre:F1}", boldFont)) { BackgroundColor = new BaseColor(240, 240, 240) });
                        tipologiaTable.AddCell(new PdfPCell(new Phrase(totaleCompenso.ToString("C"), boldFont)) { BackgroundColor = new BaseColor(240, 240, 240) });

                        document.Add(tipologiaTable);

                        // Riepilogo per strumento
                        document.Add(new Paragraph(" ")); // Spazio
                        document.Add(new Paragraph("RIEPILOGO PER STRUMENTO", headerFont));
                        document.Add(new Paragraph(" ", new iTextSharp.text.Font(baseFont, 6))); // Spazio piccolo

                        // Group by strumento
                        var perStrumento = new Dictionary<string, Tuple<int, double, decimal>>();

                        foreach (var lezione in lezioni)
                        {
                            string strumento = lezione.Strumento ?? "N/A";
                            if (!perStrumento.ContainsKey(strumento))
                            {
                                perStrumento[strumento] = new Tuple<int, double, decimal>(0, 0, 0);
                            }

                            var current = perStrumento[strumento];
                            perStrumento[strumento] = new Tuple<int, double, decimal>(
                                current.Item1 + 1,
                                current.Item2 + lezione.DurataOre,
                                current.Item3 + lezione.Compenso);
                        }

                        PdfPTable strumentoTable = new PdfPTable(4);
                        strumentoTable.WidthPercentage = 80;
                        strumentoTable.SetWidths(new float[] { 2f, 1.5f, 1.5f, 2f });

                        strumentoTable.AddCell(new PdfPCell(new Phrase("Strumento", headerFont)));
                        strumentoTable.AddCell(new PdfPCell(new Phrase("Lezioni", headerFont)));
                        strumentoTable.AddCell(new PdfPCell(new Phrase("Ore", headerFont)));
                        strumentoTable.AddCell(new PdfPCell(new Phrase("Compenso", headerFont)));

                        // Ordina gli strumenti alfabeticamente
                        var strumentiOrdinati = perStrumento.Keys.OrderBy(s => s).ToList();

                        foreach (var strumento in strumentiOrdinati)
                        {
                            var item = perStrumento[strumento];
                            strumentoTable.AddCell(new PdfPCell(new Phrase(strumento, normalFont)));
                            strumentoTable.AddCell(new PdfPCell(new Phrase(item.Item1.ToString(), normalFont)));
                            strumentoTable.AddCell(new PdfPCell(new Phrase($"{item.Item2:F1}", normalFont)));
                            strumentoTable.AddCell(new PdfPCell(new Phrase(item.Item3.ToString("C"), normalFont)));
                        }

                        // Aggiunta riga Totale
                        PdfPCell totaleStrumentoCell = new PdfPCell(new Phrase("TOTALE", boldFont));
                        totaleStrumentoCell.BackgroundColor = new BaseColor(240, 240, 240);
                        strumentoTable.AddCell(totaleStrumentoCell);

                        strumentoTable.AddCell(new PdfPCell(new Phrase(lezioni.Count.ToString(), boldFont)) { BackgroundColor = new BaseColor(240, 240, 240) });
                        strumentoTable.AddCell(new PdfPCell(new Phrase($"{totaleDurataOre:F1}", boldFont)) { BackgroundColor = new BaseColor(240, 240, 240) });
                        strumentoTable.AddCell(new PdfPCell(new Phrase(totaleCompenso.ToString("C"), boldFont)) { BackgroundColor = new BaseColor(240, 240, 240) });

                        document.Add(strumentoTable);

                        // Raggruppa per mese se è uno storico
                        if (titoloRiepilogo.Contains("Storico") && lezioni.Count > 1)
                        {
                            document.Add(new Paragraph(" ")); // Spazio
                            document.Add(new Paragraph("RIEPILOGO PER MESE", headerFont));
                            document.Add(new Paragraph(" ", new iTextSharp.text.Font(baseFont, 6))); // Spazio piccolo

                            var raggruppatoPerMese = new Dictionary<string, Tuple<int, double, decimal>>();

                            foreach (var lezione in lezioni)
                            {
                                string[] nomiMesi = {
                            "", "Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno",
                            "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre"
                        };

                                string chiave = $"{nomiMesi[lezione.Data.Month]} {lezione.Data.Year}";

                                if (!raggruppatoPerMese.ContainsKey(chiave))
                                {
                                    raggruppatoPerMese[chiave] = new Tuple<int, double, decimal>(0, 0, 0);
                                }

                                var current = raggruppatoPerMese[chiave];
                                raggruppatoPerMese[chiave] = new Tuple<int, double, decimal>(
                                    current.Item1 + 1,
                                    current.Item2 + lezione.DurataOre,
                                    current.Item3 + lezione.Compenso);
                            }

                            PdfPTable mesiTable = new PdfPTable(4); // 4 colonne
                            mesiTable.WidthPercentage = 80;
                            mesiTable.SetWidths(new float[] { 3f, 2f, 2f, 2f });

                            // Header tabella mesi
                            mesiTable.AddCell(new PdfPCell(new Phrase("Mese/Anno", headerFont)));
                            mesiTable.AddCell(new PdfPCell(new Phrase("Lezioni", headerFont)));
                            mesiTable.AddCell(new PdfPCell(new Phrase("Ore", headerFont)));
                            mesiTable.AddCell(new PdfPCell(new Phrase("Compenso", headerFont)));

                            // Ordina le chiavi per anno e mese
                            var chiavi = raggruppatoPerMese.Keys.ToList();
                            chiavi.Sort((a, b) => {
                                int annoA = int.Parse(a.Split(' ')[1]);
                                int annoB = int.Parse(b.Split(' ')[1]);
                                if (annoA != annoB) return annoA.CompareTo(annoB);

                                string meseA = a.Split(' ')[0];
                                string meseB = b.Split(' ')[0];
                                int indiceA = Array.IndexOf(new string[] {
                            "Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno",
                            "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre"
                        }, meseA);
                                int indiceB = Array.IndexOf(new string[] {
                            "Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno",
                            "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre"
                        }, meseB);

                                return indiceA.CompareTo(indiceB);
                            });

                            foreach (var chiave in chiavi)
                            {
                                var dati = raggruppatoPerMese[chiave];
                                mesiTable.AddCell(new PdfPCell(new Phrase(chiave, normalFont)));
                                mesiTable.AddCell(new PdfPCell(new Phrase(dati.Item1.ToString(), normalFont)));
                                mesiTable.AddCell(new PdfPCell(new Phrase($"{dati.Item2:F1}", normalFont)));
                                mesiTable.AddCell(new PdfPCell(new Phrase(dati.Item3.ToString("C"), normalFont)));
                            }

                            // Aggiunta riga Totale
                            PdfPCell totaleMeseCell = new PdfPCell(new Phrase("TOTALE", boldFont));
                            totaleMeseCell.BackgroundColor = new BaseColor(240, 240, 240);
                            mesiTable.AddCell(totaleMeseCell);

                            mesiTable.AddCell(new PdfPCell(new Phrase(lezioni.Count.ToString(), boldFont)) { BackgroundColor = new BaseColor(240, 240, 240) });
                            mesiTable.AddCell(new PdfPCell(new Phrase($"{totaleDurataOre:F1}", boldFont)) { BackgroundColor = new BaseColor(240, 240, 240) });
                            mesiTable.AddCell(new PdfPCell(new Phrase(totaleCompenso.ToString("C"), boldFont)) { BackgroundColor = new BaseColor(240, 240, 240) });

                            document.Add(mesiTable);
                        }
                    }
                    else
                    {
                        document.Add(new Paragraph("Nessuna lezione trovata.", normalFont));
                    }

                    // Data di generazione in basso a sinistra
                    float yPosition = document.BottomMargin + smallFont.Size;
                    PdfContentByte cb = writer.DirectContent;
                    cb.BeginText();
                    cb.SetFontAndSize(baseFont, 8);
                    cb.SetTextMatrix(document.LeftMargin, yPosition);
                    cb.ShowText($"Generato il: {DateTime.Now:dd/MM/yyyy HH:mm}");
                    cb.EndText();

                    document.Close();

                    // Chiedi se aprire il PDF
                    if (MessageBox.Show("Vuoi aprire il PDF appena creato?", "Aprire PDF",
                        MessageBoxButtons.YesNo, MessageBoxIcon.Question) == DialogResult.Yes)
                    {
                        Process.Start(saveFileDialog.FileName);
                    }
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"Errore durante la creazione del PDF: {ex.Message}", "Errore",
                        MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
        }

        private void DisableDetailForm()
        {
            txtNome.Text = string.Empty;
            txtCognome.Text = string.Empty;
            txtTelefono.Text = string.Empty;
            txtEmail.Text = string.Empty;
            txtTariffaOraria.Text = string.Empty;
            txtTariffaOrariaCoppia.Text = string.Empty; // Reset campo tariffa coppia
            clbStrumenti.Items.Clear();

            txtNome.Enabled = false;
            txtCognome.Enabled = false;
            txtTelefono.Enabled = false;
            txtEmail.Enabled = false;
            txtTariffaOraria.Enabled = false;
            txtTariffaOrariaCoppia.Enabled = false; // Disabilita campo tariffa coppia
            clbStrumenti.Enabled = false;

            btnSalva.Enabled = false;
            btnElimina.Enabled = false;
            btnWhatsapp.Enabled = false;
            btnEmail.Enabled = false;
            btnEsporta.Enabled = false;

            // Pulisce la vista riepilogo
            dgvRiepilogoMensile.DataSource = null;
            dgvLezioniFuture.DataSource = null;
            lblTotaleLezioni.Text = "Totale lezioni: 0";
            lblTotaleCompenso.Text = "Totale compenso: € 0,00";

            selectedInsegnante = null;
        }

        private void EnableDetailForm()
        {
            txtNome.Enabled = true;
            txtCognome.Enabled = true;
            txtTelefono.Enabled = true;
            txtEmail.Enabled = true;
            txtTariffaOraria.Enabled = true;
            txtTariffaOrariaCoppia.Enabled = true; // Abilita campo tariffa coppia
            clbStrumenti.Enabled = true;

            btnSalva.Enabled = true;
            btnElimina.Enabled = true;
            btnEsporta.Enabled = true;
        }

        private void LoadInsegnanteDetails(Insegnante insegnante)
        {
            selectedInsegnante = insegnante;

            txtNome.Text = insegnante.Nome ?? string.Empty;
            txtCognome.Text = insegnante.Cognome ?? string.Empty;
            txtTelefono.Text = insegnante.Telefono ?? string.Empty;
            txtEmail.Text = insegnante.Email ?? string.Empty;
            txtTariffaOraria.Text = insegnante.TariffaOraria.ToString("F2");
            txtTariffaOrariaCoppia.Text = GetTariffaOrariaCoppia(insegnante).ToString("F2"); // Carica tariffa coppia

            // Verifica che Strumenti non sia null
            if (insegnante.Strumenti == null)
            {
                insegnante.Strumenti = new List<string>();
            }

            // Carica la lista degli strumenti
            LoadStrumentiList(insegnante);

            EnableDetailForm();

            btnWhatsapp.Enabled = !string.IsNullOrEmpty(insegnante.Telefono);
            btnEmail.Enabled = !string.IsNullOrEmpty(insegnante.Email);

            // Carica il riepilogo mensile delle lezioni
            LoadMonthlyReport();

            // Carica le lezioni future
            LoadLezioniFuture();
        }

        private void LoadLezioniFuture()
        {
            if (selectedInsegnante == null)
                return;

            try
            {
                // Carica le prenotazioni dal file XML
                var prenotazioniList = MainForm.LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);

                if (prenotazioniList == null || prenotazioniList.Items == null)
                {
                    dgvLezioniFuture.DataSource = null;
                    return;
                }

                // Filtra le lezioni future per l'insegnante selezionato (non Svolta)
                var lezioniFuture = prenotazioniList.Items.Where(p =>
                    p.InsegnanteId == selectedInsegnante.Id &&
                    p.Stato != StatoLezione.Svolta &&
                    p.Data >= DateTime.Today  // Solo lezioni da oggi in poi
                ).Select(p => new
                {
                    Id = p.Id,
                    Data = p.Data.ToString("dd/MM/yyyy"),
                    DataSort = p.Data,
                    Ora = $"{p.OraInizio.ToString(@"hh\:mm")} - {p.OraFine.ToString(@"hh\:mm")}",
                    Cliente = GetClienteName(p.ClienteId),
                    Strumento = p.Strumento,
                    Stato = GetStatusText(p.Stato),
                }).OrderBy(l => l.DataSort)
                  .ThenBy(l => l.Ora)
                  .ToList();

                dgvLezioniFuture.DataSource = lezioniFuture;

                if (dgvLezioniFuture.Columns.Count > 0)
                {
                    dgvLezioniFuture.Columns["Id"].Visible = false;
                    dgvLezioniFuture.Columns["DataSort"].Visible = false;

                    dgvLezioniFuture.Columns["Data"].Width = 80;
                    dgvLezioniFuture.Columns["Ora"].Width = 90;
                    dgvLezioniFuture.Columns["Cliente"].Width = 150;
                    dgvLezioniFuture.Columns["Strumento"].Width = 100;
                    dgvLezioniFuture.Columns["Stato"].Width = 100;
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore nel caricamento delle lezioni future: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                dgvLezioniFuture.DataSource = null;
            }
        }

        private void LoadStrumentiList(Insegnante insegnante)
        {
            clbStrumenti.Items.Clear();

            // Carica tutti gli strumenti disponibili
            var strumentiList = MainForm.LoadEncryptedXml<StrumentiList>(strumentiFilePath);

            if (strumentiList == null)
            {
                strumentiList = new StrumentiList();
            }

            if (strumentiList.Items == null)
            {
                strumentiList.Items = new List<Strumento>();
                MainForm.SaveEncryptedXml(strumentiList, strumentiFilePath);
            }

            foreach (var strumento in strumentiList.Items)
            {
                bool isChecked = insegnante.Strumenti.Contains(strumento.Nome);
                clbStrumenti.Items.Add(strumento.Nome, isChecked);
            }
        }

        private void LoadMonthlyReport()
        {
            if (selectedInsegnante == null ||
                cmbMese.SelectedIndex < 0 ||
                cmbAnno.SelectedIndex < 0)
                return;

            try
            {
                int mese = cmbMese.SelectedIndex + 1; // 1-based month
                int anno = Convert.ToInt32(cmbAnno.SelectedItem);

                // Carica i dati necessari per il report
                var prenotazioniList = MainForm.LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);
                var acquisti = MainForm.LoadEncryptedXml<Models.AcquistiList>(acquistiFilePath);
                var pacchetti = MainForm.LoadEncryptedXml<AcquistiModels.AcquistiPacchettiList>(pacchettiFilePath);

                if (prenotazioniList == null)
                {
                    prenotazioniList = new PrenotazioniList();
                }

                if (prenotazioniList.Items == null)
                {
                    prenotazioniList.Items = new List<Prenotazione>();
                    MainForm.SaveEncryptedXml(prenotazioniList, prenotazioniFilePath);
                }

                // Filtra le lezioni per l'insegnante selezionato e il mese/anno selezionato
                var insegnanteLezioni = prenotazioniList.Items.Where(p =>
                    p.InsegnanteId == selectedInsegnante.Id &&
                    p.Data.Month == mese &&
                    p.Data.Year == anno &&
                    (p.Stato == StatoLezione.Svolta || p.Stato == StatoLezione.Assente)
                ).ToList();

                // Calcola la durata di ogni lezione in ore per il compenso
                var lezioniConCompenso = insegnanteLezioni.Select(p =>
                {
                    double durataOre = p.OraFine.TotalMinutes - p.OraInizio.TotalMinutes;
                    durataOre = durataOre / 60.0; // Converti in ore

                    // Verifica se la lezione è di coppia o singola
                    bool isLezioneDiCoppia = IsLezioneDiCoppia(p, acquisti?.Items, pacchetti?.Items);

                    // Scegli la tariffa appropriata in base al tipo di lezione
                    decimal tariffaApplicata = isLezioneDiCoppia ?
                        GetTariffaOrariaCoppia(selectedInsegnante) :
                        selectedInsegnante.TariffaOraria;

                    decimal compenso = tariffaApplicata * (decimal)durataOre;

                    return new
                    {
                        Data = p.Data.ToString("dd/MM/yyyy"),
                        Ora = $"{p.OraInizio.ToString(@"hh\:mm")} - {p.OraFine.ToString(@"hh\:mm")}",
                        Cliente = GetClienteName(p.ClienteId),
                        Strumento = p.Strumento,
                        TipoLezione = isLezioneDiCoppia ? "Coppia" : "Singola",
                        TariffaOraria = tariffaApplicata.ToString("C"),
                        Durata = $"{durataOre:F1} ore",
                        Compenso = compenso.ToString("C"),
                        StatoLezione = GetStatusText(p.Stato),
                        CompensoBrutto = compenso // Per il calcolo del totale
                    };
                }).OrderBy(l => DateTime.ParseExact(l.Data, "dd/MM/yyyy", CultureInfo.InvariantCulture))
                  .ThenBy(l => l.Ora)
                  .ToList();

                dgvRiepilogoMensile.DataSource = lezioniConCompenso;

                if (dgvRiepilogoMensile.Columns.Count > 0)
                {
                    dgvRiepilogoMensile.Columns["Data"].Width = 70;
                    dgvRiepilogoMensile.Columns["Ora"].Width = 75;
                    dgvRiepilogoMensile.Columns["Cliente"].Width = 125;
                    dgvRiepilogoMensile.Columns["Strumento"].Width = 75;
                    dgvRiepilogoMensile.Columns["TipoLezione"].Width = 60;
                    dgvRiepilogoMensile.Columns["TariffaOraria"].Width = 70;
                    dgvRiepilogoMensile.Columns["Durata"].Width = 60;
                    dgvRiepilogoMensile.Columns["Compenso"].Width = 70;
                    dgvRiepilogoMensile.Columns["StatoLezione"].Width = 80;

                    // Nascondi la colonna CompensoBrutto usata solo per i calcoli
                    dgvRiepilogoMensile.Columns["CompensoBrutto"].Visible = false;
                }

                // Aggiorna i totali
                int totaleLezioni = lezioniConCompenso.Count;
                decimal totaleCompenso = lezioniConCompenso.Sum(l => (decimal)l.GetType().GetProperty("CompensoBrutto").GetValue(l, null));

                lblTotaleLezioni.Text = $"Totale lezioni: {totaleLezioni}";
                lblTotaleCompenso.Text = $"Totale compenso: {totaleCompenso:C}";
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore nel caricamento delle lezioni: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                dgvRiepilogoMensile.DataSource = null;
            }
        }

        private string GetClienteName(int clienteId)
        {
            try
            {
                // Carica i clienti
                var clientiList = MainForm.LoadEncryptedXml<ClientiList>(clientiFilePath);

                if (clientiList == null)
                {
                    clientiList = new ClientiList();
                }

                if (clientiList.Items == null)
                {
                    clientiList.Items = new List<Cliente>();
                    return "N/A";
                }

                var cliente = clientiList.Items.FirstOrDefault(c => c.Id == clienteId);
                return cliente != null ? $"{cliente.Cognome} {cliente.Nome}" : "N/A";
            }
            catch
            {
                return "N/A";
            }
        }

        private string GetStatusText(StatoLezione stato)
        {
            switch (stato)
            {
                case StatoLezione.Programmata:
                    return "Programmata";
                case StatoLezione.Svolta:
                    return "Svolta";
                case StatoLezione.Assente:
                    return "Assente";
                case StatoLezione.Rimandata:
                    return "Rimandata";
                case StatoLezione.Riprogrammata:
                    return "Riprogrammata";
                default:
                    return "Sconosciuto";
            }
        }

        private void dgvInsegnanti_CellClick(object sender, DataGridViewCellEventArgs e)
        {
            if (e.RowIndex >= 0 && e.RowIndex < insegnanti.Items.Count)
            {
                var insegnante = insegnanti.Items[e.RowIndex];
                LoadInsegnanteDetails(insegnante);
            }
        }

        private void dgvInsegnanti_CellDoubleClick(object sender, DataGridViewCellEventArgs e)
        {
            if (e.RowIndex >= 0 && e.RowIndex < insegnanti.Items.Count)
            {
                var insegnante = insegnanti.Items[e.RowIndex];
                LoadInsegnanteDetails(insegnante);
            }
        }

        private void btnSalva_Click(object sender, EventArgs e)
        {
            if (selectedInsegnante == null)
                return;

            if (string.IsNullOrWhiteSpace(txtNome.Text) || string.IsNullOrWhiteSpace(txtCognome.Text))
            {
                MessageBox.Show("Nome e Cognome sono campi obbligatori.",
                    "Validazione", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            if (!string.IsNullOrWhiteSpace(txtEmail.Text) && !IsValidEmail(txtEmail.Text))
            {
                MessageBox.Show("Formato email non valido.",
                    "Validazione", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            if (!decimal.TryParse(txtTariffaOraria.Text, out decimal tariffaOraria) || tariffaOraria < 0)
            {
                MessageBox.Show("Tariffa oraria non valida. Inserisci un numero maggiore o uguale a zero.",
                    "Validazione", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            // Validazione tariffa coppia
            if (!decimal.TryParse(txtTariffaOrariaCoppia.Text, out decimal tariffaOrariaCoppia) || tariffaOrariaCoppia < 0)
            {
                MessageBox.Show("Tariffa oraria per lezioni di coppia non valida. Inserisci un numero maggiore o uguale a zero.",
                    "Validazione", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            selectedInsegnante.Nome = txtNome.Text.Trim();
            selectedInsegnante.Cognome = txtCognome.Text.Trim();
            selectedInsegnante.Telefono = txtTelefono.Text.Trim();
            selectedInsegnante.Email = txtEmail.Text.Trim();
            selectedInsegnante.TariffaOraria = tariffaOraria;
            SetTariffaOrariaCoppia(selectedInsegnante, tariffaOrariaCoppia); // Salva tariffa coppia

            if (selectedInsegnante.Strumenti == null)
            {
                selectedInsegnante.Strumenti = new List<string>();
            }

            selectedInsegnante.Strumenti.Clear();
            for (int i = 0; i < clbStrumenti.Items.Count; i++)
            {
                if (clbStrumenti.GetItemChecked(i))
                {
                    selectedInsegnante.Strumenti.Add(clbStrumenti.Items[i].ToString());
                }
            }

            MainForm.SaveEncryptedXml(insegnanti, insegnantiFilePath);

            // Salva tariffe di coppia se necessario
            SaveTariffeDiCoppia();

            bindingSource.ResetBindings(false);

            // Aggiorna la vista delle lezioni per riflettere eventuali cambiamenti nei compensi
            LoadMonthlyReport();

            MessageBox.Show("Dati insegnante salvati con successo.",
                "Salvataggio completato", MessageBoxButtons.OK, MessageBoxIcon.Information);
        }

        private void btnNuovo_Click(object sender, EventArgs e)
        {
            var nuovoInsegnante = new Insegnante
            {
                Id = insegnanti.Items.Count > 0 ? insegnanti.Items.Max(i => i.Id) + 1 : 1,
                TariffaOraria = 0,
                Strumenti = new List<string>()
            };

            // Imposta la tariffa di coppia iniziale a zero 
            SetTariffaOrariaCoppia(nuovoInsegnante, 0);

            insegnanti.Items.Add(nuovoInsegnante);
            bindingSource.ResetBindings(false);

            dgvInsegnanti.ClearSelection();
            int lastIndex = dgvInsegnanti.Rows.Count - 1;
            if (lastIndex >= 0)
            {
                dgvInsegnanti.Rows[lastIndex].Selected = true;
                LoadInsegnanteDetails(nuovoInsegnante);
                txtNome.Focus();
            }
        }

        private void btnElimina_Click(object sender, EventArgs e)
        {
            if (selectedInsegnante == null)
                return;

            var result = MessageBox.Show(
                $"Sei sicuro di voler eliminare l'insegnante {selectedInsegnante.Nome} {selectedInsegnante.Cognome}?",
                "Conferma eliminazione",
                MessageBoxButtons.YesNo,
                MessageBoxIcon.Question);

            if (result == DialogResult.Yes)
            {
                var prenotazioniList = MainForm.LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);
                if (prenotazioniList != null && prenotazioniList.Items != null &&
                    prenotazioniList.Items.Any(p => p.InsegnanteId == selectedInsegnante.Id))
                {
                    var confirmResult = MessageBox.Show(
                        "Questo insegnante ha delle lezioni associate. L'eliminazione potrebbe causare errori nel sistema. Continuare?",
                        "Attenzione",
                        MessageBoxButtons.YesNo,
                        MessageBoxIcon.Warning);

                    if (confirmResult != DialogResult.Yes)
                    {
                        return;
                    }
                }

                // Rimuovi anche la tariffa di coppia se esistente
                if (tariffeDiCoppia.ContainsKey(selectedInsegnante.Id))
                {
                    tariffeDiCoppia.Remove(selectedInsegnante.Id);
                    SaveTariffeDiCoppia();
                }

                insegnanti.Items.Remove(selectedInsegnante);
                MainForm.SaveEncryptedXml(insegnanti, insegnantiFilePath);
                bindingSource.ResetBindings(false);
                DisableDetailForm();

                MessageBox.Show("Insegnante eliminato con successo.",
                    "Eliminazione", MessageBoxButtons.OK, MessageBoxIcon.Information);
            }
        }

        private void btnWhatsapp_Click(object sender, EventArgs e)
        {
            if (selectedInsegnante != null && !string.IsNullOrEmpty(selectedInsegnante.Telefono))
            {
                string phoneNumber = new string(selectedInsegnante.Telefono.Where(c => char.IsDigit(c)).ToArray());

                if (phoneNumber.Length > 0)
                {
                    try
                    {
                        if (!phoneNumber.StartsWith("39") && phoneNumber.Length == 10)
                        {
                            phoneNumber = "39" + phoneNumber;
                        }

                        Process.Start($"https://wa.me/{phoneNumber}");
                    }
                    catch (Exception ex)
                    {
                        MessageBox.Show($"Impossibile aprire WhatsApp: {ex.Message}",
                            "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                    }
                }
                else
                {
                    MessageBox.Show("Numero di telefono non valido.",
                        "Errore", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                }
            }
        }

        private void btnEmail_Click(object sender, EventArgs e)
        {
            if (selectedInsegnante != null && !string.IsNullOrEmpty(selectedInsegnante.Email))
            {
                try
                {
                    Process.Start($"mailto:{selectedInsegnante.Email}");
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"Impossibile aprire il client di posta: {ex.Message}",
                        "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
        }

        private void cmbMese_SelectedIndexChanged(object sender, EventArgs e)
        {
            LoadMonthlyReport();
        }

        private void cmbAnno_SelectedIndexChanged(object sender, EventArgs e)
        {
            LoadMonthlyReport();
        }

        private void txtRicerca_TextChanged(object sender, EventArgs e)
        {
            string searchText = txtRicerca.Text.ToLower();

            if (string.IsNullOrWhiteSpace(searchText))
            {
                bindingSource.DataSource = insegnanti.Items;
            }
            else
            {
                bindingSource.DataSource = insegnanti.Items.Where(i =>
                    (i.Nome != null && i.Nome.ToLower().Contains(searchText)) ||
                    (i.Cognome != null && i.Cognome.ToLower().Contains(searchText)) ||
                    (i.Telefono != null && i.Telefono.ToLower().Contains(searchText)) ||
                    (i.Email != null && i.Email.ToLower().Contains(searchText))
                ).ToList();
            }
        }

        private bool IsValidEmail(string email)
        {
            try
            {
                var addr = new System.Net.Mail.MailAddress(email);
                return addr.Address == email;
            }
            catch
            {
                return false;
            }
        }
    }

    // Le classi di supporto restano le stesse...
    [XmlRoot("Strumenti")]
    public class StrumentiList
    {
        [XmlElement("Strumento")]
        public List<Strumento> Items { get; set; }

        public StrumentiList()
        {
            Items = new List<Strumento>();
        }
    }

    [Serializable]
    public class Strumento
    {
        public int Id { get; set; }
        public string Nome { get; set; }
        public bool LunAttivo { get; set; }
        public bool MarAttivo { get; set; }
        public bool MerAttivo { get; set; }
        public bool GioAttivo { get; set; }
        public bool VenAttivo { get; set; }
        public bool SabAttivo { get; set; }
        public bool DomAttivo { get; set; }
        public int MattInizio { get; set; }
        public int MattFine { get; set; }
        public int PomInizio { get; set; }
        public int PomFine { get; set; }
    }
}