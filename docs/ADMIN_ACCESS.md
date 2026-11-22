# Administrator Access Guide — EataLyft Technologies

**Date:** 2026-11-22  
**Project:** `D:\Git Projects\eatalyft-laravel`  
**Status:** Complete  
**Purpose:** Secure administrator account setup and access documentation

---

## 1. Administrator Login

### Login URL

| Environment | URL |
|-------------|-----|
| Local Development | `http://localhost/admin/login` |
| Staging | `https://staging.eatalyft.com/admin/login` |
| Production | `https://admin.eatalyft.com/admin/login` |

### Login Form Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Email | text | Yes | Administrator email address |
| Password | password | Yes | Administrator password |
| Remember me | checkbox | No | Extend session duration |

### Access Restrictions

- Only users with the `admin` or `superadmin` role can authenticate
- Customer, provider, and agency accounts are explicitly rejected
- Inactive accounts (`status !== 'active'`) are rejected
- Rate limiting: 5 attempts per minute per email address
- CSRF protection enabled on all forms

---

## 2. Local Administrator Credentials

### Environment Variables

Configure the following in your local `.env` file:

```env
ADMIN_NAME="EataLyft Administrator"
ADMIN_EMAIL="admin@eatalyft.local"
ADMIN_PASSWORD="CHANGE_THIS_TO_A_STRONG_UNIQUE_PASSWORD"
```

### Security Requirements

| Requirement | Description |
|-------------|-------------|
| Never hardcode passwords | Use environment variables only |
| Never commit credentials | `.env` is gitignored |
| Minimum password length | 8 characters |
| Change after first login | Required for security |
| Production credentials differ | Production requires separate provisioning |

### Example `.env` Entry

```
ADMIN_NAME="EataLyft Administrator"
ADMIN_EMAIL="admin@eatalyft.local"
ADMIN_PASSWORD="Str0ng!Passw0rd-2026"
```

---

## 3. Administrator Seeder

### Seeder Class

`database/seeders/AdminUserSeeder.php`

### Running the Seeder

```bash
php artisan db:seed --class=AdminUserSeeder
```

### Seeder Behavior

| Scenario | Behavior |
|----------|----------|
| Env vars missing | Skips gracefully with warning (does not break tests) |
| Admin already exists | Ensures role is correct; does not overwrite password |
| Admin does not exist | Creates new admin account with `admin` role |
| Multiple runs | Idempotent — no duplicate accounts created |

### Seeder Output

```
Administrator account created: admin@eatalyft.local (role: admin)
IMPORTANT: Change the administrator password after first login. Do not commit ADMIN_PASSWORD to version control.
```

Or if already exists:

```
Administrator account already exists: admin@eatalyft.local (role: admin)
```

---

## 4. Changing the Administrator Password

### After First Login

1. Log in as administrator at `/admin/login`
2. Navigate to `/settings` or user profile
3. Use the password change form
4. Confirm with current password

### Via Artisan Command (if needed)

```bash
php artisan tinker
```

```php
$admin = App\Models\User::where('email', 'admin@eatalyft.local')->first();
$admin->password = Hash::make('NewStr0ng!Passw0rd');
$admin->save();
```

---

## 5. Verifying Administrator Role and Permissions

### Check Role Assignment

```bash
php artisan tinker
```

```php
$admin = App\Models\User::where('email', 'admin@eatalyft.local')->first();
echo $admin->getRoleNames()->first(); // Should output: admin
echo $admin->isAdmin() ? 'true' : 'false'; // Should output: true
```

### Check Permissions

The `admin` role has all permissions except `system.admin` (reserved for `superadmin`).

### Verify in Database

```sql
SELECT u.name, u.email, u.status, r.name as role
FROM users u
JOIN model_has_roles mhr ON u.id = mhr.model_id
JOIN roles r ON mhr.role_id = r.id
WHERE u.email = 'admin@eatalyft.local';
```

---

## 6. Creating Additional Administrators

### Controlled Process

Additional administrators should be created through a controlled process:

1. Existing superadmin creates the user account via `/users/create`
2. Existing superadmin assigns the `admin` role via `/users/{id}/edit`
3. Account status is set to `active`
4. New admin logs in at `/admin/login`

### Never

- Never create administrator accounts through public registration
- Never assign `admin` role based on email address alone
- Never share administrator credentials
- Never use the same password across multiple admin accounts

---

## 7. Production vs Local Provisioning

| Aspect | Local Development | Production |
|--------|------------------|------------|
| Credentials | `.env` file (gitignored) | Secure secrets manager |
| Seeder | `php artisan db:seed --class=AdminUserSeeder` | Manual provisioning |
| Password | Example value in `.env.example` | Strong, unique, rotated |
| ADMIN_EMAIL | `admin@eatalyft.local` | Organization-specific domain |
| Provisioning | Automated via seeder | Explicit administrative process |
| MFA | Not required (local) | Recommended (production) |

### Production Provisioning Steps

1. Generate strong, unique administrator credentials
2. Store credentials in organization's secrets manager
3. Create administrator account via secure administrative process
4. Assign `superadmin` or `admin` role
5. Configure MFA
6. Rotate credentials on schedule
7. Never deploy production credentials via code or seeders

---

## 8. Security Best Practices

| Practice | Implementation |
|----------|----------------|
| Credential storage | Environment variables, never in code |
| Password hashing | Laravel's bcrypt hashing |
| Session security | Regenerate on login, invalidate on logout |
| Rate limiting | 5 attempts per minute per email |
| Account status | Checked on every login |
| Role enforcement | Server-side `isAdmin()` check |
| CSRF protection | Enabled on all forms |
| MFA | Recommended for production |

---

## 9. Troubleshooting

| Issue | Solution |
|-------|----------|
| Cannot log in | Check credentials, account status, role assignment |
| Seeder throws exception | Set `ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD` in `.env` |
| Seeder creates duplicate | Idempotent by design — check if account exists first |
| Admin redirected to login | Check account status is `active` |
| Permission denied | Verify `admin` or `superadmin` role is assigned |

---

## 10. Related Documents

| Document | Location | Purpose |
|----------|----------|---------|
| Phase 04 Implementation | `docs/PHASE_04_IMPLEMENTATION.md` | Full implementation details |
| Phase 04 Test Report | `docs/PHASE_04_TEST_REPORT.md` | Test results and coverage |
| Project Architecture | `docs/PROJECT_ARCHITECTURE.md` | System architecture reference |
| Route Inventory | `docs/ROUTE_INVENTORY.md` | Complete route listing |

---

*This document is part of the EataLyft platform documentation. It is preserved alongside Phase 00–04 documents.*