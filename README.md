

**Graphic Shop Workflow & Operations System (PHP + MySQL)**

---

## 📌 Overview

This project is a **web-based workflow management system** built for a **graphics & printing shop**.
It solves common shop-floor problems like:

* Worker miscommunication
* No visibility of task stages
* No accountability of who handled what
* Mixing operational data with financial data

The system provides:

* Role-based access (Admin / Accountant / Worker)
* Stage-wise order tracking
* Daily task checklists
* Admin performance & progress visibility
* Secure login with PHP sessions

---

## 🧱 Tech Stack

* **Backend:** PHP (PDO)
* **Database:** MySQL (XAMPP)
* **Frontend:** HTML, CSS, Vanilla JS
* **Server:** PHP Built-in Server (local development)

---

## 🔐 User Roles

| Role       | Access                                         |
| ---------- | ---------------------------------------------- |
| Admin      | Full system, workflow + financials + analytics |
| Accountant | Billing, payments, financial records           |
| Worker     | Assigned tasks & workflow stages only          |

> ⚠️ Financial data is **never visible** to workers.

---

## 🚀 Local Setup Instructions

### 1️⃣ Start XAMPP

* Ensure **MySQL is running (green)**

### 2️⃣ Project Location

```text
C:\Users\HP\.gemini\antigravity\scratch\graphic-shop-website
```

### 3️⃣ Start PHP Server

```bat
D:\Sem3\PHP\Xampp\php\php.exe -S localhost:3000
```

### 4️⃣ Initialize Database

Open in browser:

```text
http://localhost:3000/server/setup.php
```

This will:

* Create `graphic_shop_db`
* Create all required tables

### 5️⃣ Open Application

```text
http://localhost:3000
```

---

## 🗂️ Project File Structure

```text
graphic-shop-website/
│
├── index.html              # Login page
├── dashboard.html          # Main dashboard after login
│
├── assets/
│   ├── css/
│   │   └── style.css       # Main UI styles (glassmorphism theme)
│   └── js/
│       └── app.js          # Frontend logic (API calls, UI updates)
│
├── server/
│   ├── config.php          # Database configuration
│   ├── setup.php           # One-time DB setup script
│   ├── api.php             # Central API controller
│   ├── test_db.php         # DB connection test utility
│   └── install.php         # Schema installer (tables creation)
│
├── database.sql            # SQL schema (optional manual import)
│
└── README.md               # Project documentation
```

---

## 🔄 Workflow Logic (High Level)

1. Order is created → Order ID generated
2. Order moves through stages:

   * Designing
   * Printing
   * Fabrication
   * Installation
   * Billing
3. Each stage:

   * Is checkbox-driven
   * Has responsible worker
   * Is logged with timestamp
4. Admin sees:

   * Current stage
   * Last handler
   * Billing status
   * Day-end performance

---

## 🛡️ Security Notes

* Passwords stored using hashing
* PHP sessions used for authentication
* Role-based access enforced server-side
* Financial endpoints restricted to Admin/Accountant

---

## 📈 Future Enhancements

* CAPTCHA on login
* Worker performance reports
* File upload vault for designs & photos
* Audit trail per order
* Deployment on Apache/Nginx

---

## ✅ Project Status

* [x] Database schema
* [x] Setup script
* [x] PHP server working
* [x] UI structure
* [ ] Authentication polish
* [ ] Role permissions hardening
