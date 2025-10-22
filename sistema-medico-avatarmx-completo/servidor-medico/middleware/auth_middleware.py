from fastapi import Request, HTTPException, status
from starlette.middleware.base import BaseHTTPMiddleware, RequestResponseEndpoint
from starlette.responses import Response
import os
from dotenv import load_dotenv

load_dotenv()

API_KEY = os.getenv("WORDPRESS_API_KEY")

class AuthMiddleware(BaseHTTPMiddleware):
    async def dispatch(
        self, request: Request, call_next: RequestResponseEndpoint
    ) -> Response:
        api_key = request.headers.get("Authorization")

        # Allow access to docs and root path without api key
        if request.url.path in ["/api/docs", "/api/redoc", "/api/openapi.json", "/"]:
            return await call_next(request)

        if not api_key or not API_KEY or not api_key.startswith("Bearer "):
            raise HTTPException(
                status_code=status.HTTP_401_UNAUTHORIZED,
                detail="No se proporcionó una clave de API válida",
            )

        token = api_key.split(" ")[1]

        if token != API_KEY:
            raise HTTPException(
                status_code=status.HTTP_401_UNAUTHORIZED,
                detail="Clave de API no válida",
            )

        response = await call_next(request)
        return response
