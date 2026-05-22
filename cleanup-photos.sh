#!/bin/bash
# ============================================================
#  FlashLink — cleanup-photos.sh
#  Supprime les photos de plus de 24h
#  Cron : 0 3 * * * /home/ftpuser/cleanup-photos.sh
# ============================================================

PHOTOS_DIR="/home/ftpuser/photos"
MAX_AGE_HOURS=24
EXTENSIONS="jpg jpeg png gif webp"
LOG_FILE="/var/log/flashlink-photos.log"

log() {
    local msg="[$(date '+%Y-%m-%d %H:%M:%S')] $1"
    echo "$msg"
    [ -n "$LOG_FILE" ] && echo "$msg" >> "$LOG_FILE"
}

if [ ! -d "$PHOTOS_DIR" ]; then
    log "ERREUR : le dossier '$PHOTOS_DIR' n'existe pas."
    exit 1
fi

log "FlashLink — nettoyage photos > ${MAX_AGE_HOURS}h dans : $PHOTOS_DIR"

FIND_ARGS=()
first=true
for ext in $EXTENSIONS; do
    if $first; then FIND_ARGS+=(-iname "*.${ext}"); first=false
    else FIND_ARGS+=(-o -iname "*.${ext}"); fi
done

deleted=0; errors=0

while IFS= read -r -d '' file; do
    if rm "$file" 2>/dev/null; then
        log "  Supprimé : $(basename "$file")"
        ((deleted++))
    else
        log "  Erreur   : $(basename "$file")"
        ((errors++))
    fi
done < <(find "$PHOTOS_DIR" -maxdepth 2 -type f \( "${FIND_ARGS[@]}" \) -mmin +$((MAX_AGE_HOURS * 60)) -print0)

log "Terminé — ${deleted} photo(s) supprimée(s), ${errors} erreur(s)"
log "────────────────────────────────────────────────"
