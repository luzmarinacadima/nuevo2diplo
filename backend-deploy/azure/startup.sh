
#!/bin/sh
set -eu

CONFIG="/etc/nginx/sites-available/default"
BACKUP="/tmp/asistencia-nginx-original"

# Guardar una copia de la configuracion predeterminada
cp "$CONFIG" "$BACKUP"

# Cambiar la raiz publica a la carpeta de la API
sed -i -E \
  's#root[[:space:]]+/home/site/wwwroot;#root /home/site/wwwroot/api;#' \
  "$CONFIG"

# Dirigir las solicitudes /api/... al controlador PHP
sed -i \
  '/location \/ {/i\        location /api/ { rewrite ^/api/.*$ /index.php last; }' \
  "$CONFIG"

# Verificar que la raiz publica se haya actualizado
if ! grep -q 'root /home/site/wwwroot/api;' "$CONFIG"; then
    cp "$BACKUP" "$CONFIG"
    echo "ERROR: No se pudo cambiar la raiz publica."
    exit 1
fi

# Verificar la configuracion antes de recargar Nginx
if nginx -t; then
    service nginx reload
else
    cp "$BACKUP" "$CONFIG"
    echo "ERROR: La configuracion Nginx no es valida."
    exit 1
fi

