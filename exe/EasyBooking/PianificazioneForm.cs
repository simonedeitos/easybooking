using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.IO;
using System.Linq;
using System.Windows.Forms;
using System.Xml.Serialization;
using System.Diagnostics;
using System.Threading;
using EasyBooking.Models;
using EasyBooking.AcquistiModels;

namespace EasyBooking
{
    public partial class PianificazioneForm : Form
    {
        private string dataPath;
        private ClientiList clienti;
        private InsegnantiList insegnanti;
        private PrenotazioniList prenotazioni;
        private Models.AcquistiList acquisti;
        private Models.ModelImpostazioniGenerali impostazioni;
        private StrumentiList strumenti;
        private AcquistiModels.AcquistiPacchettiList pacchetti;

        // Variabili per orario
        private int oraForzata = 15;
        private int minutiForzati = 0;

        // Percorsi file
        private string clientiFilePath;
        private string insegnantiFilePath;
        private string prenotazioniFilePath;
        private string acquistiFilePath;
        private string impostazioniFilePath;
        private string strumentiFilePath;
        private string pacchettiFilePath;

        // Acquisto da pianificare
        private Models.Acquisto acquistoPreselezionato;
        private List<Models.Acquisto> acquistiDaPianificare;

        // UI per anteprima lezioni
        private Panel panelAnteprimaLezioni;
        private List<Label> lezioniLabels;

        // Flag per sopprimere i messaggi di errore
        private static bool suppressMessageBoxes = false;

        public PianificazioneForm(string dataPath, Models.Acquisto acquisto)
        {
            try
            {
                // Attiva la soppressione dei messaggi di errore
                suppressMessageBoxes = true;

                // Installa handler per intercettare errori XML
                AppDomain.CurrentDomain.UnhandledException += SuppressXmlErrors;
                Application.ThreadException += SuppressThreadXmlErrors;

                this.dataPath = dataPath;
                this.acquistoPreselezionato = acquisto;

                // Inizializza paths e proprietà di base
                InitializePaths();
                InitializeProperties();

                // Carica i componenti dell'interfaccia
                InitializeComponent();

                // Carica i dati in modo silenzioso
                LoadDataSilently();

                // Configura l'interfaccia utente
                ConfigureForm();
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"[PianificazioneForm] Errore nel costruttore: {ex.Message}");
                // Non mostrare errori all'utente
            }
            finally
            {
                // Ripristina il comportamento normale
                suppressMessageBoxes = false;

                // Rimuove gli handler temporanei
                AppDomain.CurrentDomain.UnhandledException -= SuppressXmlErrors;
                Application.ThreadException -= SuppressThreadXmlErrors;
            }
        }

        #region Gestione Errori XML

        private void SuppressXmlErrors(object sender, UnhandledExceptionEventArgs e)
        {
            if (suppressMessageBoxes && e.ExceptionObject is Exception ex)
            {
                if (ex is System.Xml.XmlException ||
                    ex.ToString().Contains("XML") ||
                    ex.ToString().Contains("xml"))
                {
                    Debug.WriteLine($"Errore XML soppresso: {ex.Message}");
                    // Impedisce la propagazione dell'eccezione
                }
            }
        }

        private void SuppressThreadXmlErrors(object sender, ThreadExceptionEventArgs e)
        {
            if (suppressMessageBoxes)
            {
                if (e.Exception is System.Xml.XmlException ||
                    e.Exception.Message.Contains("XML") ||
                    e.Exception.Message.Contains("xml") ||
                    e.Exception is System.InvalidOperationException)
                {
                    Debug.WriteLine($"Thread XML error suppressed: {e.Exception.Message}");
                }
            }
        }

        #endregion

        #region Inizializzazione

        private void InitializePaths()
        {
            clientiFilePath = Path.Combine(dataPath, "clienti.xml");
            insegnantiFilePath = Path.Combine(dataPath, "insegnanti.xml");
            prenotazioniFilePath = Path.Combine(dataPath, "prenotazioni.xml");
            acquistiFilePath = Path.Combine(dataPath, "acquisti.xml");
            impostazioniFilePath = Path.Combine(dataPath, "impostazioni-generali.xml");
            strumentiFilePath = Path.Combine(dataPath, "strumenti.xml");
            pacchettiFilePath = Path.Combine(dataPath, "pacchetti.xml");
        }

        private void InitializeProperties()
        {
            acquistiDaPianificare = new List<Models.Acquisto>();
            lezioniLabels = new List<Label>();

            // Inizializza con valori default per evitare NullReferenceException
            clienti = new ClientiList { Items = new List<Cliente>() };
            insegnanti = new InsegnantiList { Items = new List<Insegnante>() };
            prenotazioni = new PrenotazioniList { Items = new List<Prenotazione>() };
            acquisti = new Models.AcquistiList { Items = new List<Models.Acquisto>() };
            strumenti = new StrumentiList { Items = new List<Strumento>() };
            pacchetti = new AcquistiModels.AcquistiPacchettiList { Items = new List<AcquistiPacchetto>() };
            impostazioni = new Models.ModelImpostazioniGenerali
            {
                LunAttivo = true,
                MarAttivo = true,
                MerAttivo = true,
                GioAttivo = true,
                VenAttivo = true,
                SabAttivo = false,
                DomAttivo = false,
                MattInizio = 9,
                MattFine = 13,
                PomInizio = 15,
                PomFine = 19,
                DurataLezioneDefault = 60
            };
        }

        #endregion

        #region Caricamento Dati

        private void LoadDataSilently()
        {
            try
            {
                Debug.WriteLine("=== INIZIO CARICAMENTO DATI PIANIFICAZIONE ===");

                // Caricamento clienti
                clienti = LoadXmlSilently<ClientiList>(clientiFilePath) ??
                          new ClientiList { Items = new List<Cliente>() };
                Debug.WriteLine($"Clienti caricati: {clienti.Items?.Count ?? 0}");

                // Caricamento insegnanti
                insegnanti = LoadXmlSilently<InsegnantiList>(insegnantiFilePath) ??
                             new InsegnantiList { Items = new List<Insegnante>() };
                Debug.WriteLine($"Insegnanti caricati: {insegnanti.Items?.Count ?? 0}");

                // Caricamento prenotazioni con metodo speciale
                prenotazioni = LoadPrenotazioniSafely();
                Debug.WriteLine($"Prenotazioni caricate: {prenotazioni.Items?.Count ?? 0}");

                // Caricamento acquisti
                acquisti = LoadXmlSilently<Models.AcquistiList>(acquistiFilePath) ??
                           new Models.AcquistiList { Items = new List<Models.Acquisto>() };
                Debug.WriteLine($"Acquisti caricati: {acquisti.Items?.Count ?? 0}");

                // Caricamento strumenti
                strumenti = LoadXmlSilently<StrumentiList>(strumentiFilePath) ??
                            new StrumentiList { Items = new List<Strumento>() };
                Debug.WriteLine($"Strumenti caricati: {strumenti.Items?.Count ?? 0}");

                // Caricamento pacchetti
                pacchetti = LoadXmlSilently<AcquistiModels.AcquistiPacchettiList>(pacchettiFilePath) ??
                            new AcquistiModels.AcquistiPacchettiList { Items = new List<AcquistiPacchetto>() };
                Debug.WriteLine($"Pacchetti caricati: {pacchetti.Items?.Count ?? 0}");

                // Caricamento impostazioni
                impostazioni = LoadXmlSilently<Models.ModelImpostazioniGenerali>(impostazioniFilePath);
                if (impostazioni == null)
                {
                    impostazioni = new Models.ModelImpostazioniGenerali
                    {
                        LunAttivo = true,
                        MarAttivo = true,
                        MerAttivo = true,
                        GioAttivo = true,
                        VenAttivo = true,
                        SabAttivo = false,
                        DomAttivo = false,
                        MattInizio = 9,
                        MattFine = 13,
                        PomInizio = 15,
                        PomFine = 19,
                        DurataLezioneDefault = 60
                    };
                }
                Debug.WriteLine("Impostazioni caricate");

                Debug.WriteLine("=== FINE CARICAMENTO DATI PIANIFICAZIONE ===");
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in LoadDataSilently: {ex.Message}");
                // Non visualizzare errori all'utente
            }
        }

        private T LoadXmlSilently<T>(string filePath) where T : class, new()
        {
            try
            {
                if (!File.Exists(filePath))
                {
                    return new T();
                }

                try
                {
                    // Prima tenta di caricare normalmente ma senza mostrare messaggi di errore
                    byte[] fileBytes = File.ReadAllBytes(filePath);

                    // Tenta la deserializzazione diretta (non cifrata)
                    try
                    {
                        using (MemoryStream ms = new MemoryStream(fileBytes))
                        {
                            var serializer = new XmlSerializer(typeof(T));
                            return (T)serializer.Deserialize(ms);
                        }
                    }
                    catch
                    {
                        // Se fallisce, prova con la decriptazione
                        try
                        {
                            // Uso reflection per chiamare il metodo di decriptazione senza dipendenze dirette
                            string password = "EasyBooking!2025";

                            // Chiamata al metodo di decriptazione tramite reflection
                            var mainFormType = typeof(MainForm);
                            var decryptMethod = mainFormType.GetMethod("DecryptStringFromBytes",
                                System.Reflection.BindingFlags.Static |
                                System.Reflection.BindingFlags.Public |
                                System.Reflection.BindingFlags.NonPublic);

                            if (decryptMethod != null)
                            {
                                string xmlString = (string)decryptMethod.Invoke(null, new object[] { fileBytes, password });

                                XmlSerializer serializer = new XmlSerializer(typeof(T));
                                using (StringReader reader = new StringReader(xmlString))
                                {
                                    return (T)serializer.Deserialize(reader);
                                }
                            }
                        }
                        catch (Exception ex)
                        {
                            Debug.WriteLine($"Errore nella decriptazione: {ex.Message}");
                        }
                    }
                }
                catch (Exception ex)
                {
                    Debug.WriteLine($"Errore nel caricamento di {filePath}: {ex.Message}");
                }

                // Se tutto fallisce, ritorna un nuovo oggetto
                return new T();
            }
            catch
            {
                return new T();
            }
        }

        private PrenotazioniList LoadPrenotazioniSafely()
        {
            try
            {
                // Se il file non esiste, crea un nuovo file con una lista vuota
                if (!File.Exists(prenotazioniFilePath))
                {
                    var emptyList = new PrenotazioniList { Items = new List<Prenotazione>() };

                    try
                    {
                        // Assicurati che la directory esista
                        string directory = Path.GetDirectoryName(prenotazioniFilePath);
                        if (!string.IsNullOrEmpty(directory) && !Directory.Exists(directory))
                        {
                            Directory.CreateDirectory(directory);
                        }

                        // Crea un nuovo file XML semplice (non criptato)
                        using (var writer = new StreamWriter(prenotazioniFilePath))
                        {
                            var serializer = new XmlSerializer(typeof(PrenotazioniList));
                            serializer.Serialize(writer, emptyList);
                        }
                        Debug.WriteLine("Creato nuovo file prenotazioni.xml");
                    }
                    catch (Exception ex)
                    {
                        Debug.WriteLine($"Errore nella creazione del file prenotazioni: {ex.Message}");
                    }

                    return emptyList;
                }

                // Prova a caricare il file esistente in diversi modi

                // 1. Prima prova la deserializzazione diretta (non cifrata)
                try
                {
                    using (var reader = new StreamReader(prenotazioniFilePath))
                    {
                        var serializer = new XmlSerializer(typeof(PrenotazioniList));
                        var result = (PrenotazioniList)serializer.Deserialize(reader);
                        if (result != null)
                        {
                            result.Items = result.Items ?? new List<Prenotazione>();
                            Debug.WriteLine("File prenotazioni caricato correttamente");
                            return result;
                        }
                    }
                }
                catch (Exception ex)
                {
                    Debug.WriteLine($"Errore nella deserializzazione diretta: {ex.Message}");
                }

                // 2. Prova con il metodo silenzioso personalizzato
                var loadedList = LoadXmlSilently<PrenotazioniList>(prenotazioniFilePath);
                if (loadedList != null)
                {
                    loadedList.Items = loadedList.Items ?? new List<Prenotazione>();
                    return loadedList;
                }

                // 3. Fallback: crea una nuova lista vuota
                return new PrenotazioniList { Items = new List<Prenotazione>() };
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore critico in LoadPrenotazioniSafely: {ex.Message}");
                return new PrenotazioniList { Items = new List<Prenotazione>() };
            }
        }

        #endregion

        #region Configurazione Form e UI

        private void ConfigureForm()
        {
            try
            {
                // Configura il titolo e le informazioni del cliente/acquisto
                if (acquistoPreselezionato != null)
                {
                    // Cerca il cliente
                    Cliente cliente = clienti.Items.FirstOrDefault(c => c.Id == acquistoPreselezionato.ClienteId);
                    if (cliente != null)
                    {
                        this.Text = $"Pianificazione Lezioni - {cliente.Cognome} {cliente.Nome}";
                        lblClienteNome.Text = $"{cliente.Cognome} {cliente.Nome}";
                        lblAcquisto.Text = $"Acquisto: {acquistoPreselezionato.DataAcquisto:dd/MM/yyyy} - " +
                                         $"{acquistoPreselezionato.NomePacchetto} - " +
                                         $"{acquistoPreselezionato.NumeroLezioni} lezioni";
                    }

                    // Aggiungi l'acquisto alla lista da pianificare
                    acquistiDaPianificare.Add(acquistoPreselezionato);
                }

                // Configura i controlli base
                dtpDataInizio.Value = DateTime.Today;
                dtpOraInizio.Format = DateTimePickerFormat.Custom;
                dtpOraInizio.CustomFormat = "HH:mm";
                dtpOraInizio.ShowUpDown = true;
                dtpOraInizio.Value = new DateTime(DateTime.Today.Year, DateTime.Today.Month, DateTime.Today.Day, 15, 0, 0);
                dtpOraInizio.ValueChanged += DtpOraInizio_ValueChanged;

                // Imposta i valori numerici
                nudDurata.Value = impostazioni?.DurataLezioneDefault ?? 60;
                nudNumeroSettimane.Value = 10;

                // Configura ComboBox degli strumenti
                cmbStrumento.Items.Clear();
                cmbStrumento.Items.Add("Automatico (in base al pacchetto)");

                if (strumenti?.Items != null)
                {
                    var strumentiUnici = strumenti.Items
                        .Select(s => s.Nome)
                        .Where(n => !string.IsNullOrEmpty(n))
                        .Distinct()
                        .OrderBy(s => s);

                    foreach (var strumento in strumentiUnici)
                    {
                        cmbStrumento.Items.Add(strumento);
                    }
                }
                cmbStrumento.SelectedIndexChanged += cmbStrumento_SelectedIndexChanged;
                cmbStrumento.SelectedIndex = 0;

                // Popoliamo il combobox degli insegnanti senza filtro iniziale
                PopulateInsegnantiComboBox();

                // Configura ComboBox frequenza
                cmbFrequenza.Items.Clear();
                cmbFrequenza.Items.Add("Settimanale");
                cmbFrequenza.Items.Add("Bisettimanale");
                cmbFrequenza.Items.Add("Mensile");
                cmbFrequenza.SelectedIndex = 0;

                // Configura ComboBox giorni
                cmbGiorno.Items.Clear();
                cmbGiorno.Items.Add("Lunedì");
                cmbGiorno.Items.Add("Martedì");
                cmbGiorno.Items.Add("Mercoledì");
                cmbGiorno.Items.Add("Giovedì");
                cmbGiorno.Items.Add("Venerdì");
                cmbGiorno.Items.Add("Sabato");
                cmbGiorno.Items.Add("Domenica");

                // Imposta giorno in base alla data selezionata
                int dayOfWeek = ((int)dtpDataInizio.Value.DayOfWeek + 6) % 7; // Converte 0=domenica in 0=lunedì
                if (dayOfWeek >= 0 && dayOfWeek < cmbGiorno.Items.Count)
                    cmbGiorno.SelectedIndex = dayOfWeek;
                else
                    cmbGiorno.SelectedIndex = 0;

                // Configura il pannello per l'anteprima lezioni
                panelAnteprimaLezioni = new Panel();
                panelAnteprimaLezioni.Location = new Point(20, 300);
                panelAnteprimaLezioni.Size = new Size(500, 120);
                panelAnteprimaLezioni.AutoScroll = true;
                panelMain.Controls.Add(panelAnteprimaLezioni);

                // Aggiorna lista acquisti da pianificare
                UpdateAcquistiDaPianificareList();

                // Se c'è un acquisto preselezionato, configura i valori in base al pacchetto
                if (acquistoPreselezionato != null)
                {
                    ConfigureFromAcquisto(acquistoPreselezionato);
                    btnPianifica.Enabled = true;

                    // Mostra anteprima della pianificazione
                    ShowPianificazioneAnteprima();
                }
                else
                {
                    btnPianifica.Enabled = false;
                }

                // Aggiungi handler per aggiornare l'anteprima
                dtpDataInizio.ValueChanged += (s, e) => ShowPianificazioneAnteprima();
                cmbFrequenza.SelectedIndexChanged += (s, e) => ShowPianificazioneAnteprima();
                nudNumeroSettimane.ValueChanged += (s, e) => ShowPianificazioneAnteprima();
                cmbGiorno.SelectedIndexChanged += (s, e) => AdjustDateBasedOnSelectedDay();
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in ConfigureForm: {ex.Message}");
            }
        }

        private void cmbStrumento_SelectedIndexChanged(object sender, EventArgs e)
        {
            try
            {
                string strumentoSelezionato = cmbStrumento.SelectedItem?.ToString();
                PopulateInsegnantiComboBox(strumentoSelezionato);
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in cmbStrumento_SelectedIndexChanged: {ex.Message}");
            }
        }

        private void PopulateInsegnantiComboBox(string strumentoSelezionato = null)
        {
            try
            {
                // Salva l'elemento selezionato corrente, se presente
                object selectedItem = cmbInsegnante.SelectedItem;
                int selectedValue = -1;
                if (selectedItem is Models.ModelComboBoxItem item)
                {
                    selectedValue = (int)item.Value;
                }

                // Pulisci il combobox
                cmbInsegnante.Items.Clear();

                // Aggiungi sempre l'opzione "Insegnante più libero"
                cmbInsegnante.Items.Add(new Models.ModelComboBoxItem { Value = -1, Text = "Insegnante più libero" });

                if (insegnanti?.Items != null)
                {
                    // Filtra gli insegnanti in base allo strumento selezionato
                    var insegnantiFiltrati = insegnanti.Items;

                    if (!string.IsNullOrEmpty(strumentoSelezionato) && strumentoSelezionato != "Automatico (in base al pacchetto)")
                    {
                        // Filtra solo gli insegnanti che hanno questo strumento
                        insegnantiFiltrati = insegnanti.Items
                            .Where(i => i.Strumenti != null && i.Strumenti.Contains(strumentoSelezionato))
                            .ToList();

                        Debug.WriteLine($"Filtro insegnanti per strumento: {strumentoSelezionato}, trovati {insegnantiFiltrati.Count()} insegnanti");
                    }

                    // Aggiungi gli insegnanti al combobox
                    foreach (var insegnante in insegnantiFiltrati.OrderBy(i => i.Cognome).ThenBy(i => i.Nome))
                    {
                        cmbInsegnante.Items.Add(new Models.ModelComboBoxItem
                        {
                            Value = insegnante.Id,
                            Text = $"{insegnante.Cognome} {insegnante.Nome}"
                        });
                    }
                }

                // Imposta le proprietà del combobox
                cmbInsegnante.DisplayMember = "Text";
                cmbInsegnante.ValueMember = "Value";

                // Ripristina la selezione precedente se esiste ancora
                bool selectionFound = false;
                if (selectedValue != -1)
                {
                    for (int i = 0; i < cmbInsegnante.Items.Count; i++)
                    {
                        if (cmbInsegnante.Items[i] is Models.ModelComboBoxItem itemToCheck &&
                            (int)itemToCheck.Value == selectedValue)
                        {
                            cmbInsegnante.SelectedIndex = i;
                            selectionFound = true;
                            break;
                        }
                    }
                }

                // Se la selezione precedente non è stata trovata, seleziona il primo elemento
                if (!selectionFound || cmbInsegnante.SelectedIndex == -1)
                {
                    cmbInsegnante.SelectedIndex = 0;
                }
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in PopulateInsegnantiComboBox: {ex.Message}");

                // In caso di errore, ripristina una configurazione predefinita
                cmbInsegnante.Items.Clear();
                cmbInsegnante.Items.Add(new Models.ModelComboBoxItem { Value = -1, Text = "Insegnante più libero" });
                cmbInsegnante.DisplayMember = "Text";
                cmbInsegnante.ValueMember = "Value";
                cmbInsegnante.SelectedIndex = 0;
            }
        }

        private void DtpOraInizio_ValueChanged(object sender, EventArgs e)
        {
            oraForzata = dtpOraInizio.Value.Hour;
            minutiForzati = dtpOraInizio.Value.Minute;
            ShowPianificazioneAnteprima();
        }

        private void AdjustDateBasedOnSelectedDay()
        {
            try
            {
                // Ottieni il giorno della settimana selezionato
                int selectedDayIndex = cmbGiorno.SelectedIndex;
                if (selectedDayIndex < 0) return;

                // Converti l'indice del giorno in DayOfWeek (0 = lunedì, 6 = domenica)
                // DayOfWeek invece è (0 = domenica, 6 = sabato)
                DayOfWeek targetDay = (DayOfWeek)((selectedDayIndex + 1) % 7);

                // Calcola quanti giorni aggiungere alla data attuale
                DateTime currentDate = dtpDataInizio.Value;
                int currentDayOfWeek = (int)currentDate.DayOfWeek;
                int targetDayOfWeek = (int)targetDay;

                // Calcola la differenza di giorni
                int daysToAdd = (targetDayOfWeek - currentDayOfWeek + 7) % 7;

                // Se la differenza è 0, significa che siamo già nel giorno giusto
                if (daysToAdd == 0) return;

                // Aggiorna la data
                dtpDataInizio.Value = currentDate.AddDays(daysToAdd);

                ShowPianificazioneAnteprima();
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in AdjustDateBasedOnSelectedDay: {ex.Message}");
            }
        }

        private void ConfigureFromAcquisto(Models.Acquisto acquisto)
        {
            try
            {
                if (acquisto == null) return;

                // Trova il pacchetto associato all'acquisto
                var pacchetto = pacchetti?.Items?.FirstOrDefault(p => p.Id == acquisto.PacchettoId);
                if (pacchetto != null)
                {
                    // Imposta numero lezioni
                    nudNumeroSettimane.Value = pacchetto.NumeroLezioni;

                    // Imposta durata
                    nudDurata.Value = pacchetto.DurataMinuti;

                    // Imposta frequenza
                    switch (pacchetto.Frequenza?.ToLower())
                    {
                        case "settimanale":
                            cmbFrequenza.SelectedIndex = 0;
                            break;
                        case "bisettimanale":
                            cmbFrequenza.SelectedIndex = 1;
                            break;
                        case "mensile":
                            cmbFrequenza.SelectedIndex = 2;
                            break;
                        default:
                            cmbFrequenza.SelectedIndex = 0;
                            break;
                    }

                    // Imposta strumento
                    if (!string.IsNullOrEmpty(pacchetto.Strumento))
                    {
                        for (int i = 0; i < cmbStrumento.Items.Count; i++)
                        {
                            if (cmbStrumento.Items[i].ToString() == pacchetto.Strumento)
                            {
                                cmbStrumento.SelectedIndex = i;
                                break;
                            }
                        }
                    }

                    // Il filtro insegnanti avviene automaticamente tramite l'event handler del cmbStrumento
                    // Ma nel caso ciò non avvenisse, forziamo il filtro anche qui
                    string strumentoSelezionato = cmbStrumento.SelectedItem?.ToString();
                    PopulateInsegnantiComboBox(strumentoSelezionato);

                    // Dopo aver filtrato gli insegnanti, troviamo uno con lo strumento giusto
                    if (!string.IsNullOrEmpty(pacchetto.Strumento))
                    {
                        // Cerca tra gli insegnanti disponibili
                        for (int i = 0; i < cmbInsegnante.Items.Count; i++)
                        {
                            if (cmbInsegnante.Items[i] is Models.ModelComboBoxItem itemBox &&
                                (int)itemBox.Value > 0) // Salta "Insegnante più libero"
                            {
                                var insegnante = insegnanti.Items.FirstOrDefault(ins => ins.Id == (int)itemBox.Value);
                                if (insegnante != null &&
                                    insegnante.Strumenti != null &&
                                    insegnante.Strumenti.Contains(pacchetto.Strumento))
                                {
                                    cmbInsegnante.SelectedIndex = i;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in ConfigureFromAcquisto: {ex.Message}");
            }
        }

        private void UpdateAcquistiDaPianificareList()
        {
            try
            {
                lstAcquistiDaPianificare.Items.Clear();

                foreach (var acquisto in acquistiDaPianificare)
                {
                    // Trova il cliente
                    var cliente = clienti?.Items?.FirstOrDefault(c => c.Id == acquisto.ClienteId);
                    if (cliente == null) continue;

                    // Trova il pacchetto
                    string pachettoNome = !string.IsNullOrEmpty(acquisto.NomePacchetto)
                        ? acquisto.NomePacchetto
                        : "Pacchetto sconosciuto";

                    if (string.IsNullOrEmpty(acquisto.NomePacchetto) && acquisto.PacchettoId > 0)
                    {
                        var pacchetto = pacchetti?.Items?.FirstOrDefault(p => p.Id == acquisto.PacchettoId);
                        if (pacchetto != null)
                        {
                            pachettoNome = pacchetto.Nome;
                        }
                    }

                    string nomeCliente = $"{cliente.Cognome} {cliente.Nome}";
                    string displayText = $"{nomeCliente} - {pachettoNome} - {acquisto.NumeroLezioni} lezioni";

                    ListViewItem item = new ListViewItem(displayText);
                    item.Tag = acquisto;

                    lstAcquistiDaPianificare.Items.Add(item);
                }
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in UpdateAcquistiDaPianificareList: {ex.Message}");
            }
        }

        private void ShowPianificazioneAnteprima()
        {
            try
            {
                // Pulisci le labels esistenti
                foreach (var label in lezioniLabels)
                {
                    panelAnteprimaLezioni.Controls.Remove(label);
                    label.Dispose();
                }
                lezioniLabels.Clear();

                // Se non ci sono acquisti da pianificare, esci
                if (acquistiDaPianificare == null || acquistiDaPianificare.Count == 0)
                    return;

                // Genera le date di pianificazione
                List<DateTime> dateLezioni = GeneraDateLezioni();

                // Aggiungi titolo
                Label titleLabel = new Label
                {
                    Text = "Anteprima lezioni:",
                    Font = new Font("Segoe UI", 12F, FontStyle.Bold),
                    Location = new Point(0, 0),
                    Size = new Size(200, 20),
                    AutoSize = true
                };
                panelAnteprimaLezioni.Controls.Add(titleLabel);
                lezioniLabels.Add(titleLabel);

                // Mostra anteprima delle lezioni (limita a 7 per non occupare troppo spazio)
                int yPos = 25;
                int maxToShow = Math.Min(dateLezioni.Count, 7);

                for (int i = 0; i < maxToShow; i++)
                {
                    Label lezioneLabel = new Label
                    {
                        Text = $"Lezione {i + 1}: {dateLezioni[i]:dd/MM/yyyy}, {oraForzata:D2}:{minutiForzati:D2}, {GetItalianDayName(dateLezioni[i].DayOfWeek)}",
                        Font = new Font("Segoe UI", 11F),
                        ForeColor = Color.FromArgb(64, 64, 64),
                        Location = new Point(10, yPos),
                        Size = new Size(400, 20),
                        AutoSize = true
                    };

                    panelAnteprimaLezioni.Controls.Add(lezioneLabel);
                    lezioniLabels.Add(lezioneLabel);

                    yPos += 20;
                }

                // Se ci sono più lezioni di quelle mostrate, aggiungi un'indicazione
                if (dateLezioni.Count > maxToShow)
                {
                    Label moreLabel = new Label
                    {
                        Text = $"... e altre {dateLezioni.Count - maxToShow} lezioni",
                        Font = new Font("Segoe UI", 9F, FontStyle.Italic),
                        ForeColor = Color.FromArgb(100, 100, 100),
                        Location = new Point(10, yPos),
                        Size = new Size(400, 20),
                        AutoSize = true
                    };

                    panelAnteprimaLezioni.Controls.Add(moreLabel);
                    lezioniLabels.Add(moreLabel);
                }
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in ShowPianificazioneAnteprima: {ex.Message}");
            }
        }

        #endregion

        #region Pianificazione e Utility

        private List<DateTime> GeneraDateLezioni()
        {
            List<DateTime> dateLezioni = new List<DateTime>();

            try
            {
                // Usa la data selezionata come punto di partenza
                DateTime dataInizio = dtpDataInizio.Value.Date;
                dateLezioni.Add(dataInizio);

                // Ottieni parametri
                string frequenza = cmbFrequenza.SelectedItem?.ToString() ?? "Settimanale";
                int numeroLezioni = (int)nudNumeroSettimane.Value;

                // Genera le date successive
                DateTime currentDate = dataInizio;

                for (int i = 1; i < numeroLezioni; i++)
                {
                    // Calcola la prossima data in base alla frequenza
                    switch (frequenza)
                    {
                        case "Settimanale":
                            currentDate = currentDate.AddDays(7);
                            break;
                        case "Bisettimanale":
                            currentDate = currentDate.AddDays(14);
                            break;
                        case "Mensile":
                            currentDate = currentDate.AddMonths(1);
                            break;
                        default:
                            currentDate = currentDate.AddDays(7);
                            break;
                    }

                    dateLezioni.Add(currentDate);
                }
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in GeneraDateLezioni: {ex.Message}");
            }

            return dateLezioni;
        }

        private void btnPianifica_Click(object sender, EventArgs e)
        {
            try
            {
                // Verifica che ci siano acquisti da pianificare
                if (acquistiDaPianificare == null || acquistiDaPianificare.Count == 0)
                {
                    MessageBox.Show("Nessun acquisto da pianificare.",
                        "Pianificazione", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                    return;
                }

                // Ottieni i parametri di pianificazione
                DateTime dataInizioSelezionata = dtpDataInizio.Value.Date;
                TimeSpan oraInizio = new TimeSpan(oraForzata, minutiForzati, 0);
                int durataMinuti = (int)nudDurata.Value;
                TimeSpan durata = new TimeSpan(0, durataMinuti, 0);
                int numeroSettimane = (int)nudNumeroSettimane.Value;
                string frequenza = cmbFrequenza.SelectedItem?.ToString() ?? "Settimanale";

                // Genera le date delle lezioni
                List<DateTime> dateLezioni = GeneraDateLezioni();

                // Ottieni l'insegnante selezionato
                int insegnanteId = -1;
                if (cmbInsegnante.SelectedItem is Models.ModelComboBoxItem insegnanteItem)
                {
                    insegnanteId = (int)insegnanteItem.Value;
                }

                // Ottieni lo strumento selezionato
                string strumento = cmbStrumento.SelectedItem?.ToString();
                if (strumento == "Automatico (in base al pacchetto)")
                {
                    strumento = null;
                }

                // Verifica che le prenotazioni siano inizializzate
                if (prenotazioni == null) prenotazioni = new PrenotazioniList();
                if (prenotazioni.Items == null) prenotazioni.Items = new List<Prenotazione>();

                // Raggruppa gli acquisti per cliente
                var acquistiPerCliente = acquistiDaPianificare.GroupBy(a => a.ClienteId)
                                                           .ToDictionary(g => g.Key, g => g.ToList());

                // Statistiche per il messaggio finale
                int totaleLezionePianificate = 0;
                int totaleAcquistiCompletati = 0;

                foreach (var clienteId in acquistiPerCliente.Keys)
                {
                    // Ottieni gli acquisti del cliente
                    var acquistiCliente = acquistiPerCliente[clienteId];

                    // Calcola il numero totale di lezioni da pianificare per questo cliente
                    int numeroLezioniTotali = acquistiCliente.Sum(a => a.NumeroLezioni);
                    numeroLezioniTotali = Math.Min(numeroLezioniTotali, dateLezioni.Count);

                    // Pianifica le lezioni
                    int lezioniPianificate = 0;
                    int acquistiCompletati = 0;

                    foreach (var acquisto in acquistiCliente)
                    {
                        // Ottieni i dettagli dal pacchetto
                        var pacchetto = pacchetti?.Items?.FirstOrDefault(p => p.Id == acquisto.PacchettoId);
                        int lezioniRimanenti = acquisto.NumeroLezioni;
                        int lezioniAcquisto = 0;

                        // Ottieni strumento e durata dal pacchetto se disponibili
                        string strumentoPerAcquisto = strumento;
                        TimeSpan durataPerAcquisto = durata;

                        if (pacchetto != null)
                        {
                            if (string.IsNullOrEmpty(strumentoPerAcquisto) && !string.IsNullOrEmpty(pacchetto.Strumento))
                            {
                                strumentoPerAcquisto = pacchetto.Strumento;
                            }
                            if (pacchetto.DurataMinuti > 0 && pacchetto.DurataMinuti != durataMinuti)
                            {
                                durataPerAcquisto = new TimeSpan(0, pacchetto.DurataMinuti, 0);
                            }
                        }

                        // Trova l'insegnante appropriato
                        int insegnantePerAcquisto = insegnanteId;
                        if (insegnantePerAcquisto == -1)
                        {
                            // Cerca insegnante in base allo strumento
                            if (!string.IsNullOrEmpty(strumentoPerAcquisto))
                            {
                                var insegnantiConStrumento = insegnanti.Items
                                    .Where(i => i.Strumenti != null &&
                                           i.Strumenti.Contains(strumentoPerAcquisto))
                                    .ToList();

                                if (insegnantiConStrumento.Any())
                                {
                                    // Trova l'insegnante più libero
                                    insegnantePerAcquisto = TrovaInsegnanteMenoOccupato(
                                        insegnantiConStrumento.Select(i => i.Id).ToList(),
                                        dateLezioni, oraInizio, durataPerAcquisto);
                                }
                                else
                                {
                                    insegnantePerAcquisto = TrovaInsegnanteMenoOccupato(
                                        null, dateLezioni, oraInizio, durataPerAcquisto);
                                }
                            }
                            else
                            {
                                insegnantePerAcquisto = TrovaInsegnanteMenoOccupato(
                                    null, dateLezioni, oraInizio, durataPerAcquisto);
                            }
                        }

                        // Se non ci sono insegnanti disponibili, mostra un messaggio
                        if (insegnantePerAcquisto <= 0)
                        {
                            var cliente = clienti.Items.FirstOrDefault(c => c.Id == clienteId);
                            string nomeCliente = cliente != null ? $"{cliente.Cognome} {cliente.Nome}" : $"Cliente ID: {clienteId}";

                            MessageBox.Show($"Non ci sono insegnanti disponibili per {nomeCliente}.",
                                "Pianificazione", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                            continue;
                        }

                        // Se lo strumento non è specificato, usa quello dell'insegnante
                        if (string.IsNullOrEmpty(strumentoPerAcquisto))
                        {
                            var insegnante = insegnanti.Items.FirstOrDefault(i => i.Id == insegnantePerAcquisto);
                            if (insegnante != null && insegnante.Strumenti != null && insegnante.Strumenti.Count > 0)
                            {
                                strumentoPerAcquisto = insegnante.Strumenti[0];
                            }
                            else
                            {
                                strumentoPerAcquisto = "Non specificato";
                            }
                        }

                        // Pianifica le lezioni per questo acquisto
                        for (int i = 0; i < lezioniRimanenti && lezioniPianificate < numeroLezioniTotali && lezioniPianificate < dateLezioni.Count; i++)
                        {
                            // Ottieni la data della lezione
                            DateTime dataLezioneCorrente = dateLezioni[lezioniPianificate];

                            // Usa l'ora selezionata dall'utente
                            TimeSpan oraInizioLezione = oraInizio;

                            // Calcola l'ora di fine - CORREZIONE
                            int totaleMinuti = oraInizioLezione.Hours * 60 + oraInizioLezione.Minutes +
                                               (durataPerAcquisto.Hours * 60 + durataPerAcquisto.Minutes);
                            int oreFine = totaleMinuti / 60;
                            int minutiFine = totaleMinuti % 60;

                            // Normalizza se supera le 24 ore
                            if (oreFine >= 24) oreFine -= 24;

                            TimeSpan oraFineLezione = new TimeSpan(oreFine, minutiFine, 0);

                            // Verifica che l'insegnante sia disponibile
                            DateTime dataOraLezione = dataLezioneCorrente.Date.Add(oraInizioLezione);
                            if (!IsInsegnanteDisponibile(insegnantePerAcquisto, dataOraLezione, durataPerAcquisto))
                            {
                                // Se l'insegnante non è disponibile, mostra un avviso
                                var cliente = clienti.Items.FirstOrDefault(c => c.Id == clienteId);
                                string nomeCliente = cliente != null ? $"{cliente.Cognome} {cliente.Nome}" : $"Cliente ID: {clienteId}";
                                string insegnanteNome = "Sconosciuto";
                                var insegnante = insegnanti.Items.FirstOrDefault(ins => ins.Id == insegnantePerAcquisto);
                                if (insegnante != null)
                                {
                                    insegnanteNome = $"{insegnante.Cognome} {insegnante.Nome}";
                                }

                                MessageBox.Show($"L'insegnante {insegnanteNome} non è disponibile per la lezione del {dataLezioneCorrente:dd/MM/yyyy} alle {oraInizioLezione.Hours:D2}:{oraInizioLezione.Minutes:D2} per {nomeCliente}.",
                                    "Pianificazione", MessageBoxButtons.OK, MessageBoxIcon.Warning);
                                continue;
                            }

                            // Crea la prenotazione con orari corretti
                            Prenotazione nuovaLezione = new Prenotazione
                            {
                                Id = GetNextPrenotazioneId(),
                                ClienteId = clienteId,
                                InsegnanteId = insegnantePerAcquisto,
                                Data = dataLezioneCorrente.Date,
                                OraInizioStr = $"{oraInizioLezione.Hours:D2}:{oraInizioLezione.Minutes:D2}",
                                OraFineStr = $"{oraFineLezione.Hours:D2}:{oraFineLezione.Minutes:D2}",
                                Strumento = strumentoPerAcquisto,
                                Stato = StatoLezione.Programmata,
                                AcquistoId = acquisto.Id,
                                PacchettoNome = acquisto.NomePacchetto
                            };

                            // Verifica che gli orari non siano vuoti
                            if (string.IsNullOrEmpty(nuovaLezione.OraInizioStr) || nuovaLezione.OraInizioStr == "00:00")
                            {
                                Debug.WriteLine("ERRORE: La prenotazione ha orari a zero!");
                                continue;
                            }

                            // Aggiungi alla lista prenotazioni
                            prenotazioni.Items.Add(nuovaLezione);
                            Debug.WriteLine($"Lezione aggiunta: {dataLezioneCorrente:dd/MM/yyyy} {nuovaLezione.OraInizioStr}");

                            // Incrementa i contatori
                            lezioniPianificate++;
                            lezioniAcquisto++;
                        }

                        // Aggiorna lo stato dell'acquisto
                        if (lezioniAcquisto == acquisto.NumeroLezioni)
                        {
                            acquisto.Pianificato = true;
                            acquistiCompletati++;
                        }
                    }

                    // Aggiorna i contatori totali
                    totaleLezionePianificate += lezioniPianificate;
                    totaleAcquistiCompletati += acquistiCompletati;
                }

                // Salva le modifiche
                try
                {
                    // Attiva la soppressione degli errori durante il salvataggio
                    suppressMessageBoxes = true;

                    // Salva le prenotazioni
                    try
                    {
                        // Salva il file prenotazioni
                        MainForm.SaveEncryptedXml(prenotazioni, prenotazioniFilePath);
                        Debug.WriteLine($"Prenotazioni salvate: {prenotazioni.Items.Count} totali");
                    }
                    catch (Exception ex)
                    {
                        Debug.WriteLine($"Errore nel salvataggio prenotazioni: {ex.Message}");

                        // Tenta il salvataggio diretto XML come fallback
                        try
                        {
                            using (var writer = new StreamWriter(prenotazioniFilePath))
                            {
                                var serializer = new XmlSerializer(typeof(PrenotazioniList));
                                serializer.Serialize(writer, prenotazioni);
                            }
                            Debug.WriteLine("Prenotazioni salvate con metodo alternativo");
                        }
                        catch (Exception ex2)
                        {
                            Debug.WriteLine($"Anche il salvataggio alternativo è fallito: {ex2.Message}");
                        }
                    }

                    // Aggiorna e salva gli acquisti
                    try
                    {
                        foreach (var acquisto in acquistiDaPianificare.Where(a => a.Pianificato))
                        {
                            var acquistoOriginale = acquisti.Items.FirstOrDefault(a => a.Id == acquisto.Id);
                            if (acquistoOriginale != null)
                            {
                                acquistoOriginale.Pianificato = true;
                            }
                        }

                        MainForm.SaveEncryptedXml(acquisti, acquistiFilePath);
                        Debug.WriteLine("Acquisti aggiornati e salvati");
                    }
                    catch (Exception ex)
                    {
                        Debug.WriteLine($"Errore nel salvataggio acquisti: {ex.Message}");
                    }
                }
                catch (Exception ex)
                {
                    Debug.WriteLine($"Errore durante il salvataggio: {ex.Message}");
                }
                finally
                {
                    // Disattiva la soppressione degli errori
                    suppressMessageBoxes = false;
                }

                // Mostra messaggio di completamento
                MessageBox.Show($"Pianificazione completata!\n\nLezioni pianificate: {totaleLezionePianificate}\nAcquisti completati: {totaleAcquistiCompletati}",
                    "Pianificazione", MessageBoxButtons.OK, MessageBoxIcon.Information);

                // Chiudi il form con successo
                this.DialogResult = DialogResult.OK;
                this.Close();
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in btnPianifica_Click: {ex.Message}");
                MessageBox.Show($"Si è verificato un errore durante la pianificazione: {ex.Message}",
                    "Errore", MessageBoxButtons.OK, MessageBoxIcon.Error);
            }
        }

        private int GetNextPrenotazioneId()
        {
            return prenotazioni?.Items?.Any() == true ? prenotazioni.Items.Max(p => p.Id) + 1 : 1;
        }

        private bool IsInsegnanteDisponibile(int insegnanteId, DateTime dataOra, TimeSpan durata)
        {
            try
            {
                // Verifica che l'insegnante esista
                if (!insegnanti.Items.Any(i => i.Id == insegnanteId))
                    return false;

                // Calcola l'orario di fine lezione
                DateTime fineLezione = dataOra.Add(durata);

                // Verifica che non ci siano altre lezioni nello stesso orario per questo insegnante
                foreach (var p in prenotazioni.Items)
                {
                    if (p.InsegnanteId == insegnanteId && p.Data.Date == dataOra.Date)
                    {
                        // Converti gli orari stringa in TimeSpan
                        TimeSpan pOraInizio = ParseOrario(p.OraInizioStr);
                        TimeSpan pOraFine = ParseOrario(p.OraFineStr);

                        // Verifica sovrapposizione
                        if ((pOraInizio < dataOra.TimeOfDay.Add(durata) && pOraFine > dataOra.TimeOfDay) ||
                            (dataOra.TimeOfDay < pOraFine && dataOra.TimeOfDay.Add(durata) > pOraInizio))
                        {
                            return false; // C'è sovrapposizione
                        }
                    }
                }

                return true; // Nessuna sovrapposizione trovata
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in IsInsegnanteDisponibile: {ex.Message}");
                return false; // In caso di errore, meglio essere conservativi
            }
        }

        private TimeSpan ParseOrario(string orarioStr)
        {
            if (string.IsNullOrEmpty(orarioStr)) return TimeSpan.Zero;

            if (TimeSpan.TryParse(orarioStr, out TimeSpan result))
                return result;

            // Gestione formato alternativo (hh:mm)
            string[] parts = orarioStr.Split(':');
            if (parts.Length == 2 &&
                int.TryParse(parts[0], out int hours) &&
                int.TryParse(parts[1], out int minutes))
            {
                return new TimeSpan(hours, minutes, 0);
            }

            return TimeSpan.Zero;
        }

        private int TrovaInsegnanteMenoOccupato(List<int> insegnantiIds, List<DateTime> date, TimeSpan ora, TimeSpan durata)
        {
            try
            {
                // Ottieni la lista di insegnanti da considerare
                var insegnantiAttivi = insegnantiIds != null
                    ? insegnanti.Items.Where(i => insegnantiIds.Contains(i.Id)).ToList()
                    : insegnanti.Items.ToList();

                if (!insegnantiAttivi.Any())
                {
                    return 0; // Nessun insegnante disponibile
                }

                // Dizionario per contare le disponibilità
                var disponibilitaInsegnanti = new Dictionary<int, int>();
                foreach (var insegnante in insegnantiAttivi)
                {
                    disponibilitaInsegnanti[insegnante.Id] = 0;
                }

                // Conta le disponibilità per ogni insegnante in tutte le date
                foreach (var insegnante in insegnantiAttivi)
                {
                    foreach (var data in date)
                    {
                        DateTime dataOra = data.Date.Add(ora);
                        if (IsInsegnanteDisponibile(insegnante.Id, dataOra, durata))
                        {
                            disponibilitaInsegnanti[insegnante.Id]++;
                        }
                    }
                }

                // Trova l'insegnante con più disponibilità
                var insegnanteMigliore = disponibilitaInsegnanti
                    .OrderByDescending(kv => kv.Value)
                    .FirstOrDefault();

                return insegnanteMigliore.Value > 0 ? insegnanteMigliore.Key : 0;
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"Errore in TrovaInsegnanteMenoOccupato: {ex.Message}");
                return 0;
            }
        }

        private string GetItalianDayName(DayOfWeek day)
        {
            switch (day)
            {
                case DayOfWeek.Monday: return "Lunedì";
                case DayOfWeek.Tuesday: return "Martedì";
                case DayOfWeek.Wednesday: return "Mercoledì";
                case DayOfWeek.Thursday: return "Giovedì";
                case DayOfWeek.Friday: return "Venerdì";
                case DayOfWeek.Saturday: return "Sabato";
                case DayOfWeek.Sunday: return "Domenica";
                default: return day.ToString();
            }
        }

        private void btnChiudi_Click(object sender, EventArgs e)
        {
            this.DialogResult = DialogResult.Cancel;
            this.Close();
        }

        #endregion
    }
}