# Lesson 6: Exercises (Break It to Learn It)

For each exercise, do it on a **feature branch + PR** and read the failing
pipeline log before fixing. Reading red pipelines is the core skill.

## Level 1 — Watch the gates work

1. **Style failure:** add `$x=1;` (no spaces) somewhere in `src/App.php`. Which job fails? Run `composer lint:fix` locally to auto-fix.
2. **Type failure:** in `StudentController::list()`, change `self::LIST_LIMIT` to `'50'`. Which tool catches it: PHPStan or PHPUnit?
3. **Test failure:** change the name length limit from 2 to 3 in `StudentValidator`. Which test fails, and why is that a *good* thing?
4. **Smoke-test failure:** in `docker/apache-vhost.conf`, remove the `Alias /api` line. The unit tests still pass! Which step catches it? (This is why we test the built artifact.)

## Level 2 — Extend the pipeline

5. Add a **coverage gate**: fail the build if line coverage drops below 80%. (Hint: `phpunit --coverage-clover` plus a small script, or `--coverage-text` with `grep`.)
6. Add a **scheduled** trigger (`on: schedule: - cron: '0 3 * * 1'`) so the pipeline runs every Monday even without pushes. Why is that useful? (Hint: dependencies and base images change.)
7. Add **Dependabot**: create `.github/dependabot.yml` for `composer` (in `/backend-php`), `docker` and `github-actions`.
8. Add a **security scan** of the Docker image with Trivy (`aquasecurity/trivy-action`) before pushing.

## Level 3 — Real-world patterns

9. **Staging environment:** create a second Supabase project and a second Render service. Deploy to *staging* automatically, then to *production* only after a manual approval.
10. **Integration test:** in CI, start a real Postgres + PostgREST with `services:` and run a test that actually inserts a row.
11. **E2E test:** add a Playwright job that opens the deployed staging URL, fills in the form and checks the success message.
12. **Database migration in the pipeline:** use the Supabase CLI (`supabase db push`) to apply `database/migrations` automatically before deploying.

## Level 4 — The Python backend (Phase 9)

13. Build `backend-python/` with FastAPI implementing **the same API contract** (see the comment at the top of `backend-php/src/App.php`).
14. Write `python-backend.yml` using `ruff` (lint), `mypy` (types) and `pytest` (tests), with `paths: ['backend-python/**']`.
15. Deploy it as a second Render service. Point the same `frontend/` at it. Nothing in the frontend changes.
