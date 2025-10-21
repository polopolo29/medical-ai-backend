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
