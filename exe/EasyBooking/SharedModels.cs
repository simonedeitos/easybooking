using System;
using System.Collections.Generic;
using System.Xml.Serialization;

namespace EasyBooking.Models
{
    [XmlRoot("Pacchetti")]
    public class PacchettiList
    {
        [XmlElement("Pacchetto")]
        public List<Pacchetto> Items { get; set; }

        public PacchettiList()
        {
            Items = new List<Pacchetto>();
        }
    }

    [Serializable]
    public class Pacchetto
    {
        public int Id { get; set; }
        public string Nome { get; set; }
        public string Descrizione { get; set; }
        public int NumeroLezioni { get; set; }
        public int DurataMinuti { get; set; }
        public string Frequenza { get; set; }
        public decimal Prezzo { get; set; }
        public string Strumento { get; set; }
    }

    [XmlRoot("Acquisti")]
    public class AcquistiList
    {
        [XmlElement("Acquisto")]
        public List<Acquisto> Items { get; set; }

        public AcquistiList()
        {
            Items = new List<Acquisto>();
        }
    }

    [Serializable]
    public class Acquisto
    {
        public int Id { get; set; }
        public DateTime DataAcquisto { get; set; }
        public int ClienteId { get; set; }
        public int PacchettoId { get; set; }
        public decimal ImportoPagato { get; set; }
        public string StatoPagamento { get; set; }
        public bool Pianificato { get; set; }
        public string NumeroFattura { get; set; }
        public string Note { get; set; }

        // Campi per visualizzazione in griglia (non serializzati)
        [XmlIgnore]
        public string NomeCliente { get; set; }

        [XmlIgnore]
        public string NomePacchetto { get; set; }

        // Aggiunto per la compatibilità con PianificazioneForm
        public int NumeroLezioni { get; set; }
    }

    public class ModelComboBoxItem
    {
        public object Value { get; set; }
        public string Text { get; set; }

        public override string ToString()
        {
            return Text;
        }
    }

    public class ModelImpostazioniGenerali
    {
        // Giorni lavorativi
        public bool LunAttivo { get; set; } = true;
        public bool MarAttivo { get; set; } = true;
        public bool MerAttivo { get; set; } = true;
        public bool GioAttivo { get; set; } = true;
        public bool VenAttivo { get; set; } = true;
        public bool SabAttivo { get; set; } = false;
        public bool DomAttivo { get; set; } = false;

        // Orari di lavoro
        public int MattInizio { get; set; } = 9;
        public int MattFine { get; set; } = 13;
        public int PomInizio { get; set; } = 15;
        public int PomFine { get; set; } = 19;

        // Altre impostazioni
        public int DurataLezioneDefault { get; set; } = 60;
    }
}