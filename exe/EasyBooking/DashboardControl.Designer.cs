namespace EasyBooking
{
    partial class DashboardControl
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
            this.pnlTop = new System.Windows.Forms.Panel();
            this.btnRefresh = new System.Windows.Forms.Button();
            this.lblData = new System.Windows.Forms.Label();
            this.lblOra = new System.Windows.Forms.Label();
            this.lblBenvenuto = new System.Windows.Forms.Label();
            this.tableLayoutPanel1 = new System.Windows.Forms.TableLayoutPanel();
            this.pnlStatistiche = new System.Windows.Forms.Panel();
            this.label5 = new System.Windows.Forms.Label();
            this.pnlLezioniOggi = new System.Windows.Forms.Panel();
            this.flpLezioniOggi = new System.Windows.Forms.FlowLayoutPanel();
            this.label3 = new System.Windows.Forms.Label();
            this.pnlUltimiAcquisti = new System.Windows.Forms.Panel();
            this.flpUltimiAcquisti = new System.Windows.Forms.FlowLayoutPanel();
            this.label4 = new System.Windows.Forms.Label();
            this.pnlInfoGenerali = new System.Windows.Forms.Panel();
            this.splitContainer1 = new System.Windows.Forms.SplitContainer();
            this.tableLayoutPanel2 = new System.Windows.Forms.TableLayoutPanel();
            this.panel6 = new System.Windows.Forms.Panel();
            this.lblNuoviClientiMese = new System.Windows.Forms.Label();
            this.label12 = new System.Windows.Forms.Label();
            this.panel5 = new System.Windows.Forms.Panel();
            this.lblInsegnantiAttivi = new System.Windows.Forms.Label();
            this.label10 = new System.Windows.Forms.Label();
            this.panel4 = new System.Windows.Forms.Panel();
            this.lblIncassoMensile = new System.Windows.Forms.Label();
            this.label8 = new System.Windows.Forms.Label();
            this.panel3 = new System.Windows.Forms.Panel();
            this.lblLezioniOggi = new System.Windows.Forms.Label();
            this.label6 = new System.Windows.Forms.Label();
            this.panel2 = new System.Windows.Forms.Panel();
            this.lblLezioniSettimana = new System.Windows.Forms.Label();
            this.label2 = new System.Windows.Forms.Label();
            this.panel1 = new System.Windows.Forms.Panel();
            this.lblClientiAttivi = new System.Windows.Forms.Label();
            this.label1 = new System.Windows.Forms.Label();
            this.pnlClientiUltimeLezioni = new System.Windows.Forms.Panel();
            this.flpClientiUltimeLezioni = new System.Windows.Forms.FlowLayoutPanel();
            this.label13 = new System.Windows.Forms.Label();
            this.label9 = new System.Windows.Forms.Label();
            this.pnlTop.SuspendLayout();
            this.tableLayoutPanel1.SuspendLayout();
            this.pnlStatistiche.SuspendLayout();
            this.pnlLezioniOggi.SuspendLayout();
            this.pnlUltimiAcquisti.SuspendLayout();
            this.pnlInfoGenerali.SuspendLayout();
            ((System.ComponentModel.ISupportInitialize)(this.splitContainer1)).BeginInit();
            this.splitContainer1.Panel1.SuspendLayout();
            this.splitContainer1.Panel2.SuspendLayout();
            this.splitContainer1.SuspendLayout();
            this.tableLayoutPanel2.SuspendLayout();
            this.panel6.SuspendLayout();
            this.panel5.SuspendLayout();
            this.panel4.SuspendLayout();
            this.panel3.SuspendLayout();
            this.panel2.SuspendLayout();
            this.panel1.SuspendLayout();
            this.pnlClientiUltimeLezioni.SuspendLayout();
            this.SuspendLayout();
            // 
            // pnlTop
            // 
            this.pnlTop.Anchor = ((System.Windows.Forms.AnchorStyles)(((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.pnlTop.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(240)))), ((int)(((byte)(240)))), ((int)(((byte)(240)))));
            this.pnlTop.BorderStyle = System.Windows.Forms.BorderStyle.FixedSingle;
            this.pnlTop.Controls.Add(this.btnRefresh);
            this.pnlTop.Controls.Add(this.lblData);
            this.pnlTop.Controls.Add(this.lblOra);
            this.pnlTop.Controls.Add(this.lblBenvenuto);
            this.pnlTop.Location = new System.Drawing.Point(0, 0);
            this.pnlTop.Name = "pnlTop";
            this.pnlTop.Size = new System.Drawing.Size(913, 60);
            this.pnlTop.TabIndex = 0;
            // 
            // btnRefresh
            // 
            this.btnRefresh.Anchor = ((System.Windows.Forms.AnchorStyles)((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Right)));
            this.btnRefresh.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(122)))), ((int)(((byte)(204)))));
            this.btnRefresh.FlatStyle = System.Windows.Forms.FlatStyle.Flat;
            this.btnRefresh.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.btnRefresh.ForeColor = System.Drawing.Color.White;
            this.btnRefresh.Location = new System.Drawing.Point(800, 15);
            this.btnRefresh.Name = "btnRefresh";
            this.btnRefresh.Size = new System.Drawing.Size(100, 30);
            this.btnRefresh.TabIndex = 3;
            this.btnRefresh.Text = "Aggiorna Dati";
            this.btnRefresh.UseVisualStyleBackColor = false;
            this.btnRefresh.Click += new System.EventHandler(this.btnRefresh_Click);
            // 
            // lblData
            // 
            this.lblData.AutoSize = true;
            this.lblData.Font = new System.Drawing.Font("Segoe UI", 9.75F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblData.Location = new System.Drawing.Point(138, 32);
            this.lblData.Name = "lblData";
            this.lblData.Size = new System.Drawing.Size(165, 17);
            this.lblData.TabIndex = 2;
            this.lblData.Text = "Mercoledì, 26 giugno 2025";
            // 
            // lblOra
            // 
            this.lblOra.AutoSize = true;
            this.lblOra.Font = new System.Drawing.Font("Segoe UI", 15.75F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblOra.ForeColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(122)))), ((int)(((byte)(204)))));
            this.lblOra.Location = new System.Drawing.Point(136, 5);
            this.lblOra.Name = "lblOra";
            this.lblOra.Size = new System.Drawing.Size(67, 30);
            this.lblOra.TabIndex = 1;
            this.lblOra.Text = "12:47";
            // 
            // lblBenvenuto
            // 
            this.lblBenvenuto.AutoSize = true;
            this.lblBenvenuto.Font = new System.Drawing.Font("Segoe UI", 15.75F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblBenvenuto.Location = new System.Drawing.Point(10, 15);
            this.lblBenvenuto.Name = "lblBenvenuto";
            this.lblBenvenuto.Size = new System.Drawing.Size(119, 30);
            this.lblBenvenuto.TabIndex = 0;
            this.lblBenvenuto.Text = "Dashboard";
            // 
            // tableLayoutPanel1
            // 
            this.tableLayoutPanel1.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.tableLayoutPanel1.ColumnCount = 2;
            this.tableLayoutPanel1.ColumnStyles.Add(new System.Windows.Forms.ColumnStyle(System.Windows.Forms.SizeType.Percent, 57.94085F));
            this.tableLayoutPanel1.ColumnStyles.Add(new System.Windows.Forms.ColumnStyle(System.Windows.Forms.SizeType.Percent, 42.05915F));
            this.tableLayoutPanel1.Controls.Add(this.pnlStatistiche, 1, 1);
            this.tableLayoutPanel1.Controls.Add(this.pnlLezioniOggi, 0, 1);
            this.tableLayoutPanel1.Controls.Add(this.pnlUltimiAcquisti, 1, 0);
            this.tableLayoutPanel1.Controls.Add(this.pnlInfoGenerali, 0, 0);
            this.tableLayoutPanel1.Location = new System.Drawing.Point(0, 63);
            this.tableLayoutPanel1.Name = "tableLayoutPanel1";
            this.tableLayoutPanel1.RowCount = 2;
            this.tableLayoutPanel1.RowStyles.Add(new System.Windows.Forms.RowStyle(System.Windows.Forms.SizeType.Percent, 50F));
            this.tableLayoutPanel1.RowStyles.Add(new System.Windows.Forms.RowStyle(System.Windows.Forms.SizeType.Percent, 50F));
            this.tableLayoutPanel1.Size = new System.Drawing.Size(913, 537);
            this.tableLayoutPanel1.TabIndex = 1;
            // 
            // pnlStatistiche
            // 
            this.pnlStatistiche.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.pnlStatistiche.BorderStyle = System.Windows.Forms.BorderStyle.FixedSingle;
            this.pnlStatistiche.Controls.Add(this.label5);
            this.pnlStatistiche.Location = new System.Drawing.Point(532, 271);
            this.pnlStatistiche.Name = "pnlStatistiche";
            this.pnlStatistiche.Size = new System.Drawing.Size(378, 263);
            this.pnlStatistiche.TabIndex = 3;
            this.pnlStatistiche.Paint += new System.Windows.Forms.PaintEventHandler(this.pnlStatistiche_Paint);
            // 
            // label5
            // 
            this.label5.AutoSize = true;
            this.label5.Font = new System.Drawing.Font("Segoe UI", 11.25F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.label5.Location = new System.Drawing.Point(10, 10);
            this.label5.Name = "label5";
            this.label5.Size = new System.Drawing.Size(82, 20);
            this.label5.TabIndex = 1;
            this.label5.Text = "Statistiche";
            // 
            // pnlLezioniOggi
            // 
            this.pnlLezioniOggi.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.pnlLezioniOggi.BorderStyle = System.Windows.Forms.BorderStyle.FixedSingle;
            this.pnlLezioniOggi.Controls.Add(this.flpLezioniOggi);
            this.pnlLezioniOggi.Controls.Add(this.label3);
            this.pnlLezioniOggi.Location = new System.Drawing.Point(3, 271);
            this.pnlLezioniOggi.Name = "pnlLezioniOggi";
            this.pnlLezioniOggi.Size = new System.Drawing.Size(523, 263);
            this.pnlLezioniOggi.TabIndex = 2;
            // 
            // flpLezioniOggi
            // 
            this.flpLezioniOggi.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.flpLezioniOggi.AutoScroll = true;
            this.flpLezioniOggi.Location = new System.Drawing.Point(3, 40);
            this.flpLezioniOggi.Name = "flpLezioniOggi";
            this.flpLezioniOggi.Size = new System.Drawing.Size(515, 218);
            this.flpLezioniOggi.TabIndex = 2;
            // 
            // label3
            // 
            this.label3.AutoSize = true;
            this.label3.Font = new System.Drawing.Font("Segoe UI", 11.25F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.label3.Location = new System.Drawing.Point(10, 10);
            this.label3.Name = "label3";
            this.label3.Size = new System.Drawing.Size(95, 20);
            this.label3.TabIndex = 1;
            this.label3.Text = "Lezioni Oggi";
            // 
            // pnlUltimiAcquisti
            // 
            this.pnlUltimiAcquisti.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.pnlUltimiAcquisti.BorderStyle = System.Windows.Forms.BorderStyle.FixedSingle;
            this.pnlUltimiAcquisti.Controls.Add(this.flpUltimiAcquisti);
            this.pnlUltimiAcquisti.Controls.Add(this.label4);
            this.pnlUltimiAcquisti.Location = new System.Drawing.Point(532, 3);
            this.pnlUltimiAcquisti.Name = "pnlUltimiAcquisti";
            this.pnlUltimiAcquisti.Size = new System.Drawing.Size(378, 262);
            this.pnlUltimiAcquisti.TabIndex = 1;
            // 
            // flpUltimiAcquisti
            // 
            this.flpUltimiAcquisti.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.flpUltimiAcquisti.AutoScroll = true;
            this.flpUltimiAcquisti.Location = new System.Drawing.Point(3, 40);
            this.flpUltimiAcquisti.Name = "flpUltimiAcquisti";
            this.flpUltimiAcquisti.Size = new System.Drawing.Size(370, 217);
            this.flpUltimiAcquisti.TabIndex = 2;
            // 
            // label4
            // 
            this.label4.AutoSize = true;
            this.label4.Font = new System.Drawing.Font("Segoe UI", 11.25F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.label4.Location = new System.Drawing.Point(10, 10);
            this.label4.Name = "label4";
            this.label4.Size = new System.Drawing.Size(113, 20);
            this.label4.TabIndex = 1;
            this.label4.Text = "Ultimi Acquisti";
            // 
            // pnlInfoGenerali
            // 
            this.pnlInfoGenerali.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.pnlInfoGenerali.BorderStyle = System.Windows.Forms.BorderStyle.FixedSingle;
            this.pnlInfoGenerali.Controls.Add(this.splitContainer1);
            this.pnlInfoGenerali.Controls.Add(this.label9);
            this.pnlInfoGenerali.Location = new System.Drawing.Point(3, 3);
            this.pnlInfoGenerali.Name = "pnlInfoGenerali";
            this.pnlInfoGenerali.Size = new System.Drawing.Size(523, 262);
            this.pnlInfoGenerali.TabIndex = 0;
            // 
            // splitContainer1
            // 
            this.splitContainer1.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.splitContainer1.Location = new System.Drawing.Point(3, 40);
            this.splitContainer1.Name = "splitContainer1";
            // 
            // splitContainer1.Panel1
            // 
            this.splitContainer1.Panel1.Controls.Add(this.tableLayoutPanel2);
            // 
            // splitContainer1.Panel2
            // 
            this.splitContainer1.Panel2.Controls.Add(this.pnlClientiUltimeLezioni);
            this.splitContainer1.Size = new System.Drawing.Size(515, 217);
            this.splitContainer1.SplitterDistance = 188;
            this.splitContainer1.TabIndex = 4;
            // 
            // tableLayoutPanel2
            // 
            this.tableLayoutPanel2.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.tableLayoutPanel2.ColumnCount = 1;
            this.tableLayoutPanel2.ColumnStyles.Add(new System.Windows.Forms.ColumnStyle(System.Windows.Forms.SizeType.Percent, 100F));
            this.tableLayoutPanel2.Controls.Add(this.panel6, 0, 5);
            this.tableLayoutPanel2.Controls.Add(this.panel5, 0, 4);
            this.tableLayoutPanel2.Controls.Add(this.panel4, 0, 3);
            this.tableLayoutPanel2.Controls.Add(this.panel3, 0, 2);
            this.tableLayoutPanel2.Controls.Add(this.panel2, 0, 1);
            this.tableLayoutPanel2.Controls.Add(this.panel1, 0, 0);
            this.tableLayoutPanel2.Location = new System.Drawing.Point(0, 0);
            this.tableLayoutPanel2.Name = "tableLayoutPanel2";
            this.tableLayoutPanel2.RowCount = 6;
            this.tableLayoutPanel2.RowStyles.Add(new System.Windows.Forms.RowStyle(System.Windows.Forms.SizeType.Percent, 16.66667F));
            this.tableLayoutPanel2.RowStyles.Add(new System.Windows.Forms.RowStyle(System.Windows.Forms.SizeType.Percent, 16.66667F));
            this.tableLayoutPanel2.RowStyles.Add(new System.Windows.Forms.RowStyle(System.Windows.Forms.SizeType.Percent, 16.66667F));
            this.tableLayoutPanel2.RowStyles.Add(new System.Windows.Forms.RowStyle(System.Windows.Forms.SizeType.Percent, 16.66667F));
            this.tableLayoutPanel2.RowStyles.Add(new System.Windows.Forms.RowStyle(System.Windows.Forms.SizeType.Percent, 16.66667F));
            this.tableLayoutPanel2.RowStyles.Add(new System.Windows.Forms.RowStyle(System.Windows.Forms.SizeType.Percent, 16.66667F));
            this.tableLayoutPanel2.Size = new System.Drawing.Size(185, 217);
            this.tableLayoutPanel2.TabIndex = 4;
            // 
            // panel6
            // 
            this.panel6.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.panel6.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(240)))), ((int)(((byte)(240)))), ((int)(((byte)(240)))));
            this.panel6.Controls.Add(this.lblNuoviClientiMese);
            this.panel6.Controls.Add(this.label12);
            this.panel6.Location = new System.Drawing.Point(3, 183);
            this.panel6.Name = "panel6";
            this.panel6.Size = new System.Drawing.Size(179, 31);
            this.panel6.TabIndex = 5;
            // 
            // lblNuoviClientiMese
            // 
            this.lblNuoviClientiMese.AutoSize = true;
            this.lblNuoviClientiMese.Font = new System.Drawing.Font("Segoe UI", 11.25F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblNuoviClientiMese.ForeColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(122)))), ((int)(((byte)(204)))));
            this.lblNuoviClientiMese.Location = new System.Drawing.Point(150, 5);
            this.lblNuoviClientiMese.Name = "lblNuoviClientiMese";
            this.lblNuoviClientiMese.Size = new System.Drawing.Size(18, 20);
            this.lblNuoviClientiMese.TabIndex = 1;
            this.lblNuoviClientiMese.Text = "0";
            // 
            // label12
            // 
            this.label12.AutoSize = true;
            this.label12.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.label12.Location = new System.Drawing.Point(3, 5);
            this.label12.Name = "label12";
            this.label12.Size = new System.Drawing.Size(118, 15);
            this.label12.TabIndex = 0;
            this.label12.Text = "Nuovi Clienti (mese):";
            // 
            // panel5
            // 
            this.panel5.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.panel5.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(240)))), ((int)(((byte)(240)))), ((int)(((byte)(240)))));
            this.panel5.Controls.Add(this.lblInsegnantiAttivi);
            this.panel5.Controls.Add(this.label10);
            this.panel5.Location = new System.Drawing.Point(3, 147);
            this.panel5.Name = "panel5";
            this.panel5.Size = new System.Drawing.Size(179, 30);
            this.panel5.TabIndex = 4;
            // 
            // lblInsegnantiAttivi
            // 
            this.lblInsegnantiAttivi.AutoSize = true;
            this.lblInsegnantiAttivi.Font = new System.Drawing.Font("Segoe UI", 11.25F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblInsegnantiAttivi.ForeColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(122)))), ((int)(((byte)(204)))));
            this.lblInsegnantiAttivi.Location = new System.Drawing.Point(150, 5);
            this.lblInsegnantiAttivi.Name = "lblInsegnantiAttivi";
            this.lblInsegnantiAttivi.Size = new System.Drawing.Size(18, 20);
            this.lblInsegnantiAttivi.TabIndex = 1;
            this.lblInsegnantiAttivi.Text = "0";
            // 
            // label10
            // 
            this.label10.AutoSize = true;
            this.label10.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.label10.Location = new System.Drawing.Point(3, 5);
            this.label10.Name = "label10";
            this.label10.Size = new System.Drawing.Size(94, 15);
            this.label10.TabIndex = 0;
            this.label10.Text = "Insegnanti attivi:";
            // 
            // panel4
            // 
            this.panel4.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.panel4.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(240)))), ((int)(((byte)(240)))), ((int)(((byte)(240)))));
            this.panel4.Controls.Add(this.lblIncassoMensile);
            this.panel4.Controls.Add(this.label8);
            this.panel4.Location = new System.Drawing.Point(3, 111);
            this.panel4.Name = "panel4";
            this.panel4.Size = new System.Drawing.Size(179, 30);
            this.panel4.TabIndex = 3;
            // 
            // lblIncassoMensile
            // 
            this.lblIncassoMensile.AutoSize = true;
            this.lblIncassoMensile.Font = new System.Drawing.Font("Segoe UI", 11.25F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblIncassoMensile.ForeColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(122)))), ((int)(((byte)(204)))));
            this.lblIncassoMensile.Location = new System.Drawing.Point(123, 5);
            this.lblIncassoMensile.Name = "lblIncassoMensile";
            this.lblIncassoMensile.Size = new System.Drawing.Size(53, 20);
            this.lblIncassoMensile.TabIndex = 1;
            this.lblIncassoMensile.Text = "€ 0,00";
            this.lblIncassoMensile.TextAlign = System.Drawing.ContentAlignment.TopRight;
            // 
            // label8
            // 
            this.label8.AutoSize = true;
            this.label8.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.label8.Location = new System.Drawing.Point(3, 5);
            this.label8.Name = "label8";
            this.label8.Size = new System.Drawing.Size(93, 15);
            this.label8.TabIndex = 0;
            this.label8.Text = "Incasso mensile:";
            // 
            // panel3
            // 
            this.panel3.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.panel3.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(240)))), ((int)(((byte)(240)))), ((int)(((byte)(240)))));
            this.panel3.Controls.Add(this.lblLezioniOggi);
            this.panel3.Controls.Add(this.label6);
            this.panel3.Location = new System.Drawing.Point(3, 75);
            this.panel3.Name = "panel3";
            this.panel3.Size = new System.Drawing.Size(179, 30);
            this.panel3.TabIndex = 2;
            // 
            // lblLezioniOggi
            // 
            this.lblLezioniOggi.AutoSize = true;
            this.lblLezioniOggi.Font = new System.Drawing.Font("Segoe UI", 11.25F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblLezioniOggi.ForeColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(122)))), ((int)(((byte)(204)))));
            this.lblLezioniOggi.Location = new System.Drawing.Point(150, 5);
            this.lblLezioniOggi.Name = "lblLezioniOggi";
            this.lblLezioniOggi.Size = new System.Drawing.Size(18, 20);
            this.lblLezioniOggi.TabIndex = 1;
            this.lblLezioniOggi.Text = "0";
            // 
            // label6
            // 
            this.label6.AutoSize = true;
            this.label6.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.label6.Location = new System.Drawing.Point(3, 5);
            this.label6.Name = "label6";
            this.label6.Size = new System.Drawing.Size(74, 15);
            this.label6.TabIndex = 0;
            this.label6.Text = "Lezioni oggi:";
            // 
            // panel2
            // 
            this.panel2.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.panel2.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(240)))), ((int)(((byte)(240)))), ((int)(((byte)(240)))));
            this.panel2.Controls.Add(this.lblLezioniSettimana);
            this.panel2.Controls.Add(this.label2);
            this.panel2.Location = new System.Drawing.Point(3, 39);
            this.panel2.Name = "panel2";
            this.panel2.Size = new System.Drawing.Size(179, 30);
            this.panel2.TabIndex = 1;
            // 
            // lblLezioniSettimana
            // 
            this.lblLezioniSettimana.AutoSize = true;
            this.lblLezioniSettimana.Font = new System.Drawing.Font("Segoe UI", 11.25F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblLezioniSettimana.ForeColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(122)))), ((int)(((byte)(204)))));
            this.lblLezioniSettimana.Location = new System.Drawing.Point(150, 5);
            this.lblLezioniSettimana.Name = "lblLezioniSettimana";
            this.lblLezioniSettimana.Size = new System.Drawing.Size(18, 20);
            this.lblLezioniSettimana.TabIndex = 1;
            this.lblLezioniSettimana.Text = "0";
            // 
            // label2
            // 
            this.label2.AutoSize = true;
            this.label2.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.label2.Location = new System.Drawing.Point(3, 5);
            this.label2.Name = "label2";
            this.label2.Size = new System.Drawing.Size(102, 15);
            this.label2.TabIndex = 0;
            this.label2.Text = "Lezioni settimana:";
            // 
            // panel1
            // 
            this.panel1.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.panel1.BackColor = System.Drawing.Color.FromArgb(((int)(((byte)(240)))), ((int)(((byte)(240)))), ((int)(((byte)(240)))));
            this.panel1.Controls.Add(this.lblClientiAttivi);
            this.panel1.Controls.Add(this.label1);
            this.panel1.Location = new System.Drawing.Point(3, 3);
            this.panel1.Name = "panel1";
            this.panel1.Size = new System.Drawing.Size(179, 30);
            this.panel1.TabIndex = 0;
            // 
            // lblClientiAttivi
            // 
            this.lblClientiAttivi.AutoSize = true;
            this.lblClientiAttivi.Font = new System.Drawing.Font("Segoe UI", 11.25F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.lblClientiAttivi.ForeColor = System.Drawing.Color.FromArgb(((int)(((byte)(0)))), ((int)(((byte)(122)))), ((int)(((byte)(204)))));
            this.lblClientiAttivi.Location = new System.Drawing.Point(150, 5);
            this.lblClientiAttivi.Name = "lblClientiAttivi";
            this.lblClientiAttivi.Size = new System.Drawing.Size(18, 20);
            this.lblClientiAttivi.TabIndex = 1;
            this.lblClientiAttivi.Text = "0";
            // 
            // label1
            // 
            this.label1.AutoSize = true;
            this.label1.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.label1.Location = new System.Drawing.Point(3, 5);
            this.label1.Name = "label1";
            this.label1.Size = new System.Drawing.Size(73, 15);
            this.label1.TabIndex = 0;
            this.label1.Text = "Clienti attivi:";
            // 
            // pnlClientiUltimeLezioni
            // 
            this.pnlClientiUltimeLezioni.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.pnlClientiUltimeLezioni.BackColor = System.Drawing.Color.White;
            this.pnlClientiUltimeLezioni.Controls.Add(this.flpClientiUltimeLezioni);
            this.pnlClientiUltimeLezioni.Controls.Add(this.label13);
            this.pnlClientiUltimeLezioni.Location = new System.Drawing.Point(3, 0);
            this.pnlClientiUltimeLezioni.Name = "pnlClientiUltimeLezioni";
            this.pnlClientiUltimeLezioni.Size = new System.Drawing.Size(445, 217);
            this.pnlClientiUltimeLezioni.TabIndex = 0;
            // 
            // flpClientiUltimeLezioni
            // 
            this.flpClientiUltimeLezioni.Anchor = ((System.Windows.Forms.AnchorStyles)((((System.Windows.Forms.AnchorStyles.Top | System.Windows.Forms.AnchorStyles.Bottom) 
            | System.Windows.Forms.AnchorStyles.Left) 
            | System.Windows.Forms.AnchorStyles.Right)));
            this.flpClientiUltimeLezioni.AutoScroll = true;
            this.flpClientiUltimeLezioni.Location = new System.Drawing.Point(3, 30);
            this.flpClientiUltimeLezioni.Name = "flpClientiUltimeLezioni";
            this.flpClientiUltimeLezioni.Size = new System.Drawing.Size(439, 184);
            this.flpClientiUltimeLezioni.TabIndex = 1;
            // 
            // label13
            // 
            this.label13.AutoSize = true;
            this.label13.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.label13.Location = new System.Drawing.Point(3, 10);
            this.label13.Name = "label13";
            this.label13.Size = new System.Drawing.Size(226, 15);
            this.label13.TabIndex = 0;
            this.label13.Text = "Pacchettii in scadenza questa settimana";
            // 
            // label9
            // 
            this.label9.AutoSize = true;
            this.label9.Font = new System.Drawing.Font("Segoe UI", 11.25F, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.label9.Location = new System.Drawing.Point(10, 10);
            this.label9.Name = "label9";
            this.label9.Size = new System.Drawing.Size(167, 20);
            this.label9.TabIndex = 2;
            this.label9.Text = "Informazioni Principali";
            // 
            // DashboardControl
            // 
            this.AutoScaleDimensions = new System.Drawing.SizeF(7F, 15F);
            this.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font;
            this.Controls.Add(this.tableLayoutPanel1);
            this.Controls.Add(this.pnlTop);
            this.Font = new System.Drawing.Font("Segoe UI", 9F, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point, ((byte)(0)));
            this.Name = "DashboardControl";
            this.Size = new System.Drawing.Size(913, 600);
            this.Resize += new System.EventHandler(this.DashboardControl_Resize);
            this.pnlTop.ResumeLayout(false);
            this.pnlTop.PerformLayout();
            this.tableLayoutPanel1.ResumeLayout(false);
            this.pnlStatistiche.ResumeLayout(false);
            this.pnlStatistiche.PerformLayout();
            this.pnlLezioniOggi.ResumeLayout(false);
            this.pnlLezioniOggi.PerformLayout();
            this.pnlUltimiAcquisti.ResumeLayout(false);
            this.pnlUltimiAcquisti.PerformLayout();
            this.pnlInfoGenerali.ResumeLayout(false);
            this.pnlInfoGenerali.PerformLayout();
            this.splitContainer1.Panel1.ResumeLayout(false);
            this.splitContainer1.Panel2.ResumeLayout(false);
            ((System.ComponentModel.ISupportInitialize)(this.splitContainer1)).EndInit();
            this.splitContainer1.ResumeLayout(false);
            this.tableLayoutPanel2.ResumeLayout(false);
            this.panel6.ResumeLayout(false);
            this.panel6.PerformLayout();
            this.panel5.ResumeLayout(false);
            this.panel5.PerformLayout();
            this.panel4.ResumeLayout(false);
            this.panel4.PerformLayout();
            this.panel3.ResumeLayout(false);
            this.panel3.PerformLayout();
            this.panel2.ResumeLayout(false);
            this.panel2.PerformLayout();
            this.panel1.ResumeLayout(false);
            this.panel1.PerformLayout();
            this.pnlClientiUltimeLezioni.ResumeLayout(false);
            this.pnlClientiUltimeLezioni.PerformLayout();
            this.ResumeLayout(false);

        }

        #endregion

        private System.Windows.Forms.Panel pnlTop;
        private System.Windows.Forms.Button btnRefresh;
        private System.Windows.Forms.Label lblData;
        private System.Windows.Forms.Label lblOra;
        private System.Windows.Forms.Label lblBenvenuto;
        private System.Windows.Forms.TableLayoutPanel tableLayoutPanel1;
        private System.Windows.Forms.Panel pnlStatistiche;
        private System.Windows.Forms.Panel pnlLezioniOggi;
        private System.Windows.Forms.Panel pnlUltimiAcquisti;
        private System.Windows.Forms.Panel pnlInfoGenerali;
        private System.Windows.Forms.Label label9;
        private System.Windows.Forms.Label label3;
        private System.Windows.Forms.FlowLayoutPanel flpLezioniOggi;
        private System.Windows.Forms.Label label4;
        private System.Windows.Forms.FlowLayoutPanel flpUltimiAcquisti;
        private System.Windows.Forms.Label label5;
        private System.Windows.Forms.SplitContainer splitContainer1;
        private System.Windows.Forms.Panel pnlClientiUltimeLezioni;
        private System.Windows.Forms.FlowLayoutPanel flpClientiUltimeLezioni;
        private System.Windows.Forms.Label label13;
        private System.Windows.Forms.TableLayoutPanel tableLayoutPanel2;
        private System.Windows.Forms.Panel panel6;
        private System.Windows.Forms.Label lblNuoviClientiMese;
        private System.Windows.Forms.Label label12;
        private System.Windows.Forms.Panel panel5;
        private System.Windows.Forms.Label lblInsegnantiAttivi;
        private System.Windows.Forms.Label label10;
        private System.Windows.Forms.Panel panel4;
        private System.Windows.Forms.Label lblIncassoMensile;
        private System.Windows.Forms.Label label8;
        private System.Windows.Forms.Panel panel3;
        private System.Windows.Forms.Label lblLezioniOggi;
        private System.Windows.Forms.Label label6;
        private System.Windows.Forms.Panel panel2;
        private System.Windows.Forms.Label lblLezioniSettimana;
        private System.Windows.Forms.Label label2;
        private System.Windows.Forms.Panel panel1;
        private System.Windows.Forms.Label lblClientiAttivi;
        private System.Windows.Forms.Label label1;
    }
}