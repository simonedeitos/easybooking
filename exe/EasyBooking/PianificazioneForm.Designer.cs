namespace EasyBooking
{
    partial class PianificazioneForm
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
            this.headerPanel = new System.Windows.Forms.Panel();
            this.lblAcquisto = new System.Windows.Forms.Label();
            this.lblClienteNome = new System.Windows.Forms.Label();
            this.lblTitolo = new System.Windows.Forms.Label();
            this.panelMain = new System.Windows.Forms.Panel();
            this.lstAcquistiDaPianificare = new System.Windows.Forms.ListView();
            this.lblAcquistiDaPianificare = new System.Windows.Forms.Label();
            this.cmbGiorno = new System.Windows.Forms.ComboBox();
            this.lblGiorno = new System.Windows.Forms.Label();
            this.cmbFrequenza = new System.Windows.Forms.ComboBox();
            this.lblFrequenza = new System.Windows.Forms.Label();
            this.cmbStrumento = new System.Windows.Forms.ComboBox();
            this.lblStrumento = new System.Windows.Forms.Label();
            this.cmbInsegnante = new System.Windows.Forms.ComboBox();
            this.lblInsegnante = new System.Windows.Forms.Label();
            this.nudNumeroSettimane = new System.Windows.Forms.NumericUpDown();
            this.lblNumeroLezioni = new System.Windows.Forms.Label();
            this.nudDurata = new System.Windows.Forms.NumericUpDown();
            this.lblDurata = new System.Windows.Forms.Label();
            this.dtpOraInizio = new System.Windows.Forms.DateTimePicker();
            this.lblOraInizio = new System.Windows.Forms.Label();
            this.dtpDataInizio = new System.Windows.Forms.DateTimePicker();
            this.lblDataInizio = new System.Windows.Forms.Label();
            this.panelButtons = new System.Windows.Forms.Panel();
            this.btnChiudi = new System.Windows.Forms.Button();
            this.btnPianifica = new System.Windows.Forms.Button();
            this.headerPanel.SuspendLayout();
            this.panelMain.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)(this.nudNumeroSettimane)).BeginInit();
            ((System.ComponentModel.ISupportInitialize)(this.nudDurata)).BeginInit();
            this.panelButtons.SuspendLayout();
            this.SuspendLayout();
            // 
            // headerPanel
            // 
            this.headerPanel.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(240)))), ((int)(((byte)(240)))), ((int)(((byte)(240)))));
            this.headerPanel.Controls.Add(this.lblAcquisto);
            this.headerPanel.Controls.Add(this.lblClienteNome);
            this.headerPanel.Controls.Add(this.lblTitolo);
            this.headerPanel.Dock = System.Windows.Forms.DockStyle.Top;
            this.headerPanel.Location = new System.Drawing.Point(0, 0);
            this.headerPanel.Name = "headerPanel";
            this.headerPanel.Size = new System.Drawing.Size(584, 51);
            this.headerPanel.TabIndex = 0;
            // 
            // lblAcquisto
            // 
            this.lblAcquisto.AutoSize = true;
            this.lblAcquisto.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblAcquisto.Location = new System.Drawing.Point(14, 58);
            this.lblAcquisto.Name = "lblAcquisto";
            this.lblAcquisto.Size = new System.Drawing.Size(128, 15);
            this.lblAcquisto.TabIndex = 2;
            this.lblAcquisto.Text = "Acquisto da pianificare";
            this.lblAcquisto.Visible = false;
            // 
            // lblClienteNome
            // 
            this.lblClienteNome.AutoSize = true;
            this.lblClienteNome.Font = new System.Drawing.Font("Segoe UI", 11.25F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblClienteNome.ForeColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(122)))), ((int)(((byte)(204)))));
            this.lblClienteNome.Location = new System.Drawing.Point(230, 15);
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
            this.lblTitolo.Size = new System.Drawing.Size(203, 21);
            this.lblTitolo.TabIndex = 0;
            this.lblTitolo.Text = "PIANIFICAZIONE LEZIONI";
            // 
            // panelMain
            // 
            this.panelMain.BackColor = System.Drawing.Color.White;
            this.panelMain.Controls.Add(this.lstAcquistiDaPianificare);
            this.panelMain.Controls.Add(this.lblAcquistiDaPianificare);
            this.panelMain.Controls.Add(this.cmbGiorno);
            this.panelMain.Controls.Add(this.lblGiorno);
            this.panelMain.Controls.Add(this.cmbFrequenza);
            this.panelMain.Controls.Add(this.lblFrequenza);
            this.panelMain.Controls.Add(this.cmbStrumento);
            this.panelMain.Controls.Add(this.lblStrumento);
            this.panelMain.Controls.Add(this.cmbInsegnante);
            this.panelMain.Controls.Add(this.lblInsegnante);
            this.panelMain.Controls.Add(this.nudNumeroSettimane);
            this.panelMain.Controls.Add(this.lblNumeroLezioni);
            this.panelMain.Controls.Add(this.nudDurata);
            this.panelMain.Controls.Add(this.lblDurata);
            this.panelMain.Controls.Add(this.dtpOraInizio);
            this.panelMain.Controls.Add(this.lblOraInizio);
            this.panelMain.Controls.Add(this.dtpDataInizio);
            this.panelMain.Controls.Add(this.lblDataInizio);
            this.panelMain.Dock = System.Windows.Forms.DockStyle.Fill;
            this.panelMain.Location = new System.Drawing.Point(0, 51);
            this.panelMain.Name = "panelMain";
            this.panelMain.Padding = new System.Windows.Forms.Padding(20);
            this.panelMain.Size = new System.Drawing.Size(584, 501);
            this.panelMain.TabIndex = 1;
            // 
            // lstAcquistiDaPianificare
            // 
            this.lstAcquistiDaPianificare.BackColor = System.Drawing.Color.White;
            this.lstAcquistiDaPianificare.BorderStyle = System.Windows.Forms.BorderStyle.FixedSingle;
            this.lstAcquistiDaPianificare.Font = new System.Drawing.Font("Segoe UI", 9.75F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lstAcquistiDaPianificare.HideSelection = false;
            this.lstAcquistiDaPianificare.Location = new System.Drawing.Point(23, 40);
            this.lstAcquistiDaPianificare.Name = "lstAcquistiDaPianificare";
            this.lstAcquistiDaPianificare.Size = new System.Drawing.Size(538, 50);
            this.lstAcquistiDaPianificare.TabIndex = 18;
            this.lstAcquistiDaPianificare.UseCompatibleStateImageBehavior = false;
            this.lstAcquistiDaPianificare.View = System.Windows.Forms.View.List;
            // 
            // lblAcquistiDaPianificare
            // 
            this.lblAcquistiDaPianificare.AutoSize = true;
            this.lblAcquistiDaPianificare.Font = new System.Drawing.Font("Segoe UI", 9.75F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblAcquistiDaPianificare.Location = new System.Drawing.Point(23, 20);
            this.lblAcquistiDaPianificare.Name = "lblAcquistiDaPianificare";
            this.lblAcquistiDaPianificare.Size = new System.Drawing.Size(150, 17);
            this.lblAcquistiDaPianificare.TabIndex = 17;
            this.lblAcquistiDaPianificare.Text = "Acquisti da pianificare:";
            // 
            // cmbGiorno
            // 
            this.cmbGiorno.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbGiorno.FormattingEnabled = true;
            this.cmbGiorno.Location = new System.Drawing.Point(170, 449);
            this.cmbGiorno.Name = "cmbGiorno";
            this.cmbGiorno.Size = new System.Drawing.Size(140, 25);
            this.cmbGiorno.TabIndex = 16;
            this.cmbGiorno.Visible = false;
            // 
            // lblGiorno
            // 
            this.lblGiorno.AutoSize = true;
            this.lblGiorno.Location = new System.Drawing.Point(23, 452);
            this.lblGiorno.Name = "lblGiorno";
            this.lblGiorno.Size = new System.Drawing.Size(51, 17);
            this.lblGiorno.TabIndex = 15;
            this.lblGiorno.Text = "Giorno:";
            this.lblGiorno.Visible = false;
            // 
            // cmbFrequenza
            // 
            this.cmbFrequenza.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbFrequenza.FormattingEnabled = true;
            this.cmbFrequenza.Location = new System.Drawing.Point(170, 264);
            this.cmbFrequenza.Name = "cmbFrequenza";
            this.cmbFrequenza.Size = new System.Drawing.Size(250, 25);
            this.cmbFrequenza.TabIndex = 14;
            // 
            // lblFrequenza
            // 
            this.lblFrequenza.AutoSize = true;
            this.lblFrequenza.Location = new System.Drawing.Point(23, 267);
            this.lblFrequenza.Name = "lblFrequenza";
            this.lblFrequenza.Size = new System.Drawing.Size(71, 17);
            this.lblFrequenza.TabIndex = 13;
            this.lblFrequenza.Text = "Frequenza:";
            // 
            // cmbStrumento
            // 
            this.cmbStrumento.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbStrumento.FormattingEnabled = true;
            this.cmbStrumento.Location = new System.Drawing.Point(170, 224);
            this.cmbStrumento.Name = "cmbStrumento";
            this.cmbStrumento.Size = new System.Drawing.Size(250, 25);
            this.cmbStrumento.TabIndex = 12;
            // 
            // lblStrumento
            // 
            this.lblStrumento.AutoSize = true;
            this.lblStrumento.Location = new System.Drawing.Point(23, 227);
            this.lblStrumento.Name = "lblStrumento";
            this.lblStrumento.Size = new System.Drawing.Size(71, 17);
            this.lblStrumento.TabIndex = 11;
            this.lblStrumento.Text = "Strumento:";
            // 
            // cmbInsegnante
            // 
            this.cmbInsegnante.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbInsegnante.FormattingEnabled = true;
            this.cmbInsegnante.Location = new System.Drawing.Point(170, 187);
            this.cmbInsegnante.Name = "cmbInsegnante";
            this.cmbInsegnante.Size = new System.Drawing.Size(250, 25);
            this.cmbInsegnante.TabIndex = 10;
            // 
            // lblInsegnante
            // 
            this.lblInsegnante.AutoSize = true;
            this.lblInsegnante.Location = new System.Drawing.Point(23, 190);
            this.lblInsegnante.Name = "lblInsegnante";
            this.lblInsegnante.Size = new System.Drawing.Size(74, 17);
            this.lblInsegnante.TabIndex = 9;
            this.lblInsegnante.Text = "Insegnante:";
            // 
            // nudNumeroSettimane
            // 
            this.nudNumeroSettimane.Location = new System.Drawing.Point(420, 145);
            this.nudNumeroSettimane.Minimum = new decimal(new int[] {
            1,
            0,
            0,
            0});
            this.nudNumeroSettimane.Name = "nudNumeroSettimane";
            this.nudNumeroSettimane.Size = new System.Drawing.Size(80, 25);
            this.nudNumeroSettimane.TabIndex = 8;
            this.nudNumeroSettimane.Value = new decimal(new int[] {
            10,
            0,
            0,
            0});
            // 
            // lblNumeroLezioni
            // 
            this.lblNumeroLezioni.AutoSize = true;
            this.lblNumeroLezioni.Location = new System.Drawing.Point(320, 147);
            this.lblNumeroLezioni.Name = "lblNumeroLezioni";
            this.lblNumeroLezioni.Size = new System.Drawing.Size(86, 17);
            this.lblNumeroLezioni.TabIndex = 7;
            this.lblNumeroLezioni.Text = "Num. Lezioni:";
            // 
            // nudDurata
            // 
            this.nudDurata.Location = new System.Drawing.Point(170, 145);
            this.nudDurata.Maximum = new decimal(new int[] {
            240,
            0,
            0,
            0});
            this.nudDurata.Minimum = new decimal(new int[] {
            15,
            0,
            0,
            0});
            this.nudDurata.Name = "nudDurata";
            this.nudDurata.Size = new System.Drawing.Size(80, 25);
            this.nudDurata.TabIndex = 6;
            this.nudDurata.Value = new decimal(new int[] {
            60,
            0,
            0,
            0});
            // 
            // lblDurata
            // 
            this.lblDurata.AutoSize = true;
            this.lblDurata.Location = new System.Drawing.Point(23, 147);
            this.lblDurata.Name = "lblDurata";
            this.lblDurata.Size = new System.Drawing.Size(97, 17);
            this.lblDurata.TabIndex = 5;
            this.lblDurata.Text = "Durata (minuti):";
            // 
            // dtpOraInizio
            // 
            this.dtpOraInizio.Format = System.Windows.Forms.DateTimePickerFormat.Time;
            this.dtpOraInizio.Location = new System.Drawing.Point(420, 110);
            this.dtpOraInizio.Name = "dtpOraInizio";
            this.dtpOraInizio.Size = new System.Drawing.Size(110, 25);
            this.dtpOraInizio.TabIndex = 4;
            // 
            // lblOraInizio
            // 
            this.lblOraInizio.AutoSize = true;
            this.lblOraInizio.Location = new System.Drawing.Point(320, 115);
            this.lblOraInizio.Name = "lblOraInizio";
            this.lblOraInizio.Size = new System.Drawing.Size(67, 17);
            this.lblOraInizio.TabIndex = 3;
            this.lblOraInizio.Text = "Ora inizio:";
            // 
            // dtpDataInizio
            // 
            this.dtpDataInizio.Format = System.Windows.Forms.DateTimePickerFormat.Short;
            this.dtpDataInizio.Location = new System.Drawing.Point(170, 110);
            this.dtpDataInizio.Name = "dtpDataInizio";
            this.dtpDataInizio.Size = new System.Drawing.Size(120, 25);
            this.dtpDataInizio.TabIndex = 2;
            // 
            // lblDataInizio
            // 
            this.lblDataInizio.AutoSize = true;
            this.lblDataInizio.Location = new System.Drawing.Point(23, 115);
            this.lblDataInizio.Name = "lblDataInizio";
            this.lblDataInizio.Size = new System.Drawing.Size(72, 17);
            this.lblDataInizio.TabIndex = 1;
            this.lblDataInizio.Text = "Data inizio:";
            // 
            // panelButtons
            // 
            this.panelButtons.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(240)))), ((int)(((byte)(240)))), ((int)(((byte)(240)))));
            this.panelButtons.Controls.Add(this.btnChiudi);
            this.panelButtons.Controls.Add(this.btnPianifica);
            this.panelButtons.Dock = System.Windows.Forms.DockStyle.Bottom;
            this.panelButtons.Location = new System.Drawing.Point(0, 552);
            this.panelButtons.Name = "panelButtons";
            this.panelButtons.Size = new System.Drawing.Size(584, 60);
            this.panelButtons.TabIndex = 2;
            // 
            // btnChiudi
            // 
            this.btnChiudi.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Right)));
            this.btnChiudi.Location = new System.Drawing.Point(482, 14);
            this.btnChiudi.Name = "btnChiudi";
            this.btnChiudi.Size = new System.Drawing.Size(90, 32);
            this.btnChiudi.TabIndex = 1;
            this.btnChiudi.Text = "Chiudi";
            this.btnChiudi.UseVisualStyleBackColor = true;
            this.btnChiudi.Click += new System.EventHandler(this.btnChiudi_Click);
            // 
            // btnPianifica
            // 
            this.btnPianifica.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Right)));
            this.btnPianifica.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(34)))), ((int)(((byte)(139)))), ((int)(((byte)(34)))));
            this.btnPianifica.FlatAppearance.BorderColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(64)))), ((int)(((byte)(0)))));
            this.btnPianifica.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnPianifica.Font = new System.Drawing.Font("Segoe UI", 9.75F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.btnPianifica.ForeColor = System.Drawing.Color.White;
            this.btnPianifica.Location = new System.Drawing.Point(349, 14);
            this.btnPianifica.Name = "btnPianifica";
            this.btnPianifica.Size = new System.Drawing.Size(127, 32);
            this.btnPianifica.TabIndex = 0;
            this.btnPianifica.Text = "Pianifica Lezioni";
            this.btnPianifica.UseVisualStyleBackColor = false;
            this.btnPianifica.Click += new System.EventHandler(this.btnPianifica_Click);
            // 
            // PianificazioneForm
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(7F, 17F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.ClientSize = new System.Drawing.Size(584, 612);
            this.Controls.Add(this.panelMain);
            this.Controls.Add(this.panelButtons);
            this.Controls.Add(this.headerPanel);
            this.Font = new System.Drawing.Font("Segoe UI", 9.75F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.FormBorderStyle = System.Windows.Forms.FormBorderStyle.FixedDialog;
            this.Margin = new System.Windows.Forms.Padding(3, 4, 3, 4);
            this.MaximizeBox = false;
            this.MinimizeBox = false;
            this.Name = "PianificazioneForm";
            this.StartPosition = System.Windows.Forms.FormStartPosition.CenterParent;
            this.Text = "Pianificazione Lezioni";
            this.headerPanel.ResumeLayout(false);
            this.headerPanel.PerformLayout();
            this.panelMain.ResumeLayout(false);
            this.panelMain.PerformLayout();
            ((System.ComponentModel.ISupportInitialize)(this.nudNumeroSettimane)).EndInit();
            ((System.ComponentModel.ISupportInitialize)(this.nudDurata)).EndInit();
            this.panelButtons.ResumeLayout(false);
            this.ResumeLayout(false);

        }

        #endregion

        private System.Windows.Forms.Panel headerPanel;
        private System.Windows.Forms.Label lblTitolo;
        private System.Windows.Forms.Panel panelMain;
        private System.Windows.Forms.Panel panelButtons;
        private System.Windows.Forms.Label lblClienteNome;
        private System.Windows.Forms.Label lblAcquisto;
        private System.Windows.Forms.DateTimePicker dtpDataInizio;
        private System.Windows.Forms.Label lblDataInizio;
        private System.Windows.Forms.DateTimePicker dtpOraInizio;
        private System.Windows.Forms.Label lblOraInizio;
        private System.Windows.Forms.NumericUpDown nudDurata;
        private System.Windows.Forms.Label lblDurata;
        private System.Windows.Forms.NumericUpDown nudNumeroSettimane;
        private System.Windows.Forms.Label lblNumeroLezioni;
        private System.Windows.Forms.ComboBox cmbInsegnante;
        private System.Windows.Forms.Label lblInsegnante;
        private System.Windows.Forms.ComboBox cmbStrumento;
        private System.Windows.Forms.Label lblStrumento;
        private System.Windows.Forms.ComboBox cmbFrequenza;
        private System.Windows.Forms.Label lblFrequenza;
        private System.Windows.Forms.ComboBox cmbGiorno;
        private System.Windows.Forms.Label lblGiorno;
        private System.Windows.Forms.Label lblAcquistiDaPianificare;
        private System.Windows.Forms.ListView lstAcquistiDaPianificare;
        private System.Windows.Forms.Button btnChiudi;
        private System.Windows.Forms.Button btnPianifica;
    }
}