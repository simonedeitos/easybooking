using System;
using System.Collections.Generic;
using System.Xml.Serialization;

namespace EasyBooking.AcquistiModels
{
    [XmlRoot("Pacchetti")]
    public class AcquistiPacchettiList
    {
        [XmlElement("Pacchetto")]
        public List<AcquistiPacchetto> Items { get; set; }

        public AcquistiPacchettiList()
        {
            Items = new List<AcquistiPacchetto>();
        }
    }

    [Serializable]
    public class AcquistiPacchetto
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
}