#!/bin/bash

echo "🚀 INSTALADOR COMPLETO - SISTEMA MÉDICO AVATARMX"
echo "================================================"
echo "Este script instalará todo el sistema paso a paso"
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Funciones de ayuda
print_step() {
    echo -e "${BLUE}▶ $1${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

check_command() {
    if command -v $1 &> /dev/null; then
        print_success "$1 encontrado"
        return 0
    else
        print_error "$1 no encontrado"
        return 1
    fi
}

# Verificar sistema
echo "🔍 Verificando requisitos del sistema..."

check_command python3
check_command pip3
check_command node
check_command npm
check_command git

# Verificar versiones
PYTHON_VERSION=$(python3 -c 'import sys; print(".".join(map(str, sys.version_info[:2])))')
if python3 -c "import sys; sys.exit(0 if sys.version_info >= (3, 8) else 1)"; then
    print_success "Python $PYTHON_VERSION - compatible"
else
    print_error "Se requiere Python 3.8 o superior"
    exit 1
fi

# Crear entorno virtual
print_step "Creando entorno virtual Python..."
python3 -m venv venv
if [ $? -eq 0 ]; then
    print_success "Entorno virtual creado"
else
    print_error "Error creando entorno virtual"
    exit 1
fi

# Activar entorno virtual
print_step "Activando entorno virtual..."
source venv/bin/activate

# Instalar dependencias del servidor
print_step "Instalando dependencias del servidor..."
pip install -r servidor-medico/requirements.txt
if [ $? -eq 0 ]; then
    print_success "Dependencias del servidor instaladas"
else
    print_error "Error instalando dependencias"
    exit 1
fi

# Configurar variables de entorno
print_step "Configurando variables de entorno..."
cp servidor-medico/.env.example servidor-medico/.env
print_warning "Por favor, edita servidor-medico/.env con tus configuraciones"

# Crear directorios necesarios
print_step "Creando directorios del sistema..."
mkdir -p servidor-medico/static
mkdir -p servidor-medico/pdfs_medicos
mkdir -p servidor-medico/logs
mkdir -p servidor-medico/data

# Configurar permisos
print_step "Configurando permisos..."
chmod +x servidor-medico/main.py

# Probar el servidor
print_step "Probando el servidor..."
cd servidor-medico
python -c "from main import app; print('✅ Servidor importado correctamente')"
if [ $? -eq 0 ]; then
    print_success "Servidor configurado correctamente"
else
    print_error "Error en la configuración del servidor"
    exit 1
fi

cd ..

# Configurar el plugin de WordPress
print_step "Preparando plugin de WordPress..."
cd plugin-wordpress

# Crear archivo ZIP del plugin
print_step "Creando archivo ZIP del plugin..."
zip -r medico-avatarmx.zip . -x "*.git*" "*.DS_Store" "*.tmp"
if [ $? -eq 0 ]; then
    print_success "Plugin empaquetado correctamente"
    mv medico-avatarmx.zip ../
else
    print_error "Error empaquetando el plugin"
    exit 1
fi

cd ..

# Crear script de despliegue
print_step "Creando scripts de despliegue..."
cat > desplegar-servidor.sh << 'SERVERScript'
#!/bin/bash
echo "🚀 Desplegando Servidor Médico AvatarMX..."
cd servidor-medico
source ../venv/bin/activate

# Verificar variables de entorno
if [ ! -f .env ]; then
    echo "❌ Error: Archivo .env no encontrado"
    echo "   Copia .env.example a .env y configura las variables"
    exit 1
fi

echo "🌐 Iniciando servidor en http://localhost:8000"
echo "📚 Documentación disponible en http://localhost:8000/api/docs"
echo "🩺 Health check en http://localhost:8000/health"

python main.py
SERVERScript

chmod +x desplegar-servidor.sh

# Crear documentación de instalación
print_step "Creando documentación..."
cat > INSTRUCCIONES.md << 'DOC'
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
DOC

print_success "Documentación creada"

Resumen final
echo ""
echo "🎉 INSTALACIÓN COMPLETADA EXITOSAMENTE!"
echo ""
echo "📋 PRÓXIMOS PASOS:"
echo ""
echo "1. 🔧 CONFIGURAR VARIABLES DE ENTORNO:"
echo " Edita servidor-medico/.env con tus configuraciones"
echo ""
echo "2. 🚀 INICIAR SERVIDOR:"
echo " ./desplegar-servidor.sh"
echo ""
echo "3. 🌐 INSTALAR PLUGIN WORDPRESS:"
echo " Subir medico-avatarmx.zip y activar"
echo ""
echo "4. ⚙️ CONFIGURAR WORDPRESS:"
echo " Ajustes > AvatarMX Medical"
echo ""
echo "5. 🛒 CONFIGURAR PRODUCTOS:"
echo " Marcar productos con acceso médico"
echo ""
echo "6. 🧪 PROBAR SISTEMA:"
echo " Visitar /consulta-medica-avatarmx"
echo ""
echo "🔗 URLs importantes:"
echo " - Servidor: http://localhost:8000"
echo " - Documentación API: http://localhost:8000/api/docs"
echo " - Health Check: http://localhost:8000/health"
echo " - Estado del Sistema: http://localhost:8000/api/status"
echo ""
echo "📚 Documentación completa en: INSTRUCCIONES.md"
echo ""

print_success "Sistema médico AvatarMX instalado y listo para usar! 🏥"
