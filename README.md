# 🌱 Agri-Business Social Enterprise Platform

![Status](https://img.shields.io/badge/Status-Completed-success)
![Version](https://img.shields.io/badge/Version-1.0-blue)
![Tech Stack](https://img.shields.io/badge/Tech_Stack-PHP_|_PDO_|_MySQL-orange)

A structural paradigm shift in digital agriculture. This enterprise-grade web platform replaces opaque, manual agricultural coordination loops with transparent digital networks. It connects smallholder farmers, social investors, and direct consumers to eliminate predatory middlemen and drive localized wealth creation.

---

## 📸 System Previews
<img width="952" height="482" alt="Screenshot 2026-06-16 061500" src="https://github.com/user-attachments/assets/02ebe9de-5fbe-4ff1-b8e5-411b507c2224" />
<img width="959" height="463" alt="Screenshot 2026-06-16 061523" src="https://github.com/user-attachments/assets/ea439bc3-3aff-42ca-b536-ed3a1c51523e" />
<img width="956" height="473" alt="Screenshot 2026-06-16 061534" src="https://github.com/user-attachments/assets/6261513f-00d7-4f92-b225-8fa13e2e650d" />
<img width="959" height="466" alt="Screenshot 2026-06-16 061554" src="https://github.com/user-attachments/assets/d67d2fb6-efe4-4b06-86de-8bf263470e58" />
<img width="958" height="476" alt="Screenshot 2026-06-16 061609" src="https://github.com/user-attachments/assets/d1819492-8451-4bb9-811e-8e823c53214c" />
<img width="959" height="367" alt="Screenshot 2026-06-16 061623" src="https://github.com/user-attachments/assets/32bf723d-c580-4aaf-a882-edca05195d7c" />
<img width="590" height="476" alt="Screenshot 2026-06-16 062354" src="https://github.com/user-attachments/assets/3a3637d2-f138-4550-9c5e-f13895d4f0e0" />

---

## 📥 Database Download & Quick Setup
To deploy this system locally, download the required SQL database schema below:

[![Download SQL Database](https://img.shields.io/badge/Download-MySQL_Database_Schema-2ea44f?style=for-the-badge&logo=mysql&logoColor=white)](https://github.com/birru2217/agri-business-social-enterprise/raw/main/sql/schema.sql)

---

## 🎯 Core Objectives & Solutions

Traditional agricultural networks suffer from geographical isolation, asymmetrical information, and the "black box" investment dilemma. This platform solves these through:
1. **Disintermediation:** Direct Peer-to-Peer architecture bypassing brokers.
2. **Social Investment:** Micro-financing infrastructure allowing urban citizens to safely fund rural farm projects.
3. **Platform Transparency:** Auditable transaction ledgers and symmetric market pricing data.
4. **Reduction in Post-Harvest Waste:** Real-time visibility into market demand.

---

## 👥 Multi-Role Architecture (Scope)

The platform is built on a strictly isolated, role-based access control (RBAC) system:

### 🧑‍🌾 1. Farmers (The Supply Layer)
- **Digital Storefront:** Catalog harvests, set competitive pricing, and manage real-time stock availability.
- **Project Profiles:** Create structured proposals for upcoming seasonal plantings to attract external capital.

### 💼 2. Investors (The Capital Injection Layer)
- **Project Auditing:** Browse active farming profiles and evaluate geographic risk factors.
- **Impact Tracking:** Fund specific crop cycles and monitor physical milestones directly from their dashboard.

### 🛒 3. Customers (The Demand Layer)
- **Marketplace Discovery:** Filter and browse fresh agricultural products by location, price, and harvest date.
- **Traceability:** View explicit trace-history metrics showing exactly where and when food was harvested.

### 🛡️ 4. Administrators (The Governance Layer)
- **Verification Moderation:** Vet local farmers and authenticate incoming investors.
- **Marketplace Oversight:** Monitor active listings to prevent fraudulent schemes and ensure system stability.

---

## 🛠️ System Architecture & Technologies

The platform is engineered as a dynamic, client-server web application relying exclusively on highly accessible, industry-standard web tools.

*   **Frontend:** HTML5, CSS3, JavaScript, Bootstrap (Responsive, mobile-friendly design).
*   **Backend Logic:** PHP (Procedural Logic Engine with Secure Session Handling).
*   **Database:** MySQL (Structured relational database using **PDO Data Interchange Layer** to prevent SQL injection).
*   **Security:** Cryptographic password hashing (Bcrypt), input sanitization, and strict RBAC isolated views.

---

## ⚙️ Local Installation Guide

1. Clone this repository into your local server environment (e.g., `wamp64/www/` or `xampp/htdocs/`).
2. Rename the project folder to `agri_business`.
3. Open **phpMyAdmin** and create a database named **`agri_social_db`**.
4. Import the downloaded `agri_social_db.sql` file into the new database.
5. *(Optional)* Configure your database credentials in `includes/db.php` if you are using a custom MySQL password.
6. Launch the application via `http://localhost/agri_business`.

---

## 👨‍💻 Development Team

Developed as a group project for the **Advanced Web Programming (CsEg3092)** course at **Bule Hora University**, College of Computing and Informatics.

*   **Beka Temesgen** (ID: 0037/16)
*   **Atinaf Kene** (ID: 0361/16)
*   **Lemi Wodejo** (ID: 0361/16)
*   **Sabir Yesuf** (ID: 0347/16)

*Submitted to: Mr. Adugna H. | Academic Year: 2018/2026*
