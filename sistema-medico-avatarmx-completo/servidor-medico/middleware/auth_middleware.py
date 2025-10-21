from starlette.middleware.base import BaseHTTPMiddleware
from starlette.requests import Request
from starlette.responses import JSONResponse
import os

class AuthMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request: Request, call_next):
        if request.url.path.startswith("/api/v1"):
            api_key = request.headers.get("Authorization")
            expected_api_key = f"Bearer {os.getenv('WORDPRESS_API_KEY')}"
            if api_key != expected_api_key:
                return JSONResponse(
                    status_code=401,
                    content={"detail": "Unauthorized"},
                )
        response = await call_next(request)
        return response
