# Lesson 1: CI/CD Concepts

## The problem CI/CD solves

Without automation, a release looks like this:

> "It works on my laptop" → copy files to the server by FTP → something breaks
> → nobody knows which change caused it → a Friday-night rollback.

CI/CD turns that into a boring, repeatable, automatic process. Every change
goes through the **same checks** and the **same deployment steps**, every time.

## The three terms

| Term | Meaning | In this project |
|---|---|---|
| **Continuous Integration (CI)** | Every push is automatically built and tested, so broken code is caught within minutes. | `quality`, `test` and `docker` jobs |
| **Continuous Delivery** | Every change that passes CI is *ready* to release, but a human presses the button. | Would be the setting "Required reviewers" on the `production` environment |
| **Continuous Deployment** | Every change that passes CI is released to production *automatically*. | The `deploy` job (runs on every push to `main`) |

## Anatomy of a pipeline

```
Trigger  →  Stage  →  Job  →  Step
(push)      (CI)      (test)   (run phpunit)
```

- **Trigger (event):** what starts the pipeline, such as a push, a pull request, a schedule or a manual button.
- **Runner:** a fresh virtual machine (e.g. `ubuntu-latest`) that GitHub gives you for each job and then throws away.
- **Job:** a group of steps on one runner. Jobs run **in parallel** unless you connect them with `needs:`.
- **Step:** one command (`run:`) or one reusable action (`uses:`).
- **Artifact:** a file produced by the pipeline (test report, Docker image).
- **Secret:** an encrypted value (API key, deploy hook) that is injected at runtime and masked in logs.

## The 10 principles to remember

1. **Everything is in git.** Code, tests, Dockerfile, SQL migrations and even the pipeline itself (`.github/workflows/*.yml`).
2. **Fail fast.** Run the cheapest checks first (lint takes about 10 seconds), before the slow ones (build and deploy).
3. **Automate what you already do by hand.** Every pipeline step is a command you can run locally (`composer ci`).
4. **Build once, deploy many.** Build the Docker image one time, test *that* image, and deploy *that exact* image. Never rebuild for production.
5. **Immutable, traceable artifacts.** Each image is tagged with its git commit (`sha-1a2b3c4`), so you always know what's running.
6. **Configuration lives in the environment, not in the code.** The same image runs everywhere, and only env vars change (see the 12-Factor App).
7. **Secrets never go in git.** Pipeline secrets go in GitHub Secrets, runtime secrets in Render env vars, and local secrets in `.env` (git-ignored).
8. **Verify after deploying.** "Deploy succeeded" is not the same as "the app works". We call `/api/health` and check the version.
9. **Protect the main branch.** Changes arrive through pull requests, and CI must be green before merging.
10. **Keep the pipeline fast.** Use caching and parallel jobs. A 3-minute pipeline gets used; a 30-minute one gets bypassed.

## Types of automated checks (the "testing pyramid")

```
          /\        E2E tests      – slow, few (browser clicks the real form)
         /  \       Smoke tests    – "does the built container start and answer?"
        /    \      Integration    – real DB / real HTTP
       /      \     Unit tests     – fast, many, no I/O  ← most of ours
      /________\    Static checks  – lint + static analysis (no code executed)
```

This project has static checks, unit tests and smoke tests. Integration and
E2E tests are exercises for you in [06-exercises.md](06-exercises.md).

## Self-check

- Why does the `docker` job have `needs: [quality, test]`?
- Why does a pull request run CI but *not* deploy?
- Where does the Supabase secret key live, and why not in GitHub?

(The answers are in [04-pipeline-walkthrough.md](04-pipeline-walkthrough.md).)
