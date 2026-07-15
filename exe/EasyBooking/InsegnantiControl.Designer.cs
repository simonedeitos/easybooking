namespace EasyBooking
{
    partial class InsegnantiControl
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
            this.dgvInsegnanti = new System.Windows.Forms.DataGridView();
            this.grpDettaglioInsegnante = new System.Windows.Forms.GroupBox();
            this.lblTariffaOrariaCoppia = new System.Windows.Forms.Label();
            this.txtTariffaOrariaCoppia = new System.Windows.Forms.TextBox();
            this.clbStrumenti = new System.Windows.Forms.CheckedListBox();
            this.lblStrumenti = new System.Windows.Forms.Label();
            this.btnWhatsapp = new System.Windows.Forms.Button();
            this.btnEmail = new System.Windows.Forms.Button();
            this.btnSalva = new System.Windows.Forms.Button();
            this.btnNuovo = new System.Windows.Forms.Button();
            this.btnElimina = new System.Windows.Forms.Button();
            this.txtTariffaOraria = new System.Windows.Forms.TextBox();
            this.txtEmail = new System.Windows.Forms.TextBox();
            this.txtTelefono = new System.Windows.Forms.TextBox();
            this.txtCognome = new System.Windows.Forms.TextBox();
            this.txtNome = new System.Windows.Forms.TextBox();
            this.lblTariffaOraria = new System.Windows.Forms.Label();
            this.lblEmail = new System.Windows.Forms.Label();
            this.lblTelefono = new System.Windows.Forms.Label();
            this.lblCognome = new System.Windows.Forms.Label();
            this.lblNome = new System.Windows.Forms.Label();
            this.tabControlDati = new System.Windows.Forms.TabControl();
            this.tabRiepilogo = new System.Windows.Forms.TabPage();
            this.btnEsporta = new System.Windows.Forms.Button();
            this.lblTotaleCompenso = new System.Windows.Forms.Label();
            this.lblTotaleLezioni = new System.Windows.Forms.Label();
            this.dgvRiepilogoMensile = new System.Windows.Forms.DataGridView();
            this.cmbAnno = new System.Windows.Forms.ComboBox();
            this.lblAnno = new System.Windows.Forms.Label();
            this.lblMese = new System.Windows.Forms.Label();
            this.cmbMese = new System.Windows.Forms.ComboBox();
            this.tabLezioniFuture = new System.Windows.Forms.TabPage();
            this.dgvLezioniFuture = new System.Windows.Forms.DataGridView();
            this.lblRicerca = new System.Windows.Forms.Label();
            this.txtRicerca = new System.Windows.Forms.TextBox();
            this.panelRight = new System.Windows.Forms.Panel();
            ((System.ComponentModel.ISupportInitialize)(this.dgvInsegnanti)).BeginInit();
            this.grpDettaglioInsegnante.SuspendLayout();
            this.tabControlDati.SuspendLayout();
            this.tabRiepilogo.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)(this.dgvRiepilogoMensile)).BeginInit();
            this.tabLezioniFuture.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)(this.dgvLezioniFuture)).BeginInit();
            this.panelRight.SuspendLayout();
            this.SuspendLayout();
            // 
            // dgvInsegnanti
            // 
            this.dgvInsegnanti.AllowUserToAddRows = false;
            this.dgvInsegnanti.AllowUserToDeleteRows = false;
            this.dgvInsegnanti.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.dgvInsegnanti.BackgroundColor = System.Drawing.SystemColors.Control;
            this.dgvInsegnanti.ColumnHeadersHeightSizeMode = System.Windows.Forms.DataGridViewColumnHeadersHeightSizeMode.AutoSize;
            this.dgvInsegnanti.Location = new System.Drawing.Point(12, 41);
            this.dgvInsegnanti.MultiSelect = false;
            this.dgvInsegnanti.Name = "dgvInsegnanti";
            this.dgvInsegnanti.ReadOnly = true;
            this.dgvInsegnanti.RowHeadersVisible = false;
            this.dgvInsegnanti.SelectionMode = System.Windows.Forms.DataGridViewSelectionMode.FullRowSelect;
            this.dgvInsegnanti.Size = new System.Drawing.Size(533, 547);
            this.dgvInsegnanti.TabIndex = 2;
            this.dgvInsegnanti.CellClick += new System.Windows.Forms.DataGridViewCellEventHandler(this.dgvInsegnanti_CellClick);
            this.dgvInsegnanti.CellDoubleClick += new System.Windows.Forms.DataGridViewCellEventHandler(this.dgvInsegnanti_CellDoubleClick);
            // 
            // grpDettaglioInsegnante
            // 
            this.grpDettaglioInsegnante.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.grpDettaglioInsegnante.Controls.Add(this.lblTariffaOrariaCoppia);
            this.grpDettaglioInsegnante.Controls.Add(this.txtTariffaOrariaCoppia);
            this.grpDettaglioInsegnante.Controls.Add(this.clbStrumenti);
            this.grpDettaglioInsegnante.Controls.Add(this.lblStrumenti);
            this.grpDettaglioInsegnante.Controls.Add(this.btnWhatsapp);
            this.grpDettaglioInsegnante.Controls.Add(this.btnEmail);
            this.grpDettaglioInsegnante.Controls.Add(this.btnSalva);
            this.grpDettaglioInsegnante.Controls.Add(this.btnNuovo);
            this.grpDettaglioInsegnante.Controls.Add(this.btnElimina);
            this.grpDettaglioInsegnante.Controls.Add(this.txtTariffaOraria);
            this.grpDettaglioInsegnante.Controls.Add(this.txtEmail);
            this.grpDettaglioInsegnante.Controls.Add(this.txtTelefono);
            this.grpDettaglioInsegnante.Controls.Add(this.txtCognome);
            this.grpDettaglioInsegnante.Controls.Add(this.txtNome);
            this.grpDettaglioInsegnante.Controls.Add(this.lblTariffaOraria);
            this.grpDettaglioInsegnante.Controls.Add(this.lblEmail);
            this.grpDettaglioInsegnante.Controls.Add(this.lblTelefono);
            this.grpDettaglioInsegnante.Controls.Add(this.lblCognome);
            this.grpDettaglioInsegnante.Controls.Add(this.lblNome);
            this.grpDettaglioInsegnante.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.grpDettaglioInsegnante.Location = new System.Drawing.Point(4, 0);
            this.grpDettaglioInsegnante.Name = "grpDettaglioInsegnante";
            this.grpDettaglioInsegnante.Size = new System.Drawing.Size(702, 280);
            this.grpDettaglioInsegnante.TabIndex = 3;
            this.grpDettaglioInsegnante.TabStop = false;
            this.grpDettaglioInsegnante.Text = "Dati Insegnante";
            // 
            // lblTariffaOrariaCoppia
            // 
            this.lblTariffaOrariaCoppia.AutoSize = true;
            this.lblTariffaOrariaCoppia.Location = new System.Drawing.Point(135, 208);
            this.lblTariffaOrariaCoppia.Name = "lblTariffaOrariaCoppia";
            this.lblTariffaOrariaCoppia.Size = new System.Drawing.Size(118, 15);
            this.lblTariffaOrariaCoppia.TabIndex = 17;
            this.lblTariffaOrariaCoppia.Text = "Tariffa Oraria Coppia:";
            // 
            // txtTariffaOrariaCoppia
            // 
            this.txtTariffaOrariaCoppia.Location = new System.Drawing.Point(172, 233);
            this.txtTariffaOrariaCoppia.Name = "txtTariffaOrariaCoppia";
            this.txtTariffaOrariaCoppia.Size = new System.Drawing.Size(81, 23);
            this.txtTariffaOrariaCoppia.TabIndex = 11;
            // 
            // clbStrumenti
            // 
            this.clbStrumenti.FormattingEnabled = true;
            this.clbStrumenti.Location = new System.Drawing.Point(341, 53);
            this.clbStrumenti.Name = "clbStrumenti";
            this.clbStrumenti.Size = new System.Drawing.Size(186, 202);
            this.clbStrumenti.TabIndex = 12;
            // 
            // lblStrumenti
            // 
            this.lblStrumenti.AutoSize = true;
            this.lblStrumenti.Location = new System.Drawing.Point(338, 35);
            this.lblStrumenti.Name = "lblStrumenti";
            this.lblStrumenti.Size = new System.Drawing.Size(62, 15);
            this.lblStrumenti.TabIndex = 5;
            this.lblStrumenti.Text = "Strumenti:";
            // 
            // btnWhatsapp
            // 
            this.btnWhatsapp.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(37)))), ((int)(((byte)(211)))), ((int)(((byte)(102)))));
            this.btnWhatsapp.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnWhatsapp.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.btnWhatsapp.ForeColor = System.Drawing.Color.White;
            this.btnWhatsapp.Location = new System.Drawing.Point(573, 32);
            this.btnWhatsapp.Name = "btnWhatsapp";
            this.btnWhatsapp.Size = new System.Drawing.Size(79, 30);
            this.btnWhatsapp.TabIndex = 15;
            this.btnWhatsapp.Text = "WhatApp";
            this.btnWhatsapp.UseVisualStyleBackColor = false;
            this.btnWhatsapp.Click += new System.EventHandler(this.btnWhatsapp_Click);
            // 
            // btnEmail
            // 
            this.btnEmail.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(66)))), ((int)(((byte)(133)))), ((int)(((byte)(244)))));
            this.btnEmail.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnEmail.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.btnEmail.ForeColor = System.Drawing.Color.White;
            this.btnEmail.Location = new System.Drawing.Point(573, 66);
            this.btnEmail.Name = "btnEmail";
            this.btnEmail.Size = new System.Drawing.Size(79, 30);
            this.btnEmail.TabIndex = 16;
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
            this.btnSalva.Location = new System.Drawing.Point(573, 145);
            this.btnSalva.Name = "btnSalva";
            this.btnSalva.Size = new System.Drawing.Size(90, 30);
            this.btnSalva.TabIndex = 13;
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
            this.btnNuovo.Location = new System.Drawing.Point(573, 185);
            this.btnNuovo.Name = "btnNuovo";
            this.btnNuovo.Size = new System.Drawing.Size(90, 30);
            this.btnNuovo.TabIndex = 14;
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
            this.btnElimina.Location = new System.Drawing.Point(573, 225);
            this.btnElimina.Name = "btnElimina";
            this.btnElimina.Size = new System.Drawing.Size(90, 30);
            this.btnElimina.TabIndex = 14;
            this.btnElimina.Text = "Elimina";
            this.btnElimina.UseVisualStyleBackColor = false;
            this.btnElimina.Click += new System.EventHandler(this.btnElimina_Click);
            // 
            // txtTariffaOraria
            // 
            this.txtTariffaOraria.Location = new System.Drawing.Point(172, 174);
            this.txtTariffaOraria.Name = "txtTariffaOraria";
            this.txtTariffaOraria.Size = new System.Drawing.Size(81, 23);
            this.txtTariffaOraria.TabIndex = 10;
            // 
            // txtEmail
            // 
            this.txtEmail.Location = new System.Drawing.Point(138, 119);
            this.txtEmail.Name = "txtEmail";
            this.txtEmail.Size = new System.Drawing.Size(186, 23);
            this.txtEmail.TabIndex = 9;
            // 
            // txtTelefono
            // 
            this.txtTelefono.Location = new System.Drawing.Point(138, 90);
            this.txtTelefono.Name = "txtTelefono";
            this.txtTelefono.Size = new System.Drawing.Size(186, 23);
            this.txtTelefono.TabIndex = 8;
            // 
            // txtCognome
            // 
            this.txtCognome.Location = new System.Drawing.Point(138, 61);
            this.txtCognome.Name = "txtCognome";
            this.txtCognome.Size = new System.Drawing.Size(186, 23);
            this.txtCognome.TabIndex = 7;
            // 
            // txtNome
            // 
            this.txtNome.Location = new System.Drawing.Point(138, 32);
            this.txtNome.Name = "txtNome";
            this.txtNome.Size = new System.Drawing.Size(186, 23);
            this.txtNome.TabIndex = 6;
            // 
            // lblTariffaOraria
            // 
            this.lblTariffaOraria.AutoSize = true;
            this.lblTariffaOraria.Location = new System.Drawing.Point(135, 155);
            this.lblTariffaOraria.Name = "lblTariffaOraria";
            this.lblTariffaOraria.Size = new System.Drawing.Size(77, 15);
            this.lblTariffaOraria.TabIndex = 4;
            this.lblTariffaOraria.Text = "Tariffa Oraria:";
            // 
            // lblEmail
            // 
            this.lblEmail.AutoSize = true;
            this.lblEmail.Location = new System.Drawing.Point(55, 122);
            this.lblEmail.Name = "lblEmail";
            this.lblEmail.Size = new System.Drawing.Size(39, 15);
            this.lblEmail.TabIndex = 3;
            this.lblEmail.Text = "Email:";
            // 
            // lblTelefono
            // 
            this.lblTelefono.AutoSize = true;
            this.lblTelefono.Location = new System.Drawing.Point(55, 93);
            this.lblTelefono.Name = "lblTelefono";
            this.lblTelefono.Size = new System.Drawing.Size(55, 15);
            this.lblTelefono.TabIndex = 2;
            this.lblTelefono.Text = "Telefono:";
            // 
            // lblCognome
            // 
            this.lblCognome.AutoSize = true;
            this.lblCognome.Location = new System.Drawing.Point(55, 64);
            this.lblCognome.Name = "lblCognome";
            this.lblCognome.Size = new System.Drawing.Size(63, 15);
            this.lblCognome.TabIndex = 1;
            this.lblCognome.Text = "Cognome:";
            // 
            // lblNome
            // 
            this.lblNome.AutoSize = true;
            this.lblNome.Location = new System.Drawing.Point(55, 35);
            this.lblNome.Name = "lblNome";
            this.lblNome.Size = new System.Drawing.Size(43, 15);
            this.lblNome.TabIndex = 0;
            this.lblNome.Text = "Nome:";
            // 
            // tabControlDati
            // 
            this.tabControlDati.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.tabControlDati.Controls.Add(this.tabRiepilogo);
            this.tabControlDati.Controls.Add(this.tabLezioniFuture);
            this.tabControlDati.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.tabControlDati.Location = new System.Drawing.Point(0, 286);
            this.tabControlDati.Name = "tabControlDati";
            this.tabControlDati.SelectedIndex = 0;
            this.tabControlDati.Size = new System.Drawing.Size(706, 290);
            this.tabControlDati.TabIndex = 4;
            // 
            // tabRiepilogo
            // 
            this.tabRiepilogo.Controls.Add(this.btnEsporta);
            this.tabRiepilogo.Controls.Add(this.lblTotaleCompenso);
            this.tabRiepilogo.Controls.Add(this.lblTotaleLezioni);
            this.tabRiepilogo.Controls.Add(this.dgvRiepilogoMensile);
            this.tabRiepilogo.Controls.Add(this.cmbAnno);
            this.tabRiepilogo.Controls.Add(this.lblAnno);
            this.tabRiepilogo.Controls.Add(this.lblMese);
            this.tabRiepilogo.Controls.Add(this.cmbMese);
            this.tabRiepilogo.Location = new System.Drawing.Point(4, 24);
            this.tabRiepilogo.Name = "tabRiepilogo";
            this.tabRiepilogo.Padding = new System.Windows.Forms.Padding(3);
            this.tabRiepilogo.Size = new System.Drawing.Size(698, 262);
            this.tabRiepilogo.TabIndex = 0;
            this.tabRiepilogo.Text = "Riepilogo";
            this.tabRiepilogo.UseVisualStyleBackColor = true;
            // 
            // btnEsporta
            // 
            this.btnEsporta.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(255)))), ((int)(((byte)(165)))), ((int)(((byte)(0)))));
            this.btnEsporta.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnEsporta.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.btnEsporta.ForeColor = System.Drawing.Color.White;
            this.btnEsporta.Location = new System.Drawing.Point(592, 7);
            this.btnEsporta.Name = "btnEsporta";
            this.btnEsporta.Size = new System.Drawing.Size(100, 23);
            this.btnEsporta.TabIndex = 7;
            this.btnEsporta.Text = "Esporta ▼";
            this.btnEsporta.UseVisualStyleBackColor = false;
            this.btnEsporta.Click += new System.EventHandler(this.btnEsporta_Click);
            // 
            // lblTotaleCompenso
            // 
            this.lblTotaleCompenso.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Right)));
            this.lblTotaleCompenso.AutoSize = true;
            this.lblTotaleCompenso.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.lblTotaleCompenso.Location = new System.Drawing.Point(508, 234);
            this.lblTotaleCompenso.Name = "lblTotaleCompenso";
            this.lblTotaleCompenso.Size = new System.Drawing.Size(141, 15);
            this.lblTotaleCompenso.TabIndex = 6;
            this.lblTotaleCompenso.Text = "Totale compenso: € 0,00";
            // 
            // lblTotaleLezioni
            // 
            this.lblTotaleLezioni.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Left)));
            this.lblTotaleLezioni.AutoSize = true;
            this.lblTotaleLezioni.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.lblTotaleLezioni.Location = new System.Drawing.Point(12, 234);
            this.lblTotaleLezioni.Name = "lblTotaleLezioni";
            this.lblTotaleLezioni.Size = new System.Drawing.Size(93, 15);
            this.lblTotaleLezioni.TabIndex = 5;
            this.lblTotaleLezioni.Text = "Totale lezioni: 0";
            // 
            // dgvRiepilogoMensile
            // 
            this.dgvRiepilogoMensile.AllowUserToAddRows = false;
            this.dgvRiepilogoMensile.AllowUserToDeleteRows = false;
            this.dgvRiepilogoMensile.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.dgvRiepilogoMensile.BackgroundColor = System.Drawing.SystemColors.Control;
            this.dgvRiepilogoMensile.ColumnHeadersHeightSizeMode = System.Windows.Forms.DataGridViewColumnHeadersHeightSizeMode.AutoSize;
            this.dgvRiepilogoMensile.Location = new System.Drawing.Point(0, 37);
            this.dgvRiepilogoMensile.MultiSelect = false;
            this.dgvRiepilogoMensile.Name = "dgvRiepilogoMensile";
            this.dgvRiepilogoMensile.ReadOnly = true;
            this.dgvRiepilogoMensile.RowHeadersVisible = false;
            this.dgvRiepilogoMensile.SelectionMode = System.Windows.Forms.DataGridViewSelectionMode.FullRowSelect;
            this.dgvRiepilogoMensile.Size = new System.Drawing.Size(698, 189);
            this.dgvRiepilogoMensile.TabIndex = 4;
            // 
            // cmbAnno
            // 
            this.cmbAnno.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbAnno.FormattingEnabled = true;
            this.cmbAnno.Location = new System.Drawing.Point(274, 8);
            this.cmbAnno.Name = "cmbAnno";
            this.cmbAnno.Size = new System.Drawing.Size(88, 23);
            this.cmbAnno.TabIndex = 3;
            this.cmbAnno.SelectedIndexChanged += new System.EventHandler(this.cmbAnno_SelectedIndexChanged);
            // 
            // lblAnno
            // 
            this.lblAnno.AutoSize = true;
            this.lblAnno.Location = new System.Drawing.Point(228, 11);
            this.lblAnno.Name = "lblAnno";
            this.lblAnno.Size = new System.Drawing.Size(39, 15);
            this.lblAnno.TabIndex = 2;
            this.lblAnno.Text = "Anno:";
            // 
            // lblMese
            // 
            this.lblMese.AutoSize = true;
            this.lblMese.Location = new System.Drawing.Point(12, 11);
            this.lblMese.Name = "lblMese";
            this.lblMese.Size = new System.Drawing.Size(38, 15);
            this.lblMese.TabIndex = 0;
            this.lblMese.Text = "Mese:";
            // 
            // cmbMese
            // 
            this.cmbMese.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbMese.FormattingEnabled = true;
            this.cmbMese.Location = new System.Drawing.Point(57, 8);
            this.cmbMese.Name = "cmbMese";
            this.cmbMese.Size = new System.Drawing.Size(155, 23);
            this.cmbMese.TabIndex = 1;
            this.cmbMese.SelectedIndexChanged += new System.EventHandler(this.cmbMese_SelectedIndexChanged);
            // 
            // tabLezioniFuture
            // 
            this.tabLezioniFuture.Controls.Add(this.dgvLezioniFuture);
            this.tabLezioniFuture.Location = new System.Drawing.Point(4, 24);
            this.tabLezioniFuture.Name = "tabLezioniFuture";
            this.tabLezioniFuture.Padding = new System.Windows.Forms.Padding(3);
            this.tabLezioniFuture.Size = new System.Drawing.Size(575, 262);
            this.tabLezioniFuture.TabIndex = 1;
            this.tabLezioniFuture.Text = "Lezioni Future";
            this.tabLezioniFuture.UseVisualStyleBackColor = true;
            // 
            // dgvLezioniFuture
            // 
            this.dgvLezioniFuture.AllowUserToAddRows = false;
            this.dgvLezioniFuture.AllowUserToDeleteRows = false;
            this.dgvLezioniFuture.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.dgvLezioniFuture.BackgroundColor = System.Drawing.SystemColors.Control;
            this.dgvLezioniFuture.ColumnHeadersHeightSizeMode = System.Windows.Forms.DataGridViewColumnHeadersHeightSizeMode.AutoSize;
            this.dgvLezioniFuture.Location = new System.Drawing.Point(6, 6);
            this.dgvLezioniFuture.Name = "dgvLezioniFuture";
            this.dgvLezioniFuture.ReadOnly = true;
            this.dgvLezioniFuture.RowHeadersVisible = false;
            this.dgvLezioniFuture.SelectionMode = System.Windows.Forms.DataGridViewSelectionMode.FullRowSelect;
            this.dgvLezioniFuture.Size = new System.Drawing.Size(563, 250);
            this.dgvLezioniFuture.TabIndex = 0;
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
            this.txtRicerca.Location = new System.Drawing.Point(67, 12);
            this.txtRicerca.Name = "txtRicerca";
            this.txtRicerca.Size = new System.Drawing.Size(200, 23);
            this.txtRicerca.TabIndex = 1;
            this.txtRicerca.TextChanged += new System.EventHandler(this.txtRicerca_TextChanged);
            // 
            // panelRight
            // 
            this.panelRight.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.panelRight.Controls.Add(this.grpDettaglioInsegnante);
            this.panelRight.Controls.Add(this.tabControlDati);
            this.panelRight.Location = new System.Drawing.Point(551, 12);
            this.panelRight.Name = "panelRight";
            this.panelRight.Size = new System.Drawing.Size(706, 576);
            this.panelRight.TabIndex = 5;
            // 
            // InsegnantiControl
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(7F, 15F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.Controls.Add(this.panelRight);
            this.Controls.Add(this.dgvInsegnanti);
            this.Controls.Add(this.txtRicerca);
            this.Controls.Add(this.lblRicerca);
            this.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.Name = "InsegnantiControl";
            this.Size = new System.Drawing.Size(1257, 600);
            ((System.ComponentModel.ISupportInitialize)(this.dgvInsegnanti)).EndInit();
            this.grpDettaglioInsegnante.ResumeLayout(false);
            this.grpDettaglioInsegnante.PerformLayout();
            this.tabControlDati.ResumeLayout(false);
            this.tabRiepilogo.ResumeLayout(false);
            this.tabRiepilogo.PerformLayout();
            ((System.ComponentModel.ISupportInitialize)(this.dgvRiepilogoMensile)).EndInit();
            this.tabLezioniFuture.ResumeLayout(false);
            ((System.ComponentModel.ISupportInitialize)(this.dgvLezioniFuture)).EndInit();
            this.panelRight.ResumeLayout(false);
            this.ResumeLayout(false);
            this.PerformLayout();

        }

        #endregion

        private System.Windows.Forms.DataGridView dgvInsegnanti;
        private System.Windows.Forms.GroupBox grpDettaglioInsegnante;
        private System.Windows.Forms.CheckedListBox clbStrumenti;
        private System.Windows.Forms.Label lblStrumenti;
        private System.Windows.Forms.Button btnWhatsapp;
        private System.Windows.Forms.Button btnEmail;
        private System.Windows.Forms.Button btnSalva;
        private System.Windows.Forms.Button btnNuovo;
        private System.Windows.Forms.Button btnElimina;
        private System.Windows.Forms.TextBox txtTariffaOraria;
        private System.Windows.Forms.TextBox txtEmail;
        private System.Windows.Forms.TextBox txtTelefono;
        private System.Windows.Forms.TextBox txtCognome;
        private System.Windows.Forms.TextBox txtNome;
        private System.Windows.Forms.Label lblTariffaOraria;
        private System.Windows.Forms.Label lblEmail;
        private System.Windows.Forms.Label lblTelefono;
        private System.Windows.Forms.Label lblCognome;
        private System.Windows.Forms.Label lblNome;
        private System.Windows.Forms.TabControl tabControlDati;
        private System.Windows.Forms.TabPage tabRiepilogo;
        private System.Windows.Forms.Button btnEsporta;
        private System.Windows.Forms.Label lblTotaleCompenso;
        private System.Windows.Forms.Label lblTotaleLezioni;
        private System.Windows.Forms.DataGridView dgvRiepilogoMensile;
        private System.Windows.Forms.ComboBox cmbAnno;
        private System.Windows.Forms.Label lblAnno;
        private System.Windows.Forms.Label lblMese;
        private System.Windows.Forms.ComboBox cmbMese;
        private System.Windows.Forms.TabPage tabLezioniFuture;
        private System.Windows.Forms.DataGridView dgvLezioniFuture;
        private System.Windows.Forms.Label lblRicerca;
        private System.Windows.Forms.TextBox txtRicerca;
        private System.Windows.Forms.Panel panelRight;
        private System.Windows.Forms.Label lblTariffaOrariaCoppia;
        private System.Windows.Forms.TextBox txtTariffaOrariaCoppia;
    }
}