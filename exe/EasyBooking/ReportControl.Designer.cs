namespace EasyBooking
{
    partial class ReportControl
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
            this.pnlFiltri = new System.Windows.Forms.Panel();
            this.btnGenera = new System.Windows.Forms.Button();
            this.lblFormato = new System.Windows.Forms.Label();
            this.cmbFormato = new System.Windows.Forms.ComboBox();
            this.lblDataFine = new System.Windows.Forms.Label();
            this.dtpDataFine = new System.Windows.Forms.DateTimePicker();
            this.lblDataInizio = new System.Windows.Forms.Label();
            this.dtpDataInizio = new System.Windows.Forms.DateTimePicker();
            this.lblCliente = new System.Windows.Forms.Label();
            this.cmbCliente = new System.Windows.Forms.ComboBox();
            this.lblInsegnante = new System.Windows.Forms.Label();
            this.cmbInsegnante = new System.Windows.Forms.ComboBox();
            this.lblRaggruppamento = new System.Windows.Forms.Label();
            this.cmbRaggruppamento = new System.Windows.Forms.ComboBox();
            this.lblTipoReport = new System.Windows.Forms.Label();
            this.cmbTipoReport = new System.Windows.Forms.ComboBox();
            this.lblReport = new System.Windows.Forms.Label();
            this.pnlReport = new System.Windows.Forms.Panel();
            this.dgvReport = new System.Windows.Forms.DataGridView();
            this.lblTitoloReport = new System.Windows.Forms.Label();
            this.pnlFiltri.SuspendLayout();
            this.pnlReport.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)(this.dgvReport)).BeginInit();
            this.SuspendLayout();

            // pnlFiltri
            // 
            this.pnlFiltri.BorderStyle = System.Windows.Forms.BorderStyle.FixedSingle;
            this.pnlFiltri.Controls.Add(this.btnGenera);
            this.pnlFiltri.Controls.Add(this.lblFormato);
            this.pnlFiltri.Controls.Add(this.cmbFormato);
            this.pnlFiltri.Controls.Add(this.lblDataFine);
            this.pnlFiltri.Controls.Add(this.dtpDataFine);
            this.pnlFiltri.Controls.Add(this.lblDataInizio);
            this.pnlFiltri.Controls.Add(this.dtpDataInizio);
            this.pnlFiltri.Controls.Add(this.lblCliente);
            this.pnlFiltri.Controls.Add(this.cmbCliente);
            this.pnlFiltri.Controls.Add(this.lblInsegnante);
            this.pnlFiltri.Controls.Add(this.cmbInsegnante);
            this.pnlFiltri.Controls.Add(this.lblRaggruppamento);
            this.pnlFiltri.Controls.Add(this.cmbRaggruppamento);
            this.pnlFiltri.Controls.Add(this.lblTipoReport);
            this.pnlFiltri.Controls.Add(this.cmbTipoReport);
            this.pnlFiltri.Controls.Add(this.lblReport);
            this.pnlFiltri.Dock = System.Windows.Forms.DockStyle.Top;
            this.pnlFiltri.Location = new System.Drawing.Point(0, 0);
            this.pnlFiltri.Name = "pnlFiltri";
            this.pnlFiltri.Size = new System.Drawing.Size(913, 150);
            this.pnlFiltri.TabIndex = 0;

            // btnGenera
            // 
            this.btnGenera.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(122)))), ((int)(((byte)(204)))));
            this.btnGenera.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnGenera.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.btnGenera.ForeColor = System.Drawing.Color.White;
            this.btnGenera.Location = new System.Drawing.Point(753, 107);
            this.btnGenera.Name = "btnGenera";
            this.btnGenera.Size = new System.Drawing.Size(150, 30);
            this.btnGenera.TabIndex = 15;
            this.btnGenera.Text = "Genera Report";
            this.btnGenera.UseVisualStyleBackColor = false;

            // lblFormato
            // 
            this.lblFormato.AutoSize = true;
            this.lblFormato.Location = new System.Drawing.Point(15, 110);
            this.lblFormato.Name = "lblFormato";
            this.lblFormato.Size = new System.Drawing.Size(56, 15);
            this.lblFormato.TabIndex = 14;
            this.lblFormato.Text = "Formato:";

            // cmbFormato
            // 
            this.cmbFormato.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbFormato.FormattingEnabled = true;
            this.cmbFormato.Location = new System.Drawing.Point(135, 107);
            this.cmbFormato.Name = "cmbFormato";
            this.cmbFormato.Size = new System.Drawing.Size(150, 23);
            this.cmbFormato.TabIndex = 13;

            // lblDataFine
            // 
            this.lblDataFine.AutoSize = true;
            this.lblDataFine.Location = new System.Drawing.Point(350, 45);
            this.lblDataFine.Name = "lblDataFine";
            this.lblDataFine.Size = new System.Drawing.Size(61, 15);
            this.lblDataFine.TabIndex = 12;
            this.lblDataFine.Text = "Data Fine:";

            // dtpDataFine
            // 
            this.dtpDataFine.Format = System.Windows.Forms.DateTimePickerFormat.Short;
            this.dtpDataFine.Location = new System.Drawing.Point(417, 40);
            this.dtpDataFine.Name = "dtpDataFine";
            this.dtpDataFine.Size = new System.Drawing.Size(100, 23);
            this.dtpDataFine.TabIndex = 11;

            // lblDataInizio
            // 
            this.lblDataInizio.AutoSize = true;
            this.lblDataInizio.Location = new System.Drawing.Point(15, 45);
            this.lblDataInizio.Name = "lblDataInizio";
            this.lblDataInizio.Size = new System.Drawing.Size(67, 15);
            this.lblDataInizio.TabIndex = 10;
            this.lblDataInizio.Text = "Data Inizio:";

            // dtpDataInizio
            // 
            this.dtpDataInizio.Format = System.Windows.Forms.DateTimePickerFormat.Short;
            this.dtpDataInizio.Location = new System.Drawing.Point(135, 40);
            this.dtpDataInizio.Name = "dtpDataInizio";
            this.dtpDataInizio.Size = new System.Drawing.Size(100, 23);
            this.dtpDataInizio.TabIndex = 9;

            // lblCliente
            // 
            this.lblCliente.AutoSize = true;
            this.lblCliente.Location = new System.Drawing.Point(350, 78);
            this.lblCliente.Name = "lblCliente";
            this.lblCliente.Size = new System.Drawing.Size(48, 15);
            this.lblCliente.TabIndex = 8;
            this.lblCliente.Text = "Cliente:";

            // cmbCliente
            // 
            this.cmbCliente.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbCliente.FormattingEnabled = true;
            this.cmbCliente.Location = new System.Drawing.Point(417, 75);
            this.cmbCliente.Name = "cmbCliente";
            this.cmbCliente.Size = new System.Drawing.Size(200, 23);
            this.cmbCliente.TabIndex = 7;

            // lblInsegnante
            // 
            this.lblInsegnante.AutoSize = true;
            this.lblInsegnante.Location = new System.Drawing.Point(15, 78);
            this.lblInsegnante.Name = "lblInsegnante";
            this.lblInsegnante.Size = new System.Drawing.Size(70, 15);
            this.lblInsegnante.TabIndex = 6;
            this.lblInsegnante.Text = "Insegnante:";

            // cmbInsegnante
            // 
            this.cmbInsegnante.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbInsegnante.FormattingEnabled = true;
            this.cmbInsegnante.Location = new System.Drawing.Point(135, 75);
            this.cmbInsegnante.Name = "cmbInsegnante";
            this.cmbInsegnante.Size = new System.Drawing.Size(200, 23);
            this.cmbInsegnante.TabIndex = 5;

            // lblRaggruppamento
            // 
            this.lblRaggruppamento.AutoSize = true;
            this.lblRaggruppamento.Location = new System.Drawing.Point(350, 110);
            this.lblRaggruppamento.Name = "lblRaggruppamento";
            this.lblRaggruppamento.Size = new System.Drawing.Size(99, 15);
            this.lblRaggruppamento.TabIndex = 4;
            this.lblRaggruppamento.Text = "Raggruppamento:";

            // cmbRaggruppamento
            // 
            this.cmbRaggruppamento.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbRaggruppamento.FormattingEnabled = true;
            this.cmbRaggruppamento.Location = new System.Drawing.Point(455, 107);
            this.cmbRaggruppamento.Name = "cmbRaggruppamento";
            this.cmbRaggruppamento.Size = new System.Drawing.Size(150, 23);
            this.cmbRaggruppamento.TabIndex = 3;

            // lblTipoReport
            // 
            this.lblTipoReport.AutoSize = true;
            this.lblTipoReport.Location = new System.Drawing.Point(535, 15);
            this.lblTipoReport.Name = "lblTipoReport";
            this.lblTipoReport.Size = new System.Drawing.Size(70, 15);
            this.lblTipoReport.TabIndex = 2;
            this.lblTipoReport.Text = "Tipo Report:";

            // cmbTipoReport
            // 
            this.cmbTipoReport.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbTipoReport.FormattingEnabled = true;
            this.cmbTipoReport.Location = new System.Drawing.Point(611, 12);
            this.cmbTipoReport.Name = "cmbTipoReport";
            this.cmbTipoReport.Size = new System.Drawing.Size(200, 23);
            this.cmbTipoReport.TabIndex = 1;

            // lblReport
            // 
            this.lblReport.AutoSize = true;
            this.lblReport.Font = new System.Drawing.Font("Segoe UI", 12F, System.Drawing.FontStyle.Bold);
            this.lblReport.Location = new System.Drawing.Point(10, 10);
            this.lblReport.Name = "lblReport";
            this.lblReport.Size = new System.Drawing.Size(175, 21);
            this.lblReport.TabIndex = 0;
            this.lblReport.Text = "Generazione di Report";

            // pnlReport
            // 
            this.pnlReport.BorderStyle = System.Windows.Forms.BorderStyle.FixedSingle;
            this.pnlReport.Controls.Add(this.dgvReport);
            this.pnlReport.Controls.Add(this.lblTitoloReport);
            this.pnlReport.Dock = System.Windows.Forms.DockStyle.Fill;
            this.pnlReport.Location = new System.Drawing.Point(0, 150);
            this.pnlReport.Name = "pnlReport";
            this.pnlReport.Size = new System.Drawing.Size(913, 450);
            this.pnlReport.TabIndex = 1;

            // dgvReport
            // 
            this.dgvReport.AllowUserToAddRows = false;
            this.dgvReport.AllowUserToDeleteRows = false;
            this.dgvReport.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom)
            | System.Windows.Forms.AnchorStyles.Left)
            | System.Windows.Forms.AnchorStyles.Right)));
            this.dgvReport.BackgroundColor = System.Drawing.Color.White;
            this.dgvReport.ColumnHeadersHeightSizeMode = System.Windows.Forms.DataGridViewColumnHeadersHeightSizeMode.AutoSize;
            this.dgvReport.Location = new System.Drawing.Point(10, 40);
            this.dgvReport.Name = "dgvReport";
            this.dgvReport.ReadOnly = true;
            this.dgvReport.RowHeadersVisible = false;
            this.dgvReport.Size = new System.Drawing.Size(891, 398);
            this.dgvReport.TabIndex = 1;

            // lblTitoloReport
            // 
            this.lblTitoloReport.AutoSize = true;
            this.lblTitoloReport.Font = new System.Drawing.Font("Segoe UI", 9.75F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblTitoloReport.Location = new System.Drawing.Point(10, 10);
            this.lblTitoloReport.Name = "lblTitoloReport";
            this.lblTitoloReport.Size = new System.Drawing.Size(159, 17);
            this.lblTitoloReport.TabIndex = 0;
            this.lblTitoloReport.Text = "Genera un report sopra...";

            // ReportControl
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(7F, 15F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.Controls.Add(this.pnlReport);
            this.Controls.Add(this.pnlFiltri);
            this.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.Name = "ReportControl";
            this.Size = new System.Drawing.Size(913, 600);
            this.pnlFiltri.ResumeLayout(false);
            this.pnlFiltri.PerformLayout();
            this.pnlReport.ResumeLayout(false);
            this.pnlReport.PerformLayout();
            ((System.ComponentModel.ISupportInitialize)(this.dgvReport)).EndInit();
            this.ResumeLayout(false);

        }

        #endregion

        private System.Windows.Forms.Panel pnlFiltri;
        private System.Windows.Forms.Label lblReport;
        private System.Windows.Forms.Label lblTipoReport;
        private System.Windows.Forms.ComboBox cmbTipoReport;
        private System.Windows.Forms.Label lblRaggruppamento;
        private System.Windows.Forms.ComboBox cmbRaggruppamento;
        private System.Windows.Forms.Label lblInsegnante;
        private System.Windows.Forms.ComboBox cmbInsegnante;
        private System.Windows.Forms.Label lblCliente;
        private System.Windows.Forms.ComboBox cmbCliente;
        private System.Windows.Forms.Label lblDataInizio;
        private System.Windows.Forms.DateTimePicker dtpDataInizio;
        private System.Windows.Forms.Label lblDataFine;
        private System.Windows.Forms.DateTimePicker dtpDataFine;
        private System.Windows.Forms.Label lblFormato;
        private System.Windows.Forms.ComboBox cmbFormato;
        private System.Windows.Forms.Button btnGenera;
        private System.Windows.Forms.Panel pnlReport;
        private System.Windows.Forms.Label lblTitoloReport;
        private System.Windows.Forms.DataGridView dgvReport;
    }
}