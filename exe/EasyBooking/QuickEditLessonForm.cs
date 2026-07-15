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

namespace EasyBooking
{
    public partial class QuickEditLessonForm : Form
    {
        private Prenotazione lesson;
        private string dataPath;
        private string prenotazioniFilePath;
        private string insegnantiFilePath;
        private InsegnantiList insegnanti;

        public QuickEditLessonForm(Prenotazione lesson, string dataPath)
        {
            InitializeComponent();
            this.lesson = lesson;
            this.dataPath = dataPath;

            prenotazioniFilePath = Path.Combine(dataPath, "prenotazioni.xml");
            insegnantiFilePath = Path.Combine(dataPath, "insegnanti.xml");

            SetupDurationComboBox();
            LoadInsegnanti();
            LoadLessonData();
        }

        private void SetupDurationComboBox()
        {
            // Popola la combobox con le durate prefissate
            cmbDurata.Items.Clear();

            var durations = new[]
            {
                new { Minutes = 30, Display = "0:30" },
                new { Minutes = 45, Display = "0:45" },
                new { Minutes = 60, Display = "1:00" },
                new { Minutes = 75, Display = "1:15" },
                new { Minutes = 90, Display = "1:30" },
                new { Minutes = 105, Display = "1:45" },
                new { Minutes = 120, Display = "2:00" },
                new { Minutes = 135, Display = "2:15" },
                new { Minutes = 150, Display = "2:30" },
                new { Minutes = 155, Display = "2:35" },
                new { Minutes = 180, Display = "3:00" },
                new { Minutes = 195, Display = "3:15" },
                new { Minutes = 210, Display = "3:30" },
                new { Minutes = 225, Display = "3:45" },
                new { Minutes = 240, Display = "4:00" }
            };

            foreach (var duration in durations)
            {
                cmbDurata.Items.Add(new DurationItem
                {
                    Minutes = duration.Minutes,
                    Display = duration.Display
                });
            }

            cmbDurata.DisplayMember = "Display";
            cmbDurata.ValueMember = "Minutes";

            // Seleziona 1:00 come default
            cmbDurata.SelectedIndex = 2; // 1:00
        }

        private void LoadInsegnanti()
        {
            try
            {
                insegnanti = MainForm.LoadEncryptedXml<InsegnantiList>(insegnantiFilePath);

                if (insegnanti == null || insegnanti.Items == null)
                {
                    insegnanti = new InsegnantiList { Items = new List<Insegnante>() };
                }

                // Popola la combobox degli insegnanti
                cmbInsegnante.Items.Clear();
                foreach (var insegnante in insegnanti.Items.OrderBy(i => i.Cognome).ThenBy(i => i.Nome))
                {
                    cmbInsegnante.Items.Add(new ComboBoxItem
                    {
                        Value = insegnante.Id,
                        Text = $"{insegnante.Cognome} {insegnante.Nome}"
                    });
                }

                cmbInsegnante.DisplayMember = "Text";
                cmbInsegnante.ValueMember = "Value";
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore nel caricamento degli insegnanti: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void LoadLessonData()
        {
            try
            {
                // Carica i dati della lezione nei controlli
                dtpData.Value = lesson.Data;

                // Imposta l'orario di inizio
                if (TimeSpan.TryParse(lesson.OraInizioStr, out TimeSpan oraInizio))
                {
                    dtpOraInizio.Value = DateTime.Today.Add(oraInizio);
                }
                else
                {
                    dtpOraInizio.Value = DateTime.Today.AddHours(15); // Default 15:00
                }

                // Calcola la durata e seleziona nella combobox
                if (TimeSpan.TryParse(lesson.OraInizioStr, out TimeSpan inizio) &&
                    TimeSpan.TryParse(lesson.OraFineStr, out TimeSpan fine))
                {
                    int durataMinuti = (int)(fine - inizio).TotalMinutes;

                    // Trova la durata corrispondente nella combobox
                    foreach (DurationItem item in cmbDurata.Items)
                    {
                        if (item.Minutes == durataMinuti)
                        {
                            cmbDurata.SelectedItem = item;
                            break;
                        }
                    }

                    // Se non trova una corrispondenza esatta, seleziona 1:00
                    if (cmbDurata.SelectedItem == null)
                    {
                        cmbDurata.SelectedIndex = 2; // 1:00
                    }
                }
                else
                {
                    cmbDurata.SelectedIndex = 2; // Default 1:00
                }

                // Seleziona l'insegnante
                foreach (ComboBoxItem item in cmbInsegnante.Items)
                {
                    if ((int)item.Value == lesson.InsegnanteId)
                    {
                        cmbInsegnante.SelectedItem = item;
                        break;
                    }
                }

                // Imposta lo strumento
                txtStrumento.Text = lesson.Strumento ?? "";

                // Imposta il titolo del form
                this.Text = $"Modifica Lezione - {lesson.Data:dd/MM/yyyy}";

                // Aggiorna l'ora di fine
                UpdateOraFineLabel();
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore nel caricamento dei dati della lezione: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void btnSalva_Click(object sender, EventArgs e)
        {
            try
            {
                // Validazione
                if (cmbInsegnante.SelectedItem == null)
                {
                    MessageBox.Show("Seleziona un insegnante.", "Validazione",
                        MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    return;
                }

                if (cmbDurata.SelectedItem == null)
                {
                    MessageBox.Show("Seleziona una durata.", "Validazione",
                        MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    return;
                }

                // Calcola gli orari
                DateTime dataLezione = dtpData.Value.Date;
                TimeSpan oraInizio = dtpOraInizio.Value.TimeOfDay;
                int durataMinuti = ((DurationItem)cmbDurata.SelectedItem).Minutes;
                TimeSpan durata = TimeSpan.FromMinutes(durataMinuti);
                TimeSpan oraFine = oraInizio.Add(durata);

                // Normalizza se supera le 24 ore
                if (oraFine.TotalHours >= 24)
                {
                    MessageBox.Show("L'orario di fine supera le 24:00. Seleziona una durata minore.", "Validazione",
                        MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    return;
                }

                // Carica la lista delle prenotazioni
                var prenotazioniList = MainForm.LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);

                if (prenotazioniList == null || prenotazioniList.Items == null)
                {
                    MessageBox.Show("Errore nel caricamento delle prenotazioni.", "Errore",
                        MessageBoxButtons.OK, MessageBoxIcon.Error);
                    return;
                }

                // Trova la prenotazione da modificare
                var prenotazioneDaModificare = prenotazioniList.Items.FirstOrDefault(p => p.Id == lesson.Id);

                if (prenotazioneDaModificare == null)
                {
                    MessageBox.Show("Prenotazione non trovata.", "Errore",
                        MessageBoxButtons.OK, MessageBoxIcon.Error);
                    return;
                }

                // Verifica conflitti con altre prenotazioni (escludendo quella corrente)
                var insegnanteId = (int)((ComboBoxItem)cmbInsegnante.SelectedItem).Value;
                var conflitto = prenotazioniList.Items.Any(p =>
                    p.Id != lesson.Id &&
                    p.InsegnanteId == insegnanteId &&
                    p.Data.Date == dataLezione.Date &&
                    ((p.OraInizio <= oraInizio && p.OraFine > oraInizio) ||
                     (p.OraInizio < oraFine && p.OraFine >= oraFine) ||
                     (p.OraInizio >= oraInizio && p.OraFine <= oraFine)));

                if (conflitto)
                {
                    var result = MessageBox.Show(
                        "Esiste già una lezione per questo insegnante nell'orario selezionato. Continuare comunque?",
                        "Conflitto orario",
                        MessageBoxButtons.YesNo,
                        MessageBoxIcon.Warning);

                    if (result == DialogResult.No)
                    {
                        return;
                    }
                }

                // Aggiorna la prenotazione
                prenotazioneDaModificare.Data = dataLezione;
                prenotazioneDaModificare.OraInizioStr = oraInizio.ToString(@"hh\:mm");
                prenotazioneDaModificare.OraFineStr = oraFine.ToString(@"hh\:mm");
                prenotazioneDaModificare.InsegnanteId = insegnanteId;
                prenotazioneDaModificare.Strumento = txtStrumento.Text.Trim();

                // Salva le modifiche
                MainForm.SaveEncryptedXml(prenotazioniList, prenotazioniFilePath);

                // Aggiorna anche l'oggetto lesson passato come riferimento
                lesson.Data = dataLezione;
                lesson.OraInizioStr = oraInizio.ToString(@"hh\:mm");
                lesson.OraFineStr = oraFine.ToString(@"hh\:mm");
                lesson.InsegnanteId = insegnanteId;
                lesson.Strumento = txtStrumento.Text.Trim();

                this.DialogResult = DialogResult.OK;
                this.Close();
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore durante il salvataggio: {ex.Message}", "Errore",
                    MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void btnAnnulla_Click(object sender, EventArgs e)
        {
            this.DialogResult = DialogResult.Cancel;
            this.Close();
        }

        private void dtpOraInizio_ValueChanged(object sender, EventArgs e)
        {
            // Aggiorna automaticamente l'etichetta dell'ora di fine
            UpdateOraFineLabel();
        }

        private void cmbDurata_SelectedIndexChanged(object sender, EventArgs e)
        {
            // Aggiorna automaticamente l'etichetta dell'ora di fine
            UpdateOraFineLabel();
        }

        private void UpdateOraFineLabel()
        {
            try
            {
                if (cmbDurata.SelectedItem == null)
                {
                    lblOraFine.Text = "Ora fine: --:--";
                    lblOraFine.ForeColor = Color.Black;
                    return;
                }

                TimeSpan oraInizio = dtpOraInizio.Value.TimeOfDay;
                int durataMinuti = ((DurationItem)cmbDurata.SelectedItem).Minutes;
                TimeSpan durata = TimeSpan.FromMinutes(durataMinuti);
                TimeSpan oraFine = oraInizio.Add(durata);

                if (oraFine.TotalHours >= 24)
                {
                    lblOraFine.Text = "Ora fine: OLTRE 24:00";
                    lblOraFine.ForeColor = Color.Red;
                }
                else
                {
                    lblOraFine.Text = $"Ora fine: {oraFine:hh\\:mm}";
                    lblOraFine.ForeColor = Color.Black;
                }
            }
            catch
            {
                lblOraFine.Text = "Ora fine: --:--";
                lblOraFine.ForeColor = Color.Black;
            }
        }
    }

    // Classe helper per la ComboBox degli insegnanti
    public class ComboBoxItem
    {
        public object Value { get; set; }
        public string Text { get; set; }

        public override string ToString()
        {
            return Text;
        }
    }

    // Classe helper per la ComboBox delle durate
    public class DurationItem
    {
        public int Minutes { get; set; }
        public string Display { get; set; }

        public override string ToString()
        {
            return Display;
        }
    }
}