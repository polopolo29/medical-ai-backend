from sqlalchemy import Column, Integer, String, DateTime, JSON, Boolean, Enum, Text
from sqlalchemy.orm import declarative_base
from models.session import SessionStatus

Base = declarative_base()

class MedicalSessionDB(Base):
    __tablename__ = "medical_sessions"

    id = Column(Integer, primary_key=True, index=True)
    session_id = Column(String, unique=True, index=True, nullable=False)
    user_id = Column(Integer, nullable=False)
    patient_data = Column(JSON)
    current_question = Column(Integer, default=1)
    current_step = Column(Integer, default=0)
    status = Column(Enum(SessionStatus), default=SessionStatus.ACTIVE)
    protocol_steps = Column(JSON, default=[])
    start_time = Column(DateTime)
    last_activity = Column(DateTime)
    end_time = Column(DateTime)
    email_sent = Column(Boolean, default=False)
    session_duration = Column(Text) # Storing as string for simplicity
    language = Column(String, default="es")
    meta_data = Column(JSON)
