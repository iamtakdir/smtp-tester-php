# 📧 PHP Native SMTP Tester

![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)
![PHP Version](https://img.shields.io/badge/PHP-%3E%3D%208.2-777BB4.svg)
![Maintained](https://img.shields.io/badge/Maintained%3F-yes-green.svg)
![Author](https://img.shields.io/badge/Author-iamtakdir-blue)

[**🚀 View Live Demo**](https://smtp-test.iamtakdir.xyz)

A lightweight, single-file SMTP testing tool built with **native PHP sockets**. It allows developers to test SMTP server configurations, send test emails, and view real-time raw transaction logs between the client and server.

**No external libraries (PHPMailer/SwiftMailer) required.** Just drop the file and run.

---

## ✨ Features

* **⚡ Zero Dependencies:** Runs on standard PHP installations (XAMPP, WAMP, LAMP, cPanel).
* **📱 Fully Responsive:** Modern, dark-mode UI that works on Desktops, Tablets, and Mobile.
* **🔍 Raw Debug Logs:** View the exact `CLIENT` vs `SERVER` handshake command-by-command.
* **🔒 Security Support:** Full support for `TLS` (587), `SSL` (465), and `None` (25).
* **📂 Single File:** Everything (Logic + UI + CSS) is contained in one `index.php` file.

## 🚀 Installation & Usage

### Option 1: Using XAMPP / Apache / Nginx
1.  **Download** the `index.php` file.
2.  **Move** it to your server's root directory (e.g., `C:\xampp\htdocs\smtp-tester\`).
3.  **Open** your browser: `http://localhost/smtp-tester/`

### Option 2: Run without XAMPP (PHP Built-in Server)
If you have PHP installed on your machine, you don't need XAMPP. You can run it directly from your terminal.

1.  Open your terminal/command prompt.
2.  Navigate to the folder containing `index.php`.
3.  Run the following command:
    ```bash
    php -S localhost:8000
    ```
4.  Open your browser to: [http://localhost:8000](http://localhost:8000)

## 🛠 Configuration Guide

| Field | Description | Example (Gmail) |
| :--- | :--- | :--- |
| **Host** | The SMTP server address. | `smtp.gmail.com` |
| **Port** | The connection port. | `587` (TLS) or `465` (SSL) |
| **Security** | Encryption method. | `TLS` |
| **Username** | Your email address. | `user@gmail.com` |
| **Password** | Your email password or **App Password**. | `xxxx-xxxx-xxxx-xxxx` |

> **⚠️ Note for Gmail Users:** You must use an **App Password** if you have 2-Factor Authentication enabled. Your regular login password will not work.

## 🤝 Contributing

Contributions, issues, and feature requests are welcome!
Feel free to check the [issues page](https://github.com/iamtakdir).

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

---

## 👨‍💻 Author

**Takdir**

* **GitHub:** [@iamtakdir](https://github.com/iamtakdir)
* **Website:** [iamtakdir.xyz](https://iamtakdir.xyz)

---
*Made with ❤️ by iamtakdir*

