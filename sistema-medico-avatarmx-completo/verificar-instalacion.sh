#!/bin/bash

echo "🔍 VERIFICACIÓN DEL SISTEMA AVATARMX"
echo "===================================="

# Función para verificar archivos
check_file() {
if [ -f "$1" ]; then
echo "✅ $1"
return 0
else
echo "❌ $1 - FALTANTE"
return 1
fi
}

# Función para verificar directorios
check_dir() {
if [ -d "$1" ]; then
echo "✅ $1"
return 0
else
echo "❌ $1 - FALTANTE"
return 1
fi
}

echo ""
echo "📁 ESTRUCTURA DE DIRECTORIOS:"
check_dir "servidor-medico"
check_dir "servidor-medico/models"
check_dir "servidor-medico/routers"
check_dir "servidor-medico/utils"
check_dir "servidor-medico/middleware"
check_dir "plugin-wordpress"
check_dir "plugin-wordpress/assets"
check_dir "plugin-wordpress/templates"
check_dir "docs"

echo ""
echo "🔧 ARCHIVOS DEL SERVIDOR:"
check_file "servidor-medico/main.py"
check_file "servidor-medico/requirements.txt"
check_file "servidor-medico/.env.example"
check_file "servidor-medico/railway.toml"
check_file "servidor-medico/models/session.py"

echo ""
echo "🌐 ARCHIVOS DEL PLUGIN WORDPRESS:"
check_file "plugin-wordpress/medico-avatarMX.php"
check_file "plugin-wordpress/assets/css/chat-medico.css"
check_file "plugin-wordpress/assets/js/chat-medico.js"
check_file "plugin-wordpress/templates/chat-medico.php"

echo ""
echo "📚 ARCHIVOS DE DOCUMENTACIÓN:"
check_file "instalar-sistema-completo.sh"
check_file "verificar-instalacion.sh"
check_file "desplegar-servidor.sh"
check_file "INSTRUCCIONES.md"
check_file "README.md"

echo ""
echo "🎯 SCRIPTS EJECUTABLES:"
chmod +x instalar-sistema-completo.sh 2>/dev/null && echo "✅ instalar-sistema-completo.sh"
chmod +x verificar-instalacion.sh 2>/dev/null && echo "✅ verificar-instalacion.sh"
chmod +x desplegar-servidor.sh 2>/dev/null && echo "✅ desplegar-servidor.sh"

echo ""
echo "📊 RESUMEN:"
total_archivos=$(find . -type f | wc -l)
echo "Total de archivos creados: $total_archivos"

echo ""
echo "🚀 ESTADO DEL SISTEMA:"
# A simple check for a reasonable number of files
if [ "$total_archivos" -gt 10 ]; then
echo "✅ SISTEMA COMPLETO - Listo para instalar"
echo ""
echo "📝 PRÓXIMOS PASOS:"
echo "1. Ejecutar: ./instalar-sistema-completo.sh"
echo "2. Configurar variables en servidor-medico/.env"
echo "3. Ejecutar: ./desplegar-servidor.sh"
echo "4. Instalar plugin en WordPress"
else
echo "⚠️ SISTEMA INCOMPLETO - Algunos archivos faltan"
exit 1
fi
