from fastapi import APIRouter
import os

router = APIRouter()

PDF_PROTOCOLS_PATH = os.getenv("PDF_PROTOCOLS_PATH", "./pdfs_medicos")

@router.get("/estado")
async def get_pdf_status():
    """Devuelve el estado de los PDFs cargados."""
    if not os.path.exists(PDF_PROTOCOLS_PATH):
        os.makedirs(PDF_PROTOCOLS_PATH)

    pdfs = [f for f in os.listdir(PDF_PROTOCOLS_PATH) if f.endswith(".pdf")]
    return {
        "pdfs_cargados": len(pdfs),
        "protocolos_disponibles": pdfs
    }

@router.post("/procesar")
async def process_pdf():
    """Procesa un PDF manually."""
    return {"message": "Endpoint para procesar PDF"}
