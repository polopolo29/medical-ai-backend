from sqlalchemy.orm import Session
from models.orm import SessionLocal, Base, engine, MedicalSessionORM, PatientDataORM
from models.session import MedicalSession, PatientData

async def init_db():
    """Inicializa la base de datos y crea las tablas."""
    Base.metadata.create_all(bind=engine)

async def check_db_connection():
    """Comprueba la conexión a la base de datos."""
    try:
        connection = engine.connect()
        connection.close()
        return "ok"
    except Exception as e:
        return f"error: {e}"

def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

def get_session(db: Session, session_id: str) -> MedicalSession:
    """Obtiene una sesión de la base de datos."""
    sesion_orm = db.query(MedicalSessionORM).filter(MedicalSessionORM.session_id == session_id).first()
    if sesion_orm:
        datos_paciente_orm = sesion_orm.patient_data_orm
        datos_paciente = PatientData(**datos_paciente_orm.__dict__) if datos_paciente_orm else PatientData()
        return MedicalSession(
            session_id=sesion_orm.session_id,
            user_id=sesion_orm.user_id,
            patient_data=datos_paciente,
            current_question=session_orm.current_question,
            current_step=session_orm.current_step,
            status=session_orm.status,
            protocol_steps=session_orm.protocol_steps,
            start_time=session_orm.start_time,
            last_activity=session_orm.last_activity,
            end_time=session_orm.end_time,
            email_sent=session_orm.email_sent,
            language=session_orm.language,
            metadata=session_orm.metadata
        )
    return None

def create_session(db: Session, sesion: MedicalSession) -> MedicalSession:
    """Crea una nueva sesión en la base de datos."""
    sesion_orm = MedicalSessionORM(
        session_id=sesion.session_id,
        user_id=sesion.user_id
    )
    datos_paciente_orm = PatientDataORM(
        session_id=sesion.session_id,
        **sesion.patient_data.dict()
    )
    sesion_orm.patient_data_orm = datos_paciente_orm
    db.add(sesion_orm)
    db.commit()
    db.refresh(sesion_orm)
    return sesion

def update_session(db: Session, session_id: str, sesion: MedicalSession) -> MedicalSession:
    """Actualiza una sesión en la base de datos."""
    sesion_orm = db.query(MedicalSessionORM).filter(MedicalSessionORM.session_id == session_id).first()
    if sesion_orm:
        for clave, valor in sesion.dict().items():
            if clave != 'patient_data':
                setattr(sesion_orm, clave, valor)

        datos_paciente_orm = sesion_orm.patient_data_orm
        if datos_paciente_orm:
            for clave, valor in sesion.patient_data.dict().items():
                setattr(datos_paciente_orm, clave, valor)

        db.commit()
        db.refresh(sesion_orm)
    return sesion
