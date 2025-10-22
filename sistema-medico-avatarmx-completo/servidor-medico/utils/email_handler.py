import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
import os
from dotenv import load_dotenv
import logging

load_dotenv()
logger = logging.getLogger(__name__)

class EmailHandler:
    def __init__(self):
        self.smtp_host = os.getenv("EMAIL_HOST")
        self.smtp_port = int(os.getenv("EMAIL_PORT", 587))
        self.smtp_user = os.getenv("EMAIL_USER")
        self.smtp_pass = os.getenv("EMAIL_PASS")
        self.email_from = os.getenv("EMAIL_FROM")

    def _generar_detalle_protocolo(self, protocol_steps):
        if not protocol_steps:
            return "No se ejecutaron pasos del protocolo."

        detalle = ""
        for step in protocol_steps:
            detalle += f"<h3>Paso {step['paso']}:</h3>\n<pre>{step['contenido']}</pre>\n\n"
        return detalle

    def generar_email_sesion(self, session_data, patient_email):
        """
        Genera y envía un email con el resumen completo de la sesión.
        """
        if not all([self.smtp_host, self.smtp_port, self.smtp_user, self.smtp_pass, self.email_from]):
            logger.error("Faltan variables de entorno para el envío de emails.")
            return False

        patient_info = session_data.get('patient_data', {})
        protocol_steps = session_data.get('protocol_steps', [])

        asunto = f"📄 Resumen Sesión Médica AvatarMX - {datetime.now().strftime('%Y-%m-%d')}"

        cuerpo_html = f"""
        <html>
        <body>
            <h2>Resumen de tu Sesión Médica en AvatarMX</h2>

            <h3>👤 INFORMACIÓN DEL PACIENTE:</h3>
            <ul>
                <li><strong>Nombre:</strong> {patient_info.get('nombre', 'N/A')}</li>
                <li><strong>Edad:</strong> {patient_info.get('edad', 'N/A')}</li>
                <li><strong>Nacionalidad:</strong> {patient_info.get('nacionalidad', 'N/A')}</li>
            </ul>

            <h3>🏥 EVALUACIÓN MÉDICA:</h3>
            <ul>
                <li><strong>Diagnóstico:</strong> {patient_info.get('primer_diagnostico', 'N/A')}</li>
                <li><strong>Síntomas actuales:</strong> {patient_info.get('sintomas_actuales', 'N/A')}</li>
                <li><strong>Medicamentos:</strong> {patient_info.get('medicamentos_especificos', 'Ninguno')}</li>
            </ul>

            <h3>📋 PROTOCOLO APLICADO:</h3>
            {self._generar_detalle_protocolo(protocol_steps)}

            <hr>
            <p>Este es un resumen de tu sesión. Para continuar o revisar tu protocolo, por favor, accede a nuestro portal.</p>
        </body>
        </html>
        """

        msg = MIMEMultipart()
        msg['From'] = self.email_from
        msg['To'] = patient_email
        msg['Subject'] = asunto
        msg.attach(MIMEText(cuerpo_html, 'html'))

        try:
            with smtplib.SMTP(self.smtp_host, self.smtp_port) as server:
                server.starttls()
                server.login(self.smtp_user, self.smtp_pass)
                server.send_message(msg)
            logger.info(f"Email de resumen enviado exitosamente a {patient_email}")
            return True
        except Exception as e:
            logger.error(f"Error al enviar el email: {e}")
            return False

from datetime import datetime
