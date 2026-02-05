# 📊 RANCANGAN DATABASE TASK MANAGER KANBAN

Aplikasi task manager dengan fitur kanban board yang dapat di-share ke multiple user dengan role-based permissions.

---

## 📑 DAFTAR TABEL

| No | Nama Tabel | Fungsi |
|:--:|-----------|--------|
| 1 | `users` | Menyimpan data pengguna aplikasi |
| 2 | `projects` | Menyimpan data project/workspace |
| 3 | `project_members` | Relasi user dengan project (sharing & permissions) |
| 4 | `boards` | Menyimpan board kanban dalam project |
| 5 | `columns` | Menyimpan kolom dalam board kanban |
| 6 | `tasks` | Menyimpan task/card dalam kolom |
| 7 | `task_comments` | Menyimpan komentar pada task |
| 8 | `activity_logs` | Menyimpan audit trail aktivitas (optional) |

---

## 🗄️ DETAIL SETIAP TABEL

### 1️⃣ TABEL: `users`
**Fungsi:** Menyimpan data pengguna aplikasi

| Kolom | Tipe Data | Nullable | Keterangan |
|-------|-----------|----------|-----------|
| `id` | BIGINT | ✗ | Primary Key |
| `name` | VARCHAR(255) | ✗ | Nama lengkap user |
| `email` | VARCHAR(255) | ✗ | Email untuk login (UNIQUE) |
| `email_verified_at` | TIMESTAMP | ✓ | Verifikasi email |
| `password` | VARCHAR(255) | ✗ | Password terenkripsi |
| `avatar` | VARCHAR(255) | ✓ | Path foto profil |
| `is_active` | BOOLEAN | ✗ | Status aktif/tidak aktif user |
| `created_at` | TIMESTAMP | ✗ | Waktu dibuat |
| `updated_at` | TIMESTAMP | ✗ | Waktu diupdate |
| `deleted_at` | TIMESTAMP | ✓ | Soft delete |

**Primary Key:** `id`  
**Unique:** `email`  
**Soft Delete:** Ya  
**Catatan:** Base table untuk semua user di aplikasi

---

### 2️⃣ TABEL: `projects`
**Fungsi:** Menyimpan data project/workspace

| Kolom | Tipe Data | Nullable | Keterangan |
|-------|-----------|----------|-----------|
| `id` | BIGINT | ✗ | Primary Key |
| `name` | VARCHAR(255) | ✗ | Nama project |
| `slug` | VARCHAR(255) | ✗ | URL-friendly slug (UNIQUE) |
| `description` | TEXT | ✓ | Deskripsi project |
| `owner_id` | BIGINT | ✗ | FK ke users (pembuat project) |
| `is_archived` | BOOLEAN | ✗ | Status archived project |
| `color` | VARCHAR(7) | ✓ | Warna project dalam format hex |
| `icon` | VARCHAR(255) | ✓ | Icon project |
| `created_at` | TIMESTAMP | ✗ | Waktu dibuat |
| `updated_at` | TIMESTAMP | ✗ | Waktu diupdate |
| `deleted_at` | TIMESTAMP | ✓ | Soft delete |

**Primary Key:** `id`  
**Foreign Key:** `owner_id` → `users.id`  
**Unique:** `slug`  
**Index:** `owner_id`, `is_archived`  
**Soft Delete:** Ya  
**Catatan:** Satu user bisa membuat banyak project

---

### 3️⃣ TABEL: `project_members` ⭐
**Fungsi:** Relasi user dengan project (untuk sharing & permissions)

| Kolom | Tipe Data | Nullable | Keterangan |
|-------|-----------|----------|-----------|
| `id` | BIGINT | ✗ | Primary Key |
| `project_id` | BIGINT | ✗ | FK ke projects |
| `user_id` | BIGINT | ✗ | FK ke users |
| `role` | ENUM | ✗ | owner, admin, member, viewer |
| `joined_at` | TIMESTAMP | ✗ | Tanggal user join project |
| `created_at` | TIMESTAMP | ✗ | Waktu dibuat |
| `updated_at` | TIMESTAMP | ✗ | Waktu diupdate |

**Primary Key:** `id`  
**Foreign Keys:** `project_id` → `projects.id`, `user_id` → `users.id`  
**Unique:** `(project_id, user_id)`  
**Index:** `project_id`, `user_id`, `role`  
**Soft Delete:** Tidak  

**Role dan Permissions:**
- `owner` → Full access (manage project, members, tasks, delete project)
- `admin` → Manage members, edit project, delete tasks
- `member` → Create/edit own task, create comment, assign diri sendiri
- `viewer` → Read-only (hanya view project, board, task)

**Catatan:** Tabel ini mengatur sharing project ke user lain

---

### 4️⃣ TABEL: `boards`
**Fungsi:** Menyimpan board kanban dalam project

| Kolom | Tipe Data | Nullable | Keterangan |
|-------|-----------|----------|-----------|
| `id` | BIGINT | ✗ | Primary Key |
| `project_id` | BIGINT | ✗ | FK ke projects |
| `name` | VARCHAR(255) | ✗ | Nama board |
| `slug` | VARCHAR(255) | ✗ | URL-friendly slug |
| `description` | TEXT | ✓ | Deskripsi board |
| `is_default` | BOOLEAN | ✗ | Board default yang ditampilkan pertama |
| `position` | INT | ✗ | Urutan board (0, 1, 2, ...) |
| `created_at` | TIMESTAMP | ✗ | Waktu dibuat |
| `updated_at` | TIMESTAMP | ✗ | Waktu diupdate |
| `deleted_at` | TIMESTAMP | ✓ | Soft delete |

**Primary Key:** `id`  
**Foreign Key:** `project_id` → `projects.id`  
**Unique:** `(project_id, slug)`  
**Index:** `project_id`, `is_default`, `position`  
**Soft Delete:** Ya  

**Catatan:** 
- Satu project bisa punya multiple boards
- Setiap project harus punya minimal 1 board default
- Contoh: Board "Backlog", Board "Sprint 1", Board "Roadmap"

---

### 5️⃣ TABEL: `columns`
**Fungsi:** Menyimpan kolom dalam board kanban (To Do, In Progress, Done, dll)

| Kolom | Tipe Data | Nullable | Keterangan |
|-------|-----------|----------|-----------|
| `id` | BIGINT | ✗ | Primary Key |
| `board_id` | BIGINT | ✗ | FK ke boards |
| `name` | VARCHAR(255) | ✗ | Nama kolom |
| `slug` | VARCHAR(255) | ✗ | URL-friendly slug |
| `color` | VARCHAR(7) | ✓ | Warna kolom dalam format hex |
| `position` | INT | ✗ | Urutan kolom (0, 1, 2, ...) |
| `created_at` | TIMESTAMP | ✗ | Waktu dibuat |
| `updated_at` | TIMESTAMP | ✗ | Waktu diupdate |
| `deleted_at` | TIMESTAMP | ✓ | Soft delete |

**Primary Key:** `id`  
**Foreign Key:** `board_id` → `boards.id`  
**Unique:** `(board_id, slug)`  
**Index:** `board_id`, `position`  
**Soft Delete:** Ya  

**Catatan:**
- Struktur kanban board (dari kiri ke kanan)
- Contoh urutan default:
  - Position 0: "To Do" (kolom pertama)
  - Position 1: "In Progress" (kolom kedua)
  - Position 2: "Review" (kolom ketiga)
  - Position 3: "Done" (kolom keempat)

---

### 6️⃣ TABEL: `tasks` ⭐⭐
**Fungsi:** Menyimpan task/card dalam kolom kanban

| Kolom | Tipe Data | Nullable | Keterangan |
|-------|-----------|----------|-----------|
| `id` | BIGINT | ✗ | Primary Key |
| `column_id` | BIGINT | ✗ | FK ke columns (kolom tempat task berada) |
| `title` | VARCHAR(255) | ✗ | Judul task |
| `description` | LONGTEXT | ✓ | Deskripsi detail task |
| `position` | INT | ✗ | Urutan task dalam kolom |
| `priority` | ENUM | ✗ | low, medium, high, urgent |
| `status` | ENUM | ✗ | open, in_progress, completed, blocked |
| `due_date` | DATE | ✓ | Tanggal deadline |
| `start_date` | DATE | ✓ | Tanggal mulai task |
| `estimated_hours` | DECIMAL | ✓ | Estimasi jam kerja |
| `actual_hours` | DECIMAL | ✓ | Aktual jam kerja (time tracking) |
| `created_by` | BIGINT | ✗ | FK ke users (pembuat task) |
| `created_at` | TIMESTAMP | ✗ | Waktu dibuat |
| `updated_at` | TIMESTAMP | ✗ | Waktu diupdate |
| `deleted_at` | TIMESTAMP | ✓ | Soft delete |

**Primary Key:** `id`  
**Foreign Keys:** `column_id` → `columns.id`, `created_by` → `users.id`  
**Index:** `column_id`, `created_by`, `priority`, `status`, `due_date`, `position`  
**Soft Delete:** Ya  

**Priority Levels:** low < medium < high < urgent  
**Status Values:** open, in_progress, completed, blocked  

**Catatan:**
- Task dapat di-drag antar kolom (kanban card)
- Mendukung time tracking dengan estimated & actual hours
- Task dibuat oleh satu user (created_by)

---

### 7️⃣ TABEL: `task_comments`
**Fungsi:** Menyimpan komentar pada task untuk kolaborasi

| Kolom | Tipe Data | Nullable | Keterangan |
|-------|-----------|----------|-----------|
| `id` | BIGINT | ✗ | Primary Key |
| `task_id` | BIGINT | ✗ | FK ke tasks |
| `user_id` | BIGINT | ✗ | FK ke users (user yang komentar) |
| `comment` | LONGTEXT | ✗ | Isi komentar |
| `is_edited` | BOOLEAN | ✗ | Flag apakah komentar sudah diedit |
| `edited_at` | TIMESTAMP | ✓ | Waktu komentar diedit |
| `created_at` | TIMESTAMP | ✗ | Waktu dibuat |
| `updated_at` | TIMESTAMP | ✗ | Waktu diupdate |
| `deleted_at` | TIMESTAMP | ✓ | Soft delete |

**Primary Key:** `id`  
**Foreign Keys:** `task_id` → `tasks.id`, `user_id` → `users.id`  
**Index:** `task_id`, `user_id`, `created_at`  
**Soft Delete:** Ya  

**Catatan:**
- Fasilitasi kolaborasi dan diskusi pada task
- Tracking komentar yang sudah diedit

---

### 8️⃣ TABEL: `activity_logs` (OPSIONAL)
**Fungsi:** Menyimpan audit trail/log aktivitas untuk tracking perubahan

| Kolom | Tipe Data | Nullable | Keterangan |
|-------|-----------|----------|-----------|
| `id` | BIGINT | ✗ | Primary Key |
| `project_id` | BIGINT | ✗ | FK ke projects |
| `user_id` | BIGINT | ✗ | FK ke users (user yang melakukan aksi) |
| `action` | VARCHAR(100) | ✗ | Jenis aksi: created, updated, deleted, assigned, commented |
| `entity_type` | VARCHAR(100) | ✗ | Tipe entity: task, comment, member, board, column |
| `entity_id` | BIGINT | ✗ | ID dari entity yang berubah |
| `description` | TEXT | ✓ | Deskripsi activity dalam bahasa manusia |
| `old_values` | JSON | ✓ | Nilai lama dalam format JSON (untuk update tracking) |
| `new_values` | JSON | ✓ | Nilai baru dalam format JSON (untuk update tracking) |
| `created_at` | TIMESTAMP | ✗ | Waktu log dicatat |

**Primary Key:** `id`  
**Foreign Keys:** `project_id` → `projects.id`, `user_id` → `users.id`  
**Index:** `project_id`, `user_id`, `entity_type`, `created_at`  
**Soft Delete:** Tidak (log harus permanent)  

**Contoh Activity:**
- User A membuat task "Fix bug login"
- User B assign task ke User C
- User C update status task ke "In Progress"
- User A add comment "Cek apakah sudah tested"
- User B delete task "Fix bug login"

**Catatan:**
- Untuk audit trail dan tracking perubahan
- Bisa menampilkan history activity di project dashboard

---

## 🔗 RELASI ANTAR TABEL

```
users (1)
  ├─────→ projects (1:N) via owner_id
  ├─────→ project_members (N:M via junction) 
  ├─────→ tasks (1:N) via created_by
  ├─────→ task_comments (1:N) via user_id
  └─────→ activity_logs (1:N) via user_id

projects (1)
  ├─────→ project_members (1:N)
  ├─────→ boards (1:N)
  └─────→ activity_logs (1:N)

boards (1)
  └─────→ columns (1:N)

columns (1)
  └─────→ tasks (1:N)

tasks (1)
  ├─────→ task_comments (1:N)
  └─────→ activity_logs (1:N)
```

---

## 👥 PERMISSION MATRIX

| Action | Owner | Admin | Member | Viewer |
|--------|:-----:|:-----:|:-------:|:------:|
| View project | ✅ | ✅ | ✅ | ✅ |
| Edit project | ✅ | ✅ | ❌ | ❌ |
| Delete project | ✅ | ❌ | ❌ | ❌ |
| Manage members | ✅ | ✅ | ❌ | ❌ |
| Create board | ✅ | ✅ | ❌ | ❌ |
| Edit board | ✅ | ✅ | ❌ | ❌ |
| Delete board | ✅ | ✅ | ❌ | ❌ |
| Create task | ✅ | ✅ | ✅ | ❌ |
| Edit own task | ✅ | ✅ | ✅ | ❌ |
| Edit other task | ✅ | ✅ | ❌ | ❌ |
| Delete task | ✅ | ✅ | ❌ | ❌ |
| Create comment | ✅ | ✅ | ✅ | ❌ |
| Delete comment | ✅ | ✅ | Own* | ❌ |

*Member hanya bisa delete komentar milik sendiri

---

## 📊 DIAGRAM STRUKTUR KANBAN

```
PROJECT
├── BOARD 1 (Default)
│   ├── COLUMN 0: "To Do"
│   │   ├── TASK: "Judul task 1"
│   │   ├── TASK: "Judul task 2"
│   │   └── TASK: "Judul task 3"
│   ├── COLUMN 1: "In Progress"
│   │   ├── TASK: "Judul task 4"
│   │   └── TASK: "Judul task 5"
│   ├── COLUMN 2: "Review"
│   │   └── TASK: "Judul task 6"
│   └── COLUMN 3: "Done"
│       ├── TASK: "Judul task 7"
│       └── TASK: "Judul task 8"
│
└── BOARD 2 (Sprint Planning)
    ├── COLUMN 0: "Backlog"
    ├── COLUMN 1: "Planned"
    └── COLUMN 2: "Completed"
```

---

## 📈 SKALABILITAS & PERFORMA

### Indexes untuk Performa:
- User lookup: `email`
- Project lookup: `owner_id`, `is_archived`, `slug`
- Member lookup: `project_id`, `user_id`, `role`
- Board lookup: `project_id`, `is_default`
- Column lookup: `board_id`, `position`
- Task lookup: `column_id`, `priority`, `status`, `due_date`
- Assignment lookup: `task_id`, `user_id`
- Comment lookup: `task_id`, `created_at`

### Soft Delete Strategy:
- Kolom `deleted_at` pada tabel utama untuk data recovery
- User tidak akan melihat data yang sudah di-soft delete
- Implementasi di level query dengan `WHERE deleted_at IS NULL`
- Tabel dengan soft delete: users, projects, boards, columns, tasks, task_comments

### Cascading Delete Rules:
- `project_members` dihapus jika `projects` dihapus
- `boards` dihapus jika `projects` dihapus
- `columns` dihapus jika `boards` dihapus
- `tasks` dihapus jika `columns` dihapus
- `task_comments` dihapus jika `tasks` dihapus

---

## 🎯 FITUR UTAMA

✅ **Multi-User Collaboration** - Share project ke multiple user  
✅ **Role-Based Access Control** - Owner, Admin, Member, Viewer  
✅ **Kanban Board** - Drag & drop tasks antar kolom  
✅ **Flexible Columns** - Customizable workflow columns  
✅ **Task Management** - Priority, deadline, status tracking  
✅ **Time Tracking** - Estimated vs actual hours  
✅ **Comments & Discussion** - Kolaborasi via komentar  
✅ **Activity Log** - Audit trail untuk tracking perubahan  
✅ **Soft Delete** - Data can be recovered  
✅ **Multiple Boards** - Berbeda workflow per board  

---

## 🚀 IMPLEMENTASI DI LARAVEL 12

Database ini dirancang optimal untuk Laravel 12:
- Foreign key constraints untuk data integrity
- Soft deletes untuk safe deletion
- Proper indexing untuk query performance
- Timestamps untuk audit trail
- Role-based permissions siap untuk Gates & Policies
- Eloquent relationships siap diimplementasikan

---

**Total: 8 Tabel | Relasi: 16 | Soft Delete: 6 Tabel | Optional: 1 Tabel**
