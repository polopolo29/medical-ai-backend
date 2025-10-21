import os
import PyPDF2

PDF_PROTOCOLS_PATH = os.getenv("PDF_PROTOCOLS_PATH", "./pdfs_medicos")

class PDFProcessor:
    def __init__(self):
        if not os.path.exists(PDF_PROTOCOLS_PATH):
            os.makedirs(PDF_PROTOCOLS_PATH)
        self.pdfs = [f for f in os.listdir(PDF_PROTOCOLS_PATH) if f.endswith(".pdf")]

    def obtener_estadisticas(self):
        return {
            "pdfs_cargados": len(self.pdfs),
            "protocolos_disponibles": self.pdfs
        }

    def extract_text(self, filename: str) -> str:
        """Extrae texto de un archivo PDF."""
        filepath = os.path.join(PDF_PROTOCOLS_PATH, filename)
        if not os.path.exists(filepath):
            return ""

        text = ""
        with open(filepath, "rb") as f:
            reader = PyPDF2.PdfReader(f)
            for page in reader.pages:
                text += page.extract_text()
        return text
