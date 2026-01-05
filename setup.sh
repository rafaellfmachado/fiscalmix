#!/bin/bash

echo "🔧 Configurando backend Laravel..."

# Copiar .env.example para .env
docker-compose exec -T backend cp .env.example .env

# Configurar database PostgreSQL no .env
docker-compose exec -T backend sh -c "cat > .env << 'EOF'
APP_NAME=FiscalMix
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8001

LOG_CHANNEL=stack

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=fiscalmix
DB_USERNAME=fiscalmix
DB_PASSWORD=fiscalmix_secret

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_CLIENT=predis
REDIS_HOST=redis
REDIS_PASSWORD=fiscalmix_redis_secret
REDIS_PORT=6379

SANCTUM_STATEFUL_DOMAINS=localhost:5173
SPA_URL=http://localhost:5173
EOF"

# Gerar chave da aplicação
docker-compose exec -T backend php artisan key:generate

# Executar migrations
echo "📦 Executando migrations..."
docker-compose exec -T backend php artisan migrate --force

echo "✅ Backend configurado!"
echo ""
echo "🌐 Serviços:"
echo "   - API: http://localhost:8001/api"
echo "   - PostgreSQL: localhost:5434"
echo "   - Redis: localhost:6382"
