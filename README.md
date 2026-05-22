# ⚡ FlashLink

**FlashLink** est un micro-service de raccourcissement d'URL éphémères, conçu pour les photobooths événementiels.  
Les liens sont automatiquement supprimés après 24h — comme les photos qu'ils pointent.

> Développé pour un photobooth Raspberry Pi qui uploade ses clichés sur un serveur personnel, génère des QR codes et partage des liens courts via SMS ou écran.

---

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
GET /create.php?key=TA_CLE&url=https://ton-url-longue.fr/photo.jpg
```

```json
{
  "id": "a3k9w",
  "short": "https://url.tondomaine.fr/a3k9w",
  "url": "https://ton-url-longue.fr/photo.jpg",
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
- API protégée par clé secrète (`API_KEY`) — paramètre GET ou header `X-Api-Key`
- `urls.json` exclu du repo via `.gitignore`

---

## 🚀 Initialisation du repo Git

```bash
git init
git add .
git commit -m "Initial commit — FlashLink"
git remote add origin https://github.com/TON_USER/flashlink.git
git push -u origin main
```

---

## 📄 Licence

MIT — voir [LICENSE](LICENSE)
