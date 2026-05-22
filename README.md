# ⚡ FlashLink

**FlashLink** is a lightweight ephemeral URL shortener designed for event photobooths.  
Links are automatically deleted after 24h — just like the photos they point to.

> Built for a Raspberry Pi photobooth that uploads pictures to a personal server, generates QR codes, and shares short links on screen or via SMS.

---

<details open>
<summary>🇬🇧 English documentation</summary>

## 📁 Project structure

```
flashlink/
├── index.php               # Short link redirection
├── create.php              # API — create / stats / delete
├── nginx-flashlink.conf    # Nginx config for the subdomain
├── cleanup-photos.sh       # Cron: delete photos older than 24h
├── cleanup-urls.sh         # Cron: delete expired short links
├── .gitignore
└── LICENSE
```

---

## 🔗 URL Shortener

Lightweight PHP service with no database (JSON file storage), auto-expiry at 24h managed by cron.

### Requirements

- Linux server with Nginx + PHP-FPM (PHP 8.x)
- A short subdomain (e.g. `url.yourdomain.com`)

### Installation

**1. Deploy the PHP files**

```bash
mkdir -p /var/www/flashlink
cp index.php create.php /var/www/flashlink/
chown -R www-data:www-data /var/www/flashlink
chmod 755 /var/www/flashlink
```

**2. Configure `create.php`**

```php
define('BASE_URL', 'https://url.yourdomain.com');  // your domain
define('API_KEY',  'your-secret-key-here');         // choose a secret key
```

**3. Enable Nginx config**

Check your PHP-FPM version first:
```bash
ls /run/php/
```

Update the socket path in `nginx-flashlink.conf` if needed (e.g. `php8.1-fpm.sock`), then:

```bash
cp nginx-flashlink.conf /etc/nginx/sites-available/flashlink
ln -s /etc/nginx/sites-available/flashlink /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

**4. DNS setup**

Add an `A` record for `url.yourdomain.com` pointing to your server IP.

---

## 🌐 API

### Create a short link

```
GET /create.php?key=YOUR_KEY&url=https://your-long-url.com/photo.jpg
```

```json
{
  "id": "a3k9w",
  "short": "https://url.yourdomain.com/a3k9w",
  "url": "https://your-long-url.com/photo.jpg",
  "expires": "2025-06-29T03:00:00+00:00"
}
```

### Custom alias

```
GET /create.php?key=YOUR_KEY&url=https://...&custom=myalias
→ https://url.yourdomain.com/myalias
```

### Statistics

```
GET /create.php?key=YOUR_KEY&stats=a3k9w     # single link
GET /create.php?key=YOUR_KEY&stats=all       # all links
```

### Delete a link

```
GET /create.php?key=YOUR_KEY&delete=a3k9w
```

### PHP integration (photobooth)

```php
$longUrl = 'https://photobooth.yourdomain.com/photos/2025-06-28-08-13-58.jpg';
$apiKey  = 'YOUR_KEY';
$apiUrl  = 'https://url.yourdomain.com/create.php?key=' . $apiKey . '&url=' . urlencode($longUrl);

$response = json_decode(file_get_contents($apiUrl), true);
$shortUrl = $response['short']; // → https://url.yourdomain.com/a3k9w
```

---

## 🗑️ Automatic cleanup (cron)

Two shell scripts delete expired photos and links every night.

### Installation

```bash
cp cleanup-photos.sh cleanup-urls.sh /home/ftpuser/
chmod +x /home/ftpuser/cleanup-photos.sh
chmod +x /home/ftpuser/cleanup-urls.sh
```

Edit the paths at the top of each script:

| Script | Variable | Default |
|--------|----------|---------|
| `cleanup-photos.sh` | `PHOTOS_DIR` | `/home/ftpuser/photos` |
| `cleanup-photos.sh` | `LOG_FILE` | `/var/log/flashlink-photos.log` |
| `cleanup-urls.sh` | `DB_FILE` | `/var/www/flashlink/urls.json` |
| `cleanup-urls.sh` | `LOG_FILE` | `/var/log/flashlink-urls.log` |

### Crontab

```bash
crontab -e
```

```
0 3 * * * /home/ftpuser/cleanup-photos.sh
5 3 * * * /home/ftpuser/cleanup-urls.sh
```

- **3:00 AM** → delete photos older than 24h
- **3:05 AM** → delete expired short links

### Logs

```bash
tail -f /var/log/flashlink-photos.log
tail -f /var/log/flashlink-urls.log
```

---

## ⚙️ Configuration reference

| Parameter | File | Default |
|-----------|------|---------|
| Short domain | `create.php` | `https://url.baule.fr` |
| API key | `create.php` | `CHANGE_MOI_ICI` |
| Link lifetime | `create.php` | `24` hours |
| PHP-FPM socket | `nginx-flashlink.conf` | `php8.2-fpm.sock` |
| Photos folder | `cleanup-photos.sh` | `/home/ftpuser/photos` |
| JSON database | `cleanup-urls.sh` | `/var/www/flashlink/urls.json` |

---

## 🔒 Security

- Direct access to `urls.json` blocked by Nginx
- API protected by secret key — pass as `?key=` or `X-Api-Key` header
- `urls.json` excluded from repo via `.gitignore`

---

## 🚀 Git setup

```bash
git init
git add .
git commit -m "Initial commit — FlashLink ⚡"
git remote add origin https://github.com/YOUR_USER/flashlink.git
git push -u origin main
```

</details>

---

<details>
<summary>🇫🇷 Documentation en français</summary>

## 📁 Structure du projet

```
flashlink/
├── index.php               # Redirection des liens courts
├── create.php              # API création / stats / suppression
├── nginx-flashlink.conf    # Config Nginx pour le sous-domaine
├── cleanup-photos.sh       # Cron : supprime les photos > 24h
├── cleanup-urls.sh         # Cron : supprime les liens courts > 24h
├── .gitignore
└── LICENSE
```

---

## 🔗 Raccourcisseur d'URL

Micro-service PHP sans base de données (stockage fichier JSON), avec expiration automatique à 24h gérée par cron.

### Prérequis

- Serveur Linux avec Nginx + PHP-FPM (PHP 8.x)
- Un sous-domaine court (ex: `url.mondomaine.fr`)

### Installation

**1. Dépose les fichiers PHP**

```bash
mkdir -p /var/www/flashlink
cp index.php create.php /var/www/flashlink/
chown -R www-data:www-data /var/www/flashlink
chmod 755 /var/www/flashlink
```

**2. Configure `create.php`**

```php
define('BASE_URL', 'https://url.tondomaine.fr');  // ton domaine
define('API_KEY',  'une-cle-secrete-longue');      // clé à choisir
```

**3. Active la config Nginx**

Vérifie ta version de PHP-FPM :
```bash
ls /run/php/
```

Adapte le socket dans `nginx-flashlink.conf` si besoin (ex: `php8.1-fpm.sock`), puis :

```bash
cp nginx-flashlink.conf /etc/nginx/sites-available/flashlink
ln -s /etc/nginx/sites-available/flashlink /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

**4. Configure le DNS**

Ajoute un enregistrement `A` sur `url.tondomaine.fr` pointant vers l'IP de ton serveur.

---

## 🌐 API

### Créer un lien court

```
GET /create.php?key=TA_CLE&url=https://ton-url-long.fr/photo.jpg
```

```json
{
  "id": "a3k9w",
  "short": "https://url.tondomaine.fr/a3k9w",
  "url": "https://ton-url-long.fr/photo.jpg",
  "expires": "2025-06-29T03:00:00+00:00"
}
```

### Alias personnalisé

```
GET /create.php?key=TA_CLE&url=https://...&custom=monalias
→ https://url.tondomaine.fr/monalias
```

### Statistiques

```
GET /create.php?key=TA_CLE&stats=a3k9w      # un lien
GET /create.php?key=TA_CLE&stats=all        # tous les liens
```

### Supprimer un lien

```
GET /create.php?key=TA_CLE&delete=a3k9w
```

### Intégration PHP (photobooth)

```php
$longUrl = 'https://photobooth.tondomaine.fr/photos/2025-06-28-08-13-58.jpg';
$apiKey  = 'TA_CLE';
$apiUrl  = 'https://url.tondomaine.fr/create.php?key=' . $apiKey . '&url=' . urlencode($longUrl);

$response = json_decode(file_get_contents($apiUrl), true);
$shortUrl = $response['short']; // → https://url.tondomaine.fr/a3k9w
```

---

## 🗑️ Nettoyage automatique (cron)

Deux scripts shell suppriment les photos et les liens expirés chaque nuit.

### Installation

```bash
cp cleanup-photos.sh cleanup-urls.sh /home/ftpuser/
chmod +x /home/ftpuser/cleanup-photos.sh
chmod +x /home/ftpuser/cleanup-urls.sh
```

Adapte les chemins en tête de chaque script :

| Script | Variable | Valeur par défaut |
|--------|----------|-------------------|
| `cleanup-photos.sh` | `PHOTOS_DIR` | `/home/ftpuser/photos` |
| `cleanup-photos.sh` | `LOG_FILE` | `/var/log/flashlink-photos.log` |
| `cleanup-urls.sh` | `DB_FILE` | `/var/www/flashlink/urls.json` |
| `cleanup-urls.sh` | `LOG_FILE` | `/var/log/flashlink-urls.log` |

### Crontab

```bash
crontab -e
```

```
0 3 * * * /home/ftpuser/cleanup-photos.sh
5 3 * * * /home/ftpuser/cleanup-urls.sh
```

- **3h00** → suppression des photos > 24h
- **3h05** → suppression des liens courts > 24h

### Logs

```bash
tail -f /var/log/flashlink-photos.log
tail -f /var/log/flashlink-urls.log
```

---

## ⚙️ Récapitulatif de configuration

| Paramètre | Fichier | Valeur par défaut |
|-----------|---------|-------------------|
| Domaine court | `create.php` | `https://url.baule.fr` |
| Clé API | `create.php` | `CHANGE_MOI_ICI` |
| Durée de vie des liens | `create.php` | `24` heures |
| Socket PHP-FPM | `nginx-flashlink.conf` | `php8.2-fpm.sock` |
| Dossier photos | `cleanup-photos.sh` | `/home/ftpuser/photos` |
| Base JSON | `cleanup-urls.sh` | `/var/www/flashlink/urls.json` |

---

## 🔒 Sécurité

- Accès direct à `urls.json` bloqué par Nginx
- API protégée par clé secrète — paramètre `?key=` ou header `X-Api-Key`
- `urls.json` exclu du repo via `.gitignore`

---

## 🚀 Initialisation Git

```bash
git init
git add .
git commit -m "Initial commit — FlashLink ⚡"
git remote add origin https://github.com/TON_USER/flashlink.git
git push -u origin main
```

</details>

---

## 📄 License / Licence

MIT — see [LICENSE](LICENSE)
