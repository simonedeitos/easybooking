using System;
using System.IO;
using System.Windows.Forms;

namespace EasyBooking
{
    public partial class MegaSettingsForm : Form
    {
        private Cliente cliente;

        public MegaSettingsForm(Cliente cliente)
        {
            InitializeComponent();
            this.cliente = cliente;
            LoadClientMegaSettings();
        }

        private void LoadClientMegaSettings()
        {
            txtMegaLink.Text = cliente.MegaCartellaPubblica ?? string.Empty;
            txtCartellaLocale.Text = cliente.MegaCartellaLocale ?? string.Empty;

            lblCliente.Text = $"Impostazioni MEGA per: {cliente.Nome} {cliente.Cognome}";
        }

        private void btnSfogliaCartella_Click(object sender, EventArgs e)
        {
            using (FolderBrowserDialog folderDialog = new FolderBrowserDialog())
            {
                folderDialog.Description = "Seleziona la cartella locale MEGA per questo cliente";
                folderDialog.ShowNewFolderButton = true;

                if (!string.IsNullOrEmpty(txtCartellaLocale.Text) && Directory.Exists(txtCartellaLocale.Text))
                {
                    folderDialog.SelectedPath = txtCartellaLocale.Text;
                }

                if (folderDialog.ShowDialog() == DialogResult.OK)
                {
                    txtCartellaLocale.Text = folderDialog.SelectedPath;
                }
            }
        }

        private void btnTestCartella_Click(object sender, EventArgs e)
        {
            if (string.IsNullOrWhiteSpace(txtCartellaLocale.Text))
            {
                MessageBox.Show("Inserisci prima il percorso della cartella locale.", "Percorso mancante",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            if (Directory.Exists(txtCartellaLocale.Text))
            {
                MessageBox.Show("Cartella trovata e accessibile!", "Test riuscito",
                    MessageBoxButtons.OK, MessageBoxIcon.Information);

                // Chiedi se aprire la cartella
                if (MessageBox.Show("Vuoi aprire la cartella?", "Aprire cartella",
                    MessageBoxButtons.YesNo, MessageBoxIcon.Question) == DialogResult.Yes)
                {
                    try
                    {
                        System.Diagnostics.Process.Start("explorer.exe", txtCartellaLocale.Text);
                    }
                    catch (Exception ex)
                    {
                        MessageBox.Show($"Errore nell'apertura della cartella: {ex.Message}", "Errore",
                            MessageBoxButtons.OK, MessageBoxIcon.Error);
                    }
                }
            }
            else
            {
                MessageBox.Show("La cartella specificata non esiste o non è accessibile.", "Cartella non trovata",
                    MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void btnTestLink_Click(object sender, EventArgs e)
        {
            if (string.IsNullOrWhiteSpace(txtMegaLink.Text))
            {
                MessageBox.Show("Inserisci prima il link MEGA.", "Link mancante",
                    MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return;
            }

            try
            {
                System.Diagnostics.Process.Start(txtMegaLink.Text.Trim());
                MessageBox.Show("Link aperto nel browser!", "Test riuscito",
                    MessageBoxButtons.OK, MessageBoxIcon.Information);
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore nell'apertura del link: {ex.Message}", "Errore",
                    MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void btnOK_Click(object sender, EventArgs e)
        {
            // Valida il link MEGA se presente
            if (!string.IsNullOrWhiteSpace(txtMegaLink.Text))
            {
                string link = txtMegaLink.Text.Trim();
                if (!link.StartsWith("http://") && !link.StartsWith("https://"))
                {
                    MessageBox.Show("Il link MEGA deve iniziare con http:// o https://", "Link non valido",
                        MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    txtMegaLink.Focus();
                    return;
                }
            }

            // Valida la cartella locale se presente
            if (!string.IsNullOrWhiteSpace(txtCartellaLocale.Text))
            {
                if (!Directory.Exists(txtCartellaLocale.Text.Trim()))
                {
                    var result = MessageBox.Show(
                        "La cartella specificata non esiste. Vuoi salvarla comunque?",
                        "Cartella non esistente",
                        MessageBoxButtons.YesNo,
                        MessageBoxIcon.Question);

                    if (result == DialogResult.No)
                    {
                        txtCartellaLocale.Focus();
                        return;
                    }
                }
            }

            // Salva le impostazioni nel cliente
            cliente.MegaCartellaPubblica = txtMegaLink.Text.Trim();
            cliente.MegaCartellaLocale = txtCartellaLocale.Text.Trim();

            this.DialogResult = DialogResult.OK;
            this.Close();
        }

        private void btnAnnulla_Click(object sender, EventArgs e)
        {
            this.DialogResult = DialogResult.Cancel;
            this.Close();
        }

        private void btnPulisci_Click(object sender, EventArgs e)
        {
            var result = MessageBox.Show(
                "Sei sicuro di voler cancellare tutte le impostazioni MEGA per questo cliente?",
                "Conferma cancellazione",
                MessageBoxButtons.YesNo,
                MessageBoxIcon.Question);

            if (result == DialogResult.Yes)
            {
                txtMegaLink.Text = string.Empty;
                txtCartellaLocale.Text = string.Empty;
            }
        }

        private void lblInfo_Click(object sender, EventArgs e)
        {

        }
    }
}