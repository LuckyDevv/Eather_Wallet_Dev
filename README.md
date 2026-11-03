# 💰 Eather Wallet — веб-кошелёк (PHP + WebSocket)

[![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat-square&logo=php)](https://php.net)

## 📌 О проекте

Веб-приложение электронного кошелька EATHER: счета и переводы между
пользователями в реальном времени (WebSocket), авторизация с подтверждением
по email, история операций. Бэкенд — собственная MVC-архитектура на PHP,
связка с Android-приложением ([Eather_Wallet_App_Dev](https://github.com/LuckyDevv/Eather_Wallet_App_Dev)).

## ✨ Возможности

- Регистрация / авторизация, сессии, подтверждение по email
- Балансы и переводы между пользователями
- Операции в реальном времени — WebSocket-сервер (Ratchet)
- API для мобильного приложения
- История транзакций

## 🧰 Стек

- PHP 8.1, PDO (MySQL), Ratchet (WebSockets), Twig
- JavaScript (ES6+) фронтенд, CSS3
- REST API для Android-клиента (Java)

## 🚀 Запуск

```bash
git clone https://github.com/LuckyDevv/Eather_Wallet_Dev.git
cd Eather_Wallet_Dev
composer install
# Настроить подключение к MySQL в server/controllers/ConfigController.php
php server/socket/ServerRunner.php   # WebSocket-сервер
# Открыть index.php через веб-сервер
```

## 📂 Структура

```
index.php                # точка входа
auth/                    # страница авторизации
server/
  api/                   # REST API для мобильного приложения
  socket/                # WebSocket-сервер (Ratchet)
  controllers/           # контроллеры (конфиг, авторизация, почта)
  db_managers/           # аккаунты, сессии, транзакции
src/                     # js + css фронтенда
```

## 📜 Лицензия

MIT
