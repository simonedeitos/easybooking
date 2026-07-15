namespace EasyBooking
{
    partial class ClientiControl
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
            this.dgvClienti = new System.Windows.Forms.DataGridView();
            this.grpDettaglioCliente = new System.Windows.Forms.GroupBox();
            this.btnMegaSettings = new System.Windows.Forms.Button();
            this.btnMega = new System.Windows.Forms.Button();
            this.btnCartella = new System.Windows.Forms.Button();
            this.btnWhatsapp = new System.Windows.Forms.Button();
            this.btnEmail = new System.Windows.Forms.Button();
            this.btnSalva = new System.Windows.Forms.Button();
            this.btnNuovo = new System.Windows.Forms.Button();
            this.btnElimina = new System.Windows.Forms.Button();
            this.txtNote = new System.Windows.Forms.TextBox();
            this.txtEmail = new System.Windows.Forms.TextBox();
            this.txtCodiceFiscale = new System.Windows.Forms.TextBox();
            this.txtIndirizzo = new System.Windows.Forms.TextBox();
            this.txtTelefono = new System.Windows.Forms.TextBox();
            this.txtCognome = new System.Windows.Forms.TextBox();
            this.txtNome = new System.Windows.Forms.TextBox();
            this.lblNote = new System.Windows.Forms.Label();
            this.lblEmail = new System.Windows.Forms.Label();
            this.lblCodiceFiscale = new System.Windows.Forms.Label();
            this.lblIndirizzo = new System.Windows.Forms.Label();
            this.lblTelefono = new System.Windows.Forms.Label();
            this.lblCognome = new System.Windows.Forms.Label();
            this.lblNome = new System.Windows.Forms.Label();
            this.tabControlDati = new System.Windows.Forms.TabControl();
            this.tabLezioni = new System.Windows.Forms.TabPage();
            this.btnRinnovoVeloce = new System.Windows.Forms.Button();
            this.btnRiepilogo = new System.Windows.Forms.Button();
            this.dgvLezioni = new System.Windows.Forms.DataGridView();
            this.lblFiltroLezioni = new System.Windows.Forms.Label();
            this.cmbFiltroLezioni = new System.Windows.Forms.ComboBox();
            this.tabAcquisti = new System.Windows.Forms.TabPage();
            this.dgvAcquisti = new System.Windows.Forms.DataGridView();
            this.lblRicerca = new System.Windows.Forms.Label();
            this.txtRicerca = new System.Windows.Forms.TextBox();
            ((System.ComponentModel.ISupportInitialize)(this.dgvClienti)).BeginInit();
            this.grpDettaglioCliente.SuspendLayout();
            this.tabControlDati.SuspendLayout();
            this.tabLezioni.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)(this.dgvLezioni)).BeginInit();
            this.tabAcquisti.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)(this.dgvAcquisti)).BeginInit();
            this.SuspendLayout();
            // 
            // dgvClienti
            // 
            this.dgvClienti.AllowUserToAddRows = false;
            this.dgvClienti.AllowUserToDeleteRows = false;
            this.dgvClienti.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom)
            | System.Windows.Forms.AnchorStyles.Left)
            | System.Windows.Forms.AnchorStyles.Right)));
            this.dgvClienti.BackgroundColor = System.Drawing.SystemColors.Control;
            this.dgvClienti.ColumnHeadersHeightSizeMode = System.Windows.Forms.DataGridViewColumnHeadersHeightSizeMode.AutoSize;
            this.dgvClienti.Location = new System.Drawing.Point(12, 41);
            this.dgvClienti.MultiSelect = false;
            this.dgvClienti.Name = "dgvClienti";
            this.dgvClienti.ReadOnly = true;
            this.dgvClienti.RowHeadersVisible = false;
            this.dgvClienti.SelectionMode = System.Windows.Forms.DataGridViewSelectionMode.FullRowSelect;
            this.dgvClienti.Size = new System.Drawing.Size(256, 547);
            this.dgvClienti.TabIndex = 2;
            this.dgvClienti.CellClick += new System.Windows.Forms.DataGridViewCellEventHandler(this.dgvClienti_CellClick);
            this.dgvClienti.CellDoubleClick += new System.Windows.Forms.DataGridViewCellEventHandler(this.dgvClienti_CellDoubleClick);
            // 
            // grpDettaglioCliente
            // 
            this.grpDettaglioCliente.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Right)));
            this.grpDettaglioCliente.Controls.Add(this.btnMegaSettings);
            this.grpDettaglioCliente.Controls.Add(this.btnMega);
            this.grpDettaglioCliente.Controls.Add(this.btnCartella);
            this.grpDettaglioCliente.Controls.Add(this.btnWhatsapp);
            this.grpDettaglioCliente.Controls.Add(this.btnEmail);
            this.grpDettaglioCliente.Controls.Add(this.btnSalva);
            this.grpDettaglioCliente.Controls.Add(this.btnNuovo);
            this.grpDettaglioCliente.Controls.Add(this.btnElimina);
            this.grpDettaglioCliente.Controls.Add(this.txtNote);
            this.grpDettaglioCliente.Controls.Add(this.txtEmail);
            this.grpDettaglioCliente.Controls.Add(this.txtCodiceFiscale);
            this.grpDettaglioCliente.Controls.Add(this.txtIndirizzo);
            this.grpDettaglioCliente.Controls.Add(this.txtTelefono);
            this.grpDettaglioCliente.Controls.Add(this.txtCognome);
            this.grpDettaglioCliente.Controls.Add(this.txtNome);
            this.grpDettaglioCliente.Controls.Add(this.lblNote);
            this.grpDettaglioCliente.Controls.Add(this.lblEmail);
            this.grpDettaglioCliente.Controls.Add(this.lblCodiceFiscale);
            this.grpDettaglioCliente.Controls.Add(this.lblIndirizzo);
            this.grpDettaglioCliente.Controls.Add(this.lblTelefono);
            this.grpDettaglioCliente.Controls.Add(this.lblCognome);
            this.grpDettaglioCliente.Controls.Add(this.lblNome);
            this.grpDettaglioCliente.Font = new System.Drawing.Font("Arial", 9.75F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.grpDettaglioCliente.Location = new System.Drawing.Point(274, 12);
            this.grpDettaglioCliente.Name = "grpDettaglioCliente";
            this.grpDettaglioCliente.Size = new System.Drawing.Size(639, 280);
            this.grpDettaglioCliente.TabIndex = 3;
            this.grpDettaglioCliente.TabStop = false;
            this.grpDettaglioCliente.Text = "Dati Cliente";
            // 
            // btnMegaSettings
            // 
            this.btnMegaSettings.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(224)))), ((int)(((byte)(224)))), ((int)(((byte)(224)))));
            this.btnMegaSettings.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnMegaSettings.Font = new System.Drawing.Font("Calibri", 8.25F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.btnMegaSettings.ForeColor = System.Drawing.Color.Black;
            this.btnMegaSettings.Location = new System.Drawing.Point(550, 28);
            this.btnMegaSettings.Name = "btnMegaSettings";
            this.btnMegaSettings.Size = new System.Drawing.Size(74, 22);
            this.btnMegaSettings.TabIndex = 21;
            this.btnMegaSettings.Text = "Edit Folders";
            this.btnMegaSettings.TextAlign = System.Drawing.ContentAlignment.TopCenter;
            this.btnMegaSettings.UseVisualStyleBackColor = false;
            this.btnMegaSettings.Click += new System.EventHandler(this.btnMegaSettings_Click);
            // 
            // btnMega
            // 
            this.btnMega.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(220)))), ((int)(((byte)(20)))), ((int)(((byte)(60)))));
            this.btnMega.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnMega.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.btnMega.ForeColor = System.Drawing.Color.White;
            this.btnMega.Location = new System.Drawing.Point(470, 27);
            this.btnMega.Name = "btnMega";
            this.btnMega.Size = new System.Drawing.Size(74, 23);
            this.btnMega.TabIndex = 20;
            this.btnMega.Text = "MEGA";
            this.btnMega.UseVisualStyleBackColor = false;
            this.btnMega.Click += new System.EventHandler(this.btnMega_Click);
            // 
            // btnCartella
            // 
            this.btnCartella.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(34)))), ((int)(((byte)(139)))), ((int)(((byte)(34)))));
            this.btnCartella.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnCartella.Font = new System.Drawing.Font("Segoe UI", 8F, System.Drawing.FontStyle.Bold);
            this.btnCartella.ForeColor = System.Drawing.Color.White;
            this.btnCartella.Location = new System.Drawing.Point(390, 27);
            this.btnCartella.Name = "btnCartella";
            this.btnCartella.Size = new System.Drawing.Size(74, 23);
            this.btnCartella.TabIndex = 19;
            this.btnCartella.Text = "CARTELLA";
            this.btnCartella.UseVisualStyleBackColor = false;
            this.btnCartella.Click += new System.EventHandler(this.btnCartella_Click);
            // 
            // btnWhatsapp
            // 
            this.btnWhatsapp.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(37)))), ((int)(((byte)(211)))), ((int)(((byte)(102)))));
            this.btnWhatsapp.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnWhatsapp.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.btnWhatsapp.ForeColor = System.Drawing.Color.White;
            this.btnWhatsapp.Location = new System.Drawing.Point(366, 87);
            this.btnWhatsapp.Name = "btnWhatsapp";
            this.btnWhatsapp.Size = new System.Drawing.Size(74, 23);
            this.btnWhatsapp.TabIndex = 17;
            this.btnWhatsapp.Text = "WhatsApp";
            this.btnWhatsapp.UseVisualStyleBackColor = false;
            this.btnWhatsapp.Click += new System.EventHandler(this.btnWhatsapp_Click);
            // 
            // btnEmail
            // 
            this.btnEmail.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(66)))), ((int)(((byte)(133)))), ((int)(((byte)(244)))));
            this.btnEmail.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnEmail.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.btnEmail.ForeColor = System.Drawing.Color.White;
            this.btnEmail.Location = new System.Drawing.Point(366, 177);
            this.btnEmail.Name = "btnEmail";
            this.btnEmail.Size = new System.Drawing.Size(74, 23);
            this.btnEmail.TabIndex = 18;
            this.btnEmail.Text = "Email";
            this.btnEmail.UseVisualStyleBackColor = false;
            this.btnEmail.Click += new System.EventHandler(this.btnEmail_Click);
            // 
            // btnSalva
            // 
            this.btnSalva.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(164)))), ((int)(((byte)(0)))));
            this.btnSalva.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnSalva.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.btnSalva.ForeColor = System.Drawing.Color.White;
            this.btnSalva.Location = new System.Drawing.Point(534, 201);
            this.btnSalva.Name = "btnSalva";
            this.btnSalva.Size = new System.Drawing.Size(90, 30);
            this.btnSalva.TabIndex = 14;
            this.btnSalva.Text = "Salva";
            this.btnSalva.UseVisualStyleBackColor = false;
            this.btnSalva.Click += new System.EventHandler(this.btnSalva_Click);
            // 
            // btnNuovo
            // 
            this.btnNuovo.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(122)))), ((int)(((byte)(204)))));
            this.btnNuovo.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnNuovo.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.btnNuovo.ForeColor = System.Drawing.Color.White;
            this.btnNuovo.Location = new System.Drawing.Point(20, 237);
            this.btnNuovo.Name = "btnNuovo";
            this.btnNuovo.Size = new System.Drawing.Size(90, 30);
            this.btnNuovo.TabIndex = 15;
            this.btnNuovo.Text = "Nuovo";
            this.btnNuovo.UseVisualStyleBackColor = false;
            this.btnNuovo.Click += new System.EventHandler(this.btnNuovo_Click);
            // 
            // btnElimina
            // 
            this.btnElimina.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(204)))), ((int)(((byte)(0)))), ((int)(((byte)(0)))));
            this.btnElimina.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnElimina.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.btnElimina.ForeColor = System.Drawing.Color.White;
            this.btnElimina.Location = new System.Drawing.Point(534, 237);
            this.btnElimina.Name = "btnElimina";
            this.btnElimina.Size = new System.Drawing.Size(90, 30);
            this.btnElimina.TabIndex = 16;
            this.btnElimina.Text = "Elimina";
            this.btnElimina.UseVisualStyleBackColor = false;
            this.btnElimina.Click += new System.EventHandler(this.btnElimina_Click);
            // 
            // txtNote
            // 
            this.txtNote.Location = new System.Drawing.Point(120, 207);
            this.txtNote.Multiline = true;
            this.txtNote.Name = "txtNote";
            this.txtNote.Size = new System.Drawing.Size(320, 60);
            this.txtNote.TabIndex = 13;
            // 
            // txtEmail
            // 
            this.txtEmail.Location = new System.Drawing.Point(120, 177);
            this.txtEmail.Name = "txtEmail";
            this.txtEmail.Size = new System.Drawing.Size(240, 22);
            this.txtEmail.TabIndex = 12;
            // 
            // txtCodiceFiscale
            // 
            this.txtCodiceFiscale.CharacterCasing = System.Windows.Forms.CharacterCasing.Upper;
            this.txtCodiceFiscale.Location = new System.Drawing.Point(120, 147);
            this.txtCodiceFiscale.MaxLength = 16;
            this.txtCodiceFiscale.Name = "txtCodiceFiscale";
            this.txtCodiceFiscale.Size = new System.Drawing.Size(240, 22);
            this.txtCodiceFiscale.TabIndex = 11;
            // 
            // txtIndirizzo
            // 
            this.txtIndirizzo.Location = new System.Drawing.Point(120, 117);
            this.txtIndirizzo.Name = "txtIndirizzo";
            this.txtIndirizzo.Size = new System.Drawing.Size(504, 22);
            this.txtIndirizzo.TabIndex = 10;
            // 
            // txtTelefono
            // 
            this.txtTelefono.Location = new System.Drawing.Point(120, 87);
            this.txtTelefono.Name = "txtTelefono";
            this.txtTelefono.Size = new System.Drawing.Size(240, 22);
            this.txtTelefono.TabIndex = 9;
            // 
            // txtCognome
            // 
            this.txtCognome.Location = new System.Drawing.Point(120, 57);
            this.txtCognome.Name = "txtCognome";
            this.txtCognome.Size = new System.Drawing.Size(240, 22);
            this.txtCognome.TabIndex = 8;
            // 
            // txtNome
            // 
            this.txtNome.Location = new System.Drawing.Point(120, 27);
            this.txtNome.Name = "txtNome";
            this.txtNome.Size = new System.Drawing.Size(240, 22);
            this.txtNome.TabIndex = 7;
            // 
            // lblNote
            // 
            this.lblNote.AutoSize = true;
            this.lblNote.Location = new System.Drawing.Point(20, 210);
            this.lblNote.Name = "lblNote";
            this.lblNote.Size = new System.Drawing.Size(38, 16);
            this.lblNote.TabIndex = 6;
            this.lblNote.Text = "Note:";
            // 
            // lblEmail
            // 
            this.lblEmail.AutoSize = true;
            this.lblEmail.Location = new System.Drawing.Point(20, 180);
            this.lblEmail.Name = "lblEmail";
            this.lblEmail.Size = new System.Drawing.Size(44, 16);
            this.lblEmail.TabIndex = 5;
            this.lblEmail.Text = "Email:";
            // 
            // lblCodiceFiscale
            // 
            this.lblCodiceFiscale.AutoSize = true;
            this.lblCodiceFiscale.Location = new System.Drawing.Point(20, 150);
            this.lblCodiceFiscale.Name = "lblCodiceFiscale";
            this.lblCodiceFiscale.Size = new System.Drawing.Size(97, 16);
            this.lblCodiceFiscale.TabIndex = 4;
            this.lblCodiceFiscale.Text = "Codice Fiscale:";
            // 
            // lblIndirizzo
            // 
            this.lblIndirizzo.AutoSize = true;
            this.lblIndirizzo.Location = new System.Drawing.Point(20, 120);
            this.lblIndirizzo.Name = "lblIndirizzo";
            this.lblIndirizzo.Size = new System.Drawing.Size(59, 16);
            this.lblIndirizzo.TabIndex = 3;
            this.lblIndirizzo.Text = "Indirizzo:";
            // 
            // lblTelefono
            // 
            this.lblTelefono.AutoSize = true;
            this.lblTelefono.Location = new System.Drawing.Point(20, 90);
            this.lblTelefono.Name = "lblTelefono";
            this.lblTelefono.Size = new System.Drawing.Size(58, 16);
            this.lblTelefono.TabIndex = 2;
            this.lblTelefono.Text = "Telefono:";
            // 
            // lblCognome
            // 
            this.lblCognome.AutoSize = true;
            this.lblCognome.Location = new System.Drawing.Point(20, 60);
            this.lblCognome.Name = "lblCognome";
            this.lblCognome.Size = new System.Drawing.Size(66, 16);
            this.lblCognome.TabIndex = 1;
            this.lblCognome.Text = "Cognome:";
            // 
            // lblNome
            // 
            this.lblNome.AutoSize = true;
            this.lblNome.Location = new System.Drawing.Point(20, 30);
            this.lblNome.Name = "lblNome";
            this.lblNome.Size = new System.Drawing.Size(45, 16);
            this.lblNome.TabIndex = 0;
            this.lblNome.Text = "Nome:";
            // 
            // tabControlDati
            // 
            this.tabControlDati.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom)
            | System.Windows.Forms.AnchorStyles.Right)));
            this.tabControlDati.Controls.Add(this.tabLezioni);
            this.tabControlDati.Controls.Add(this.tabAcquisti);
            this.tabControlDati.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.tabControlDati.Location = new System.Drawing.Point(274, 298);
            this.tabControlDati.Name = "tabControlDati";
            this.tabControlDati.SelectedIndex = 0;
            this.tabControlDati.Size = new System.Drawing.Size(639, 290);
            this.tabControlDati.TabIndex = 5;
            // 
            // tabLezioni
            // 
            this.tabLezioni.Controls.Add(this.btnRinnovoVeloce);
            this.tabLezioni.Controls.Add(this.btnRiepilogo);
            this.tabLezioni.Controls.Add(this.dgvLezioni);
            this.tabLezioni.Controls.Add(this.lblFiltroLezioni);
            this.tabLezioni.Controls.Add(this.cmbFiltroLezioni);
            this.tabLezioni.Location = new System.Drawing.Point(4, 24);
            this.tabLezioni.Name = "tabLezioni";
            this.tabLezioni.Padding = new System.Windows.Forms.Padding(3);
            this.tabLezioni.Size = new System.Drawing.Size(631, 262);
            this.tabLezioni.TabIndex = 0;
            this.tabLezioni.Text = "Lezioni";
            this.tabLezioni.UseVisualStyleBackColor = true;
            // 
            // btnRinnovoVeloce
            // 
            this.btnRinnovoVeloce.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Right)));
            this.btnRinnovoVeloce.BackColor = System.Drawing.Color.DarkGreen;
            this.btnRinnovoVeloce.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnRinnovoVeloce.Font = new System.Drawing.Font("Segoe UI Semibold", 9F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.btnRinnovoVeloce.ForeColor = System.Drawing.Color.White;
            this.btnRinnovoVeloce.Location = new System.Drawing.Point(379, 10);
            this.btnRinnovoVeloce.Name = "btnRinnovoVeloce";
            this.btnRinnovoVeloce.Size = new System.Drawing.Size(120, 26);
            this.btnRinnovoVeloce.TabIndex = 4;
            this.btnRinnovoVeloce.Text = "RINNOVO VELOCE";
            this.btnRinnovoVeloce.UseVisualStyleBackColor = false;
            this.btnRinnovoVeloce.Click += new System.EventHandler(this.btnRinnovoVeloce_Click);
            // 
            // btnRiepilogo
            // 
            this.btnRiepilogo.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Right)));
            this.btnRiepilogo.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(128)))), ((int)(((byte)(128)))), ((int)(((byte)(128)))));
            this.btnRiepilogo.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnRiepilogo.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.btnRiepilogo.ForeColor = System.Drawing.Color.White;
            this.btnRiepilogo.Location = new System.Drawing.Point(505, 10);
            this.btnRiepilogo.Name = "btnRiepilogo";
            this.btnRiepilogo.Size = new System.Drawing.Size(120, 26);
            this.btnRiepilogo.TabIndex = 3;
            this.btnRiepilogo.Text = "Riepilogo            ▼";
            this.btnRiepilogo.UseVisualStyleBackColor = false;
            this.btnRiepilogo.Click += new System.EventHandler(this.btnRiepilogo_Click);
            // 
            // dgvLezioni
            // 
            this.dgvLezioni.AllowUserToAddRows = false;
            this.dgvLezioni.AllowUserToDeleteRows = false;
            this.dgvLezioni.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom)
            | System.Windows.Forms.AnchorStyles.Left)
            | System.Windows.Forms.AnchorStyles.Right)));
            this.dgvLezioni.BackgroundColor = System.Drawing.SystemColors.Control;
            this.dgvLezioni.ColumnHeadersHeightSizeMode = System.Windows.Forms.DataGridViewColumnHeadersHeightSizeMode.AutoSize;
            this.dgvLezioni.Location = new System.Drawing.Point(6, 42);
            this.dgvLezioni.MultiSelect = true;
            this.dgvLezioni.Name = "dgvLezioni";
            this.dgvLezioni.ReadOnly = true;
            this.dgvLezioni.RowHeadersVisible = false;
            this.dgvLezioni.SelectionMode = System.Windows.Forms.DataGridViewSelectionMode.FullRowSelect;
            this.dgvLezioni.Size = new System.Drawing.Size(619, 214);
            this.dgvLezioni.TabIndex = 2;
            // 
            // lblFiltroLezioni
            // 
            this.lblFiltroLezioni.AutoSize = true;
            this.lblFiltroLezioni.Location = new System.Drawing.Point(6, 16);
            this.lblFiltroLezioni.Name = "lblFiltroLezioni";
            this.lblFiltroLezioni.Size = new System.Drawing.Size(37, 15);
            this.lblFiltroLezioni.TabIndex = 0;
            this.lblFiltroLezioni.Text = "Filtro:";
            // 
            // cmbFiltroLezioni
            // 
            this.cmbFiltroLezioni.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbFiltroLezioni.FormattingEnabled = true;
            this.cmbFiltroLezioni.Items.AddRange(new object[] {
            "Tutte",
            "Svolte",
            "Programmate"});
            this.cmbFiltroLezioni.Location = new System.Drawing.Point(49, 13);
            this.cmbFiltroLezioni.Name = "cmbFiltroLezioni";
            this.cmbFiltroLezioni.Size = new System.Drawing.Size(121, 23);
            this.cmbFiltroLezioni.TabIndex = 1;
            this.cmbFiltroLezioni.SelectedIndexChanged += new System.EventHandler(this.cmbFiltroLezioni_SelectedIndexChanged);
            // 
            // tabAcquisti
            // 
            this.tabAcquisti.Controls.Add(this.dgvAcquisti);
            this.tabAcquisti.Location = new System.Drawing.Point(4, 24);
            this.tabAcquisti.Name = "tabAcquisti";
            this.tabAcquisti.Padding = new System.Windows.Forms.Padding(3);
            this.tabAcquisti.Size = new System.Drawing.Size(631, 262);
            this.tabAcquisti.TabIndex = 1;
            this.tabAcquisti.Text = "Acquisti";
            this.tabAcquisti.UseVisualStyleBackColor = true;
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
            this.dgvAcquisti.Location = new System.Drawing.Point(6, 6);
            this.dgvAcquisti.MultiSelect = false;
            this.dgvAcquisti.Name = "dgvAcquisti";
            this.dgvAcquisti.ReadOnly = true;
            this.dgvAcquisti.RowHeadersVisible = false;
            this.dgvAcquisti.SelectionMode = System.Windows.Forms.DataGridViewSelectionMode.FullRowSelect;
            this.dgvAcquisti.Size = new System.Drawing.Size(619, 250);
            this.dgvAcquisti.TabIndex = 0;
            // 
            // lblRicerca
            // 
            this.lblRicerca.AutoSize = true;
            this.lblRicerca.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.lblRicerca.Location = new System.Drawing.Point(12, 15);
            this.lblRicerca.Name = "lblRicerca";
            this.lblRicerca.Size = new System.Drawing.Size(48, 15);
            this.lblRicerca.TabIndex = 0;
            this.lblRicerca.Text = "Ricerca:";
            // 
            // txtRicerca
            // 
            this.txtRicerca.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left)
            | System.Windows.Forms.AnchorStyles.Right)));
            this.txtRicerca.Location = new System.Drawing.Point(67, 12);
            this.txtRicerca.Name = "txtRicerca";
            this.txtRicerca.Size = new System.Drawing.Size(201, 23);
            this.txtRicerca.TabIndex = 1;
            this.txtRicerca.TextChanged += new System.EventHandler(this.txtRicerca_TextChanged);
            // 
            // ClientiControl
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(7F, 15F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.Controls.Add(this.tabControlDati);
            this.Controls.Add(this.grpDettaglioCliente);
            this.Controls.Add(this.dgvClienti);
            this.Controls.Add(this.txtRicerca);
            this.Controls.Add(this.lblRicerca);
            this.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.MinimumSize = new System.Drawing.Size(913, 600);
            this.Name = "ClientiControl";
            this.Size = new System.Drawing.Size(925, 600);
            ((System.ComponentModel.ISupportInitialize)(this.dgvClienti)).EndInit();
            this.grpDettaglioCliente.ResumeLayout(false);
            this.grpDettaglioCliente.PerformLayout();
            this.tabControlDati.ResumeLayout(false);
            this.tabLezioni.ResumeLayout(false);
            this.tabLezioni.PerformLayout();
            ((System.ComponentModel.ISupportInitialize)(this.dgvLezioni)).EndInit();
            this.tabAcquisti.ResumeLayout(false);
            ((System.ComponentModel.ISupportInitialize)(this.dgvAcquisti)).EndInit();
            this.ResumeLayout(false);
            this.PerformLayout();

        }

        #endregion

        private System.Windows.Forms.DataGridView dgvClienti;
        private System.Windows.Forms.GroupBox grpDettaglioCliente;
        private System.Windows.Forms.Button btnMegaSettings;
        private System.Windows.Forms.Button btnMega;
        private System.Windows.Forms.Button btnCartella;
        private System.Windows.Forms.Button btnWhatsapp;
        private System.Windows.Forms.Button btnEmail;
        private System.Windows.Forms.Button btnSalva;
        private System.Windows.Forms.Button btnNuovo;
        private System.Windows.Forms.Button btnElimina;
        private System.Windows.Forms.TextBox txtNote;
        private System.Windows.Forms.TextBox txtEmail;
        private System.Windows.Forms.TextBox txtCodiceFiscale;
        private System.Windows.Forms.TextBox txtIndirizzo;
        private System.Windows.Forms.TextBox txtTelefono;
        private System.Windows.Forms.TextBox txtCognome;
        private System.Windows.Forms.TextBox txtNome;
        private System.Windows.Forms.Label lblNote;
        private System.Windows.Forms.Label lblEmail;
        private System.Windows.Forms.Label lblCodiceFiscale;
        private System.Windows.Forms.Label lblIndirizzo;
        private System.Windows.Forms.Label lblTelefono;
        private System.Windows.Forms.Label lblCognome;
        private System.Windows.Forms.Label lblNome;
        private System.Windows.Forms.TabControl tabControlDati;
        private System.Windows.Forms.TabPage tabLezioni;
        private System.Windows.Forms.Button btnRinnovoVeloce;
        private System.Windows.Forms.Button btnRiepilogo;
        private System.Windows.Forms.DataGridView dgvLezioni;
        private System.Windows.Forms.Label lblFiltroLezioni;
        private System.Windows.Forms.ComboBox cmbFiltroLezioni;
        private System.Windows.Forms.TabPage tabAcquisti;
        private System.Windows.Forms.DataGridView dgvAcquisti;
        private System.Windows.Forms.Label lblRicerca;
        private System.Windows.Forms.TextBox txtRicerca;
    }
}