namespace EasyBooking
{
    partial class AcquistoSelectorForm
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
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(AcquistoSelectorForm));
            this.lblTitolo = new System.Windows.Forms.Label();
            this.dgvAcquisti = new System.Windows.Forms.DataGridView();
            this.lblNoAcquisti = new System.Windows.Forms.Label();
            this.btnSeleziona = new System.Windows.Forms.Button();
            this.btnAnnulla = new System.Windows.Forms.Button();
            ((System.ComponentModel.ISupportInitialize)(this.dgvAcquisti)).BeginInit();
            this.SuspendLayout();
            // 
            // lblTitolo
            // 
            this.lblTitolo.AutoSize = true;
            this.lblTitolo.Font = new System.Drawing.Font("Segoe UI", 9.75F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblTitolo.Location = new System.Drawing.Point(12, 9);
            this.lblTitolo.Name = "lblTitolo";
            this.lblTitolo.Size = new System.Drawing.Size(199, 17);
            this.lblTitolo.TabIndex = 0;
            this.lblTitolo.Text = "Seleziona un pacchetto cliente:";
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
            this.dgvAcquisti.Location = new System.Drawing.Point(12, 36);
            this.dgvAcquisti.MultiSelect = false;
            this.dgvAcquisti.Name = "dgvAcquisti";
            this.dgvAcquisti.ReadOnly = true;
            this.dgvAcquisti.SelectionMode = System.Windows.Forms.DataGridViewSelectionMode.FullRowSelect;
            this.dgvAcquisti.Size = new System.Drawing.Size(560, 284);
            this.dgvAcquisti.TabIndex = 1;
            this.dgvAcquisti.CellDoubleClick += new System.Windows.Forms.DataGridViewCellEventHandler(this.dgvAcquisti_CellDoubleClick);
            // 
            // lblNoAcquisti
            // 
            this.lblNoAcquisti.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.lblNoAcquisti.BackColor = System.Drawing.SystemColors.Control;
            this.lblNoAcquisti.Font = new System.Drawing.Font("Segoe UI", 9.75F, System.Drawing.FontStyle.Italic, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblNoAcquisti.Location = new System.Drawing.Point(14, 130);
            this.lblNoAcquisti.Name = "lblNoAcquisti";
            this.lblNoAcquisti.Size = new System.Drawing.Size(557, 50);
            this.lblNoAcquisti.TabIndex = 2;
            this.lblNoAcquisti.Text = "Nessun pacchetto disponibile per questo cliente.";
            this.lblNoAcquisti.TextAlign = System.Drawing.ContentAlignment.MiddleCenter;
            this.lblNoAcquisti.Visible = false;
            // 
            // btnSeleziona
            // 
            this.btnSeleziona.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Right)));
            this.btnSeleziona.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(164)))), ((int)(((byte)(0)))));
            this.btnSeleziona.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnSeleziona.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.btnSeleziona.ForeColor = System.Drawing.Color.White;
            this.btnSeleziona.Location = new System.Drawing.Point(472, 326);
            this.btnSeleziona.Name = "btnSeleziona";
            this.btnSeleziona.Size = new System.Drawing.Size(100, 30);
            this.btnSeleziona.TabIndex = 3;
            this.btnSeleziona.Text = "Seleziona";
            this.btnSeleziona.UseVisualStyleBackColor = false;
            this.btnSeleziona.Click += new System.EventHandler(this.btnSeleziona_Click);
            // 
            // btnAnnulla
            // 
            this.btnAnnulla.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Right)));
            this.btnAnnulla.Location = new System.Drawing.Point(366, 326);
            this.btnAnnulla.Name = "btnAnnulla";
            this.btnAnnulla.Size = new System.Drawing.Size(100, 30);
            this.btnAnnulla.TabIndex = 4;
            this.btnAnnulla.Text = "Annulla";
            this.btnAnnulla.UseVisualStyleBackColor = true;
            this.btnAnnulla.Click += new System.EventHandler(this.btnAnnulla_Click);
            // 
            // AcquistoSelectorForm
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(7F, 15F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.ClientSize = new System.Drawing.Size(584, 368);
            this.Controls.Add(this.btnAnnulla);
            this.Controls.Add(this.btnSeleziona);
            this.Controls.Add(this.lblNoAcquisti);
            this.Controls.Add(this.dgvAcquisti);
            this.Controls.Add(this.lblTitolo);
            this.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.Icon = ((System.Drawing.Icon)(resources.GetObject("$this.Icon")));
            this.MinimumSize = new System.Drawing.Size(600, 400);
            this.Name = "AcquistoSelectorForm";
            this.StartPosition = System.Windows.Forms.FormStartPosition.CenterParent;
            this.Text = "Seleziona Pacchetto";
            ((System.ComponentModel.ISupportInitialize)(this.dgvAcquisti)).EndInit();
            this.ResumeLayout(false);
            this.PerformLayout();

        }

        #endregion

        private System.Windows.Forms.Label lblTitolo;
        private System.Windows.Forms.DataGridView dgvAcquisti;
        private System.Windows.Forms.Label lblNoAcquisti;
        private System.Windows.Forms.Button btnSeleziona;
        private System.Windows.Forms.Button btnAnnulla;
    }
}