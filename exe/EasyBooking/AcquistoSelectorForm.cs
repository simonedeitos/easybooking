using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.IO;
using System.Linq;
using System.Windows.Forms;
using EasyBooking.Models;  // Aggiungi questa direttiva

namespace EasyBooking
{
    public partial class AcquistoSelectorForm : Form
    {
        private string dataPath;
        private int clienteId;
        private Models.AcquistiList acquisti;  // Modifica qui
        private Models.PacchettiList pacchetti;  // Modifica qui

        public Models.Acquisto SelectedAcquisto { get; private set; }  // Modifica qui

        public AcquistoSelectorForm(string dataPath, int clienteId)
        {
            InitializeComponent();

            this.dataPath = dataPath;
            this.clienteId = clienteId;

            LoadData();
            PopulateGrid();
        }

        private void LoadData()
        {
            // Carica i dati degli acquisti
            string acquistiFilePath = Path.Combine(dataPath, "acquisti.xml");
            acquisti = MainForm.LoadEncryptedXml<Models.AcquistiList>(acquistiFilePath);  // Modifica qui

            if (acquisti == null || acquisti.Items == null)
            {
                acquisti = new Models.AcquistiList();  // Modifica qui
            }

            // Carica i dati dei pacchetti
            string pacchettiFilePath = Path.Combine(dataPath, "pacchetti.xml");
            pacchetti = MainForm.LoadEncryptedXml<Models.PacchettiList>(pacchettiFilePath);  // Modifica qui

            if (pacchetti == null || pacchetti.Items == null)
            {
                pacchetti = new Models.PacchettiList();  // Modifica qui
            }
        }

        private void PopulateGrid()
        {
            // Filtra gli acquisti per il cliente selezionato
            var clienteAcquisti = acquisti.Items
                .Where(a => a.ClienteId == clienteId && a.StatoPagamento != "Annullato")
                .ToList();

            // Crea una vista personalizzata per la griglia
            var acquistiFiltrati = clienteAcquisti.Select(a => new
            {
                Id = a.Id,
                Data = a.DataAcquisto.ToString("dd/MM/yyyy"),
                Pacchetto = GetPacchettoName(a.PacchettoId),
                Importo = a.ImportoPagato.ToString("c"),
                Stato = a.StatoPagamento,
                Pianificato = a.Pianificato ? "Sì" : "No"
            }).ToList();

            dgvAcquisti.DataSource = acquistiFiltrati;

            if (dgvAcquisti.Columns.Count > 0)
            {
                dgvAcquisti.Columns["Id"].Width = 50;
                dgvAcquisti.Columns["Data"].Width = 80;
                dgvAcquisti.Columns["Pacchetto"].Width = 150;
                dgvAcquisti.Columns["Importo"].Width = 80;
                dgvAcquisti.Columns["Stato"].Width = 100;
                dgvAcquisti.Columns["Pianificato"].Width = 60;
            }

            // Se non ci sono acquisti, mostra un messaggio
            if (clienteAcquisti.Count == 0)
            {
                lblNoAcquisti.Visible = true;
                btnSeleziona.Enabled = false;
            }
            else
            {
                lblNoAcquisti.Visible = false;
                btnSeleziona.Enabled = true;
            }
        }

        private string GetPacchettoName(int pacchettoId)
        {
            var pacchetto = pacchetti.Items.FirstOrDefault(p => p.Id == pacchettoId);
            return pacchetto != null ? pacchetto.Nome : "N/A";
        }

        private void btnSeleziona_Click(object sender, EventArgs e)
        {
            if (dgvAcquisti.SelectedRows.Count > 0)
            {
                // Ottiene l'ID dell'acquisto selezionato
                int acquistoId = (int)dgvAcquisti.SelectedRows[0].Cells["Id"].Value;

                // Trova l'acquisto corrispondente
                SelectedAcquisto = acquisti.Items.FirstOrDefault(a => a.Id == acquistoId);

                if (SelectedAcquisto != null)
                {
                    DialogResult = DialogResult.OK;
                    Close();
                }
            }
        }

        private void btnAnnulla_Click(object sender, EventArgs e)
        {
            DialogResult = DialogResult.Cancel;
            Close();
        }

        private void dgvAcquisti_CellDoubleClick(object sender, DataGridViewCellEventArgs e)
        {
            if (e.RowIndex >= 0)
            {
                btnSeleziona_Click(sender, EventArgs.Empty);
            }
        }
    }
}