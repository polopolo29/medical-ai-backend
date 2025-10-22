# 🏥 Sistema Médico AvatarMX

Sistema de consulta médica automatizada con protocolos específicos basados en medicina natural y espiritual.

## ✨ Características Principales

- **🩺 Evaluación Médica Completa**
  - 12 preguntas detalladas del paciente
  - Análisis de síntomas y diagnóstico
  - Investigación de efectos secundarios de medicamentos
  - Evaluación de hábitos alimenticios
- **📚 Protocolos de Sanación**
  - **Causa del Origen** - Explicación detallada de la enfermedad
  - **Protocolo Espiritual** - Video y prácticas espirituales
  - **Protocolo Específico** - Tratamiento según diagnóstico
  - **Dieta Personalizada** - Adaptada por nacionalidad
  - **Protocolo de Ejercicio** - Actividad física específica
  - **Productos Naturales** - Recomendaciones con enlaces a tienda

## 🔄 Flujo del Sistema

1.  **Compra de Acceso**: Usuario compra producto en WooCommerce.
2.  **Acceso Otorgado**: Plugin otorga acceso al sistema médico.
3.  **Inicio de Consulta**: Usuario accede a la página de consulta.
4.  **Evaluación (12 preguntas)**: El sistema realiza una evaluación completa.
5.  **Análisis (Servidor)**: El servidor procesa las respuestas.
6.  **Protocolo (6 pasos)**: Se genera y presenta un protocolo personalizado.
7.  **Email de Resumen**: El usuario recibe un resumen completo por email.
8.  **Compra de Productos**: Enlaces a productos naturales recomendados.

## 🛠️ Tecnologías

- **Backend**:
  - **FastAPI** - Framework Python moderno y rápido
  - **SQLAlchemy** - ORM para base de datos
  - **PyPDF2** - Procesamiento de PDFs médicos
  - **JWT** - Autenticación segura
- **Frontend**:
  - **WordPress** - CMS para gestión de contenido
  - **WooCommerce** - Sistema de e-commerce
  - **JavaScript (Vanilla)** - Interactividad del chat
  - **CSS3** - Diseño responsive y moderno
- **Despliegue**:
  - **Railway** - Plataforma de despliegue del servidor
  - **WordPress Hosting** - Cualquier hosting compatible
  - **SQLite/PostgreSQL** - Base de datos

## 📁 Estructura del Proyecto

```text
sistema-medico-avatarmx-completo/
├── servidor-medico/                 # Servidor FastAPI
│   ├── models/                      # Modelos de datos
│   ├── routers/                     # Endpoints API
│   ├── utils/                       # Utilidades
│   ├── middleware/                  # Middlewares
│   └── main.py                      # App principal
├── plugin-wordpress/               # Plugin WordPress
│   ├── assets/                     # CSS, JS, imágenes
│   ├── templates/                  # Plantillas PHP
│   └── medico-avatarMX.php        # Plugin principal
└── docs/                          # Documentación
```

## 🚀 Instalación Rápida

```bash
# 1. Clonar o descargar proyecto
git clone <repositorio>
cd sistema-medico-avatarmx-completo

# 2. Ejecutar instalador automático
./instalar-sistema-completo.sh

# 3. Seguir instrucciones en pantalla
```

## 🔧 Configuración

### Variables de Entorno Críticas
- `WORDPRESS_URL` - URL de tu sitio WordPress
- `WORDPRESS_API_KEY` - Clave API para comunicación
- `PDF_PROTOCOLS_PATH` - Ruta de PDFs médicos
- `EMAIL_*` - Configuración para emails

### Configuración WordPress
- Activar plugin `medico-avatarmx.zip`
- Configurar en `Ajustes > AvatarMX Medical`
- Crear productos con acceso médico

## 📊 API Endpoints

### Médicos
- `POST /api/v1/iniciar-conversacion` - Iniciar sesión
- `POST /api/v1/procesar-respuesta` - Procesar respuesta
- `POST /api/v1/ejecutar-paso-protocolo/{paso}` - Ejecutar protocolo

### Administración
- `GET /api/v1/admin/estadisticas` - Estadísticas del sistema
- `POST /api/v1/admin/cargar-pdf` - Cargar nuevo PDF médico

### PDFs
- `GET /api/v1/pdf/estado` - Estado de PDFs cargados
- `POST /api/v1/pdf/procesar` - Procesar PDF manualmente

## 🎯 Uso del Sistema

### Para Pacientes
1. Comprar producto con acceso médico
2. Acceder a página de consulta médica
3. Completar evaluación de 12 preguntas
4. Seguir protocolo de 6 pasos
5. Recibir resumen por email

### Para Administradores
1. Cargar PDFs médicos en `pdfs_medicos/`
2. Configurar productos en WooCommerce
3. Monitorear uso del sistema
4. Ver estadísticas de consultas

## 🔒 Seguridad
- Autenticación JWT entre servidor y WordPress
- Validación de datos con Pydantic
- CORS configurado para dominios específicos
- Logs de auditoría de todas las consultas
- Datos de pacientes protegidos

## 📈 Monitoreo

### Health Checks
```bash
curl https://tu-servidor.railway.app/health
```
### Métricas
- Consultas realizadas por día
- PDFs procesados correctamente
- Tiempos de respuesta del servidor
- Errores y excepciones

## 🐛 Solución de Problemas

### Comunes
- **Error de conexión** - Verificar URL y API Key
- **PDFs no cargan** - Verificar formato y permisos
- **Email no envía** - Revisar configuración SMTP
- **Acceso denegado** - Verificar compra del producto

### Logs
- `servidor-medico/medical_system.log` - Logs principales
- Logs de WordPress - Errores PHP
- Logs del navegador - Errores JavaScript

## 🤝 Contribución
1. Fork el proyecto
2. Crear feature branch
3. Commit cambios
4. Push al branch
5. Crear Pull Request

## 📄 Licencia
Este proyecto es privado y confidencial de AvatarMX.

## 🆘 Soporte
Para soporte técnico:
- Revisar documentación en `docs/`
- Verificar logs del sistema
- Contactar al equipo de desarrollo

---
**AvatarMX Medical System - Transformando la salud a través de tecnología y sabiduría natural 🌿**
