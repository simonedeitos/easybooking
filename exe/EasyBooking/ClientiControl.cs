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
using System.Text.RegularExpressions;
using System.Globalization;
using System.Text;
using Microsoft.Win32;
using iTextSharp.text;
using iTextSharp.text.pdf;
using EasyBooking.Models;
using EasyBooking.AcquistiModels;

namespace EasyBooking
{
    public partial class ClientiControl : UserControl
    {
        private string dataPath;
        private ClientiList clienti;
        private BindingSource bindingSource;
        private Cliente selectedCliente;
        private bool isNewClient;

        private string clientiFilePath;
        private string prenotazioniFilePath;
        private string acquistiFilePath;
        private string pacchettiFilePath;

        // Lista delle prenotazioni del cliente corrente per il menu contestuale
        private List<Prenotazione> currentClientLessons;


        public ClientiControl(string dataPath)
        {
            InitializeComponent();
            this.dataPath = dataPath;
            clientiFilePath = Path.Combine(dataPath, "clienti.xml");
            prenotazioniFilePath = Path.Combine(dataPath, "prenotazioni.xml");
            acquistiFilePath = Path.Combine(dataPath, "acquisti.xml");
            pacchettiFilePath = Path.Combine(dataPath, "pacchetti.xml");

            LoadData();
            SetupDataGridView();
            SetupLessonsContextMenu();
            SetupRiepilogoMenu();
            SetupAcquistiContextMenu();
        }

        

        private void TabControlDati_SelectedIndexChanged(object sender, EventArgs e)
        {
            if (selectedCliente != null && tabControlDati.SelectedTab == tabAcquisti)
            {
                LoadClientAcquistiGrid();
            }
        }

        private void SetupAcquistiContextMenu()
        {
            ContextMenuStrip acquistiContextMenu = new ContextMenuStrip();

            ToolStripMenuItem viewDetailsMenuItem = new ToolStripMenuItem("Visualizza Dettagli");
            viewDetailsMenuItem.Click += ViewAcquistoDetails_Click;
            acquistiContextMenu.Items.Add(viewDetailsMenuItem);

            ToolStripMenuItem editPagamentoMenuItem = new ToolStripMenuItem("Modifica Stato Pagamento");
            editPagamentoMenuItem.Click += EditStatoPagamento_Click;
            acquistiContextMenu.Items.Add(editPagamentoMenuItem);

            dgvAcquisti.ContextMenuStrip = acquistiContextMenu;
        }

        private void ViewAcquistoDetails_Click(object sender, EventArgs e)
        {
            if (dgvAcquisti.SelectedRows.Count == 0)
            {
                MessageBox.Show("Seleziona un acquisto da visualizzare.", "Attenzione",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            try
            {
                int selectedRowIndex = dgvAcquisti.SelectedRows[0].Index;
                var acquisti = GetClientAcquisti();

                if (selectedRowIndex < acquisti.Count)
                {
                    var acquisto = acquisti[selectedRowIndex];

                    // Mostra i dettagli dell'acquisto
                    string dettagli = $"Data Acquisto: {acquisto.DataAcquisto:dd/MM/yyyy}\n" +
                                    $"Pacchetto: {acquisto.NomePacchetto}\n" +
                                    $"Numero Lezioni: {acquisto.NumeroLezioni}\n" +
                                    $"Importo: {acquisto.ImportoPagato:C}\n" +
                                    $"Stato Pagamento: {acquisto.StatoPagamento}\n" +
                                    $"Numero Fattura: {(string.IsNullOrEmpty(acquisto.NumeroFattura) ? "Non emessa" : acquisto.NumeroFattura)}\n" +
                                    $"Pianificato: {(acquisto.Pianificato ? "Sì" : "No")}\n" +
                                    $"Note: {(string.IsNullOrEmpty(acquisto.Note) ? "Nessuna" : acquisto.Note)}";

                    MessageBox.Show(dettagli, "Dettagli Acquisto", MessageBoxButtons.OK, MessageBoxIcon.Information);
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante la visualizzazione dei dettagli: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void EditStatoPagamento_Click(object sender, EventArgs e)
        {
            if (dgvAcquisti.SelectedRows.Count == 0)
            {
                MessageBox.Show("Seleziona un acquisto da modificare.", "Attenzione",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            try
            {
                int selectedRowIndex = dgvAcquisti.SelectedRows[0].Index;
                var acquisti = GetClientAcquisti();

                if (selectedRowIndex < acquisti.Count)
                {
                    var acquisto = acquisti[selectedRowIndex];

                    // Crea un semplice form per modificare lo stato pagamento
                    using (var form = new Form())
                    {
                        form.Text = "Modifica Stato Pagamento";
                        form.Size = new Size(350, 150);
                        form.StartPosition = FormStartPosition.CenterParent;

                        var lblStato = new Label { Text = "Stato Pagamento:", Location = new Point(20, 20), Size = new Size(100, 25) };
                        var cmbStato = new ComboBox
                        {
                            Location = new Point(130, 20),
                            Size = new Size(180, 25),
                            DropDownStyle = ComboBoxStyle.DropDownList
                        };
                        cmbStato.Items.AddRange(new[] { "Pagato", "Da pagare", "Pagamento parziale", "Annullato" });
                        cmbStato.SelectedItem = acquisto.StatoPagamento;

                        var btnOK = new Button { Text = "OK", Location = new Point(150, 60), Size = new Size(75, 30), DialogResult = DialogResult.OK };
                        var btnCancel = new Button { Text = "Annulla", Location = new Point(235, 60), Size = new Size(75, 30), DialogResult = DialogResult.Cancel };

                        form.Controls.AddRange(new Control[] { lblStato, cmbStato, btnOK, btnCancel });

                        if (form.ShowDialog() == DialogResult.OK)
                        {
                            // Aggiorna lo stato pagamento
                            var acquistiList = MainForm.LoadEncryptedXml<Models.AcquistiList>(acquistiFilePath);
                            var acquistoToUpdate = acquistiList.Items.FirstOrDefault(a => a.Id == acquisto.Id);

                            if (acquistoToUpdate != null)
                            {
                                acquistoToUpdate.StatoPagamento = cmbStato.SelectedItem.ToString();
                                MainForm.SaveEncryptedXml(acquistiList, acquistiFilePath);

                                LoadClientAcquistiGrid();
                                MessageBox.Show("Stato pagamento aggiornato con successo.", "Successo",
                                    MessageBoxButtons.OK, MessageBoxIcon.Information);
                            }
                        }
                    }
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante la modifica dello stato pagamento: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void LoadClientAcquistiGrid()
        {
            if (selectedCliente == null)
            {
                dgvAcquisti.DataSource = null;
                return;
            }

            try
            {
                var acquisti = GetClientAcquisti();
                var prenotazioniList = MainForm.LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);

                var acquistiView = acquisti.Select(a =>
                {
                    // Conta le lezioni effettuate per questo acquisto
                    int lezioniEffettuate = 0;
                    if (prenotazioniList?.Items != null)
                    {
                        lezioniEffettuate = prenotazioniList.Items
                            .Count(p => p.ClienteId == selectedCliente.Id &&
                                       p.AcquistoId == a.Id &&
                                       p.Stato == StatoLezione.Svolta);
                    }

                    return new
                    {
                        Data = a.DataAcquisto.ToString("dd/MM/yyyy"),
                        Pacchetto = string.IsNullOrEmpty(a.NomePacchetto) ? $"Pacchetto ID {a.PacchettoId}" : a.NomePacchetto,
                        Importo = a.ImportoPagato.ToString("C", CultureInfo.CreateSpecificCulture("it-IT")),
                        Stato = a.StatoPagamento,
                        Fattura = string.IsNullOrEmpty(a.NumeroFattura) ? "-" : a.NumeroFattura,
                        Lezioni = $"{lezioniEffettuate}/{a.NumeroLezioni}",
                        Pianificato = a.Pianificato ? "Sì" : "No"
                    };
                }).ToList();

                dgvAcquisti.DataSource = acquistiView;

                if (dgvAcquisti.Columns.Count > 0)
                {
                    dgvAcquisti.Columns["Data"].Width = 75;
                    dgvAcquisti.Columns["Pacchetto"].Width = 150;
                    dgvAcquisti.Columns["Importo"].Width = 65;
                    dgvAcquisti.Columns["Stato"].Width = 80;
                    dgvAcquisti.Columns["Fattura"].Width = 80;
                    dgvAcquisti.Columns["Lezioni"].Width = 60;
                    dgvAcquisti.Columns["Lezioni"].HeaderText = "Svolte";
                    dgvAcquisti.Columns["Pianificato"].Width = 75;
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore nel caricamento degli acquisti: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                dgvAcquisti.DataSource = null;
            }
        }

        // Il resto del codice rimane uguale...
        private void LoadData()
        {
            clienti = MainForm.LoadEncryptedXml<ClientiList>(clientiFilePath);

            if (clienti == null || clienti.Items == null)
            {
                clienti = new ClientiList();
            }

            cmbFiltroLezioni.SelectedIndex = 0;
        }

        private void DgvClienti_ColumnHeaderMouseClick(object sender, DataGridViewCellMouseEventArgs e)
        {
            try
            {
                string columnName = dgvClienti.Columns[e.ColumnIndex].Name;

                // Consenti l'ordinamento solo per le colonne Nome e Cognome
                if (columnName == "Nome" || columnName == "Cognome")
                {
                    _isUpdatingSelection = true; // Blocca gli eventi di selezione

                    var currentList = ((BindingList<Cliente>)bindingSource.DataSource).ToList();
                    ListSortDirection sortDirection = ListSortDirection.Ascending;

                    // Determina la direzione dell'ordinamento in base all'ultimo ordinamento
                    if (dgvClienti.Columns[e.ColumnIndex].HeaderCell.SortGlyphDirection == SortOrder.Ascending)
                    {
                        sortDirection = ListSortDirection.Descending;
                    }

                    // Esegui l'ordinamento
                    IEnumerable<Cliente> sortedList;
                    if (columnName == "Nome")
                    {
                        sortedList = sortDirection == ListSortDirection.Ascending
                            ? currentList.OrderBy(c => c.Nome ?? string.Empty)
                            : currentList.OrderByDescending(c => c.Nome ?? string.Empty);
                    }
                    else // Cognome
                    {
                        sortedList = sortDirection == ListSortDirection.Ascending
                            ? currentList.OrderBy(c => c.Cognome ?? string.Empty)
                            : currentList.OrderByDescending(c => c.Cognome ?? string.Empty);
                    }

                    // Salva l'ID del cliente correntemente selezionato (se esiste)
                    int? selectedClienteId = selectedCliente?.Id;

                    // Aggiorna la lista ordinata
                    var newSortableList = new BindingList<Cliente>(sortedList.ToList());
                    bindingSource.DataSource = newSortableList;

                    // Aggiorna il glifo di ordinamento
                    foreach (DataGridViewColumn col in dgvClienti.Columns)
                    {
                        col.HeaderCell.SortGlyphDirection = SortOrder.None;
                    }

                    dgvClienti.Columns[e.ColumnIndex].HeaderCell.SortGlyphDirection =
                        sortDirection == ListSortDirection.Ascending ? SortOrder.Ascending : SortOrder.Descending;

                    // Riseleziona il cliente che era selezionato prima dell'ordinamento
                    if (selectedClienteId.HasValue)
                    {
                        for (int i = 0; i < newSortableList.Count; i++)
                        {
                            if (newSortableList[i].Id == selectedClienteId.Value)
                            {
                                dgvClienti.ClearSelection();
                                dgvClienti.Rows[i].Selected = true;
                                dgvClienti.FirstDisplayedScrollingRowIndex = i;
                                break;
                            }
                        }
                    }

                    _isUpdatingSelection = false; // Riattiva gli eventi di selezione
                }
            }
            catch (Exception ex)
            {
                _isUpdatingSelection = false; // Assicurati di riattivare in caso di errore
                MessageBox.Show($"Errore durante l'ordinamento: {ex.Message}", "Errore",
                    MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        // Tutti gli altri metodi rimangono identici...
        private void SetupDataGridView()
        {
            bindingSource = new BindingSource();

            // Ordina per Cognome di default prima di creare la BindingList
            var sortedClients = clienti.Items.OrderBy(c => c.Cognome ?? string.Empty).ToList();
            var sortableList = new BindingList<Cliente>(sortedClients);
            bindingSource.DataSource = sortableList;

            dgvClienti.DataSource = bindingSource;

            // Abilita l'ordinamento per le colonne
            dgvClienti.AllowUserToOrderColumns = true;

            // Gestisci manualmente l'evento di ordinamento delle colonne
            dgvClienti.ColumnHeaderMouseClick += DgvClienti_ColumnHeaderMouseClick;

            if (dgvClienti.Columns.Count > 0)
            {
                // Nascondi le colonne non necessarie - usa DisplayIndex = -1 se Visible = false non funziona
                if (dgvClienti.Columns["Id"] != null)
                {
                    dgvClienti.Columns["Id"].Visible = false;
                    dgvClienti.Columns["Id"].Width = 0; // Imposta anche width a 0 per sicurezza
                }

                if (dgvClienti.Columns["Indirizzo"] != null)
                    dgvClienti.Columns["Indirizzo"].Visible = false;
                if (dgvClienti.Columns["CodiceFiscale"] != null)
                    dgvClienti.Columns["CodiceFiscale"].Visible = false;
                if (dgvClienti.Columns["Note"] != null)
                    dgvClienti.Columns["Note"].Visible = false;
                if (dgvClienti.Columns["MegaCartellaPubblica"] != null)
                    dgvClienti.Columns["MegaCartellaPubblica"].Visible = false;
                if (dgvClienti.Columns["MegaCartellaLocale"] != null)
                    dgvClienti.Columns["MegaCartellaLocale"].Visible = false;

                // Configura le colonne visibili
                if (dgvClienti.Columns["Nome"] != null)
                {
                    dgvClienti.Columns["Nome"].HeaderText = "Nome";
                    dgvClienti.Columns["Nome"].Width = 100;
                    dgvClienti.Columns["Nome"].SortMode = DataGridViewColumnSortMode.Automatic;
                }

                if (dgvClienti.Columns["Cognome"] != null)
                {
                    dgvClienti.Columns["Cognome"].HeaderText = "Cognome";
                    dgvClienti.Columns["Cognome"].Width = 100;
                    dgvClienti.Columns["Cognome"].SortMode = DataGridViewColumnSortMode.Automatic;
                    // Imposta il glifo di ordinamento per indicare che è ordinato per Cognome
                    dgvClienti.Columns["Cognome"].HeaderCell.SortGlyphDirection = SortOrder.Ascending;
                }

                if (dgvClienti.Columns["Telefono"] != null)
                {
                    dgvClienti.Columns["Telefono"].HeaderText = "Telefono";
                    dgvClienti.Columns["Telefono"].Width = 90;
                }

                if (dgvClienti.Columns["Email"] != null)
                {
                    dgvClienti.Columns["Email"].HeaderText = "Email";
                    dgvClienti.Columns["Email"].Width = 180;
                }
            }

            DisableDetailForm();
        }

        private void ApplyDefaultSort()
        {
            try
            {
                _isUpdatingSelection = true; // Blocca gli eventi durante l'ordinamento iniziale

                var currentList = ((BindingList<Cliente>)bindingSource.DataSource).ToList();

                // Ordina per cognome in ordine alfabetico (ascendente)
                var sortedList = currentList.OrderBy(c => c.Cognome ?? string.Empty).ToList();

                // Aggiorna la lista ordinata
                var newSortableList = new BindingList<Cliente>(sortedList);
                bindingSource.DataSource = newSortableList;

                // Imposta il glifo di ordinamento sulla colonna Cognome
                foreach (DataGridViewColumn col in dgvClienti.Columns)
                {
                    col.HeaderCell.SortGlyphDirection = SortOrder.None;
                }

                if (dgvClienti.Columns["Cognome"] != null)
                {
                    dgvClienti.Columns["Cognome"].HeaderCell.SortGlyphDirection = SortOrder.Ascending;
                }

                _isUpdatingSelection = false;
            }
            catch (Exception ex)
            {
                _isUpdatingSelection = false;
                MessageBox.Show($"Errore durante l'ordinamento di default: {ex.Message}", "Errore",
                    MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void SetupLessonsContextMenu()
        {
            // Crea il menu contestuale per la griglia delle lezioni
            ContextMenuStrip lessonsContextMenu = new ContextMenuStrip();

            ToolStripMenuItem editMenuItem = new ToolStripMenuItem("Modifica Lezione");
            editMenuItem.Name = "editMenuItem";
            editMenuItem.Click += EditLesson_Click;
            lessonsContextMenu.Items.Add(editMenuItem);

            ToolStripMenuItem deleteMenuItem = new ToolStripMenuItem("Elimina Lezione");
            deleteMenuItem.Name = "deleteMenuItem";
            deleteMenuItem.Click += DeleteLesson_Click;
            lessonsContextMenu.Items.Add(deleteMenuItem);

            // NUOVO: Voce per l'eliminazione multipla
            ToolStripMenuItem deleteMultipleMenuItem = new ToolStripMenuItem("Elimina Lezioni Selezionate");
            deleteMultipleMenuItem.Name = "deleteMultipleMenuItem";
            deleteMultipleMenuItem.Click += DeleteMultipleLessons_Click;
            lessonsContextMenu.Items.Add(deleteMultipleMenuItem);

            // Gestisci l'apertura del menu per mostrare/nascondere le voci
            lessonsContextMenu.Opening += (sender, e) =>
            {
                bool isMultiSelect = dgvLezioni.SelectedRows.Count > 1;
                lessonsContextMenu.Items["editMenuItem"].Visible = !isMultiSelect;
                lessonsContextMenu.Items["deleteMenuItem"].Visible = !isMultiSelect;
                lessonsContextMenu.Items["deleteMultipleMenuItem"].Visible = isMultiSelect;
            };

            // Assegna il menu contestuale alla griglia delle lezioni
            dgvLezioni.ContextMenuStrip = lessonsContextMenu;
        }

        private void DeleteMultipleLessons_Click(object sender, EventArgs e)
        {
            int selectedCount = dgvLezioni.SelectedRows.Count;
            if (selectedCount <= 1) return;

            var result = MessageBox.Show(
                $"Sei sicuro di voler eliminare le {selectedCount} lezioni selezionate?",
                "Conferma eliminazione multipla",
                MessageBoxButtons.YesNo,
                MessageBoxIcon.Question);

            if (result == DialogResult.No) return;

            try
            {
                var idsToDelete = new List<int>();
                foreach (DataGridViewRow row in dgvLezioni.SelectedRows)
                {
                    int rowIndex = row.Index;
                    if (rowIndex < currentClientLessons.Count)
                    {
                        idsToDelete.Add(currentClientLessons[rowIndex].Id);
                    }
                }

                if (idsToDelete.Any())
                {
                    var prenotazioniList = MainForm.LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);
                    if (prenotazioniList?.Items != null)
                    {
                        int removedCount = prenotazioniList.Items.RemoveAll(p => idsToDelete.Contains(p.Id));
                        MainForm.SaveEncryptedXml(prenotazioniList, prenotazioniFilePath);
                        LoadClientLessons(selectedCliente);

                        MessageBox.Show($"{removedCount} lezioni eliminate con successo.", "Successo",
                            MessageBoxButtons.OK, MessageBoxIcon.Information);
                    }
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante l'eliminazione delle lezioni: {ex.Message}", "Errore",
                    MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void SetupRiepilogoMenu()
        {
            // Crea il menu contestuale per il pulsante Riepilogo
            ContextMenuStrip riepilogoMenu = new ContextMenuStrip();

            ToolStripMenuItem whatsappFuturi = new ToolStripMenuItem("Riepilogo Futuri WhatsApp");
            whatsappFuturi.Click += (s, e) => RiepilogoFuturiWhatsApp();
            riepilogoMenu.Items.Add(whatsappFuturi);

            ToolStripMenuItem emailFuturi = new ToolStripMenuItem("Riepilogo Futuri Email");
            emailFuturi.Click += (s, e) => RiepilogoFuturiEmail();
            riepilogoMenu.Items.Add(emailFuturi);

            ToolStripMenuItem pdfFuturi = new ToolStripMenuItem("Riepilogo Futuri PDF");
            pdfFuturi.Click += (s, e) => RiepilogoFuturiPDF();
            riepilogoMenu.Items.Add(pdfFuturi);

            riepilogoMenu.Items.Add(new ToolStripSeparator());

            ToolStripMenuItem pdfStorico = new ToolStripMenuItem("Riepilogo Storico PDF");
            pdfStorico.Click += (s, e) => RiepilogoStoricoPDF();
            riepilogoMenu.Items.Add(pdfStorico);

            // Assegna il menu al pulsante
            btnRiepilogo.ContextMenuStrip = riepilogoMenu;
        }

        private void LoadClientDetails(Cliente cliente)
        {
            selectedCliente = cliente;
            isNewClient = string.IsNullOrWhiteSpace(cliente.Nome) && string.IsNullOrWhiteSpace(cliente.Cognome);

            txtNome.Text = cliente.Nome ?? string.Empty;
            txtCognome.Text = cliente.Cognome ?? string.Empty;
            txtTelefono.Text = cliente.Telefono ?? string.Empty;
            txtIndirizzo.Text = cliente.Indirizzo ?? string.Empty;
            txtCodiceFiscale.Text = cliente.CodiceFiscale ?? string.Empty;
            txtEmail.Text = cliente.Email ?? string.Empty;
            txtNote.Text = cliente.Note ?? string.Empty;

            EnableDetailForm();

            btnWhatsapp.Enabled = !string.IsNullOrEmpty(cliente.Telefono);
            btnEmail.Enabled = !string.IsNullOrEmpty(cliente.Email);
            btnRiepilogo.Enabled = true;

            // Aggiorna lo stato dei pulsanti MEGA
            UpdateMegaButtonsState();

            // Aggiorna lo stato del pulsante Rinnovo Veloce
            UpdateRinnovoVeloceButtonState();

            LoadClientLessons(cliente);

            // Se il tab Acquisti è selezionato, carica anche gli acquisti
            if (tabControlDati.SelectedTab == tabAcquisti)
            {
                LoadClientAcquistiGrid();
            }
        }

        // Il resto dei metodi rimane identico come nel codice originale...
        private void btnRinnovoVeloce_Click(object sender, EventArgs e)
        {
            try
            {
                // 1. Verifica se è stato selezionato un cliente
                if (selectedCliente == null)
                {
                    MessageBox.Show("Seleziona un cliente.", "Attenzione",
                        MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    return;
                }

                // 2. Trova l'ultimo acquisto del cliente
                var acquistiList = MainForm.LoadEncryptedXml<Models.AcquistiList>(acquistiFilePath);
                if (acquistiList == null || acquistiList.Items == null)
                {
                    MessageBox.Show("Non ci sono acquisti per questo cliente.", "Informazione",
                        MessageBoxButtons.OK, MessageBoxIcon.Information);
                    return;
                }

                var clienteAcquisti = acquistiList.Items
                    .Where(a => a.ClienteId == selectedCliente.Id)
                    .OrderByDescending(a => a.DataAcquisto)
                    .ToList();

                if (!clienteAcquisti.Any())
                {
                    MessageBox.Show("Non ci sono acquisti per questo cliente.", "Informazione",
                        MessageBoxButtons.OK, MessageBoxIcon.Information);
                    return;
                }

                var ultimoAcquisto = clienteAcquisti.First();

                // 3. Trova le lezioni associate all'ultimo acquisto
                var prenotazioniList = MainForm.LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);
                if (prenotazioniList == null || prenotazioniList.Items == null)
                {
                    MessageBox.Show("Non ci sono lezioni per questo acquisto.", "Informazione",
                        MessageBoxButtons.OK, MessageBoxIcon.Information);
                    return;
                }

                var lezioniUltimoAcquisto = prenotazioniList.Items
                    .Where(p => p.ClienteId == selectedCliente.Id && p.AcquistoId == ultimoAcquisto.Id)
                    .OrderBy(p => p.Data)
                    .ThenBy(p => p.OraInizio)
                    .ToList();

                // 4. Verifica che ci sia almeno una lezione
                if (!lezioniUltimoAcquisto.Any())
                {
                    MessageBox.Show("Non ci sono lezioni associate all'ultimo acquisto.", "Informazione",
                        MessageBoxButtons.OK, MessageBoxIcon.Information);
                    return;
                }

                // 5. Apri il form RinnovoVeloce
                using (var rinnovoForm = new RinnovoVeloceForm(dataPath, selectedCliente, ultimoAcquisto, lezioniUltimoAcquisto))
                {
                    if (rinnovoForm.ShowDialog() == DialogResult.OK)
                    {
                        // 6. Recupera il nuovo acquisto creato
                        var nuovoAcquisto = rinnovoForm.NuovoAcquisto;
                        if (nuovoAcquisto != null)
                        {
                            // Assegna un nuovo ID
                            nuovoAcquisto.Id = acquistiList.Items.Count > 0 ? acquistiList.Items.Max(a => a.Id) + 1 : 1;

                            // Aggiungi il nuovo acquisto alla lista
                            acquistiList.Items.Add(nuovoAcquisto);

                            // 7. Salva il file degli acquisti
                            MainForm.SaveEncryptedXml(acquistiList, acquistiFilePath);

                            // 8. Se richiesto, pianifica automaticamente le nuove lezioni
                            if (rinnovoForm.PianificaSubito)
                            {
                                PianificaLezioniAutomaticamente(nuovoAcquisto, lezioniUltimoAcquisto);
                            }

                            // 9. Aggiorna la visualizzazione
                            LoadClientLessons(selectedCliente);
                            if (tabControlDati.SelectedTab == tabAcquisti)
                            {
                                LoadClientAcquistiGrid();
                            }
                            UpdateRinnovoVeloceButtonState();

                            MessageBox.Show("Rinnovo completato con successo!", "Successo",
                                MessageBoxButtons.OK, MessageBoxIcon.Information);
                        }
                    }
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante il rinnovo veloce: {ex.Message}", "Errore",
                    MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        // Tutti gli altri metodi rimangono identici al codice originale...
        private void PianificaLezioniAutomaticamente(Models.Acquisto nuovoAcquisto, List<Prenotazione> lezioniPrecedenti)
        {
            try
            {
                var prenotazioniList = MainForm.LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);
                if (prenotazioniList == null)
                {
                    prenotazioniList = new PrenotazioniList();
                }

                // Trova l'ultima lezione del gruppo precedente
                var ultimaLezione = lezioniPrecedenti
                    .OrderByDescending(l => l.Data)
                    .ThenByDescending(l => l.OraInizio)
                    .FirstOrDefault();

                if (ultimaLezione == null)
                    return;

                // Calcola la data iniziale per le nuove lezioni (settimana successiva all'ultima)
                DateTime dataIniziale = ultimaLezione.Data.AddDays(7);

                // Assicurati che sia nel futuro
                while (dataIniziale <= DateTime.Today)
                {
                    dataIniziale = dataIniziale.AddDays(7);
                }

                // Genera l'ID iniziale per le nuove prenotazioni
                int nextId = prenotazioniList.Items.Count > 0 ? prenotazioniList.Items.Max(p => p.Id) + 1 : 1;

                // Crea le nuove lezioni basandosi sul pattern dell'ultima lezione
                for (int i = 0; i < nuovoAcquisto.NumeroLezioni; i++)
                {
                    var nuovaLezione = new Prenotazione
                    {
                        Id = nextId++,
                        Data = dataIniziale.AddDays(i * 7),
                        OraInizioStr = ultimaLezione.OraInizioStr,
                        OraFineStr = ultimaLezione.OraFineStr,
                        ClienteId = ultimaLezione.ClienteId,
                        InsegnanteId = ultimaLezione.InsegnanteId,
                        Strumento = ultimaLezione.Strumento,
                        Stato = StatoLezione.Programmata,
                        PacchettoNome = nuovoAcquisto.NomePacchetto,
                        AcquistoId = nuovoAcquisto.Id
                    };

                    prenotazioniList.Items.Add(nuovaLezione);
                }

                // Salva le nuove prenotazioni
                MainForm.SaveEncryptedXml(prenotazioniList, prenotazioniFilePath);

                // Aggiorna l'acquisto per segnare che è stato pianificato
                var acquistiList = MainForm.LoadEncryptedXml<Models.AcquistiList>(acquistiFilePath);
                var acquistoDaAggiornare = acquistiList.Items.FirstOrDefault(a => a.Id == nuovoAcquisto.Id);
                if (acquistoDaAggiornare != null)
                {
                    acquistoDaAggiornare.Pianificato = true;
                    MainForm.SaveEncryptedXml(acquistiList, acquistiFilePath);
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante la pianificazione automatica: {ex.Message}", "Errore",
                    MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void UpdateRinnovoVeloceButtonState()
        {
            if (selectedCliente == null || btnRinnovoVeloce == null)
            {
                if (btnRinnovoVeloce != null)
                    btnRinnovoVeloce.Enabled = false;
                return;
            }

            try
            {
                // Verifica se il cliente ha acquisti con lezioni associate
                var acquistiList = MainForm.LoadEncryptedXml<Models.AcquistiList>(acquistiFilePath);
                var prenotazioniList = MainForm.LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);

                bool hasAcquistiConLezioni = false;

                if (acquistiList?.Items != null && prenotazioniList?.Items != null)
                {
                    var clienteAcquisti = acquistiList.Items.Where(a => a.ClienteId == selectedCliente.Id).ToList();

                    foreach (var acquisto in clienteAcquisti)
                    {
                        if (prenotazioniList.Items.Any(p => p.ClienteId == selectedCliente.Id && p.AcquistoId == acquisto.Id))
                        {
                            hasAcquistiConLezioni = true;
                            break;
                        }
                    }
                }

                btnRinnovoVeloce.Enabled = hasAcquistiConLezioni;
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in UpdateRinnovoVeloceButtonState: {ex.Message}");
                btnRinnovoVeloce.Enabled = false;
            }
        }

        private void btnRiepilogo_Click(object sender, EventArgs e)
        {
            if (selectedCliente == null)
            {
                MessageBox.Show("Seleziona un cliente.", "Attenzione",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            // Mostra il menu contestuale sotto il pulsante
            Point location = new Point(0, btnRiepilogo.Height);
            btnRiepilogo.ContextMenuStrip.Show(btnRiepilogo, location);
        }

        private void btnCartella_Click(object sender, EventArgs e)
        {
            if (selectedCliente == null)
            {
                MessageBox.Show("Seleziona un cliente.", "Attenzione",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            if (string.IsNullOrEmpty(selectedCliente.MegaCartellaLocale))
            {
                MessageBox.Show("Cartella locale MEGA non configurata per questo cliente.\nUsa 'MEGA Settings' per configurarla.",
                    "Cartella non configurata", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            if (!Directory.Exists(selectedCliente.MegaCartellaLocale))
            {
                MessageBox.Show($"La cartella '{selectedCliente.MegaCartellaLocale}' non esiste.\nVerifica il percorso nelle impostazioni MEGA.",
                    "Cartella non trovata", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            try
            {
                Process.Start("explorer.exe", selectedCliente.MegaCartellaLocale);
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore nell'apertura della cartella: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void btnMega_Click(object sender, EventArgs e)
        {
            if (selectedCliente == null)
            {
                MessageBox.Show("Seleziona un cliente.", "Attenzione",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            if (string.IsNullOrEmpty(selectedCliente.MegaCartellaPubblica))
            {
                MessageBox.Show("Link MEGA non configurato per questo cliente.\nUsa 'MEGA Settings' per configurarlo.",
                    "Link non configurato", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            try
            {
                Process.Start(selectedCliente.MegaCartellaPubblica);
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore nell'apertura del link MEGA: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void btnMegaSettings_Click(object sender, EventArgs e)
        {
            if (selectedCliente == null)
            {
                MessageBox.Show("Seleziona un cliente.", "Attenzione",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            using (MegaSettingsForm megaForm = new MegaSettingsForm(selectedCliente))
            {
                if (megaForm.ShowDialog() == DialogResult.OK)
                {
                    // Salva le modifiche
                    MainForm.SaveEncryptedXml(clienti, clientiFilePath);

                    // Aggiorna lo stato dei pulsanti MEGA
                    UpdateMegaButtonsState();

                    MessageBox.Show("Impostazioni MEGA salvate con successo.", "Salvataggio",
                        MessageBoxButtons.OK, MessageBoxIcon.Information);
                }
            }
        }

        private void UpdateMegaButtonsState()
        {
            if (selectedCliente == null)
            {
                btnCartella.Enabled = false;
                btnMega.Enabled = false;
                btnMegaSettings.Enabled = false;
                return;
            }

            btnCartella.Enabled = !string.IsNullOrEmpty(selectedCliente.MegaCartellaLocale);
            btnMega.Enabled = !string.IsNullOrEmpty(selectedCliente.MegaCartellaPubblica);
            btnMegaSettings.Enabled = true;

            // Cambia il colore dei pulsanti in base allo stato
            if (btnCartella.Enabled)
            {
                btnCartella.BackColor = Color.FromArgb(34, 139, 34); // Verde scuro
                btnCartella.ForeColor = Color.White;
            }
            else
            {
                btnCartella.BackColor = Color.Gray;
                btnCartella.ForeColor = Color.White;
            }

            if (btnMega.Enabled)
            {
                btnMega.BackColor = Color.FromArgb(220, 20, 60); // Crimson
                btnMega.ForeColor = Color.White;
            }
            else
            {
                btnMega.BackColor = Color.Gray;
                btnMega.ForeColor = Color.White;
            }
        }

        private void RiepilogoFuturiWhatsApp()
        {
            try
            {
                if (string.IsNullOrEmpty(selectedCliente.Telefono))
                {
                    MessageBox.Show("Il cliente non ha un numero di telefono.", "Attenzione",
                        MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    return;
                }

                var lezioniNonSvolte = GetUpcomingLessons();
                if (lezioniNonSvolte.Count == 0)
                {
                    MessageBox.Show("Non ci sono lezioni future programmate per questo cliente.", "Informazione",
                        MessageBoxButtons.OK, MessageBoxIcon.Information);
                    return;
                }

                StringBuilder message = CreateWhatsAppMessage(lezioniNonSvolte);
                SendWhatsAppMessage(message.ToString());
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante la preparazione del messaggio WhatsApp: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void RiepilogoFuturiEmail()
        {
            try
            {
                if (string.IsNullOrEmpty(selectedCliente.Email))
                {
                    MessageBox.Show("Il cliente non ha un indirizzo email.", "Attenzione",
                        MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    return;
                }

                var lezioniNonSvolte = GetUpcomingLessons();
                if (lezioniNonSvolte.Count == 0)
                {
                    MessageBox.Show("Non ci sono lezioni future programmate per questo cliente.", "Informazione",
                        MessageBoxButtons.OK, MessageBoxIcon.Information);
                    return;
                }

                StringBuilder message = CreateEmailMessage(lezioniNonSvolte);
                SendEmail("Promemoria lezioni", message.ToString());
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante la preparazione dell'email: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void RiepilogoFuturiPDF()
        {
            try
            {
                var lezioniNonSvolte = GetUpcomingLessons();
                if (lezioniNonSvolte.Count == 0)
                {
                    MessageBox.Show("Non ci sono lezioni future programmate per questo cliente.", "Informazione",
                        MessageBoxButtons.OK, MessageBoxIcon.Information);
                    return;
                }

                CreatePDF(lezioniNonSvolte, "Riepilogo Appuntamenti Futuri");
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante la creazione del PDF: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void RiepilogoStoricoPDF()
        {
            try
            {
                var tutteLeLezioni = GetAllClientLessons();
                if (tutteLeLezioni.Count == 0)
                {
                    MessageBox.Show("Non ci sono lezioni per questo cliente.", "Informazione",
                        MessageBoxButtons.OK, MessageBoxIcon.Information);
                    return;
                }

                CreatePDF(tutteLeLezioni, "Riepilogo Storico Completo");
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante la creazione del PDF: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
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
            return "Scuola di Musica"; // Default se non trova il valore
        }

        private List<Prenotazione> GetAllClientLessons()
        {
            if (selectedCliente == null) return new List<Prenotazione>();

            try
            {
                var prenotazioniList = MainForm.LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);

                if (prenotazioniList == null || prenotazioniList.Items == null)
                    return new List<Prenotazione>();

                return prenotazioniList.Items
                    .Where(p => p.ClienteId == selectedCliente.Id)
                    .OrderBy(p => p.Data)
                    .ThenBy(p => p.OraInizio)
                    .ToList();
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore nel recupero di tutte le lezioni: {ex.Message}");
                return new List<Prenotazione>();
            }
        }

        private List<Models.Acquisto> GetClientAcquisti()
        {
            if (selectedCliente == null) return new List<Models.Acquisto>();

            try
            {
                var acquistiList = MainForm.LoadEncryptedXml<Models.AcquistiList>(acquistiFilePath);
                var pacchettiList = MainForm.LoadEncryptedXml<AcquistiModels.AcquistiPacchettiList>(pacchettiFilePath);

                if (acquistiList == null || acquistiList.Items == null)
                    return new List<Models.Acquisto>();

                var clientAcquisti = acquistiList.Items
                    .Where(a => a.ClienteId == selectedCliente.Id)
                    .ToList();

                // RISOLVI I NOMI DEI PACCHETTI MANCANTI
                if (pacchettiList != null && pacchettiList.Items != null)
                {
                    foreach (var acquisto in clientAcquisti)
                    {
                        // Se il nome del pacchetto è vuoto ma abbiamo l'ID, recuperalo
                        if (string.IsNullOrEmpty(acquisto.NomePacchetto) && acquisto.PacchettoId > 0)
                        {
                            var pacchetto = pacchettiList.Items.FirstOrDefault(p => p.Id == acquisto.PacchettoId);
                            if (pacchetto != null)
                            {
                                acquisto.NomePacchetto = pacchetto.Nome;
                                Debug.WriteLine($"Recuperato nome pacchetto: {pacchetto.Nome} per acquisto ID {acquisto.Id}");
                            }
                            else
                            {
                                Debug.WriteLine($"Pacchetto con ID {acquisto.PacchettoId} non trovato per acquisto ID {acquisto.Id}");
                            }
                        }
                    }
                }

                return clientAcquisti.OrderBy(a => a.DataAcquisto).ToList();
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore nel recupero degli acquisti: {ex.Message}");
                return new List<Models.Acquisto>();
            }
        }

        private void CreatePDF(List<Prenotazione> lezioni, string tipoRiepilogo)
        {
            SaveFileDialog saveFileDialog = new SaveFileDialog();
            saveFileDialog.Filter = "PDF files (*.pdf)|*.pdf";
            saveFileDialog.Title = "Salva riepilogo PDF";
            saveFileDialog.FileName = $"Riepilogo_{selectedCliente.Cognome}_{selectedCliente.Nome}_{DateTime.Now:yyyyMMdd}.pdf";

            if (saveFileDialog.ShowDialog() == DialogResult.OK)
            {
                // Prima di procedere, chiedi all'utente se vuole unificare le tabelle
                bool unificaTabelleScelta = MessageBox.Show(
                    "Vuoi unificare le tabelle di acquisti e lezioni in un'unica tabella ordinata per data?",
                    "Unifica tabelle",
                    MessageBoxButtons.YesNo,
                    MessageBoxIcon.Question) == DialogResult.Yes;

                try
                {
                    Document document = new Document(PageSize.A4, 50, 50, 50, 50);
                    PdfWriter writer = PdfWriter.GetInstance(document, new FileStream(saveFileDialog.FileName, FileMode.Create));
                    document.Open();

                    // Font
                    BaseFont baseFont = BaseFont.CreateFont(BaseFont.HELVETICA, BaseFont.CP1252, BaseFont.NOT_EMBEDDED);
                    iTextSharp.text.Font titleFont = new iTextSharp.text.Font(baseFont, 16, iTextSharp.text.Font.BOLD);
                    iTextSharp.text.Font headerFont = new iTextSharp.text.Font(baseFont, 9, iTextSharp.text.Font.BOLD);
                    iTextSharp.text.Font normalFont = new iTextSharp.text.Font(baseFont, 8, iTextSharp.text.Font.NORMAL);
                    iTextSharp.text.Font smallFont = new iTextSharp.text.Font(baseFont, 7, iTextSharp.text.Font.NORMAL);

                    // Titolo
                    string schoolName = GetSchoolName();
                    Paragraph title = new Paragraph($"{schoolName} - {tipoRiepilogo}", titleFont);
                    title.Alignment = Element.ALIGN_CENTER;
                    document.Add(title);
                    document.Add(new Paragraph(" ")); // Spazio

                    // Dati Cliente (rimosso la data da qui)
                    document.Add(new Paragraph("DATI CLIENTE", headerFont));
                    document.Add(new Paragraph($"Nome: {selectedCliente.Nome} {selectedCliente.Cognome}", normalFont));
                    if (!string.IsNullOrEmpty(selectedCliente.Telefono))
                        document.Add(new Paragraph($"Telefono: {selectedCliente.Telefono}", normalFont));
                    if (!string.IsNullOrEmpty(selectedCliente.Email))
                        document.Add(new Paragraph($"Email: {selectedCliente.Email}", normalFont));
                    if (!string.IsNullOrEmpty(selectedCliente.Indirizzo))
                        document.Add(new Paragraph($"Indirizzo: {selectedCliente.Indirizzo}", normalFont));
                    if (!string.IsNullOrEmpty(selectedCliente.CodiceFiscale))
                        document.Add(new Paragraph($"Codice Fiscale: {selectedCliente.CodiceFiscale}", normalFont));
                    document.Add(new Paragraph(" ")); // Spazio

                    var acquisti = GetClientAcquisti();

                    if (unificaTabelleScelta)
                    {
                        // VERSIONE CON TABELLA UNIFICATA
                        document.Add(new Paragraph("DATI ACQUISTI E LEZIONI", headerFont));
                        document.Add(new Paragraph(" ", new iTextSharp.text.Font(baseFont, 6))); // Spazio piccolo

                        // Creiamo una lista combinata di eventi (acquisti e lezioni)
                        var eventiCombinati = new List<object>();

                        // Aggiungiamo gli acquisti
                        foreach (var acquisto in acquisti)
                        {
                            eventiCombinati.Add(new
                            {
                                Tipo = "Acquisto",
                                Data = acquisto.DataAcquisto,
                                Dettagli = acquisto
                            });
                        }

                        // Aggiungiamo le lezioni
                        foreach (var lezione in lezioni)
                        {
                            eventiCombinati.Add(new
                            {
                                Tipo = "Lezione",
                                Data = lezione.Data,
                                Dettagli = lezione
                            });
                        }

                        // Ordina per data (dalla più vecchia)
                        eventiCombinati = eventiCombinati.OrderBy(e => ((dynamic)e).Data).ToList();

                        // Se ci sono eventi
                        if (eventiCombinati.Count > 0)
                        {
                            PdfPTable tabellaUnificata = new PdfPTable(6); // 6 colonne
                            tabellaUnificata.WidthPercentage = 100;
                            tabellaUnificata.SetWidths(new float[] { 2.5f, 1.2f, 2f, 2f, 2f, 1.5f });

                            // Header tabella unificata
                            tabellaUnificata.AddCell(new PdfPCell(new Phrase("Data e Orario", headerFont)));
                            tabellaUnificata.AddCell(new PdfPCell(new Phrase("Tipo", headerFont)));
                            tabellaUnificata.AddCell(new PdfPCell(new Phrase("Pacchetto", headerFont)));
                            tabellaUnificata.AddCell(new PdfPCell(new Phrase("Importo/Strumento", headerFont)));
                            tabellaUnificata.AddCell(new PdfPCell(new Phrase("Saldo/Insegnante", headerFont)));
                            tabellaUnificata.AddCell(new PdfPCell(new Phrase("Stato", headerFont)));

                            // Popola la tabella unificata
                            foreach (var evento in eventiCombinati)
                            {
                                dynamic e = evento;

                                if (e.Tipo == "Acquisto")
                                {
                                    var acquisto = (dynamic)e.Dettagli;

                                    // Data e orario (solo data per acquisti)
                                    tabellaUnificata.AddCell(new PdfPCell(new Phrase(acquisto.DataAcquisto.ToString("dd/MM/yyyy"), normalFont)));

                                    // Tipo
                                    tabellaUnificata.AddCell(new PdfPCell(new Phrase("Acquisto", normalFont)));

                                    // Pacchetto (nome)
                                    string nomePacchetto = acquisto.NomePacchetto;
                                    if (string.IsNullOrEmpty(nomePacchetto))
                                    {
                                        nomePacchetto = $"Pacchetto ID {acquisto.PacchettoId}";
                                    }
                                    tabellaUnificata.AddCell(new PdfPCell(new Phrase(nomePacchetto, normalFont)));

                                    // Importo
                                    tabellaUnificata.AddCell(new PdfPCell(new Phrase(acquisto.ImportoPagato.ToString("C", CultureInfo.CreateSpecificCulture("it-IT")), normalFont)));

                                    // Stato pagamento
                                    tabellaUnificata.AddCell(new PdfPCell(new Phrase(acquisto.StatoPagamento ?? "N/A", normalFont)));

                                    // Fattura/Stato
                                    tabellaUnificata.AddCell(new PdfPCell(new Phrase(string.IsNullOrEmpty(acquisto.NumeroFattura) ? "-" : acquisto.NumeroFattura, normalFont)));
                                }
                                else // "Lezione"
                                {
                                    var lezione = (dynamic)e.Dettagli;

                                    // Data e orario (combinati)
                                    tabellaUnificata.AddCell(new PdfPCell(new Phrase($"{lezione.Data.ToString("dd/MM/yyyy")} {lezione.OraInizioStr} - {lezione.OraFineStr}", normalFont)));

                                    // Tipo
                                    tabellaUnificata.AddCell(new PdfPCell(new Phrase("Lezione", normalFont)));

                                    // Pacchetto (progressione)
                                    string progressionePacchetto = GetLessonProgression(lezione);
                                    tabellaUnificata.AddCell(new PdfPCell(new Phrase(progressionePacchetto, normalFont)));

                                    // Strumento (invece di importo)
                                    tabellaUnificata.AddCell(new PdfPCell(new Phrase(lezione.Strumento ?? "N/A", normalFont)));

                                    // Insegnante (invece di stato pagamento)
                                    tabellaUnificata.AddCell(new PdfPCell(new Phrase(GetTeacherName(lezione.InsegnanteId), normalFont)));

                                    // Stato
                                    tabellaUnificata.AddCell(new PdfPCell(new Phrase(GetStatusText(lezione.Stato), normalFont)));
                                }
                            }

                            document.Add(tabellaUnificata);
                        }
                        else
                        {
                            document.Add(new Paragraph("Nessun dato trovato.", normalFont));
                        }
                    }
                    else
                    {
                        // VERSIONE CON TABELLE SEPARATE

                        // Tabella Acquisti del cliente
                        document.Add(new Paragraph("ACQUISTI", headerFont));
                        document.Add(new Paragraph(" ", new iTextSharp.text.Font(baseFont, 6))); // Spazio piccolo

                        if (acquisti.Count > 0)
                        {
                            PdfPTable acquistiTable = new PdfPTable(5); // 5 colonne
                            acquistiTable.WidthPercentage = 100;
                            acquistiTable.SetWidths(new float[] { 2f, 3f, 2f, 2f, 1.5f });

                            // Header tabella acquisti
                            acquistiTable.AddCell(new PdfPCell(new Phrase("Data", headerFont)));
                            acquistiTable.AddCell(new PdfPCell(new Phrase("Pacchetto", headerFont)));
                            acquistiTable.AddCell(new PdfPCell(new Phrase("Importo", headerFont)));
                            acquistiTable.AddCell(new PdfPCell(new Phrase("Stato Pagamento", headerFont)));
                            acquistiTable.AddCell(new PdfPCell(new Phrase("Fattura", headerFont)));

                            // Righe della tabella acquisti
                            foreach (var acquisto in acquisti)
                            {
                                acquistiTable.AddCell(new PdfPCell(new Phrase(acquisto.DataAcquisto.ToString("dd/MM/yyyy"), normalFont)));

                                // GESTISCI IL NOME DEL PACCHETTO CON FALLBACK
                                string nomePacchetto = acquisto.NomePacchetto;
                                if (string.IsNullOrEmpty(nomePacchetto))
                                {
                                    nomePacchetto = $"Pacchetto ID {acquisto.PacchettoId}";
                                    Debug.WriteLine($"Fallback per pacchetto: {nomePacchetto}");
                                }

                                acquistiTable.AddCell(new PdfPCell(new Phrase(nomePacchetto, normalFont)));
                                acquistiTable.AddCell(new PdfPCell(new Phrase(acquisto.ImportoPagato.ToString("C", CultureInfo.CreateSpecificCulture("it-IT")), normalFont)));
                                acquistiTable.AddCell(new PdfPCell(new Phrase(acquisto.StatoPagamento ?? "N/A", normalFont)));
                                acquistiTable.AddCell(new PdfPCell(new Phrase(string.IsNullOrEmpty(acquisto.NumeroFattura) ? "-" : acquisto.NumeroFattura, normalFont)));
                            }

                            document.Add(acquistiTable);
                        }
                        else
                        {
                            document.Add(new Paragraph("Nessun acquisto trovato.", normalFont));
                        }

                        document.Add(new Paragraph(" ")); // Spazio

                        // Tabella Lezioni
                        document.Add(new Paragraph("LEZIONI", headerFont));
                        document.Add(new Paragraph(" ", new iTextSharp.text.Font(baseFont, 6))); // Spazio piccolo

                        if (lezioni.Count > 0)
                        {
                            PdfPTable lezioniTable = new PdfPTable(5); // 5 colonne
                            lezioniTable.WidthPercentage = 100;
                            lezioniTable.SetWidths(new float[] { 2.5f, 1.5f, 2f, 2f, 1.5f });

                            // Header tabella lezioni - Data e orario combinati, aggiunta Pacchetto
                            lezioniTable.AddCell(new PdfPCell(new Phrase("Data e Orario", headerFont)));
                            lezioniTable.AddCell(new PdfPCell(new Phrase("Pacchetto", headerFont)));
                            lezioniTable.AddCell(new PdfPCell(new Phrase("Strumento", headerFont)));
                            lezioniTable.AddCell(new PdfPCell(new Phrase("Insegnante", headerFont)));
                            lezioniTable.AddCell(new PdfPCell(new Phrase("Stato", headerFont)));

                            // Righe della tabella lezioni
                            foreach (var lezione in lezioni)
                            {
                                // Data e orario combinati
                                lezioniTable.AddCell(new PdfPCell(new Phrase($"{lezione.Data.ToString("dd/MM/yyyy")} {lezione.OraInizioStr} - {lezione.OraFineStr}", normalFont)));

                                // Informazione sul pacchetto (1/4, 2/4, ecc.)
                                string progressionePacchetto = GetLessonProgression(lezione);
                                lezioniTable.AddCell(new PdfPCell(new Phrase(progressionePacchetto, normalFont)));

                                lezioniTable.AddCell(new PdfPCell(new Phrase(lezione.Strumento ?? "N/A", normalFont)));
                                lezioniTable.AddCell(new PdfPCell(new Phrase(GetTeacherName(lezione.InsegnanteId), normalFont)));
                                lezioniTable.AddCell(new PdfPCell(new Phrase(GetStatusText(lezione.Stato), normalFont)));
                            }

                            document.Add(lezioniTable);
                        }
                        else
                        {
                            document.Add(new Paragraph("Nessuna lezione trovata.", normalFont));
                        }
                    }

                    // AGGIUNGE LA DATA DI GENERAZIONE IN BASSO A SINISTRA
                    // Calcola la posizione Y più bassa possibile (margine inferiore + altezza del testo)
                    float yPosition = document.BottomMargin + smallFont.Size;

                    // Crea il contenuto diretto su canvas per posizionamento assoluto
                    PdfContentByte cb = writer.DirectContent;
                    cb.BeginText();
                    cb.SetFontAndSize(baseFont, 8);
                    cb.SetTextMatrix(document.LeftMargin, yPosition); // Posizione: margine sinistro, in basso
                    cb.ShowText($"Generato il: {DateTime.Now:dd/MM/yyyy HH:mm}");
                    cb.EndText();

                    document.Close();

                    MessageBox.Show($"PDF creato con successo:\n{saveFileDialog.FileName}", "Successo",
                        MessageBoxButtons.OK, MessageBoxIcon.Information);

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

        // Metodo ausiliario per ottenere la progressione della lezione nel pacchetto (1/4, 2/4, ecc.)
        private string GetLessonProgression(Prenotazione lezione)
        {
            try
            {
                // Verifica che l'acquisto ID sia valido (solo con il valore numerico)
                if (lezione.AcquistoId == 0)
                {
                    return "N/A";
                }

                // Carica le prenotazioni
                var prenotazioniList = MainForm.LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);

                // Trova l'acquisto relativo a questa lezione
                var acquisti = MainForm.LoadEncryptedXml<Models.AcquistiList>(acquistiFilePath);
                var acquisto = acquisti.Items.FirstOrDefault(a => a.Id == lezione.AcquistoId);

                if (acquisto == null)
                {
                    return "N/A";
                }

                // Conta quante lezioni di questo acquisto precedono questa lezione (inclusa questa)
                var lezioniAcquisto = prenotazioniList.Items
                    .Where(p => p.AcquistoId == lezione.AcquistoId && p.ClienteId == lezione.ClienteId)
                    .OrderBy(p => p.Data)
                    .ThenBy(p => p.OraInizio)
                    .ToList();

                int progressione = lezioniAcquisto.FindIndex(l => l.Id == lezione.Id) + 1;
                return $"{progressione}/{acquisto.NumeroLezioni}";
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore nel calcolo della progressione: {ex.Message}");
                return "N/A";
            }
        }

        private StringBuilder CreateWhatsAppMessage(List<Prenotazione> lezioni)
        {
            StringBuilder message = new StringBuilder();
            message.AppendLine("Ciao!");
            message.AppendLine("Ecco il promemoria delle tue prossime lezioni:");
            message.AppendLine();

            foreach (var lezione in lezioni)
            {
                string dayName = GetItalianDayName(lezione.Data.DayOfWeek);
                string formattedDate = $"{dayName} {lezione.Data.Day} {GetItalianMonthName(lezione.Data.Month)}";
                message.AppendLine($"• {formattedDate} – ore {lezione.OraInizioStr} – {lezione.Strumento}");
            }

            message.AppendLine();
            message.AppendLine("A presto!");
            return message;
        }

        private StringBuilder CreateEmailMessage(List<Prenotazione> lezioni)
        {
            StringBuilder message = new StringBuilder();
            message.AppendLine("Buongiorno,");
            message.AppendLine();
            message.AppendLine("di seguito il promemoria delle tue prossime lezioni:");
            message.AppendLine();

            foreach (var lezione in lezioni)
            {
                string dayName = GetItalianDayName(lezione.Data.DayOfWeek);
                string formattedDate = $"{dayName} {lezione.Data.Day} {GetItalianMonthName(lezione.Data.Month)}";
                message.AppendLine($"• {formattedDate} – ore {lezione.OraInizioStr} – {lezione.Strumento}");
            }

            message.AppendLine();
            message.AppendLine("Ti invitiamo a comunicarci con anticipo eventuali variazioni o impossibilità a partecipare.");
            message.AppendLine();
            message.AppendLine("Rimaniamo a disposizione per qualsiasi necessità.");
            message.AppendLine("Un cordiale saluto,");
            message.AppendLine();
            message.AppendLine();
            message.AppendLine();
            return message;
        }

        private void SendWhatsAppMessage(string messageText)
        {
            string phoneNumber = new string(selectedCliente.Telefono.Where(c => char.IsDigit(c)).ToArray());
            if (!phoneNumber.StartsWith("39") && phoneNumber.Length == 10)
            {
                phoneNumber = "39" + phoneNumber;
            }

            string encodedMessage = Uri.EscapeDataString(messageText);
            string whatsappUrl = $"https://wa.me/{phoneNumber}?text={encodedMessage}";
            Process.Start(whatsappUrl);
        }

        private void SendEmail(string subject, string body)
        {
            string encodedSubject = Uri.EscapeDataString(subject);
            string encodedBody = Uri.EscapeDataString(body);
            string emailUrl = $"mailto:{selectedCliente.Email}?subject={encodedSubject}&body={encodedBody}";
            Process.Start(emailUrl);
        }

        // Il resto dei metodi rimane uguale...
        private void EditLesson_Click(object sender, EventArgs e)
        {
            try
            {
                if (dgvLezioni.SelectedRows.Count == 0)
                {
                    MessageBox.Show("Seleziona una lezione da modificare.", "Attenzione",
                        MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    return;
                }

                int selectedRowIndex = dgvLezioni.SelectedRows[0].Index;

                if (currentClientLessons == null || selectedRowIndex >= currentClientLessons.Count)
                {
                    MessageBox.Show("Errore nell'identificazione della lezione selezionata.", "Errore",
                        MessageBoxButtons.OK, MessageBoxIcon.Error);
                    return;
                }

                var selectedLesson = currentClientLessons[selectedRowIndex];
                QuickEditLessonForm editForm = new QuickEditLessonForm(selectedLesson, dataPath);

                if (editForm.ShowDialog() == DialogResult.OK)
                {
                    LoadClientLessons(selectedCliente);
                    MessageBox.Show("Lezione modificata con successo.", "Successo",
                        MessageBoxButtons.OK, MessageBoxIcon.Information);
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante la modifica della lezione: {ex.Message}", "Errore",
                    MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void DeleteLesson_Click(object sender, EventArgs e)
        {
            try
            {
                if (dgvLezioni.SelectedRows.Count == 0)
                {
                    MessageBox.Show("Seleziona una lezione da eliminare.", "Attenzione",
                        MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    return;
                }

                int selectedRowIndex = dgvLezioni.SelectedRows[0].Index;

                if (currentClientLessons == null || selectedRowIndex >= currentClientLessons.Count)
                {
                    MessageBox.Show("Errore nell'identificazione della lezione selezionata.", "Errore",
                        MessageBoxButtons.OK, MessageBoxIcon.Error);
                    return;
                }

                var selectedLesson = currentClientLessons[selectedRowIndex];

                var result = MessageBox.Show(
                    $"Sei sicuro di voler eliminare la lezione del {selectedLesson.Data:dd/MM/yyyy} alle {selectedLesson.OraInizioStr}?",
                    "Conferma eliminazione",
                    MessageBoxButtons.YesNo,
                    MessageBoxIcon.Question);

                if (result == DialogResult.Yes)
                {
                    var prenotazioniList = MainForm.LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);

                    if (prenotazioniList != null && prenotazioniList.Items != null)
                    {
                        prenotazioniList.Items.RemoveAll(p => p.Id == selectedLesson.Id);
                        MainForm.SaveEncryptedXml(prenotazioniList, prenotazioniFilePath);
                        LoadClientLessons(selectedCliente);

                        MessageBox.Show("Lezione eliminata con successo.", "Successo",
                            MessageBoxButtons.OK, MessageBoxIcon.Information);
                    }
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante l'eliminazione della lezione: {ex.Message}", "Errore",
                    MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private List<Prenotazione> GetUpcomingLessons()
        {
            if (selectedCliente == null) return new List<Prenotazione>();

            try
            {
                var prenotazioniList = MainForm.LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);

                if (prenotazioniList == null || prenotazioniList.Items == null)
                    return new List<Prenotazione>();

                var today = DateTime.Today;
                var now = DateTime.Now;

                return prenotazioniList.Items
                    .Where(p => p.ClienteId == selectedCliente.Id &&
                                p.Stato != StatoLezione.Svolta &&
                                p.Stato != StatoLezione.Assente &&
                                (p.Data > today ||
                                 (p.Data == today && p.OraInizio.TotalMinutes > now.TimeOfDay.TotalMinutes)))
                    .OrderBy(p => p.Data)
                    .ThenBy(p => p.OraInizio)
                    .ToList();
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore nel recupero delle lezioni future: {ex.Message}");
                return new List<Prenotazione>();
            }
        }

        private string GetItalianDayName(DayOfWeek dayOfWeek)
        {
            switch (dayOfWeek)
            {
                case DayOfWeek.Monday: return "Lunedì";
                case DayOfWeek.Tuesday: return "Martedì";
                case DayOfWeek.Wednesday: return "Mercoledì";
                case DayOfWeek.Thursday: return "Giovedì";
                case DayOfWeek.Friday: return "Venerdì";
                case DayOfWeek.Saturday: return "Sabato";
                case DayOfWeek.Sunday: return "Domenica";
                default: return dayOfWeek.ToString();
            }
        }

        private string GetItalianMonthName(int month)
        {
            string[] months = {
                "", "gennaio", "febbraio", "marzo", "aprile", "maggio", "giugno",
                "luglio", "agosto", "settembre", "ottobre", "novembre", "dicembre"
            };
            return month >= 1 && month <= 12 ? months[month] : month.ToString();
        }

        private void DisableDetailForm()
        {
            txtNome.Text = string.Empty;
            txtCognome.Text = string.Empty;
            txtTelefono.Text = string.Empty;
            txtIndirizzo.Text = string.Empty;
            txtCodiceFiscale.Text = string.Empty;
            txtEmail.Text = string.Empty;
            txtNote.Text = string.Empty;

            txtNome.Enabled = false;
            txtCognome.Enabled = false;
            txtTelefono.Enabled = false;
            txtIndirizzo.Enabled = false;
            txtCodiceFiscale.Enabled = false;
            txtEmail.Enabled = false;
            txtNote.Enabled = false;

            btnSalva.Enabled = false;
            btnElimina.Enabled = false;
            btnWhatsapp.Enabled = false;
            btnEmail.Enabled = false;

            // Controllo null per btnRinnovoVeloce
            if (btnRinnovoVeloce != null)
                btnRinnovoVeloce.Enabled = false;

            btnRiepilogo.Enabled = false;
            btnCartella.Enabled = false;
            btnMega.Enabled = false;
            btnMegaSettings.Enabled = false;

            dgvLezioni.DataSource = null;
            if (dgvAcquisti != null)
                dgvAcquisti.DataSource = null;
            currentClientLessons = null;

            selectedCliente = null;
            isNewClient = false;
        }

        private void EnableDetailForm()
        {
            txtNome.Enabled = true;
            txtCognome.Enabled = true;

            txtTelefono.Enabled = true;
            txtIndirizzo.Enabled = true;
            txtCodiceFiscale.Enabled = true;
            txtEmail.Enabled = true;
            txtNote.Enabled = true;

            btnSalva.Enabled = true;
            btnElimina.Enabled = true;
        }

        private void LoadClientLessons(Cliente cliente)
        {
            if (cliente == null)
                return;

            try
            {
                var prenotazioniList = MainForm.LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);
                var acquistiList = MainForm.LoadEncryptedXml<Models.AcquistiList>(acquistiFilePath);
                var pacchettiList = MainForm.LoadEncryptedXml<AcquistiModels.AcquistiPacchettiList>(pacchettiFilePath);

                if (prenotazioniList == null || prenotazioniList.Items == null)
                {
                    dgvLezioni.DataSource = null;
                    currentClientLessons = null;
                    return;
                }

                var clienteLessons = prenotazioniList.Items.Where(p =>
                                      p.ClienteId == cliente.Id).ToList();

                switch (cmbFiltroLezioni.SelectedIndex)
                {
                    case 1:
                        clienteLessons = clienteLessons.Where(p =>
                            p.Data < DateTime.Today ||
                            (p.Data == DateTime.Today && p.OraInizio.Hours < DateTime.Now.Hour) ||
                            p.Stato == StatoLezione.Svolta ||
                            p.Stato == StatoLezione.Assente).ToList();
                        break;
                    case 2:
                        clienteLessons = clienteLessons.Where(p =>
                            (p.Data > DateTime.Today ||
                            (p.Data == DateTime.Today && p.OraInizio.Hours >= DateTime.Now.Hour)) &&
                            p.Stato != StatoLezione.Svolta &&
                            p.Stato != StatoLezione.Assente).ToList();
                        break;
                }

                currentClientLessons = clienteLessons
                    .OrderBy(p => p.Data)
                    .ThenBy(p => p.OraInizio)
                    .ToList();

                // Dizionari per lookup rapido
                var acquistiDict = new Dictionary<int, Models.Acquisto>();
                if (acquistiList?.Items != null)
                {
                    foreach (var acquisto in acquistiList.Items.Where(a => a.ClienteId == cliente.Id))
                    {
                        acquistiDict[acquisto.Id] = acquisto;
                    }
                }

                // Raggruppa le lezioni per AcquistoId
                var lezioniPerAcquisto = currentClientLessons
                    .Where(l => l.AcquistoId > 0)
                    .GroupBy(l => l.AcquistoId)
                    .ToDictionary(
                        g => g.Key,
                        g => g.OrderBy(l => l.Data).ThenBy(l => l.OraInizio).ToList()
                    );

                // Crea la vista delle lezioni
                var lessonsView = currentClientLessons.Select(p =>
                {
                    string pacchetto = p.PacchettoNome ?? "N/A";

                    // Aggiungi l'informazione sul numero della lezione (1/4, 2/4, ecc.)
                    if (p.AcquistoId > 0 && acquistiDict.TryGetValue(p.AcquistoId, out var acquisto))
                    {
                        if (lezioniPerAcquisto.TryGetValue(p.AcquistoId, out var lezioniGruppo))
                        {
                            // Trova l'indice di questa lezione nel gruppo
                            int indice = lezioniGruppo.FindIndex(l => l.Id == p.Id) + 1;

                            if (indice > 0 && acquisto.NumeroLezioni > 0)
                            {
                                // Se abbiamo sia l'indice che il numero totale, mostriamoli
                                pacchetto = !string.IsNullOrEmpty(p.PacchettoNome)
                                    ? $"{indice}/{acquisto.NumeroLezioni}  |  {p.PacchettoNome}"
                                    : $"{indice}/{acquisto.NumeroLezioni}";
                            }
                        }
                    }

                    return new
                    {
                        Data = p.Data.ToString("dd/MM/yyyy"),
                        Ora = FormatOrariDiretto(p.OraInizioStr, p.OraFineStr),
                        Strumento = p.Strumento ?? "N/A",
                        Insegnante = GetTeacherName(p.InsegnanteId),
                        Stato = GetStatusText(p.Stato),
                        Pacchetto = pacchetto
                    };
                }).ToList();

                dgvLezioni.DataSource = lessonsView;

                if (dgvLezioni.Columns.Count > 0)
                {
                    dgvLezioni.Columns["Data"].Width = 70;
                    dgvLezioni.Columns["Ora"].Width = 80;
                    dgvLezioni.Columns["Strumento"].Width = 80;
                    dgvLezioni.Columns["Insegnante"].Width = 125;
                    dgvLezioni.Columns["Stato"].Width = 90;
                    dgvLezioni.Columns["Pacchetto"].Width = 145;  // Allargato per accomodare il formato X/Y
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore nel caricamento delle lezioni: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                dgvLezioni.DataSource = null;
                currentClientLessons = null;
            }
        }

        private string FormatOrariDiretto(string oraInizioStr, string oraFineStr)
        {
            if (string.IsNullOrEmpty(oraInizioStr) || string.IsNullOrEmpty(oraFineStr))
            {
                return "ORARI VUOTI";
            }

            if (oraInizioStr == "00:00" && oraFineStr == "00:00")
            {
                return "00:00 - 00:00 [STRINGHE ZERO]";
            }

            return $"{oraInizioStr} - {oraFineStr}";
        }

        private string GetTeacherName(int insegnanteId)
        {
            try
            {
                var insegnantiFilePath = Path.Combine(dataPath, "insegnanti.xml");
                var insegnantiList = MainForm.LoadEncryptedXml<InsegnantiList>(insegnantiFilePath);

                if (insegnantiList == null || insegnantiList.Items == null)
                    return "N/A";

                var insegnante = insegnantiList.Items.FirstOrDefault(i => i.Id == insegnanteId);
                return insegnante != null ? $"{insegnante.Cognome} {insegnante.Nome}" : "N/A";
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore nel recupero nome insegnante: {ex.Message}");
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

        // Rest of the existing methods (dgvClienti_CellClick, btnSalva_Click, etc.) remain the same...
        private void dgvClienti_CellClick(object sender, DataGridViewCellEventArgs e)
        {
            if (e.RowIndex >= 0)
            {
                try
                {
                    // Recupera il cliente dalla riga selezionata del DataGridView
                    var bindingList = (BindingList<Cliente>)bindingSource.DataSource;

                    if (e.RowIndex < bindingList.Count)
                    {
                        var cliente = bindingList[e.RowIndex];

                        // Verifica che non sia già il cliente selezionato per evitare refresh inutili
                        if (selectedCliente == null || selectedCliente.Id != cliente.Id)
                        {
                            LoadClientDetails(cliente);
                        }
                    }
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"Errore durante la selezione del cliente: {ex.Message}", "Errore",
                        MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
        }

        private void dgvClienti_CellDoubleClick(object sender, DataGridViewCellEventArgs e)
        {
            if (e.RowIndex >= 0)
            {
                try
                {
                    var bindingList = (BindingList<Cliente>)bindingSource.DataSource;

                    if (e.RowIndex < bindingList.Count)
                    {
                        var cliente = bindingList[e.RowIndex];
                        LoadClientDetails(cliente);

                        // Metti il focus sul primo campo editabile
                        if (isNewClient)
                            txtNome.Focus();
                        else
                            txtTelefono.Focus();
                    }
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"Errore durante la selezione del cliente: {ex.Message}", "Errore",
                        MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
        }

        // IMPORTANTE: Rimuovi o modifica questo metodo per evitare conflitti durante l'ordinamento
        private bool _isUpdatingSelection = false;

        private void dgvClienti_SelectionChanged(object sender, EventArgs e)
        {
            // Evita di processare l'evento durante operazioni di ordinamento o aggiornamento
            if (_isUpdatingSelection || dgvClienti.SelectedRows.Count == 0)
                return;

            try
            {
                int selectedIndex = dgvClienti.SelectedRows[0].Index;
                var bindingList = (BindingList<Cliente>)bindingSource.DataSource;

                if (selectedIndex >= 0 && selectedIndex < bindingList.Count)
                {
                    var cliente = bindingList[selectedIndex];

                    // Carica solo se è un cliente diverso
                    if (selectedCliente == null || selectedCliente.Id != cliente.Id)
                    {
                        LoadClientDetails(cliente);
                    }
                }
            }
            catch (Exception ex)
            {
                // Gestione silenziosa dell'errore per evitare popup continui durante la navigazione
                Debug.WriteLine($"Errore durante il cambio selezione: {ex.Message}");
            }
        }

        private void btnSalva_Click(object sender, EventArgs e)
        {
            if (selectedCliente == null)
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

            if (!string.IsNullOrWhiteSpace(txtCodiceFiscale.Text) && !IsValidCodiceFiscale(txtCodiceFiscale.Text))
            {
                MessageBox.Show("Formato codice fiscale non valido.",
                    "Validazione", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            selectedCliente.Nome = txtNome.Text.Trim();
            selectedCliente.Cognome = txtCognome.Text.Trim();
            selectedCliente.Telefono = txtTelefono.Text.Trim();
            selectedCliente.Indirizzo = txtIndirizzo.Text.Trim();
            selectedCliente.CodiceFiscale = txtCodiceFiscale.Text.Trim().ToUpper();
            selectedCliente.Email = txtEmail.Text.Trim();
            selectedCliente.Note = txtNote.Text.Trim();

            MainForm.SaveEncryptedXml(clienti, clientiFilePath);

            bindingSource.ResetBindings(false);

            isNewClient = false;
            EnableDetailForm();

            MessageBox.Show("Cliente salvato con successo.",
                "Salvataggio", MessageBoxButtons.OK, MessageBoxIcon.Information);
        }

        private void btnNuovo_Click(object sender, EventArgs e)
        {
            var nuovoCliente = new Cliente
            {
                Id = clienti.Items.Count > 0 ? clienti.Items.Max(c => c.Id) + 1 : 1
            };

            clienti.Items.Add(nuovoCliente);

            bindingSource.ResetBindings(false);

            dgvClienti.ClearSelection();
            int lastIndex = dgvClienti.Rows.Count - 1;
            if (lastIndex >= 0)
            {
                dgvClienti.Rows[lastIndex].Selected = true;
                LoadClientDetails(nuovoCliente);
                txtNome.Focus();
            }
        }

        private void btnElimina_Click(object sender, EventArgs e)
        {
            if (selectedCliente == null)
                return;

            var result = MessageBox.Show(
                $"Sei sicuro di voler eliminare il cliente {selectedCliente.Nome} {selectedCliente.Cognome}?",
                "Conferma eliminazione",
                MessageBoxButtons.YesNo,
                MessageBoxIcon.Question);

            if (result == DialogResult.Yes)
            {
                var prenotazioniList = MainForm.LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);
                if (prenotazioniList != null && prenotazioniList.Items != null &&
                    prenotazioniList.Items.Any(p => p.ClienteId == selectedCliente.Id))
                {
                    var confirmResult = MessageBox.Show(
                        "Questo cliente ha delle lezioni associate. L'eliminazione rimuoverà anche tutte le sue lezioni. Continuare?",
                        "Attenzione",
                        MessageBoxButtons.YesNo,
                        MessageBoxIcon.Warning);

                    if (confirmResult == DialogResult.Yes)
                    {
                        prenotazioniList.Items.RemoveAll(p => p.ClienteId == selectedCliente.Id);
                        MainForm.SaveEncryptedXml(prenotazioniList, prenotazioniFilePath);
                    }
                    else
                    {
                        return;
                    }
                }

                clienti.Items.Remove(selectedCliente);
                MainForm.SaveEncryptedXml(clienti, clientiFilePath);

                bindingSource.ResetBindings(false);

                DisableDetailForm();

                MessageBox.Show("Cliente eliminato con successo.",
                    "Eliminazione", MessageBoxButtons.OK, MessageBoxIcon.Information);
            }
        }

        private void btnWhatsapp_Click(object sender, EventArgs e)
        {
            if (selectedCliente != null && !string.IsNullOrEmpty(selectedCliente.Telefono))
            {
                string phoneNumber = new string(selectedCliente.Telefono.Where(c => char.IsDigit(c)).ToArray());

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
            if (selectedCliente != null && !string.IsNullOrEmpty(selectedCliente.Email))
            {
                try
                {
                    Process.Start($"mailto:{selectedCliente.Email}");
                }
                catch (Exception ex)
                {
                    MessageBox.Show($"Impossibile aprire il client di posta: {ex.Message}",
                        "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                }
            }
        }

        private void cmbFiltroLezioni_SelectedIndexChanged(object sender, EventArgs e)
        {
            if (selectedCliente != null)
            {
                LoadClientLessons(selectedCliente);
            }
        }

        private void txtRicerca_TextChanged(object sender, EventArgs e)
        {
            string searchText = txtRicerca.Text.ToLower();
            List<Cliente> filteredList;

            if (string.IsNullOrWhiteSpace(searchText))
            {
                filteredList = clienti.Items;
            }
            else
            {
                filteredList = clienti.Items.Where(c =>
                    (c.Nome != null && c.Nome.ToLower().Contains(searchText)) ||
                    (c.Cognome != null && c.Cognome.ToLower().Contains(searchText)) ||
                    (c.Telefono != null && c.Telefono.Contains(searchText)) || // Contains è sufficiente per i numeri
                    (c.Email != null && c.Email.ToLower().Contains(searchText))
                ).ToList();
            }

            // CORREZIONE: Assegna sempre una nuova BindingList per mantenere la coerenza
            bindingSource.DataSource = new BindingList<Cliente>(filteredList);
            bindingSource.ResetBindings(false);
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

        private bool IsValidCodiceFiscale(string codiceFiscale)
        {
            codiceFiscale = codiceFiscale.Trim().ToUpper();

            if (codiceFiscale.Length != 16)
                return false;

            Regex regex = new Regex(@"^[A-Z]{6}\d{2}[A-Z]\d{2}[A-Z]\d{3}[A-Z]$");
            return regex.IsMatch(codiceFiscale);
        }

        public void RefreshData()
        {
            try
            {
                LoadData();
                bindingSource.ResetBindings(false);

                if (selectedCliente != null)
                {
                    LoadClientLessons(selectedCliente);
                    if (tabControlDati != null && tabControlDati.SelectedTab == tabAcquisti)
                    {
                        LoadClientAcquistiGrid();
                    }
                    UpdateRinnovoVeloceButtonState();
                }
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore nel refresh dei dati: {ex.Message}");
            }
        }
    }

    // Le classi di supporto aggiornate...
    [XmlRoot("Clienti")]
    public class ClientiList
    {
        [XmlElement("Cliente")]
        public List<Cliente> Items { get; set; }

        public ClientiList()
        {
            Items = new List<Cliente>();
        }
    }

    [Serializable]
    public class Cliente
    {
        public int Id { get; set; }
        public string Nome { get; set; }
        public string Cognome { get; set; }
        public string Telefono { get; set; }
        public string Email { get; set; }
        public string Indirizzo { get; set; }
        public string CodiceFiscale { get; set; }
        public string Note { get; set; }
        public string MegaCartellaPubblica { get; set; } // Link MEGA pubblico
        public string MegaCartellaLocale { get; set; }   // Percorso cartella locale MEGA

        [XmlIgnore]
        public string NomeCompleto => $"{Cognome} {Nome}";
    }

    // Le altre classi restano le stesse...
    [XmlRoot("Prenotazioni")]
    public class PrenotazioniList
    {
        [XmlElement("Prenotazione")]
        public List<Prenotazione> Items { get; set; }

        public PrenotazioniList()
        {
            Items = new List<Prenotazione>();
        }
    }

    [Serializable]
    public class Prenotazione
    {
        public int Id { get; set; }
        public DateTime Data { get; set; }

        public string OraInizioStr { get; set; }
        public string OraFineStr { get; set; }

        public int ClienteId { get; set; }
        public int InsegnanteId { get; set; }
        public string Strumento { get; set; }
        public StatoLezione Stato { get; set; }
        public string PacchettoNome { get; set; }
        public int AcquistoId { get; set; }

        [XmlIgnore]
        public TimeSpan OraInizio
        {
            get
            {
                if (string.IsNullOrEmpty(OraInizioStr))
                    return TimeSpan.Zero;

                if (TimeSpan.TryParse(OraInizioStr, out TimeSpan result))
                    return result;

                return TimeSpan.Zero;
            }
            set
            {
                OraInizioStr = value.ToString(@"hh\:mm");
            }
        }

        [XmlIgnore]
        public TimeSpan OraFine
        {
            get
            {
                if (string.IsNullOrEmpty(OraFineStr))
                    return TimeSpan.Zero;

                if (TimeSpan.TryParse(OraFineStr, out TimeSpan result))
                    return result;

                return TimeSpan.Zero;
            }
            set
            {
                OraFineStr = value.ToString(@"hh\:mm");
            }
        }

        public Prenotazione()
        {
            OraInizioStr = "00:00";
            OraFineStr = "00:00";
        }
    }

    [Serializable]
    public enum StatoLezione
    {
        Programmata,
        Svolta,
        Assente,
        Rimandata,
        Riprogrammata
    }

    [XmlRoot("Insegnanti")]
    public class InsegnantiList
    {
        [XmlElement("Insegnante")]
        public List<Insegnante> Items { get; set; }

        public InsegnantiList()
        {
            Items = new List<Insegnante>();
        }
    }

    [Serializable]
    public class Insegnante
    {
        public int Id { get; set; }
        public string Nome { get; set; }
        public string Cognome { get; set; }
        public string Telefono { get; set; }
        public string Email { get; set; }
        public decimal TariffaOraria { get; set; }
        public List<string> Strumenti { get; set; } = new List<string>();

        [XmlIgnore]
        public string NomeCompleto => $"{Cognome} {Nome}";
    }
}