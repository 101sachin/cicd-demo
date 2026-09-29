-- Migration 001: students table
-- Run this once in Supabase -> SQL Editor (see docs/02-supabase-setup.md).
--
-- CI/CD lesson: database changes are versioned files in git, just like code.
-- Never change a migration that has already run; add 002_..., 003_... instead.

create table if not exists public.students (
    id          bigint generated always as identity primary key,
    full_name   text        not null check (char_length(full_name) between 2 and 100),
    email       text        not null unique check (char_length(email) <= 254),
    phone       text                 check (phone is null or char_length(phone) <= 20),
    course      text        not null,
    created_at  timestamptz not null default now()
);

create index if not exists students_created_at_idx on public.students (created_at desc);

-- Row Level Security ON with NO policies = the public "anon"/publishable key
-- can do nothing. Only our backend, using the SECRET key, can read/write.
-- The browser never talks to Supabase directly - it talks to our API.
alter table public.students enable row level security;
