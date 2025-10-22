from reportlab.pdfgen import canvas
from reportlab.lib.pagesizes import letter
from reportlab.lib.styles import getSampleStyleSheet
from reportlab.platypus import Paragraph, SimpleDocTemplate
import os

def create_pdf_from_txt(txt_path, pdf_path):
    """
    Crea un archivo PDF a partir de un archivo de texto.
    """
    with open(txt_path, 'r', encoding='utf-8') as f:
        content = f.read()

    doc = SimpleDocTemplate(pdf_path, pagesize=letter)
    styles = getSampleStyleSheet()
    story = [Paragraph(line.replace(' ', '&nbsp;'), styles['Normal']) for line in content.split('\n')]

    doc.build(story)
    print(f"PDF creado exitosamente en {pdf_path}")

if __name__ == "__main__":
    txt_file = os.path.join("sistema-medico-avatarmx-completo", "servidor-medico", "data", "conocimiento_medico.txt")
    pdf_dir = os.path.join("sistema-medico-avatarmx-completo", "servidor-medico", "pdfs_medicos")

    if not os.path.exists(pdf_dir):
        os.makedirs(pdf_dir)

    pdf_file = os.path.join(pdf_dir, "conocimiento_medico.pdf")

    create_pdf_from_txt(txt_file, pdf_file)
