using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.IO;
using System.Linq;
using System.Windows.Forms;
using System.Xml.Serialization;

namespace EasyBooking
{
    public partial class LezioneForm : Form
    {
        private string dataPath;
        private Prenotazione prenotazione;
        private ClientiList clienti;
        private InsegnantiList insegnanti;
        private StrumentiList strumenti;
        private PrenotazioniList prenotazioni;
        private bool isNew;

        // Percorsi dei file XML
        private string prenotazioniFilePath;
        private string clientiFilePath;
        private string insegnantiFilePath;
        private string strumentiFilePath;

        public LezioneForm(string dataPath, Prenotazione prenotazione, bool isNew)
        {
            InitializeComponent();

            this.dataPath = dataPath;
            this.prenotazione = prenotazione;
            this.isNew = isNew;

            prenotazioniFilePath = Path.Combine(dataPath, "prenotazioni.xml");
            clientiFilePath = Path.Combine(dataPath, "clienti.xml");
            insegnantiFilePath = Path.Combine(dataPath, "insegnanti.xml");
            strumentiFilePath = Path.Combine(dataPath, "strumenti.xml");

            LoadData();
            PopulateComboBoxes();
            SetupForm();
        }

        private void LoadData()
        {
            // Carica i dati delle prenotazioni dal file XML
            prenotazioni = MainForm.LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);

            if (prenotazioni == null || prenotazioni.Items == null)
            {
                prenotazioni = new PrenotazioniList();
            }

            // Carica i dati dei clienti dal file XML
            clienti = MainForm.LoadEncryptedXml<ClientiList>(clientiFilePath);

            if (clienti == null || clienti.Items == null)
            {
                clienti = new ClientiList();
            }

            // Carica i dati degli insegnanti dal file XML
            insegnanti = MainForm.LoadEncryptedXml<InsegnantiList>(insegnantiFilePath);

            if (insegnanti == null || insegnanti.Items == null)
            {
                insegnanti = new InsegnantiList();
            }

            // Carica i dati degli strumenti dal file XML
            strumenti = MainForm.LoadEncryptedXml<StrumentiList>(strumentiFilePath);

            if (strumenti == null || strumenti.Items == null)
            {
                strumenti = new StrumentiList();
            }
        }

        private void PopulateComboBoxes()
        {
            // Popola la combo box dei clienti
            cmbCliente.Items.Clear();

            foreach (var cliente in clienti.Items.OrderBy(c => c.Cognome).ThenBy(c => c.Nome))
            {
                cmbCliente.Items.Add(new LezioneComboBoxItem { Value = cliente.Id, Text = $"{cliente.Cognome} {cliente.Nome}" });
            }

            cmbCliente.DisplayMember = "Text";
            cmbCliente.ValueMember = "Value";

            // Popola la combo box degli insegnanti
            cmbInsegnante.Items.Clear();

            foreach (var insegnante in insegnanti.Items.OrderBy(i => i.Cognome).ThenBy(i => i.Nome))
            {
                cmbInsegnante.Items.Add(new LezioneComboBoxItem { Value = insegnante.Id, Text = $"{insegnante.Cognome} {insegnante.Nome}" });
            }

            cmbInsegnante.DisplayMember = "Text";
            cmbInsegnante.ValueMember = "Value";

            // Popola la combo box degli strumenti
            cmbStrumento.Items.Clear();

            foreach (var strumento in strumenti.Items.OrderBy(s => s.Nome))
            {
                cmbStrumento.Items.Add(strumento.Nome);
            }

            // Popola la combo box dello stato
            cmbStato.Items.Clear();
            cmbStato.Items.AddRange(new string[] {
                "Programmata", "Svolta", "Assente", "Rimandata", "Riprogrammata"
            });
        }

        private void SetupForm()
        {
            // Imposta i valori nei controlli
            dtpData.Value = prenotazione.Data;
            dtpOraInizio.Value = DateTime.Today.Add(prenotazione.OraInizio);
            dtpOraFine.Value = DateTime.Today.Add(prenotazione.OraFine);

            // Seleziona il cliente
            if (prenotazione.ClienteId > 0)
            {
                for (int i = 0; i < cmbCliente.Items.Count; i++)
                {
                    LezioneComboBoxItem item = (LezioneComboBoxItem)cmbCliente.Items[i];
                    if ((int)item.Value == prenotazione.ClienteId)
                    {
                        cmbCliente.SelectedIndex = i;
                        break;
                    }
                }
            }

            // Seleziona l'insegnante
            if (prenotazione.InsegnanteId > 0)
            {
                for (int i = 0; i < cmbInsegnante.Items.Count; i++)
                {
                    LezioneComboBoxItem item = (LezioneComboBoxItem)cmbInsegnante.Items[i];
                    if ((int)item.Value == prenotazione.InsegnanteId)
                    {
                        cmbInsegnante.SelectedIndex = i;
                        break;
                    }
                }
            }

            // Seleziona lo strumento
            if (!string.IsNullOrEmpty(prenotazione.Strumento))
            {
                cmbStrumento.SelectedItem = prenotazione.Strumento;
            }

            // Seleziona lo stato
            switch (prenotazione.Stato)
            {
                case StatoLezione.Programmata:
                    cmbStato.SelectedIndex = 0;
                    break;
                case StatoLezione.Svolta:
                    cmbStato.SelectedIndex = 1;
                    break;
                case StatoLezione.Assente:
                    cmbStato.SelectedIndex = 2;
                    break;
                case StatoLezione.Rimandata:
                    cmbStato.SelectedIndex = 3;
                    break;
                case StatoLezione.Riprogrammata:
                    cmbStato.SelectedIndex = 4;
                    break;
                default:
                    cmbStato.SelectedIndex = 0;
                    break;
            }

            // Imposta gli oggetti per acquisto
            nudAcquistoId.Value = prenotazione.AcquistoId;

            // Imposta il titolo della form in base all'operazione
            this.Text = isNew ? "Nuova Lezione" : "Modifica Lezione";

            // Se è una nuova lezione, il pulsante Elimina è disabilitato
            btnElimina.Enabled = !isNew;
        }

        private void btnSalva_Click(object sender, EventArgs e)
        {
            // Verifica che tutti i campi obbligatori siano stati compilati
            if (cmbCliente.SelectedItem == null)
            {
                MessageBox.Show("Seleziona un cliente.", "Dato mancante", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            if (cmbInsegnante.SelectedItem == null)
            {
                MessageBox.Show("Seleziona un insegnante.", "Dato mancante", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            if (cmbStrumento.SelectedItem == null)
            {
                MessageBox.Show("Seleziona uno strumento.", "Dato mancante", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            if (cmbStato.SelectedItem == null)
            {
                MessageBox.Show("Seleziona uno stato.", "Dato mancante", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            // Verifica che l'ora di inizio sia precedente all'ora di fine
            TimeSpan oraInizio = dtpOraInizio.Value.TimeOfDay;
            TimeSpan oraFine = dtpOraFine.Value.TimeOfDay;

            if (oraInizio >= oraFine)
            {
                MessageBox.Show("L'ora di inizio deve essere precedente all'ora di fine.",
                    "Orari non validi", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            // Salva i dati nella prenotazione
            prenotazione.Data = dtpData.Value.Date;
            prenotazione.OraInizio = oraInizio;
            prenotazione.OraFine = oraFine;

            // Salva il cliente selezionato
            LezioneComboBoxItem clienteItem = (LezioneComboBoxItem)cmbCliente.SelectedItem;
            prenotazione.ClienteId = (int)clienteItem.Value;

            // Salva l'insegnante selezionato
            LezioneComboBoxItem insegnanteItem = (LezioneComboBoxItem)cmbInsegnante.SelectedItem;
            prenotazione.InsegnanteId = (int)insegnanteItem.Value;

            // Salva lo strumento selezionato
            prenotazione.Strumento = cmbStrumento.SelectedItem.ToString();

            // Salva lo stato selezionato
            switch (cmbStato.SelectedIndex)
            {
                case 0:
                    prenotazione.Stato = StatoLezione.Programmata;
                    break;
                case 1:
                    prenotazione.Stato = StatoLezione.Svolta;
                    break;
                case 2:
                    prenotazione.Stato = StatoLezione.Assente;
                    break;
                case 3:
                    prenotazione.Stato = StatoLezione.Rimandata;
                    break;
                case 4:
                    prenotazione.Stato = StatoLezione.Riprogrammata;
                    break;
            }

            // Salva l'ID dell'acquisto
            prenotazione.AcquistoId = (int)nudAcquistoId.Value;

            // Se è una nuova prenotazione, aggiungila alla lista
            if (isNew)
            {
                prenotazioni.Items.Add(prenotazione);
            }

            // Salva tutte le prenotazioni nel file XML
            MainForm.SaveEncryptedXml(prenotazioni, prenotazioniFilePath);

            // Chiudi la form con risultato OK
            DialogResult = DialogResult.OK;
            Close();
        }

        private void btnAnnulla_Click(object sender, EventArgs e)
        {
            // Chiudi la form con risultato Cancel
            DialogResult = DialogResult.Cancel;
            Close();
        }

        private void btnElimina_Click(object sender, EventArgs e)
        {
            // Chiedi conferma prima di eliminare
            DialogResult result = MessageBox.Show(
                "Sei sicuro di voler eliminare questa lezione?",
                "Conferma eliminazione",
                MessageBoxButtons.YesNo,
                MessageBoxIcon.Question);

            if (result == DialogResult.Yes)
            {
                // Rimuovi la prenotazione dalla lista
                prenotazioni.Items.RemoveAll(p => p.Id == prenotazione.Id);

                // Salva le prenotazioni nel file XML
                MainForm.SaveEncryptedXml(prenotazioni, prenotazioniFilePath);

                // Chiudi la form con risultato OK
                DialogResult = DialogResult.OK;
                Close();
            }
        }

        private void cmbInsegnante_SelectedIndexChanged(object sender, EventArgs e)
        {
            UpdateStrumentiForInsegnante();
        }

        private void UpdateStrumentiForInsegnante()
        {
            if (cmbInsegnante.SelectedItem is LezioneComboBoxItem selectedItem)
            {
                int insegnanteId = (int)selectedItem.Value;
                var insegnante = insegnanti.Items.FirstOrDefault(i => i.Id == insegnanteId);

                if (insegnante != null && insegnante.Strumenti != null && insegnante.Strumenti.Count > 0)
                {
                    // Salva lo strumento corrente
                    string currentStrumento = cmbStrumento.SelectedItem?.ToString();

                    // Aggiorna la combobox con solo gli strumenti dell'insegnante
                    cmbStrumento.Items.Clear();
                    foreach (string strumento in insegnante.Strumenti.OrderBy(s => s))
                    {
                        cmbStrumento.Items.Add(strumento);
                    }

                    // Riseleziona lo strumento precedente se è tra quelli disponibili
                    if (!string.IsNullOrEmpty(currentStrumento) && cmbStrumento.Items.Contains(currentStrumento))
                    {
                        cmbStrumento.SelectedItem = currentStrumento;
                    }
                    else if (cmbStrumento.Items.Count > 0)
                    {
                        cmbStrumento.SelectedIndex = 0;
                    }
                }
            }
        }

        private void btnCercaAcquisto_Click(object sender, EventArgs e)
        {
            // Apri un form per cercare acquisti associati al cliente selezionato
            if (cmbCliente.SelectedItem is LezioneComboBoxItem selectedItem)
            {
                int clienteId = (int)selectedItem.Value;

                using (AcquistoSelectorForm selectorForm = new AcquistoSelectorForm(dataPath, clienteId))
                {
                    if (selectorForm.ShowDialog() == DialogResult.OK && selectorForm.SelectedAcquisto != null)
                    {
                        // Imposta l'ID dell'acquisto selezionato
                        nudAcquistoId.Value = selectorForm.SelectedAcquisto.Id;
                    }
                }
            }
            else
            {
                MessageBox.Show("Seleziona prima un cliente per cercare i suoi acquisti.",
                    "Cliente non selezionato", MessageBoxButtons.OK, MessageBoxIcon.Information);
            }
        }
    }

    public class LezioneComboBoxItem
    {
        public object Value { get; set; }
        public string Text { get; set; }

        public override string ToString()
        {
            return Text;
        }
    }
}