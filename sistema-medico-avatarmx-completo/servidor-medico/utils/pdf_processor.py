import os
import fitz
import logging
import re

logger = logging.getLogger(__name__)

class PDFProcessor:
    def __init__(self, pdf_name="conocimiento_medico.pdf"):
        self.pdf_path = self._find_pdf(pdf_name)
        if not self.pdf_path:
            raise FileNotFoundError(f"No se pudo encontrar el PDF principal: {pdf_name}")
        self.full_text = self._extract_full_text()
        logger.info(f"PDF principal '{pdf_name}' cargado y procesado.")

    def _find_pdf(self, pdf_name):
        pdf_dir = os.path.join("sistema-medico-avatarmx-completo", "servidor-medico", "pdfs_medicos")
        path = os.path.join(pdf_dir, pdf_name)
        return path if os.path.exists(path) else None

    def _extract_full_text(self):
        try:
            doc = fitz.open(self.pdf_path)
            text = ""
            for page in doc:
                text += page.get_text()
            return text
        except Exception as e:
            logger.error(f"Error al extraer texto del PDF: {e}")
            return ""

    def _extract_section(self, section_title, full_text):
        pattern = f"==> SECCION: {section_title.upper()} <=="
        start_match = re.search(pattern, full_text)
        if not start_match:
            logger.warning(f"No se encontró la sección '{section_title}' en el PDF.")
            return None
        start_index = start_match.end()
        next_section_pattern = r"==> SECCION: .*? <=="
        next_match = re.search(next_section_pattern, full_text[start_index:])
        end_index = start_index + next_match.start() if next_match else len(full_text)
        return full_text[start_index:end_index].strip()

    def _search_in_section(self, section_content, entity_type, entity_name):
        if not section_content:
            return f"Contenido de la sección no disponible."
        pattern = f"==> {entity_type.upper()}: {entity_name.upper()} <==\n(.*?)(?=\n==> {entity_type.upper()}:|\Z)"
        match = re.search(pattern, section_content, re.DOTALL)
        if not match:
            logger.warning(f"No se encontró la entidad '{entity_name}' en la sección.")
            return f"No se encontró información específica para '{entity_name}'."
        return match.group(1).strip()

    def obtener_explicacion_literal(self, enfermedad):
        causas_section = self._extract_section("CAUSAS_ORIGEN", self.full_text)
        return self._search_in_section(causas_section, "ENFERMEDAD", enfermedad)

    def obtener_protocolo_espiritual(self):
        return self._extract_section("PROTOCOLO_ESPIRITUAL", self.full_text)

    def buscar_protocolo_especifico(self, diagnostico):
        protocolos_section = self._extract_section("PROTOCOLOS_ESPECIFICOS", self.full_text)
        return self._search_in_section(protocolos_section, "ENFERMEDAD", diagnostico)

    def obtener_dieta_por_nacionalidad(self, enfermedad, nacionalidad):
        dietas_section = self._extract_section("DIETAS_POR_NACIONALIDAD", self.full_text)
        dieta_base = self._search_in_section(dietas_section, "ENFERMEDAD", enfermedad)
        adaptacion = self._search_in_section(dietas_section, "NACIONALIDAD", nacionalidad)
        return f"{dieta_base}\n\nAdaptación para {nacionalidad}:\n{adaptacion}"

    def obtener_productos_recomendados(self, diagnostico):
        productos_section = self._extract_section("PRODUCTOS_NATURALES", self.full_text)
        return self._search_in_section(productos_section, "ENFERMEDAD", diagnostico)

    def buscar_efectos_secundarios(self, medicamento):
        medicamentos_section = self._extract_section("MEDICAMENTOS", self.full_text)
        return self._search_in_section(medicamentos_section, "MEDICAMENTO", medicamento)
