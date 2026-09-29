# Lesson 4: The Pipeline, Line by Line

File: [`.github/workflows/php-backend.yml`](../.github/workflows/php-backend.yml)

```
push / PR ─▶ quality ─────┐
             test (8.2) ──┼─▶ docker ─▶ deploy
             test (8.3) ──┘   (build, smoke, push)   (main only)
```

## 1. `on:` — triggers

```yaml
on:
  push:          { branches: [main], paths: [...] }
  pull_request:  { branches: [main], paths: [...] }
  workflow_dispatch:
```

- **push to main** → full pipeline, including deploy.
- **pull_request** → CI only (quality, test, docker build and smoke test). Nothing is pushed or deployed. You get feedback *before* merging.
- **paths** → a change in `backend-python/` or `docs/` won't trigger the PHP pipeline. This is how monorepos stay fast.
- **workflow_dispatch** → a manual "Run workflow" button.

## 2. `concurrency:`

If you push twice quickly to a PR, the first run is cancelled. On `main` we
*don't* cancel (`cancel-in-progress` is false for pushes), so a deploy is never
killed halfway through.

## 3. `permissions: contents: read`

The auto-generated `GITHUB_TOKEN` gets the **minimum** rights. Only the `docker`
job raises it to `packages: write`, because that's the only job that needs it.
This is the *principle of least privilege*.

## 4. Job `quality`

| Step | Why |
|---|---|
| `actions/checkout` | The runner starts empty, so we clone the repo first. |
| `setup-php` | Installs PHP 8.3 on the runner. |
| cache | Saves `~/.composer/cache` keyed by a hash of `composer.lock`. If the lock file doesn't change, dependencies download from cache and take seconds instead of minutes. |
| `composer validate --strict` | Catches a `composer.json` that was edited without updating the lock. |
| `php -l` | A syntax error is the cheapest possible failure to catch. |
| `composer lint` | PSR-12 style, so code reviews discuss logic, not spaces. |
| `composer analyse` | PHPStan level 8 finds type bugs without running code. (It caught a real one while we built this project!) |

## 5. Job `test` — the matrix

```yaml
strategy:
  fail-fast: false
  matrix:
    php: ['8.2', '8.3']
```

One job definition becomes **two parallel jobs**. `composer.json` says we
support PHP ≥ 8.2, and the matrix proves it. `upload-artifact` with
`if: always()` saves the JUnit report even when tests fail, which is exactly
when you need it. (Find it on the run page → *Artifacts*.)

**`quality` and `test` have no `needs:`, so they run at the same time.**

## 6. Job `docker` — build once, test the artifact

- `needs: [quality, test]` makes this a **gate**: no image is built from code that failed checks.
- `--build-arg APP_VERSION=${{ github.sha }}` bakes the commit SHA into the image.
- Tags: `sha-1a2b3c4` is immutable and traceable. `latest` is just a convenience pointer.
- **Smoke test:** we `docker run` the *real production image* and assert:
  - `/api/health` returns our commit SHA
  - the HTML form is served
  - `POST /api/students` returns 503 (no DB in CI), which proves routing works and the app doesn't crash
  - `/src/App.php` is **not** downloadable (a security check)
- `if: failure()` prints container logs only when something broke.
- Login and push **only** happen on `push` to `main`. PR images are built and tested, then thrown away.

## 7. Job `deploy` — continuous deployment

- `environment: production` → GitHub tracks deployments, shows the URL, and can require a **manual approval** (Settings → Environments → Required reviewers). Turning that on changes Continuous *Deployment* into Continuous *Delivery*.
- The **deploy hook** is a secret URL from Render. We add `&imgURL=<our image>` so Render deploys **the exact image we just tested**, not "whatever is latest".
- **Verify:** poll `/api/health` until `version == github.sha`. The pipeline goes green only when production is *actually running the new code*.
- If Render isn't configured yet, the job prints a warning and skips instead of failing.

## Secrets: who knows what?

| Secret | Stored in | Used by |
|---|---|---|
| `GITHUB_TOKEN` | Auto-created by GitHub for each run | `docker` job, to push to GHCR |
| `RENDER_DEPLOY_HOOK_URL` | GitHub → Settings → Secrets | `deploy` job, to trigger a deploy |
| `SUPABASE_SECRET_KEY` | **Render** → Environment | The running app only |

The pipeline **never sees the database key**. It doesn't need it, so it
doesn't get it. If the pipeline were ever compromised, your data would still be safe.

## Answers to the Lesson 1 self-check

- `needs: [quality, test]` → don't waste time building (or ever ship) code that fails checks.
- PRs don't deploy → the `if: github.event_name == 'push' && github.ref == 'refs/heads/main'` condition.
- Supabase key → in Render (the runtime), because only the running app talks to the DB.
