# 🏥 Sistema Médico AvatarMX - Instrucciones de Instalación

## 📋 Requisitos del Sistema

### Servidor
- Python 3.8 o superior
- pip (gestor de paquetes Python)
- 1GB RAM mínimo
- 500MB espacio en disco

### WordPress
- WordPress 6.0 o superior
- WooCommerce 6.0 o superior
- PHP 7.4 o superior
- 100MB espacio adicional

## 🚀 Instalación Rápida

### 1. Servidor FastAPI

```bash
cd servidor-medico

# Configurar entorno virtual
python -m venv venv
source venv/bin/activate  # Linux/Mac
# venv\Scripts\activate  # Windows

# Instalar dependencias
pip install -r requirements.txt

# Configurar variables
cp .env.example .env
# Editar .env con tus configuraciones

# Ejecutar servidor
python main.py
2. Plugin WordPress
Subir medico-avatarmx.zip a tu sitio WordPress

Activar el plugin

Ir a Ajustes > AvatarMX Medical

Configurar URL del servidor y API Key

3. Configurar Productos
Editar un producto en WooCommerce

En la pestaña "AvatarMX Medical", marcar "Dar acceso al sistema médico"

Guardar cambios

🔧 Configuración Detallada
Variables de Entorno (.env)
env
ENVIRONMENT=development
PORT=8000
WORDPRESS_URL=https://tudominio.com
WORDPRESS_API_KEY=tu_clave_api_generada
DATABASE_URL=sqlite:///./medical_system.db
PDF_PROTOCOLS_PATH=./pdfs_medicos
SESSION_TIMEOUT=7200
EMAIL_HOST=smtp.gmail.com
EMAIL_PORT=587
EMAIL_USER=tu_email@gmail.com
EMAIL_PASS=tu_password
Configuración WordPress
URL del Servidor: URL donde está desplegado tu servidor FastAPI

API Key: Clave generada automáticamente durante la activación

Página de Chat: Se crea automáticamente en tudominio.com/consulta-medica-avatarmx

📊 Verificación de Instalación
Servidor
bash
# Health check
curl http://localhost:8000/health

# Estado del sistema
curl http://localhost:8000/api/status
WordPress
Verificar que el shortcode [chat_medico_avatarmx] funcione

Comprobar acceso médico en productos configurados

🐛 Solución de Problemas
Error: No se puede conectar al servidor
Verificar que el servidor esté ejecutándose

Comprobar firewall y puertos

Verificar URL en configuración WordPress

Error: Acceso denegado
Verificar API Key en ambos lados

Comprobar que el usuario tenga acceso médico

Verificar logs del servidor

Error: PDFs no se cargan
Verificar permisos de directorio pdfs_medicos

Comprobar formato de los PDFs

Revisar logs de procesamiento

📞 Soporte
Para soporte técnico:

Revisar logs en servidor-medico/medical_system.log

Verificar estado del sistema en /api/status

Contactar al equipo de desarrollo

Logs importantes:

servidor-medico/medical_system.log - Logs del servidor

servidor-medico/logs/ - Logs específicos por funcionalidad

Logs de WordPress - Revisar errores PHP
