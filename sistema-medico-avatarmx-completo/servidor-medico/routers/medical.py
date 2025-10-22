from fastapi import APIRouter, HTTPException, Depends
from typing import Dict, Any
from models.session import MedicalSession
from models.database_models import MedicalSessionDB
from data.preguntas import CUESTIONARIO
from utils.database import get_session
from utils.pdf_processor import PDFProcessor
from utils.email_handler import EmailHandler
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy.future import select
import logging
from datetime import datetime

router = APIRouter()
logger = logging.getLogger(__name__)

pdf_processor = PDFProcessor()
email_handler = EmailHandler()

@router.post("/iniciar-conversacion")
async def iniciar_conversacion(user_id: int, db: AsyncSession = Depends(get_session)):
    # ... (código sin cambios)
    session = MedicalSession(user_id=user_id, start_time=datetime.now(), last_activity=datetime.now())
    db_session = MedicalSessionDB(
        session_id=session.session_id, user_id=session.user_id, patient_data={},
        start_time=session.start_time, last_activity=session.last_activity
    )
    db.add(db_session)
    await db.commit()
    logger.info(f"Sesión {session.session_id} iniciada para el usuario {user_id}.")
    return {"session_id": session.session_id, "siguiente_pregunta": CUESTIONARIO[0]}

@router.post("/procesar-respuesta/{session_id}")
async def procesar_respuesta(session_id: str, response: Dict[str, Any], db: AsyncSession = Depends(get_session)):
    # ... (código sin cambios)
    answer = response.get("answer", "").strip()
    if not answer:
        raise HTTPException(status_code=400, detail="La respuesta no puede estar vacía.")

    result = await db.execute(select(MedicalSessionDB).filter(MedicalSessionDB.session_id == session_id))
    db_session = result.scalars().first()
    if not db_session:
        logger.error(f"Intento de procesar respuesta para sesión no existente: {session_id}")
        raise HTTPException(status_code=404, detail="Sesión no encontrada")

    current_question_num = db_session.current_question
    campo = CUESTIONARIO[current_question_num - 1]["campo"]

    logger.info(f"Sesión {session_id}: Recibida respuesta para la pregunta {current_question_num} ('{campo}').")

    patient_data = db_session.patient_data or {}
    patient_data[campo] = answer
    db_session.patient_data = patient_data
    db_session.current_question += 1
    db_session.last_activity = datetime.now()

    info_medicamentos = None
    if campo == "medicamentos_especificos" and answer.lower() not in ["no", "ninguno"]:
        medicamentos = [m.strip() for m in answer.split(',')]
        info_medicamentos = {}
        for med in medicamentos:
            info_medicamentos[med] = pdf_processor.buscar_efectos_secundarios(med)
        logger.info(f"Sesión {session_id}: Se investigaron los medicamentos: {', '.join(medicamentos)}")

    await db.commit()

    response_data = {"session_id": session_id}
    if info_medicamentos:
        response_data["info_medicamentos"] = info_medicamentos

    if db_session.current_question > len(CUESTIONARIO):
        logger.info(f"Sesión {session_id}: Cuestionario completado.")
        response_data.update({"message": "Cuestionario completado.", "siguiente_paso": 1})
    else:
        response_data["siguiente_pregunta"] = CUESTIONARIO[db_session.current_question - 1]

    return response_data

@router.post("/ejecutar-siguiente-paso/{session_id}")
async def ejecutar_siguiente_paso(session_id: str, db: AsyncSession = Depends(get_session)):
    result = await db.execute(select(MedicalSessionDB).filter(MedicalSessionDB.session_id == session_id))
    db_session = result.scalars().first()
    if not db_session:
        raise HTTPException(status_code=404, detail="Sesión no encontrada")

    paso_actual = db_session.current_step + 1
    logger.info(f"Sesión {session_id}: Ejecutando paso {paso_actual} del protocolo.")

    patient_data = db_session.patient_data
    diagnostico = patient_data.get("primer_diagnostico", "desconocido")
    nacionalidad = patient_data.get("nacionalidad", "desconocida")
    contenido_paso = ""

    if paso_actual == 1:
        contenido_paso = pdf_processor.obtener_explicacion_literal(diagnostico)
    elif paso_actual == 2:
        contenido_paso = pdf_processor.obtener_protocolo_espiritual()
    elif paso_actual == 3:
        contenido_paso = pdf_processor.buscar_protocolo_especifico(diagnostico)
    elif paso_actual == 4:
        contenido_paso = pdf_processor.obtener_dieta_por_nacionalidad(diagnostico, nacionalidad)
    elif paso_actual == 5:
        # Simplificado por falta de datos de edad/condición en el cuestionario actual
        contenido_paso = f"Basado en su diagnóstico de {diagnostico}, se recomienda ejercicio moderado como caminar 30 minutos al día. Consulte el PDF para un protocolo de ejercicio detallado y adaptado a su edad y condición física."
    elif paso_actual == 6:
        contenido_paso = pdf_processor.obtener_productos_recomendados(diagnostico)
        logger.info(f"Sesión {session_id}: Protocolo completado.")
        patient_email = patient_data.get("email") # Se necesita la pregunta de email en el cuestionario
        if patient_email:
            email_sent = email_handler.generar_email_sesion(db_session.__dict__, patient_email)
            db_session.email_sent = email_sent
            if email_sent:
                logger.info(f"Sesión {session_id}: Email de resumen enviado a {patient_email}.")
            else:
                logger.error(f"Sesión {session_id}: Fallo al enviar email de resumen a {patient_email}.")
        else:
            logger.warning(f"Sesión {session_id}: No se encontró email del paciente para enviar resumen.")

        db_session.status = "completed"
        db_session.end_time = datetime.now()

    protocol_steps = db_session.protocol_steps or []
    protocol_steps.append({"paso": paso_actual, "contenido": contenido_paso})
    db_session.protocol_steps = protocol_steps
    db_session.current_step = paso_actual

    await db.commit()

    return {
        "session_id": session_id,
        "paso_ejecutado": paso_actual,
        "contenido": contenido_paso,
        "siguiente_paso": paso_actual + 1 if paso_actual < 7 else None
    }
