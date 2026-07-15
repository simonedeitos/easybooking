namespace EasyBooking
{
    partial class AcquistiControl
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

        #region Component Designer generated code

        /// <summary> 
        /// Required method for Designer support - do not modify 
        /// the contents of this method with the code editor.
        /// </summary>
        private void InitializeComponent()
        {
            this.tabControlAcquisti = new System.Windows.Forms.TabControl();
            this.tabAcquisti = new System.Windows.Forms.TabPage();
            this.lblAcquistiDaFatturare = new System.Windows.Forms.Label();
            this.lblRicercaAcquisti = new System.Windows.Forms.Label();
            this.txtRicercaAcquisti = new System.Windows.Forms.TextBox();
            this.dgvAcquisti = new System.Windows.Forms.DataGridView();
            this.panelAcquistiRight = new System.Windows.Forms.Panel();
            this.grpDettaglioAcquisto = new System.Windows.Forms.GroupBox();
            this.label1 = new System.Windows.Forms.Label();
            this.cmbStrumentoFiltro = new System.Windows.Forms.ComboBox();
            this.lblStrumentoFiltro = new System.Windows.Forms.Label();
            this.btnPianifica = new System.Windows.Forms.Button();
            this.txtNoteAcquisto = new System.Windows.Forms.TextBox();
            this.lblNoteAcquisto = new System.Windows.Forms.Label();
            this.txtNumeroFattura = new System.Windows.Forms.TextBox();
            this.lblNumeroFattura = new System.Windows.Forms.Label();
            this.chkPianificato = new System.Windows.Forms.CheckBox();
            this.cmbStatoPagamento = new System.Windows.Forms.ComboBox();
            this.lblStatoPagamento = new System.Windows.Forms.Label();
            this.txtImportoPagato = new System.Windows.Forms.TextBox();
            this.lblImportoPagato = new System.Windows.Forms.Label();
            this.cmbTipoSconto = new System.Windows.Forms.ComboBox();
            this.txtSconto = new System.Windows.Forms.TextBox();
            this.lblSconto = new System.Windows.Forms.Label();
            this.txtCostoPacchetto = new System.Windows.Forms.TextBox();
            this.lblCostoPacchetto = new System.Windows.Forms.Label();
            this.cmbPacchetti = new System.Windows.Forms.ComboBox();
            this.lblPacchetto = new System.Windows.Forms.Label();
            this.cmbClienti = new System.Windows.Forms.ComboBox();
            this.lblCliente = new System.Windows.Forms.Label();
            this.dtpDataAcquisto = new System.Windows.Forms.DateTimePicker();
            this.lblDataAcquisto = new System.Windows.Forms.Label();
            this.btnEliminaAcquisto = new System.Windows.Forms.Button();
            this.btnSalvaAcquisto = new System.Windows.Forms.Button();
            this.btnNuovoAcquisto = new System.Windows.Forms.Button();
            this.tabPacchetti = new System.Windows.Forms.TabPage();
            this.lblRicercaPacchetti = new System.Windows.Forms.Label();
            this.txtRicercaPacchetti = new System.Windows.Forms.TextBox();
            this.dgvPacchetti = new System.Windows.Forms.DataGridView();
            this.panelPacchettiRight = new System.Windows.Forms.Panel();
            this.grpDettaglioPacchetto = new System.Windows.Forms.GroupBox();
            this.checkCoppia = new System.Windows.Forms.CheckBox();
            this.cmbStrumento = new System.Windows.Forms.ComboBox();
            this.lblStrumento = new System.Windows.Forms.Label();
            this.txtPrezzo = new System.Windows.Forms.TextBox();
            this.lblPrezzo = new System.Windows.Forms.Label();
            this.cmbFrequenza = new System.Windows.Forms.ComboBox();
            this.lblFrequenza = new System.Windows.Forms.Label();
            this.nudDurataMinuti = new System.Windows.Forms.NumericUpDown();
            this.lblDurata = new System.Windows.Forms.Label();
            this.nudNumeroLezioni = new System.Windows.Forms.NumericUpDown();
            this.lblNumeroLezioni = new System.Windows.Forms.Label();
            this.txtDescrizione = new System.Windows.Forms.TextBox();
            this.lblDescrizione = new System.Windows.Forms.Label();
            this.txtNomePacchetto = new System.Windows.Forms.TextBox();
            this.lblNomePacchetto = new System.Windows.Forms.Label();
            this.btnEliminaPacchetto = new System.Windows.Forms.Button();
            this.btnSalvaPacchetto = new System.Windows.Forms.Button();
            this.btnNuovoPacchetto = new System.Windows.Forms.Button();
            this.tabControlAcquisti.SuspendLayout();
            this.tabAcquisti.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)(this.dgvAcquisti)).BeginInit();
            this.panelAcquistiRight.SuspendLayout();
            this.grpDettaglioAcquisto.SuspendLayout();
            this.tabPacchetti.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)(this.dgvPacchetti)).BeginInit();
            this.panelPacchettiRight.SuspendLayout();
            this.grpDettaglioPacchetto.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)(this.nudDurataMinuti)).BeginInit();
            ((System.ComponentModel.ISupportInitialize)(this.nudNumeroLezioni)).BeginInit();
            this.SuspendLayout();
            // 
            // tabControlAcquisti
            // 
            this.tabControlAcquisti.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.tabControlAcquisti.Controls.Add(this.tabAcquisti);
            this.tabControlAcquisti.Controls.Add(this.tabPacchetti);
            this.tabControlAcquisti.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.tabControlAcquisti.Location = new System.Drawing.Point(3, 3);
            this.tabControlAcquisti.Name = "tabControlAcquisti";
            this.tabControlAcquisti.SelectedIndex = 0;
            this.tabControlAcquisti.Size = new System.Drawing.Size(907, 594);
            this.tabControlAcquisti.TabIndex = 0;
            // 
            // tabAcquisti
            // 
            this.tabAcquisti.Controls.Add(this.lblAcquistiDaFatturare);
            this.tabAcquisti.Controls.Add(this.lblRicercaAcquisti);
            this.tabAcquisti.Controls.Add(this.txtRicercaAcquisti);
            this.tabAcquisti.Controls.Add(this.dgvAcquisti);
            this.tabAcquisti.Controls.Add(this.panelAcquistiRight);
            this.tabAcquisti.Location = new System.Drawing.Point(4, 24);
            this.tabAcquisti.Name = "tabAcquisti";
            this.tabAcquisti.Padding = new System.Windows.Forms.Padding(3);
            this.tabAcquisti.Size = new System.Drawing.Size(899, 566);
            this.tabAcquisti.TabIndex = 1;
            this.tabAcquisti.Text = "Acquisti";
            this.tabAcquisti.UseVisualStyleBackColor = true;
            // 
            // lblAcquistiDaFatturare
            // 
            this.lblAcquistiDaFatturare.Font = new System.Drawing.Font("Segoe UI", 12F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblAcquistiDaFatturare.ForeColor = System.Drawing.Color.Red;
            this.lblAcquistiDaFatturare.Location = new System.Drawing.Point(289, 10);
            this.lblAcquistiDaFatturare.Name = "lblAcquistiDaFatturare";
            this.lblAcquistiDaFatturare.Size = new System.Drawing.Size(237, 23);
            this.lblAcquistiDaFatturare.TabIndex = 8;
            this.lblAcquistiDaFatturare.Text = "0 Acquisti da fatturare";
            this.lblAcquistiDaFatturare.Visible = false;
            // 
            // lblRicercaAcquisti
            // 
            this.lblRicercaAcquisti.AutoSize = true;
            this.lblRicercaAcquisti.Location = new System.Drawing.Point(8, 13);
            this.lblRicercaAcquisti.Name = "lblRicercaAcquisti";
            this.lblRicercaAcquisti.Size = new System.Drawing.Size(48, 15);
            this.lblRicercaAcquisti.TabIndex = 0;
            this.lblRicercaAcquisti.Text = "Ricerca:";
            // 
            // txtRicercaAcquisti
            // 
            this.txtRicercaAcquisti.Location = new System.Drawing.Point(63, 10);
            this.txtRicercaAcquisti.Name = "txtRicercaAcquisti";
            this.txtRicercaAcquisti.Size = new System.Drawing.Size(200, 23);
            this.txtRicercaAcquisti.TabIndex = 1;
            this.txtRicercaAcquisti.TextChanged += new System.EventHandler(this.txtRicercaAcquisti_TextChanged);
            // 
            // dgvAcquisti
            // 
            this.dgvAcquisti.AllowUserToAddRows = false;
            this.dgvAcquisti.AllowUserToDeleteRows = false;
            this.dgvAcquisti.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.dgvAcquisti.BackgroundColor = System.Drawing.SystemColors.Control;
            this.dgvAcquisti.ColumnHeadersHeightSizeMode = System.Windows.Forms.DataGridViewColumnHeadersHeightSizeMode.AutoSize;
            this.dgvAcquisti.Location = new System.Drawing.Point(8, 39);
            this.dgvAcquisti.MultiSelect = false;
            this.dgvAcquisti.Name = "dgvAcquisti";
            this.dgvAcquisti.ReadOnly = true;
            this.dgvAcquisti.RowHeadersVisible = false;
            this.dgvAcquisti.SelectionMode = System.Windows.Forms.DataGridViewSelectionMode.FullRowSelect;
            this.dgvAcquisti.Size = new System.Drawing.Size(463, 521);
            this.dgvAcquisti.TabIndex = 2;
            this.dgvAcquisti.CellClick += new System.Windows.Forms.DataGridViewCellEventHandler(this.dgvAcquisti_CellClick);
            // 
            // panelAcquistiRight
            // 
            this.panelAcquistiRight.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.panelAcquistiRight.Controls.Add(this.grpDettaglioAcquisto);
            this.panelAcquistiRight.Controls.Add(this.btnEliminaAcquisto);
            this.panelAcquistiRight.Controls.Add(this.btnSalvaAcquisto);
            this.panelAcquistiRight.Controls.Add(this.btnNuovoAcquisto);
            this.panelAcquistiRight.Location = new System.Drawing.Point(477, 6);
            this.panelAcquistiRight.Name = "panelAcquistiRight";
            this.panelAcquistiRight.Size = new System.Drawing.Size(414, 554);
            this.panelAcquistiRight.TabIndex = 7;
            // 
            // grpDettaglioAcquisto
            // 
            this.grpDettaglioAcquisto.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.grpDettaglioAcquisto.Controls.Add(this.label1);
            this.grpDettaglioAcquisto.Controls.Add(this.cmbStrumentoFiltro);
            this.grpDettaglioAcquisto.Controls.Add(this.lblStrumentoFiltro);
            this.grpDettaglioAcquisto.Controls.Add(this.btnPianifica);
            this.grpDettaglioAcquisto.Controls.Add(this.txtNoteAcquisto);
            this.grpDettaglioAcquisto.Controls.Add(this.lblNoteAcquisto);
            this.grpDettaglioAcquisto.Controls.Add(this.txtNumeroFattura);
            this.grpDettaglioAcquisto.Controls.Add(this.lblNumeroFattura);
            this.grpDettaglioAcquisto.Controls.Add(this.chkPianificato);
            this.grpDettaglioAcquisto.Controls.Add(this.cmbStatoPagamento);
            this.grpDettaglioAcquisto.Controls.Add(this.lblStatoPagamento);
            this.grpDettaglioAcquisto.Controls.Add(this.txtImportoPagato);
            this.grpDettaglioAcquisto.Controls.Add(this.lblImportoPagato);
            this.grpDettaglioAcquisto.Controls.Add(this.cmbTipoSconto);
            this.grpDettaglioAcquisto.Controls.Add(this.txtSconto);
            this.grpDettaglioAcquisto.Controls.Add(this.lblSconto);
            this.grpDettaglioAcquisto.Controls.Add(this.txtCostoPacchetto);
            this.grpDettaglioAcquisto.Controls.Add(this.lblCostoPacchetto);
            this.grpDettaglioAcquisto.Controls.Add(this.cmbPacchetti);
            this.grpDettaglioAcquisto.Controls.Add(this.lblPacchetto);
            this.grpDettaglioAcquisto.Controls.Add(this.cmbClienti);
            this.grpDettaglioAcquisto.Controls.Add(this.lblCliente);
            this.grpDettaglioAcquisto.Controls.Add(this.dtpDataAcquisto);
            this.grpDettaglioAcquisto.Controls.Add(this.lblDataAcquisto);
            this.grpDettaglioAcquisto.Location = new System.Drawing.Point(0, 33);
            this.grpDettaglioAcquisto.Name = "grpDettaglioAcquisto";
            this.grpDettaglioAcquisto.Size = new System.Drawing.Size(414, 482);
            this.grpDettaglioAcquisto.TabIndex = 3;
            this.grpDettaglioAcquisto.TabStop = false;
            this.grpDettaglioAcquisto.Text = "Dettaglio Acquisto";
            // 
            // label1
            // 
            this.label1.AutoSize = true;
            this.label1.Location = new System.Drawing.Point(6, 133);
            this.label1.Name = "label1";
            this.label1.Size = new System.Drawing.Size(63, 15);
            this.label1.TabIndex = 23;
            this.label1.Text = "Pacchetto:";
            // 
            // cmbStrumentoFiltro
            // 
            this.cmbStrumentoFiltro.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbStrumentoFiltro.FormattingEnabled = true;
            this.cmbStrumentoFiltro.Location = new System.Drawing.Point(105, 101);
            this.cmbStrumentoFiltro.Name = "cmbStrumentoFiltro";
            this.cmbStrumentoFiltro.Size = new System.Drawing.Size(301, 23);
            this.cmbStrumentoFiltro.TabIndex = 22;
            this.cmbStrumentoFiltro.SelectedIndexChanged += new System.EventHandler(this.cmbStrumentoFiltro_SelectedIndexChanged);
            // 
            // lblStrumentoFiltro
            // 
            this.lblStrumentoFiltro.AutoSize = true;
            this.lblStrumentoFiltro.Location = new System.Drawing.Point(6, 104);
            this.lblStrumentoFiltro.Name = "lblStrumentoFiltro";
            this.lblStrumentoFiltro.Size = new System.Drawing.Size(66, 15);
            this.lblStrumentoFiltro.TabIndex = 21;
            this.lblStrumentoFiltro.Text = "Strumento:";
            // 
            // btnPianifica
            // 
            this.btnPianifica.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Right)));
            this.btnPianifica.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(255)))), ((int)(((byte)(128)))), ((int)(((byte)(0)))));
            this.btnPianifica.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnPianifica.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.btnPianifica.ForeColor = System.Drawing.Color.White;
            this.btnPianifica.Location = new System.Drawing.Point(220, 443);
            this.btnPianifica.Name = "btnPianifica";
            this.btnPianifica.Size = new System.Drawing.Size(186, 33);
            this.btnPianifica.TabIndex = 20;
            this.btnPianifica.Text = "Pianifica Lezioni";
            this.btnPianifica.UseVisualStyleBackColor = false;
            this.btnPianifica.Click += new System.EventHandler(this.btnPianifica_Click);
            // 
            // txtNoteAcquisto
            // 
            this.txtNoteAcquisto.Location = new System.Drawing.Point(106, 338);
            this.txtNoteAcquisto.Multiline = true;
            this.txtNoteAcquisto.Name = "txtNoteAcquisto";
            this.txtNoteAcquisto.Size = new System.Drawing.Size(250, 60);
            this.txtNoteAcquisto.TabIndex = 19;
            // 
            // lblNoteAcquisto
            // 
            this.lblNoteAcquisto.AutoSize = true;
            this.lblNoteAcquisto.Location = new System.Drawing.Point(6, 341);
            this.lblNoteAcquisto.Name = "lblNoteAcquisto";
            this.lblNoteAcquisto.Size = new System.Drawing.Size(36, 15);
            this.lblNoteAcquisto.TabIndex = 18;
            this.lblNoteAcquisto.Text = "Note:";
            // 
            // txtNumeroFattura
            // 
            this.txtNumeroFattura.Location = new System.Drawing.Point(106, 308);
            this.txtNumeroFattura.Name = "txtNumeroFattura";
            this.txtNumeroFattura.Size = new System.Drawing.Size(150, 23);
            this.txtNumeroFattura.TabIndex = 17;
            // 
            // lblNumeroFattura
            // 
            this.lblNumeroFattura.AutoSize = true;
            this.lblNumeroFattura.Location = new System.Drawing.Point(6, 311);
            this.lblNumeroFattura.Name = "lblNumeroFattura";
            this.lblNumeroFattura.Size = new System.Drawing.Size(94, 15);
            this.lblNumeroFattura.TabIndex = 16;
            this.lblNumeroFattura.Text = "Numero Fattura:";
            // 
            // chkPianificato
            // 
            this.chkPianificato.AutoSize = true;
            this.chkPianificato.Enabled = false;
            this.chkPianificato.Location = new System.Drawing.Point(106, 281);
            this.chkPianificato.Name = "chkPianificato";
            this.chkPianificato.Size = new System.Drawing.Size(82, 19);
            this.chkPianificato.TabIndex = 15;
            this.chkPianificato.Text = "Pianificato";
            this.chkPianificato.UseVisualStyleBackColor = true;
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
            this.cmbStatoPagamento.Location = new System.Drawing.Point(105, 248);
            this.cmbStatoPagamento.Name = "cmbStatoPagamento";
            this.cmbStatoPagamento.Size = new System.Drawing.Size(158, 23);
            this.cmbStatoPagamento.TabIndex = 14;
            // 
            // lblStatoPagamento
            // 
            this.lblStatoPagamento.AutoSize = true;
            this.lblStatoPagamento.Location = new System.Drawing.Point(6, 251);
            this.lblStatoPagamento.Name = "lblStatoPagamento";
            this.lblStatoPagamento.Size = new System.Drawing.Size(37, 15);
            this.lblStatoPagamento.TabIndex = 13;
            this.lblStatoPagamento.Text = "Stato:";
            // 
            // txtImportoPagato
            // 
            this.txtImportoPagato.Enabled = false;
            this.txtImportoPagato.Location = new System.Drawing.Point(105, 218);
            this.txtImportoPagato.Name = "txtImportoPagato";
            this.txtImportoPagato.Size = new System.Drawing.Size(158, 23);
            this.txtImportoPagato.TabIndex = 12;
            // 
            // lblImportoPagato
            // 
            this.lblImportoPagato.AutoSize = true;
            this.lblImportoPagato.Location = new System.Drawing.Point(6, 221);
            this.lblImportoPagato.Name = "lblImportoPagato";
            this.lblImportoPagato.Size = new System.Drawing.Size(93, 15);
            this.lblImportoPagato.TabIndex = 11;
            this.lblImportoPagato.Text = "Importo Pagato:";
            // 
            // cmbTipoSconto
            // 
            this.cmbTipoSconto.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbTipoSconto.FormattingEnabled = true;
            this.cmbTipoSconto.Location = new System.Drawing.Point(224, 188);
            this.cmbTipoSconto.Name = "cmbTipoSconto";
            this.cmbTipoSconto.Size = new System.Drawing.Size(39, 23);
            this.cmbTipoSconto.TabIndex = 10;
            // 
            // txtSconto
            // 
            this.txtSconto.Location = new System.Drawing.Point(105, 188);
            this.txtSconto.Name = "txtSconto";
            this.txtSconto.Size = new System.Drawing.Size(113, 23);
            this.txtSconto.TabIndex = 9;
            this.txtSconto.Text = "0";
            // 
            // lblSconto
            // 
            this.lblSconto.AutoSize = true;
            this.lblSconto.Location = new System.Drawing.Point(6, 191);
            this.lblSconto.Name = "lblSconto";
            this.lblSconto.Size = new System.Drawing.Size(47, 15);
            this.lblSconto.TabIndex = 8;
            this.lblSconto.Text = "Sconto:";
            // 
            // txtCostoPacchetto
            // 
            this.txtCostoPacchetto.Enabled = false;
            this.txtCostoPacchetto.Location = new System.Drawing.Point(105, 159);
            this.txtCostoPacchetto.Name = "txtCostoPacchetto";
            this.txtCostoPacchetto.Size = new System.Drawing.Size(113, 23);
            this.txtCostoPacchetto.TabIndex = 7;
            // 
            // lblCostoPacchetto
            // 
            this.lblCostoPacchetto.AutoSize = true;
            this.lblCostoPacchetto.Location = new System.Drawing.Point(6, 162);
            this.lblCostoPacchetto.Name = "lblCostoPacchetto";
            this.lblCostoPacchetto.Size = new System.Drawing.Size(58, 15);
            this.lblCostoPacchetto.TabIndex = 6;
            this.lblCostoPacchetto.Text = "Costo (€):";
            // 
            // cmbPacchetti
            // 
            this.cmbPacchetti.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbPacchetti.FormattingEnabled = true;
            this.cmbPacchetti.Location = new System.Drawing.Point(105, 130);
            this.cmbPacchetti.Name = "cmbPacchetti";
            this.cmbPacchetti.Size = new System.Drawing.Size(301, 23);
            this.cmbPacchetti.TabIndex = 5;
            this.cmbPacchetti.SelectedIndexChanged += new System.EventHandler(this.cmbPacchetti_SelectedIndexChanged);
            // 
            // lblPacchetto
            // 
            this.lblPacchetto.AutoSize = true;
            this.lblPacchetto.Location = new System.Drawing.Point(6, 162);
            this.lblPacchetto.Name = "lblPacchetto";
            this.lblPacchetto.Size = new System.Drawing.Size(63, 15);
            this.lblPacchetto.TabIndex = 4;
            this.lblPacchetto.Text = "Pacchetto:";
            // 
            // cmbClienti
            // 
            this.cmbClienti.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbClienti.FormattingEnabled = true;
            this.cmbClienti.Location = new System.Drawing.Point(105, 71);
            this.cmbClienti.Name = "cmbClienti";
            this.cmbClienti.Size = new System.Drawing.Size(301, 23);
            this.cmbClienti.TabIndex = 3;
            // 
            // lblCliente
            // 
            this.lblCliente.AutoSize = true;
            this.lblCliente.Location = new System.Drawing.Point(6, 74);
            this.lblCliente.Name = "lblCliente";
            this.lblCliente.Size = new System.Drawing.Size(47, 15);
            this.lblCliente.TabIndex = 2;
            this.lblCliente.Text = "Cliente:";
            // 
            // dtpDataAcquisto
            // 
            this.dtpDataAcquisto.Format = System.Windows.Forms.DateTimePickerFormat.Short;
            this.dtpDataAcquisto.Location = new System.Drawing.Point(105, 42);
            this.dtpDataAcquisto.Name = "dtpDataAcquisto";
            this.dtpDataAcquisto.Size = new System.Drawing.Size(120, 23);
            this.dtpDataAcquisto.TabIndex = 1;
            // 
            // lblDataAcquisto
            // 
            this.lblDataAcquisto.AutoSize = true;
            this.lblDataAcquisto.Location = new System.Drawing.Point(6, 44);
            this.lblDataAcquisto.Name = "lblDataAcquisto";
            this.lblDataAcquisto.Size = new System.Drawing.Size(84, 15);
            this.lblDataAcquisto.TabIndex = 0;
            this.lblDataAcquisto.Text = "Data Acquisto:";
            // 
            // btnEliminaAcquisto
            // 
            this.btnEliminaAcquisto.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Right)));
            this.btnEliminaAcquisto.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(204)))), ((int)(((byte)(0)))), ((int)(((byte)(0)))));
            this.btnEliminaAcquisto.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnEliminaAcquisto.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.btnEliminaAcquisto.ForeColor = System.Drawing.Color.White;
            this.btnEliminaAcquisto.Location = new System.Drawing.Point(316, 521);
            this.btnEliminaAcquisto.Name = "btnEliminaAcquisto";
            this.btnEliminaAcquisto.Size = new System.Drawing.Size(90, 33);
            this.btnEliminaAcquisto.TabIndex = 6;
            this.btnEliminaAcquisto.Text = "Elimina";
            this.btnEliminaAcquisto.UseVisualStyleBackColor = false;
            this.btnEliminaAcquisto.Click += new System.EventHandler(this.btnEliminaAcquisto_Click);
            // 
            // btnSalvaAcquisto
            // 
            this.btnSalvaAcquisto.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Right)));
            this.btnSalvaAcquisto.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(164)))), ((int)(((byte)(0)))));
            this.btnSalvaAcquisto.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnSalvaAcquisto.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.btnSalvaAcquisto.ForeColor = System.Drawing.Color.White;
            this.btnSalvaAcquisto.Location = new System.Drawing.Point(220, 521);
            this.btnSalvaAcquisto.Name = "btnSalvaAcquisto";
            this.btnSalvaAcquisto.Size = new System.Drawing.Size(90, 33);
            this.btnSalvaAcquisto.TabIndex = 5;
            this.btnSalvaAcquisto.Text = "Salva";
            this.btnSalvaAcquisto.UseVisualStyleBackColor = false;
            this.btnSalvaAcquisto.Click += new System.EventHandler(this.btnSalvaAcquisto_Click);
            // 
            // btnNuovoAcquisto
            // 
            this.btnNuovoAcquisto.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Right)));
            this.btnNuovoAcquisto.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(122)))), ((int)(((byte)(204)))));
            this.btnNuovoAcquisto.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnNuovoAcquisto.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.btnNuovoAcquisto.ForeColor = System.Drawing.Color.White;
            this.btnNuovoAcquisto.Location = new System.Drawing.Point(22, 518);
            this.btnNuovoAcquisto.Name = "btnNuovoAcquisto";
            this.btnNuovoAcquisto.Size = new System.Drawing.Size(90, 33);
            this.btnNuovoAcquisto.TabIndex = 4;
            this.btnNuovoAcquisto.Text = "Nuovo";
            this.btnNuovoAcquisto.UseVisualStyleBackColor = false;
            this.btnNuovoAcquisto.Click += new System.EventHandler(this.btnNuovoAcquisto_Click);
            // 
            // tabPacchetti
            // 
            this.tabPacchetti.Controls.Add(this.lblRicercaPacchetti);
            this.tabPacchetti.Controls.Add(this.txtRicercaPacchetti);
            this.tabPacchetti.Controls.Add(this.dgvPacchetti);
            this.tabPacchetti.Controls.Add(this.panelPacchettiRight);
            this.tabPacchetti.Location = new System.Drawing.Point(4, 24);
            this.tabPacchetti.Name = "tabPacchetti";
            this.tabPacchetti.Padding = new System.Windows.Forms.Padding(3);
            this.tabPacchetti.Size = new System.Drawing.Size(899, 566);
            this.tabPacchetti.TabIndex = 0;
            this.tabPacchetti.Text = "Pacchetti";
            this.tabPacchetti.UseVisualStyleBackColor = true;
            // 
            // lblRicercaPacchetti
            // 
            this.lblRicercaPacchetti.AutoSize = true;
            this.lblRicercaPacchetti.Location = new System.Drawing.Point(8, 13);
            this.lblRicercaPacchetti.Name = "lblRicercaPacchetti";
            this.lblRicercaPacchetti.Size = new System.Drawing.Size(48, 15);
            this.lblRicercaPacchetti.TabIndex = 0;
            this.lblRicercaPacchetti.Text = "Ricerca:";
            // 
            // txtRicercaPacchetti
            // 
            this.txtRicercaPacchetti.Location = new System.Drawing.Point(63, 10);
            this.txtRicercaPacchetti.Name = "txtRicercaPacchetti";
            this.txtRicercaPacchetti.Size = new System.Drawing.Size(200, 23);
            this.txtRicercaPacchetti.TabIndex = 1;
            this.txtRicercaPacchetti.TextChanged += new System.EventHandler(this.txtRicercaPacchetti_TextChanged);
            // 
            // dgvPacchetti
            // 
            this.dgvPacchetti.AllowUserToAddRows = false;
            this.dgvPacchetti.AllowUserToDeleteRows = false;
            this.dgvPacchetti.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.dgvPacchetti.BackgroundColor = System.Drawing.SystemColors.Control;
            this.dgvPacchetti.ColumnHeadersHeightSizeMode = System.Windows.Forms.DataGridViewColumnHeadersHeightSizeMode.AutoSize;
            this.dgvPacchetti.Location = new System.Drawing.Point(8, 39);
            this.dgvPacchetti.MultiSelect = false;
            this.dgvPacchetti.Name = "dgvPacchetti";
            this.dgvPacchetti.ReadOnly = true;
            this.dgvPacchetti.RowHeadersVisible = false;
            this.dgvPacchetti.SelectionMode = System.Windows.Forms.DataGridViewSelectionMode.FullRowSelect;
            this.dgvPacchetti.Size = new System.Drawing.Size(360, 521);
            this.dgvPacchetti.TabIndex = 2;
            this.dgvPacchetti.CellClick += new System.Windows.Forms.DataGridViewCellEventHandler(this.dgvPacchetti_CellClick);
            // 
            // panelPacchettiRight
            // 
            this.panelPacchettiRight.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.panelPacchettiRight.Controls.Add(this.grpDettaglioPacchetto);
            this.panelPacchettiRight.Controls.Add(this.btnEliminaPacchetto);
            this.panelPacchettiRight.Controls.Add(this.btnSalvaPacchetto);
            this.panelPacchettiRight.Controls.Add(this.btnNuovoPacchetto);
            this.panelPacchettiRight.Location = new System.Drawing.Point(374, 6);
            this.panelPacchettiRight.Name = "panelPacchettiRight";
            this.panelPacchettiRight.Size = new System.Drawing.Size(517, 554);
            this.panelPacchettiRight.TabIndex = 7;
            // 
            // grpDettaglioPacchetto
            // 
            this.grpDettaglioPacchetto.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.grpDettaglioPacchetto.Controls.Add(this.checkCoppia);
            this.grpDettaglioPacchetto.Controls.Add(this.cmbStrumento);
            this.grpDettaglioPacchetto.Controls.Add(this.lblStrumento);
            this.grpDettaglioPacchetto.Controls.Add(this.txtPrezzo);
            this.grpDettaglioPacchetto.Controls.Add(this.lblPrezzo);
            this.grpDettaglioPacchetto.Controls.Add(this.cmbFrequenza);
            this.grpDettaglioPacchetto.Controls.Add(this.lblFrequenza);
            this.grpDettaglioPacchetto.Controls.Add(this.nudDurataMinuti);
            this.grpDettaglioPacchetto.Controls.Add(this.lblDurata);
            this.grpDettaglioPacchetto.Controls.Add(this.nudNumeroLezioni);
            this.grpDettaglioPacchetto.Controls.Add(this.lblNumeroLezioni);
            this.grpDettaglioPacchetto.Controls.Add(this.txtDescrizione);
            this.grpDettaglioPacchetto.Controls.Add(this.lblDescrizione);
            this.grpDettaglioPacchetto.Controls.Add(this.txtNomePacchetto);
            this.grpDettaglioPacchetto.Controls.Add(this.lblNomePacchetto);
            this.grpDettaglioPacchetto.Location = new System.Drawing.Point(0, 33);
            this.grpDettaglioPacchetto.Name = "grpDettaglioPacchetto";
            this.grpDettaglioPacchetto.Size = new System.Drawing.Size(517, 482);
            this.grpDettaglioPacchetto.TabIndex = 3;
            this.grpDettaglioPacchetto.TabStop = false;
            this.grpDettaglioPacchetto.Text = "Dettaglio Pacchetto";
            // 
            // checkCoppia
            // 
            this.checkCoppia.AutoSize = true;
            this.checkCoppia.Location = new System.Drawing.Point(140, 288);
            this.checkCoppia.Name = "checkCoppia";
            this.checkCoppia.Size = new System.Drawing.Size(121, 19);
            this.checkCoppia.TabIndex = 14;
            this.checkCoppia.Text = "Lezione Di Coppia";
            this.checkCoppia.UseVisualStyleBackColor = true;
            // 
            // cmbStrumento
            // 
            this.cmbStrumento.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbStrumento.FormattingEnabled = true;
            this.cmbStrumento.Location = new System.Drawing.Point(140, 247);
            this.cmbStrumento.Name = "cmbStrumento";
            this.cmbStrumento.Size = new System.Drawing.Size(200, 23);
            this.cmbStrumento.TabIndex = 13;
            // 
            // lblStrumento
            // 
            this.lblStrumento.AutoSize = true;
            this.lblStrumento.Location = new System.Drawing.Point(19, 250);
            this.lblStrumento.Name = "lblStrumento";
            this.lblStrumento.Size = new System.Drawing.Size(66, 15);
            this.lblStrumento.TabIndex = 12;
            this.lblStrumento.Text = "Strumento:";
            // 
            // txtPrezzo
            // 
            this.txtPrezzo.Location = new System.Drawing.Point(140, 217);
            this.txtPrezzo.Name = "txtPrezzo";
            this.txtPrezzo.Size = new System.Drawing.Size(100, 23);
            this.txtPrezzo.TabIndex = 11;
            // 
            // lblPrezzo
            // 
            this.lblPrezzo.AutoSize = true;
            this.lblPrezzo.Location = new System.Drawing.Point(19, 220);
            this.lblPrezzo.Name = "lblPrezzo";
            this.lblPrezzo.Size = new System.Drawing.Size(44, 15);
            this.lblPrezzo.TabIndex = 10;
            this.lblPrezzo.Text = "Prezzo:";
            // 
            // cmbFrequenza
            // 
            this.cmbFrequenza.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbFrequenza.FormattingEnabled = true;
            this.cmbFrequenza.Items.AddRange(new object[] {
            "Settimanale",
            "Bisettimanale",
            "Mensile",
            "Personalizzata"});
            this.cmbFrequenza.Location = new System.Drawing.Point(140, 187);
            this.cmbFrequenza.Name = "cmbFrequenza";
            this.cmbFrequenza.Size = new System.Drawing.Size(150, 23);
            this.cmbFrequenza.TabIndex = 9;
            // 
            // lblFrequenza
            // 
            this.lblFrequenza.AutoSize = true;
            this.lblFrequenza.Location = new System.Drawing.Point(19, 190);
            this.lblFrequenza.Name = "lblFrequenza";
            this.lblFrequenza.Size = new System.Drawing.Size(64, 15);
            this.lblFrequenza.TabIndex = 8;
            this.lblFrequenza.Text = "Frequenza:";
            // 
            // nudDurataMinuti
            // 
            this.nudDurataMinuti.Location = new System.Drawing.Point(140, 158);
            this.nudDurataMinuti.Maximum = new decimal(new int[] {
            240,
            0,
            0,
            0});
            this.nudDurataMinuti.Minimum = new decimal(new int[] {
            15,
            0,
            0,
            0});
            this.nudDurataMinuti.Name = "nudDurataMinuti";
            this.nudDurataMinuti.Size = new System.Drawing.Size(60, 23);
            this.nudDurataMinuti.TabIndex = 7;
            this.nudDurataMinuti.Value = new decimal(new int[] {
            60,
            0,
            0,
            0});
            // 
            // lblDurata
            // 
            this.lblDurata.AutoSize = true;
            this.lblDurata.Location = new System.Drawing.Point(19, 160);
            this.lblDurata.Name = "lblDurata";
            this.lblDurata.Size = new System.Drawing.Size(91, 15);
            this.lblDurata.TabIndex = 6;
            this.lblDurata.Text = "Durata (minuti):";
            // 
            // nudNumeroLezioni
            // 
            this.nudNumeroLezioni.Location = new System.Drawing.Point(140, 128);
            this.nudNumeroLezioni.Minimum = new decimal(new int[] {
            1,
            0,
            0,
            0});
            this.nudNumeroLezioni.Name = "nudNumeroLezioni";
            this.nudNumeroLezioni.Size = new System.Drawing.Size(60, 23);
            this.nudNumeroLezioni.TabIndex = 5;
            this.nudNumeroLezioni.Value = new decimal(new int[] {
            1,
            0,
            0,
            0});
            // 
            // lblNumeroLezioni
            // 
            this.lblNumeroLezioni.AutoSize = true;
            this.lblNumeroLezioni.Location = new System.Drawing.Point(19, 130);
            this.lblNumeroLezioni.Name = "lblNumeroLezioni";
            this.lblNumeroLezioni.Size = new System.Drawing.Size(94, 15);
            this.lblNumeroLezioni.TabIndex = 4;
            this.lblNumeroLezioni.Text = "Numero Lezioni:";
            // 
            // txtDescrizione
            // 
            this.txtDescrizione.Location = new System.Drawing.Point(140, 57);
            this.txtDescrizione.Multiline = true;
            this.txtDescrizione.Name = "txtDescrizione";
            this.txtDescrizione.Size = new System.Drawing.Size(369, 60);
            this.txtDescrizione.TabIndex = 3;
            // 
            // lblDescrizione
            // 
            this.lblDescrizione.AutoSize = true;
            this.lblDescrizione.Location = new System.Drawing.Point(19, 60);
            this.lblDescrizione.Name = "lblDescrizione";
            this.lblDescrizione.Size = new System.Drawing.Size(70, 15);
            this.lblDescrizione.TabIndex = 2;
            this.lblDescrizione.Text = "Descrizione:";
            // 
            // txtNomePacchetto
            // 
            this.txtNomePacchetto.Location = new System.Drawing.Point(140, 27);
            this.txtNomePacchetto.Name = "txtNomePacchetto";
            this.txtNomePacchetto.Size = new System.Drawing.Size(369, 23);
            this.txtNomePacchetto.TabIndex = 1;
            // 
            // lblNomePacchetto
            // 
            this.lblNomePacchetto.AutoSize = true;
            this.lblNomePacchetto.Location = new System.Drawing.Point(19, 30);
            this.lblNomePacchetto.Name = "lblNomePacchetto";
            this.lblNomePacchetto.Size = new System.Drawing.Size(43, 15);
            this.lblNomePacchetto.TabIndex = 0;
            this.lblNomePacchetto.Text = "Nome:";
            // 
            // btnEliminaPacchetto
            // 
            this.btnEliminaPacchetto.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Right)));
            this.btnEliminaPacchetto.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(204)))), ((int)(((byte)(0)))), ((int)(((byte)(0)))));
            this.btnEliminaPacchetto.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnEliminaPacchetto.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.btnEliminaPacchetto.ForeColor = System.Drawing.Color.White;
            this.btnEliminaPacchetto.Location = new System.Drawing.Point(419, 521);
            this.btnEliminaPacchetto.Name = "btnEliminaPacchetto";
            this.btnEliminaPacchetto.Size = new System.Drawing.Size(90, 33);
            this.btnEliminaPacchetto.TabIndex = 6;
            this.btnEliminaPacchetto.Text = "Elimina";
            this.btnEliminaPacchetto.UseVisualStyleBackColor = false;
            this.btnEliminaPacchetto.Click += new System.EventHandler(this.btnEliminaPacchetto_Click);
            // 
            // btnSalvaPacchetto
            // 
            this.btnSalvaPacchetto.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Right)));
            this.btnSalvaPacchetto.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(164)))), ((int)(((byte)(0)))));
            this.btnSalvaPacchetto.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnSalvaPacchetto.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.btnSalvaPacchetto.ForeColor = System.Drawing.Color.White;
            this.btnSalvaPacchetto.Location = new System.Drawing.Point(323, 521);
            this.btnSalvaPacchetto.Name = "btnSalvaPacchetto";
            this.btnSalvaPacchetto.Size = new System.Drawing.Size(90, 33);
            this.btnSalvaPacchetto.TabIndex = 5;
            this.btnSalvaPacchetto.Text = "Salva";
            this.btnSalvaPacchetto.UseVisualStyleBackColor = false;
            this.btnSalvaPacchetto.Click += new System.EventHandler(this.btnSalvaPacchetto_Click);
            // 
            // btnNuovoPacchetto
            // 
            this.btnNuovoPacchetto.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Right)));
            this.btnNuovoPacchetto.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(122)))), ((int)(((byte)(204)))));
            this.btnNuovoPacchetto.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnNuovoPacchetto.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.btnNuovoPacchetto.ForeColor = System.Drawing.Color.White;
            this.btnNuovoPacchetto.Location = new System.Drawing.Point(227, 521);
            this.btnNuovoPacchetto.Name = "btnNuovoPacchetto";
            this.btnNuovoPacchetto.Size = new System.Drawing.Size(90, 33);
            this.btnNuovoPacchetto.TabIndex = 4;
            this.btnNuovoPacchetto.Text = "Nuovo";
            this.btnNuovoPacchetto.UseVisualStyleBackColor = false;
            this.btnNuovoPacchetto.Click += new System.EventHandler(this.btnNuovoPacchetto_Click);
            // 
            // AcquistiControl
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(7F, 15F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.Controls.Add(this.tabControlAcquisti);
            this.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.Name = "AcquistiControl";
            this.Size = new System.Drawing.Size(913, 600);
            this.tabControlAcquisti.ResumeLayout(false);
            this.tabAcquisti.ResumeLayout(false);
            this.tabAcquisti.PerformLayout();
            ((System.ComponentModel.ISupportInitialize)(this.dgvAcquisti)).EndInit();
            this.panelAcquistiRight.ResumeLayout(false);
            this.grpDettaglioAcquisto.ResumeLayout(false);
            this.grpDettaglioAcquisto.PerformLayout();
            this.tabPacchetti.ResumeLayout(false);
            this.tabPacchetti.PerformLayout();
            ((System.ComponentModel.ISupportInitialize)(this.dgvPacchetti)).EndInit();
            this.panelPacchettiRight.ResumeLayout(false);
            this.grpDettaglioPacchetto.ResumeLayout(false);
            this.grpDettaglioPacchetto.PerformLayout();
            ((System.ComponentModel.ISupportInitialize)(this.nudDurataMinuti)).EndInit();
            ((System.ComponentModel.ISupportInitialize)(this.nudNumeroLezioni)).EndInit();
            this.ResumeLayout(false);

        }

        #endregion

        private System.Windows.Forms.TabControl tabControlAcquisti;
        private System.Windows.Forms.TabPage tabPacchetti;
        private System.Windows.Forms.Label lblRicercaPacchetti;
        private System.Windows.Forms.TextBox txtRicercaPacchetti;
        private System.Windows.Forms.DataGridView dgvPacchetti;
        private System.Windows.Forms.GroupBox grpDettaglioPacchetto;
        private System.Windows.Forms.ComboBox cmbStrumento;
        private System.Windows.Forms.Label lblStrumento;
        private System.Windows.Forms.TextBox txtPrezzo;
        private System.Windows.Forms.Label lblPrezzo;
        private System.Windows.Forms.ComboBox cmbFrequenza;
        private System.Windows.Forms.Label lblFrequenza;
        private System.Windows.Forms.NumericUpDown nudDurataMinuti;
        private System.Windows.Forms.Label lblDurata;
        private System.Windows.Forms.NumericUpDown nudNumeroLezioni;
        private System.Windows.Forms.Label lblNumeroLezioni;
        private System.Windows.Forms.TextBox txtDescrizione;
        private System.Windows.Forms.Label lblDescrizione;
        private System.Windows.Forms.TextBox txtNomePacchetto;
        private System.Windows.Forms.Label lblNomePacchetto;
        private System.Windows.Forms.Button btnEliminaPacchetto;
        private System.Windows.Forms.Button btnSalvaPacchetto;
        private System.Windows.Forms.Button btnNuovoPacchetto;
        private System.Windows.Forms.TabPage tabAcquisti;
        private System.Windows.Forms.Label lblAcquistiDaFatturare;
        private System.Windows.Forms.Label lblRicercaAcquisti;
        private System.Windows.Forms.TextBox txtRicercaAcquisti;
        private System.Windows.Forms.DataGridView dgvAcquisti;
        private System.Windows.Forms.GroupBox grpDettaglioAcquisto;
        private System.Windows.Forms.Button btnPianifica;
        private System.Windows.Forms.TextBox txtNoteAcquisto;
        private System.Windows.Forms.Label lblNoteAcquisto;
        private System.Windows.Forms.TextBox txtNumeroFattura;
        private System.Windows.Forms.Label lblNumeroFattura;
        private System.Windows.Forms.CheckBox chkPianificato;
        private System.Windows.Forms.ComboBox cmbStatoPagamento;
        private System.Windows.Forms.Label lblStatoPagamento;
        private System.Windows.Forms.TextBox txtImportoPagato;
        private System.Windows.Forms.Label lblImportoPagato;
        private System.Windows.Forms.ComboBox cmbTipoSconto;
        private System.Windows.Forms.TextBox txtSconto;
        private System.Windows.Forms.Label lblSconto;
        private System.Windows.Forms.TextBox txtCostoPacchetto;
        private System.Windows.Forms.Label lblCostoPacchetto;
        private System.Windows.Forms.ComboBox cmbPacchetti;
        private System.Windows.Forms.Label lblPacchetto;
        private System.Windows.Forms.ComboBox cmbClienti;
        private System.Windows.Forms.Label lblCliente;
        private System.Windows.Forms.DateTimePicker dtpDataAcquisto;
        private System.Windows.Forms.Label lblDataAcquisto;
        private System.Windows.Forms.Button btnEliminaAcquisto;
        private System.Windows.Forms.Button btnSalvaAcquisto;
        private System.Windows.Forms.Button btnNuovoAcquisto;
        private System.Windows.Forms.Panel panelAcquistiRight;
        private System.Windows.Forms.Panel panelPacchettiRight;
        private System.Windows.Forms.ComboBox cmbStrumentoFiltro;
        private System.Windows.Forms.Label lblStrumentoFiltro;
        private System.Windows.Forms.Label label1;
        private System.Windows.Forms.CheckBox checkCoppia;
    }
}