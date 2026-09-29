# Student Registration: a CI/CD Learning Project

A small, realistic app used to learn **CI/CD end to end**:

- **Frontend:** HTML + vanilla JS form (`frontend/`), talking to the backend only through a JSON API
- **Backend:** PHP 8.3 REST API (`backend-php/`); Python/FastAPI version coming later (`backend-python/`)
- **Database:** Supabase (PostgreSQL) via its REST API
- **CI/CD:** GitHub Actions → lint → static analysis → tests → Docker build → smoke test → GHCR → Render → verify

👉 **Start with [PLAN.md](PLAN.md)**. It's the step-by-step learning roadmap.

## Quick start (local)

Requires Docker Desktop only (no PHP install needed).

```bash
cp .env.example .env      # then fill in your Supabase URL + secret key
```
```bash
docker compose run --rm api composer install
```
```bash
docker compose up --build
```

Open http://localhost:8080

## Run the CI checks locally

```bash
docker compose run --rm api composer ci
```

## Lessons

1. [CI/CD concepts](docs/01-cicd-concepts.md)
2. [Supabase setup](docs/02-supabase-setup.md)
3. [Local development](docs/03-local-development.md)
4. [The pipeline, line by line](docs/04-pipeline-walkthrough.md)
5. [Push to GitHub & deploy to Render](docs/05-deploy-to-render.md)
6. [Exercises](docs/06-exercises.md)
