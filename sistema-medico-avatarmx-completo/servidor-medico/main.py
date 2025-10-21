from fastapi import FastAPI, HTTPException, Request
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
from fastapi.staticfiles import StaticFiles
from fastapi.templating import Jinja2Templates
from routers import medical, admin, pdf_manager
from middleware.auth_middleware import AuthMiddleware
from utils.database import init_db
import uvicorn
import os
from dotenv import load_dotenv
import logging

# Configurar logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('medical_system.log'),
        logging.StreamHandler()
    ]
)

logger = logging.getLogger(__name__)

load_dotenv()

app = FastAPI(
    title="🏥 Sistema Médico AvatarMX API",
    description="Sistema de consulta médica automatizada con protocolos específicos basados en PDFs médicos",
    version="2.0.0",
    docs_url="/api/docs",
    redoc_url="/api/redoc",
    openapi_url="/api/openapi.json"
)

# Configurar CORS para permitir conexiones desde WordPress
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # En producción, especificar dominios específicos
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Middleware de autenticación personalizado
app.add_middleware(AuthMiddleware)

# Montar archivos estáticos
app.mount("/static", StaticFiles(directory="static"), name="static")
templates = Jinja2Templates(directory="templates")

# Incluir routers
app.include_router(medical.router, prefix="/api/v1", tags=["medical"])
app.include_router(admin.router, prefix="/api/v1/admin", tags=["admin"])
app.include_router(pdf_manager.router, prefix="/api/v1/pdf", tags=["pdf"])

# Eventos de inicialización
@app.on_event("startup")
async def startup_event():
    logger.info("🚀 Iniciando Sistema Médico AvatarMX...")
    await init_db()
    logger.info("✅ Base de datos inicializada")
    logger.info("📚 Cargando protocolos médicos desde PDFs...")

@app.on_event("shutdown")
async def shutdown_event():
    logger.info("🛑 Cerrando Sistema Médico AvatarMX...")

# Rutas principales
@app.get("/")
async def root():
    return {
        "message": "🏥 Sistema Médico AvatarMX - Servidor Activo",
        "version": "2.0.0",
        "status": "operacional",
        "endpoints": {
            "medical": "/api/v1",
            "admin": "/api/v1/admin",
            "pdf": "/api/v1/pdf",
            "docs": "/api/docs"
        }
    }

@app.get("/health")
async def health_check():
    from utils.database import check_db_connection
    from utils.pdf_processor import PDFProcessor

    db_status = await check_db_connection()
    pdf_processor = PDFProcessor()
    pdf_stats = pdf_processor.obtener_estadisticas()

    return {
        "status": "healthy",
        "service": "medical-api",
        "database": db_status,
        "pdfs_loaded": pdf_stats["pdfs_cargados"],
        "protocols_available": pdf_stats["protocolos_disponibles"],
        "timestamp": os.getenv("DEPLOY_TIMESTAMP", "unknown")
    }

@app.get("/api/status")
async def system_status():
    """Endpoint detallado del estado del sistema"""
    from utils.pdf_processor import PDFProcessor

    pdf_processor = PDFProcessor()
    stats = pdf_processor.obtener_estadisticas()

    return {
        "system": "AvatarMX Medical System",
        "version": "2.0.0",
        "environment": os.getenv("ENVIRONMENT", "development"),
        "statistics": stats,
        "features": {
            "pdf_processing": True,
            "medical_protocols": True,
            "spiritual_protocol": True,
            "diet_planning": True,
            "natural_products": True,
            "email_notifications": True
        }
    }

# Manejo de errores global
@app.exception_handler(HTTPException)
async def http_exception_handler(request: Request, exc: HTTPException):
    return JSONResponse(
        status_code=exc.status_code,
        content={
            "success": False,
            "error": exc.detail,
            "path": request.url.path,
            "method": request.method
        }
    )

@app.exception_handler(Exception)
async def general_exception_handler(request: Request, exc: Exception):
    logger.error(f"Error no manejado: {str(exc)}", exc_info=True)
    return JSONResponse(
        status_code=500,
        content={
            "success": False,
            "error": "Error interno del servidor",
            "message": "Por favor, contacte al administrador del sistema"
        }
    )

if __name__ == "__main__":
    uvicorn.run(
        "main:app",
        host="0.0.0.0",
        port=int(os.getenv("PORT", "8000")),
        reload=os.getenv("ENVIRONMENT") == "development",
        log_level="info",
        access_log=True
    )
