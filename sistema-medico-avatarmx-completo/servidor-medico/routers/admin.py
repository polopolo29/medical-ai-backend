from fastapi import APIRouter

router = APIRouter()

@router.get("/estadisticas")
async def get_estadisticas():
    """
    Endpoint para obtener estadísticas del sistema.
    (Implementación de ejemplo)
    """
    return {
        "consultas_totales": 150,
        "usuarios_activos": 25,
        "pdfs_procesados": 10
    }
