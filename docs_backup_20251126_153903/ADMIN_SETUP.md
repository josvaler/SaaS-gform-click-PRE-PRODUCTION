# Admin User Setup Guide

## Overview

Admin users in this system are **manually assigned via SQL** - there is no UI for role changes. This is a security feature to prevent unauthorized privilege escalation.

## How to Activate an Admin User

### Step 1: Find the User ID

You need to identify the user you want to make an admin. You can do this by:

**Option A: Find by Email**
```sql
SELECT id, email, name, role FROM users WHERE email = 'user@example.com';
```

**Option B: List All Users**
```sql
SELECT id, email, name, role, plan FROM users ORDER BY id;
```

**Option C: Find by Google ID**
```sql
SELECT id, email, name, role FROM users WHERE google_id = 'google_id_here';
```

### Step 2: Update User Role to ADMIN

Once you have the user ID, run:

```sql
UPDATE users SET role = 'ADMIN' WHERE id = YOUR_USER_ID;
```

**Example:**
```sql
-- Make user with ID 1 an admin
UPDATE users SET role = 'ADMIN' WHERE id = 1;

-- Make user with email admin@example.com an admin
UPDATE users SET role = 'ADMIN' WHERE email = 'admin@example.com';
```

### Step 3: Verify the Change

Check that the role was updated:

```sql
SELECT id, email, name, role FROM users WHERE id = YOUR_USER_ID;
```

The `role` column should now show `ADMIN` instead of `USER`.

### Step 4: Log Out and Log Back In

The user must **log out and log back in** for the session to refresh with the new admin role. The `require_admin()` function checks the role directly from the database, not from the session.

## Database Connection

Based on your setup, use these credentials:

```bash
# Connect to MySQL/MariaDB
mysql -u gforms_user -p'StrongMenNeverGiveItUp!' backend_gforms
```

Or use the environment variables:
- **Database**: `backend_gforms`
- **User**: `gforms_user`
- **Password**: `StrongMenNeverGiveItUp!`
- **Host**: `localhost`

## Quick SQL Script

Here's a complete script to make a user admin by email:

```sql
-- Replace 'admin@example.com' with the actual email
UPDATE users 
SET role = 'ADMIN' 
WHERE email = 'admin@example.com';

-- Verify
SELECT id, email, name, role, plan 
FROM users 
WHERE email = 'admin@example.com';
```

## Removing Admin Access

To remove admin privileges:

```sql
UPDATE users SET role = 'USER' WHERE id = YOUR_USER_ID;
```

## Security Notes

1. **No UI for Role Changes**: Admin assignment is intentionally manual via SQL only
2. **Database Check**: The `require_admin()` function checks the role directly from the database on every request
3. **Session Refresh**: Users must log out and log back in after role changes
4. **Access Control**: Only users with `role = 'ADMIN'` can access `/admin` route

## Admin Features

Once a user has the ADMIN role, they can:
- Access the Admin Panel at `/admin`
- Search users by IP address
- Search users by Google ID
- Assign ENTERPRISE plans to users
- View all users and their login logs

## Troubleshooting

**User still can't access admin panel after role change:**
1. Verify the role in database: `SELECT role FROM users WHERE id = X;`
2. Make sure the user logged out and logged back in
3. Check browser cache - try incognito/private mode
4. Verify the user is accessing `/admin` route

**Check current admin users:**
```sql
SELECT id, email, name, role FROM users WHERE role = 'ADMIN';
```

