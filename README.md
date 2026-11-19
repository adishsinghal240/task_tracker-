# 📋 Internship Task Tracker (v2.0 Final)

A full-stack, lightweight, and modern PHP + MySQL web platform for interns and engineering mentors to manage tasks, track domain milestones, upload deliverables, and analyze productivity metrics.

---

## 🌟 Key Features

* **Role-Based Access Control (RBAC)**: Dedicated portals for **Interns** and **Managers/Mentors**.
* **Task CRUD & Milestones**: Create, read, edit, and delete tasks with priority (`Low`, `Medium`, `High`) and status (`Pending`, `In Progress`, `Completed`).
* **Domain Categories**: Tag tasks by specialization: `Development`, `Design`, `Testing`, `Research`, `Documentation`, `General`.
* **Deliverables & File Attachments**: Attach project deliverables (`.pdf`, `.png`, `.jpg`, `.zip`, `.docx`) or submit GitHub repository URLs.
* **Smart Due Reminders**: Visual indicators for `⚠️ Overdue (X days)`, `⏳ Due Today`, and `🔔 Due Soon (within 48h)`.
* **Dynamic Search & Multi-Criteria Filtering**: Filter instantly by keyword, intern, category, status, or priority.
* **Interactive Dashboard & Progress**: Visual cohort and individual task completion percentage bars.
* **CSV Reporting**: Export tasks and cohort reports directly to `.csv` format.
* **Dark & Light Mode**: User preference saved seamlessly in browser storage.
* **Automated System Diagnostics**: Built-in verification utility at `test_env.php`.

---

## ⚡ Quick Demo Accounts (Pre-Seeded)

| Role | Email Address | Password | Access Rights |
| :--- | :--- | :--- | :--- |
| **Manager / Admin** | `admin@tracker.com` | `Admin@123` | Full visibility, cohort analytics, assign tasks to any intern |
| **Intern** | `intern@tracker.com` | `Intern@123` | Personal dashboard, manage assigned tasks, attach deliverables |

*(You can also use the one-click **Quick Demo Login** buttons on the login screen).*

---

## 🚀 Deployment Option 1: Run with XAMPP (Standard)

### Step 1: Copy Project to htdocs
Copy the `Internship_Task_Tracker` folder into your XAMPP web root directory:
* **Windows**: `C:\xampp\htdocs\Internship_Task_Tracker`
* **macOS**: `/Applications/XAMPP/xamppfiles/htdocs/Internship_Task_Tracker`
* **Linux**: `/opt/lampp/htdocs/Internship_Task_Tracker`

### Step 2: Start Services
Open the **XAMPP Control Panel** and start both:
* **Apache**
* **MySQL**

### Step 3: Import Database
1. Open your browser and navigate to: `http://localhost/phpmyadmin/`
2. Click **New** on the left sidebar and enter database name: `internship_task_tracker` (Collation: `utf8mb4_unicode_ci`).
3. Click the newly created database, navigate to the **Import** tab at the top.
4. Click **Choose File**, select `database/internship_tracker.sql` from the project folder, and click **Import** (or **Go**).
5. *(Optional)* If upgrading an existing installation, import `database/upgrade_v2.sql`.

### Step 4: Open the Application
Navigate to:
```
http://localhost/Internship_Task_Tracker/
```
To run automated diagnostic checks, visit:
```
http://localhost/Internship_Task_Tracker/test_env.php
```

---

## 🐳 Deployment Option 2: Run with Docker (1-Command Setup)

If you have **Docker** and **Docker Compose** installed:

1. Open a terminal inside the `Internship_Task_Tracker` folder:
   ```bash
   docker compose up -d
   ```
2. Once the containers are running:
   * **Web Application**: Open [http://localhost:8080](http://localhost:8080)
   * **phpMyAdmin**: Open [http://localhost:8081](http://localhost:8081) (Server: `db`, User: `root`, Password: `rootpassword`)
   * **MySQL Port**: `3307` mapped to host
3. To stop containers:
   ```bash
   docker compose down
   ```

---

## ⚙️ Configuration & Environment Variables

Database settings are defined in `includes/db.php`. It automatically adapts to both XAMPP defaults and Docker environment variables:

| Variable | Description | Default (XAMPP) | Docker Setting |
| :--- | :--- | :--- | :--- |
| `DB_HOST` | Database Hostname | `localhost` | `db` |
| `DB_PORT` | MySQL Port | `3306` | `3306` |
| `DB_USER` | MySQL Username | `root` | `root` |
| `DB_PASS` | MySQL Password | *(empty)* | `rootpassword` |
| `DB_NAME` | Database Name | `internship_task_tracker` | `internship_task_tracker` |

---

## 🛠️ Common Troubleshooting

### 1. Apache Port 80 Conflict (XAMPP)
If Apache fails to start because port 80 is occupied (e.g. by Skype, IIS, or VMware):
* Open XAMPP Control Panel &rarr; Click **Config** next to Apache &rarr; Open `httpd.conf`.
* Search for `Listen 80` and change to `Listen 8080`.
* Access your project via: `http://localhost:8080/Internship_Task_Tracker/`.

### 2. File Upload Limits
If uploading larger attachments fails:
* Open `php.ini` in XAMPP:
  ```ini
  upload_max_filesize = 10M
  post_max_size = 12M
  ```
* Restart Apache.

### 3. Folder Permissions on Linux / macOS
Ensure the `uploads/` folder is writable by the web server:
```bash
chmod -R 777 uploads/
```
