from sqlalchemy import create_engine, Column, Integer, String, DateTime, JSON, Enum, Boolean, Text, ForeignKey
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import sessionmaker, relationship
import os
from datetime import datetime
from models.session import SessionStatus, PatientGender

DATABASE_URL = os.getenv("DATABASE_URL", "sqlite:///./medical_system.db")

Base = declarative_base()

class PatientDataORM(Base):
    __tablename__ = "patient_data"
    id = Column(Integer, primary_key=True, index=True)
    session_id = Column(String, ForeignKey("medical_sessions.session_id"))
    nombre = Column(String, nullable=True)
    edad = Column(String, nullable=True)
    genero = Column(Enum(PatientGender), nullable=True)
    estatura_peso = Column(String, nullable=True)
    nacionalidad = Column(String, nullable=True)
    sintomas_iniciales = Column(Text, nullable=True)
    primer_diagnostico = Column(Text, nullable=True)
    tiempo_diagnostico = Column(String, nullable=True)
    sintomas_actuales = Column(Text, nullable=True)
    consume_medicamentos = Column(String, nullable=True)
    medicamentos_especificos = Column(Text, nullable=True)
    dieta_actual = Column(Text, nullable=True)
    alergias = Column(Text, nullable=True)
    condiciones_previas = Column(Text, nullable=True)
    email = Column(String, nullable=True)
    session = relationship("MedicalSessionORM", back_populates="patient_data_orm")

class MedicalSessionORM(Base):
    __tablename__ = "medical_sessions"
    session_id = Column(String, primary_key=True, index=True)
    user_id = Column(Integer, nullable=False)
    current_question = Column(Integer, default=1)
    current_step = Column(Integer, default=0)
    status = Column(Enum(SessionStatus), default=SessionStatus.ACTIVE)
    protocol_steps = Column(JSON, default=[])
    start_time = Column(DateTime, default=datetime.now)
    last_activity = Column(DateTime, default=datetime.now)
    end_time = Column(DateTime, nullable=True)
    email_sent = Column(Boolean, default=False)
    language = Column(String, default="es")
    metadata = Column(JSON, nullable=True)
    patient_data_orm = relationship("PatientDataORM", uselist=False, back_populates="session", cascade="all, delete-orphan")

engine = create_engine(DATABASE_URL)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)

def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()
