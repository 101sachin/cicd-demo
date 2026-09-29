# Learning Plan — CI/CD with a Student Registration App

Tick the boxes (`[x]`) as you finish each step. Every phase has a **Goal**
(what you build/do) and a **Concept** (what you learn). Don't skip ahead —
each phase builds on the previous one.

```
 you ──git push──▶ GitHub ──triggers──▶ GitHub Actions pipeline
                                          │
          ┌───────────────┬───────────────┼────────────────┐
          ▼               ▼               ▼                ▼
       1. Lint       2. Static       3. Unit tests    (all must pass)
       (PSR-12)      analysis        (PHP 8.2+8.3)
                     (PHPStan)
                                          │
                                          ▼
                              4. Build Docker image
                              5. Smoke-test the image
                              6. Push image to GHCR  (main branch only)
                                          │
                                          ▼
                              7. Deploy to Render
                              8. Verify /api/health == this commit
                                          │
                                          ▼
                     Browser ──▶ Render (PHP API + form) ──▶ Supabase (Postgres)
```

---

## Phase 0 — Understand the theory (30 min)
**Concept:** What CI and CD are, and why teams use them.

- [ ] Read [docs/01-cicd-concepts.md](docs/01-cicd-concepts.md)
- [ ] Be able to answer: What is the difference between Continuous *Delivery* and Continuous *Deployment*?
- [ ] Be able to answer: Why do we "build once, deploy the same artifact"?

## Phase 1 — Set up the database (Supabase) (20 min)
**Concept:** Managed databases, migrations as code, and keeping secret keys secret.

- [ ] Create a free Supabase project → [docs/02-supabase-setup.md](docs/02-supabase-setup.md)
- [ ] Run [database/migrations/001_create_students.sql](database/migrations/001_create_students.sql) in the SQL Editor
- [ ] Copy the **Project URL** and **Secret key**
- [ ] `cp .env.example .env` and paste the values into `.env` (never commit this file)

## Phase 2 — Run the app locally with Docker (30 min)
**Concept:** Containers give "works on my machine" = "works everywhere".

- [ ] Start Docker Desktop
- [ ] `docker compose run --rm api composer install`
- [ ] `docker compose up --build` → open http://localhost:8080
- [ ] Register a student, then check the row in Supabase → Table Editor
- [ ] Read [docs/03-local-development.md](docs/03-local-development.md)

## Phase 3 — Run the CI checks by hand (30 min)
**Concept:** A pipeline only automates commands you can already run yourself.

- [ ] `docker compose run --rm api composer lint` (coding standard)
- [ ] `docker compose run --rm api composer analyse` (static analysis)
- [ ] `docker compose run --rm api composer test` (unit tests)
- [ ] Break a test on purpose, watch it fail, then fix it

## Phase 4 — Put the code on GitHub (20 min)
**Concept:** Version control is the single source of truth, and each push is an event that triggers the pipeline.

- [ ] Create an empty GitHub repo (no README)
- [ ] `git init`, `git add .`, `git commit`, `git push` → [docs/05-deploy-to-render.md](docs/05-deploy-to-render.md) §1
- [ ] Open the **Actions** tab and watch your first pipeline run
- [ ] Expect "deploy skipped", because Render isn't set up yet. That's normal.

## Phase 5 — Understand the pipeline file line by line (45 min)
**Concept:** Triggers, jobs, steps, runners, `needs`, matrix, cache, artifacts, conditions, secrets.

- [ ] Read [.github/workflows/php-backend.yml](.github/workflows/php-backend.yml) alongside [docs/04-pipeline-walkthrough.md](docs/04-pipeline-walkthrough.md)
- [ ] Find in the Actions UI: the job graph, the matrix jobs, and the uploaded test-report artifact

## Phase 6 — Continuous Deployment to Render (45 min)
**Concept:** Container registries, deploy hooks, pipeline secrets vs runtime secrets, environments, post-deploy verification.

- [ ] Make the GHCR image package **public**
- [ ] Create a Render Web Service from the image and add the Supabase env vars **in Render**
- [ ] Add the GitHub secret `RENDER_DEPLOY_HOOK_URL` and the variable `RENDER_APP_URL`
- [ ] Push a small change, watch it reach production automatically, and check that `/api/health` shows the new commit SHA

## Phase 7 — Team workflow & safety nets (30 min)
**Concept:** Branch protection, pull requests, and required status checks.

- [ ] Protect `main`: require a PR and require the CI jobs to pass
- [ ] Create a feature branch, open a PR, and see CI run *without* deploying
- [ ] Merge the PR and see it deploy

## Phase 8 — Break things on purpose (practice) (60 min)
**Concept:** Learn to read failures, which is the most important real-world skill.

- [ ] Do the exercises in [docs/06-exercises.md](docs/06-exercises.md)

## Phase 9 — Python backend (later)
**Concept:** One API contract, many implementations, each with its own pipeline (monorepo + path filters).

- [ ] Build `backend-python/` (FastAPI) with the **same** `/api/...` contract
- [ ] Add `.github/workflows/python-backend.yml` (pytest, ruff, Docker, deploy)
- [ ] Deploy it as a second Render service and point it at the same Supabase DB
- [ ] Note that the frontend doesn't change at all

---

## Project map

| Path | What it is |
|---|---|
| `frontend/` | HTML form + JS that calls the API (shared by both backends) |
| `backend-php/src/` | PHP API code: router, controller, validator, Supabase repository |
| `backend-php/tests/` | Unit tests (no database needed, using fakes) |
| `backend-php/Dockerfile` | Multi-stage image: `dev` for local work, `prod` for deployment |
| `database/migrations/` | SQL schema, versioned in git |
| `.github/workflows/` | The CI/CD pipeline(s) |
| `docker-compose.yml` | One-command local environment |
| `docs/` | Lessons, one file per phase |
| `backend-python/` | Phase 9 (coming later) |
