# Attendance Management System

A web-based Attendance Management System developed using PHP and MySQL.  
This project helps organizations manage employee attendance with role-based access like Admin, HR, Agent, Manager, and Parent.

---

## 🚀 Features

- 👤 Multi-role login system (Admin, HR, Agent, Manager, Parent)
- 📍 Location-based attendance (can be extended)
- 📊 Attendance records & reports
- 🧑‍💼 Employee & user management
- 📥 Download attendance reports
- 🔐 Secure configuration (no credentials in repo)

---

## 🛠️ Tech Stack

- **Backend:** PHP  
- **Database:** MySQL  
- **Frontend:** HTML, CSS, JavaScript  
- **Server:** Apache (XAMPP / WAMP recommended)

---

## 📂 Project Structure

attendance-management-system/
├── admin/ # Admin panel files
├── hr/ # HR related modules
├── agent/ # Agent dashboard
├── manager/ # Manager related files
├── parent/ # Parent / report access
├── config.php # Database config (dummy values)
├── config.example.php
├── README.md



---

## ▶️ How to Run the Project (Local Setup)

1. Install **XAMPP / WAMP**
2. Copy project folder to:
3. Start **Apache** and **MySQL**
4. Create a database in phpMyAdmin
5. Update database details in `config.php`
6. Open browser and visit:

---

## 🔐 Security Notice

- Database credentials are **NOT included** in this repository.
- Use `config.example.php` as a reference to create your own `config.php` locally.
- Sensitive files are ignored using `.gitignore`.

---

## 🧑‍💻 Author

**Sanju Aayu**  
GitHub: https://github.com/sanjuaayu

---

## 📌 Note

This project is created for **learning, college submission, and practice purposes**.  
You are free to improve and extend it.
