using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Diagnostics;
using System.Drawing;
using System.Globalization;
using System.IO;
using System.Linq;
using System.Windows.Forms;
using System.Xml.Serialization;

namespace EasyBooking
{
    public partial class PlanningForm : Form
    {
        private string dataPath;
        private ClientiList clienti;
        private InsegnantiList insegnanti;
        private PrenotazioniList prenotazioni;
        private Models.AcquistiList acquisti;
        private AcquistiModels.AcquistiPacchettiList pacchetti;

        // Percorsi dei file XML
        private string clientiFilePath;
        private string insegnantiFilePath;
        private string prenotazioniFilePath;
        private string acquistiFilePath;
        private string pacchettiFilePath;

        // Configurazione calendario
        private DateTime currentWeek;
        private const int CELL_HEIGHT = 50; // altezza celle
        private const int CELL_WIDTH = 150; // larghezza celle
        private const int HOUR_LABEL_WIDTH = 60;
        private const int DAY_HEADER_HEIGHT = 40;
        private const int MIN_HOUR = 8;
        private const int MAX_HOUR = 22;

        // Filtri
        private int selectedClientId = -1;
        private int selectedInsegnanteId = -1;

        // Colori per stati lezione
        private readonly Dictionary<StatoLezione, Color> stateColors = new Dictionary<StatoLezione, Color>
        {
            { StatoLezione.Programmata, Color.LightGreen },
            { StatoLezione.Svolta, Color.LightBlue },
            { StatoLezione.Assente, Color.LightPink },
            { StatoLezione.Rimandata, Color.Orange },
            { StatoLezione.Riprogrammata, Color.LightYellow }
        };

        public PlanningForm(string dataPath)
        {
            InitializeComponent();
            this.dataPath = dataPath;
            InitializePaths();
            LoadData();
            SetupControls();
            currentWeek = GetWeekStart(DateTime.Today);
            UpdateWeekDisplay();

            // Inizializza il pannello dettagli
            ClearAppointmentDetails();

            DrawCalendar();
            LoadTodayAppointments();
        }

        private void InitializePaths()
        {
            clientiFilePath = Path.Combine(dataPath, "clienti.xml");
            insegnantiFilePath = Path.Combine(dataPath, "insegnanti.xml");
            prenotazioniFilePath = Path.Combine(dataPath, "prenotazioni.xml");
            acquistiFilePath = Path.Combine(dataPath, "acquisti.xml");
            pacchettiFilePath = Path.Combine(dataPath, "pacchetti.xml");
        }

        private void LoadData()
        {
            try
            {
                clienti = MainForm.LoadEncryptedXml<ClientiList>(clientiFilePath) ?? new ClientiList { Items = new List<Cliente>() };
                insegnanti = MainForm.LoadEncryptedXml<InsegnantiList>(insegnantiFilePath) ?? new InsegnantiList { Items = new List<Insegnante>() };
                prenotazioni = MainForm.LoadEncryptedXml<PrenotazioniList>(prenotazioniFilePath) ?? new PrenotazioniList { Items = new List<Prenotazione>() };
                acquisti = MainForm.LoadEncryptedXml<Models.AcquistiList>(acquistiFilePath) ?? new Models.AcquistiList { Items = new List<Models.Acquisto>() };
                pacchetti = MainForm.LoadEncryptedXml<AcquistiModels.AcquistiPacchettiList>(pacchettiFilePath) ?? new AcquistiModels.AcquistiPacchettiList { Items = new List<AcquistiModels.AcquistiPacchetto>() };
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"ERRORE CARICAMENTO: {ex.Message}");
                MessageBox.Show($"Errore nel caricamento dei dati: {ex.Message}", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void SetupControls()
        {
            // Setup ComboBox Insegnanti
            cmbInsegnanti.Items.Clear();
            cmbInsegnanti.Items.Add(new ComboBoxItem { Value = -1, Text = "Tutti gli insegnanti" });
            foreach (var insegnante in insegnanti.Items.OrderBy(i => i.Cognome).ThenBy(i => i.Nome))
            {
                cmbInsegnanti.Items.Add(new ComboBoxItem { Value = insegnante.Id, Text = $"{insegnante.Cognome} {insegnante.Nome}" });
            }
            cmbInsegnanti.DisplayMember = "Text";
            cmbInsegnanti.ValueMember = "Value";
            cmbInsegnanti.SelectedIndex = 0;

            // Setup ComboBox Clienti
            cmbClienti.Items.Clear();
            cmbClienti.Items.Add(new ComboBoxItem { Value = -1, Text = "Tutti i clienti" });
            foreach (var cliente in clienti.Items.OrderBy(c => c.Cognome).ThenBy(c => c.Nome))
            {
                cmbClienti.Items.Add(new ComboBoxItem { Value = cliente.Id, Text = $"{cliente.Cognome} {cliente.Nome}" });
            }
            cmbClienti.DisplayMember = "Text";
            cmbClienti.ValueMember = "Value";
            cmbClienti.SelectedIndex = 0;

            // Setup DateTimePicker
            dtpSettimana.Value = DateTime.Today;

            // Setup ListView per appuntamenti di oggi
            lstTodayAppointments.Columns.Clear();
            lstTodayAppointments.Columns.Add("Dettagli", 320);

            // Aggiorna titolo con data corrente
            lblTodayTitle.Text = $"Appuntamenti di Oggi - {DateTime.Today:dd/MM/yyyy}";
        }

        private DateTime GetWeekStart(DateTime date)
        {
            int daysFromMonday = (int)date.DayOfWeek - 1;
            if (daysFromMonday < 0) daysFromMonday = 6; // Domenica
            return date.AddDays(-daysFromMonday);
        }

        private void UpdateWeekDisplay()
        {
            DateTime weekEnd = currentWeek.AddDays(6);
            lblWeek.Text = $"Settimana dal {currentWeek:dd/MM} al {weekEnd:dd/MM}";
            dtpSettimana.Value = currentWeek;
        }

        private void DrawCalendar()
        {
            panelPlanning.Controls.Clear();
            panelPlanning.AutoScroll = false;
            panelPlanning.AutoScroll = true;

            int totalWidth = HOUR_LABEL_WIDTH + (7 * CELL_WIDTH) + 20;
            int totalHeight = DAY_HEADER_HEIGHT + ((MAX_HOUR - MIN_HOUR + 1) * CELL_HEIGHT) + 20;

            panelPlanning.AutoScrollMinSize = new Size(totalWidth, totalHeight);

            DrawDayHeaders();
            DrawHourGrid();
            DrawAppointments();
        }

        private void DrawDayHeaders()
        {
            string[] dayNames = { "Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato", "Domenica" };
            for (int day = 0; day < 7; day++)
            {
                DateTime currentDay = currentWeek.AddDays(day);
                Label dayLabel = new Label
                {
                    Text = $"{dayNames[day]}\n{currentDay:dd/MM}",
                    Size = new Size(CELL_WIDTH, DAY_HEADER_HEIGHT),
                    Location = new Point(HOUR_LABEL_WIDTH + (day * CELL_WIDTH), 0),
                    BackColor = currentDay.Date == DateTime.Today ? Color.LightBlue : Color.LightGray,
                    BorderStyle = BorderStyle.FixedSingle,
                    TextAlign = ContentAlignment.MiddleCenter,
                    Font = new Font("Segoe UI", 9, FontStyle.Bold)
                };
                panelPlanning.Controls.Add(dayLabel);
            }
        }

        private void DrawHourGrid()
        {
            for (int hour = MIN_HOUR; hour <= MAX_HOUR; hour++)
            {
                Label hourLabel = new Label
                {
                    Text = $"{hour:00}:00",
                    Size = new Size(HOUR_LABEL_WIDTH, CELL_HEIGHT),
                    Location = new Point(0, DAY_HEADER_HEIGHT + ((hour - MIN_HOUR) * CELL_HEIGHT)),
                    BackColor = Color.LightGray,
                    BorderStyle = BorderStyle.FixedSingle,
                    TextAlign = ContentAlignment.MiddleCenter,
                    Font = new Font("Segoe UI", 8)
                };
                panelPlanning.Controls.Add(hourLabel);

                for (int day = 0; day < 7; day++)
                {
                    Panel cell = new Panel
                    {
                        Size = new Size(CELL_WIDTH, CELL_HEIGHT),
                        Location = new Point(HOUR_LABEL_WIDTH + (day * CELL_WIDTH), DAY_HEADER_HEIGHT + ((hour - MIN_HOUR) * CELL_HEIGHT)),
                        BorderStyle = BorderStyle.FixedSingle,
                        BackColor = Color.White
                    };

                    DateTime cellDate = currentWeek.AddDays(day);
                    cell.Tag = new { Date = cellDate, Hour = hour };
                    cell.Click += Cell_Click;
                    cell.DoubleClick += Cell_DoubleClick;
                    panelPlanning.Controls.Add(cell);
                }
            }
        }

        private void DrawAppointments()
        {
            var filteredAppointments = GetFilteredAppointments()
                .Where(p => p.Data >= currentWeek && p.Data < currentWeek.AddDays(7))
                .ToList();

            var appointmentsByDay = filteredAppointments
                .GroupBy(p => p.Data.DayOfWeek)
                .ToDictionary(g => g.Key, g => g.ToList());

            for (int day = 0; day < 7; day++)
            {
                DayOfWeek currentDayOfWeek = currentWeek.AddDays(day).DayOfWeek;
                if (appointmentsByDay.ContainsKey(currentDayOfWeek))
                {
                    ProcessAndDrawAppointmentsForDay(appointmentsByDay[currentDayOfWeek]);
                }
            }
        }

        private void ProcessAndDrawAppointmentsForDay(List<Prenotazione> appointments)
        {
            if (!appointments.Any()) return;

            var sortedAppointments = appointments
                .OrderBy(p => ParseTimeStringRobust(p.OraInizioStr))
                .ThenBy(p => ParseTimeStringRobust(p.OraFineStr))
                .ToList();

            var overlapGroups = new List<List<Prenotazione>>();

            foreach (var appointment in sortedAppointments)
            {
                bool placed = false;
                TimeSpan currentStart = ParseTimeStringRobust(appointment.OraInizioStr);
                TimeSpan currentEnd = ParseTimeStringRobust(appointment.OraFineStr);

                foreach (var group in overlapGroups)
                {
                    bool overlaps = group.Any(p => {
                        TimeSpan pStart = ParseTimeStringRobust(p.OraInizioStr);
                        TimeSpan pEnd = ParseTimeStringRobust(p.OraFineStr);
                        return (currentStart < pEnd && currentEnd > pStart);
                    });

                    if (!overlaps)
                    {
                        group.Add(appointment);
                        placed = true;
                        break;
                    }
                }

                if (!placed)
                {
                    overlapGroups.Add(new List<Prenotazione> { appointment });
                }
            }

            var finalColumns = new Dictionary<int, Tuple<int, int>>(); // Key: AppId, Value: <colIndex, totalCols>
            int totalColsInBlock = overlapGroups.Count;

            for (int i = 0; i < overlapGroups.Count; i++)
            {
                foreach (var app in overlapGroups[i])
                {
                    finalColumns[app.Id] = Tuple.Create(i, totalColsInBlock);
                }
            }

            foreach (var appointment in sortedAppointments)
            {
                var colInfo = finalColumns[appointment.Id];
                DrawAppointment(appointment, colInfo.Item1, colInfo.Item2);
            }
        }

        private void DrawAppointment(Prenotazione appointment, int columnIndex, int totalColumns)
        {
            try
            {
                int dayOfWeek = (int)appointment.Data.DayOfWeek - 1;
                if (dayOfWeek < 0) dayOfWeek = 6;

                TimeSpan oraInizio = ParseTimeStringRobust(appointment.OraInizioStr);
                TimeSpan oraFine = ParseTimeStringRobust(appointment.OraFineStr);

                if (oraInizio == TimeSpan.Zero && oraFine == TimeSpan.Zero) return;

                int startHour = oraInizio.Hours;
                int startMinute = oraInizio.Minutes;

                if (startHour < MIN_HOUR || startHour > MAX_HOUR) return;

                int colWidth = CELL_WIDTH / totalColumns;
                int x = HOUR_LABEL_WIDTH + (dayOfWeek * CELL_WIDTH) + (columnIndex * colWidth) + 1;
                int y = DAY_HEADER_HEIGHT + ((startHour - MIN_HOUR) * CELL_HEIGHT) + (int)((startMinute / 60.0) * CELL_HEIGHT) + 1;

                int durationMinutes = (int)(oraFine - oraInizio).TotalMinutes;
                int height = Math.Max(25, (int)((durationMinutes / 60.0) * CELL_HEIGHT) - 2);
                int width = colWidth - 2;

                Panel appointmentPanel = new Panel
                {
                    Size = new Size(width, height),
                    Location = new Point(x, y),
                    BackColor = GetStateColor(appointment.Stato),
                    BorderStyle = BorderStyle.FixedSingle,
                    Tag = appointment,
                    Cursor = Cursors.Hand
                };

                Label infoLabel = new Label
                {
                    Text = CreateAppointmentText(appointment),
                    Dock = DockStyle.Fill,
                    Font = new Font("Segoe UI", 8, FontStyle.Regular),
                    TextAlign = ContentAlignment.TopLeft,
                    AutoSize = false,
                    Padding = new Padding(2),
                    BackColor = Color.Transparent,
                    ForeColor = Color.Black
                };

                appointmentPanel.Controls.Add(infoLabel);

                appointmentPanel.MouseClick += AppointmentPanel_MouseClick;
                appointmentPanel.DoubleClick += AppointmentPanel_DoubleClick;
                infoLabel.MouseClick += AppointmentPanel_MouseClick;
                infoLabel.DoubleClick += AppointmentPanel_DoubleClick;

                ContextMenuStrip contextMenu = CreateAppointmentContextMenu(appointment);
                appointmentPanel.ContextMenuStrip = contextMenu;
                infoLabel.ContextMenuStrip = contextMenu;

                panelPlanning.Controls.Add(appointmentPanel);
                appointmentPanel.BringToFront();
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"ERRORE nel disegno appuntamento {appointment.Id}: {ex.Message}");
            }
        }

        private TimeSpan ParseTimeStringRobust(string timeStr)
        {
            if (string.IsNullOrWhiteSpace(timeStr)) return TimeSpan.Zero;
            try
            {
                timeStr = timeStr.Trim();
                if (timeStr.Contains(":"))
                {
                    string[] parts = timeStr.Split(':');
                    if (parts.Length == 2 && int.TryParse(parts[0], out int hours) && int.TryParse(parts[1], out int minutes))
                    {
                        return new TimeSpan(hours, minutes, 0);
                    }
                }
                return TimeSpan.Parse(timeStr);
            }
            catch (Exception)
            {
                return TimeSpan.Zero;
            }
        }

        private string CreateAppointmentText(Prenotazione appointment)
        {
            var cliente = clienti.Items.FirstOrDefault(c => c.Id == appointment.ClienteId);
            string nomeCliente = cliente != null ? $"{cliente.Nome} {cliente.Cognome}" : "Cliente non trovato";
            string orarioStrumento = $"{appointment.OraInizioStr}-{appointment.OraFineStr} - {appointment.Strumento ?? "N/A"}";
            string numerazionePacchetto = GetPackageProgression(appointment);
            return $"{nomeCliente}\n{orarioStrumento}\n{numerazionePacchetto}";
        }

        private string GetPackageProgression(Prenotazione appointment)
        {
            try
            {
                if (appointment.AcquistoId <= 0) return "Lezione singola";
                var acquisto = acquisti.Items.FirstOrDefault(a => a.Id == appointment.AcquistoId);
                if (acquisto == null) return "Acquisto non trovato";

                var lezioniAcquisto = prenotazioni.Items
                    .Where(p => p.AcquistoId == appointment.AcquistoId)
                    .OrderBy(p => p.Data)
                    .ThenBy(p => ParseTimeStringRobust(p.OraInizioStr))
                    .ToList();

                int posizione = lezioniAcquisto.FindIndex(l => l.Id == appointment.Id) + 1;
                int totale = acquisto.NumeroLezioni;

                return $"{posizione}/{totale}";
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore nel calcolo progressione pacchetto: {ex.Message}");
                return "N/A";
            }
        }

        private void LoadTodayAppointments()
        {
            try
            {
                lstTodayAppointments.Items.Clear();
                var todayAppointments = prenotazioni.Items
                    .Where(p => p.Data.Date == DateTime.Today)
                    .OrderBy(p => ParseTimeStringRobust(p.OraInizioStr))
                    .GroupBy(p => p.InsegnanteId)
                    .OrderBy(g => GetInsegnanteName(g.Key));

                foreach (var group in todayAppointments)
                {
                    string insegnanteName = GetInsegnanteName(group.Key);
                    ListViewItem headerItem = new ListViewItem($"▼ {insegnanteName}")
                    {
                        Font = new Font("Segoe UI", 9, FontStyle.Bold),
                        BackColor = Color.LightSteelBlue,
                        Tag = new { IsHeader = true, InsegnanteId = group.Key }
                    };
                    lstTodayAppointments.Items.Add(headerItem);

                    foreach (var appointment in group)
                    {
                        var cliente = clienti.Items.FirstOrDefault(c => c.Id == appointment.ClienteId);
                        string clienteName = cliente != null ? $"{cliente.Nome} {cliente.Cognome}" : "Cliente non trovato";
                        string appointmentText = $"  {appointment.OraInizioStr}-{appointment.OraFineStr} • {clienteName}\n" +
                                               $"  {appointment.Strumento ?? "N/A"} • {GetPackageProgression(appointment)}";
                        ListViewItem item = new ListViewItem(appointmentText)
                        {
                            Tag = appointment,
                            BackColor = GetStateColor(appointment.Stato),
                            Font = new Font("Segoe UI", 8)
                        };
                        lstTodayAppointments.Items.Add(item);
                    }

                    if (group != todayAppointments.Last())
                    {
                        lstTodayAppointments.Items.Add(new ListViewItem("") { BackColor = Color.WhiteSmoke, Tag = new { IsSpacer = true } });
                    }
                }

                if (!todayAppointments.Any())
                {
                    lstTodayAppointments.Items.Add(new ListViewItem("Nessun appuntamento oggi") { ForeColor = Color.Gray, Font = new Font("Segoe UI", 9, FontStyle.Italic) });
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore nel caricamento appuntamenti di oggi: {ex.Message}", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        // NUOVO: Popola il pannello dei dettagli
        private void ShowAppointmentDetailsInPanel(Prenotazione appointment)
        {
            if (appointment == null)
            {
                ClearAppointmentDetails();
                return;
            }

            lblAppointmentDetailWelcome.Visible = false;
            lblAppointmentDetails.Visible = true;

            var cliente = clienti.Items.FirstOrDefault(c => c.Id == appointment.ClienteId);
            string clienteName = cliente != null ? $"{cliente.Nome} {cliente.Cognome}" : "Cliente non trovato";
            string insegnanteName = GetInsegnanteName(appointment.InsegnanteId);
            string statoText = GetStatusText(appointment.Stato);
            string progressione = GetPackageProgression(appointment);

            var sb = new System.Text.StringBuilder();
            sb.AppendLine($"CLIENTE: {clienteName.ToUpper()}");
            sb.AppendLine($"DATA: {appointment.Data:dddd d MMMM yyyy}");
            sb.AppendLine($"ORARIO: {appointment.OraInizioStr} - {appointment.OraFineStr}");
            sb.AppendLine($"INSEGNANTE: {insegnanteName}");
            sb.AppendLine($"STRUMENTO: {appointment.Strumento ?? "N/A"}");
            sb.AppendLine($"STATO: {statoText}");
            sb.AppendLine($"PACCHETTO: {progressione}");

            lblAppointmentDetails.Text = sb.ToString();
            panelAppointmentDetail.BackColor = GetStateColor(appointment.Stato);
        }

        // NUOVO: Pulisce il pannello dei dettagli
        private void ClearAppointmentDetails()
        {
            lblAppointmentDetailWelcome.Visible = true;
            lblAppointmentDetails.Visible = false;
            lblAppointmentDetails.Text = "";
            panelAppointmentDetail.BackColor = Color.WhiteSmoke;
        }

        private Color GetStateColor(StatoLezione stato) => stateColors.ContainsKey(stato) ? stateColors[stato] : Color.White;

        private string GetInsegnanteName(int insegnanteId)
        {
            var insegnante = insegnanti.Items.FirstOrDefault(i => i.Id == insegnanteId);
            return insegnante != null ? $"{insegnante.Cognome} {insegnante.Nome}" : "Insegnante non trovato";
        }

        private List<Prenotazione> GetFilteredAppointments()
        {
            var filtered = prenotazioni.Items.AsEnumerable();
            if (selectedClientId > 0) filtered = filtered.Where(p => p.ClienteId == selectedClientId);
            if (selectedInsegnanteId > 0) filtered = filtered.Where(p => p.InsegnanteId == selectedInsegnanteId);
            return filtered.ToList();
        }

        private void Cell_DoubleClick(object sender, EventArgs e)
        {
            try
            {
                if (!(sender is Panel cell) || cell.Tag == null) return;

                dynamic cellInfo = cell.Tag;
                DateTime cellDate = cellInfo.Date;
                int cellHour = cellInfo.Hour;

                var appointmentsInCell = GetFilteredAppointments()
                    .Where(p => p.Data.Date == cellDate.Date)
                    .Where(p => {
                        var oraInizio = ParseTimeStringRobust(p.OraInizioStr);
                        var oraFine = ParseTimeStringRobust(p.OraFineStr);
                        return (oraInizio.Hours <= cellHour && oraFine.Hours > cellHour) || (oraInizio.Hours == cellHour);
                    })
                    .ToList();

                if (appointmentsInCell.Count == 1)
                {
                    ShowClientHistoryDialog(appointmentsInCell[0]);
                }
                else if (appointmentsInCell.Count > 1)
                {
                    ShowAppointmentSelectionMenu(appointmentsInCell, cell.Location);
                }
                else
                {
                    MessageBox.Show($"Nessun appuntamento trovato per {cellDate:dd/MM/yyyy} alle {cellHour}:00", "Informazione", MessageBoxButtons.OK, MessageBoxIcon.Information);
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore: {ex.Message}", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void ShowAppointmentSelectionMenu(List<Prenotazione> appointments, Point location)
        {
            ContextMenuStrip selectionMenu = new ContextMenuStrip();
            foreach (var appointment in appointments)
            {
                var cliente = clienti.Items.FirstOrDefault(c => c.Id == appointment.ClienteId);
                string nomeCliente = cliente != null ? $"{cliente.Nome} {cliente.Cognome}" : "Cliente non trovato";
                string menuText = $"{appointment.OraInizioStr}-{appointment.OraFineStr} • {nomeCliente}";

                ToolStripMenuItem appointmentItem = new ToolStripMenuItem(menuText);
                appointmentItem.Tag = appointment;
                appointmentItem.Click += (s, e) => {
                    if ((s as ToolStripMenuItem)?.Tag is Prenotazione selectedAppointment)
                    {
                        ShowClientHistoryDialog(selectedAppointment);
                    }
                };
                selectionMenu.Items.Add(appointmentItem);
            }
            selectionMenu.Show(panelPlanning.PointToScreen(location));
        }

        private ContextMenuStrip CreateAppointmentContextMenu(Prenotazione appointment)
        {
            ContextMenuStrip menu = new ContextMenuStrip();

            ToolStripMenuItem dettagliItem = new ToolStripMenuItem("Dettagli Cliente (Storico)");
            dettagliItem.Click += (s, e) => ShowClientHistoryDialog(appointment);
            dettagliItem.Font = new Font(dettagliItem.Font, FontStyle.Bold);
            menu.Items.Add(dettagliItem);
            menu.Items.Add(new ToolStripSeparator());

            ToolStripMenuItem assenteItem = new ToolStripMenuItem("Assente");
            if (appointment.Stato == StatoLezione.Assente) { assenteItem.Text = "✓ Assente"; assenteItem.Enabled = false; }
            else { assenteItem.Click += (s, e) => MarkAsAssente(appointment); }
            menu.Items.Add(assenteItem);

            ToolStripMenuItem rimandaItem = new ToolStripMenuItem("Rimanda (Accoda)");
            if (appointment.Stato == StatoLezione.Svolta) { rimandaItem.Enabled = false; rimandaItem.ToolTipText = "Non puoi rimandare una lezione già svolta"; }
            else if (appointment.Stato == StatoLezione.Rimandata) { rimandaItem.Text = "✓ Rimandata"; rimandaItem.Click += (s, e) => RimandaLezione(appointment); }
            else { rimandaItem.Click += (s, e) => RimandaLezione(appointment); }
            menu.Items.Add(rimandaItem);

            ToolStripMenuItem riprogrammaItem = new ToolStripMenuItem("Riprogramma");
            if (appointment.Stato == StatoLezione.Svolta) { riprogrammaItem.Enabled = false; riprogrammaItem.ToolTipText = "Non puoi riprogrammare una lezione già svolta"; }
            else if (appointment.Stato == StatoLezione.Riprogrammata) { riprogrammaItem.Text = "✓ Riprogramma"; riprogrammaItem.Click += (s, e) => RiprogrammaLezione(appointment); }
            else { riprogrammaItem.Click += (s, e) => RiprogrammaLezione(appointment); }
            menu.Items.Add(riprogrammaItem);

            menu.Items.Add(new ToolStripSeparator());

            if (appointment.Stato != StatoLezione.Svolta)
            {
                ToolStripMenuItem svoltaItem = new ToolStripMenuItem("Marca come Svolta");
                svoltaItem.Click += (s, e) => MarkAsSvolta(appointment);
                menu.Items.Add(svoltaItem);
            }
            else
            {
                menu.Items.Add(new ToolStripMenuItem("✓ Svolta") { Enabled = false });
            }

            if (appointment.Stato != StatoLezione.Programmata && appointment.Stato != StatoLezione.Svolta)
            {
                ToolStripMenuItem ripristinaItem = new ToolStripMenuItem("Ripristina a Programmata");
                ripristinaItem.Click += (s, e) => MarkAsProgrammata(appointment);
                menu.Items.Add(ripristinaItem);
            }
            return menu;
        }

        private void UpdateAppointmentState(Prenotazione appointment, StatoLezione newState, string confirmationMessage, string successMessage)
        {
            try
            {
                var cliente = clienti.Items.FirstOrDefault(c => c.Id == appointment.ClienteId);
                string nomeCliente = cliente != null ? $"{cliente.Nome} {cliente.Cognome}" : "Cliente";

                var result = MessageBox.Show(string.Format(confirmationMessage, nomeCliente, appointment.Data, appointment.OraInizioStr),
                    "Conferma", MessageBoxButtons.YesNo, MessageBoxIcon.Question);

                if (result == DialogResult.Yes)
                {
                    appointment.Stato = newState;
                    MainForm.SaveEncryptedXml(prenotazioni, prenotazioniFilePath);
                    DrawCalendar();
                    LoadTodayAppointments();
                    ShowAppointmentDetailsInPanel(appointment); // Aggiorna pannello dettagli
                    MessageBox.Show(successMessage, "Completato", MessageBoxButtons.OK, MessageBoxIcon.Information);
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore: {ex.Message}", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void MarkAsAssente(Prenotazione p) => UpdateAppointmentState(p, StatoLezione.Assente, "Confermi che {0} è assente alla lezione del {1:dd/MM/yyyy} alle {2}?\nLa lezione verrà marcata come persa.", "Lezione marcata come assente.");
        private void MarkAsSvolta(Prenotazione p) => UpdateAppointmentState(p, StatoLezione.Svolta, "Confermi che la lezione di {0} del {1:dd/MM/yyyy} alle {2} è stata svolta?", "Lezione marcata come svolta.");
        private void MarkAsProgrammata(Prenotazione p) => UpdateAppointmentState(p, StatoLezione.Programmata, "Confermi di ripristinare la lezione di {0} del {1:dd/MM/yyyy} alle {2} come programmata?", "Lezione ripristinata come programmata.");

        private void RimandaLezione(Prenotazione appointment)
        {
            var cliente = clienti.Items.FirstOrDefault(c => c.Id == appointment.ClienteId);
            string nomeCliente = cliente != null ? $"{cliente.Nome} {cliente.Cognome}" : "Cliente";

            var result = MessageBox.Show(
                $"Confermi di rimandare la lezione di {nomeCliente} del {appointment.Data:dd/MM/yyyy} alle {appointment.OraInizioStr}?\n\n" +
                "La lezione verrà spostata una settimana dopo l'ultima lezione futura.",
                "Conferma Rimando", MessageBoxButtons.YesNo, MessageBoxIcon.Question);

            if (result != DialogResult.Yes) return;

            try
            {
                var ultimaLezione = prenotazioni.Items
                    .Where(p => p.ClienteId == appointment.ClienteId && p.AcquistoId == appointment.AcquistoId && p.Id != appointment.Id)
                    .OrderByDescending(p => p.Data)
                    .ThenByDescending(p => ParseTimeStringRobust(p.OraInizioStr))
                    .FirstOrDefault();

                DateTime nuovaData = (ultimaLezione?.Data ?? appointment.Data).AddDays(7);

                while (nuovaData.DayOfWeek != appointment.Data.DayOfWeek) { nuovaData = nuovaData.AddDays(1); }

                while (prenotazioni.Items.Any(p => p.Id != appointment.Id && p.ClienteId == appointment.ClienteId && p.Data.Date == nuovaData.Date && ParseTimeStringRobust(p.OraInizioStr) == ParseTimeStringRobust(appointment.OraInizioStr)))
                {
                    nuovaData = nuovaData.AddDays(7);
                }

                appointment.Data = nuovaData;
                appointment.Stato = StatoLezione.Rimandata;

                MainForm.SaveEncryptedXml(prenotazioni, prenotazioniFilePath);
                DrawCalendar();
                LoadTodayAppointments();
                ShowAppointmentDetailsInPanel(appointment);

                MessageBox.Show($"Lezione rimandata al {nuovaData:dd/MM/yyyy}.", "Completato", MessageBoxButtons.OK, MessageBoxIcon.Information);
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore nel rimandare la lezione: {ex.Message}", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void RiprogrammaLezione(Prenotazione appointment)
        {
            try
            {
                using (var quickEditForm = new QuickEditLessonForm(appointment, dataPath))
                {
                    if (quickEditForm.ShowDialog(this) == DialogResult.OK)
                    {
                        appointment.Stato = StatoLezione.Riprogrammata;
                        MainForm.SaveEncryptedXml(prenotazioni, prenotazioniFilePath);

                        LoadData(); // Ricarica tutto per coerenza
                        DrawCalendar();
                        LoadTodayAppointments();
                        ShowAppointmentDetailsInPanel(appointment);

                        MessageBox.Show($"Lezione riprogrammata con successo.", "Riprogrammazione Completata", MessageBoxButtons.OK, MessageBoxIcon.Information);
                    }
                }
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore nella riprogrammazione: {ex.Message}", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void ShowClientHistoryDialog(Prenotazione appointment)
        {
            try
            {
                var cliente = clienti.Items.FirstOrDefault(c => c.Id == appointment.ClienteId);
                if (cliente == null)
                {
                    MessageBox.Show("Cliente non trovato.", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    return;
                }

                var ultimeLezioniFatte = prenotazioni.Items
                    .Where(p => p.ClienteId == appointment.ClienteId && (p.Stato == StatoLezione.Svolta || p.Stato == StatoLezione.Assente) && p.Data <= DateTime.Today && p.Id != appointment.Id)
                    .OrderByDescending(p => p.Data).ThenByDescending(p => ParseTimeStringRobust(p.OraInizioStr)).Take(10).ToList();

                var lezioniFuture = prenotazioni.Items
                    .Where(p => p.ClienteId == appointment.ClienteId && p.Data >= DateTime.Today && p.Stato != StatoLezione.Svolta && p.Stato != StatoLezione.Assente && p.Id != appointment.Id)
                    .OrderBy(p => p.Data).ThenBy(p => ParseTimeStringRobust(p.OraInizioStr)).ToList();

                ShowClientLessonsDialog(cliente, ultimeLezioniFatte, lezioniFuture, appointment);
            }
            catch (Exception ex)
            {
                MessageBox.Show($"Errore nel caricamento dettagli cliente: {ex.Message}", "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private void ShowClientLessonsDialog(Cliente cliente, List<Prenotazione> ultimeLezioniFatte, List<Prenotazione> lezioniFuture, Prenotazione lezioneCorrente = null)
        {
            using (Form dialogForm = new Form
            {
                Text = $"Dettagli Storico Cliente - {cliente.Nome} {cliente.Cognome}",
                Size = new Size(600, 600),
                StartPosition = FormStartPosition.CenterParent,
                MinimizeBox = false,
                MaximizeBox = false,
                FormBorderStyle = FormBorderStyle.FixedDialog
            })
            {
                Panel mainPanel = new Panel { Dock = DockStyle.Fill, Padding = new Padding(10) };
                dialogForm.Controls.Add(mainPanel);

                int currentY = 0;
                Label clientInfoLabel = new Label { Text = $"Cliente: {cliente.Nome} {cliente.Cognome}\nTelefono: {cliente.Telefono ?? "N/A"}\nEmail: {cliente.Email ?? "N/A"}", Font = new Font("Segoe UI", 10, FontStyle.Bold), AutoSize = true, Location = new Point(0, currentY) };
                mainPanel.Controls.Add(clientInfoLabel);
                currentY = clientInfoLabel.Bottom + 20;

                if (lezioneCorrente != null)
                {
                    Label lezioneCorrenteLabel = new Label { Text = "Lezione selezionata:", Font = new Font("Segoe UI", 10, FontStyle.Bold), Location = new Point(0, currentY), AutoSize = true, ForeColor = Color.DarkBlue };
                    mainPanel.Controls.Add(lezioneCorrenteLabel);
                    currentY = lezioneCorrenteLabel.Bottom + 5;
                    Panel lezioneCorrentePanel = new Panel { Location = new Point(0, currentY), Size = new Size(560, 70), BackColor = GetStateColor(lezioneCorrente.Stato), BorderStyle = BorderStyle.FixedSingle };
                    Label lezioneCorrenteInfo = new Label { Text = $"📅 {lezioneCorrente.Data:dd/MM/yyyy} - {lezioneCorrente.OraInizioStr}-{lezioneCorrente.OraFineStr}\n🎵 {lezioneCorrente.Strumento ?? "N/A"} • 👨‍🏫 {GetInsegnanteName(lezioneCorrente.InsegnanteId)}\n📊 {GetStatusText(lezioneCorrente.Stato)} • {GetPackageProgression(lezioneCorrente)}", Font = new Font("Segoe UI", 10, FontStyle.Bold), Location = new Point(8, 8), Size = new Size(544, 54), ForeColor = Color.Black };
                    lezioneCorrentePanel.Controls.Add(lezioneCorrenteInfo);
                    mainPanel.Controls.Add(lezioneCorrentePanel);
                    currentY = lezioneCorrentePanel.Bottom + 20;
                }

                mainPanel.Controls.Add(new Label { Text = "Ultime lezioni svolte (max 10):", Font = new Font("Segoe UI", 9, FontStyle.Bold), Location = new Point(0, currentY), AutoSize = true });
                currentY += 25;
                ListBox ultimeListBox = new ListBox { Location = new Point(0, currentY), Size = new Size(560, 110), Font = new Font("Segoe UI", 8) };
                foreach (var l in ultimeLezioniFatte) ultimeListBox.Items.Add($"{l.Data:dd/MM/yyyy} - {l.OraInizioStr} - {l.Strumento} - {GetInsegnanteName(l.InsegnanteId)} - {(l.Stato == StatoLezione.Svolta ? "Svolta" : "Assente")} - {GetPackageProgression(l)}");
                if (!ultimeLezioniFatte.Any()) ultimeListBox.Items.Add("Nessuna lezione svolta trovata");
                mainPanel.Controls.Add(ultimeListBox);
                currentY = ultimeListBox.Bottom + 20;

                mainPanel.Controls.Add(new Label { Text = "Lezioni future programmate:", Font = new Font("Segoe UI", 9, FontStyle.Bold), Location = new Point(0, currentY), AutoSize = true });
                currentY += 25;
                ListBox futureListBox = new ListBox { Location = new Point(0, currentY), Size = new Size(560, 110), Font = new Font("Segoe UI", 8) };
                foreach (var l in lezioniFuture) futureListBox.Items.Add($"{(lezioneCorrente != null && l.Id == lezioneCorrente.Id ? "► " : "  ")}{l.Data:dd/MM/yyyy} - {l.OraInizioStr} - {l.Strumento} - {GetInsegnanteName(l.InsegnanteId)} - {GetStatusText(l.Stato)} - {GetPackageProgression(l)}");
                if (!lezioniFuture.Any()) futureListBox.Items.Add("Nessuna lezione futura programmata");
                mainPanel.Controls.Add(futureListBox);
                currentY = futureListBox.Bottom + 20;

                Button closeButton = new Button { Text = "Chiudi", Size = new Size(80, 30), Location = new Point(490, currentY), DialogResult = DialogResult.OK };
                mainPanel.Controls.Add(closeButton);

                dialogForm.ShowDialog();
            }
        }

        private string GetStatusText(StatoLezione stato)
        {
            switch (stato)
            {
                case StatoLezione.Programmata: return "Programmata";
                case StatoLezione.Svolta: return "Svolta";
                case StatoLezione.Assente: return "Assente";
                case StatoLezione.Rimandata: return "Rimandata";
                case StatoLezione.Riprogrammata: return "Riprogrammata";
                default: return "Sconosciuto";
            }
        }

        private void Cell_Click(object sender, EventArgs e) { }

        private void AppointmentPanel_MouseClick(object sender, MouseEventArgs e)
        {
            if (e.Button == MouseButtons.Left)
            {
                Control control = sender as Control;
                Prenotazione appointment = control?.Tag as Prenotazione ?? (control?.Parent as Control)?.Tag as Prenotazione;
                if (appointment != null)
                {
                    ShowAppointmentDetailsInPanel(appointment);
                }
            }
        }

        private void AppointmentPanel_DoubleClick(object sender, EventArgs e)
        {
            Control control = sender as Control;
            Prenotazione appointment = control?.Tag as Prenotazione ?? (control?.Parent as Control)?.Tag as Prenotazione;
            if (appointment != null)
            {
                ShowClientHistoryDialog(appointment);
            }
        }

        private void lstTodayAppointments_DoubleClick(object sender, EventArgs e)
        {
            if (lstTodayAppointments.SelectedItems.Count > 0 && lstTodayAppointments.SelectedItems[0].Tag is Prenotazione appointment)
            {
                ShowClientHistoryDialog(appointment);
            }
        }

        private void lstTodayAppointments_DrawItem(object sender, DrawListViewItemEventArgs e) => e.DrawDefault = true;
        private void lstTodayAppointments_DrawSubItem(object sender, DrawListViewSubItemEventArgs e) => e.DrawDefault = true;

        private void btnPrevWeek_Click(object sender, EventArgs e) { currentWeek = currentWeek.AddDays(-7); UpdateWeekDisplay(); DrawCalendar(); }
        private void btnNextWeek_Click(object sender, EventArgs e) { currentWeek = currentWeek.AddDays(7); UpdateWeekDisplay(); DrawCalendar(); }
        private void btnToday_Click(object sender, EventArgs e) { currentWeek = GetWeekStart(DateTime.Today); UpdateWeekDisplay(); DrawCalendar(); }

        private void dtpSettimana_ValueChanged(object sender, EventArgs e)
        {
            if (GetWeekStart(dtpSettimana.Value) != currentWeek)
            {
                currentWeek = GetWeekStart(dtpSettimana.Value);
                UpdateWeekDisplay();
                DrawCalendar();
            }
        }

        private void cmbInsegnanti_SelectedIndexChanged(object sender, EventArgs e)
        {
            if (cmbInsegnanti.SelectedItem is ComboBoxItem item)
            {
                selectedInsegnanteId = (int)item.Value;
                DrawCalendar();
                ClearAppointmentDetails();
            }
        }

        private void cmbClienti_SelectedIndexChanged(object sender, EventArgs e)
        {
            if (cmbClienti.SelectedItem is ComboBoxItem item)
            {
                selectedClientId = (int)item.Value;
                DrawCalendar();
                ClearAppointmentDetails();
            }
        }

        public class ComboBoxItem { public int Value { get; set; } public string Text { get; set; } }
    }
}