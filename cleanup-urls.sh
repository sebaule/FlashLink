#!/bin/bash
# ============================================================
#  FlashLink — cleanup-urls.sh
#  Supprime les liens courts de plus de 24h
#  Cron : 5 3 * * * /home/ftpuser/cleanup-urls.sh
# ============================================================

DB_FILE="/var/www/flashlink/urls.json"
MAX_AGE_HOURS=24
LOG_FILE="/var/log/flashlink-urls.log"
PHP_BIN=$(which php)

log() {
    local msg="[$(date '+%Y-%m-%d %H:%M:%S')] $1"
    echo "$msg"
    [ -n "$LOG_FILE" ] && echo "$msg" >> "$LOG_FILE"
}

if [ ! -f "$DB_FILE" ]; then
    log "INFO : $DB_FILE introuvable, rien à faire."
    exit 0
fi

log "FlashLink — purge des liens expirés (> ${MAX_AGE_HOURS}h)"

RESULT=$($PHP_BIN - "$DB_FILE" "$MAX_AGE_HOURS" << 'PHP'
<?php
$dbFile      = $argv[1];
$maxAgeHours = (int) $argv[2];
$db    = json_decode(file_get_contents($dbFile), true);
if (!is_array($db)) { echo "0 0"; exit; }
$total  = count($db);
$limit  = time() - ($maxAgeHours * 3600);
$purged = 0;
foreach ($db as $id => $entry) {
    $created = strtotime($entry['created'] ?? '');
    if ($created && $created < $limit) { unset($db[$id]); $purged++; }
}
file_put_contents($dbFile, json_encode($db, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "$purged " . $total;
PHP
)

purged=$(echo $RESULT | cut -d' ' -f1)
total=$(echo $RESULT | cut -d' ' -f2)
kept=$((total - purged))

log "Terminé — ${purged} lien(s) supprimé(s), ${kept} conservé(s)"
log "────────────────────────────────────────────────"
