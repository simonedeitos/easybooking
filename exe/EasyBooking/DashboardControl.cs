using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Diagnostics;
using System.Drawing;
using System.Globalization;
using System.IO;
using System.Linq;
using System.Text;
using System.Windows.Forms;
using System.Xml.Serialization;
using EasyBooking.Models;

namespace EasyBooking
{
    public partial class DashboardControl : UserControl
    {
        private string dataPath;
        private ClientiList clienti;
        private InsegnantiList insegnanti;
        private PrenotazioniList prenotazioni;
        private Models.AcquistiList acquisti;

        private string clientiFilePath;
        private string insegnantiFilePath;
        private string prenotazioniFilePath;
        private string acquistiFilePath;
        private string scadenzaMessageFilePath;

        private Timer refreshTimer;
        private Timer clockTimer;

        public DashboardControl(string dataPath)
        {
            InitializeComponent();

            this.dataPath = dataPath;
            clientiFilePath = Path.Combine(dataPath, "clienti.xml");
            insegnantiFilePath = Path.Combine(dataPath, "insegnanti.xml");
            prenotazioniFilePath = Path.Combine(dataPath, "prenotazioni.xml");
            acquistiFilePath = Path.Combine(dataPath, "acquisti.xml");

            // Percorso del file scadenza.txt nella cartella dell'eseguibile
            scadenzaMessageFilePath = Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "scadenza.txt");

            LoadData();
            UpdateDashboard();

            refreshTimer = new Timer();
            refreshTimer.Interval = 300000;
            refreshTimer.Tick += RefreshTimer_Tick;
            refreshTimer.Start();

            clockTimer = new Timer();
            clockTimer.Interval = 1000;
            clockTimer.Tick += ClockTimer_Tick;
            clockTimer.Start();
        }

        private void RefreshTimer_Tick(object sender, EventArgs e)
        {
            LoadData();
            UpdateDashboard();
        }

        private void ClockTimer_Tick(object sender, EventArgs e)
        {
            lblOra.Text = DateTime.Now.ToString("HH:mm:ss");
            lblData.Text = DateTime.Now.ToString("dddd, dd MMMM yyyy");
        }

        public void ManualRefresh()
        {
            LoadData();
            UpdateDashboard();
        }

        private void LoadData()
        {
            clienti = MainForm.LoadEncryptedXml<ClientiList>(clientiFilePath);
            if (clienti == null || clienti.Items == null) clienti = new ClientiList();

            insegnanti = MainForm.LoadEncryptedXml<InsegnantiList>(insegnantiFilePath);
            if (insegnanti == null || insegnanti.Items == null) insegnanti = new InsegnantiList();

            prenotazioni = MainForm.LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath);
            if (prenotazioni == null || prenotazioni.Items == null) prenotazioni = new PrenotazioniList();

            acquisti = MainForm.LoadEncryptedXml<Models.AcquistiList>(acquistiFilePath);
            if (acquisti == null || acquisti.Items == null) acquisti = new Models.AcquistiList();
        }

        private void UpdateDashboard()
        {
            lblOra.Text = DateTime.Now.ToString("HH:mm:ss");
            lblData.Text = DateTime.Now.ToString("dddd, dd MMMM yyyy");

            UpdateGeneralStats();

            UpdateTodaysLessons();

            UpdateRecentPurchases();

            UpdateStatisticsChart();

            UpdateClientiUltimeLezioni();
        }

        private void UpdateGeneralStats()
        {
            try
            {
                DateTime today = DateTime.Today;

                var clientiConPrenotazioniFuture = prenotazioni.Items?
                    .Where(p => p.Data > today && p.Stato == StatoLezione.Programmata)
                    .Select(p => p.ClienteId)
                    .Distinct() ?? Enumerable.Empty<int>();

                int clientiAttivi = clientiConPrenotazioniFuture.Count();
                lblClientiAttivi.Text = clientiAttivi.ToString();

                DateTime startOfWeek = DateTime.Today;
                while (startOfWeek.DayOfWeek != DayOfWeek.Monday)
                {
                    startOfWeek = startOfWeek.AddDays(-1);
                }

                DateTime endOfWeek = startOfWeek.AddDays(6);

                int lezioniSettimana = prenotazioni.Items?.Count(p =>
                    p.Data >= startOfWeek &&
                    p.Data <= endOfWeek &&
                    p.Stato == StatoLezione.Programmata) ?? 0;

                lblLezioniSettimana.Text = lezioniSettimana.ToString();

                int lezioniOggi = prenotazioni.Items?.Count(p => p.Data.Date == DateTime.Today.Date) ?? 0;
                lblLezioniOggi.Text = lezioniOggi.ToString();

                DateTime primoDelMese = new DateTime(DateTime.Today.Year, DateTime.Today.Month, 1);
                DateTime ultimoDelMese = primoDelMese.AddMonths(1).AddDays(-1);

                decimal incassoMensile = acquisti.Items
                    ?.Where(a => a.DataAcquisto >= primoDelMese && a.DataAcquisto <= ultimoDelMese)
                    .Sum(a => a.ImportoPagato) ?? 0;

                lblIncassoMensile.Text = incassoMensile.ToString("C");

                int insegnantiAttivi = insegnanti.Items?.Count() ?? 0;
                lblInsegnantiAttivi.Text = insegnantiAttivi.ToString();

                // *** CORREZIONE ***
                // La riga che causava l'errore su 'DataIscrizione' è stata rimossa.
                // Il valore viene lasciato a 0 come nel codice originale.
                int nuoviClienti = 0;
                lblNuoviClientiMese.Text = nuoviClienti.ToString();
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore nell'aggiornamento delle statistiche: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void UpdateClientiUltimeLezioni()
        {
            try
            {
                flpClientiUltimeLezioni.Controls.Clear();

                flpClientiUltimeLezioni.AutoScroll = true;
                flpClientiUltimeLezioni.FlowDirection = FlowDirection.TopDown;
                flpClientiUltimeLezioni.WrapContents = false;

                Panel headerPanel = new Panel
                {
                    Size = new Size(flpClientiUltimeLezioni.Width - 40, 30),
                    BackColor = Color.FromArgb(230, 230, 230),
                    Margin = new Padding(3, 3, 3, 5)
                };

                Label lblHeaderNome = new Label { Text = "Nome Cliente", Font = new Font("Segoe UI", 9, FontStyle.Bold), Location = new Point(10, 7), AutoSize = true };
                Label lblHeaderPacchetto = new Label { Text = "Pacchetto", Font = new Font("Segoe UI", 9, FontStyle.Bold), Location = new Point(200, 7), AutoSize = true };
                Label lblHeaderDataLezione = new Label { Text = "Data", Font = new Font("Segoe UI", 9, FontStyle.Bold), Location = new Point(280, 7), AutoSize = true };
                Label lblHeaderAzioni = new Label { Text = "Azioni", Font = new Font("Segoe UI", 9, FontStyle.Bold), Location = new Point(350, 7), AutoSize = true };

                headerPanel.Controls.Add(lblHeaderNome);
                headerPanel.Controls.Add(lblHeaderPacchetto);
                headerPanel.Controls.Add(lblHeaderDataLezione);
                headerPanel.Controls.Add(lblHeaderAzioni);
                flpClientiUltimeLezioni.Controls.Add(headerPanel);

                DateTime startOfWeek = DateTime.Today;
                while (startOfWeek.DayOfWeek != DayOfWeek.Monday) { startOfWeek = startOfWeek.AddDays(-1); }
                DateTime endOfWeek = startOfWeek.AddDays(6);

                var clientiConUltimeLezioni = new List<(int ClienteId, string NomeCliente, string NomeClienteSms, int TotalePacchetto, int LezioniUsate, DateTime DataUltimaLezione, string Telefono)>();

                if (acquisti.Items != null && prenotazioni.Items != null)
                {
                    var lezioniPerAcquisto = prenotazioni.Items
                        .Where(p => p.AcquistoId > 0)
                        .GroupBy(p => p.AcquistoId)
                        .ToDictionary(g => g.Key, g => g.OrderBy(l => l.Data).ThenBy(l => ParseTimeStringRobust(l.OraInizioStr)).ToList());

                    foreach (var acquisto in acquisti.Items)
                    {
                        if (acquisto.NumeroLezioni == 0) continue;

                        if (lezioniPerAcquisto.TryGetValue(acquisto.Id, out var lezioniAcquisto))
                        {
                            var ultimaLezione = lezioniAcquisto.OrderByDescending(p => p.Data).ThenByDescending(p => ParseTimeStringRobust(p.OraInizioStr)).FirstOrDefault();

                            if (ultimaLezione != null && ultimaLezione.Data >= startOfWeek && ultimaLezione.Data <= endOfWeek && lezioniAcquisto.Count == acquisto.NumeroLezioni)
                            {
                                var cliente = clienti.Items?.FirstOrDefault(c => c.Id == acquisto.ClienteId);
                                string nomeCliente = cliente != null ? $"{cliente.Nome} {cliente.Cognome}" : "Cliente non trovato";
                                string nomeClienteSms = cliente != null ? cliente.Nome : "";
                                string telefono = cliente?.Telefono ?? "";

                                clientiConUltimeLezioni.Add((acquisto.ClienteId, nomeCliente, nomeClienteSms, acquisto.NumeroLezioni, lezioniAcquisto.Count, ultimaLezione.Data, telefono));
                            }
                        }
                    }
                }

                if (clientiConUltimeLezioni.Count == 0)
                {
                    Label lblNoLezioni = new Label { Text = "Nessun cliente con ultima lezione questa settimana.", AutoSize = true, Margin = new Padding(10), Font = new Font("Segoe UI", 9, FontStyle.Italic) };
                    flpClientiUltimeLezioni.Controls.Add(lblNoLezioni);
                    return;
                }

                clientiConUltimeLezioni = clientiConUltimeLezioni.OrderBy(c => c.DataUltimaLezione).ToList();

                foreach (var clienteInfo in clientiConUltimeLezioni)
                {
                    Panel clientePanel = new Panel { Size = new Size(flpClientiUltimeLezioni.Width - 40, 35), BorderStyle = BorderStyle.None, Margin = new Padding(3, 2, 3, 2), BackColor = Color.FromArgb(255, 240, 200) };
                    Label lblNome = new Label { Text = clienteInfo.NomeCliente, Font = new Font("Segoe UI", 9), Location = new Point(10, 10), AutoSize = true };
                    Label lblPacchetto = new Label { Text = $"{clienteInfo.LezioniUsate}/{clienteInfo.TotalePacchetto}", Font = new Font("Segoe UI", 9), Location = new Point(200, 10), AutoSize = true };
                    Label lblDataLezione = new Label { Text = clienteInfo.DataUltimaLezione.ToString("dd/MM/yyyy"), Font = new Font("Segoe UI", 9), Location = new Point(280, 10), AutoSize = true };

                    Button btnSMS = new Button { Text = "Notifica", Font = new Font("Segoe UI", 8), Location = new Point(365, 5), Size = new Size(70, 25), BackColor = Color.FromArgb(37, 211, 102), ForeColor = Color.White, FlatStyle = FlatStyle.Flat, Cursor = Cursors.Hand, Tag = new Tuple<string, string>(clienteInfo.NomeClienteSms, clienteInfo.Telefono) };
                    btnSMS.FlatAppearance.BorderSize = 0;
                    btnSMS.Click += BtnSMS_Click;

                    clientePanel.Controls.Add(lblNome);
                    clientePanel.Controls.Add(lblPacchetto);
                    clientePanel.Controls.Add(lblDataLezione);
                    clientePanel.Controls.Add(btnSMS);
                    flpClientiUltimeLezioni.Controls.Add(clientePanel);
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore nell'aggiornamento dei clienti con ultime lezioni: {ex.Message}", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void BtnSMS_Click(object sender, EventArgs e)
        {
            try
            {
                Button btn = sender as Button;
                if (btn?.Tag == null) return;
                if (!(btn.Tag is Tuple<string, string> tagInfo)) return;

                string nomeClienteSms = tagInfo.Item1;
                string telefono = tagInfo.Item2;

                if (string.IsNullOrWhiteSpace(telefono))
                {
                    MessageBox.Show("Numero di telefono non disponibile per questo cliente.", "Attenzione", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    return;
                }

                telefono = new string(telefono.Where(c => char.IsDigit(c) || c == '+').ToArray());

                if (string.IsNullOrEmpty(telefono))
                {
                    MessageBox.Show("Numero di telefono non valido.", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
                    return;
                }

                string messaggioTemplate = LeggiMessaggioScadenza();
                string messaggio = messaggioTemplate.Replace("[nome]", nomeClienteSms);
                ApriWhatsApp(telefono, messaggio);
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore nell'apertura di WhatsApp: {ex.Message}", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private string LeggiMessaggioScadenza()
        {
            try
            {
                if (!File.Exists(scadenzaMessageFilePath))
                {
                    string messaggioDefault = "Ciao [nome],\n\nTi ricordiamo che hai terminato le lezioni del tuo pacchetto.\n\nPer continuare, puoi acquistare un nuovo pacchetto.\n\nGrazie! ";
                    File.WriteAllText(scadenzaMessageFilePath, messaggioDefault, Encoding.UTF8);
                    return messaggioDefault;
                }
                return File.ReadAllText(scadenzaMessageFilePath, Encoding.UTF8);
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore nella lettura del file scadenza.txt: {ex.Message}\n\nVerrà usato un messaggio predefinito.", "Attenzione", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                return "Ciao [nome],\n\nTi ricordiamo che hai terminato le lezioni del tuo pacchetto.";
            }
        }

        private void ApriWhatsApp(string numeroTelefono, string messaggio)
        {
            try
            {
                string messaggioCodificato = Uri.EscapeDataString(messaggio);
                string numeroFormattato = numeroTelefono.TrimStart('+');
                string whatsappUrl = $"https://wa.me/{numeroFormattato}?text={messaggioCodificato}";
                Process.Start(new ProcessStartInfo { FileName = whatsappUrl, UseShellExecute = true });
            }
            catch (Exception ex)
            {
                throw new Exception($"Impossibile aprire WhatsApp: {ex.Message}");
            }
        }

        private TimeSpan ParseTimeStringRobust(string timeStr)
        {
            if (string.IsNullOrWhiteSpace(timeStr)) return TimeSpan.Zero;
            TimeSpan.TryParse(timeStr, CultureInfo.InvariantCulture, out TimeSpan result);
            return result;
        }

        private void UpdateTodaysLessons()
        {
            try
            {
                var lezioniOggi = prenotazioni.Items
                    ?.Where(p => p.Data.Date == DateTime.Today.Date)
                    .OrderBy(p => ParseTimeStringRobust(p.OraInizioStr))
                    .ToList() ?? new List<Prenotazione>();

                flpLezioniOggi.Controls.Clear();

                Panel headerPanel = new Panel { Size = new Size(flpLezioniOggi.Width - 25, 30), BackColor = Color.FromArgb(230, 230, 230), Margin = new Padding(3, 3, 3, 5) };
                Label lblHeaderOrario = new Label { Text = "Orario", Font = new Font("Segoe UI", 9, FontStyle.Bold), Location = new Point(10, 7), AutoSize = true };
                Label lblHeaderCliente = new Label { Text = "Nome Cognome cliente", Font = new Font("Segoe UI", 9, FontStyle.Bold), Location = new Point(120, 7), AutoSize = true };
                Label lblHeaderStrumento = new Label { Text = "Strumento", Font = new Font("Segoe UI", 9, FontStyle.Bold), Location = new Point(300, 7), AutoSize = true };
                Label lblHeaderInsegnante = new Label { Text = "Insegnante", Font = new Font("Segoe UI", 9, FontStyle.Bold), Location = new Point(370, 7), AutoSize = true };

                headerPanel.Controls.Add(lblHeaderOrario);
                headerPanel.Controls.Add(lblHeaderCliente);
                headerPanel.Controls.Add(lblHeaderStrumento);
                headerPanel.Controls.Add(lblHeaderInsegnante);
                flpLezioniOggi.Controls.Add(headerPanel);

                if (lezioniOggi.Count == 0)
                {
                    Label lblNoLezioni = new Label { Text = "Nessuna lezione programmata per oggi.", AutoSize = true, Margin = new Padding(10), Font = new Font("Segoe UI", 9, FontStyle.Italic) };
                    flpLezioniOggi.Controls.Add(lblNoLezioni);
                    return;
                }

                foreach (var lezione in lezioniOggi)
                {
                    var cliente = clienti.Items?.FirstOrDefault(c => c.Id == lezione.ClienteId);
                    string nomeCliente = cliente != null ? $"{cliente.Nome} {cliente.Cognome}" : "Cliente non trovato";
                    var insegnante = insegnanti.Items?.FirstOrDefault(i => i.Id == lezione.InsegnanteId);
                    string nomeInsegnante = insegnante != null ? $"{insegnante.Nome} {insegnante.Cognome}" : "Insegnante non trovato";

                    Panel lezionePanel = new Panel { Size = new Size(flpLezioniOggi.Width - 25, 30), BorderStyle = BorderStyle.None, Margin = new Padding(3, 2, 3, 2) };

                    switch (lezione.Stato)
                    {
                        case StatoLezione.Programmata: lezionePanel.BackColor = Color.FromArgb(230, 255, 230); break;
                        case StatoLezione.Svolta: lezionePanel.BackColor = Color.FromArgb(230, 240, 255); break;
                        case StatoLezione.Assente: lezionePanel.BackColor = Color.FromArgb(255, 230, 230); break;
                        case StatoLezione.Rimandata: lezionePanel.BackColor = Color.FromArgb(255, 240, 200); break;
                        case StatoLezione.Riprogrammata: lezionePanel.BackColor = Color.FromArgb(255, 255, 200); break;
                    }

                    Label lblOrario = new Label { Text = $"{lezione.OraInizioStr} - {lezione.OraFineStr}", Font = new Font("Segoe UI", 9), Location = new Point(10, 7), AutoSize = true };
                    Label lblCliente = new Label { Text = nomeCliente, Font = new Font("Segoe UI", 9), Location = new Point(120, 7), AutoSize = true };
                    Label lblStrumento = new Label { Text = lezione.Strumento, Font = new Font("Segoe UI", 9), Location = new Point(300, 7), AutoSize = true };
                    Label lblInsegnante = new Label { Text = nomeInsegnante, Font = new Font("Segoe UI", 9), Location = new Point(370, 7), AutoSize = true };

                    lezionePanel.Controls.Add(lblOrario);
                    lezionePanel.Controls.Add(lblCliente);
                    lezionePanel.Controls.Add(lblStrumento);
                    lezionePanel.Controls.Add(lblInsegnante);
                    flpLezioniOggi.Controls.Add(lezionePanel);
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore nell'aggiornamento delle lezioni di oggi: {ex.Message}", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void UpdateRecentPurchases()
        {
            try
            {
                var ultimiAcquisti = acquisti.Items?.OrderByDescending(a => a.DataAcquisto).Take(5).ToList() ?? new List<Models.Acquisto>();

                flpUltimiAcquisti.Controls.Clear();

                Panel headerPanel = new Panel { Size = new Size(flpUltimiAcquisti.Width - 25, 30), BackColor = Color.FromArgb(230, 230, 230), Margin = new Padding(3, 3, 3, 5) };
                Label lblHeaderData = new Label { Text = "Data", Font = new Font("Segoe UI", 9, FontStyle.Bold), Location = new Point(10, 7), AutoSize = true };
                Label lblHeaderCliente = new Label { Text = "Nome Cognome cliente", Font = new Font("Segoe UI", 9, FontStyle.Bold), Location = new Point(120, 7), AutoSize = true };
                Label lblHeaderCifra = new Label { Text = "Cifra", Font = new Font("Segoe UI", 9, FontStyle.Bold), Location = new Point(300, 7), AutoSize = true };
                Label lblHeaderStato = new Label { Text = "Stato pagamento", Font = new Font("Segoe UI", 9, FontStyle.Bold), Location = new Point(370, 7), AutoSize = true };

                headerPanel.Controls.Add(lblHeaderData);
                headerPanel.Controls.Add(lblHeaderCliente);
                headerPanel.Controls.Add(lblHeaderCifra);
                headerPanel.Controls.Add(lblHeaderStato);
                flpUltimiAcquisti.Controls.Add(headerPanel);

                if (ultimiAcquisti.Count == 0)
                {
                    Label lblNoAcquisti = new Label { Text = "Nessun acquisto registrato.", AutoSize = true, Margin = new Padding(10), Font = new Font("Segoe UI", 9, FontStyle.Italic) };
                    flpUltimiAcquisti.Controls.Add(lblNoAcquisti);
                    return;
                }

                foreach (var acquisto in ultimiAcquisti)
                {
                    var cliente = clienti.Items?.FirstOrDefault(c => c.Id == acquisto.ClienteId);
                    string nomeCliente = cliente != null ? $"{cliente.Nome} {cliente.Cognome}" : "Cliente non trovato";

                    Panel acquistoPanel = new Panel { Size = new Size(flpUltimiAcquisti.Width - 25, 30), BorderStyle = BorderStyle.None, Margin = new Padding(3, 2, 3, 2), BackColor = Color.FromArgb(245, 245, 245) };
                    Label lblData = new Label { Text = acquisto.DataAcquisto.ToString("dd/MM/yyyy"), Font = new Font("Segoe UI", 9), Location = new Point(10, 7), AutoSize = true };
                    Label lblCliente = new Label { Text = nomeCliente, Font = new Font("Segoe UI", 9), Location = new Point(120, 7), AutoSize = true };
                    Label lblImporto = new Label { Text = acquisto.ImportoPagato.ToString("C"), Font = new Font("Segoe UI", 9), Location = new Point(300, 7), AutoSize = true };
                    Label lblStato = new Label { Text = acquisto.StatoPagamento, Font = new Font("Segoe UI", 9), Location = new Point(370, 7), AutoSize = true };

                    acquistoPanel.Controls.Add(lblData);
                    acquistoPanel.Controls.Add(lblCliente);
                    acquistoPanel.Controls.Add(lblImporto);
                    acquistoPanel.Controls.Add(lblStato);
                    flpUltimiAcquisti.Controls.Add(acquistoPanel);
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore nell'aggiornamento degli ultimi acquisti: {ex.Message}", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void UpdateStatisticsChart()
        {
            try
            {
                pnlStatistiche.Controls.Clear();
                var now = DateTime.Now;
                int currentYear = now.Year;
                string[] mesi = new string[12];
                int[] numLezioni = new int[12];
                int maxLezioni = 0;

                for (int i = 0; i < 12; i++)
                {
                    DateTime currentMonth = new DateTime(currentYear, i + 1, 1);
                    DateTime nextMonth = currentMonth.AddMonths(1);
                    mesi[i] = currentMonth.ToString("MMM");
                    numLezioni[i] = prenotazioni.Items?.Count(p => p.Data >= currentMonth && p.Data < nextMonth) ?? 0;
                    if (numLezioni[i] > maxLezioni) maxLezioni = numLezioni[i];
                }

                if (maxLezioni == 0)
                {
                    Label lblNoData = new Label { Text = "Nessun dato disponibile per il grafico.", AutoSize = true, Font = new Font("Segoe UI", 9, FontStyle.Italic), Location = new Point(pnlStatistiche.Width / 2 - 100, pnlStatistiche.Height / 2 - 10) };
                    pnlStatistiche.Controls.Add(lblNoData);
                    return;
                }

                Label lblTitle = new Label { Text = $"Lezioni per mese - {currentYear}", AutoSize = true, Font = new Font("Segoe UI", 9, FontStyle.Bold), Location = new Point(pnlStatistiche.Width / 2 - 70, 10) };
                pnlStatistiche.Controls.Add(lblTitle);

                int titleHeight = 40;
                int chartWidth = pnlStatistiche.Width - 50;
                int chartHeight = pnlStatistiche.Height - 70 - titleHeight;
                int barWidth = chartWidth / 15;
                int spacing = 5;

                Panel xAxis = new Panel { Location = new Point(30, titleHeight + chartHeight + 10), Size = new Size(chartWidth, 1), BackColor = Color.Black };
                pnlStatistiche.Controls.Add(xAxis);
                Panel yAxis = new Panel { Location = new Point(30, titleHeight), Size = new Size(1, chartHeight + 10), BackColor = Color.Black };
                pnlStatistiche.Controls.Add(yAxis);

                for (int i = 0; i < 12; i++)
                {
                    int barHeight = (numLezioni[i] * chartHeight) / (maxLezioni > 0 ? maxLezioni : 1);
                    if (barHeight < 1 && numLezioni[i] > 0) barHeight = 1;
                    int xPosition = 30 + (i * ((chartWidth - 10) / 12)) + spacing;
                    Panel bar = new Panel { Location = new Point(xPosition, titleHeight + chartHeight + 10 - barHeight), Size = new Size(barWidth, barHeight), BackColor = Color.FromArgb(0, 122, 204) };
                    pnlStatistiche.Controls.Add(bar);
                    Label lblMese = new Label { Text = mesi[i], AutoSize = true, Font = new Font("Segoe UI", 7), Location = new Point(xPosition + (barWidth / 2) - 10, titleHeight + chartHeight + 12) };
                    pnlStatistiche.Controls.Add(lblMese);

                    if (barHeight > 15 && numLezioni[i] > 0)
                    {
                        int valueY = titleHeight + chartHeight + 10 - barHeight - 15;
                        if (valueY < titleHeight + 5) { valueY = titleHeight + chartHeight + 10 - barHeight / 2; }
                        Label lblNum = new Label { Text = numLezioni[i].ToString(), AutoSize = true, Font = new Font("Segoe UI", 7), ForeColor = valueY < titleHeight + 5 ? Color.White : Color.Black, Location = new Point(xPosition + (barWidth / 2) - 5, valueY) };
                        pnlStatistiche.Controls.Add(lblNum);
                    }
                }

                if (maxLezioni > 0)
                {
                    int step = Math.Max(1, maxLezioni / 4);
                    for (int i = 0; i <= maxLezioni; i += step)
                    {
                        if (i == 0) continue;
                        int yPos = titleHeight + chartHeight + 10 - ((i * chartHeight) / maxLezioni);
                        Panel gridLine = new Panel { Location = new Point(30, yPos), Size = new Size(chartWidth, 1), BackColor = Color.FromArgb(230, 230, 230) };
                        pnlStatistiche.Controls.Add(gridLine);
                        Label lblValue = new Label { Text = i.ToString(), AutoSize = true, Font = new Font("Segoe UI", 7), Location = new Point(15, yPos - 7) };
                        pnlStatistiche.Controls.Add(lblValue);
                    }
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore nell'aggiornamento del grafico: {ex.Message}", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void btnRefresh_Click(object sender, EventArgs e)
        {
            LoadData();
            UpdateDashboard();
        }

        private void DashboardControl_Resize(object sender, EventArgs e)
        {
            UpdateDashboard();
        }

        private void pnlStatistiche_Paint(object sender, PaintEventArgs e) { }
        private void DashboardControl_Load(object sender, EventArgs e) { }

        private void CleanupTimers()
        {
            if (refreshTimer != null)
            {
                refreshTimer.Stop();
                refreshTimer.Dispose();
                refreshTimer = null;
            }

            if (clockTimer != null)
            {
                clockTimer.Stop();
                clockTimer.Dispose();
                clockTimer = null;
            }
        }
    }
}