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
cd sistema-medico-avatarmx-completo/servidor-medico

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
```
### 2. Plugin WordPress
- Sube el archivo `medico-avatarmx.zip` (que puedes crear comprimiendo la carpeta `plugin-wordpress`) a tu sitio WordPress a través del panel de administración.
- Activa el plugin.
- Ve a `Ajustes > AvatarMX Medical` en el panel de WordPress.
- Configura la **URL del Servidor** (ej. `http://127.0.0.1:8000`) y la **API Key** (la encontrarás en `servidor-medico/.env` después de configurarla).

### 3. Configurar Productos
- Edita un producto en WooCommerce.
- En la pestaña "AvatarMX Medical", marca la casilla "Dar acceso al sistema médico".
- Guarda los cambios.

## 🔧 Configuración Detallada

### Variables de Entorno (`servidor-medico/.env`)
Asegúrate de que estas variables estén configuradas correctamente:
```env
ENVIRONMENT=development
PORT=8000
WORDPRESS_URL=https://tudominio.com
WORDPRESS_API_KEY=tu_clave_api_generada_en_el_plugin
DATABASE_URL=sqlite:///./medical_system.db
PDF_PROTOCOLS_PATH=./pdfs_medicos
SESSION_TIMEOUT=7200 # 2 horas
EMAIL_HOST=smtp.gmail.com
EMAIL_PORT=587
EMAIL_USER=tu_email@gmail.com
EMAIL_PASS=tu_password_de_aplicacion
```
### Configuración WordPress
- **URL del Servidor:** La dirección completa donde se está ejecutando tu servidor FastAPI.
- **API Key:** Debe coincidir exactamente con la `WORDPRESS_API_KEY` en tu archivo `.env`.
- **Página de Chat:** El plugin crea automáticamente una página en `tudominio.com/consulta-medica-avatarmx` con el shortcode `[chat_medico_avatarmx]`.

## 📊 Verificación de Instalación

### Servidor
Verifica que el servidor esté funcionando correctamente.
```bash
# Health check (debería devolver {"status": "healthy", ...})
curl http://localhost:8000/health

# Estado del sistema
curl http://localhost:8000/api/status
```
### WordPress
- Visita la página `/consulta-medica-avatarmx` en tu sitio.
- Si has comprado un producto que da acceso, deberías ver la interfaz del chat.
- Si no, deberías ver un mensaje indicando que necesitas adquirir acceso.

## 🐛 Solución de Problemas
- **Error: No se puede conectar al servidor**
  - Asegúrate de que el servidor FastAPI (`python main.py`) esté ejecutándose.
  - Comprueba que no haya un firewall bloqueando el puerto `8000`.
  - Verifica que la URL del servidor en la configuración de WordPress sea correcta.
- **Error: Acceso denegado / API Key no válida**
  - Asegúrate de que la API Key en la configuración de WordPress y en el archivo `.env` del servidor sean idénticas.
  - Verifica que el usuario de WordPress haya comprado un producto que otorgue acceso.
- **Error: PDFs no se cargan**
  - Comprueba que el directorio `servidor-medico/pdfs_medicos` exista y tenga permisos de lectura.
  - Revisa los logs del servidor (`servidor-medico/medical_system.log`) para ver si hay errores de procesamiento.

## 📞 Soporte
- Revisa los logs en `servidor-medico/medical_system.log`.
- Verifica el estado del sistema en el endpoint `/api/status`.
- Contacta al equipo de desarrollo si el problema persiste.
