namespace EasyBooking
{
    partial class MainForm
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
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(MainForm));
            this.btnClienti = new System.Windows.Forms.Button();
            this.btnInsegnanti = new System.Windows.Forms.Button();
            this.btnAcquisti = new System.Windows.Forms.Button();
            this.btnImpostazioni = new System.Windows.Forms.Button();
            this.btnPlanning = new System.Windows.Forms.Button();
            this.panelMenu = new System.Windows.Forms.Panel();
            this.btnReport = new System.Windows.Forms.Button();
            this.btnDashboard = new System.Windows.Forms.Button();
            this.panelLogo = new System.Windows.Forms.Panel();
            this.lblActivityName = new System.Windows.Forms.Label();
            this.panelContent = new System.Windows.Forms.Panel();
            this.panelMenu.SuspendLayout();
            this.SuspendLayout();
            // 
            // btnClienti
            // 
            this.btnClienti.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(122)))), ((int)(((byte)(204)))));
            this.btnClienti.FlatAppearance.BorderSize = 0;
            this.btnClienti.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnClienti.Font = new System.Drawing.Font("Segoe UI", 10F, System.Drawing.FontStyle.Bold);
            this.btnClienti.ForeColor = System.Drawing.Color.White;
            this.btnClienti.Location = new System.Drawing.Point(0, 120);
            this.btnClienti.Name = "btnClienti";
            this.btnClienti.Size = new System.Drawing.Size(210, 50);
            this.btnClienti.TabIndex = 0;
            this.btnClienti.Text = "Clienti";
            this.btnClienti.UseVisualStyleBackColor = false;
            this.btnClienti.Click += new System.EventHandler(this.btnClienti_Click);
            // 
            // btnInsegnanti
            // 
            this.btnInsegnanti.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(50)))), ((int)(((byte)(50)))), ((int)(((byte)(50)))));
            this.btnInsegnanti.FlatAppearance.BorderSize = 0;
            this.btnInsegnanti.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnInsegnanti.Font = new System.Drawing.Font("Segoe UI", 10F, System.Drawing.FontStyle.Bold);
            this.btnInsegnanti.ForeColor = System.Drawing.Color.White;
            this.btnInsegnanti.Location = new System.Drawing.Point(0, 170);
            this.btnInsegnanti.Name = "btnInsegnanti";
            this.btnInsegnanti.Size = new System.Drawing.Size(210, 50);
            this.btnInsegnanti.TabIndex = 1;
            this.btnInsegnanti.Text = "Insegnanti";
            this.btnInsegnanti.UseVisualStyleBackColor = false;
            this.btnInsegnanti.Click += new System.EventHandler(this.btnInsegnanti_Click);
            // 
            // btnAcquisti
            // 
            this.btnAcquisti.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(50)))), ((int)(((byte)(50)))), ((int)(((byte)(50)))));
            this.btnAcquisti.FlatAppearance.BorderSize = 0;
            this.btnAcquisti.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnAcquisti.Font = new System.Drawing.Font("Segoe UI", 10F, System.Drawing.FontStyle.Bold);
            this.btnAcquisti.ForeColor = System.Drawing.Color.White;
            this.btnAcquisti.Location = new System.Drawing.Point(0, 220);
            this.btnAcquisti.Name = "btnAcquisti";
            this.btnAcquisti.Size = new System.Drawing.Size(210, 50);
            this.btnAcquisti.TabIndex = 2;
            this.btnAcquisti.Text = "Acquisti";
            this.btnAcquisti.UseVisualStyleBackColor = false;
            this.btnAcquisti.Click += new System.EventHandler(this.btnAcquisti_Click);
            // 
            // btnImpostazioni
            // 
            this.btnImpostazioni.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(50)))), ((int)(((byte)(50)))), ((int)(((byte)(50)))));
            this.btnImpostazioni.FlatAppearance.BorderSize = 0;
            this.btnImpostazioni.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnImpostazioni.Font = new System.Drawing.Font("Segoe UI", 10F, System.Drawing.FontStyle.Bold);
            this.btnImpostazioni.ForeColor = System.Drawing.Color.White;
            this.btnImpostazioni.Location = new System.Drawing.Point(0, 270);
            this.btnImpostazioni.Name = "btnImpostazioni";
            this.btnImpostazioni.Size = new System.Drawing.Size(210, 50);
            this.btnImpostazioni.TabIndex = 3;
            this.btnImpostazioni.Text = "Impostazioni";
            this.btnImpostazioni.UseVisualStyleBackColor = false;
            this.btnImpostazioni.Click += new System.EventHandler(this.btnImpostazioni_Click);
            // 
            // btnPlanning
            // 
            this.btnPlanning.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(50)))), ((int)(((byte)(50)))), ((int)(((byte)(50)))));
            this.btnPlanning.FlatAppearance.BorderSize = 0;
            this.btnPlanning.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnPlanning.Font = new System.Drawing.Font("Segoe UI", 10F, System.Drawing.FontStyle.Bold);
            this.btnPlanning.ForeColor = System.Drawing.Color.White;
            this.btnPlanning.Location = new System.Drawing.Point(0, 320);
            this.btnPlanning.Name = "btnPlanning";
            this.btnPlanning.Size = new System.Drawing.Size(210, 50);
            this.btnPlanning.TabIndex = 4;
            this.btnPlanning.Text = "Planning";
            this.btnPlanning.UseVisualStyleBackColor = false;
            this.btnPlanning.Click += new System.EventHandler(this.btnPlanning_Click);
            // 
            // panelMenu
            // 
            this.panelMenu.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(40)))), ((int)(((byte)(40)))), ((int)(((byte)(40)))));
            this.panelMenu.Controls.Add(this.btnReport);
            this.panelMenu.Controls.Add(this.btnDashboard);
            this.panelMenu.Controls.Add(this.panelLogo);
            this.panelMenu.Controls.Add(this.lblActivityName);
            this.panelMenu.Controls.Add(this.btnClienti);
            this.panelMenu.Controls.Add(this.btnInsegnanti);
            this.panelMenu.Controls.Add(this.btnAcquisti);
            this.panelMenu.Controls.Add(this.btnImpostazioni);
            this.panelMenu.Controls.Add(this.btnPlanning);
            this.panelMenu.Dock = System.Windows.Forms.DockStyle.Left;
            this.panelMenu.Location = new System.Drawing.Point(0, 0);
            this.panelMenu.Name = "panelMenu";
            this.panelMenu.Size = new System.Drawing.Size(210, 600);
            this.panelMenu.TabIndex = 0;
            // 
            // btnReport
            // 
            this.btnReport.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(50)))), ((int)(((byte)(50)))), ((int)(((byte)(50)))));
            this.btnReport.FlatAppearance.BorderSize = 0;
            this.btnReport.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnReport.Font = new System.Drawing.Font("Segoe UI", 10F, System.Drawing.FontStyle.Bold);
            this.btnReport.ForeColor = System.Drawing.Color.White;
            this.btnReport.Location = new System.Drawing.Point(0, 420);
            this.btnReport.Name = "btnReport";
            this.btnReport.Size = new System.Drawing.Size(210, 50);
            this.btnReport.TabIndex = 6;
            this.btnReport.Text = "Report";
            this.btnReport.UseVisualStyleBackColor = false;
            this.btnReport.Click += new System.EventHandler(this.btnReport_Click);
            // 
            // btnDashboard
            // 
            this.btnDashboard.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(50)))), ((int)(((byte)(50)))), ((int)(((byte)(50)))));
            this.btnDashboard.FlatAppearance.BorderSize = 0;
            this.btnDashboard.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnDashboard.Font = new System.Drawing.Font("Segoe UI", 10F, System.Drawing.FontStyle.Bold);
            this.btnDashboard.ForeColor = System.Drawing.Color.White;
            this.btnDashboard.Location = new System.Drawing.Point(0, 370);
            this.btnDashboard.Name = "btnDashboard";
            this.btnDashboard.Size = new System.Drawing.Size(210, 50);
            this.btnDashboard.TabIndex = 5;
            this.btnDashboard.Text = "Dashboard";
            this.btnDashboard.UseVisualStyleBackColor = false;
            this.btnDashboard.Click += new System.EventHandler(this.btnDashboard_Click);
            // 
            // panelLogo
            // 
            this.panelLogo.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(30)))), ((int)(((byte)(30)))), ((int)(((byte)(30)))));
            this.panelLogo.BackgroundImageLayout = System.Windows.Forms.ImageLayout.Zoom;
            this.panelLogo.Location = new System.Drawing.Point(55, 12);
            this.panelLogo.Name = "panelLogo";
            this.panelLogo.Size = new System.Drawing.Size(100, 60);
            this.panelLogo.TabIndex = 0;
            this.panelLogo.Paint += new System.Windows.Forms.PaintEventHandler(this.panelLogo_Paint);
            // 
            // lblActivityName
            // 
            this.lblActivityName.Font = new System.Drawing.Font("Segoe UI Semibold", 9.75F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblActivityName.ForeColor = System.Drawing.Color.White;
            this.lblActivityName.Location = new System.Drawing.Point(0, 75);
            this.lblActivityName.Name = "lblActivityName";
            this.lblActivityName.Size = new System.Drawing.Size(210, 30);
            this.lblActivityName.TabIndex = 1;
            this.lblActivityName.Text = "Scuola di Musica";
            this.lblActivityName.TextAlign = System.Drawing.ContentAlignment.MiddleCenter;
            this.lblActivityName.Click += new System.EventHandler(this.lblActivityName_Click);
            // 
            // panelContent
            // 
            this.panelContent.Dock = System.Windows.Forms.DockStyle.Fill;
            this.panelContent.Location = new System.Drawing.Point(210, 0);
            this.panelContent.Name = "panelContent";
            this.panelContent.Size = new System.Drawing.Size(1259, 600);
            this.panelContent.TabIndex = 1;
            // 
            // MainForm
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(7F, 15F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.ClientSize = new System.Drawing.Size(1469, 600);
            this.Controls.Add(this.panelContent);
            this.Controls.Add(this.panelMenu);
            this.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.Icon = ((System.Drawing.Icon)(resources.GetObject("$this.Icon")));
            this.MinimumSize = new System.Drawing.Size(800, 600);
            this.Name = "MainForm";
            this.StartPosition = System.Windows.Forms.FormStartPosition.CenterScreen;
            this.Text = "EasyBooking - Gestione Scuola di Musica";
            this.FormClosing += new System.Windows.Forms.FormClosingEventHandler(this.MainForm_FormClosing);
            this.panelMenu.ResumeLayout(false);
            this.ResumeLayout(false);

        }

        #endregion

        private System.Windows.Forms.Button btnClienti;
        private System.Windows.Forms.Button btnInsegnanti;
        private System.Windows.Forms.Button btnAcquisti;
        private System.Windows.Forms.Button btnImpostazioni;
        private System.Windows.Forms.Button btnPlanning;
        private System.Windows.Forms.Button btnDashboard;
        private System.Windows.Forms.Button btnReport;
        private System.Windows.Forms.Panel panelMenu;
        private System.Windows.Forms.Panel panelLogo;
        private System.Windows.Forms.Label lblActivityName;
        private System.Windows.Forms.Panel panelContent;
    }
}