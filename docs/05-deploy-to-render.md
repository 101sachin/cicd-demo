# Lesson 5: Push to GitHub & Deploy to Render

## 1. Push the project to GitHub

1. Create a new **empty** repository on github.com (no README, no .gitignore), e.g. `cicd-demo`. Make it **public** so Actions minutes are unlimited.
2. In this folder:

```bash
git init -b main
```
```bash
git add .
```
```bash
git commit -m "Initial commit: PHP student registration API with CI/CD"
```
```bash
git remote add origin https://github.com/<your-username>/cicd-demo.git
```
```bash
git push -u origin main
```

3. Open the repo → **Actions** tab. Watch the pipeline run.
   - `quality`, `test (8.2)` and `test (8.3)` run in parallel.
   - `docker` builds, smoke-tests and pushes to GHCR.
   - `deploy` shows a ⚠️ warning ("Render is not configured yet"). That's expected.

## 2. Make the container image public

Render needs to pull your image.

1. GitHub → your profile → **Packages** → `student-api-php`.
2. **Package settings** → *Danger Zone* → **Change visibility** → Public.

(Alternative: keep it private and add a GitHub token as a *registry credential* in Render.)

## 3. Create the Render service

1. https://render.com → sign in with GitHub.
2. **New +** → **Web Service** → **Existing image**.
3. Image URL: `ghcr.io/<your-username-lowercase>/student-api-php:latest`
4. Name: `student-api-php`, Region: Singapore (closest to India), Instance: **Free**.
5. **Environment Variables:**
   - `SUPABASE_URL` = your project URL
   - `SUPABASE_SECRET_KEY` = your secret key
6. **Advanced** → Health Check Path: `/api/health`
7. Create the service. After a minute, open `https://student-api-php-xxxx.onrender.com`.

> Free Render services sleep after 15 minutes of inactivity, so the first
> request can take about 50 seconds. That's normal.

## 4. Connect GitHub Actions → Render

1. Render → your service → **Settings** → **Deploy Hook** → copy the URL. Treat it as a password.
2. GitHub repo → **Settings** → **Secrets and variables** → **Actions**:
   - **Secrets** tab → New secret: `RENDER_DEPLOY_HOOK_URL` = the hook URL
   - **Variables** tab → New variable: `RENDER_APP_URL` = `https://student-api-php-xxxx.onrender.com` (no trailing slash)
3. (Recommended) Render → Settings → **Auto-Deploy: Off**. From now on, *only the pipeline* deploys.

## 5. Watch continuous deployment happen

Change something visible, e.g. the `<h1>` in `frontend/index.html`, then:

```bash
git commit -am "Change page title"
```
```bash
git push
```

In the Actions tab, the `deploy` job should end with
**✅ Production is running `abc1234`**. Refresh your Render URL: the badge
shows the same short SHA.

## 6. Protect `main` (team workflow)

GitHub repo → **Settings** → **Branches** (or **Rules → Rulesets**) → add a rule for `main`:

- ✅ Require a pull request before merging
- ✅ Require status checks to pass: `Lint & static analysis`, `Unit tests (PHP 8.2)`, `Unit tests (PHP 8.3)`, `Build, smoke-test & publish image`

Now try it:

```bash
git switch -c feature/new-course
```

Add a course to `StudentValidator::COURSES` **and** to the `<select>` in `index.html`, then commit, push the branch and open a PR. CI runs and tells you whether it's safe to merge. Merging triggers the deploy.

## 7. (Optional) Require a manual approval before production

Settings → **Environments** → `production` → **Required reviewers** → add yourself.
The `deploy` job now pauses until you click **Approve**. That's Continuous *Delivery*.

## Rollback

Every image is kept in GHCR with its SHA tag. To roll back, pick an older
`sha-xxxxxxx` tag and deploy it (Render → Manual Deploy → *Deploy an image*),
or `git revert` the bad commit and push. That second option is preferred,
because git remains the source of truth.
