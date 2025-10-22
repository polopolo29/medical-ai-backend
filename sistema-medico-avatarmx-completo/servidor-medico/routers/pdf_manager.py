from fastapi import APIRouter, UploadFile, File, HTTPException
from utils.pdf_processor import PDFProcessor
import shutil
import os

router = APIRouter()

@router.get("/estado")
async def get_pdf_estado():
    """
    Obtiene el estado de los PDFs cargados en el sistema.
    """
    pdf_processor = PDFProcessor()
    return pdf_processor.obtener_estadisticas()

@router.post("/cargar-pdf")
async def cargar_pdf(file: UploadFile = File(...)):
    """
    Sube un nuevo PDF de protocolo al sistema.
    """

    # Asegúrate de que el directorio de PDFs exista
    pdf_dir = os.getenv("PDF_PROTOCOLS_PATH", "pdfs_medicos")
    if not os.path.exists(pdf_dir):
        os.makedirs(pdf_dir)

    file_path = os.path.join(pdf_dir, file.filename)

    # Guardar el archivo subido
    with open(file_path, "wb") as buffer:
        shutil.copyfileobj(file.file, buffer)

    return {"filename": file.filename, "message": "PDF cargado exitosamente"}
