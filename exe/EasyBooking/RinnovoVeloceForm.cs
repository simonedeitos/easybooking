using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Linq;
using System.Windows.Forms;
using System.IO;
using System.Diagnostics;
using EasyBooking.Models;
using EasyBooking.AcquistiModels;

namespace EasyBooking
{
    public partial class RinnovoVeloceForm : Form
    {
        private string dataPath;
        private Cliente cliente;
        private Models.Acquisto ultimoAcquisto;
        private List<Prenotazione> ultimoGruppoLezioni;
        private AcquistiModels.AcquistiPacchettiList pacchetti;
        private Panel panelAltreLezioni;
        private List<Label> altreLezioniLabels;

        public Models.Acquisto NuovoAcquisto { get; private set; }
        public bool PianificaSubito { get; private set; }

        public RinnovoVeloceForm(string dataPath, Cliente cliente, Models.Acquisto ultimoAcquisto, List<Prenotazione> ultimoGruppoLezioni)
        {
            this.dataPath = dataPath;
            this.cliente = cliente;
            this.ultimoAcquisto = ultimoAcquisto;
            this.ultimoGruppoLezioni = ultimoGruppoLezioni;

            // Carica la lista dei pacchetti per verificare i dettagli
            string pacchettiFilePath = Path.Combine(dataPath, "pacchetti.xml");
            this.pacchetti = MainForm.LoadEncryptedXml<AcquistiModels.AcquistiPacchettiList>(pacchettiFilePath);

            InitializeComponent();
            ConfigureForm();
        }

        private void ConfigureForm()
        {
            try
            {
                // Imposta il titolo del form con il nome del cliente
                this.Text = $"Rinnovo Veloce - {cliente.Cognome} {cliente.Nome}";

                // Aggiorna il titolo del cliente
                lblClienteNome.Text = $"{cliente.Cognome} {cliente.Nome}";

                if (ultimoAcquisto != null)
                {
                    // Precompila i campi con i dati dell'ultimo acquisto
                    txtImporto.Text = ultimoAcquisto.ImportoPagato.ToString("F2");

                    // CORREGGI IL NOME DEL PACCHETTO
                    string nomePacchetto = ultimoAcquisto.NomePacchetto;

                    // Se il nome del pacchetto è vuoto, prova a recuperarlo dalla lista dei pacchetti
                    if (string.IsNullOrEmpty(nomePacchetto) && ultimoAcquisto.PacchettoId > 0 && pacchetti?.Items != null)
                    {
                        var pacchetto = pacchetti.Items.FirstOrDefault(p => p.Id == ultimoAcquisto.PacchettoId);
                        if (pacchetto != null)
                        {
                            nomePacchetto = pacchetto.Nome;
                            Debug.WriteLine($"Nome pacchetto recuperato: {nomePacchetto}");
                        }
                    }

                    txtNomePacchetto.Text = nomePacchetto ?? "Pacchetto non trovato";
                    txtNomePacchetto.ReadOnly = true; // Non modificabile
                    txtNomePacchetto.BackColor = SystemColors.Control; // Colore di sfondo per indicare che è read-only

                    nudNumeroLezioni.Value = ultimoAcquisto.NumeroLezioni;
                    nudNumeroLezioni.Enabled = false; // Non modificabile

                    // Imposta "Da pagare" come stato pagamento predefinito
                    cmbStatoPagamento.SelectedItem = "Da pagare";

                    // Trova il pacchetto corrispondente per verificare i dettagli
                    if (pacchetti?.Items != null && ultimoAcquisto.PacchettoId > 0)
                    {
                        var pacchetto = pacchetti.Items.FirstOrDefault(p => p.Id == ultimoAcquisto.PacchettoId);
                        if (pacchetto != null && pacchetto.NumeroLezioni > 0)
                        {
                            nudNumeroLezioni.Value = pacchetto.NumeroLezioni;
                        }
                    }

                    // Aggiorna i dettagli dell'ultimo acquisto
                    lblUltimoAcquisto.Text = $"Ultimo acquisto: {ultimoAcquisto.DataAcquisto:dd/MM/yyyy} - {nomePacchetto}";
                }

                if (ultimoGruppoLezioni != null && ultimoGruppoLezioni.Count > 0)
                {
                    // Trova l'ultima lezione del gruppo
                    var ultimaLezione = ultimoGruppoLezioni
                        .OrderByDescending(l => l.Data)
                        .ThenByDescending(l => l.OraInizio)
                        .FirstOrDefault();

                    if (ultimaLezione != null)
                    {
                        // Mostra l'ultima lezione
                        lblUltimaLezione.Text = $"{ultimaLezione.Data:dd/MM/yyyy}, {ultimaLezione.OraInizioStr}, {GetItalianDayName(ultimaLezione.Data.DayOfWeek)}";

                        // Calcola la data della prossima lezione (stesso giorno della settimana, una settimana dopo l'ultima)
                        DateTime dataUltimaLezione = ultimaLezione.Data;
                        DateTime dataProssimaLezione = dataUltimaLezione.AddDays(7);

                        // Continua a sommare 7 giorni finché non si trova una data futura
                        while (dataProssimaLezione <= DateTime.Today)
                        {
                            dataProssimaLezione = dataProssimaLezione.AddDays(7);
                        }

                        // Mostra la prossima lezione
                        lblProssimaLezione.Text = $"{dataProssimaLezione:dd/MM/yyyy}, {ultimaLezione.OraInizioStr}, {GetItalianDayName(dataProssimaLezione.DayOfWeek)}";

                        // Se ci sono più lezioni nel pacchetto, mostra le altre
                        if (nudNumeroLezioni.Value > 1)
                        {
                            ShowAltreLezioni(dataProssimaLezione, ultimaLezione.OraInizioStr, (int)nudNumeroLezioni.Value);
                        }
                    }
                }
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in ConfigureForm: {ex.Message}");
                MessageBox.Show($"Errore durante la configurazione del form: {ex.Message}", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void ShowAltreLezioni(DateTime primaLezione, string orario, int numeroTotaleLezioni)
        {
            // Crea il pannello per le altre lezioni se non esiste già
            if (panelAltreLezioni == null)
            {
                panelAltreLezioni = new Panel();
                panelAltreLezioni.Location = new Point(23, 255);
                panelAltreLezioni.Size = new Size(440, 120); // Ridotto l'altezza
                panelAltreLezioni.AutoScroll = true;
                panelMain.Controls.Add(panelAltreLezioni);

                // Sposta il checkbox più vicino (ridotto lo spazio)
                chkPianificaSubito.Location = new Point(chkPianificaSubito.Location.X, panelAltreLezioni.Bottom + 5);

                // Ridimensiona il form se necessario (con meno altezza)
                this.Height = Math.Max(this.Height, chkPianificaSubito.Bottom + panelButtons.Height + 60);
            }

            // Pulisci le label esistenti
            if (altreLezioniLabels != null)
            {
                foreach (var lbl in altreLezioniLabels)
                {
                    panelAltreLezioni.Controls.Remove(lbl);
                    lbl.Dispose();
                }
            }
            altreLezioniLabels = new List<Label>();

            // Aggiungi titolo per le altre lezioni
            Label titleLabel = new Label();
            titleLabel.Text = "Altre lezioni del pacchetto:";
            titleLabel.Font = new Font("Segoe UI", 9.75F, FontStyle.Bold);
            titleLabel.Location = new Point(0, 0);
            titleLabel.Size = new Size(200, 20);
            panelAltreLezioni.Controls.Add(titleLabel);
            altreLezioniLabels.Add(titleLabel);

            // Mostra le altre lezioni (dalla seconda in poi)
            int yPos = 22; // Ridotto lo spazio iniziale
            DateTime dataLezione = primaLezione;

            for (int i = 2; i <= numeroTotaleLezioni; i++)
            {
                dataLezione = dataLezione.AddDays(7);

                Label lezioneLabel = new Label();
                lezioneLabel.Text = $"Lezione {i}: {dataLezione:dd/MM/yyyy}, {orario}, {GetItalianDayName(dataLezione.DayOfWeek)}";
                lezioneLabel.Font = new Font("Segoe UI", 9F);
                lezioneLabel.ForeColor = Color.FromArgb(64, 64, 64);
                lezioneLabel.Location = new Point(20, yPos);
                lezioneLabel.Size = new Size(400, 20);
                lezioneLabel.AutoSize = true;

                panelAltreLezioni.Controls.Add(lezioneLabel);
                altreLezioniLabels.Add(lezioneLabel);

                yPos += 20; // Ridotto lo spazio tra le righe
            }
        }

        private void btnConferma_Click(object sender, EventArgs e)
        {
            try
            {
                if (string.IsNullOrWhiteSpace(txtNomePacchetto.Text))
                {
                    MessageBox.Show("Nome del pacchetto non valido.", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                    return;
                }

                // Validazione importo
                decimal importo;
                if (!decimal.TryParse(txtImporto.Text, out importo) || importo <= 0)
                {
                    MessageBox.Show("Importo non valido. Inserire un valore numerico positivo.", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                    txtImporto.Focus();
                    return;
                }

                // Crea il nuovo acquisto simile all'ultimo
                NuovoAcquisto = new Models.Acquisto
                {
                    ClienteId = cliente.Id,
                    NomeCliente = $"{cliente.Cognome} {cliente.Nome}",
                    PacchettoId = ultimoAcquisto.PacchettoId,
                    NomePacchetto = txtNomePacchetto.Text,
                    DataAcquisto = dtpDataAcquisto.Value,
                    ImportoPagato = importo,
                    StatoPagamento = cmbStatoPagamento.SelectedItem.ToString(),
                    NumeroFattura = "", // Vuoto come richiesto
                    NumeroLezioni = (int)nudNumeroLezioni.Value,
                    Pianificato = false, // Sarà impostato a true solo dopo la pianificazione
                    Note = "Creato tramite funzione Rinnovo Veloce"
                };

                PianificaSubito = chkPianificaSubito.Checked;

                this.DialogResult = DialogResult.OK;
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante la creazione del rinnovo: {ex.Message}", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void btnAnnulla_Click(object sender, EventArgs e)
        {
            this.DialogResult = DialogResult.Cancel;
            this.Close();
        }

        private void txtImporto_KeyPress(object sender, KeyPressEventArgs e)
        {
            // Permetti solo numeri, virgola, punto e backspace
            if (!char.IsControl(e.KeyChar) && !char.IsDigit(e.KeyChar) &&
                e.KeyChar != ',' && e.KeyChar != '.')
            {
                e.Handled = true;
            }

            // Permetti solo una virgola o punto
            if ((e.KeyChar == ',' || e.KeyChar == '.') &&
                (txtImporto.Text.Contains(',') || txtImporto.Text.Contains('.')))
            {
                e.Handled = true;
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
    }
}