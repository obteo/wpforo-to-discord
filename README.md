# WPForo → Discord

A simple WordPress plugin that sends **new wpForo topics** to a Discord channel via Webhook.  
Compatible with **wpForo v2.4.6** and tested on WordPress 6.x.

## ✨ Features
- Sends new topics (title, excerpt, author, forum name) to a Discord channel.
- Discord embed style (title clickable → goes to the forum topic).
- Supports emojis (decoded from HTML entities).
- Exclude specific forums from notifications (via admin settings).
- Simple and lightweight.

---

## ⚙️ Installation
1. Download or clone this repository.
2. Copy the folder into your WordPress plugins directory:
/wp-content/plugins/wpforo-to-discord/
3. Activate the plugin from **WordPress → Plugins**.
4. Configure your Discord Webhook in:
Settings → WPForo → Discord
5. (Optional) Exclude forums from being sent to Discord.

---

## 📝 Usage
- Every new **topic** created in wpForo will automatically send a message to the configured Discord channel.
- Excluded forums will **not** send notifications.
- Categories are ignored (only real forums can be excluded).

---

## 🛠️ Development
- PHP 7.4+ recommended.
- Tested with wpForo 2.4.6.
- Uses WordPress built-in HTTP API (`wp_remote_post`).
- Admin UI built with standard WordPress options API.

---

## 📌 Roadmap
- Option to also send replies (not only topics).
- Multiple webhook support.
- Per-forum custom webhook.

---

## 📜 License
GPL v2 or later.
