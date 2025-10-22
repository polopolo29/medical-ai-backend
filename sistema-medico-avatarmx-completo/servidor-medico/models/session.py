from pydantic import BaseModel, Field, EmailStr
from typing import Dict, Any, Optional, List, Union
from datetime import datetime, timedelta
from enum import Enum
import uuid

class SessionStatus(str, Enum):
    ACTIVE = "active"
    COMPLETED = "completed"
    TIMEOUT = "timeout"
    CANCELLED = "cancelled"

class PatientGender(str, Enum):
    MALE = "male"
    FEMALE = "female"
    OTHER = "other"

class MedicalCondition(str, Enum):
    DIABETES = "diabetes"
    HYPERTENSION = "hypertension"
    ARTHRITIS = "arthritis"
    DIGESTIVE = "digestive"
    RESPIRATORY = "respiratory"
    CARDIOVASCULAR = "cardiovascular"
    OTHER = "other"

class PatientData(BaseModel):
    nombre: Optional[str] = Field(None, description="Nombre completo del paciente")
    edad: Optional[str] = Field(None, description="Edad del paciente")
    genero: Optional[PatientGender] = Field(None, description="Género del paciente")
    estatura_peso: Optional[str] = Field(None, description="Estatura y peso en formato 'estatura-cm/peso-kg'")
    nacionalidad: Optional[str] = Field(None, description="Nacionalidad del paciente")
    sintomas_iniciales: Optional[str] = Field(None, description="Síntomas iniciales del paciente")
    primer_diagnostico: Optional[str] = Field(None, description="Primer diagnóstico médico recibido")
    tiempo_diagnostico: Optional[str] = Field(None, description="Tiempo desde el diagnóstico")
    sintomas_actuales: Optional[str] = Field(None, description="Síntomas actuales del paciente")
    consume_medicamentos: Optional[str] = Field(None, description="Indica si consume medicamentos")
    medicamentos_especificos: Optional[str] = Field(None, description="Medicamentos específicos que consume")
    dieta_actual: Optional[str] = Field(None, description="Alimentos que consume regularmente")
    alergias: Optional[str] = Field(None, description="Alergias conocidas")
    condiciones_previas: Optional[str] = Field(None, description="Condiciones médicas previas")
    email: Optional[EmailStr] = Field(None, description="Email del paciente")

    class Config:
        json_schema_extra = {
            "example": {
                "nombre": "María González",
                "edad": "45",
                "genero": "female",
                "estatura_peso": "165cm/68kg",
                "nacionalidad": "mexicana",
                "sintomas_iniciales": "Cansancio constante y sed excesiva",
                "primer_diagnostico": "Diabetes tipo 2",
                "tiempo_diagnostico": "2 años",
                "sintomas_actuales": "Visión borrosa y hormigueo en pies",
                "consume_medicamentos": "Sí",
                "medicamentos_especificos": "Metformina 500mg",
                "dieta_actual": "Cerdo, trigo, arroz, azúcar, café",
                "alergias": "Ninguna",
                "condiciones_previas": "Hipertensión",
                "email": "paciente@ejemplo.com"
            }
        }

class ProtocolStepType(str, Enum):
    CAUSA_ORIGEN = "causa_origen"
    PROTOCOLO_ESPIRITUAL = "protocolo_espiritual"
    PROTOCOLO_ESPECIFICO = "protocolo_especifico"
    DIETA_PERSONALIZADA = "dieta_personalizada"
    PROTOCOLO_EJERCICIO = "protocolo_ejercicio"
    PRODUCTOS_NATURALES = "productos_naturales"

class ProtocolStep(BaseModel):
    step_number: int = Field(..., description="Número del paso en el protocolo")
    step_type: ProtocolStepType = Field(..., description="Tipo de paso del protocolo")
    step_name: str = Field(..., description="Nombre del paso")
    completed: bool = Field(False, description="Indica si el paso está completado")
    content: Optional[Dict[str, Any]] = Field(None, description="Contenido específico del paso")
    timestamp: Optional[datetime] = Field(None, description="Timestamp de ejecución")
    user_confirmation: bool = Field(False, description="Confirmación del usuario de haber comprendido")
    metadata: Optional[Dict[str, Any]] = Field(None, description="Metadatos adicionales")

    class Config:
        json_schema_extra = {
            "example": {
                "step_number": 1,
                "step_type": "causa_origen",
                "step_name": "Causa del Origen",
                "completed": True,
                "content": {
                    "explicacion": "La diabetes surge de un desequilibrio metabólico...",
                    "factores": ["genéticos", "alimenticios", "estilo de vida"]
                },
                "timestamp": "2024-01-15T10:30:00Z",
                "user_confirmation": True,
                "metadata": {"duracion_lectura": "5 minutos"}
            }
        }

class MedicalSession(BaseModel):
    session_id: str = Field(default_factory=lambda: str(uuid.uuid4()), description="ID único de la sesión")
    user_id: int = Field(..., description="ID del usuario en WordPress")
    patient_data: PatientData = Field(default_factory=PatientData, description="Datos del paciente")
    current_question: int = Field(1, description="Número de pregunta actual", ge=1, le=12)
    current_step: int = Field(0, description="Paso actual del protocolo", ge=0, le=6)
    status: SessionStatus = Field(SessionStatus.ACTIVE, description="Estado de la sesión")
    protocol_steps: List[ProtocolStep] = Field(default_factory=list, description="Pasos del protocolo ejecutados")
    start_time: datetime = Field(default_factory=datetime.now, description="Inicio de la sesión")
    last_activity: datetime = Field(default_factory=datetime.now, description="Última actividad")
    end_time: Optional[datetime] = Field(None, description="Fin de la sesión")
    email_sent: bool = Field(False, description="Indica si se envió el email de resumen")
    session_duration: Optional[timedelta] = Field(None, description="Duración total de la sesión")
    language: str = Field("es", description="Idioma de la sesión")
    metadata: Optional[Dict[str, Any]] = Field(None, description="Metadatos adicionales de la sesión")

    def update_activity(self):
        """Actualiza el timestamp de última actividad"""
        self.last_activity = datetime.now()

    def complete_question(self, question_num: int, answer: str):
        """Completa una pregunta y avanza a la siguiente"""
        question_field_map = {
            1: "nombre",
            2: "edad",
            3: "genero",
            4: "estatura_peso",
            5: "nacionalidad",
            6: "sintomas_iniciales",
            7: "primer_diagnostico",
            8: "tiempo_diagnostico",
            9: "sintomas_actuales",
            10: "consume_medicamentos",
            11: "medicamentos_especificos",
            12: "dieta_actual"
        }

        if question_num in question_field_map:
            setattr(self.patient_data, question_field_map[question_num], answer)
            self.current_question = question_num + 1
            self.update_activity()

    def is_evaluation_complete(self):
        """Verifica si la evaluación está completa"""
        return self.current_question > 12

    def add_protocol_step(self, step: ProtocolStep):
        """Agrega un paso al protocolo"""
        step.timestamp = datetime.now()
        self.protocol_steps.append(step)
        self.current_step = step.step_number
        self.update_activity()

    def complete_session(self):
        """Marca la sesión como completada"""
        self.status = SessionStatus.COMPLETED
        self.end_time = datetime.now()
        self.session_duration = self.end_time - self.start_time
        self.update_activity()

    def get_session_summary(self) -> Dict[str, Any]:
        """Obtiene un resumen de la sesión"""
        return {
            "session_id": self.session_id,
            "user_id": self.user_id,
            "patient_name": self.patient_data.nombre,
            "diagnosis": self.patient_data.primer_diagnostico,
            "status": self.status,
            "questions_completed": self.current_question - 1,
            "protocol_steps_completed": len([step for step in self.protocol_steps if step.completed]),
            "start_time": self.start_time.isoformat(),
            "duration": str(self.session_duration) if self.session_duration else None,
            "email_sent": self.email_sent
        }

class MedicalReport(BaseModel):
    report_id: str = Field(default_factory=lambda: str(uuid.uuid4()))
    session_id: str
    user_id: int
    patient_data: PatientData
    protocol_steps: List[ProtocolStep]
    generated_at: datetime = Field(default_factory=datetime.now)
    recommendations: List[str]
    follow_up_date: Optional[datetime]
    is_archived: bool = False

    class Config:
        json_schema_extra = {
            "example": {
                "report_id": "rep_123456789",
                "session_id": "sess_123456789",
                "user_id": 123,
                "patient_data": {
                    "nombre": "María González",
                    "edad": "45",
                    "diagnosis": "Diabetes tipo 2"
                },
                "generated_at": "2024-01-15T12:00:00Z",
                "recommendations": [
                    "Seguir dieta específica",
                    "Practicar protocolo espiritual diario",
                    "Usar productos naturales recomendados"
                ],
                "follow_up_date": "2024-02-15T12:00:00Z",
                "is_archived": False
            }
        }
