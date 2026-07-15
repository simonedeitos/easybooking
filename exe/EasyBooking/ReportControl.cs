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
using EasyBooking.Models;  // Aggiungi questa direttiva

namespace EasyBooking
{
    public partial class ReportControl : UserControl
    {
        private string dataPath;
        private ClientiList clienti;
        private InsegnantiList insegnanti;
        private PrenotazioniList prenotazioni;
        private Models.AcquistiList acquisti;  // Modifica qui
        // Elimina l'ambiguità eliminando del tutto il nome di tipo PacchettiList
        // e usando direttamente il tipo completo quando necessario
        private List<Models.Pacchetto> pacchettiItems;  // Modifica qui

        // Percorsi dei file XML
        private string clientiFilePath;
        private string insegnantiFilePath;
        private string prenotazioniFilePath;
        private string acquistiFilePath;
        private string pacchettiFilePath;

        public ReportControl(string dataPath)
        {
            InitializeComponent();

            this.dataPath = dataPath;
            clientiFilePath = Path.Combine(dataPath, "clienti.xml");
            insegnantiFilePath = Path.Combine(dataPath, "insegnanti.xml");
            prenotazioniFilePath = Path.Combine(dataPath, "prenotazioni.xml");
            acquistiFilePath = Path.Combine(dataPath, "acquisti.xml");
            pacchettiFilePath = Path.Combine(dataPath, "pacchetti.xml");

            // Imposta le date di default per il periodo
            dtpDataInizio.Value = new DateTime(DateTime.Today.Year, DateTime.Today.Month, 1);
            dtpDataFine.Value = DateTime.Today;

            LoadData();
            InitializeComboBoxes();

            // Collega gli eventi
            btnGenera.Click += btnGenera_Click;
            cmbTipoReport.SelectedIndexChanged += cmbTipoReport_SelectedIndexChanged;
            dtpDataInizio.ValueChanged += dtpDataInizio_ValueChanged;
            dtpDataFine.ValueChanged += dtpDataFine_ValueChanged;
        }

        private void LoadData()
        {
            // Carica i dati dei clienti
            clienti = MainForm.LoadEncryptedXml<ClientiList>(clientiFilePath);
            if (clienti == null || clienti.Items == null) clienti = new ClientiList();

            // Carica i dati degli insegnanti
            insegnanti = MainForm.LoadEncryptedXml<InsegnantiList>(insegnantiFilePath);
            if (insegnanti == null || insegnanti.Items == null) insegnanti = new InsegnantiList();

            // Carica i dati delle prenotazioni
            prenotazioni = MainForm.LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);
            if (prenotazioni == null || prenotazioni.Items == null) prenotazioni = new PrenotazioniList();

            // Carica i dati degli acquisti
            acquisti = MainForm.LoadEncryptedXml<Models.AcquistiList>(acquistiFilePath);  // Modifica qui
            if (acquisti == null || acquisti.Items == null) acquisti = new Models.AcquistiList();  // Modifica qui

            // Carica i dati dei pacchetti - evitando completamente di usare il tipo PacchettiList
            var pacchettiTemp = MainForm.LoadEncryptedXml<Models.PacchettiList>(pacchettiFilePath);  // Modifica qui
            pacchettiItems = pacchettiTemp?.Items ?? new List<Models.Pacchetto>();  // Modifica qui
        }

        // Corretto il metodo per utilizzare i nomi corretti dal designer
        private void InitializeComboBoxes()
        {
            try
            {
                // Popola il ComboBox dei clienti
                cmbCliente.Items.Clear();
                cmbCliente.Items.Add(new ReportComboBoxItem { Value = -1, Text = "Tutti i clienti" });

                foreach (var cliente in clienti.Items.OrderBy(c => c.Cognome).ThenBy(c => c.Nome))
                {
                    cmbCliente.Items.Add(new ReportComboBoxItem
                    {
                        Value = cliente.Id,
                        Text = $"{cliente.Cognome} {cliente.Nome}"
                    });
                }

                cmbCliente.DisplayMember = "Text";
                cmbCliente.ValueMember = "Value";
                cmbCliente.SelectedIndex = 0;

                // Popola il ComboBox degli insegnanti
                cmbInsegnante.Items.Clear();
                cmbInsegnante.Items.Add(new ReportComboBoxItem { Value = -1, Text = "Tutti gli insegnanti" });

                foreach (var insegnante in insegnanti.Items.OrderBy(i => i.Cognome).ThenBy(i => i.Nome))
                {
                    cmbInsegnante.Items.Add(new ReportComboBoxItem
                    {
                        Value = insegnante.Id,
                        Text = $"{insegnante.Cognome} {insegnante.Nome}"
                    });
                }

                cmbInsegnante.DisplayMember = "Text";
                cmbInsegnante.ValueMember = "Value";
                cmbInsegnante.SelectedIndex = 0;

                // Popola il ComboBox dei tipi di report
                cmbTipoReport.Items.Clear();
                cmbTipoReport.Items.Add("Report Lezioni");
                cmbTipoReport.Items.Add("Report Incassi");
                cmbTipoReport.Items.Add("Report Presenze");
                cmbTipoReport.SelectedIndex = 0;

                // Popola il ComboBox del formato
                cmbFormato.Items.Clear();
                cmbFormato.Items.Add("Tabellare");
                cmbFormato.Items.Add("Grafico");
                cmbFormato.SelectedIndex = 0;

                // Popola il ComboBox del raggruppamento
                cmbRaggruppamento.Items.Clear();
                cmbRaggruppamento.Items.Add("Nessuno");
                cmbRaggruppamento.Items.Add("Per cliente");
                cmbRaggruppamento.Items.Add("Per insegnante");
                cmbRaggruppamento.Items.Add("Per mese");
                cmbRaggruppamento.SelectedIndex = 0;
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante l'inizializzazione dei controlli: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private string GetPacchettoNome(int pacchettoId)
        {
            var pacchetto = pacchettiItems.FirstOrDefault(p => p.Id == pacchettoId);
            return pacchetto != null ? pacchetto.Nome : "N/A";
        }

        // Metodi per l'aggiornamento dei report in base ai filtri
        private void btnGenera_Click(object sender, EventArgs e)
        {
            try
            {
                // Aggiorna il report in base ai filtri selezionati
                UpdateReport();
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante la generazione del report: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void UpdateReport()
        {
            try
            {
                // Ottieni i filtri selezionati
                DateTime dataInizio = dtpDataInizio.Value.Date;
                DateTime dataFine = dtpDataFine.Value.Date.AddDays(1).AddSeconds(-1); // Fine della giornata

                int clienteId = cmbCliente.SelectedItem is ReportComboBoxItem clienteItem ?
                    Convert.ToInt32(clienteItem.Value) : -1;

                int insegnanteId = cmbInsegnante.SelectedItem is ReportComboBoxItem insegnanteItem ?
                    Convert.ToInt32(insegnanteItem.Value) : -1;

                string tipoReport = cmbTipoReport.SelectedItem.ToString();

                // Aggiorna il titolo del report
                lblTitoloReport.Text = $"{tipoReport} - Dal {dataInizio:dd/MM/yyyy} al {dataFine:dd/MM/yyyy}";

                // Genera il report appropriato
                switch (tipoReport)
                {
                    case "Report Lezioni":
                        GenerateLezioniReport(dataInizio, dataFine, clienteId, insegnanteId);
                        break;
                    case "Report Incassi":
                        GenerateIncassiReport(dataInizio, dataFine, clienteId);
                        break;
                    case "Report Presenze":
                        GeneratePresenzeReport(dataInizio, dataFine, insegnanteId);
                        break;
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante l'aggiornamento del report: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void GenerateLezioniReport(DateTime dataInizio, DateTime dataFine, int clienteId, int insegnanteId)
        {
            // Filtra le lezioni in base ai parametri
            var lezioniQuery = prenotazioni.Items.Where(p => p.Data >= dataInizio && p.Data <= dataFine);

            if (clienteId != -1)
                lezioniQuery = lezioniQuery.Where(p => p.ClienteId == clienteId);

            if (insegnanteId != -1)
                lezioniQuery = lezioniQuery.Where(p => p.InsegnanteId == insegnanteId);

            var lezioni = lezioniQuery.OrderBy(p => p.Data).ThenBy(p => p.OraInizio).ToList();

            // Visualizza i risultati
            dgvReport.Rows.Clear();
            dgvReport.Columns.Clear();

            // Configura le colonne
            dgvReport.Columns.Add("Data", "Data");
            dgvReport.Columns.Add("Orario", "Orario");
            dgvReport.Columns.Add("Cliente", "Cliente");
            dgvReport.Columns.Add("Insegnante", "Insegnante");
            dgvReport.Columns.Add("Strumento", "Strumento");
            dgvReport.Columns.Add("Stato", "Stato");

            // Popola la griglia
            foreach (var lezione in lezioni)
            {
                var cliente = clienti.Items.FirstOrDefault(c => c.Id == lezione.ClienteId);
                string nomeCliente = cliente != null ? $"{cliente.Cognome} {cliente.Nome}" : "N/A";

                var insegnante = insegnanti.Items.FirstOrDefault(i => i.Id == lezione.InsegnanteId);
                string nomeInsegnante = insegnante != null ? $"{insegnante.Cognome} {insegnante.Nome}" : "N/A";

                dgvReport.Rows.Add(
                    lezione.Data.ToString("dd/MM/yyyy"),
                    $"{lezione.OraInizio:hh\\:mm} - {lezione.OraFine:hh\\:mm}",
                    nomeCliente,
                    nomeInsegnante,
                    lezione.Strumento,
                    lezione.Stato.ToString()
                );
            }

            // Aggiorna il riepilogo (verifica se lblRiepilogo esiste nel designer)
            if (lblTitoloReport != null)
            {
                lblTitoloReport.Text += $" - Totale lezioni: {lezioni.Count}";
            }
        }

        private void GenerateIncassiReport(DateTime dataInizio, DateTime dataFine, int clienteId)
        {
            // Filtra gli acquisti in base ai parametri
            var acquistiQuery = acquisti.Items.Where(a => a.DataAcquisto >= dataInizio && a.DataAcquisto <= dataFine);

            if (clienteId != -1)
                acquistiQuery = acquistiQuery.Where(a => a.ClienteId == clienteId);

            var acquistiList = acquistiQuery.OrderBy(a => a.DataAcquisto).ToList();

            // Visualizza i risultati
            dgvReport.Rows.Clear();
            dgvReport.Columns.Clear();

            // Configura le colonne
            dgvReport.Columns.Add("Data", "Data");
            dgvReport.Columns.Add("Cliente", "Cliente");
            dgvReport.Columns.Add("Pacchetto", "Pacchetto");
            dgvReport.Columns.Add("Importo", "Importo");
            dgvReport.Columns.Add("Stato", "Stato");

            // Popola la griglia
            decimal totaleIncassi = 0;
            foreach (var acquisto in acquistiList)
            {
                var cliente = clienti.Items.FirstOrDefault(c => c.Id == acquisto.ClienteId);
                string nomeCliente = cliente != null ? $"{cliente.Cognome} {cliente.Nome}" : "N/A";

                dgvReport.Rows.Add(
                    acquisto.DataAcquisto.ToString("dd/MM/yyyy"),
                    nomeCliente,
                    acquisto.NomePacchetto,
                    acquisto.ImportoPagato.ToString("C"),
                    acquisto.StatoPagamento
                );

                totaleIncassi += acquisto.ImportoPagato;
            }

            // Aggiorna il riepilogo
            if (lblTitoloReport != null)
            {
                lblTitoloReport.Text += $" - Totale incassi: {totaleIncassi:C}";
            }
        }

        private void GeneratePresenzeReport(DateTime dataInizio, DateTime dataFine, int insegnanteId)
        {
            // Filtra le lezioni in base ai parametri per verificare le presenze
            var lezioniQuery = prenotazioni.Items.Where(p => p.Data >= dataInizio && p.Data <= dataFine);

            if (insegnanteId != -1)
                lezioniQuery = lezioniQuery.Where(p => p.InsegnanteId == insegnanteId);

            var lezioni = lezioniQuery.ToList();

            // Calcola le statistiche per insegnante
            var statsPerInsegnante = new Dictionary<int, (int Totali, int Svolte, int Assenti, int Rimandate)>();

            foreach (var lezione in lezioni)
            {
                if (!statsPerInsegnante.ContainsKey(lezione.InsegnanteId))
                {
                    statsPerInsegnante[lezione.InsegnanteId] = (0, 0, 0, 0);
                }

                var currentStats = statsPerInsegnante[lezione.InsegnanteId];
                var newStats = currentStats;

                newStats.Totali++;

                switch (lezione.Stato)
                {
                    case StatoLezione.Svolta:
                        newStats.Svolte++;
                        break;
                    case StatoLezione.Assente:
                        newStats.Assenti++;
                        break;
                    case StatoLezione.Rimandata:
                        newStats.Rimandate++;
                        break;
                }

                statsPerInsegnante[lezione.InsegnanteId] = newStats;
            }

            // Visualizza i risultati
            dgvReport.Rows.Clear();
            dgvReport.Columns.Clear();

            // Configura le colonne
            dgvReport.Columns.Add("Insegnante", "Insegnante");
            dgvReport.Columns.Add("Totali", "Totali");
            dgvReport.Columns.Add("Svolte", "Svolte");
            dgvReport.Columns.Add("Assenti", "Assenti");
            dgvReport.Columns.Add("Rimandate", "Rimandate");
            dgvReport.Columns.Add("PercentualePresenze", "% Presenze");

            // Popola la griglia
            int totaleSvolte = 0;
            int totaleSomma = 0;

            foreach (var stats in statsPerInsegnante)
            {
                var insegnante = insegnanti.Items.FirstOrDefault(i => i.Id == stats.Key);
                string nomeInsegnante = insegnante != null ? $"{insegnante.Cognome} {insegnante.Nome}" : "N/A";

                var (totali, svolte, assenti, rimandate) = stats.Value;
                double percentuale = totali > 0 ? (svolte * 100.0 / totali) : 0;

                dgvReport.Rows.Add(
                    nomeInsegnante,
                    totali,
                    svolte,
                    assenti,
                    rimandate,
                    $"{percentuale:F1}%"
                );

                totaleSvolte += svolte;
                totaleSomma += totali;
            }

            // Aggiorna il riepilogo
            double percentualeTotale = totaleSomma > 0 ? (totaleSvolte * 100.0 / totaleSomma) : 0;
            if (lblTitoloReport != null)
            {
                lblTitoloReport.Text += $" - Presenze totali: {percentualeTotale:F1}% ({totaleSvolte}/{totaleSomma})";
            }
        }

        // Evento per il cambio del tipo di report
        private void cmbTipoReport_SelectedIndexChanged(object sender, EventArgs e)
        {
            // Aggiorna l'interfaccia in base al tipo di report selezionato
            string tipoReport = cmbTipoReport.SelectedItem.ToString();

            switch (tipoReport)
            {
                case "Report Lezioni":
                    cmbCliente.Enabled = true;
                    cmbInsegnante.Enabled = true;
                    break;
                case "Report Incassi":
                    cmbCliente.Enabled = true;
                    cmbInsegnante.Enabled = false;
                    break;
                case "Report Presenze":
                    cmbCliente.Enabled = false;
                    cmbInsegnante.Enabled = true;
                    break;
            }

            // Aggiorna il report con il nuovo tipo
            UpdateReport();
        }

        // Evento per il cambio del periodo
        private void dtpDataInizio_ValueChanged(object sender, EventArgs e)
        {
            if (dtpDataInizio.Value > dtpDataFine.Value)
            {
                dtpDataFine.Value = dtpDataInizio.Value;
            }
        }

        private void dtpDataFine_ValueChanged(object sender, EventArgs e)
        {
            if (dtpDataFine.Value < dtpDataInizio.Value)
            {
                dtpDataInizio.Value = dtpDataFine.Value;
            }
        }
    }

    // Utilizziamo un nome diverso per evitare ambiguità con altre classi ComboBoxItem
    public class ReportComboBoxItem
    {
        public object Value { get; set; }
        public string Text { get; set; }

        public override string ToString()
        {
            return Text;
        }
    }
}