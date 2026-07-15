using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.IO;
using System.Linq;
using System.Windows.Forms;
using System.Xml.Serialization;
using System.Globalization;
using EasyBooking.AcquistiModels;
using EasyBooking.Models;
using System.Diagnostics;
using System.Text;
using System.Threading;

namespace EasyBooking
{
    public partial class AcquistiControl : UserControl
    {
        private string dataPath;
        private List<AcquistiModels.AcquistiPacchetto> pacchettiItems;
        private Models.AcquistiList acquisti;
        private ClientiList clienti;
        private BindingSource bindingSourcePacchetti;
        private BindingSource bindingSourceAcquisti;
        private AcquistiModels.AcquistiPacchetto selectedPacchetto;
        private Models.Acquisto selectedAcquisto;
        private bool isCreatingNewAcquisto = false;

        // Flag per tracciare se i dati sono stati modificati
        private bool dataModified = false;

        // Percorsi dei file XML
        private string pacchettiFilePath;
        private string acquistiFilePath;
        private string clientiFilePath;

        // Flag per evitare loop durante il calcolo dello sconto
        private bool isCalculatingDiscount = false;

        // Lista degli strumenti disponibili
        private List<string> strumentiDisponibili = new List<string>();

        public AcquistiControl(string dataPath)
        {
            InitializeComponent();
            this.dataPath = dataPath;
            pacchettiFilePath = Path.Combine(dataPath, "pacchetti.xml");
            acquistiFilePath = Path.Combine(dataPath, "acquisti.xml");
            clientiFilePath = Path.Combine(dataPath, "clienti.xml");
            LoadData();
            SetupDataGridViews();
            SetupDiscountControls();
        }

        private void SetupDiscountControls()
        {
            // Imposta i valori di default
            cmbTipoSconto.Items.Clear();
            cmbTipoSconto.Items.AddRange(new string[] { "€", "%" });
            cmbTipoSconto.SelectedIndex = 0; // Imposta su €

            // Aggiungi gli event handler per il calcolo automatico
            txtCostoPacchetto.TextChanged += CalcolaImportoPagato;
            txtSconto.TextChanged += CalcolaImportoPagato;
            cmbTipoSconto.SelectedIndexChanged += CalcolaImportoPagato;
        }

        private void CalcolaImportoPagato(object sender, EventArgs e)
        {
            if (isCalculatingDiscount)
                return;

            try
            {
                isCalculatingDiscount = true;

                // Ottieni il costo del pacchetto
                if (!decimal.TryParse(txtCostoPacchetto.Text, out decimal costoPacchetto))
                    costoPacchetto = 0;

                // Ottieni lo sconto
                if (!decimal.TryParse(txtSconto.Text, out decimal sconto))
                    sconto = 0;

                decimal importoFinale = costoPacchetto;

                // Calcola l'importo finale in base al tipo di sconto
                if (cmbTipoSconto.SelectedIndex == 1) // Percentuale
                {
                    // Assicurati che la percentuale sia tra 0 e 100
                    if (sconto > 100) sconto = 100;
                    if (sconto < 0) sconto = 0;

                    decimal scontoValore = (costoPacchetto * sconto) / 100;
                    importoFinale = costoPacchetto - scontoValore;
                }
                else // Valore in €
                {
                    // Lo sconto non può essere maggiore del costo
                    if (sconto > costoPacchetto) sconto = costoPacchetto;
                    if (sconto < 0) sconto = 0;

                    importoFinale = costoPacchetto - sconto; // Corretto: usa sconto invece di scontoValore
                }

                // Imposta l'importo pagato
                txtImportoPagato.Text = importoFinale.ToString("F2");
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore nel calcolo dello sconto: {ex.Message}");
            }
            finally
            {
                isCalculatingDiscount = false;
            }
        }

        protected override void OnVisibleChanged(EventArgs e)
        {
            base.OnVisibleChanged(e);

            if (Visible)
            {
                // Quando il controllo diventa visibile, ricarichiamo completamente i dati
                ForceReload();
            }
        }

        public void ForceReload()
        {
            try
            {
                // Salva eventuali modifiche pendenti
                if (dataModified)
                {
                    MainForm.SaveEncryptedXml(acquisti, acquistiFilePath);
                    dataModified = false;
                }

                // Ricarica tutto da zero
                LoadData();

                // Aggiorna l'interfaccia
                bindingSourceAcquisti.DataSource = GetSortedAcquisti();
                bindingSourceAcquisti.ResetBindings(false);

                // Forza un refresh della DataGridView
                dgvAcquisti.Refresh();

                // Aggiorna il contatore degli acquisti da fatturare
                UpdateAcquistiDaFatturareCount();

                // Debug con verifica dei dati
                VerifyDataLoaded();
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in ForceReload: {ex.Message}");
            }
        }

        private List<Models.Acquisto> GetSortedAcquisti()
        {
            // Ordina gli acquisti per data in modo discendente (più recenti in cima)
            return acquisti.Items.OrderByDescending(a => a.DataAcquisto).ToList();
        }

        private void UpdateAcquistiDaFatturareCount()
        {
            try
            {
                if (acquisti?.Items != null)
                {
                    int acquistiDaFatturare = acquisti.Items.Count(a => string.IsNullOrWhiteSpace(a.NumeroFattura));

                    if (acquistiDaFatturare > 0)
                    {
                        lblAcquistiDaFatturare.Text = $"{acquistiDaFatturare} Acquisti da fatturare";
                        lblAcquistiDaFatturare.Visible = true;
                    }
                    else
                    {
                        lblAcquistiDaFatturare.Visible = false;
                    }
                }
                else
                {
                    lblAcquistiDaFatturare.Visible = false;
                }
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in UpdateAcquistiDaFatturareCount: {ex.Message}");
                lblAcquistiDaFatturare.Visible = false;
            }
        }

        private void VerifyDataLoaded()
        {
            StringBuilder sb = new StringBuilder();
            sb.AppendLine("=== VERIFICA DATI CARICATI ===");

            // Verifica acquisti
            sb.AppendLine($"Acquisti totali: {acquisti?.Items?.Count ?? 0}");
            if (acquisti?.Items?.Count > 0)
            {
                sb.AppendLine("Primi 3 acquisti:");
                foreach (var acq in acquisti.Items.Take(3))
                {
                    sb.AppendLine($"  ID={acq.Id}, Cliente={acq.NomeCliente}, Pacchetto={acq.NomePacchetto}");
                }
            }

            // Verifica binding
            sb.AppendLine($"Righe nella DataGridView: {dgvAcquisti.Rows.Count}");
            if (dgvAcquisti.Rows.Count > 0 && dgvAcquisti.Rows[0].DataBoundItem is Models.Acquisto acq1)
            {
                sb.AppendLine($"Prima riga: ID={acq1.Id}, Cliente={acq1.NomeCliente}, Pacchetto={acq1.NomePacchetto}");
            }

            Debug.WriteLine(sb.ToString());
        }

        private void LoadData()
        {
            try
            {
                // Carica i dati dei pacchetti dal file XML
                var pacchettiList = MainForm.LoadEncryptedXml<AcquistiModels.AcquistiPacchettiList>(pacchettiFilePath);
                pacchettiItems = pacchettiList?.Items ?? new List<AcquistiModels.AcquistiPacchetto>();

                // Carica i dati dei clienti per la selezione
                clienti = MainForm.LoadEncryptedXml<ClientiList>(clientiFilePath);
                if (clienti == null) clienti = new ClientiList() { Items = new List<Cliente>() };
                if (clienti.Items == null) clienti.Items = new List<Cliente>();

                // Carica gli strumenti disponibili dai pacchetti
                strumentiDisponibili = new List<string>();
                strumentiDisponibili.Add(""); // Opzione vuota per "Tutti gli strumenti"

                // Aggiungi tutti gli strumenti unici dalla lista dei pacchetti
                if (pacchettiItems != null)
                {
                    foreach (var pacchetto in pacchettiItems)
                    {
                        if (!string.IsNullOrEmpty(pacchetto.Strumento) &&
                            !strumentiDisponibili.Contains(pacchetto.Strumento))
                        {
                            strumentiDisponibili.Add(pacchetto.Strumento);
                        }
                    }
                }

                // Carica i dati degli acquisti dal file XML - PUNTO CRITICO
                acquisti = MainForm.LoadEncryptedXml<Models.AcquistiList>(acquistiFilePath);

                // Sempre inizializzare se null
                if (acquisti == null)
                {
                    acquisti = new Models.AcquistiList() { Items = new List<Models.Acquisto>() };
                }

                // Sempre inizializzare Items se null
                if (acquisti.Items == null)
                {
                    acquisti.Items = new List<Models.Acquisto>();
                }

                // VERIFICA ESPLICITA DI OGNI ACQUISTO
                bool modificheEffettuate = false;

                foreach (var acq in acquisti.Items)
                {
                    // Se manca il nome del cliente ma abbiamo l'ID, proviamo a recuperarlo
                    if (string.IsNullOrEmpty(acq.NomeCliente) && acq.ClienteId > 0)
                    {
                        // Recupera il nome del cliente
                        var cliente = clienti.Items.FirstOrDefault(c => c.Id == acq.ClienteId);
                        if (cliente != null)
                        {
                            acq.NomeCliente = $"{cliente.Cognome} {cliente.Nome}";
                            Debug.WriteLine($"Recuperato nome cliente: {acq.NomeCliente} per ID={acq.ClienteId}");
                            modificheEffettuate = true;
                        }
                    }

                    // Se manca il nome del pacchetto ma abbiamo l'ID, proviamo a recuperarlo
                    if (string.IsNullOrEmpty(acq.NomePacchetto) && acq.PacchettoId > 0)
                    {
                        // Recupera il nome del pacchetto
                        var pacchetto = pacchettiItems.FirstOrDefault(p => p.Id == acq.PacchettoId);
                        if (pacchetto != null)
                        {
                            acq.NomePacchetto = pacchetto.Nome;
                            Debug.WriteLine($"Recuperato nome pacchetto: {acq.NomePacchetto} per ID={acq.PacchettoId}");
                            modificheEffettuate = true;
                        }
                    }
                }

                // ORDINA GLI ACQUISTI PER DATA (dal più recente al più vecchio)
                if (acquisti.Items.Count > 0)
                {
                    acquisti.Items = acquisti.Items.OrderByDescending(a => a.DataAcquisto).ToList();
                }

                // Se abbiamo ricostruito nomi di clienti o pacchetti, salviamo subito
                if (modificheEffettuate)
                {
                    try
                    {
                        Debug.WriteLine("Salvando acquisti dopo recupero nomi...");
                        MainForm.SaveEncryptedXml(acquisti, acquistiFilePath);
                        Debug.WriteLine("Acquisti salvati con successo.");

                        // Verifica dopo il salvataggio
                        var verificaAcquisti = MainForm.LoadEncryptedXml<Models.AcquistiList>(acquistiFilePath);
                        Debug.WriteLine($"Verifica dopo salvataggio: {verificaAcquisti?.Items?.Count ?? 0} acquisti");
                    }
                    catch (Exception ex)
                    {
                        Debug.WriteLine($"Errore nel salvataggio acquisti: {ex.Message}");
                    }
                }

                dataModified = false;
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante il caricamento dei dati: {ex.Message}\n{ex.StackTrace}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);

                // Inizializza liste vuote in caso di errore
                pacchettiItems = new List<AcquistiModels.AcquistiPacchetto>();
                acquisti = new Models.AcquistiList() { Items = new List<Models.Acquisto>() };
                clienti = new ClientiList() { Items = new List<Cliente>() };
                strumentiDisponibili = new List<string>() { "" };
            }
        }

        // Metodo per forzare il salvataggio dei dati quando necessario
        private void SaveData()
        {
            if (dataModified)
            {
                try
                {
                    Debug.WriteLine("Salvataggio forzato dei dati acquisti");
                    MainForm.SaveEncryptedXml(acquisti, acquistiFilePath);
                    dataModified = false;

                    // Verifica che il file esista dopo il salvataggio
                    if (File.Exists(acquistiFilePath))
                    {
                        FileInfo fi = new FileInfo(acquistiFilePath);
                        Debug.WriteLine($"File salvato: {fi.FullName}, dimensione: {fi.Length} bytes");
                    }
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"Errore durante il salvataggio dei dati: {ex.Message}",
                        "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
        }

        // Override del metodo OnHandleDestroyed per salvare i dati quando il controllo viene distrutto
        protected override void OnHandleDestroyed(EventArgs e)
        {
            SaveData();
            base.OnHandleDestroyed(e);
        }

        // Override del metodo OnParentChanged per salvare i dati quando il controllo viene rimosso
        protected override void OnParentChanged(EventArgs e)
        {
            if (Parent == null && dataModified)
            {
                SaveData();
            }
            base.OnParentChanged(e);
        }

        private void SetupDataGridViews()
        {
            try
            {
                // Configura la griglia dei pacchetti
                bindingSourcePacchetti = new BindingSource();
                bindingSourcePacchetti.DataSource = pacchettiItems;
                dgvPacchetti.DataSource = bindingSourcePacchetti;

                if (dgvPacchetti.Columns.Count > 0)
                {
                    dgvPacchetti.Columns["Id"].Visible = false;

                    dgvPacchetti.Columns["Nome"].HeaderText = "Nome Pacchetto";
                    dgvPacchetti.Columns["Nome"].Width = 150;
                    dgvPacchetti.Columns["Descrizione"].HeaderText = "Descrizione";
                    dgvPacchetti.Columns["Descrizione"].Width = 200;
                    dgvPacchetti.Columns["NumeroLezioni"].HeaderText = "Num. Lezioni";
                    dgvPacchetti.Columns["NumeroLezioni"].Width = 80;
                    dgvPacchetti.Columns["DurataMinuti"].HeaderText = "Durata (min)";
                    dgvPacchetti.Columns["DurataMinuti"].Width = 80;
                    dgvPacchetti.Columns["Frequenza"].HeaderText = "Frequenza";
                    dgvPacchetti.Columns["Frequenza"].Width = 100;
                    dgvPacchetti.Columns["Prezzo"].HeaderText = "Prezzo";
                    dgvPacchetti.Columns["Prezzo"].Width = 80;
                    dgvPacchetti.Columns["Prezzo"].DefaultCellStyle.Format = "c";
                    dgvPacchetti.Columns["Strumento"].HeaderText = "Strumento";
                    dgvPacchetti.Columns["Strumento"].Width = 100;
                }

                // Configura la griglia degli acquisti
                bindingSourceAcquisti = new BindingSource();
                bindingSourceAcquisti.DataSource = acquisti.Items.OrderByDescending(a => a.DataAcquisto).ToList();
                dgvAcquisti.DataSource = bindingSourceAcquisti;

                if (dgvAcquisti.Columns.Count > 0)
                {
                    try
                    {
                        // Impostiamo le colonne ESPLICITAMENTE per essere sicuri
                        dgvAcquisti.Columns["Id"].Visible = false;
                        dgvAcquisti.Columns["ClienteId"].Visible = false;
                        dgvAcquisti.Columns["PacchettoId"].Visible = false;
                        dgvAcquisti.Columns["NumeroFattura"].Visible = false;
                        dgvAcquisti.Columns["Note"].Visible = false;

                        // QUESTE SONO LE COLONNE CRITICHE
                        dgvAcquisti.Columns["NomeCliente"].HeaderText = "Cliente";
                        dgvAcquisti.Columns["NomeCliente"].Width = 180;
                        dgvAcquisti.Columns["NomeCliente"].Visible = true;  // Assicuriamoci che sia visibile

                        dgvAcquisti.Columns["NomePacchetto"].HeaderText = "Pacchetto";
                        dgvAcquisti.Columns["NomePacchetto"].Width = 150;
                        dgvAcquisti.Columns["NomePacchetto"].Visible = true;  // Assicuriamoci che sia visibile

                        dgvAcquisti.Columns["DataAcquisto"].HeaderText = "Data Acquisto";
                        dgvAcquisti.Columns["DataAcquisto"].Width = 90;
                        dgvAcquisti.Columns["DataAcquisto"].DefaultCellStyle.Format = "dd/MM/yyyy";

                        dgvAcquisti.Columns["ImportoPagato"].HeaderText = "Importo";
                        dgvAcquisti.Columns["ImportoPagato"].Width = 80;
                        dgvAcquisti.Columns["ImportoPagato"].DefaultCellStyle.Format = "c";

                        dgvAcquisti.Columns["StatoPagamento"].HeaderText = "Stato";
                        dgvAcquisti.Columns["StatoPagamento"].Width = 100;

                        dgvAcquisti.Columns["Pianificato"].HeaderText = "Pianificato";
                        dgvAcquisti.Columns["Pianificato"].Width = 80;
                    }
                    catch (Exception colEx)
                    {
                        Debug.WriteLine($"Errore configurazione colonne: {colEx.Message}");
                    }
                }

                // Aggiungi l'evento per colorare le righe
                dgvAcquisti.CellFormatting += dgvAcquisti_CellFormatting;

                // Forza refresh esplicito
                dgvPacchetti.Refresh();
                dgvAcquisti.Refresh();

                // Disabilita i form di dettaglio inizialmente
                DisableDetailForms();

                // Aggiorna il contatore
                UpdateAcquistiDaFatturareCount();
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in SetupDataGridViews: {ex.Message}");
            }
        }

        private void dgvAcquisti_CellFormatting(object sender, DataGridViewCellFormattingEventArgs e)
        {
            try
            {
                if (dgvAcquisti.Rows[e.RowIndex].DataBoundItem is Models.Acquisto acquisto)
                {
                    // Se il numero fattura è vuoto, colora la riga di rosso
                    if (string.IsNullOrWhiteSpace(acquisto.NumeroFattura))
                    {
                        dgvAcquisti.Rows[e.RowIndex].DefaultCellStyle.BackColor = Color.LightCoral;
                        dgvAcquisti.Rows[e.RowIndex].DefaultCellStyle.ForeColor = Color.DarkRed;
                    }
                    else
                    {
                        dgvAcquisti.Rows[e.RowIndex].DefaultCellStyle.BackColor = Color.White;
                        dgvAcquisti.Rows[e.RowIndex].DefaultCellStyle.ForeColor = Color.Black;
                    }
                }
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in dgvAcquisti_CellFormatting: {ex.Message}");
            }
        }

        private void DisableDetailForms()
        {
            isCreatingNewAcquisto = false;

            // Disabilita form dettaglio pacchetto
            txtNomePacchetto.Text = string.Empty;
            txtDescrizione.Text = string.Empty;
            nudNumeroLezioni.Value = 1;
            nudDurataMinuti.Value = 60;
            cmbFrequenza.SelectedIndex = -1;
            txtPrezzo.Text = string.Empty;
            cmbStrumento.Items.Clear();
            cmbStrumento.SelectedIndex = -1;
            checkCoppia.Checked = false; // Reset checkbox stato

            txtNomePacchetto.Enabled = false;
            txtDescrizione.Enabled = false;
            nudNumeroLezioni.Enabled = false;
            nudDurataMinuti.Enabled = false;
            cmbFrequenza.Enabled = false;
            txtPrezzo.Enabled = false;
            cmbStrumento.Enabled = false;
            checkCoppia.Enabled = false; // Disabilita checkbox

            btnSalvaPacchetto.Enabled = false;
            btnEliminaPacchetto.Enabled = false;

            // Disabilita form dettaglio acquisto
            dtpDataAcquisto.Value = DateTime.Today;
            cmbClienti.Items.Clear();
            cmbClienti.SelectedIndex = -1;

            // Reset del combobox dello strumento per il filtro
            cmbStrumentoFiltro.Items.Clear();
            cmbStrumentoFiltro.SelectedIndex = -1;

            cmbPacchetti.Items.Clear();
            cmbPacchetti.SelectedIndex = -1;
            txtCostoPacchetto.Text = string.Empty;
            txtSconto.Text = "0";
            cmbTipoSconto.SelectedIndex = 0;
            txtImportoPagato.Text = string.Empty;
            cmbStatoPagamento.SelectedIndex = -1;
            chkPianificato.Checked = false;
            txtNumeroFattura.Text = string.Empty;
            txtNoteAcquisto.Text = string.Empty;

            dtpDataAcquisto.Enabled = false;
            cmbClienti.Enabled = false;
            cmbStrumentoFiltro.Enabled = false;
            cmbPacchetti.Enabled = false;
            txtCostoPacchetto.Enabled = false;
            txtSconto.Enabled = false;
            cmbTipoSconto.Enabled = false;
            txtImportoPagato.Enabled = false;
            cmbStatoPagamento.Enabled = false;
            chkPianificato.Enabled = false;
            txtNumeroFattura.Enabled = false;
            txtNoteAcquisto.Enabled = false;

            btnSalvaAcquisto.Enabled = false;
            btnEliminaAcquisto.Enabled = false;
            btnPianifica.Enabled = false;

            selectedPacchetto = null;
            selectedAcquisto = null;
        }

        private void EnablePacchettoDetailForm()
        {
            txtNomePacchetto.Enabled = true;
            txtDescrizione.Enabled = true;
            nudNumeroLezioni.Enabled = true;
            nudDurataMinuti.Enabled = true;
            cmbFrequenza.Enabled = true;
            txtPrezzo.Enabled = true;
            cmbStrumento.Enabled = true;
            checkCoppia.Enabled = true; // Abilita checkbox

            btnSalvaPacchetto.Enabled = true;
            btnEliminaPacchetto.Enabled = true;

            // Carica gli strumenti nella combobox
            LoadStrumentiComboBox();

            // Imposta i valori predefiniti nella frequenza
            if (cmbFrequenza.Items.Count == 0)
            {
                cmbFrequenza.Items.AddRange(new string[] {
                    "Settimanale",
                    "Bisettimanale",
                    "Mensile",
                    "Personalizzata"
                });
            }
        }

        private void EnableAcquistoDetailForm()
        {
            dtpDataAcquisto.Enabled = true;
            cmbClienti.Enabled = true;
            cmbStrumentoFiltro.Enabled = true;
            cmbPacchetti.Enabled = true;
            txtCostoPacchetto.Enabled = true;
            txtSconto.Enabled = true;
            cmbTipoSconto.Enabled = true;
            txtImportoPagato.Enabled = false; // L'importo pagato è calcolato automaticamente
            cmbStatoPagamento.Enabled = true;
            chkPianificato.Enabled = selectedAcquisto != null && !isCreatingNewAcquisto;
            txtNumeroFattura.Enabled = true;
            txtNoteAcquisto.Enabled = true;

            btnSalvaAcquisto.Enabled = true;
            btnEliminaAcquisto.Enabled = !isCreatingNewAcquisto;
            btnPianifica.Enabled = selectedAcquisto != null && !isCreatingNewAcquisto && !selectedAcquisto.Pianificato;

            // Imposta i valori predefiniti negli stati di pagamento se non già impostati
            if (cmbStatoPagamento.Items.Count == 0)
            {
                cmbStatoPagamento.Items.AddRange(new string[] {
                    "Pagato",
                    "Da pagare",
                    "Pagamento parziale",
                    "Annullato"
                });
            }
        }


        private void LoadStrumentiFiltroCombBox()
        {
            try
            {
                Debug.WriteLine("=== CARICAMENTO STRUMENTI FILTRO COMBOBOX ===");

                // Salva l'elemento selezionato attualmente, se presente
                string elementoAttuale = cmbStrumentoFiltro.Text;

                cmbStrumentoFiltro.Items.Clear();

                // Aggiungi l'opzione per "Tutti gli strumenti" come primo elemento
                cmbStrumentoFiltro.Items.Add("Tutti gli strumenti");

                // Aggiungi tutti gli strumenti disponibili
                HashSet<string> strumentiUnici = new HashSet<string>(StringComparer.OrdinalIgnoreCase);

                // Prima raccogli tutti gli strumenti esistenti nei pacchetti
                if (pacchettiItems != null)
                {
                    foreach (var pacchetto in pacchettiItems)
                    {
                        string strumentoPacchetto = pacchetto.Strumento?.Trim() ?? "";
                        if (!string.IsNullOrEmpty(strumentoPacchetto) && !strumentiUnici.Contains(strumentoPacchetto))
                        {
                            strumentiUnici.Add(strumentoPacchetto);
                            Debug.WriteLine($"Strumento trovato nei pacchetti: '{strumentoPacchetto}'");
                        }
                    }
                }

                // Poi aggiungi i predefiniti da strumentiDisponibili
                foreach (var strumento in strumentiDisponibili)
                {
                    if (!string.IsNullOrEmpty(strumento) && !strumentiUnici.Contains(strumento))
                    {
                        strumentiUnici.Add(strumento);
                    }
                }

                // Aggiungi gli strumenti unici alla dropdown
                foreach (var strumento in strumentiUnici.OrderBy(s => s))
                {
                    cmbStrumentoFiltro.Items.Add(strumento);
                    Debug.WriteLine($"Strumento aggiunto al filtro: '{strumento}'");
                }

                // Cerca di ripristinare la selezione precedente o impostare "Tutti gli strumenti"
                int indice = -1;

                if (!string.IsNullOrEmpty(elementoAttuale))
                {
                    indice = cmbStrumentoFiltro.Items.IndexOf(elementoAttuale);
                }

                cmbStrumentoFiltro.SelectedIndex = indice >= 0 ? indice : 0;

                Debug.WriteLine($"ComboBox strumenti filtro configurata con {cmbStrumentoFiltro.Items.Count} elementi");
                Debug.WriteLine($"Selezione impostata su: '{cmbStrumentoFiltro.Text}'");
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in LoadStrumentiFiltroCombBox: {ex.Message}");
            }
        }

        // Evento di cambio selezione per il filtro strumenti - assicurati che sia collegato nel designer
        private void cmbStrumentoFiltro_SelectedIndexChanged(object sender, EventArgs e)
        {
            try
            {
                // APPROCCIO DIRETTO: ottieni il testo esatto selezionato
                string strumentoSelezionato = cmbStrumentoFiltro.Text;

                // Gestione speciale per "Tutti gli strumenti"
                if (strumentoSelezionato == "Tutti gli strumenti")
                    strumentoSelezionato = "";

                Debug.WriteLine($"### FILTRO ATTIVATO: Strumento='{strumentoSelezionato}' ###");

                // FORZARE completamente il ricaricamento dei pacchetti
                CaricaPacchettiFiltrati(strumentoSelezionato);
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"ERRORE nel filtro strumenti: {ex.Message}");
                MessageBox.Show($"Errore durante il filtraggio dei pacchetti: {ex.Message}", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void CaricaPacchettiFiltrati(string strumentoSelezionato)
        {
            try
            {
                // 1. Svuota completamente la combobox
                cmbPacchetti.DataSource = null;
                cmbPacchetti.Items.Clear();

                // 2. Prepara una nuova lista vuota
                List<AcquistiPacchettoItem> pacchettiFiltrati = new List<AcquistiPacchettoItem>();

                // Aggiungi l'elemento "- Scegli un pacchetto -" come primo elemento
                pacchettiFiltrati.Add(new AcquistiPacchettoItem
                {
                    Id = -1,  // ID speciale per identificare l'elemento placeholder
                    Nome = "- Scegli un pacchetto -",
                    Prezzo = 0
                });

                // 3. Conta elementi per debug
                int contatoreTotale = 0;
                int contatoreCorrispondenti = 0;

                // 4. Processa ogni pacchetto uno per uno
                if (pacchettiItems != null)
                {
                    foreach (var pacchetto in pacchettiItems)
                    {
                        contatoreTotale++;

                        // Determina se questo pacchetto corrisponde al filtro
                        bool deveEssereIncluso = false;

                        if (string.IsNullOrEmpty(strumentoSelezionato))
                        {
                            // Se non c'è filtro, includi tutti
                            deveEssereIncluso = true;
                        }
                        else
                        {
                            // Controllo strumento con gestione null-safe e case insensitive
                            string strumentoPacchetto = pacchetto.Strumento?.Trim() ?? "";
                            deveEssereIncluso = string.Equals(strumentoPacchetto, strumentoSelezionato,
                                                            StringComparison.OrdinalIgnoreCase);

                            Debug.WriteLine($"Confronto: '{strumentoPacchetto}' == '{strumentoSelezionato}' ? {deveEssereIncluso}");
                        }

                        // Se corrisponde, aggiungilo alla lista filtrata
                        if (deveEssereIncluso)
                        {
                            contatoreCorrispondenti++;

                            var pacchettoItem = new AcquistiPacchettoItem
                            {
                                Id = pacchetto.Id,
                                Nome = $"{pacchetto.Nome} ({pacchetto.NumeroLezioni} lez., {pacchetto.DurataMinuti} min) - {pacchetto.Prezzo:c}",
                                Prezzo = pacchetto.Prezzo
                            };

                            pacchettiFiltrati.Add(pacchettoItem);
                            Debug.WriteLine($"AGGIUNTO AL FILTRO: ID={pacchetto.Id}, Nome={pacchetto.Nome}, Strumento={pacchetto.Strumento}");
                        }
                    }
                }

                // 5. Debug sommario dei risultati
                Debug.WriteLine($"RISULTATO FILTRAGGIO: {contatoreCorrispondenti} pacchetti corrispondenti su {contatoreTotale} totali");

                // 6. Imposta il DataSource solo alla fine
                cmbPacchetti.DisplayMember = "Nome";
                cmbPacchetti.ValueMember = "Id";
                cmbPacchetti.DataSource = pacchettiFiltrati;

                // 7. Seleziona l'elemento "- Scegli un pacchetto -"
                cmbPacchetti.SelectedIndex = 0;

                // 8. Forza il refresh dell'interfaccia
                cmbPacchetti.Refresh();

                // 9. Se non ci sono risultati oltre all'elemento placeholder, mostra un messaggio
                if (pacchettiFiltrati.Count == 1 && !string.IsNullOrEmpty(strumentoSelezionato))
                {
                    MessageBox.Show($"Non sono disponibili pacchetti per lo strumento '{strumentoSelezionato}'.",
                                   "Nessun pacchetto trovato", MessageBoxButtons.OK, MessageBoxIcon.Information);
                }
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"ERRORE CRITICO in CaricaPacchettiFiltrati: {ex.Message}");
                MessageBox.Show($"Errore grave durante il filtraggio dei pacchetti: {ex.Message}",
                               "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void LoadPacchettiComboBox(string strumentoFiltro = "")
        {
            try
            {
                Debug.WriteLine("=== CARICAMENTO PACCHETTI COMBOBOX ===");
                Debug.WriteLine($"Filtro strumento: '{strumentoFiltro}'");

                cmbPacchetti.Items.Clear();
                cmbPacchetti.DataSource = null;
                cmbPacchetti.DisplayMember = "";
                cmbPacchetti.ValueMember = "";

                if (pacchettiItems != null)
                {
                    // Filtra i pacchetti in base allo strumento selezionato
                    var pacchettiDaMostrare = pacchettiItems;
                    if (!string.IsNullOrEmpty(strumentoFiltro))
                    {
                        pacchettiDaMostrare = pacchettiItems
                            .Where(p => string.Equals(p.Strumento?.Trim() ?? "", strumentoFiltro, StringComparison.OrdinalIgnoreCase))
                            .ToList();
                        Debug.WriteLine($"Filtrati {pacchettiDaMostrare.Count} pacchetti per strumento '{strumentoFiltro}'");

                        // Debug per vedere quali strumenti sono disponibili
                        var strumentiInPacchetti = pacchettiItems.Select(p => p.Strumento).Distinct().ToList();
                        Debug.WriteLine($"Strumenti presenti nei pacchetti: {string.Join(", ", strumentiInPacchetti)}");
                    }
                    else
                    {
                        Debug.WriteLine($"Mostrati tutti i {pacchettiDaMostrare.Count} pacchetti (nessun filtro)");
                    }

                    List<AcquistiPacchettoItem> listaPacchetti = new List<AcquistiPacchettoItem>();

                    foreach (var pacchetto in pacchettiDaMostrare.OrderBy(p => p.Nome))
                    {
                        var pacchettoItem = new AcquistiPacchettoItem
                        {
                            Id = pacchetto.Id,
                            Nome = $"{pacchetto.Nome} ({pacchetto.NumeroLezioni} lez., {pacchetto.DurataMinuti} min) - {pacchetto.Prezzo:c}",
                            Prezzo = pacchetto.Prezzo
                        };
                        listaPacchetti.Add(pacchettoItem);
                        Debug.WriteLine($"Pacchetto aggiunto: ID={pacchettoItem.Id}, Nome={pacchetto.Nome}, Strumento={pacchetto.Strumento}");
                    }

                    cmbPacchetti.DataSource = listaPacchetti;
                    cmbPacchetti.DisplayMember = "Nome";
                    cmbPacchetti.ValueMember = "Id";
                    cmbPacchetti.SelectedIndex = listaPacchetti.Count > 0 ? -1 : -1;

                    Debug.WriteLine($"ComboBox pacchetti configurata con {listaPacchetti.Count} elementi");
                }
                else
                {
                    Debug.WriteLine("Nessun pacchetto trovato o lista pacchetti null");
                }
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in LoadPacchettiComboBox: {ex.Message}");
            }
        }

        // Aggiungi anche questo metodo di debug se vuoi verificare cosa sta succedendo
        private void DebugShowAllInstruments()
        {
            Debug.WriteLine("=== STRUMENTI DISPONIBILI NEI PACCHETTI ===");
            foreach (var pacchetto in pacchettiItems)
            {
                Debug.WriteLine($"Pacchetto: {pacchetto.Nome}, Strumento: '{pacchetto.Strumento}'");
            }
        }

        private void LoadClientiComboBox()
        {
            try
            {
                Debug.WriteLine("=== CARICAMENTO CLIENTI COMBOBOX ===");

                cmbClienti.Items.Clear();
                cmbClienti.DataSource = null;
                cmbClienti.DisplayMember = "";
                cmbClienti.ValueMember = "";

                if (clienti != null && clienti.Items != null)
                {
                    Debug.WriteLine($"Trovati {clienti.Items.Count} clienti");

                    List<AcquistiClienteItem> listaClienti = new List<AcquistiClienteItem>();

                    foreach (var cliente in clienti.Items.OrderBy(c => c.Cognome).ThenBy(c => c.Nome))
                    {
                        var clienteItem = new AcquistiClienteItem
                        {
                            Id = cliente.Id,
                            Nome = $"{cliente.Cognome} {cliente.Nome}"
                        };
                        listaClienti.Add(clienteItem);
                        Debug.WriteLine($"Cliente aggiunto: ID={clienteItem.Id}, Nome={clienteItem.Nome}");
                    }

                    cmbClienti.DataSource = listaClienti;
                    cmbClienti.DisplayMember = "Nome";
                    cmbClienti.ValueMember = "Id";
                    cmbClienti.SelectedIndex = -1;

                    Debug.WriteLine($"ComboBox clienti configurata con {listaClienti.Count} elementi");
                }
                else
                {
                    Debug.WriteLine("Nessun cliente trovato o lista clienti null");
                }
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in LoadClientiComboBox: {ex.Message}");
            }
        }

        private void LoadPacchettoDetails(AcquistiModels.AcquistiPacchetto pacchetto)
        {
            selectedPacchetto = pacchetto;

            // Verifica se c'è la proprietà IsLezioneDiCoppia o se usa il tag nella descrizione
            bool isLezioneDiCoppia = false;

            // APPROCCIO 1: Usa reflection per verificare se esiste la proprietà
            var property = pacchetto.GetType().GetProperty("IsLezioneDiCoppia");
            if (property != null)
            {
                // La proprietà esiste, possiamo usarla direttamente
                isLezioneDiCoppia = (bool)property.GetValue(pacchetto, null);
            }
            else
            {
                // APPROCCIO 2: Fallback - Controlla nella descrizione
                string descrizione = pacchetto.Descrizione ?? string.Empty;
                isLezioneDiCoppia = descrizione.Contains("[LEZIONE_COPPIA]");
            }

            txtNomePacchetto.Text = pacchetto.Nome ?? string.Empty;

            // Rimuoviamo il tag dalla descrizione se presente
            string descrizioneVisualizzata = pacchetto.Descrizione ?? string.Empty;
            if (descrizioneVisualizzata.Contains("[LEZIONE_COPPIA]"))
            {
                descrizioneVisualizzata = descrizioneVisualizzata.Replace("[LEZIONE_COPPIA]", "").Trim();
            }

            txtDescrizione.Text = descrizioneVisualizzata;
            nudNumeroLezioni.Value = pacchetto.NumeroLezioni;
            nudDurataMinuti.Value = pacchetto.DurataMinuti;

            // Imposta la frequenza
            int frequenzaIndex = -1;
            switch (pacchetto.Frequenza)
            {
                case "Settimanale": frequenzaIndex = 0; break;
                case "Bisettimanale": frequenzaIndex = 1; break;
                case "Mensile": frequenzaIndex = 2; break;
                case "Personalizzata": frequenzaIndex = 3; break;
            }
            cmbFrequenza.SelectedIndex = frequenzaIndex;

            txtPrezzo.Text = pacchetto.Prezzo.ToString("F2");

            // CORREZIONE: Carica completamente gli strumenti e poi seleziona quello del pacchetto
            cmbStrumento.Items.Clear();
            LoadStrumentiComboBox(); // Carica la lista degli strumenti disponibili

            // DEBUG: Stampiamo lo strumento del pacchetto e tutti gli strumenti disponibili nella ComboBox
            string strumentoPacchetto = pacchetto.Strumento ?? string.Empty;
            Debug.WriteLine($"Strumento del pacchetto: '{strumentoPacchetto}'");
            Debug.WriteLine("Strumenti disponibili nella ComboBox:");
            for (int i = 0; i < cmbStrumento.Items.Count; i++)
            {
                Debug.WriteLine($"  {i}: '{cmbStrumento.Items[i]}'");
            }

            // Se lo strumento del pacchetto non è vuoto ma non è nella lista, lo aggiungiamo
            bool strumentoTrovato = false;
            if (!string.IsNullOrEmpty(strumentoPacchetto))
            {
                for (int i = 0; i < cmbStrumento.Items.Count; i++)
                {
                    if (string.Equals(cmbStrumento.Items[i].ToString(), strumentoPacchetto, StringComparison.OrdinalIgnoreCase))
                    {
                        cmbStrumento.SelectedIndex = i;
                        strumentoTrovato = true;
                        Debug.WriteLine($"Strumento trovato nella ComboBox all'indice {i}");
                        break;
                    }
                }

                if (!strumentoTrovato)
                {
                    Debug.WriteLine($"Strumento '{strumentoPacchetto}' non trovato, lo aggiungiamo alla lista");
                    cmbStrumento.Items.Add(strumentoPacchetto);
                    cmbStrumento.SelectedIndex = cmbStrumento.Items.Count - 1;
                }
            }
            else
            {
                cmbStrumento.SelectedIndex = -1; // Nessuno strumento selezionato
                Debug.WriteLine("Nessuno strumento da selezionare (strumento vuoto)");
            }

            // Imposta lo stato della checkbox per lezioni di coppia
            checkCoppia.Checked = isLezioneDiCoppia;

            EnablePacchettoDetailForm();

            // Stampa debug finale
            Debug.WriteLine($"Selezione finale strumento: Indice={cmbStrumento.SelectedIndex}, Testo='{cmbStrumento.Text}'");
        }

        private void LoadStrumentiComboBox()
        {
            // Salva temporaneamente lo strumento selezionato, se presente
            string strumentoSelezionato = cmbStrumento.SelectedItem?.ToString();

            // Pulisci la lista
            cmbStrumento.Items.Clear();

            // Carica tutti gli strumenti disponibili
            var strumentiFilePath = Path.Combine(dataPath, "strumenti.xml");
            var strumentiList = MainForm.LoadEncryptedXml<StrumentiList>(strumentiFilePath);

            if (strumentiList != null && strumentiList.Items != null)
            {
                foreach (var strumento in strumentiList.Items)
                {
                    if (!string.IsNullOrEmpty(strumento.Nome))
                    {
                        cmbStrumento.Items.Add(strumento.Nome);
                        Debug.WriteLine($"Strumento aggiunto alla ComboBox: '{strumento.Nome}'");
                    }
                }
            }

            // Se c'era uno strumento selezionato, cerca di ripristinarlo
            if (!string.IsNullOrEmpty(strumentoSelezionato))
            {
                for (int i = 0; i < cmbStrumento.Items.Count; i++)
                {
                    if (string.Equals(cmbStrumento.Items[i].ToString(), strumentoSelezionato, StringComparison.OrdinalIgnoreCase))
                    {
                        cmbStrumento.SelectedIndex = i;
                        Debug.WriteLine($"Ripristinato strumento selezionato: '{strumentoSelezionato}'");
                        return;
                    }
                }
            }
        }


        private void LoadAcquistoDetails(Models.Acquisto acquisto, bool isNew = false)
        {
            try
            {
                Debug.WriteLine("=== CARICAMENTO DETTAGLI ACQUISTO ===");
                Debug.WriteLine($"Acquisto: ID={acquisto.Id}, ClienteId={acquisto.ClienteId}, PacchettoId={acquisto.PacchettoId}");
                Debug.WriteLine($"NomeCliente='{acquisto.NomeCliente}', NomePacchetto='{acquisto.NomePacchetto}'");

                selectedAcquisto = isNew ? null : acquisto;
                isCreatingNewAcquisto = isNew;

                // Prima di tutto, carica le ComboBox
                LoadClientiComboBox();
                LoadStrumentiFiltroCombBox();
                LoadPacchettiComboBox(); // Carica tutti i pacchetti inizialmente

                // Imposta la data
                dtpDataAcquisto.Value = acquisto.DataAcquisto;

                // SELEZIONE CLIENTE
                bool clienteSelezionato = false;
                if (acquisto.ClienteId > 0)
                {
                    Debug.WriteLine($"Tentativo selezione cliente per ID: {acquisto.ClienteId}");

                    // Cerca per ID usando il ValueMember
                    for (int i = 0; i < cmbClienti.Items.Count; i++)
                    {
                        if (cmbClienti.Items[i] is AcquistiClienteItem clienteItem && clienteItem.Id == acquisto.ClienteId)
                        {
                            cmbClienti.SelectedIndex = i;
                            clienteSelezionato = true;
                            Debug.WriteLine($"Cliente selezionato per ID: {clienteItem.Nome} (indice {i})");
                            break;
                        }
                    }
                }

                // Se non trovato per ID, cerca per nome
                if (!clienteSelezionato && !string.IsNullOrEmpty(acquisto.NomeCliente))
                {
                    Debug.WriteLine($"Tentativo selezione cliente per nome: '{acquisto.NomeCliente}'");

                    for (int i = 0; i < cmbClienti.Items.Count; i++)
                    {
                        if (cmbClienti.Items[i] is AcquistiClienteItem clienteItem &&
                            string.Equals(clienteItem.Nome.Trim(), acquisto.NomeCliente.Trim(), StringComparison.OrdinalIgnoreCase))
                        {
                            cmbClienti.SelectedIndex = i;
                            clienteSelezionato = true;
                            Debug.WriteLine($"Cliente selezionato per nome: {clienteItem.Nome} (indice {i})");
                            break;
                        }
                    }
                }

                if (!clienteSelezionato)
                {
                    Debug.WriteLine("ATTENZIONE: Cliente non selezionato!");
                    cmbClienti.SelectedIndex = -1;
                }

                // Se non è un nuovo acquisto e conosciamo il PacchettoId, troviamo lo strumento associato
                string strumentoAssociato = "";
                if (!isNew && acquisto.PacchettoId > 0)
                {
                    var pacchetto = pacchettiItems.FirstOrDefault(p => p.Id == acquisto.PacchettoId);
                    if (pacchetto != null && !string.IsNullOrEmpty(pacchetto.Strumento))
                    {
                        strumentoAssociato = pacchetto.Strumento;

                        // Selezioniamo lo strumento nel filtro
                        for (int i = 0; i < cmbStrumentoFiltro.Items.Count; i++)
                        {
                            if (cmbStrumentoFiltro.Items[i].ToString() == strumentoAssociato)
                            {
                                cmbStrumentoFiltro.SelectedIndex = i;
                                Debug.WriteLine($"Strumento filtro selezionato: {strumentoAssociato}");

                                // Ricarica i pacchetti filtrati per questo strumento
                                LoadPacchettiComboBox(strumentoAssociato);
                                break;
                            }
                        }
                    }
                }

                // SELEZIONE PACCHETTO
                bool pacchettoSelezionato = false;
                if (acquisto.PacchettoId > 0)
                {
                    Debug.WriteLine($"Tentativo selezione pacchetto per ID: {acquisto.PacchettoId}");

                    // Cerca per ID usando il ValueMember
                    for (int i = 0; i < cmbPacchetti.Items.Count; i++)
                    {
                        if (cmbPacchetti.Items[i] is AcquistiPacchettoItem pacchettoItem && pacchettoItem.Id == acquisto.PacchettoId)
                        {
                            cmbPacchetti.SelectedIndex = i;
                            pacchettoSelezionato = true;
                            Debug.WriteLine($"Pacchetto selezionato per ID: indice {i}");
                            break;
                        }
                    }
                }

                // Se non trovato per ID, cerca per nome del pacchetto originale
                if (!pacchettoSelezionato && !string.IsNullOrEmpty(acquisto.NomePacchetto))
                {
                    Debug.WriteLine($"Tentativo selezione pacchetto per nome: '{acquisto.NomePacchetto}'");

                    // Trova il pacchetto originale per nome
                    var pacchettoOriginale = pacchettiItems.FirstOrDefault(p =>
                        string.Equals(p.Nome.Trim(), acquisto.NomePacchetto.Trim(), StringComparison.OrdinalIgnoreCase));

                    if (pacchettoOriginale != null)
                    {
                        for (int i = 0; i < cmbPacchetti.Items.Count; i++)
                        {
                            if (cmbPacchetti.Items[i] is AcquistiPacchettoItem pacchettoItem && pacchettoItem.Id == pacchettoOriginale.Id)
                            {
                                cmbPacchetti.SelectedIndex = i;
                                pacchettoSelezionato = true;
                                Debug.WriteLine($"Pacchetto selezionato per nome: {pacchettoOriginale.Nome} (indice {i})");
                                break;
                            }
                        }
                    }
                }

                if (!pacchettoSelezionato)
                {
                    Debug.WriteLine("ATTENZIONE: Pacchetto non selezionato!");
                    cmbPacchetti.SelectedIndex = -1;
                }

                // Imposta i campi dello sconto
                if (!isNew && acquisto.PacchettoId > 0)
                {
                    var pacchetto = pacchettiItems.FirstOrDefault(p => p.Id == acquisto.PacchettoId);
                    if (pacchetto != null)
                    {
                        txtCostoPacchetto.Text = pacchetto.Prezzo.ToString("F2");

                        // Calcola lo sconto dal prezzo originale
                        decimal sconto = pacchetto.Prezzo - acquisto.ImportoPagato;
                        if (sconto > 0)
                        {
                            txtSconto.Text = sconto.ToString("F2");
                            cmbTipoSconto.SelectedIndex = 0; // Euro
                        }
                        else
                        {
                            txtSconto.Text = "0";
                        }
                    }
                }
                else if (isNew)
                {
                    txtCostoPacchetto.Text = "0";
                    txtSconto.Text = "0";
                    cmbTipoSconto.SelectedIndex = 0; // Euro
                }

                // Imposta l'importo pagato
                txtImportoPagato.Text = acquisto.ImportoPagato.ToString("F2");

                // Imposta lo stato di pagamento
                int statoPagamentoIndex = -1;
                switch (acquisto.StatoPagamento)
                {
                    case "Pagato": statoPagamentoIndex = 0; break;
                    case "Da pagare": statoPagamentoIndex = 1; break;
                    case "Pagamento parziale": statoPagamentoIndex = 2; break;
                    case "Annullato": statoPagamentoIndex = 3; break;
                }
                cmbStatoPagamento.SelectedIndex = statoPagamentoIndex;

                chkPianificato.Checked = acquisto.Pianificato;
                txtNumeroFattura.Text = acquisto.NumeroFattura ?? string.Empty;
                txtNoteAcquisto.Text = acquisto.Note ?? string.Empty;

                // Abilita i controlli
                EnableAcquistoDetailForm();

                // Abilita/disabilita il pulsante di pianificazione in base allo stato
                btnPianifica.Enabled = !isNew && !acquisto.Pianificato;

                // Messaggio di avviso se mancano dati
                if (!isNew && (!clienteSelezionato || !pacchettoSelezionato))
                {
                    string messaggio = "Attenzione: ";
                    if (!clienteSelezionato)
                        messaggio += $"Cliente '{acquisto.NomeCliente}' (ID={acquisto.ClienteId}) non trovato. ";
                    if (!pacchettoSelezionato)
                        messaggio += $"Pacchetto '{acquisto.NomePacchetto}' (ID={acquisto.PacchettoId}) non trovato.";

                    MessageBox.Show($"{messaggio}\nVerifica che cliente e pacchetto esistano ancora nel sistema.",
                        "Avviso", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                }

                Debug.WriteLine($"Caricamento completato. Cliente selezionato: {clienteSelezionato}, Pacchetto selezionato: {pacchettoSelezionato}");
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in LoadAcquistoDetails: {ex.Message}");
                MessageBox.Show($"Errore nel caricamento dei dettagli acquisto: {ex.Message}", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void dgvPacchetti_CellClick(object sender, DataGridViewCellEventArgs e)
        {
            if (e.RowIndex >= 0 && e.RowIndex < pacchettiItems.Count)
            {
                var pacchetto = pacchettiItems[e.RowIndex];
                LoadPacchettoDetails(pacchetto);
            }
        }

        private void dgvAcquisti_CellClick(object sender, DataGridViewCellEventArgs e)
        {
            if (e.RowIndex >= 0 && e.RowIndex < acquisti.Items.Count)
            {
                var acquisto = acquisti.Items[e.RowIndex];
                LoadAcquistoDetails(acquisto, false);
            }
        }

        private void btnNuovoPacchetto_Click(object sender, EventArgs e)
        {
            // Crea un nuovo pacchetto e assegna un ID univoco
            var nuovoPacchetto = new AcquistiModels.AcquistiPacchetto
            {
                Id = pacchettiItems.Count > 0 ? pacchettiItems.Max(p => p.Id) + 1 : 1,
                NumeroLezioni = 1,
                DurataMinuti = 60,
                Prezzo = 0
            };

            // Prova a impostare la proprietà IsLezioneDiCoppia se esiste
            var property = nuovoPacchetto.GetType().GetProperty("IsLezioneDiCoppia");
            if (property != null)
            {
                property.SetValue(nuovoPacchetto, false);
            }

            // Aggiungi alla lista
            pacchettiItems.Add(nuovoPacchetto);

            // Aggiorna la griglia
            bindingSourcePacchetti.ResetBindings(false);

            // Seleziona il nuovo pacchetto
            dgvPacchetti.ClearSelection();
            int lastIndex = dgvPacchetti.Rows.Count - 1;
            if (lastIndex >= 0)
            {
                dgvPacchetti.Rows[lastIndex].Selected = true;
                LoadPacchettoDetails(nuovoPacchetto);
                txtNomePacchetto.Focus();
            }
        }

        private void btnNuovoAcquisto_Click(object sender, EventArgs e)
        {
            // Crea un nuovo acquisto solo temporaneamente, senza aggiungerlo alla lista
            var nuovoAcquisto = new Models.Acquisto
            {
                Id = acquisti.Items.Count > 0 ? acquisti.Items.Max(a => a.Id) + 1 : 1,
                DataAcquisto = DateTime.Today,
                ImportoPagato = 0,
                Pianificato = false,
                NumeroLezioni = 0,
                StatoPagamento = "Pagato", // Stato predefinito
                NomeCliente = "", // Inizializziamo stringhe vuote per evitare null
                NomePacchetto = ""
            };

            // Carica i controlli per l'acquisto, ma indica che è nuovo
            LoadAcquistoDetails(nuovoAcquisto, true);
            cmbClienti.Focus();

            // Qui non aggiungiamo ancora l'acquisto alla lista - lo faremo solo al salvataggio
        }

        private void btnSalvaPacchetto_Click(object sender, EventArgs e)
        {
            if (selectedPacchetto == null)
                return;

            // Verifica campi obbligatori
            if (string.IsNullOrWhiteSpace(txtNomePacchetto.Text))
            {
                MessageBox.Show("Il nome del pacchetto è obbligatorio.",
                    "Validazione", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            // Verifica che il prezzo sia un numero valido
            if (!decimal.TryParse(txtPrezzo.Text, out decimal prezzo) || prezzo < 0)
            {
                MessageBox.Show("Prezzo non valido. Inserisci un numero maggiore o uguale a zero.",
                    "Validazione", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            // Ottieni il vecchio strumento per verificare se è cambiato
            string vecchioStrumento = selectedPacchetto.Strumento;

            // Preparazione del campo descrizione, in caso sia necessario usare l'approccio con tag
            string descrizione = txtDescrizione.Text.Trim();

            // Rimuovi eventuali tag precedenti
            if (descrizione.Contains("[LEZIONE_COPPIA]"))
            {
                descrizione = descrizione.Replace("[LEZIONE_COPPIA]", "").Trim();
            }

            // Aggiorna l'oggetto pacchetto
            selectedPacchetto.Nome = txtNomePacchetto.Text.Trim();
            selectedPacchetto.NumeroLezioni = (int)nudNumeroLezioni.Value;
            selectedPacchetto.DurataMinuti = (int)nudDurataMinuti.Value;
            selectedPacchetto.Frequenza = cmbFrequenza.SelectedItem?.ToString() ?? "Settimanale";
            selectedPacchetto.Prezzo = prezzo;
            selectedPacchetto.Strumento = cmbStrumento.SelectedItem?.ToString() ?? string.Empty;

            // APPROCCIO 1: Usa reflection per verificare se esiste la proprietà
            var property = selectedPacchetto.GetType().GetProperty("IsLezioneDiCoppia");
            if (property != null)
            {
                // La proprietà esiste, impostiamola direttamente
                property.SetValue(selectedPacchetto, checkCoppia.Checked);
                selectedPacchetto.Descrizione = descrizione; // Descrizione senza tag
            }
            else
            {
                // APPROCCIO 2: Fallback - usa il tag nella descrizione
                selectedPacchetto.Descrizione = checkCoppia.Checked ?
                    descrizione + " [LEZIONE_COPPIA]" :
                    descrizione;
            }

            // Se lo strumento è cambiato, aggiorniamo la lista degli strumenti disponibili
            if (selectedPacchetto.Strumento != vecchioStrumento)
            {
                if (!string.IsNullOrEmpty(selectedPacchetto.Strumento) &&
                    !strumentiDisponibili.Contains(selectedPacchetto.Strumento))
                {
                    strumentiDisponibili.Add(selectedPacchetto.Strumento);
                }
            }

            // Crea un nuovo oggetto AcquistiPacchettiList per salvaggio
            var pacchettiToSave = new AcquistiModels.AcquistiPacchettiList
            {
                Items = pacchettiItems
            };

            // Salva nel file
            MainForm.SaveEncryptedXml(pacchettiToSave, pacchettiFilePath);

            // Aggiorna la griglia
            bindingSourcePacchetti.ResetBindings(false);

            // Aggiorna la combobox del filtro strumenti se necessario
            if (selectedPacchetto.Strumento != vecchioStrumento)
            {
                LoadStrumentiFiltroCombBox();
            }

            // Aggiorna la combobox dei pacchetti nel form acquisti
            string strumentoFiltroSelezionato = cmbStrumentoFiltro.SelectedItem?.ToString() ?? "";
            LoadPacchettiComboBox(strumentoFiltroSelezionato);

            // Aggiorna anche nome pacchetto negli acquisti esistenti
            bool modifichePacchetti = false;
            foreach (var acq in acquisti.Items)
            {
                if (acq.PacchettoId == selectedPacchetto.Id)
                {
                    acq.NomePacchetto = selectedPacchetto.Nome;
                    modifichePacchetti = true;
                }
            }

            if (modifichePacchetti)
            {
                MainForm.SaveEncryptedXml(acquisti, acquistiFilePath);
                bindingSourceAcquisti.ResetBindings(false);
            }
        }

        private void btnSalvaAcquisto_Click(object sender, EventArgs e)
        {
            try
            {
                // 1. VERIFICA SELEZIONI
                if (cmbClienti.SelectedItem == null)
                {
                    MessageBox.Show("Seleziona un cliente.", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    return;
                }
                if (cmbPacchetti.SelectedItem == null)
                {
                    MessageBox.Show("Seleziona un pacchetto.", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    return;
                }

                // 2. OTTIENI I VALORI ESPLICITI
                AcquistiClienteItem clienteItem = cmbClienti.SelectedItem as AcquistiClienteItem;
                AcquistiPacchettoItem pacchettoItem = cmbPacchetti.SelectedItem as AcquistiPacchettoItem;

                if (clienteItem == null || pacchettoItem == null)
                {
                    MessageBox.Show("Errore nella selezione di cliente o pacchetto.", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                    return;
                }

                // 3. VALORI ESPLICITI - NIENTE FUNZIONI O OGGETTI INTERMEDI
                int clienteId = clienteItem.Id;
                string nomeCliente = clienteItem.Nome;  // QUESTO È IL NOME CLIENTE

                int pacchettoId = pacchettoItem.Id;
                string nomePacchetto = "";  // Lo cerchiamo direttamente
                int numeroLezioni = 0;

                // 4. CERCA IL PACCHETTO E OTTIENI IL NOME
                foreach (var p in pacchettiItems)
                {
                    if (p.Id == pacchettoId)
                    {
                        nomePacchetto = p.Nome;  // QUESTO È IL NOME PACCHETTO
                        numeroLezioni = p.NumeroLezioni;
                        break;
                    }
                }

                // 5. VERIFICA IMPORTO (ora viene calcolato automaticamente)
                decimal importoPagato = 0;
                if (!decimal.TryParse(txtImportoPagato.Text, out importoPagato))
                {
                    MessageBox.Show("Importo non valido.", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    return;
                }

                // 6. STATO PAGAMENTO
                string statoPagamento = cmbStatoPagamento.SelectedItem?.ToString() ?? "Pagato";

                // 7. CREA O AGGIORNA L'ACQUISTO CON VALORI ESPLICITI
                if (isCreatingNewAcquisto)
                {
                    // Nuovo acquisto
                    Models.Acquisto nuovoAcquisto = new Models.Acquisto
                    {
                        Id = acquisti.Items.Count > 0 ? acquisti.Items.Max(a => a.Id) + 1 : 1,
                        DataAcquisto = dtpDataAcquisto.Value,
                        ClienteId = clienteId,
                        NomeCliente = nomeCliente,  // NOME CLIENTE ESPLICITO
                        PacchettoId = pacchettoId,
                        NomePacchetto = nomePacchetto,  // NOME PACCHETTO ESPLICITO
                        ImportoPagato = importoPagato,
                        StatoPagamento = statoPagamento,
                        Pianificato = false,
                        NumeroFattura = txtNumeroFattura.Text.Trim(),
                        Note = txtNoteAcquisto.Text.Trim(),
                        NumeroLezioni = numeroLezioni
                    };

                    // Aggiungi alla lista
                    acquisti.Items.Add(nuovoAcquisto);
                    selectedAcquisto = nuovoAcquisto;
                    isCreatingNewAcquisto = false;

                    // Debug - Stampa informazioni
                    Debug.WriteLine($"Creato nuovo acquisto: ID={nuovoAcquisto.Id}, Cliente={nomeCliente}, Pacchetto={nomePacchetto}");
                }
                else if (selectedAcquisto != null)
                {
                    // Aggiorna acquisto esistente - VALORI ESPLICITI
                    selectedAcquisto.DataAcquisto = dtpDataAcquisto.Value;
                    selectedAcquisto.ClienteId = clienteId;
                    selectedAcquisto.NomeCliente = nomeCliente;  // NOME CLIENTE ESPLICITO
                    selectedAcquisto.PacchettoId = pacchettoId;
                    selectedAcquisto.NomePacchetto = nomePacchetto;  // NOME PACCHETTO ESPLICITO
                    selectedAcquisto.ImportoPagato = importoPagato;
                    selectedAcquisto.StatoPagamento = statoPagamento;
                    selectedAcquisto.Pianificato = chkPianificato.Checked;
                    selectedAcquisto.NumeroFattura = txtNumeroFattura.Text.Trim();
                    selectedAcquisto.Note = txtNoteAcquisto.Text.Trim();
                    selectedAcquisto.NumeroLezioni = numeroLezioni;

                    // Debug - Stampa informazioni
                    Debug.WriteLine($"Aggiornato acquisto: ID={selectedAcquisto.Id}, Cliente={nomeCliente}, Pacchetto={nomePacchetto}");
                }
                else
                {
                    MessageBox.Show("Nessun acquisto selezionato.", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                    return;
                }

                // 8. SALVA IMMEDIATAMENTE
                MainForm.SaveEncryptedXml(acquisti, acquistiFilePath);
                dataModified = false;

                // 9. RICARICA PER VERIFICARE
                var verificaAcquisti = MainForm.LoadEncryptedXml<Models.AcquistiList>(acquistiFilePath);

                // Stampa debug per verifica
                if (verificaAcquisti?.Items != null)
                {
                    var acquistoDaVerificare = verificaAcquisti.Items.FirstOrDefault(a =>
                        a.Id == selectedAcquisto.Id);

                    if (acquistoDaVerificare != null)
                    {
                        Debug.WriteLine($"Verifica salvataggio: ID={acquistoDaVerificare.Id}, " +
                                       $"Cliente={acquistoDaVerificare.NomeCliente}, Pacchetto={acquistoDaVerificare.NomePacchetto}");
                    }
                }

                // 10. AGGIORNA UI
                bindingSourceAcquisti.ResetBindings(false);

                // 11. AGGIORNA CONTATORE ACQUISTI DA FATTURARE
                UpdateAcquistiDaFatturareCount();

                // 12. RIMOSTRA I DETTAGLI PER VERIFICA
                LoadAcquistoDetails(selectedAcquisto, false);


            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore: {ex.Message}", "Errore salvataggio", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void btnEliminaPacchetto_Click(object sender, EventArgs e)
        {
            if (selectedPacchetto == null)
                return;

            // Chiede conferma
            var result = MessageBox.Show(
                $"Sei sicuro di voler eliminare il pacchetto '{selectedPacchetto.Nome}'?",
                "Conferma eliminazione",
                MessageBoxButtons.YesNo,
                MessageBoxIcon.Question);

            if (result == DialogResult.Yes)
            {
                // Verifica se ci sono acquisti associati
                if (acquisti != null && acquisti.Items != null &&
                    acquisti.Items.Any(a => a.PacchettoId == selectedPacchetto.Id))
                {
                    // Chiede conferma per eliminare un pacchetto con acquisti
                    var confirmResult = MessageBox.Show(
                        "Questo pacchetto ha degli acquisti associati. L'eliminazione potrebbe causare errori nel sistema. Continuare?",
                        "Attenzione",
                        MessageBoxButtons.YesNo,
                        MessageBoxIcon.Warning);

                    if (confirmResult != DialogResult.Yes)
                    {
                        return; // Annulla l'eliminazione
                    }
                }

                // Rimuove il pacchetto
                pacchettiItems.Remove(selectedPacchetto);

                // Crea un nuovo oggetto AcquistiPacchettiList per salvaggio
                var pacchettiToSave = new AcquistiModels.AcquistiPacchettiList
                {
                    Items = pacchettiItems
                };

                // Salva nel file
                MainForm.SaveEncryptedXml(pacchettiToSave, pacchettiFilePath);

                // Aggiorna la griglia
                bindingSourcePacchetti.ResetBindings(false);

                // Ricostruisci la lista degli strumenti disponibili
                strumentiDisponibili = new List<string>();
                strumentiDisponibili.Add(""); // Opzione vuota per "Tutti gli strumenti"
                foreach (var pacchetto in pacchettiItems)
                {
                    if (!string.IsNullOrEmpty(pacchetto.Strumento) &&
                        !strumentiDisponibili.Contains(pacchetto.Strumento))
                    {
                        strumentiDisponibili.Add(pacchetto.Strumento);
                    }
                }

                // Aggiorna la combobox dei pacchetti e filtro strumenti nel form acquisti
                LoadStrumentiFiltroCombBox();
                LoadPacchettiComboBox();

                // Disabilita il form di dettaglio
                DisableDetailForms();

                MessageBox.Show("Pacchetto eliminato con successo.",
                    "Eliminazione", MessageBoxButtons.OK, MessageBoxIcon.Information);
            }
        }

        private void btnEliminaAcquisto_Click(object sender, EventArgs e)
        {
            if (selectedAcquisto == null)
                return;

            // Chiede conferma
            var result = MessageBox.Show(
                $"Sei sicuro di voler eliminare questo acquisto?",
                "Conferma eliminazione",
                MessageBoxButtons.YesNo,
                MessageBoxIcon.Question);

            if (result == DialogResult.Yes)
            {
                // Verifica se l'acquisto è stato pianificato
                if (selectedAcquisto.Pianificato)
                {
                    // Chiede conferma per eliminare un acquisto pianificato
                    var confirmResult = MessageBox.Show(
                        "Questo acquisto è già stato pianificato. L'eliminazione non rimuoverà le lezioni già programmate. Continuare?",
                        "Attenzione",
                        MessageBoxButtons.YesNo,
                        MessageBoxIcon.Warning);

                    if (confirmResult != DialogResult.Yes)
                    {
                        return; // Annulla l'eliminazione
                    }
                }

                // Rimuove l'acquisto
                acquisti.Items.Remove(selectedAcquisto);

                // Salva immediatamente le modifiche
                MainForm.SaveEncryptedXml(acquisti, acquistiFilePath);
                dataModified = false;

                // Aggiorna la griglia
                bindingSourceAcquisti.ResetBindings(false);

                // Aggiorna contatore acquisti da fatturare
                UpdateAcquistiDaFatturareCount();

                // Disabilita il form di dettaglio
                DisableDetailForms();

                MessageBox.Show("Acquisto eliminato con successo.",
                    "Eliminazione", MessageBoxButtons.OK, MessageBoxIcon.Information);
            }
        }

        private void UpdateAcquistoControls(Models.Acquisto acquisto)
        {
            // Aggiorna lo stato del controllo dell'acquisto selezionato
            if (acquisto == null)
            {
                btnPianifica.Enabled = false;
                chkPianificato.Checked = false;
            }
            else
            {
                btnPianifica.Enabled = !acquisto.Pianificato;
                chkPianificato.Checked = acquisto.Pianificato;
            }
        }

        private void btnPianifica_Click(object sender, EventArgs e)
        {
            // Flag per tracciare se stiamo già gestendo un'eccezione
            bool handlingException = false;

            try
            {
                if (selectedAcquisto == null || selectedAcquisto.Pianificato)
                {
                    MessageBox.Show("Seleziona un acquisto non pianificato.",
                        "Pianificazione", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    return;
                }

                // Verifica che il file prenotazioni esista
                string prenotazioniFilePath = Path.Combine(dataPath, "prenotazioni.xml");
                EnsurePrenotazioniFileExists(prenotazioniFilePath);

                // Sopprime eccezioni non gestite durante l'apertura del form
                AppDomain.CurrentDomain.UnhandledException += (s, ex) =>
                {
                    if (!handlingException && ex.ExceptionObject is Exception exception &&
                        (exception.ToString().Contains("XML") || exception.ToString().Contains("xml")))
                    {
                        handlingException = true;
                        Debug.WriteLine($"Errore XML soppresso: {exception.Message}");
                    }
                };

                // CREA IL FORM CON INVOCAZIONE DIFFERITA PER ISOLARE ERRORI
                PianificazioneForm pianificazioneForm = null;

                try
                {
                    // Crea il form in un thread separato per isolare gli errori
                    Action createFormAction = () =>
                    {
                        try
                        {
                            pianificazioneForm = new PianificazioneForm(dataPath, selectedAcquisto);
                        }
                        catch (Exception ex)
                        {
                            Debug.WriteLine($"Errore nella creazione del form: {ex.Message}");
                        }
                    };

                    // Esegui in un thread separato
                    var thread = new Thread(new ThreadStart(createFormAction));
                    thread.SetApartmentState(ApartmentState.STA);
                    thread.Start();
                    thread.Join(5000); // Attendi max 5 secondi
                }
                catch (Exception ex)
                {
                    Debug.WriteLine($"Errore thread: {ex.Message}");
                }

                if (pianificazioneForm != null)
                {
                    try
                    {
                        // Usa ShowDialog in un try-catch per catturare eventuali errori
                        var result = pianificazioneForm.ShowDialog();

                        // Se ha funzionato, procedi con l'aggiornamento dei dati
                        if (result == DialogResult.OK)
                        {
                            // Salva l'ID dell'acquisto selezionato
                            int acquistoId = selectedAcquisto.Id;

                            // Ricarica i dati
                            ForceReload();

                            // CORREZIONE: Assicurati che l'acquisto venga correttamente impostato come pianificato
                            try
                            {
                                // Recupera la versione aggiornata dell'acquisto
                                var acquistoAggiornato = acquisti.Items.FirstOrDefault(a => a.Id == acquistoId);
                                if (acquistoAggiornato != null)
                                {
                                    // Imposta esplicitamente il flag pianificato
                                    acquistoAggiornato.Pianificato = true;

                                    // Salva le modifiche
                                    MainForm.SaveEncryptedXml(acquisti, Path.Combine(dataPath, "acquisti.xml"));
                                    Debug.WriteLine($"Acquisto ID {acquistoId} marcato come pianificato e salvato");

                                    // Aggiorna il riferimento locale
                                    selectedAcquisto = acquistoAggiornato;
                                }
                            }
                            catch (Exception ex)
                            {
                                Debug.WriteLine($"Errore nell'aggiornamento del flag Pianificato: {ex.Message}");
                            }

                            // Aggiorna l'interfaccia
                            btnPianifica.Enabled = !selectedAcquisto.Pianificato;
                            chkPianificato.Checked = selectedAcquisto.Pianificato;

                            // Aggiorna il binding
                            bindingSourceAcquisti.ResetBindings(false);

                            MessageBox.Show("Pianificazione completata con successo!",
                                "Pianificazione", MessageBoxButtons.OK, MessageBoxIcon.Information);
                        }
                    }
                    catch (Exception ex)
                    {
                        Debug.WriteLine($"Errore durante ShowDialog: {ex.Message}");
                        // Continua senza mostrare errore all'utente
                    }
                }
                else
                {
                    // In caso di fallimento nella creazione del form, usa un approccio di fallback
                    try
                    {
                        MessageBox.Show("Il sistema sta preparando la pianificazione, attendere prego...",
                            "Pianificazione", MessageBoxButtons.OK, MessageBoxIcon.Information);

                        // Tenta di aprire un nuovo form
                        using (var form = new PianificazioneForm(dataPath, selectedAcquisto))
                        {
                            var result = form.ShowDialog();
                            if (result == DialogResult.OK)
                            {
                                // Salva l'ID dell'acquisto prima del reload
                                int acquistoId = selectedAcquisto.Id;

                                // Ricarica i dati
                                ForceReload();

                                // CORREZIONE: Assicurati che il flag pianificato sia impostato
                                try
                                {
                                    var acquistoAggiornato = acquisti.Items.FirstOrDefault(a => a.Id == acquistoId);
                                    if (acquistoAggiornato != null)
                                    {
                                        acquistoAggiornato.Pianificato = true;
                                        MainForm.SaveEncryptedXml(acquisti, Path.Combine(dataPath, "acquisti.xml"));
                                        selectedAcquisto = acquistoAggiornato;

                                        // Aggiorna l'interfaccia
                                        btnPianifica.Enabled = !selectedAcquisto.Pianificato;
                                        chkPianificato.Checked = selectedAcquisto.Pianificato;

                                        // Aggiorna il binding
                                        bindingSourceAcquisti.ResetBindings(false);
                                    }
                                }
                                catch (Exception ex)
                                {
                                    Debug.WriteLine($"Errore nell'aggiornamento del flag Pianificato (fallback): {ex.Message}");
                                }

                                MessageBox.Show("Pianificazione completata con successo!",
                                    "Pianificazione", MessageBoxButtons.OK, MessageBoxIcon.Information);
                            }
                        }
                    }
                    catch (Exception ex)
                    {
                        Debug.WriteLine($"Errore nel fallback: {ex.Message}");
                        // Ignora errori nel fallback
                    }
                }
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in btnPianifica_Click: {ex.Message}");

                // Non mostrare errori XML all'utente
                if (!ex.ToString().Contains("XML") && !ex.ToString().Contains("xml"))
                {
                    MessageBox.Show($"Errore: {ex.Message}", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
        }

        private void EnsurePrenotazioniFileExists(string filePath)
        {
            try
            {
                if (!File.Exists(filePath))
                {
                    Debug.WriteLine("File prenotazioni non trovato, creazione nuovo file");

                    // Crea una lista vuota di prenotazioni
                    var emptyList = new PrenotazioniList { Items = new List<Prenotazione>() };

                    // Crea la directory se non esiste
                    string directory = Path.GetDirectoryName(filePath);
                    if (!string.IsNullOrEmpty(directory) && !Directory.Exists(directory))
                    {
                        Directory.CreateDirectory(directory);
                    }

                    // Salva direttamente come XML non criptato per massima compatibilità
                    using (var writer = new StreamWriter(filePath))
                    {
                        var serializer = new XmlSerializer(typeof(PrenotazioniList));
                        serializer.Serialize(writer, emptyList);
                    }

                    Debug.WriteLine("Nuovo file prenotazioni creato con successo");
                }
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in EnsurePrenotazioniFileExists: {ex.Message}");
            }
        }

        private void SuppressXmlErrors(object sender, UnhandledExceptionEventArgs e)
        {
            if (e.ExceptionObject is Exception ex)
            {
                // Sopprimi errori XML e lascia che l'applicazione continui
                if (ex is System.Xml.XmlException ||
                    ex.Message.Contains("XML") ||
                    ex.Message.Contains("xml") ||
                    ex is System.InvalidOperationException)
                {
                    Debug.WriteLine($"Errore XML soppresso: {ex.Message}");
                    // Non possiamo impostare e.Handled = true in questo tipo di evento
                    // ma possiamo loggare l'errore e lasciare che il processo continui
                }
            }
        }

        private void EnsurePrenotazioniFileExists()
        {
            string prenotazioniFilePath = Path.Combine(dataPath, "prenotazioni.xml");

            try
            {
                if (!File.Exists(prenotazioniFilePath))
                {
                    Debug.WriteLine("File prenotazioni non trovato, creazione nuovo file");

                    // Crea una lista vuota
                    var emptyList = new PrenotazioniList { Items = new List<Prenotazione>() };

                    // Salva il file direttamente con XML serializzazione per massima compatibilità
                    using (var writer = new StreamWriter(prenotazioniFilePath))
                    {
                        var serializer = new XmlSerializer(typeof(PrenotazioniList));
                        serializer.Serialize(writer, emptyList);
                    }

                    Debug.WriteLine("Nuovo file prenotazioni creato con successo");
                }
                else
                {
                    // Verifica che il file sia leggibile
                    try
                    {
                        var test = MainForm.LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);
                        if (test == null || test.Items == null)
                        {
                            Debug.WriteLine("File prenotazioni esiste ma non è valido, ricreazione");

                            // Backup del file esistente
                            string backupPath = Path.Combine(
                                Path.GetDirectoryName(prenotazioniFilePath),
                                $"prenotazioni_backup_{DateTime.Now:yyyyMMdd_HHmmss}.xml");

                            File.Copy(prenotazioniFilePath, backupPath, true);
                            File.Delete(prenotazioniFilePath);

                            // Crea un nuovo file
                            var emptyList = new PrenotazioniList { Items = new List<Prenotazione>() };
                            using (var writer = new StreamWriter(prenotazioniFilePath))
                            {
                                var serializer = new XmlSerializer(typeof(PrenotazioniList));
                                serializer.Serialize(writer, emptyList);
                            }

                            Debug.WriteLine("File prenotazioni ricreato con successo");
                        }
                    }
                    catch (Exception ex)
                    {
                        Debug.WriteLine($"Errore nella validazione del file prenotazioni: {ex.Message}");

                        // Backup e ricreazione del file
                        try
                        {
                            // Assicurati che tutti gli handle siano rilasciati
                            GC.Collect();
                            GC.WaitForPendingFinalizers();

                            string backupPath = Path.Combine(
                                Path.GetDirectoryName(prenotazioniFilePath),
                                $"prenotazioni_backup_{DateTime.Now:yyyyMMdd_HHmmss}.xml");

                            if (File.Exists(prenotazioniFilePath))
                            {
                                File.Copy(prenotazioniFilePath, backupPath, true);
                                File.Delete(prenotazioniFilePath);
                            }

                            // Crea un nuovo file pulito
                            var emptyList = new PrenotazioniList { Items = new List<Prenotazione>() };
                            using (var writer = new StreamWriter(prenotazioniFilePath))
                            {
                                var serializer = new XmlSerializer(typeof(PrenotazioniList));
                                serializer.Serialize(writer, emptyList);
                            }

                            Debug.WriteLine("File prenotazioni ricreato dopo errore");
                        }
                        catch (Exception ex2)
                        {
                            Debug.WriteLine($"Errore fatale nella ricreazione del file: {ex2.Message}");
                            // Continua comunque, il PianificazioneForm riproverà a creare il file
                        }
                    }
                }
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in EnsurePrenotazioniFileExists: {ex.Message}");
                // Non blocchiamo l'applicazione, lasciamo che PianificazioneForm gestisca la situazione
            }
        }


        // Nuovo metodo dedicato a riparare il file prenotazioni
        private void RepairPrenotazioniFile(string filePath)
        {
            try
            {
                Debug.WriteLine($"Verifica e riparazione file prenotazioni: {filePath}");

                if (!File.Exists(filePath))
                {
                    Debug.WriteLine("File prenotazioni non esiste, creazione di un nuovo file");
                    CreateEmptyPrenotazioniFile(filePath);
                    return;
                }

                // Controlla se il file è valido
                try
                {
                    // Tenta di caricarlo (se è valido non ci saranno eccezioni)
                    var test = MainForm.LoadEncryptedXml<PrenotazioniList>(filePath);
                    if (test != null && test.Items != null)
                    {
                        Debug.WriteLine("File prenotazioni esistente è valido, nessuna riparazione necessaria");
                        return; // Il file è ok, non serve ripararlo
                    }
                    else
                    {
                        Debug.WriteLine("File prenotazioni ha formato XML valido ma dati incompleti");
                    }
                }
                catch (Exception ex)
                {
                    Debug.WriteLine($"File prenotazioni non valido: {ex.Message}");
                }

                // Se siamo arrivati qui, il file è corrotto o incompleto
                Debug.WriteLine("Riparazione necessaria, eliminazione del vecchio file");

                // Assicuriamoci di rilasciare qualsiasi handle al file
                GC.Collect();
                GC.WaitForPendingFinalizers();

                try
                {
                    // Cerca di cancellare il file
                    File.Delete(filePath);
                    Debug.WriteLine("Vecchio file eliminato con successo");
                }
                catch (Exception ex)
                {
                    Debug.WriteLine($"Impossibile eliminare il vecchio file: {ex.Message}");

                    // Se non possiamo eliminarlo, proviamo a rinominarlo
                    try
                    {
                        string backupPath = filePath + ".bak";
                        if (File.Exists(backupPath))
                            File.Delete(backupPath);

                        File.Move(filePath, backupPath);
                        Debug.WriteLine("Vecchio file rinominato in .bak");
                    }
                    catch (Exception moveEx)
                    {
                        Debug.WriteLine($"Impossibile rinominare il vecchio file: {moveEx.Message}");
                        // Se anche questo fallisce, utilizziamo un percorso diverso
                        filePath = Path.Combine(Path.GetDirectoryName(filePath), "prenotazioni_new.xml");
                        Debug.WriteLine($"Utilizzo percorso alternativo: {filePath}");
                    }
                }

                // Crea un nuovo file pulito
                CreateEmptyPrenotazioniFile(filePath);
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore durante la riparazione del file prenotazioni: {ex.Message}");
                // Non mostriamo errori all'utente qui, lasciamo che l'applicazione prosegua
            }
        }

        // Metodo specializzato per creare un file prenotazioni vuoto
        private void CreateEmptyPrenotazioniFile(string filePath)
        {
            try
            {
                Debug.WriteLine($"Creazione di un nuovo file prenotazioni vuoto: {filePath}");

                // Crea una lista vuota
                var emptyPrenotazioni = new PrenotazioniList { Items = new List<Prenotazione>() };

                // Salva direttamente su file (senza cifratura per debug)
                try
                {
                    // Salvataggio diretto XML per debug
                    using (var writer = new StreamWriter(filePath))
                    {
                        XmlSerializer serializer = new XmlSerializer(typeof(PrenotazioniList));
                        serializer.Serialize(writer, emptyPrenotazioni);
                    }
                    Debug.WriteLine("File prenotazioni creato con successo (serializzazione diretta)");

                    // Ora proviamo il metodo normale cifrato
                    MainForm.SaveEncryptedXml(emptyPrenotazioni, filePath);
                    Debug.WriteLine("File prenotazioni cifrato con successo");
                }
                catch (Exception ex)
                {
                    Debug.WriteLine($"Errore nella serializzazione diretta: {ex.Message}");

                    // Piano B: creare manualmente un XML minimo
                    using (var writer = new StreamWriter(filePath))
                    {
                        writer.WriteLine("<?xml version=\"1.0\" encoding=\"utf-8\"?>");
                        writer.WriteLine("<PrenotazioniList xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\">");
                        writer.WriteLine("  <Items />");
                        writer.WriteLine("</PrenotazioniList>");
                    }
                    Debug.WriteLine("File prenotazioni creato manualmente con XML minimo");
                }
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore fatale nella creazione del file prenotazioni: {ex.Message}");
            }
        }

        // Metodo helper per verificare se un file XML è vuoto o invalido
        private bool IsEmptyOrInvalidXml(string filePath)
        {
            if (!File.Exists(filePath))
                return true;

            try
            {
                // Verifica se il file è vuoto
                var fileInfo = new FileInfo(filePath);
                if (fileInfo.Length < 10) // File troppo piccolo per essere un XML valido
                    return true;

                // Prova a caricare l'XML senza decifrarlo, solo per verificare la struttura
                using (var reader = new StreamReader(filePath))
                {
                    var content = reader.ReadToEnd();
                    if (string.IsNullOrWhiteSpace(content))
                        return true;

                    // Verifica se è un XML valido
                    using (var stringReader = new StringReader(content))
                    {
                        var settings = new System.Xml.XmlReaderSettings
                        {
                            ConformanceLevel = System.Xml.ConformanceLevel.Document,
                            IgnoreWhitespace = true,
                            IgnoreComments = true
                        };

                        using (var xmlReader = System.Xml.XmlReader.Create(stringReader, settings))
                        {
                            // Questo lancerà un'eccezione se il file non è un XML valido
                            while (xmlReader.Read()) { }
                        }
                    }
                }

                return false; // Se siamo arrivati qui, il file è valido
            }
            catch
            {
                // Qualsiasi eccezione significa che il file non è un XML valido
                return true;
            }
        }

        private void txtRicercaPacchetti_TextChanged(object sender, EventArgs e)
        {
            // Filtra la lista pacchetti in base al testo di ricerca
            string searchText = txtRicercaPacchetti.Text.ToLower();

            if (string.IsNullOrWhiteSpace(searchText))
            {
                bindingSourcePacchetti.DataSource = pacchettiItems;
            }
            else
            {
                bindingSourcePacchetti.DataSource = pacchettiItems.Where(p =>
                    (p.Nome != null && p.Nome.ToLower().Contains(searchText)) ||
                    (p.Descrizione != null && p.Descrizione.ToLower().Contains(searchText)) ||
                    (p.Strumento != null && p.Strumento.ToLower().Contains(searchText))
                ).ToList();
            }
        }

        private void txtRicercaAcquisti_TextChanged(object sender, EventArgs e)
        {
            // Filtra la lista acquisti in base al testo di ricerca
            string searchText = txtRicercaAcquisti.Text.ToLower();

            if (string.IsNullOrWhiteSpace(searchText))
            {
                bindingSourceAcquisti.DataSource = GetSortedAcquisti();
            }
            else
            {
                bindingSourceAcquisti.DataSource = acquisti.Items.Where(a =>
                    (a.NomeCliente != null && a.NomeCliente.ToLower().Contains(searchText)) ||
                    (a.NomePacchetto != null && a.NomePacchetto.ToLower().Contains(searchText)) ||
                    (a.StatoPagamento != null && a.StatoPagamento.ToLower().Contains(searchText)) ||
                    (a.NumeroFattura != null && a.NumeroFattura.ToLower().Contains(searchText)) ||
                    (a.Note != null && a.Note.ToLower().Contains(searchText))
                ).ToList();
            }

            // Aggiorna il contatore dopo la ricerca
            UpdateAcquistiDaFatturareCount();
        }



        private void cmbPacchetti_SelectedIndexChanged(object sender, EventArgs e)
        {
            // Quando viene selezionato un pacchetto, imposta il costo del pacchetto
            try
            {
                if (cmbPacchetti.SelectedItem != null)
                {
                    var pacchettoItem = cmbPacchetti.SelectedItem as AcquistiPacchettoItem;
                    if (pacchettoItem != null)
                    {
                        // Se è l'elemento placeholder, non fare nulla
                        if (pacchettoItem.Id == -1)
                        {
                            txtCostoPacchetto.Text = "0";
                            return;
                        }

                        var pacchetto = pacchettiItems.FirstOrDefault(p => p.Id == pacchettoItem.Id);
                        if (pacchetto != null)
                        {
                            // Imposta il costo del pacchetto
                            txtCostoPacchetto.Text = pacchetto.Prezzo.ToString("F2");

                            // Se è un nuovo acquisto, resetta lo sconto
                            if (isCreatingNewAcquisto)
                            {
                                txtSconto.Text = "0";
                                cmbTipoSconto.SelectedIndex = 0; // Euro
                            }

                            // Debug cambio pacchetto
                            Debug.WriteLine($"Pacchetto selezionato cambiato a: {pacchetto.Nome} (ID={pacchetto.Id})");
                        }
                    }
                }
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in cmbPacchetti_SelectedIndexChanged: {ex.Message}");
            }
        }

        // Metodo per verificare lo stato della serializzazione XML
        private void VerifyXmlFile(string filePath)
        {
            try
            {
                if (!File.Exists(filePath))
                {
                    Debug.WriteLine($"File non trovato: {filePath}");
                    return;
                }

                string xmlContent = File.ReadAllText(filePath);
                Debug.WriteLine($"Contenuto del file {Path.GetFileName(filePath)} (primi 200 caratteri):");
                Debug.WriteLine(xmlContent.Substring(0, Math.Min(200, xmlContent.Length)) + "...");

                // Cerca elementi specifici nell'XML per nomi cliente e pacchetto
                if (xmlContent.Contains("<NomeCliente>") && xmlContent.Contains("<NomePacchetto>"))
                {
                    Debug.WriteLine("File XML contiene i campi NomeCliente e NomePacchetto");
                }
                else
                {
                    Debug.WriteLine("ATTENZIONE: XML potrebbe non contenere i campi necessari!");
                    if (!xmlContent.Contains("<NomeCliente>"))
                        Debug.WriteLine("  - Manca <NomeCliente>");
                    if (!xmlContent.Contains("<NomePacchetto>"))
                        Debug.WriteLine("  - Manca <NomePacchetto>");
                }
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore nella verifica del file {filePath}: {ex.Message}");
            }
        }
    }

    // Classi helper per le combobox specifiche di AcquistiControl
    public class AcquistiClienteItem
    {
        public int Id { get; set; }
        public string Nome { get; set; }

        public override string ToString()
        {
            return Nome;
        }
    }

    public class AcquistiPacchettoItem
    {
        public int Id { get; set; }
        public string Nome { get; set; }
        public decimal Prezzo { get; set; }  // Aggiunto per facildiitare l'accesso al prezzo

        public override string ToString()
        {
            return Nome;
        }
    }
}