# Lesson 3: Local Development with Docker

You don't need PHP installed. Docker gives you exactly the same PHP 8.3 +
Apache that runs in CI and in production.

## Run the app

```bash
# 1. First time only: install PHP dependencies into backend-php/vendor
docker compose run --rm api composer install

# 2. Start the app
docker compose up --build
```

Open http://localhost:8080. The badge at the top should say **API local-dev online**.
Register a student, then check Supabase → Table Editor → `students`.

Stop it with `Ctrl+C` (or `docker compose down`).

## Run the same checks CI runs

```bash
docker compose run --rm api composer lint      # PSR-12 coding style
docker compose run --rm api composer lint:fix  # auto-fix style issues
docker compose run --rm api composer analyse   # PHPStan static analysis
docker compose run --rm api composer test      # PHPUnit
docker compose run --rm api composer ci        # all three, in order
```

> **Rule of thumb:** run `composer ci` before every `git push`. If it passes
> locally, it will almost always pass in the pipeline.

## Try the API directly

```bash
curl http://localhost:8080/api/health
curl http://localhost:8080/api/students
curl -X POST http://localhost:8080/api/students -H "Content-Type: application/json" -d "{\"full_name\":\"Asha Verma\",\"email\":\"asha@example.com\",\"course\":\"Civil\"}"
```

| Endpoint | Success | Errors |
|---|---|---|
| `GET /api/health` | 200 | — |
| `GET /api/students` | 200 `{students: [...]}` | 502 DB down, 503 DB not configured |
| `POST /api/students` | 201 `{message, student}` | 400 bad JSON, 409 duplicate email, 422 validation (`errors` per field) |

## Build the production image yourself

This is exactly what the `docker` job in CI does:

```bash
docker build -f backend-php/Dockerfile --target prod --build-arg APP_VERSION=my-test -t student-api-php .
docker run --rm -p 8081:8080 --env-file .env student-api-php
```

Open http://localhost:8081. The version badge now shows `my-test`.

## How the code is organised

```
Request → public/index.php → App (router) → StudentController
                                               ├─ StudentValidator   (pure logic)
                                               └─ StudentRepository  (interface)
                                                    └─ SupabaseStudentRepository → HttpClient → Supabase
```

Each arrow is a seam where tests plug in a fake: `InMemoryStudentRepository`
and `FakeHttpClient`. That's why the 28 unit tests run in under a second with
**no database**. This matters for CI, where a test that depends on the network
is a test that randomly fails.
