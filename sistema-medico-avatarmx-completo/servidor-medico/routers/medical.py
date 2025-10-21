from fastapi import APIRouter, HTTPException, Depends
from sqlalchemy.orm import Session
from models.session import MedicalSession
from utils import database

router = APIRouter()

preguntas = [
    "Hola, soy tu asistente médico. ¿Cuál es tu nombre completo?",
    "Gracias, {nombre}. ¿Cuál es tu edad?",
    "¿Cuál es tu género?",
    "¿Cuál es tu estatura y peso en formato 'estatura-cm/peso-kg'?",
    "¿Cuál es tu nacionalidad?",
    "Describe tus síntomas iniciales.",
    "¿Cuál fue tu primer diagnóstico médico?",
    "¿Cuánto tiempo ha pasado desde el diagnóstico?",
    "Describe tus síntomas actuales.",
    "¿Consumes medicamentos?",
    "Si es así, ¿qué medicamentos específicos consumes?",
    "Describe tu dieta actual."
]

@router.post("/iniciar-conversacion")
async def iniciar_conversacion(user_id: int, db: Session = Depends(database.get_db)):
    """Inicia una nueva sesión de conversación médica."""
    sesion = MedicalSession(user_id=user_id)
    database.create_session(db, sesion)
    return {"session_id": sesion.session_id, "message": preguntas[0]}

@router.post("/procesar-respuesta")
async def procesar_respuesta(session_id: str, respuesta: str, db: Session = Depends(database.get_db)):
    """Procesa la respuesta del usuario y devuelve la siguiente pregunta."""
    sesion = database.get_session(db, session_id)
    if not sesion:
        raise HTTPException(status_code=404, detail="Sesión no encontrada")

    sesion.complete_question(sesion.current_question, respuesta)
    database.update_session(db, session_id, sesion)

    if not sesion.is_evaluation_complete():
        siguiente_pregunta = preguntas[sesion.current_question - 1]
        if "{nombre}" in siguiente_pregunta:
            siguiente_pregunta = siguiente_pregunta.format(nombre=sesion.patient_data.nombre)
        return {"message": siguiente_pregunta}
    else:
        sesion.complete_session()
        database.update_session(db, session_id, sesion)
        return {"message": "Gracias por tus respuestas. Hemos completado la evaluación. Ahora comenzaremos con el protocolo."}
