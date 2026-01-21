# Security Analytics Center

Повнофункціональний аналітичний центр для кібербезпеки з підтримкою парсингу сканів, управління вразливостями, CTF модуля та нотаток.

## 🚀 Функціонал

### 1. Завантаження та парсинг даних
- **Nmap**: автоматичний парсинг XML-звітів сканування, збереження хостів, портів та сервісів
- **Nuclei**: парсинг JSON/TXT звітів з автоматичним створенням записів вразливостей

### 2. Управління вразливостями
- Додавання, редагування, видалення записів про вразливості
- Категоризація за severity: critical, high, medium, low, info
- Прив'язка до конкретних хостів/сайтів
- Відстеження статусу: new, in_progress, fixed, false_positive
- Підтримка CVE ID та CVSS scores

### 3. CTF модуль
- Облік CTF змагань з датами, платформою, командою
- Управління завданнями (challenges) з writeups
- Категорії: web, pwn, crypto, forensics, misc, reverse, osint
- Статус завдань: solved, unsolved, in_progress

### 4. Нотатки
- Створення та редагування нотаток
- Категоризація та теги
- Прив'язка до вразливостей, CTF або хостів

### 5. Dashboard
- Статистика по вразливостях (графіки severity та status)
- Останні завантажені скани
- Швидкий доступ до всіх модулів

## 🛠 Технології

- **Backend**: PHP 8.2 з PDO
- **Database**: MySQL 8.0
- **Web Server**: Nginx (Alpine)
- **Frontend**: Bootstrap 5, Font Awesome, Chart.js
- **Infrastructure**: Docker Compose
- **Admin**: phpMyAdmin

## 📋 Вимоги

- Docker
- Docker Compose
- Вільні порти: 80, 3306, 8080

## 🔧 Встановлення

### 1. Клонування репозиторію

```bash
git clone https://github.com/plamagnum/analitycs.git
cd analitycs
```

### 2. Налаштування оточення

Скопіюйте файл конфігурації:

```bash
cp .env.example .env
```

Відредагуйте `.env` за потреби (паролі, порти):

```bash
nano .env
```

### 3. Запуск сервісів

```bash
docker-compose up -d
```

Перший запуск може зайняти 2-3 хвилини (завантаження образів та побудова контейнерів).

### 4. Перевірка статусу

```bash
docker-compose ps
```

Всі сервіси повинні бути в стані `Up`.

## 🌐 Доступ до сервісів

| Сервіс | URL | Опис |
|--------|-----|------|
| **Web Application** | http://localhost | Головний додаток |
| **phpMyAdmin** | http://localhost:8080 | Управління базою даних |
| **MySQL** | localhost:3306 | Прямий доступ до БД |

### Credentials для phpMyAdmin

- **Server**: mysql
- **Username**: analitycs_user (або MYSQL_USER з .env)
- **Password**: analitycs_pass (або MYSQL_PASSWORD з .env)

Або використовуйте root:
- **Username**: root
- **Password**: root_password (або MYSQL_ROOT_PASSWORD з .env)

## 📚 Використання

### Завантаження Nmap скану

1. Згенеруйте скан:
```bash
nmap -oX scan.xml -sV 192.168.1.1
```

2. Відкрийте http://localhost/pages/nmap.php
3. Перетягніть або виберіть XML файл
4. Система автоматично розпарсить хости, порти та сервіси

### Завантаження Nuclei скану

1. Згенеруйте скан:
```bash
nuclei -u https://example.com -jsonl -o results.json
```

2. Відкрийте http://localhost/pages/nuclei.php
3. Завантажте JSON або TXT файл
4. Вразливості будуть автоматично додані в систему

### Управління вразливостями

1. Перейдіть в розділ Vulnerabilities
2. Використовуйте кнопку "Add Vulnerability" для ручного додавання
3. Фільтруйте за severity або статусом
4. Редагуйте або видаляйте існуючі записи

### CTF модуль

1. Створіть змагання в табі "Competitions"
2. Додайте завдання в табі "Challenges"
3. Прив'яжіть challenge до competition
4. Додавайте writeups та рішення

## 🗂 Структура проекту

```
/
├── docker-compose.yml          # Конфігурація Docker
├── .env.example                # Приклад змінних оточення
├── nginx/
│   └── default.conf            # Конфігурація Nginx
├── php/
│   ├── Dockerfile              # PHP-FPM контейнер
│   └── src/                    # Вихідний код додатку
│       ├── index.php           # Dashboard
│       ├── config/
│       │   └── database.php    # Підключення до БД
│       ├── api/                # REST API endpoints
│       │   ├── upload_nmap.php
│       │   ├── upload_nuclei.php
│       │   ├── vulnerabilities.php
│       │   ├── ctf.php
│       │   └── notes.php
│       ├── includes/           # Загальні компоненти
│       │   ├── header.php
│       │   ├── footer.php
│       │   └── functions.php
│       ├── pages/              # Сторінки додатку
│       │   ├── vulnerabilities.php
│       │   ├── ctf.php
│       │   ├── notes.php
│       │   ├── nmap.php
│       │   └── nuclei.php
│       └── assets/
│           ├── js/
│           │   ├── app.js      # Основний JS
│           │   └── ctf.js      # CTF функціонал
│           └── css/
│               └── style.css   # Темна тема
├── mysql/
│   └── init.sql                # Схема бази даних
└── README.md
```

## 🗄 База даних

### Таблиці

1. **hosts** - хости та сайти з Nmap сканів
2. **ports** - порти та сервіси на хостах
3. **vulnerabilities** - знайдені вразливості
4. **nmap_scans** - історія Nmap завантажень
5. **nuclei_scans** - історія Nuclei завантажень
6. **ctf_competitions** - CTF змагання
7. **ctf_challenges** - CTF завдання
8. **notes** - нотатки користувача

## 🔒 Безпека

- Всі SQL запити використовують prepared statements (захист від SQL injection)
- Валідація типів файлів при завантаженні
- XSS захист через htmlspecialchars()
- Обмеження розміру файлів (100MB)

## 🛑 Зупинка сервісів

```bash
docker-compose down
```

Для видалення даних (включно з БД):
```bash
docker-compose down -v
```

## 🔄 Оновлення

```bash
git pull
docker-compose down
docker-compose up -d --build
```

## 📊 Приклади команд

### Nmap

```bash
# Швидке сканування
nmap -oX quick.xml -T4 -F 192.168.1.0/24

# Повне сканування з визначенням ОС
nmap -oX full.xml -A -p- target.com

# Тільки відкриті порти
nmap -oX open.xml -sV --open 10.0.0.0/8
```

### Nuclei

```bash
# Скан одного хоста
nuclei -u https://example.com -jsonl -o scan.json

# Скан з файлу
nuclei -l targets.txt -jsonl -o results.json

# Тільки критичні та високі
nuclei -u target.com -s critical,high -jsonl -o critical.json

# З конкретними темплейтами
nuclei -u target.com -t cves/ -jsonl -o cves.json
```

## 🐛 Troubleshooting

### Порт вже використовується

Змініть порти в `.env`:
```
NGINX_PORT=8000
MYSQL_EXTERNAL_PORT=3307
PHPMYADMIN_PORT=8081
```

### Помилка підключення до БД

Перевірте, що MySQL контейнер запущений:
```bash
docker-compose logs mysql
```

### Проблеми з правами на файли

```bash
chmod -R 755 php/src
```

## 👨‍💻 Розробка

### Логи

```bash
# Всі сервіси
docker-compose logs -f

# Конкретний сервіс
docker-compose logs -f nginx
docker-compose logs -f php-fpm
docker-compose logs -f mysql
```

### Доступ до контейнера

```bash
# PHP контейнер
docker-compose exec php-fpm bash

# MySQL
docker-compose exec mysql mysql -u root -p
```

## 📝 Ліцензія

MIT License

## 🤝 Внесок

Pull requests вітаються. Для серйозних змін спочатку відкрийте issue.

## 📧 Контакти

GitHub: [@plamagnum](https://github.com/plamagnum)
