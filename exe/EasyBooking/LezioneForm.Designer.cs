namespace EasyBooking
{
    partial class LezioneForm
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
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(LezioneForm));
            this.lblData = new System.Windows.Forms.Label();
            this.dtpData = new System.Windows.Forms.DateTimePicker();
            this.lblOraInizio = new System.Windows.Forms.Label();
            this.dtpOraInizio = new System.Windows.Forms.DateTimePicker();
            this.lblOraFine = new System.Windows.Forms.Label();
            this.dtpOraFine = new System.Windows.Forms.DateTimePicker();
            this.lblCliente = new System.Windows.Forms.Label();
            this.cmbCliente = new System.Windows.Forms.ComboBox();
            this.lblInsegnante = new System.Windows.Forms.Label();
            this.cmbInsegnante = new System.Windows.Forms.ComboBox();
            this.lblStrumento = new System.Windows.Forms.Label();
            this.cmbStrumento = new System.Windows.Forms.ComboBox();
            this.lblStato = new System.Windows.Forms.Label();
            this.cmbStato = new System.Windows.Forms.ComboBox();
            this.lblAcquistoId = new System.Windows.Forms.Label();
            this.nudAcquistoId = new System.Windows.Forms.NumericUpDown();
            this.btnCercaAcquisto = new System.Windows.Forms.Button();
            this.btnSalva = new System.Windows.Forms.Button();
            this.btnAnnulla = new System.Windows.Forms.Button();
            this.btnElimina = new System.Windows.Forms.Button();
            ((System.ComponentModel.ISupportInitialize)(this.nudAcquistoId)).BeginInit();
            this.SuspendLayout();

            // lblData
            // 
            this.lblData.AutoSize = true;
            this.lblData.Location = new System.Drawing.Point(20, 20);
            this.lblData.Name = "lblData";
            this.lblData.Size = new System.Drawing.Size(34, 15);
            this.lblData.TabIndex = 0;
            this.lblData.Text = "Data:";

            // dtpData
            // 
            this.dtpData.Format = System.Windows.Forms.DateTimePickerFormat.Short;
            this.dtpData.Location = new System.Drawing.Point(110, 16);
            this.dtpData.Name = "dtpData";
            this.dtpData.Size = new System.Drawing.Size(120, 23);
            this.dtpData.TabIndex = 1;

            // lblOraInizio
            // 
            this.lblOraInizio.AutoSize = true;
            this.lblOraInizio.Location = new System.Drawing.Point(20, 50);
            this.lblOraInizio.Name = "lblOraInizio";
            this.lblOraInizio.Size = new System.Drawing.Size(60, 15);
            this.lblOraInizio.TabIndex = 2;
            this.lblOraInizio.Text = "Ora Inizio:";

            // dtpOraInizio
            // 
            this.dtpOraInizio.CustomFormat = "HH:mm";
            this.dtpOraInizio.Format = System.Windows.Forms.DateTimePickerFormat.Custom;
            this.dtpOraInizio.Location = new System.Drawing.Point(110, 46);
            this.dtpOraInizio.Name = "dtpOraInizio";
            this.dtpOraInizio.ShowUpDown = true;
            this.dtpOraInizio.Size = new System.Drawing.Size(80, 23);
            this.dtpOraInizio.TabIndex = 3;

            // lblOraFine
            // 
            this.lblOraFine.AutoSize = true;
            this.lblOraFine.Location = new System.Drawing.Point(20, 80);
            this.lblOraFine.Name = "lblOraFine";
            this.lblOraFine.Size = new System.Drawing.Size(55, 15);
            this.lblOraFine.TabIndex = 4;
            this.lblOraFine.Text = "Ora Fine:";

            // dtpOraFine
            // 
            this.dtpOraFine.CustomFormat = "HH:mm";
            this.dtpOraFine.Format = System.Windows.Forms.DateTimePickerFormat.Custom;
            this.dtpOraFine.Location = new System.Drawing.Point(110, 76);
            this.dtpOraFine.Name = "dtpOraFine";
            this.dtpOraFine.ShowUpDown = true;
            this.dtpOraFine.Size = new System.Drawing.Size(80, 23);
            this.dtpOraFine.TabIndex = 5;

            // lblCliente
            // 
            this.lblCliente.AutoSize = true;
            this.lblCliente.Location = new System.Drawing.Point(20, 110);
            this.lblCliente.Name = "lblCliente";
            this.lblCliente.Size = new System.Drawing.Size(48, 15);
            this.lblCliente.TabIndex = 6;
            this.lblCliente.Text = "Cliente:";

            // cmbCliente
            // 
            this.cmbCliente.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbCliente.FormattingEnabled = true;
            this.cmbCliente.Location = new System.Drawing.Point(110, 106);
            this.cmbCliente.Name = "cmbCliente";
            this.cmbCliente.Size = new System.Drawing.Size(250, 23);
            this.cmbCliente.TabIndex = 7;

            // lblInsegnante
            // 
            this.lblInsegnante.AutoSize = true;
            this.lblInsegnante.Location = new System.Drawing.Point(20, 140);
            this.lblInsegnante.Name = "lblInsegnante";
            this.lblInsegnante.Size = new System.Drawing.Size(68, 15);
            this.lblInsegnante.TabIndex = 8;
            this.lblInsegnante.Text = "Insegnante:";

            // cmbInsegnante
            // 
            this.cmbInsegnante.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbInsegnante.FormattingEnabled = true;
            this.cmbInsegnante.Location = new System.Drawing.Point(110, 136);
            this.cmbInsegnante.Name = "cmbInsegnante";
            this.cmbInsegnante.Size = new System.Drawing.Size(250, 23);
            this.cmbInsegnante.TabIndex = 9;
            this.cmbInsegnante.SelectedIndexChanged += new System.EventHandler(this.cmbInsegnante_SelectedIndexChanged);

            // lblStrumento
            // 
            this.lblStrumento.AutoSize = true;
            this.lblStrumento.Location = new System.Drawing.Point(20, 170);
            this.lblStrumento.Name = "lblStrumento";
            this.lblStrumento.Size = new System.Drawing.Size(65, 15);
            this.lblStrumento.TabIndex = 10;
            this.lblStrumento.Text = "Strumento:";

            // cmbStrumento
            // 
            this.cmbStrumento.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbStrumento.FormattingEnabled = true;
            this.cmbStrumento.Location = new System.Drawing.Point(110, 166);
            this.cmbStrumento.Name = "cmbStrumento";
            this.cmbStrumento.Size = new System.Drawing.Size(250, 23);
            this.cmbStrumento.TabIndex = 11;

            // lblStato
            // 
            this.lblStato.AutoSize = true;
            this.lblStato.Location = new System.Drawing.Point(20, 200);
            this.lblStato.Name = "lblStato";
            this.lblStato.Size = new System.Drawing.Size(37, 15);
            this.lblStato.TabIndex = 12;
            this.lblStato.Text = "Stato:";

            // cmbStato
            // 
            this.cmbStato.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbStato.FormattingEnabled = true;
            this.cmbStato.Location = new System.Drawing.Point(110, 196);
            this.cmbStato.Name = "cmbStato";
            this.cmbStato.Size = new System.Drawing.Size(180, 23);
            this.cmbStato.TabIndex = 13;

            // lblAcquistoId
            // 
            this.lblAcquistoId.AutoSize = true;
            this.lblAcquistoId.Location = new System.Drawing.Point(20, 230);
            this.lblAcquistoId.Name = "lblAcquistoId";
            this.lblAcquistoId.Size = new System.Drawing.Size(79, 15);
            this.lblAcquistoId.TabIndex = 14;
            this.lblAcquistoId.Text = "ID Pacchetto:";

            // nudAcquistoId
            // 
            this.nudAcquistoId.Location = new System.Drawing.Point(110, 226);
            this.nudAcquistoId.Maximum = new decimal(new int[] {
            10000,
            0,
            0,
            0});
            this.nudAcquistoId.Name = "nudAcquistoId";
            this.nudAcquistoId.Size = new System.Drawing.Size(80, 23);
            this.nudAcquistoId.TabIndex = 15;

            // btnCercaAcquisto
            // 
            this.btnCercaAcquisto.Location = new System.Drawing.Point(200, 226);
            this.btnCercaAcquisto.Name = "btnCercaAcquisto";
            this.btnCercaAcquisto.Size = new System.Drawing.Size(75, 23);
            this.btnCercaAcquisto.TabIndex = 16;
            this.btnCercaAcquisto.Text = "Cerca...";
            this.btnCercaAcquisto.UseVisualStyleBackColor = true;
            this.btnCercaAcquisto.Click += new System.EventHandler(this.btnCercaAcquisto_Click);

            // btnSalva
            // 
            this.btnSalva.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(164)))), ((int)(((byte)(0)))));
            this.btnSalva.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnSalva.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.btnSalva.ForeColor = System.Drawing.Color.White;
            this.btnSalva.Location = new System.Drawing.Point(182, 276);
            this.btnSalva.Name = "btnSalva";
            this.btnSalva.Size = new System.Drawing.Size(90, 30);
            this.btnSalva.TabIndex = 17;
            this.btnSalva.Text = "Salva";
            this.btnSalva.UseVisualStyleBackColor = false;
            this.btnSalva.Click += new System.EventHandler(this.btnSalva_Click);

            // btnAnnulla
            // 
            this.btnAnnulla.Location = new System.Drawing.Point(86, 276);
            this.btnAnnulla.Name = "btnAnnulla";
            this.btnAnnulla.Size = new System.Drawing.Size(90, 30);
            this.btnAnnulla.TabIndex = 18;
            this.btnAnnulla.Text = "Annulla";
            this.btnAnnulla.UseVisualStyleBackColor = true;
            this.btnAnnulla.Click += new System.EventHandler(this.btnAnnulla_Click);

            // btnElimina
            // 
            this.btnElimina.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(204)))), ((int)(((byte)(0)))), ((int)(((byte)(0)))));
            this.btnElimina.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnElimina.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.btnElimina.ForeColor = System.Drawing.Color.White;
            this.btnElimina.Location = new System.Drawing.Point(278, 276);
            this.btnElimina.Name = "btnElimina";
            this.btnElimina.Size = new System.Drawing.Size(90, 30);
            this.btnElimina.TabIndex = 19;
            this.btnElimina.Text = "Elimina";
            this.btnElimina.UseVisualStyleBackColor = false;
            this.btnElimina.Click += new System.EventHandler(this.btnElimina_Click);

            // LezioneForm
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(7F, 15F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.ClientSize = new System.Drawing.Size(384, 321);
            this.Controls.Add(this.btnElimina);
            this.Controls.Add(this.btnAnnulla);
            this.Controls.Add(this.btnSalva);
            this.Controls.Add(this.btnCercaAcquisto);
            this.Controls.Add(this.nudAcquistoId);
            this.Controls.Add(this.lblAcquistoId);
            this.Controls.Add(this.cmbStato);
            this.Controls.Add(this.lblStato);
            this.Controls.Add(this.cmbStrumento);
            this.Controls.Add(this.lblStrumento);
            this.Controls.Add(this.cmbInsegnante);
            this.Controls.Add(this.lblInsegnante);
            this.Controls.Add(this.cmbCliente);
            this.Controls.Add(this.lblCliente);
            this.Controls.Add(this.dtpOraFine);
            this.Controls.Add(this.lblOraFine);
            this.Controls.Add(this.dtpOraInizio);
            this.Controls.Add(this.lblOraInizio);
            this.Controls.Add(this.dtpData);
            this.Controls.Add(this.lblData);
            this.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.FormBorderStyle = System.Windows.Forms.FormBorderStyle.FixedDialog;
            this.Icon = ((System.Drawing.Icon)(resources.GetObject("$this.Icon")));
            this.MaximizeBox = false;
            this.MinimizeBox = false;
            this.Name = "LezioneForm";
            this.StartPosition = System.Windows.Forms.FormStartPosition.CenterParent;
            this.Text = "Dettaglio Lezione";
            ((System.ComponentModel.ISupportInitialize)(this.nudAcquistoId)).EndInit();
            this.ResumeLayout(false);
            this.PerformLayout();

        }

        #endregion

        private System.Windows.Forms.Label lblData;
        private System.Windows.Forms.DateTimePicker dtpData;
        private System.Windows.Forms.Label lblOraInizio;
        private System.Windows.Forms.DateTimePicker dtpOraInizio;
        private System.Windows.Forms.Label lblOraFine;
        private System.Windows.Forms.DateTimePicker dtpOraFine;
        private System.Windows.Forms.Label lblCliente;
        private System.Windows.Forms.ComboBox cmbCliente;
        private System.Windows.Forms.Label lblInsegnante;
        private System.Windows.Forms.ComboBox cmbInsegnante;
        private System.Windows.Forms.Label lblStrumento;
        private System.Windows.Forms.ComboBox cmbStrumento;
        private System.Windows.Forms.Label lblStato;
        private System.Windows.Forms.ComboBox cmbStato;
        private System.Windows.Forms.Label lblAcquistoId;
        private System.Windows.Forms.NumericUpDown nudAcquistoId;
        private System.Windows.Forms.Button btnCercaAcquisto;
        private System.Windows.Forms.Button btnSalva;
        private System.Windows.Forms.Button btnAnnulla;
        private System.Windows.Forms.Button btnElimina;
    }
}