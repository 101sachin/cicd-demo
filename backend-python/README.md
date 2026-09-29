# Python backend (Phase 9 — coming later)

This folder will hold a FastAPI implementation of the **same API contract** as
`backend-php/`:

| Method | Path | Responses |
|---|---|---|
| GET | `/api/health` | 200 `{status, service, version, environment, database}` |
| GET | `/api/students` | 200 `{students: [{id, full_name, course, created_at}]}` |
| POST | `/api/students` | 201 / 400 / 409 / 422 (field `errors`) / 502 / 503 |

It will reuse `frontend/` and the same Supabase table, and get its own
pipeline: `.github/workflows/python-backend.yml`, triggered only by changes in
`backend-python/**`.
