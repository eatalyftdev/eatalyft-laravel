-- EataLyft shared media storage for dashboard ads, promos, service cards and admin uploads.
-- Apply in Supabase SQL Editor or through the migration runner.

insert into storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
values (
  'media',
  'media',
  true,
  10485760,
  array['image/jpeg','image/png','image/webp','image/gif','image/avif','application/pdf']
)
on conflict (id) do update set
  public = excluded.public,
  file_size_limit = excluded.file_size_limit,
  allowed_mime_types = excluded.allowed_mime_types;

create table if not exists public.media_assets (
  id uuid primary key default gen_random_uuid(),
  bucket_id text not null default 'media',
  object_key text not null unique,
  public_url text not null,
  original_name text,
  mime_type text,
  size_bytes bigint,
  folder text not null default 'general',
  owner_uid text,
  firestore_collection text,
  firestore_doc_id text,
  firestore_field text,
  metadata jsonb not null default '{}'::jsonb,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create index if not exists media_assets_folder_created_idx
  on public.media_assets (folder, created_at desc);
create index if not exists media_assets_firestore_target_idx
  on public.media_assets (firestore_collection, firestore_doc_id);

alter table public.media_assets enable row level security;

drop policy if exists media_assets_public_read on public.media_assets;
create policy media_assets_public_read
  on public.media_assets for select
  using (true);

-- Writes are performed by the server with the Supabase service role.
drop policy if exists media_assets_service_write on public.media_assets;
create policy media_assets_service_write
  on public.media_assets for all
  using (false)
  with check (false);

drop policy if exists media_bucket_public_read on storage.objects;
create policy media_bucket_public_read
  on storage.objects for select
  using (bucket_id = 'media');

-- Uploads, updates and deletes are server-side only through the service role.
drop policy if exists media_bucket_service_write on storage.objects;
create policy media_bucket_service_write
  on storage.objects for all
  using (false)
  with check (false);

comment on table public.media_assets is 'Server-managed Supabase media metadata with optional Firestore document link.';
