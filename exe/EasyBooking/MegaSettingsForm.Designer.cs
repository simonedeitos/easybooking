namespace EasyBooking
{
    partial class MegaSettingsForm
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
            this.lblCliente = new System.Windows.Forms.Label();
            this.grpMegaSettings = new System.Windows.Forms.GroupBox();
            this.btnTestCartella = new System.Windows.Forms.Button();
            this.btnSfogliaCartella = new System.Windows.Forms.Button();
            this.txtCartellaLocale = new System.Windows.Forms.TextBox();
            this.lblCartellaLocale = new System.Windows.Forms.Label();
            this.btnTestLink = new System.Windows.Forms.Button();
            this.txtMegaLink = new System.Windows.Forms.TextBox();
            this.lblMegaLink = new System.Windows.Forms.Label();
            this.btnOK = new System.Windows.Forms.Button();
            this.btnAnnulla = new System.Windows.Forms.Button();
            this.btnPulisci = new System.Windows.Forms.Button();
            this.lblInfo = new System.Windows.Forms.Label();
            this.grpMegaSettings.SuspendLayout();
            this.SuspendLayout();
            // 
            // lblCliente
            // 
            this.lblCliente.AutoSize = true;
            this.lblCliente.Font = new System.Drawing.Font("Segoe UI", 12F, System.Drawing.FontStyle.Bold);
            this.lblCliente.Location = new System.Drawing.Point(12, 15);
            this.lblCliente.Name = "lblCliente";
            this.lblCliente.Size = new System.Drawing.Size(210, 21);
            this.lblCliente.TabIndex = 0;
            this.lblCliente.Text = "Impostazioni MEGA per: ...";
            // 
            // grpMegaSettings
            // 
            this.grpMegaSettings.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.grpMegaSettings.Controls.Add(this.btnTestCartella);
            this.grpMegaSettings.Controls.Add(this.btnSfogliaCartella);
            this.grpMegaSettings.Controls.Add(this.txtCartellaLocale);
            this.grpMegaSettings.Controls.Add(this.lblCartellaLocale);
            this.grpMegaSettings.Controls.Add(this.btnTestLink);
            this.grpMegaSettings.Controls.Add(this.txtMegaLink);
            this.grpMegaSettings.Controls.Add(this.lblMegaLink);
            this.grpMegaSettings.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.grpMegaSettings.Location = new System.Drawing.Point(12, 50);
            this.grpMegaSettings.Name = "grpMegaSettings";
            this.grpMegaSettings.Size = new System.Drawing.Size(580, 160);
            this.grpMegaSettings.TabIndex = 1;
            this.grpMegaSettings.TabStop = false;
            this.grpMegaSettings.Text = "Configurazione MEGA";
            // 
            // btnTestCartella
            // 
            this.btnTestCartella.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Right)));
            this.btnTestCartella.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(34)))), ((int)(((byte)(139)))), ((int)(((byte)(34)))));
            this.btnTestCartella.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnTestCartella.Font = new System.Drawing.Font("Segoe UI", 8F, System.Drawing.FontStyle.Bold);
            this.btnTestCartella.ForeColor = System.Drawing.Color.White;
            this.btnTestCartella.Location = new System.Drawing.Point(485, 110);
            this.btnTestCartella.Name = "btnTestCartella";
            this.btnTestCartella.Size = new System.Drawing.Size(80, 23);
            this.btnTestCartella.TabIndex = 6;
            this.btnTestCartella.Text = "Test";
            this.btnTestCartella.UseVisualStyleBackColor = false;
            this.btnTestCartella.Click += new System.EventHandler(this.btnTestCartella_Click);
            // 
            // btnSfogliaCartella
            // 
            this.btnSfogliaCartella.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Right)));
            this.btnSfogliaCartella.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(122)))), ((int)(((byte)(204)))));
            this.btnSfogliaCartella.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnSfogliaCartella.Font = new System.Drawing.Font("Segoe UI", 8F, System.Drawing.FontStyle.Bold);
            this.btnSfogliaCartella.ForeColor = System.Drawing.Color.White;
            this.btnSfogliaCartella.Location = new System.Drawing.Point(405, 110);
            this.btnSfogliaCartella.Name = "btnSfogliaCartella";
            this.btnSfogliaCartella.Size = new System.Drawing.Size(70, 23);
            this.btnSfogliaCartella.TabIndex = 5;
            this.btnSfogliaCartella.Text = "Sfoglia";
            this.btnSfogliaCartella.UseVisualStyleBackColor = false;
            this.btnSfogliaCartella.Click += new System.EventHandler(this.btnSfogliaCartella_Click);
            // 
            // txtCartellaLocale
            // 
            this.txtCartellaLocale.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.txtCartellaLocale.Location = new System.Drawing.Point(15, 110);
            this.txtCartellaLocale.Name = "txtCartellaLocale";
            this.txtCartellaLocale.Size = new System.Drawing.Size(380, 23);
            this.txtCartellaLocale.TabIndex = 4;
            // 
            // lblCartellaLocale
            // 
            this.lblCartellaLocale.AutoSize = true;
            this.lblCartellaLocale.Location = new System.Drawing.Point(15, 90);
            this.lblCartellaLocale.Name = "lblCartellaLocale";
            this.lblCartellaLocale.Size = new System.Drawing.Size(84, 15);
            this.lblCartellaLocale.TabIndex = 3;
            this.lblCartellaLocale.Text = "Cartella locale:";
            // 
            // btnTestLink
            // 
            this.btnTestLink.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Right)));
            this.btnTestLink.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(220)))), ((int)(((byte)(20)))), ((int)(((byte)(60)))));
            this.btnTestLink.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnTestLink.Font = new System.Drawing.Font("Segoe UI", 8F, System.Drawing.FontStyle.Bold);
            this.btnTestLink.ForeColor = System.Drawing.Color.White;
            this.btnTestLink.Location = new System.Drawing.Point(485, 50);
            this.btnTestLink.Name = "btnTestLink";
            this.btnTestLink.Size = new System.Drawing.Size(80, 23);
            this.btnTestLink.TabIndex = 2;
            this.btnTestLink.Text = "Test Link";
            this.btnTestLink.UseVisualStyleBackColor = false;
            this.btnTestLink.Click += new System.EventHandler(this.btnTestLink_Click);
            // 
            // txtMegaLink
            // 
            this.txtMegaLink.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.txtMegaLink.Location = new System.Drawing.Point(15, 50);
            this.txtMegaLink.Name = "txtMegaLink";
            this.txtMegaLink.Size = new System.Drawing.Size(460, 23);
            this.txtMegaLink.TabIndex = 1;
            // 
            // lblMegaLink
            // 
            this.lblMegaLink.AutoSize = true;
            this.lblMegaLink.Location = new System.Drawing.Point(15, 30);
            this.lblMegaLink.Name = "lblMegaLink";
            this.lblMegaLink.Size = new System.Drawing.Size(122, 15);
            this.lblMegaLink.TabIndex = 0;
            this.lblMegaLink.Text = "Link cartella pubblica:";
            // 
            // btnOK
            // 
            this.btnOK.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Right)));
            this.btnOK.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(164)))), ((int)(((byte)(0)))));
            this.btnOK.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnOK.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.btnOK.ForeColor = System.Drawing.Color.White;
            this.btnOK.Location = new System.Drawing.Point(426, 270);
            this.btnOK.Name = "btnOK";
            this.btnOK.Size = new System.Drawing.Size(80, 30);
            this.btnOK.TabIndex = 4;
            this.btnOK.Text = "OK";
            this.btnOK.UseVisualStyleBackColor = false;
            this.btnOK.Click += new System.EventHandler(this.btnOK_Click);
            // 
            // btnAnnulla
            // 
            this.btnAnnulla.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Right)));
            this.btnAnnulla.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(128)))), ((int)(((byte)(128)))), ((int)(((byte)(128)))));
            this.btnAnnulla.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnAnnulla.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.btnAnnulla.ForeColor = System.Drawing.Color.White;
            this.btnAnnulla.Location = new System.Drawing.Point(512, 270);
            this.btnAnnulla.Name = "btnAnnulla";
            this.btnAnnulla.Size = new System.Drawing.Size(80, 30);
            this.btnAnnulla.TabIndex = 5;
            this.btnAnnulla.Text = "Annulla";
            this.btnAnnulla.UseVisualStyleBackColor = false;
            this.btnAnnulla.Click += new System.EventHandler(this.btnAnnulla_Click);
            // 
            // btnPulisci
            // 
            this.btnPulisci.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Left)));
            this.btnPulisci.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(204)))), ((int)(((byte)(0)))), ((int)(((byte)(0)))));
            this.btnPulisci.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnPulisci.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.btnPulisci.ForeColor = System.Drawing.Color.White;
            this.btnPulisci.Location = new System.Drawing.Point(12, 270);
            this.btnPulisci.Name = "btnPulisci";
            this.btnPulisci.Size = new System.Drawing.Size(100, 30);
            this.btnPulisci.TabIndex = 3;
            this.btnPulisci.Text = "Pulisci tutto";
            this.btnPulisci.UseVisualStyleBackColor = false;
            this.btnPulisci.Click += new System.EventHandler(this.btnPulisci_Click);
            // 
            // lblInfo
            // 
            this.lblInfo.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.lblInfo.Font = new System.Drawing.Font("Segoe UI", 8F);
            this.lblInfo.ForeColor = System.Drawing.Color.FromArgb(((int)(((byte)(64)))), ((int)(((byte)(64)))), ((int)(((byte)(64)))));
            this.lblInfo.Location = new System.Drawing.Point(12, 220);
            this.lblInfo.Name = "lblInfo";
            this.lblInfo.Size = new System.Drawing.Size(580, 40);
            this.lblInfo.TabIndex = 2;
            this.lblInfo.Text = "Il link della cartella pubblica permette di condividere file con il cliente. La c" +
    "artella locale è il percorso dove MEGA sincronizza i file del cliente sul tuo co" +
    "mputer.";
            this.lblInfo.Click += new System.EventHandler(this.lblInfo_Click);
            // 
            // MegaSettingsForm
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(7F, 15F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.ClientSize = new System.Drawing.Size(604, 312);
            this.Controls.Add(this.lblInfo);
            this.Controls.Add(this.btnPulisci);
            this.Controls.Add(this.btnAnnulla);
            this.Controls.Add(this.btnOK);
            this.Controls.Add(this.grpMegaSettings);
            this.Controls.Add(this.lblCliente);
            this.Font = new System.Drawing.Font("Segoe UI", 9F);
            this.FormBorderStyle = System.Windows.Forms.FormBorderStyle.FixedDialog;
            this.MaximizeBox = false;
            this.MinimizeBox = false;
            this.Name = "MegaSettingsForm";
            this.StartPosition = System.Windows.Forms.FormStartPosition.CenterParent;
            this.Text = "Impostazioni MEGA Cliente";
            this.grpMegaSettings.ResumeLayout(false);
            this.grpMegaSettings.PerformLayout();
            this.ResumeLayout(false);
            this.PerformLayout();

        }

        #endregion

        private System.Windows.Forms.Label lblCliente;
        private System.Windows.Forms.GroupBox grpMegaSettings;
        private System.Windows.Forms.Label lblMegaLink;
        private System.Windows.Forms.TextBox txtMegaLink;
        private System.Windows.Forms.Button btnTestLink;
        private System.Windows.Forms.Label lblCartellaLocale;
        private System.Windows.Forms.TextBox txtCartellaLocale;
        private System.Windows.Forms.Button btnSfogliaCartella;
        private System.Windows.Forms.Button btnTestCartella;
        private System.Windows.Forms.Button btnOK;
        private System.Windows.Forms.Button btnAnnulla;
        private System.Windows.Forms.Button btnPulisci;
        private System.Windows.Forms.Label lblInfo;
    }
}