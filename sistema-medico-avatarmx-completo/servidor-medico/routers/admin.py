from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session
from utils import database
from models.orm import MedicalSessionORM

router = APIRouter()

@router.get("/estadisticas")
async def get_stats(db: Session = Depends(database.get_db)):
    """Devuelve estadísticas del sistema."""
    total_sessions = db.query(MedicalSessionORM).count()
    completed_sessions = db.query(MedicalSessionORM).filter(MedicalSessionORM.status == 'completed').count()
    return {
        "total_sessions": total_sessions,
        "completed_sessions": completed_sessions
    }

@router.post("/cargar-pdf")
async def upload_pdf():
    """Sube un nuevo PDF médico."""
    return {"message": "Endpoint para subir PDF"}
