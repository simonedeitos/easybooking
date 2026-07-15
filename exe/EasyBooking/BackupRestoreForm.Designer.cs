namespace EasyBooking
{
    partial class BackupRestoreForm
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
            this.components = new System.ComponentModel.Container();
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(BackupRestoreForm));
            this.lblTitle = new System.Windows.Forms.Label();
            this.lstBackup = new System.Windows.Forms.ListBox();
            this.lblBackupList = new System.Windows.Forms.Label();
            this.btnBackup = new System.Windows.Forms.Button();
            this.btnRestore = new System.Windows.Forms.Button();
            this.btnDelete = new System.Windows.Forms.Button();
            this.txtInfo = new System.Windows.Forms.TextBox();
            this.lblInfo = new System.Windows.Forms.Label();
            this.chkBackupAutomatico = new System.Windows.Forms.CheckBox();
            this.dtpOraBackup = new System.Windows.Forms.DateTimePicker();
            this.lblBackupAutomatico = new System.Windows.Forms.Label();
            this.timerBackupAutomatico = new System.Windows.Forms.Timer(this.components);
            this.progressBar = new System.Windows.Forms.ProgressBar();
            this.lblStatus = new System.Windows.Forms.Label();
            this.btnCancel = new System.Windows.Forms.Button();
            this.btnOpenBackupFolder = new System.Windows.Forms.Button();
            this.SuspendLayout();
            // 
            // lblTitle
            // 
            this.lblTitle.AutoSize = true;
            this.lblTitle.Font = new System.Drawing.Font("Segoe UI", 12F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblTitle.Location = new System.Drawing.Point(12, 9);
            this.lblTitle.Name = "lblTitle";
            this.lblTitle.Size = new System.Drawing.Size(157, 21);
            this.lblTitle.TabIndex = 0;
            this.lblTitle.Text = "Backup e Ripristino";
            // 
            // lstBackup
            // 
            this.lstBackup.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.lstBackup.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lstBackup.FormattingEnabled = true;
            this.lstBackup.ItemHeight = 15;
            this.lstBackup.Location = new System.Drawing.Point(12, 56);
            this.lstBackup.Name = "lstBackup";
            this.lstBackup.Size = new System.Drawing.Size(560, 124);
            this.lstBackup.TabIndex = 1;
            this.lstBackup.SelectedIndexChanged += new System.EventHandler(this.lstBackup_SelectedIndexChanged);
            // 
            // lblBackupList
            // 
            this.lblBackupList.AutoSize = true;
            this.lblBackupList.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblBackupList.Location = new System.Drawing.Point(12, 38);
            this.lblBackupList.Name = "lblBackupList";
            this.lblBackupList.Size = new System.Drawing.Size(109, 15);
            this.lblBackupList.TabIndex = 2;
            this.lblBackupList.Text = "Backup disponibili:";
            // 
            // btnBackup
            // 
            this.btnBackup.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(122)))), ((int)(((byte)(204)))));
            this.btnBackup.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnBackup.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.btnBackup.ForeColor = System.Drawing.Color.White;
            this.btnBackup.Location = new System.Drawing.Point(12, 192);
            this.btnBackup.Name = "btnBackup";
            this.btnBackup.Size = new System.Drawing.Size(120, 30);
            this.btnBackup.TabIndex = 3;
            this.btnBackup.Text = "Crea Backup";
            this.btnBackup.UseVisualStyleBackColor = false;
            this.btnBackup.Click += new System.EventHandler(this.btnBackup_Click);
            // 
            // btnRestore
            // 
            this.btnRestore.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(255)))), ((int)(((byte)(128)))), ((int)(((byte)(0)))));
            this.btnRestore.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnRestore.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.btnRestore.ForeColor = System.Drawing.Color.White;
            this.btnRestore.Location = new System.Drawing.Point(146, 192);
            this.btnRestore.Name = "btnRestore";
            this.btnRestore.Size = new System.Drawing.Size(120, 30);
            this.btnRestore.TabIndex = 4;
            this.btnRestore.Text = "Ripristina";
            this.btnRestore.UseVisualStyleBackColor = false;
            this.btnRestore.Click += new System.EventHandler(this.btnRestore_Click);
            // 
            // btnDelete
            // 
            this.btnDelete.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(204)))), ((int)(((byte)(0)))), ((int)(((byte)(0)))));
            this.btnDelete.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnDelete.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.btnDelete.ForeColor = System.Drawing.Color.White;
            this.btnDelete.Location = new System.Drawing.Point(280, 192);
            this.btnDelete.Name = "btnDelete";
            this.btnDelete.Size = new System.Drawing.Size(120, 30);
            this.btnDelete.TabIndex = 5;
            this.btnDelete.Text = "Elimina";
            this.btnDelete.UseVisualStyleBackColor = false;
            this.btnDelete.Click += new System.EventHandler(this.btnDelete_Click);
            // 
            // txtInfo
            // 
            this.txtInfo.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.txtInfo.BackColor = System.Drawing.SystemColors.Window;
            this.txtInfo.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.txtInfo.Location = new System.Drawing.Point(12, 254);
            this.txtInfo.Multiline = true;
            this.txtInfo.Name = "txtInfo";
            this.txtInfo.ReadOnly = true;
            this.txtInfo.Size = new System.Drawing.Size(560, 80);
            this.txtInfo.TabIndex = 6;
            // 
            // lblInfo
            // 
            this.lblInfo.AutoSize = true;
            this.lblInfo.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblInfo.Location = new System.Drawing.Point(12, 236);
            this.lblInfo.Name = "lblInfo";
            this.lblInfo.Size = new System.Drawing.Size(142, 15);
            this.lblInfo.TabIndex = 7;
            this.lblInfo.Text = "Informazioni sul backup:";
            // 
            // chkBackupAutomatico
            // 
            this.chkBackupAutomatico.AutoSize = true;
            this.chkBackupAutomatico.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.chkBackupAutomatico.Location = new System.Drawing.Point(15, 350);
            this.chkBackupAutomatico.Name = "chkBackupAutomatico";
            this.chkBackupAutomatico.Size = new System.Drawing.Size(129, 19);
            this.chkBackupAutomatico.TabIndex = 8;
            this.chkBackupAutomatico.Text = "Backup automatico";
            this.chkBackupAutomatico.UseVisualStyleBackColor = true;
            this.chkBackupAutomatico.CheckedChanged += new System.EventHandler(this.chkBackupAutomatico_CheckedChanged);
            // 
            // dtpOraBackup
            // 
            this.dtpOraBackup.CustomFormat = "HH:mm";
            this.dtpOraBackup.Enabled = false;
            this.dtpOraBackup.Format = System.Windows.Forms.DateTimePickerFormat.Custom;
            this.dtpOraBackup.Location = new System.Drawing.Point(160, 348);
            this.dtpOraBackup.Name = "dtpOraBackup";
            this.dtpOraBackup.ShowUpDown = true;
            this.dtpOraBackup.Size = new System.Drawing.Size(80, 23);
            this.dtpOraBackup.TabIndex = 9;
            this.dtpOraBackup.ValueChanged += new System.EventHandler(this.dtpOraBackup_ValueChanged);
            // 
            // lblBackupAutomatico
            // 
            this.lblBackupAutomatico.AutoSize = true;
            this.lblBackupAutomatico.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblBackupAutomatico.Location = new System.Drawing.Point(12, 380);
            this.lblBackupAutomatico.Name = "lblBackupAutomatico";
            this.lblBackupAutomatico.Size = new System.Drawing.Size(226, 15);
            this.lblBackupAutomatico.TabIndex = 10;
            this.lblBackupAutomatico.Text = "Prossimo backup automatico: 26/06/2025";
            this.lblBackupAutomatico.Visible = false;
            // 
            // progressBar
            // 
            this.progressBar.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.progressBar.Location = new System.Drawing.Point(12, 409);
            this.progressBar.Name = "progressBar";
            this.progressBar.Size = new System.Drawing.Size(450, 23);
            this.progressBar.TabIndex = 11;
            this.progressBar.Visible = false;
            // 
            // lblStatus
            // 
            this.lblStatus.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Left)));
            this.lblStatus.AutoSize = true;
            this.lblStatus.Location = new System.Drawing.Point(12, 435);
            this.lblStatus.Name = "lblStatus";
            this.lblStatus.Size = new System.Drawing.Size(42, 15);
            this.lblStatus.TabIndex = 12;
            this.lblStatus.Text = "Status:";
            this.lblStatus.Visible = false;
            // 
            // btnCancel
            // 
            this.btnCancel.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Bottom | System.Windows.Forms.AnchorStyles.Right)));
            this.btnCancel.Location = new System.Drawing.Point(468, 409);
            this.btnCancel.Name = "btnCancel";
            this.btnCancel.Size = new System.Drawing.Size(104, 23);
            this.btnCancel.TabIndex = 13;
            this.btnCancel.Text = "Annulla";
            this.btnCancel.UseVisualStyleBackColor = true;
            this.btnCancel.Visible = false;
            this.btnCancel.Click += new System.EventHandler(this.btnCancel_Click);
            // 
            // btnOpenBackupFolder
            // 
            this.btnOpenBackupFolder.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Right)));
            this.btnOpenBackupFolder.Location = new System.Drawing.Point(421, 192);
            this.btnOpenBackupFolder.Name = "btnOpenBackupFolder";
            this.btnOpenBackupFolder.Size = new System.Drawing.Size(151, 30);
            this.btnOpenBackupFolder.TabIndex = 14;
            this.btnOpenBackupFolder.Text = "Apri cartella backup";
            this.btnOpenBackupFolder.UseVisualStyleBackColor = true;
            this.btnOpenBackupFolder.Click += new System.EventHandler(this.btnOpenBackupFolder_Click);
            // 
            // BackupRestoreForm
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(7F, 15F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.ClientSize = new System.Drawing.Size(584, 461);
            this.Controls.Add(this.btnOpenBackupFolder);
            this.Controls.Add(this.btnCancel);
            this.Controls.Add(this.lblStatus);
            this.Controls.Add(this.progressBar);
            this.Controls.Add(this.lblBackupAutomatico);
            this.Controls.Add(this.dtpOraBackup);
            this.Controls.Add(this.chkBackupAutomatico);
            this.Controls.Add(this.lblInfo);
            this.Controls.Add(this.txtInfo);
            this.Controls.Add(this.btnDelete);
            this.Controls.Add(this.btnRestore);
            this.Controls.Add(this.btnBackup);
            this.Controls.Add(this.lblBackupList);
            this.Controls.Add(this.lstBackup);
            this.Controls.Add(this.lblTitle);
            this.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.Icon = ((System.Drawing.Icon)(resources.GetObject("$this.Icon")));
            this.MinimumSize = new System.Drawing.Size(600, 500);
            this.Name = "BackupRestoreForm";
            this.StartPosition = System.Windows.Forms.FormStartPosition.CenterParent;
            this.Text = "Backup e Ripristino - EasyBooking";
            this.FormClosing += new System.Windows.Forms.FormClosingEventHandler(this.BackupRestoreForm_FormClosing);
            this.ResumeLayout(false);
            this.PerformLayout();

        }

        #endregion

        private System.Windows.Forms.Label lblTitle;
        private System.Windows.Forms.ListBox lstBackup;
        private System.Windows.Forms.Label lblBackupList;
        private System.Windows.Forms.Button btnBackup;
        private System.Windows.Forms.Button btnRestore;
        private System.Windows.Forms.Button btnDelete;
        private System.Windows.Forms.TextBox txtInfo;
        private System.Windows.Forms.Label lblInfo;
        private System.Windows.Forms.CheckBox chkBackupAutomatico;
        private System.Windows.Forms.DateTimePicker dtpOraBackup;
        private System.Windows.Forms.Label lblBackupAutomatico;
        private System.Windows.Forms.Timer timerBackupAutomatico;
        private System.Windows.Forms.ProgressBar progressBar;
        private System.Windows.Forms.Label lblStatus;
        private System.Windows.Forms.Button btnCancel;
        private System.Windows.Forms.Button btnOpenBackupFolder;
    }
}