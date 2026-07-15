namespace EasyBooking
{
    partial class PlanningForm
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
            System.ComponentModel.ComponentResourceManager resources = new System.ComponentModel.ComponentResourceManager(typeof(PlanningForm));
            this.panelControls = new System.Windows.Forms.Panel();
            this.lblCliente = new System.Windows.Forms.Label();
            this.cmbClienti = new System.Windows.Forms.ComboBox();
            this.lblInsegnante = new System.Windows.Forms.Label();
            this.cmbInsegnanti = new System.Windows.Forms.ComboBox();
            this.lblWeek = new System.Windows.Forms.Label();
            this.btnToday = new System.Windows.Forms.Button();
            this.btnNextWeek = new System.Windows.Forms.Button();
            this.btnPrevWeek = new System.Windows.Forms.Button();
            this.dtpSettimana = new System.Windows.Forms.DateTimePicker();
            this.panelMain = new System.Windows.Forms.Panel();
            this.panelPlanning = new System.Windows.Forms.Panel();
            this.statusStrip = new System.Windows.Forms.StatusStrip();
            this.lblLeggenda = new System.Windows.Forms.ToolStripStatusLabel();
            this.colorProgrammata = new System.Windows.Forms.ToolStripStatusLabel();
            this.lblProgrammata = new System.Windows.Forms.ToolStripStatusLabel();
            this.colorSvolta = new System.Windows.Forms.ToolStripStatusLabel();
            this.lblSvolta = new System.Windows.Forms.ToolStripStatusLabel();
            this.colorAssente = new System.Windows.Forms.ToolStripStatusLabel();
            this.lblAssente = new System.Windows.Forms.ToolStripStatusLabel();
            this.colorRimandata = new System.Windows.Forms.ToolStripStatusLabel();
            this.lblRimandata = new System.Windows.Forms.ToolStripStatusLabel();
            this.colorRiprogrammata = new System.Windows.Forms.ToolStripStatusLabel();
            this.lblRiprogrammata = new System.Windows.Forms.ToolStripStatusLabel();
            this.lblInstructions = new System.Windows.Forms.ToolStripStatusLabel();
            this.splitContainerRight = new System.Windows.Forms.SplitContainer();
            this.lstTodayAppointments = new System.Windows.Forms.ListView();
            this.lblTodayTitle = new System.Windows.Forms.Label();
            this.panelAppointmentDetail = new System.Windows.Forms.Panel();
            this.lblAppointmentDetailWelcome = new System.Windows.Forms.Label();
            this.lblAppointmentDetails = new System.Windows.Forms.Label();
            this.lblAppointmentDetailTitle = new System.Windows.Forms.Label();
            this.panelControls.SuspendLayout();
            this.panelMain.SuspendLayout();
            this.statusStrip.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)(this.splitContainerRight)).BeginInit();
            this.splitContainerRight.Panel1.SuspendLayout();
            this.splitContainerRight.Panel2.SuspendLayout();
            this.splitContainerRight.SuspendLayout();
            this.panelAppointmentDetail.SuspendLayout();
            this.SuspendLayout();
            // 
            // panelControls
            // 
            this.panelControls.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left)
            | System.Windows.Forms.AnchorStyles.Right)));
            this.panelControls.BackColor = System.Drawing.SystemColors.Control;
            this.panelControls.Controls.Add(this.lblCliente);
            this.panelControls.Controls.Add(this.cmbClienti);
            this.panelControls.Controls.Add(this.lblInsegnante);
            this.panelControls.Controls.Add(this.cmbInsegnanti);
            this.panelControls.Controls.Add(this.lblWeek);
            this.panelControls.Controls.Add(this.btnToday);
            this.panelControls.Controls.Add(this.btnNextWeek);
            this.panelControls.Controls.Add(this.btnPrevWeek);
            this.panelControls.Controls.Add(this.dtpSettimana);
            this.panelControls.Location = new System.Drawing.Point(0, 0);
            this.panelControls.Name = "panelControls";
            this.panelControls.Size = new System.Drawing.Size(1464, 60);
            this.panelControls.TabIndex = 0;
            // 
            // lblCliente
            // 
            this.lblCliente.AutoSize = true;
            this.lblCliente.Location = new System.Drawing.Point(690, 15);
            this.lblCliente.Name = "lblCliente";
            this.lblCliente.Size = new System.Drawing.Size(47, 15);
            this.lblCliente.TabIndex = 8;
            this.lblCliente.Text = "Cliente:";
            // 
            // cmbClienti
            // 
            this.cmbClienti.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbClienti.FormattingEnabled = true;
            this.cmbClienti.Location = new System.Drawing.Point(744, 12);
            this.cmbClienti.Name = "cmbClienti";
            this.cmbClienti.Size = new System.Drawing.Size(180, 23);
            this.cmbClienti.TabIndex = 7;
            this.cmbClienti.SelectedIndexChanged += new System.EventHandler(this.cmbClienti_SelectedIndexChanged);
            // 
            // lblInsegnante
            // 
            this.lblInsegnante.AutoSize = true;
            this.lblInsegnante.Location = new System.Drawing.Point(470, 15);
            this.lblInsegnante.Name = "lblInsegnante";
            this.lblInsegnante.Size = new System.Drawing.Size(68, 15);
            this.lblInsegnante.TabIndex = 6;
            this.lblInsegnante.Text = "Insegnante:";
            // 
            // cmbInsegnanti
            // 
            this.cmbInsegnanti.DropDownStyle = System.Windows.Forms.ComboBoxStyle.DropDownList;
            this.cmbInsegnanti.FormattingEnabled = true;
            this.cmbInsegnanti.Location = new System.Drawing.Point(544, 12);
            this.cmbInsegnanti.Name = "cmbInsegnanti";
            this.cmbInsegnanti.Size = new System.Drawing.Size(140, 23);
            this.cmbInsegnanti.TabIndex = 5;
            this.cmbInsegnanti.SelectedIndexChanged += new System.EventHandler(this.cmbInsegnanti_SelectedIndexChanged);
            // 
            // lblWeek
            // 
            this.lblWeek.AutoSize = true;
            this.lblWeek.Font = new System.Drawing.Font("Segoe UI Semibold", 9.75F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblWeek.Location = new System.Drawing.Point(12, 38);
            this.lblWeek.Name = "lblWeek";
            this.lblWeek.Size = new System.Drawing.Size(173, 17);
            this.lblWeek.TabIndex = 4;
            this.lblWeek.Text = "Settimana dal 01/01 al 07/01";
            // 
            // btnToday
            // 
            this.btnToday.Location = new System.Drawing.Point(376, 11);
            this.btnToday.Name = "btnToday";
            this.btnToday.Size = new System.Drawing.Size(75, 23);
            this.btnToday.TabIndex = 3;
            this.btnToday.Text = "Oggi";
            this.btnToday.UseVisualStyleBackColor = true;
            this.btnToday.Click += new System.EventHandler(this.btnToday_Click);
            // 
            // btnNextWeek
            // 
            this.btnNextWeek.Location = new System.Drawing.Point(296, 11);
            this.btnNextWeek.Name = "btnNextWeek";
            this.btnNextWeek.Size = new System.Drawing.Size(75, 23);
            this.btnNextWeek.TabIndex = 2;
            this.btnNextWeek.Text = "▶️";
            this.btnNextWeek.UseVisualStyleBackColor = true;
            this.btnNextWeek.Click += new System.EventHandler(this.btnNextWeek_Click);
            // 
            // btnPrevWeek
            // 
            this.btnPrevWeek.Location = new System.Drawing.Point(215, 11);
            this.btnPrevWeek.Name = "btnPrevWeek";
            this.btnPrevWeek.Size = new System.Drawing.Size(75, 23);
            this.btnPrevWeek.TabIndex = 1;
            this.btnPrevWeek.Text = "◀️";
            this.btnPrevWeek.UseVisualStyleBackColor = true;
            this.btnPrevWeek.Click += new System.EventHandler(this.btnPrevWeek_Click);
            // 
            // dtpSettimana
            // 
            this.dtpSettimana.Format = System.Windows.Forms.DateTimePickerFormat.Short;
            this.dtpSettimana.Location = new System.Drawing.Point(12, 12);
            this.dtpSettimana.Name = "dtpSettimana";
            this.dtpSettimana.Size = new System.Drawing.Size(197, 23);
            this.dtpSettimana.TabIndex = 0;
            this.dtpSettimana.ValueChanged += new System.EventHandler(this.dtpSettimana_ValueChanged);
            // 
            // panelMain
            // 
            this.panelMain.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom)
            | System.Windows.Forms.AnchorStyles.Left)
            | System.Windows.Forms.AnchorStyles.Right)));
            this.panelMain.Controls.Add(this.splitContainerRight);
            this.panelMain.Controls.Add(this.panelPlanning);
            this.panelMain.Location = new System.Drawing.Point(0, 60);
            this.panelMain.Name = "panelMain";
            this.panelMain.Size = new System.Drawing.Size(1464, 814);
            this.panelMain.TabIndex = 1;
            // 
            // panelPlanning
            // 
            this.panelPlanning.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom)
            | System.Windows.Forms.AnchorStyles.Left)
            | System.Windows.Forms.AnchorStyles.Right)));
            this.panelPlanning.AutoScroll = true;
            this.panelPlanning.BackColor = System.Drawing.Color.White;
            this.panelPlanning.Location = new System.Drawing.Point(0, 0);
            this.panelPlanning.Name = "panelPlanning";
            this.panelPlanning.Size = new System.Drawing.Size(1130, 814);
            this.panelPlanning.TabIndex = 0;
            // 
            // statusStrip
            // 
            this.statusStrip.Items.AddRange(new System.Windows.Forms.ToolStripItem[] {
            this.lblLeggenda,
            this.colorProgrammata,
            this.lblProgrammata,
            this.colorSvolta,
            this.lblSvolta,
            this.colorAssente,
            this.lblAssente,
            this.colorRimandata,
            this.lblRimandata,
            this.colorRiprogrammata,
            this.lblRiprogrammata,
            this.lblInstructions});
            this.statusStrip.Location = new System.Drawing.Point(0, 874);
            this.statusStrip.Name = "statusStrip";
            this.statusStrip.Size = new System.Drawing.Size(1464, 22);
            this.statusStrip.TabIndex = 2;
            this.statusStrip.Text = "statusStrip1";
            // 
            // lblLeggenda
            // 
            this.lblLeggenda.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold);
            this.lblLeggenda.Name = "lblLeggenda";
            this.lblLeggenda.Size = new System.Drawing.Size(64, 17);
            this.lblLeggenda.Text = "Leggenda:";
            // 
            // colorProgrammata
            // 
            this.colorProgrammata.BackColor = System.Drawing.Color.LightGreen;
            this.colorProgrammata.Name = "colorProgrammata";
            this.colorProgrammata.Size = new System.Drawing.Size(16, 17);
            this.colorProgrammata.Text = "   ";
            // 
            // lblProgrammata
            // 
            this.lblProgrammata.Name = "lblProgrammata";
            this.lblProgrammata.Size = new System.Drawing.Size(80, 17);
            this.lblProgrammata.Text = "Programmata";
            // 
            // colorSvolta
            // 
            this.colorSvolta.BackColor = System.Drawing.Color.LightBlue;
            this.colorSvolta.Name = "colorSvolta";
            this.colorSvolta.Size = new System.Drawing.Size(16, 17);
            this.colorSvolta.Text = "   ";
            // 
            // lblSvolta
            // 
            this.lblSvolta.Name = "lblSvolta";
            this.lblSvolta.Size = new System.Drawing.Size(39, 17);
            this.lblSvolta.Text = "Svolta";
            // 
            // colorAssente
            // 
            this.colorAssente.BackColor = System.Drawing.Color.LightPink;
            this.colorAssente.Name = "colorAssente";
            this.colorAssente.Size = new System.Drawing.Size(16, 17);
            this.colorAssente.Text = "   ";
            // 
            // lblAssente
            // 
            this.lblAssente.Name = "lblAssente";
            this.lblAssente.Size = new System.Drawing.Size(48, 17);
            this.lblAssente.Text = "Assente";
            // 
            // colorRimandata
            // 
            this.colorRimandata.BackColor = System.Drawing.Color.Orange;
            this.colorRimandata.Name = "colorRimandata";
            this.colorRimandata.Size = new System.Drawing.Size(16, 17);
            this.colorRimandata.Text = "   ";
            // 
            // lblRimandata
            // 
            this.lblRimandata.Name = "lblRimandata";
            this.lblRimandata.Size = new System.Drawing.Size(64, 17);
            this.lblRimandata.Text = "Rimandata";
            // 
            // colorRiprogrammata
            // 
            this.colorRiprogrammata.BackColor = System.Drawing.Color.LightYellow;
            this.colorRiprogrammata.Name = "colorRiprogrammata";
            this.colorRiprogrammata.Size = new System.Drawing.Size(16, 17);
            this.colorRiprogrammata.Text = "   ";
            // 
            // lblRiprogrammata
            // 
            this.lblRiprogrammata.Name = "lblRiprogrammata";
            this.lblRiprogrammata.Size = new System.Drawing.Size(90, 17);
            this.lblRiprogrammata.Text = "Riprogrammata";
            // 
            // lblInstructions
            // 
            this.lblInstructions.Name = "lblInstructions";
            this.lblInstructions.Size = new System.Drawing.Size(984, 17);
            this.lblInstructions.Spring = true;
            this.lblInstructions.Text = "| Click: Dettagli | Tasto destro: Opzioni | Doppio click: Storico Cliente";
            this.lblInstructions.TextAlign = System.Drawing.ContentAlignment.MiddleRight;
            // 
            // splitContainerRight
            // 
            this.splitContainerRight.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom)
            | System.Windows.Forms.AnchorStyles.Right)));
            this.splitContainerRight.BorderStyle = System.Windows.Forms.BorderStyle.FixedSingle;
            this.splitContainerRight.Location = new System.Drawing.Point(1130, 0);
            this.splitContainerRight.Name = "splitContainerRight";
            this.splitContainerRight.Orientation = System.Windows.Forms.Orientation.Horizontal;
            // 
            // splitContainerRight.Panel1
            // 
            this.splitContainerRight.Panel1.Controls.Add(this.lstTodayAppointments);
            this.splitContainerRight.Panel1.Controls.Add(this.lblTodayTitle);
            // 
            // splitContainerRight.Panel2
            // 
            this.splitContainerRight.Panel2.Controls.Add(this.panelAppointmentDetail);
            this.splitContainerRight.Panel2.Controls.Add(this.lblAppointmentDetailTitle);
            this.splitContainerRight.Size = new System.Drawing.Size(334, 814);
            this.splitContainerRight.SplitterDistance = 407;
            this.splitContainerRight.TabIndex = 1;
            // 
            // lstTodayAppointments
            // 
            this.lstTodayAppointments.BorderStyle = System.Windows.Forms.BorderStyle.None;
            this.lstTodayAppointments.Dock = System.Windows.Forms.DockStyle.Fill;
            this.lstTodayAppointments.Font = new System.Drawing.Font("Segoe UI", 8.25F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lstTodayAppointments.FullRowSelect = true;
            this.lstTodayAppointments.GridLines = true;
            this.lstTodayAppointments.HeaderStyle = System.Windows.Forms.ColumnHeaderStyle.None;
            this.lstTodayAppointments.HideSelection = false;
            this.lstTodayAppointments.Location = new System.Drawing.Point(0, 30);
            this.lstTodayAppointments.Name = "lstTodayAppointments";
            this.lstTodayAppointments.OwnerDraw = true;
            this.lstTodayAppointments.Size = new System.Drawing.Size(332, 375);
            this.lstTodayAppointments.TabIndex = 3;
            this.lstTodayAppointments.UseCompatibleStateImageBehavior = false;
            this.lstTodayAppointments.View = System.Windows.Forms.View.Details;
            // 
            // lblTodayTitle
            // 
            this.lblTodayTitle.BackColor = System.Drawing.Color.DarkBlue;
            this.lblTodayTitle.Dock = System.Windows.Forms.DockStyle.Top;
            this.lblTodayTitle.Font = new System.Drawing.Font("Segoe UI", 9.75F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblTodayTitle.ForeColor = System.Drawing.Color.White;
            this.lblTodayTitle.Location = new System.Drawing.Point(0, 0);
            this.lblTodayTitle.Name = "lblTodayTitle";
            this.lblTodayTitle.Size = new System.Drawing.Size(332, 30);
            this.lblTodayTitle.TabIndex = 2;
            this.lblTodayTitle.Text = "Appuntamenti di Oggi - 27/06/2025";
            this.lblTodayTitle.TextAlign = System.Drawing.ContentAlignment.MiddleCenter;
            // 
            // panelAppointmentDetail
            // 
            this.panelAppointmentDetail.BackColor = System.Drawing.Color.WhiteSmoke;
            this.panelAppointmentDetail.Controls.Add(this.lblAppointmentDetailWelcome);
            this.panelAppointmentDetail.Controls.Add(this.lblAppointmentDetails);
            this.panelAppointmentDetail.Dock = System.Windows.Forms.DockStyle.Fill;
            this.panelAppointmentDetail.Location = new System.Drawing.Point(0, 30);
            this.panelAppointmentDetail.Name = "panelAppointmentDetail";
            this.panelAppointmentDetail.Size = new System.Drawing.Size(332, 371);
            this.panelAppointmentDetail.TabIndex = 4;
            // 
            // lblAppointmentDetailWelcome
            // 
            this.lblAppointmentDetailWelcome.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom)
            | System.Windows.Forms.AnchorStyles.Left)
            | System.Windows.Forms.AnchorStyles.Right)));
            this.lblAppointmentDetailWelcome.Font = new System.Drawing.Font("Segoe UI", 9.75F, System.Drawing.FontStyle.Italic, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblAppointmentDetailWelcome.ForeColor = System.Drawing.SystemColors.ControlDarkDark;
            this.lblAppointmentDetailWelcome.Location = new System.Drawing.Point(12, 110);
            this.lblAppointmentDetailWelcome.Name = "lblAppointmentDetailWelcome";
            this.lblAppointmentDetailWelcome.Size = new System.Drawing.Size(306, 68);
            this.lblAppointmentDetailWelcome.TabIndex = 1;
            this.lblAppointmentDetailWelcome.Text = "Clicca su una lezione nel planning per visualizzarne i dettagli qui.";
            this.lblAppointmentDetailWelcome.TextAlign = System.Drawing.ContentAlignment.MiddleCenter;
            // 
            // lblAppointmentDetails
            // 
            this.lblAppointmentDetails.AutoSize = true;
            this.lblAppointmentDetails.Font = new System.Drawing.Font("Segoe UI Semibold", 9.75F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblAppointmentDetails.Location = new System.Drawing.Point(12, 16);
            this.lblAppointmentDetails.Name = "lblAppointmentDetails";
            this.lblAppointmentDetails.Size = new System.Drawing.Size(125, 17);
            this.lblAppointmentDetails.TabIndex = 0;
            this.lblAppointmentDetails.Text = "Dettagli lezione...";
            // 
            // lblAppointmentDetailTitle
            // 
            this.lblAppointmentDetailTitle.BackColor = System.Drawing.Color.DarkSlateGray;
            this.lblAppointmentDetailTitle.Dock = System.Windows.Forms.DockStyle.Top;
            this.lblAppointmentDetailTitle.Font = new System.Drawing.Font("Segoe UI", 9.75F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblAppointmentDetailTitle.ForeColor = System.Drawing.Color.White;
            this.lblAppointmentDetailTitle.Location = new System.Drawing.Point(0, 0);
            this.lblAppointmentDetailTitle.Name = "lblAppointmentDetailTitle";
            this.lblAppointmentDetailTitle.Size = new System.Drawing.Size(332, 30);
            this.lblAppointmentDetailTitle.TabIndex = 3;
            this.lblAppointmentDetailTitle.Text = "Dettaglio Lezione Selezionata";
            this.lblAppointmentDetailTitle.TextAlign = System.Drawing.ContentAlignment.MiddleCenter;
            // 
            // PlanningForm
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(7F, 15F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.ClientSize = new System.Drawing.Size(1464, 896);
            this.Controls.Add(this.statusStrip);
            this.Controls.Add(this.panelMain);
            this.Controls.Add(this.panelControls);
            this.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.Icon = ((System.Drawing.Icon)(resources.GetObject("$this.Icon")));
            this.MinimumSize = new System.Drawing.Size(1200, 600);
            this.Name = "PlanningForm";
            this.StartPosition = System.Windows.Forms.FormStartPosition.CenterScreen;
            this.Text = "Planning Settimanale - EasyBooking";
            this.panelControls.ResumeLayout(false);
            this.panelControls.PerformLayout();
            this.panelMain.ResumeLayout(false);
            this.statusStrip.ResumeLayout(false);
            this.statusStrip.PerformLayout();
            this.splitContainerRight.Panel1.ResumeLayout(false);
            this.splitContainerRight.Panel2.ResumeLayout(false);
            ((System.ComponentModel.ISupportInitialize)(this.splitContainerRight)).EndInit();
            this.splitContainerRight.ResumeLayout(false);
            this.panelAppointmentDetail.ResumeLayout(false);
            this.panelAppointmentDetail.PerformLayout();
            this.ResumeLayout(false);
            this.PerformLayout();

        }

        #endregion

        private System.Windows.Forms.Panel panelControls;
        private System.Windows.Forms.Panel panelMain;
        private System.Windows.Forms.Panel panelPlanning;
        private System.Windows.Forms.DateTimePicker dtpSettimana;
        private System.Windows.Forms.Button btnNextWeek;
        private System.Windows.Forms.Button btnPrevWeek;
        private System.Windows.Forms.Button btnToday;
        private System.Windows.Forms.Label lblWeek;
        private System.Windows.Forms.ComboBox cmbInsegnanti;
        private System.Windows.Forms.Label lblInsegnante;
        private System.Windows.Forms.Label lblCliente;
        private System.Windows.Forms.ComboBox cmbClienti;
        private System.Windows.Forms.StatusStrip statusStrip;
        private System.Windows.Forms.ToolStripStatusLabel lblLeggenda;
        private System.Windows.Forms.ToolStripStatusLabel colorProgrammata;
        private System.Windows.Forms.ToolStripStatusLabel lblProgrammata;
        private System.Windows.Forms.ToolStripStatusLabel colorSvolta;
        private System.Windows.Forms.ToolStripStatusLabel lblSvolta;
        private System.Windows.Forms.ToolStripStatusLabel colorAssente;
        private System.Windows.Forms.ToolStripStatusLabel lblAssente;
        private System.Windows.Forms.ToolStripStatusLabel colorRimandata;
        private System.Windows.Forms.ToolStripStatusLabel lblRimandata;
        private System.Windows.Forms.ToolStripStatusLabel colorRiprogrammata;
        private System.Windows.Forms.ToolStripStatusLabel lblRiprogrammata;
        private System.Windows.Forms.ToolStripStatusLabel lblInstructions;
        private System.Windows.Forms.SplitContainer splitContainerRight;
        private System.Windows.Forms.ListView lstTodayAppointments;
        private System.Windows.Forms.Label lblTodayTitle;
        private System.Windows.Forms.Label lblAppointmentDetailTitle;
        private System.Windows.Forms.Panel panelAppointmentDetail;
        private System.Windows.Forms.Label lblAppointmentDetails;
        private System.Windows.Forms.Label lblAppointmentDetailWelcome;
    }
}