# Citizen Issue Reporting System (CIRS)

A modern, responsive, and secure civic grievance redressal web application built with **Core PHP**, **MySQL**, **Bootstrap 5**, **JavaScript**, **Leaflet.js (OpenStreetMap)**, and **Chart.js**.

---

## 📌 Project Overview

**Citizen Issue Reporting System (CIRS)** is designed to bridge the communication gap between citizens and local municipal authorities. It provides an intuitive online platform for residents to report public infrastructure and civic problems (such as potholes, overflowing garbage bins, water supply leaks, broken streetlights, and open drainage) and track real-time resolution progress on an interactive visual timeline.

---

## 🛠️ Mandatory Technology Stack

| Component | Technology |
| :--- | :--- |
| **Frontend UI** | HTML5, CSS3, Bootstrap 5.3, Bootstrap Icons |
| **Interactivity & Maps** | Vanilla JavaScript, Leaflet.js (Free OpenStreetMap API) |
| **Data Analytics** | Chart.js 4.4 |
| **Backend** | Core / Plain PHP (with PDO Prepared Statements) |
| **Database** | MySQL / MariaDB |
| **Server** | XAMPP (Apache + MySQL) |

---

## 📂 Project Folder Structure

```text
Citizen Issue Reporting System/
│
├── index.php                      # Modern civic landing page with hero, live stats & categories
├── login.php                      # Citizen login with password toggle & CSRF protection
├── register.php                   # Citizen registration with validation & OTP trigger
├── verify_otp.php                 # Demo/Live Mobile OTP verification interface
├── track.php                      # Public complaint tracker with live multi-stage timeline
├── about.php                      # About the civic initiative & department directory
├── contact.php                    # Public 24/7 helpline numbers & inquiry form
├── logout.php                     # Citizen logout & session destruction
│
├── config/
│   ├── database.php               # PDO database connection & error handling
│   ├── config.php                 # Global application constants, status & priority maps
│   └── otp_config.php             # OTP configuration (demo mode vs live SMS API)
│
├── includes/
│   ├── header.php                 # HTML <head>, Bootstrap 5, Bootstrap Icons, Leaflet CSS
│   ├── footer.php                 # Civic footer with quick links, helplines & script bundles
│   ├── navbar.php                 # Dynamic responsive navigation bar
│   ├── auth.php                   # Citizen authentication guard & session protection
│   ├── admin_auth.php             # Municipal Admin authentication guard
│   └── functions.php              # Core helper functions (CSRF, sanitization, ID generator, dept suggester)
│
├── citizen/
│   ├── dashboard.php              # Citizen overview, KPI cards & recent submissions
│   ├── file_complaint.php         # Issue filing form with Leaflet map picker & image upload
│   ├── submit_complaint.php       # Backend complaint processor & unique ID generation
│   ├── complaints.php             # "My Complaints" list with filter & search
│   ├── complaint_details.php      # Detailed view with dynamic progress timeline & remarks log
│   ├── profile.php                # Citizen profile management & password change
│   └── update_profile.php         # Profile & password update backend handler
│
├── admin/
│   ├── login.php                  # Municipal administrator login portal
│   ├── logout.php                 # Admin session destruction
│   ├── dashboard.php              # Admin analytics dashboard with Chart.js charts & KPIs
│   ├── complaints.php             # Master complaints registry with multi-criteria filtering
│   ├── complaint_details.php      # Administrative action panel (verify, assign dept, set priority, log progress)
│   ├── update_complaint.php       # Backend handler for admin status & progress actions
│   ├── users.php                  # Registered citizens management & complaint count overview
│   └── departments.php            # Predefined municipal departments & workload metrics
│
├── assets/
│   ├── css/
│   │   └── style.css              # Custom styling: civic theme, status badges, timelines
│   └── js/
│   │   └── script.js              # Leaflet map picker, client-side validation, copy helpers
│
├── uploads/
│   └── complaints/                # Secure directory for citizen uploaded complaint photos
│
├── database/
│   └── cirs_db.sql                # Complete schema, foreign keys & pre-seeded demo data
│
└── README.md                      # Comprehensive project documentation & viva guide
```

---

## 🚀 Step-by-Step Installation Instructions

### 1. Prerequisites
- Install [XAMPP](https://www.apachefriends.org/) (PHP 7.4+ or PHP 8.x).
- Visual Studio Code or any code editor.
- Web Browser (Chrome, Edge, Firefox).

### 2. Setup Project Files
1. Open XAMPP directory: `C:\xampp\htdocs\`
2. Place the project folder inside `htdocs`. For example: `C:\xampp\htdocs\Citizen Issue Reporting System` (or rename to `cirs`).

### 3. Import Database
1. Launch **XAMPP Control Panel**.
2. Click **Start** for **Apache** and **MySQL**.
3. Open your browser and go to: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
4. Click on the **Import** tab at the top.
5. Click **Choose File** and select `database/cirs_db.sql` located inside the project folder.
6. Click the **Import** button at the bottom.
7. The database `cirs_db` with all 6 tables and demo records will be created automatically.

### 4. Open in Web Browser
Open your browser and navigate to:
```text
http://localhost/Citizen%20Issue%20Reporting%20System/
```
*(Or `http://localhost/cirs/` if renamed).*

---

## 🔑 Default Demo Credentials

For quick evaluation and college viva presentation, pre-seeded accounts are provided:

### 🛡️ Municipal Administrator Account
- **Portal URL:** `http://localhost/Citizen%20Issue%20Reporting%20System/admin/login.php`
- **Email:** `admin@cirs.gov`
- **Password:** `admin.gov`

### 👤 Citizen Demo Accounts
- **Portal URL:** `http://localhost/Citizen%20Issue%20Reporting%20System/login.php`
- **Citizen 1:** `rahul.sharma@example.com` | **Password:** `citizen123`
- **Citizen 2:** `priya.patel@example.com` | **Password:** `citizen123`

---

## 🌟 Key Features & Innovations

1. **Unique Public Reference IDs:**
   - Every complaint generates a standardized tracking ID: `CIRS-2026-XXXXX`.

2. **OpenStreetMap & Leaflet Integration:**
   - Interactive map picker with draggable marker and "Use Current Location" (HTML5 Geolocation) button.
   - Coordinates automatically stored for municipal field teams.

3. **Intelligent Category → Department Suggestions:**
   - Automatic routing recommendations:
     - `Pothole` ➔ Road / Public Works Department
     - `Garbage` / `Illegal Dumping` ➔ Sanitation Department
     - `Water Leakage` ➔ Water Supply Department
     - `Streetlight` ➔ Electricity Department
     - `Drainage` ➔ Drainage & Sewerage Department
     - `Other` ➔ Municipal Administration Department

4. **Multi-Stage Dynamic Visual Timeline:**
   - Live visual status indicator tracking 5 phases: *Submitted ➔ Admin Verified ➔ Department Assigned ➔ Work in Progress ➔ Resolved & Closed*.

5. **Chart.js Visual Analytics:**
   - Interactive charts for administrative monitoring of complaints by category, status distribution, and department workload.

6. **Free / Demo Mobile OTP Mode:**
   - Demonstrates two-factor mobile verification with on-screen demo code without requiring paid third-party SMS gateway subscriptions.

7. **Strict Security Architecture:**
   - 100% PDO prepared statements against SQL Injection.
   - Bcrypt `password_hash()` for user and admin passwords.
   - Output sanitization with `htmlspecialchars()` against XSS.
   - Strict session isolation between Citizen and Admin portals.
   - Secure image file verification (extension whitelist, MIME validation, 5MB limit).

---

## 🎓 Viva Questions & Answers (TYCS College Project)

**Q1. Why did you use PDO instead of MySQLi?**  
*Answer:* PDO (PHP Data Objects) provides a unified database abstraction interface, supports named parameters for prepared statements, and offers robust exception handling, making our application secure against SQL Injection.

**Q2. How is password security implemented?**  
*Answer:* Passwords are never stored in plain text. We utilize PHP's built-in `password_hash()` using the Bcrypt algorithm with automatic salting, and verify them using `password_verify()`.

**Q3. How does the intelligent department suggestion work?**  
*Answer:* In `includes/functions.php`, the function `suggestDepartmentId()` matches the complaint category against predefined municipal domain mappings and automatically pre-selects the appropriate department in the admin console while allowing manual override if required.

**Q4. How does the public tracking page work without requiring a citizen login?**  
*Answer:* The `track.php` page queries the database by the unique public reference ID (`CIRS-2026-XXXXX`). It retrieves the status, assigned department, coordinates, and official remarks log while protecting personal passwords and sensitive profile data.

---

## 📄 License
Developed for academic presentation and municipal civic redressal demonstrations.
#   C i t i z e n - I s s u e - R e p o r t i n g - S y s t e m  
 