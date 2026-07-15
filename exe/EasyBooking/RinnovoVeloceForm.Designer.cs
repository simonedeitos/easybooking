namespace EasyBooking
{
    partial class RinnovoVeloceForm
    {
        /// <summary>
        /// Required designer variable.
        /// </summary>
        private System.ComponentModel.IContainer components = null;

        /// <summary>
        /// Clean up any resources being used.
        /// </summary>
        /// <param name="disposing">true if managed resources should be disposed; otherwise, false.</param>
        protected override void Dispose(bool disposing)
        {
            if (disposing && (components != null))
            {
                components.Dispose();
            }
            base.Dispose(disposing);
        }

        #region Windows Form Designer generated code

        /// <summary>
        /// Required method for Designer support - do not modify
        /// the contents of this method with the code editor.
        /// </summary>
        private void InitializeComponent()
        {
            this.panel1 = new System.Windows.Forms.Panel();
            this.lblUltimoAcquisto = new System.Windows.Forms.Label();
            this.lblClienteNome = new System.Windows.Forms.Label();
            this.lblTitolo = new System.Windows.Forms.Label();
            this.panelMain = new System.Windows.Forms.Panel();
            this.chkPianificaSubito = new System.Windows.Forms.CheckBox();
            this.lblProssimaLezione = new System.Windows.Forms.Label();
            this.lblProssimaLezioneTitle = new System.Windows.Forms.Label();
            this.lblUltimaLezione = new System.Windows.Forms.Label();
            this.lblUltimaLezioneTitle = new System.Windows.Forms.Label();
            this.nudNumeroLezioni = new System.Windows.Forms.NumericUpDown();
            this.lblNumeroLezioni = new System.Windows.Forms.Label();
            this.txtNomePacchetto = new System.Windows.Forms.TextBox();
            this.lblPacchetto = new System.Windows.Forms.Label();
            this.cmbStatoPagamento = new System.Windows.Forms.ComboBox();
            this.lblStatoPagamento = new System.Windows.Forms.Label();
            this.txtImporto = new System.Windows.Forms.TextBox();
            this.lblImporto = new System.Windows.Forms.Label();
            this.dtpDataAcquisto = new System.Windows.Forms.DateTimePicker();
            this.lblDataAcquisto = new System.Windows.Forms.Label();
            this.panelButtons = new System.Windows.Forms.Panel();
            this.btnAnnulla = new System.Windows.Forms.Button();
            this.btnConferma = new System.Windows.Forms.Button();
            this.panel1.SuspendLayout();
            this.panelMain.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)(this.nudNumeroLezioni)).BeginInit();
            this.panelButtons.SuspendLayout();
            this.SuspendLayout();
            // 
            // panel1
            // 
            this.panel1.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(240)))), ((int)(((byte)(240)))), ((int)(((byte)(240)))));
            this.panel1.Controls.Add(this.lblUltimoAcquisto);
            this.panel1.Controls.Add(this.lblClienteNome);
            this.panel1.Controls.Add(this.lblTitolo);
            this.panel1.Dock = System.Windows.Forms.DockStyle.Top;
            this.panel1.Location = new System.Drawing.Point(0, 0);
            this.panel1.Name = "panel1";
            this.panel1.Size = new System.Drawing.Size(484, 70);
            this.panel1.TabIndex = 0;
            // 
            // lblUltimoAcquisto
            // 
            this.lblUltimoAcquisto.AutoSize = true;
            this.lblUltimoAcquisto.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblUltimoAcquisto.Location = new System.Drawing.Point(14, 45);
            this.lblUltimoAcquisto.Name = "lblUltimoAcquisto";
            this.lblUltimoAcquisto.Size = new System.Drawing.Size(91, 15);
            this.lblUltimoAcquisto.TabIndex = 2;
            this.lblUltimoAcquisto.Text = "Ultimo acquisto";
            // 
            // lblClienteNome
            // 
            this.lblClienteNome.AutoSize = true;
            this.lblClienteNome.Font = new System.Drawing.Font("Segoe UI", 11.25F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblClienteNome.ForeColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(122)))), ((int)(((byte)(204)))));
            this.lblClienteNome.Location = new System.Drawing.Point(161, 15);
            this.lblClienteNome.Name = "lblClienteNome";
            this.lblClienteNome.Size = new System.Drawing.Size(104, 20);
            this.lblClienteNome.TabIndex = 1;
            this.lblClienteNome.Text = "Nome Cliente";
            // 
            // lblTitolo
            // 
            this.lblTitolo.AutoSize = true;
            this.lblTitolo.Font = new System.Drawing.Font("Segoe UI", 12F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblTitolo.ForeColor = System.Drawing.Color.FromArgb(((int)(((byte)(34)))), ((int)(((byte)(139)))), ((int)(((byte)(34)))));
            this.lblTitolo.Location = new System.Drawing.Point(12, 14);
            this.lblTitolo.Name = "lblTitolo";
            this.lblTitolo.Size = new System.Drawing.Size(149, 21);
            this.lblTitolo.TabIndex = 0;
            this.lblTitolo.Text = "RINNOVO VELOCE";
            // 
            // panelMain
            // 
            this.panelMain.BackColor = System.Drawing.Color.White;
            this.panelMain.Controls.Add(this.chkPianificaSubito);
            this.panelMain.Controls.Add(this.lblProssimaLezione);
            this.panelMain.Controls.Add(this.lblProssimaLezioneTitle);
            this.panelMain.Controls.Add(this.lblUltimaLezione);
            this.panelMain.Controls.Add(this.lblUltimaLezioneTitle);
            this.panelMain.Controls.Add(this.nudNumeroLezioni);
            this.panelMain.Controls.Add(this.lblNumeroLezioni);
            this.panelMain.Controls.Add(this.txtNomePacchetto);
            this.panelMain.Controls.Add(this.lblPacchetto);
            this.panelMain.Controls.Add(this.cmbStatoPagamento);
            this.panelMain.Controls.Add(this.lblStatoPagamento);
            this.panelMain.Controls.Add(this.txtImporto);
            this.panelMain.Controls.Add(this.lblImporto);
            this.panelMain.Controls.Add(this.dtpDataAcquisto);
            this.panelMain.Controls.Add(this.lblDataAcquisto);
            this.panelMain.Dock = System.Windows.Forms.DockStyle.Fill;
            this.panelMain.Location = new System.Drawing.Point(0, 70);
            this.panelMain.Name = "panelMain";
            this.panelMain.Padding = new System.Windows.Forms.Padding(20);
            this.panelMain.Size = new System.Drawing.Size(484, 429);
            this.panelMain.TabIndex = 1;
            // 
            // chkPianificaSubito
            // 
            this.chkPianificaSubito.AutoSize = true;
            this.chkPianificaSubito.Checked = true;
            this.chkPianificaSubito.CheckState = System.Windows.Forms.CheckState.Checked;
            this.chkPianificaSubito.Font = new System.Drawing.Font("Segoe UI", 9.75F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.chkPianificaSubito.Location = new System.Drawing.Point(168, 260);
            this.chkPianificaSubito.Name = "chkPianificaSubito";
            this.chkPianificaSubito.Size = new System.Drawing.Size(123, 21);
            this.chkPianificaSubito.TabIndex = 14;
            this.chkPianificaSubito.Text = "Pianifica subito";
            this.chkPianificaSubito.UseVisualStyleBackColor = true;
            // 
            // lblProssimaLezione
            // 
            this.lblProssimaLezione.AutoSize = true;
            this.lblProssimaLezione.Font = new System.Drawing.Font("Segoe UI", 9.75F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblProssimaLezione.ForeColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(122)))), ((int)(((byte)(204)))));
            this.lblProssimaLezione.Location = new System.Drawing.Point(167, 225);
            this.lblProssimaLezione.Name = "lblProssimaLezione";
            this.lblProssimaLezione.Size = new System.Drawing.Size(33, 17);
            this.lblProssimaLezione.TabIndex = 13;
            this.lblProssimaLezione.Text = "N/A";
            // 
            // lblProssimaLezioneTitle
            // 
            this.lblProssimaLezioneTitle.AutoSize = true;
            this.lblProssimaLezioneTitle.Location = new System.Drawing.Point(23, 225);
            this.lblProssimaLezioneTitle.Name = "lblProssimaLezioneTitle";
            this.lblProssimaLezioneTitle.Size = new System.Drawing.Size(128, 17);
            this.lblProssimaLezioneTitle.TabIndex = 12;
            this.lblProssimaLezioneTitle.Text = "Prima nuova lezione:";
            // 
            // lblUltimaLezione
            // 
            this.lblUltimaLezione.AutoSize = true;
            this.lblUltimaLezione.Location = new System.Drawing.Point(167, 195);
            this.lblUltimaLezione.Name = "lblUltimaLezione";
            this.lblUltimaLezione.Size = new System.Drawing.Size(31, 17);
            this.lblUltimaLezione.TabIndex = 11;
            this.lblUltimaLezione.Text = "N/A";
            // 
            // lblUltimaLezioneTitle
            // 
            this.lblUltimaLezioneTitle.AutoSize = true;
            this.lblUltimaLezioneTitle.Location = new System.Drawing.Point(23, 195);
            this.lblUltimaLezioneTitle.Name = "lblUltimaLezioneTitle";
            this.lblUltimaLezioneTitle.Size = new System.Drawing.Size(93, 17);
            this.lblUltimaLezioneTitle.TabIndex = 10;
            this.lblUltimaLezioneTitle.Text = "Ultima lezione:";
            // 
            // nudNumeroLezioni
            // 
            this.nudNumeroLezioni.Location = new System.Drawing.Point(167, 155);
            this.nudNumeroLezioni.Minimum = new decimal(new int[] {
            1,
            0,
            0,
            0});
            this.nudNumeroLezioni.Name = "nudNumeroLezioni";
            this.nudNumeroLezioni.Size = new System.Drawing.Size(80, 25);
            this.nudNumeroLezioni.TabIndex = 9;
            this.nudNumeroLezioni.Value = new decimal(new int[] {
            10,
            0,
            0,
            0});
            // 
            // lblNumeroLezioni
            // 
            this.lblNumeroLezioni.AutoSize = true;
            this.lblNumeroLezioni.Location = new System.Drawing.Point(23, 157);
            this.lblNumeroLezioni.Name = "lblNumeroLezioni";
            this.lblNumeroLezioni.Size = new System.Drawing.Size(100, 17);
            this.lblNumeroLezioni.TabIndex = 8;
            this.lblNumeroLezioni.Text = "Numero lezioni:";
            // 
            // txtNomePacchetto
            // 
            this.txtNomePacchetto.Location = new System.Drawing.Point(167, 120);
            this.txtNomePacchetto.Name = "txtNomePacchetto";
            this.txtNomePacchetto.Size = new System.Drawing.Size(250, 25);
            this.txtNomePacchetto.TabIndex = 7;
            // 
            // lblPacchetto
            // 
            this.lblPacchetto.AutoSize = true;
            this.lblPacchetto.Location = new System.Drawing.Point(23, 123);
            this.lblPacchetto.Name = "lblPacchetto";
            this.lblPacchetto.Size = new System.Drawing.Size(108, 17);
            this.lblPacchetto.TabIndex = 6;
            this.lblPacchetto.Text = "Nome pacchetto:";
            // 
            // cmbStatoPagamento
            // 
            this.cmbStatoPagamento.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbStatoPagamento.FormattingEnabled = true;
            this.cmbStatoPagamento.Items.AddRange(new object[] {
            "Pagato",
            "Da pagare",
            "Pagamento parziale",
            "Annullato"});
            this.cmbStatoPagamento.Location = new System.Drawing.Point(167, 85);
            this.cmbStatoPagamento.Name = "cmbStatoPagamento";
            this.cmbStatoPagamento.Size = new System.Drawing.Size(150, 25);
            this.cmbStatoPagamento.TabIndex = 5;
            // 
            // lblStatoPagamento
            // 
            this.lblStatoPagamento.AutoSize = true;
            this.lblStatoPagamento.Location = new System.Drawing.Point(23, 88);
            this.lblStatoPagamento.Name = "lblStatoPagamento";
            this.lblStatoPagamento.Size = new System.Drawing.Size(112, 17);
            this.lblStatoPagamento.TabIndex = 4;
            this.lblStatoPagamento.Text = "Stato pagamento:";
            // 
            // txtImporto
            // 
            this.txtImporto.Location = new System.Drawing.Point(167, 50);
            this.txtImporto.Name = "txtImporto";
            this.txtImporto.Size = new System.Drawing.Size(120, 25);
            this.txtImporto.TabIndex = 3;
            this.txtImporto.KeyPress += new System.Windows.Forms.KeyPressEventHandler(this.txtImporto_KeyPress);
            // 
            // lblImporto
            // 
            this.lblImporto.AutoSize = true;
            this.lblImporto.Location = new System.Drawing.Point(23, 52);
            this.lblImporto.Name = "lblImporto";
            this.lblImporto.Size = new System.Drawing.Size(58, 17);
            this.lblImporto.TabIndex = 2;
            this.lblImporto.Text = "Importo:";
            // 
            // dtpDataAcquisto
            // 
            this.dtpDataAcquisto.Format = System.Windows.Forms.DateTimePickerFormat.Short;
            this.dtpDataAcquisto.Location = new System.Drawing.Point(167, 15);
            this.dtpDataAcquisto.Name = "dtpDataAcquisto";
            this.dtpDataAcquisto.Size = new System.Drawing.Size(120, 25);
            this.dtpDataAcquisto.TabIndex = 1;
            // 
            // lblDataAcquisto
            // 
            this.lblDataAcquisto.AutoSize = true;
            this.lblDataAcquisto.Location = new System.Drawing.Point(23, 20);
            this.lblDataAcquisto.Name = "lblDataAcquisto";
            this.lblDataAcquisto.Size = new System.Drawing.Size(91, 17);
            this.lblDataAcquisto.TabIndex = 0;
            this.lblDataAcquisto.Text = "Data acquisto:";
            // 
            // panelButtons
            // 
            this.panelButtons.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(240)))), ((int)(((byte)(240)))), ((int)(((byte)(240)))));
            this.panelButtons.Controls.Add(this.btnAnnulla);
            this.panelButtons.Controls.Add(this.btnConferma);
            this.panelButtons.Dock = System.Windows.Forms.DockStyle.Bottom;
            this.panelButtons.Location = new System.Drawing.Point(0, 499);
            this.panelButtons.Name = "panelButtons";
            this.panelButtons.Size = new System.Drawing.Size(484, 60);
            this.panelButtons.TabIndex = 2;
            // 
            // btnAnnulla
            // 
            this.btnAnnulla.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Right)));
            this.btnAnnulla.Location = new System.Drawing.Point(382, 14);
            this.btnAnnulla.Name = "btnAnnulla";
            this.btnAnnulla.Size = new System.Drawing.Size(90, 32);
            this.btnAnnulla.TabIndex = 1;
            this.btnAnnulla.Text = "Annulla";
            this.btnAnnulla.UseVisualStyleBackColor = true;
            this.btnAnnulla.Click += new System.EventHandler(this.btnAnnulla_Click);
            // 
            // btnConferma
            // 
            this.btnConferma.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Right)));
            this.btnConferma.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(34)))), ((int)(((byte)(139)))), ((int)(((byte)(34)))));
            this.btnConferma.FlatAppearance.BorderColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(64)))), ((int)(((byte)(0)))));
            this.btnConferma.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnConferma.Font = new System.Drawing.Font("Segoe UI", 9.75F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.btnConferma.ForeColor = System.Drawing.Color.White;
            this.btnConferma.Location = new System.Drawing.Point(249, 14);
            this.btnConferma.Name = "btnConferma";
            this.btnConferma.Size = new System.Drawing.Size(127, 32);
            this.btnConferma.TabIndex = 0;
            this.btnConferma.Text = "Conferma Rinnovo";
            this.btnConferma.UseVisualStyleBackColor = false;
            this.btnConferma.Click += new System.EventHandler(this.btnConferma_Click);
            // 
            // RinnovoVeloceForm
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(7F, 17F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.ClientSize = new System.Drawing.Size(484, 559);
            this.Controls.Add(this.panelMain);
            this.Controls.Add(this.panelButtons);
            this.Controls.Add(this.panel1);
            this.Font = new System.Drawing.Font("Segoe UI", 9.75F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.FormBorderStyle = System.Windows.Forms.FormBorderStyle.FixedDialog;
            this.Margin = new System.Windows.Forms.Padding(4);
            this.MaximizeBox = false;
            this.MinimizeBox = false;
            this.Name = "RinnovoVeloceForm";
            this.StartPosition = System.Windows.Forms.FormStartPosition.CenterParent;
            this.Text = "Rinnovo Veloce";
            this.panel1.ResumeLayout(false);
            this.panel1.PerformLayout();
            this.panelMain.ResumeLayout(false);
            this.panelMain.PerformLayout();
            ((System.ComponentModel.ISupportInitialize)(this.nudNumeroLezioni)).EndInit();
            this.panelButtons.ResumeLayout(false);
            this.ResumeLayout(false);

        }

        #endregion

        private System.Windows.Forms.Panel panel1;
        private System.Windows.Forms.Label lblTitolo;
        private System.Windows.Forms.Panel panelMain;
        private System.Windows.Forms.Panel panelButtons;
        private System.Windows.Forms.Label lblClienteNome;
        private System.Windows.Forms.Label lblUltimoAcquisto;
        private System.Windows.Forms.DateTimePicker dtpDataAcquisto;
        private System.Windows.Forms.Label lblDataAcquisto;
        private System.Windows.Forms.TextBox txtImporto;
        private System.Windows.Forms.Label lblImporto;
        private System.Windows.Forms.ComboBox cmbStatoPagamento;
        private System.Windows.Forms.Label lblStatoPagamento;
        private System.Windows.Forms.TextBox txtNomePacchetto;
        private System.Windows.Forms.Label lblPacchetto;
        private System.Windows.Forms.NumericUpDown nudNumeroLezioni;
        private System.Windows.Forms.Label lblNumeroLezioni;
        private System.Windows.Forms.Label lblProssimaLezione;
        private System.Windows.Forms.Label lblProssimaLezioneTitle;
        private System.Windows.Forms.Label lblUltimaLezione;
        private System.Windows.Forms.Label lblUltimaLezioneTitle;
        private System.Windows.Forms.CheckBox chkPianificaSubito;
        private System.Windows.Forms.Button btnAnnulla;
        private System.Windows.Forms.Button btnConferma;
    }
}