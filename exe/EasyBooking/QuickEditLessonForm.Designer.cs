namespace EasyBooking
{
    partial class QuickEditLessonForm
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
            this.lblData = new System.Windows.Forms.Label();
            this.dtpData = new System.Windows.Forms.DateTimePicker();
            this.lblOraInizio = new System.Windows.Forms.Label();
            this.dtpOraInizio = new System.Windows.Forms.DateTimePicker();
            this.lblDurata = new System.Windows.Forms.Label();
            this.cmbDurata = new System.Windows.Forms.ComboBox();
            this.lblOraFine = new System.Windows.Forms.Label();
            this.lblInsegnante = new System.Windows.Forms.Label();
            this.cmbInsegnante = new System.Windows.Forms.ComboBox();
            this.lblStrumento = new System.Windows.Forms.Label();
            this.txtStrumento = new System.Windows.Forms.TextBox();
            this.btnSalva = new System.Windows.Forms.Button();
            this.btnAnnulla = new System.Windows.Forms.Button();
            this.panelButtons = new System.Windows.Forms.Panel();
            this.panelButtons.SuspendLayout();
            this.SuspendLayout();
            // 
            // lblData
            // 
            this.lblData.AutoSize = true;
            this.lblData.Location = new System.Drawing.Point(12, 15);
            this.lblData.Name = "lblData";
            this.lblData.Size = new System.Drawing.Size(34, 15);
            this.lblData.TabIndex = 0;
            this.lblData.Text = "Data:";
            // 
            // dtpData
            // 
            this.dtpData.Format = System.Windows.Forms.DateTimePickerFormat.Short;
            this.dtpData.Location = new System.Drawing.Point(15, 33);
            this.dtpData.Name = "dtpData";
            this.dtpData.Size = new System.Drawing.Size(120, 23);
            this.dtpData.TabIndex = 1;
            // 
            // lblOraInizio
            // 
            this.lblOraInizio.AutoSize = true;
            this.lblOraInizio.Location = new System.Drawing.Point(152, 15);
            this.lblOraInizio.Name = "lblOraInizio";
            this.lblOraInizio.Size = new System.Drawing.Size(63, 15);
            this.lblOraInizio.TabIndex = 2;
            this.lblOraInizio.Text = "Ora inizio:";
            // 
            // dtpOraInizio
            // 
            this.dtpOraInizio.CustomFormat = "HH:mm";
            this.dtpOraInizio.Format = System.Windows.Forms.DateTimePickerFormat.Custom;
            this.dtpOraInizio.Location = new System.Drawing.Point(155, 33);
            this.dtpOraInizio.Name = "dtpOraInizio";
            this.dtpOraInizio.ShowUpDown = true;
            this.dtpOraInizio.Size = new System.Drawing.Size(100, 23);
            this.dtpOraInizio.TabIndex = 3;
            this.dtpOraInizio.ValueChanged += new System.EventHandler(this.dtpOraInizio_ValueChanged);
            // 
            // lblDurata
            // 
            this.lblDurata.AutoSize = true;
            this.lblDurata.Location = new System.Drawing.Point(12, 70);
            this.lblDurata.Name = "lblDurata";
            this.lblDurata.Size = new System.Drawing.Size(45, 15);
            this.lblDurata.TabIndex = 4;
            this.lblDurata.Text = "Durata:";
            // 
            // cmbDurata
            // 
            this.cmbDurata.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbDurata.FormattingEnabled = true;
            this.cmbDurata.Location = new System.Drawing.Point(15, 88);
            this.cmbDurata.Name = "cmbDurata";
            this.cmbDurata.Size = new System.Drawing.Size(100, 23);
            this.cmbDurata.TabIndex = 5;
            this.cmbDurata.SelectedIndexChanged += new System.EventHandler(this.cmbDurata_SelectedIndexChanged);
            // 
            // lblOraFine
            // 
            this.lblOraFine.AutoSize = true;
            this.lblOraFine.Location = new System.Drawing.Point(155, 90);
            this.lblOraFine.Name = "lblOraFine";
            this.lblOraFine.Size = new System.Drawing.Size(82, 15);
            this.lblOraFine.TabIndex = 6;
            this.lblOraFine.Text = "Ora fine: --:--";
            // 
            // lblInsegnante
            // 
            this.lblInsegnante.AutoSize = true;
            this.lblInsegnante.Location = new System.Drawing.Point(12, 125);
            this.lblInsegnante.Name = "lblInsegnante";
            this.lblInsegnante.Size = new System.Drawing.Size(68, 15);
            this.lblInsegnante.TabIndex = 7;
            this.lblInsegnante.Text = "Insegnante:";
            // 
            // cmbInsegnante
            // 
            this.cmbInsegnante.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbInsegnante.FormattingEnabled = true;
            this.cmbInsegnante.Location = new System.Drawing.Point(15, 143);
            this.cmbInsegnante.Name = "cmbInsegnante";
            this.cmbInsegnante.Size = new System.Drawing.Size(240, 23);
            this.cmbInsegnante.TabIndex = 8;
            // 
            // lblStrumento
            // 
            this.lblStrumento.AutoSize = true;
            this.lblStrumento.Location = new System.Drawing.Point(12, 180);
            this.lblStrumento.Name = "lblStrumento";
            this.lblStrumento.Size = new System.Drawing.Size(66, 15);
            this.lblStrumento.TabIndex = 9;
            this.lblStrumento.Text = "Strumento:";
            // 
            // txtStrumento
            // 
            this.txtStrumento.Location = new System.Drawing.Point(15, 198);
            this.txtStrumento.Name = "txtStrumento";
            this.txtStrumento.Size = new System.Drawing.Size(240, 23);
            this.txtStrumento.TabIndex = 10;
            // 
            // btnSalva
            // 
            this.btnSalva.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Right)));
            this.btnSalva.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(122)))), ((int)(((byte)(204)))));
            this.btnSalva.FlatAppearance.BorderSize = 0;
            this.btnSalva.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnSalva.ForeColor = System.Drawing.Color.White;
            this.btnSalva.Location = new System.Drawing.Point(95, 10);
            this.btnSalva.Name = "btnSalva";
            this.btnSalva.Size = new System.Drawing.Size(80, 30);
            this.btnSalva.TabIndex = 11;
            this.btnSalva.Text = "Salva";
            this.btnSalva.UseVisualStyleBackColor = false;
            this.btnSalva.Click += new System.EventHandler(this.btnSalva_Click);
            // 
            // btnAnnulla
            // 
            this.btnAnnulla.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Right)));
            this.btnAnnulla.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(108)))), ((int)(((byte)(117)))), ((int)(((byte)(125)))));
            this.btnAnnulla.FlatAppearance.BorderSize = 0;
            this.btnAnnulla.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnAnnulla.ForeColor = System.Drawing.Color.White;
            this.btnAnnulla.Location = new System.Drawing.Point(181, 10);
            this.btnAnnulla.Name = "btnAnnulla";
            this.btnAnnulla.Size = new System.Drawing.Size(80, 30);
            this.btnAnnulla.TabIndex = 12;
            this.btnAnnulla.Text = "Annulla";
            this.btnAnnulla.UseVisualStyleBackColor = false;
            this.btnAnnulla.Click += new System.EventHandler(this.btnAnnulla_Click);
            // 
            // panelButtons
            // 
            this.panelButtons.Controls.Add(this.btnSalva);
            this.panelButtons.Controls.Add(this.btnAnnulla);
            this.panelButtons.Dock = System.Windows.Forms.DockStyle.Bottom;
            this.panelButtons.Location = new System.Drawing.Point(0, 240);
            this.panelButtons.Name = "panelButtons";
            this.panelButtons.Size = new System.Drawing.Size(274, 50);
            this.panelButtons.TabIndex = 13;
            // 
            // QuickEditLessonForm
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(7F, 15F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.ClientSize = new System.Drawing.Size(274, 290);
            this.Controls.Add(this.panelButtons);
            this.Controls.Add(this.txtStrumento);
            this.Controls.Add(this.lblStrumento);
            this.Controls.Add(this.cmbInsegnante);
            this.Controls.Add(this.lblInsegnante);
            this.Controls.Add(this.lblOraFine);
            this.Controls.Add(this.cmbDurata);
            this.Controls.Add(this.lblDurata);
            this.Controls.Add(this.dtpOraInizio);
            this.Controls.Add(this.lblOraInizio);
            this.Controls.Add(this.dtpData);
            this.Controls.Add(this.lblData);
            this.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.FormBorderStyle = System.Windows.Forms.FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.MinimizeBox = false;
            this.Name = "QuickEditLessonForm";
            this.StartPosition = System.Windows.Forms.FormStartPosition.CenterParent;
            this.Text = "Modifica Lezione";
            this.panelButtons.ResumeLayout(false);
            this.ResumeLayout(false);
            this.PerformLayout();

        }

        #endregion

        private System.Windows.Forms.Label lblData;
        private System.Windows.Forms.DateTimePicker dtpData;
        private System.Windows.Forms.Label lblOraInizio;
        private System.Windows.Forms.DateTimePicker dtpOraInizio;
        private System.Windows.Forms.Label lblDurata;
        private System.Windows.Forms.ComboBox cmbDurata;
        private System.Windows.Forms.Label lblOraFine;
        private System.Windows.Forms.Label lblInsegnante;
        private System.Windows.Forms.ComboBox cmbInsegnante;
        private System.Windows.Forms.Label lblStrumento;
        private System.Windows.Forms.TextBox txtStrumento;
        private System.Windows.Forms.Button btnSalva;
        private System.Windows.Forms.Button btnAnnulla;
        private System.Windows.Forms.Panel panelButtons;
    }
}