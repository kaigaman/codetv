from fastapi import FastAPI, Query
from fastapi.middleware.cors import CORSMiddleware
from contextlib import asynccontextmanager

from api.routes import router
from api.tasks import celery_app
from http_client import close_session


@asynccontextmanager
async def lifespan(app: FastAPI):
    print("CODETV Python API started")
    yield
    print("CODETV Python API shutting down...")
    await close_session()
    print("HTTP session closed")


app = FastAPI(
    title="CODETV Python API",
    description="Microservices layer for CODETV - stream validation, EPG, M3U generation",
    version="1.0.0",
    lifespan=lifespan,
    contact={"name": "William Kaiga", "role": "Developer"},
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(router, prefix="/api/v1")


@app.get("/health")
async def health():
    return {"status": "ok", "service": "codetv-python", "version": "1.0.0"}
