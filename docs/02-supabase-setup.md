# Lesson 2: Supabase Setup

Supabase is a hosted **PostgreSQL** database. It also generates a REST API
(PostgREST) for every table, and our PHP backend uses that API over HTTPS.
Because of this we don't need a PDO/Postgres driver, and the same approach will
work from Python.

## 1. Create the project

1. Go to https://supabase.com and sign in (GitHub login works).
2. Click **New project**.
   - Name: `student-registration`
   - Database password: generate one and save it in a password manager. We won't need it in the code.
   - Region: the one closest to you (e.g. *Mumbai / ap-south-1*).
3. Wait about 2 minutes for provisioning.

## 2. Create the table (run the migration)

1. Left sidebar → **SQL Editor** → **New query**.
2. Paste the contents of [`database/migrations/001_create_students.sql`](../database/migrations/001_create_students.sql).
3. Click **Run**. You should see "Success. No rows returned".
4. Left sidebar → **Table Editor** → you should now see a `students` table.

> **Concept: migrations as code.** The schema is a versioned file in git, not
> something you click together in a UI. Anyone (or any pipeline) can recreate
> the database from these files. The next schema change will be a new file,
> `002_...sql`.

## 3. Get the keys

Left sidebar → **Project Settings** → **API Keys** (and **Data API** for the URL).

| Value | Where it goes | Secret? |
|---|---|---|
| **Project URL** `https://xxxx.supabase.co` | `SUPABASE_URL` | No |
| **Secret key** `sb_secret_...` (or legacy **service_role** key) | `SUPABASE_SECRET_KEY` | **YES** |
| Publishable / anon key | not used | No |

> **Why the secret key, and why is it safe?** The secret key bypasses Row Level
> Security. That's fine **only on a server**. The browser never sees it,
> because the browser talks to *our API*, and our API talks to Supabase. The
> migration enables RLS with no policies, so the public key can't read or
> write anything.

## 4. Put the keys in `.env` (local only)

```bash
cp .env.example .env
```

Edit `.env`:

```
SUPABASE_URL=https://xxxx.supabase.co
SUPABASE_SECRET_KEY=sb_secret_xxxxxxxxxxxx
```

`.env` is in `.gitignore`, so it will never be committed. Later you'll enter the
**same two values** in the Render dashboard for production.

## 5. Quick test with curl (optional)

```bash
curl "https://xxxx.supabase.co/rest/v1/students?select=*" -H "apikey: sb_secret_xxxx"
```

`[]` means the connection works and the table is empty.
