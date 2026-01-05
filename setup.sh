#!/bin/bash

echo "🚀 FiscalMix - Setup Automático"
echo "================================"
echo ""

# Cores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}📦 Instalando dependências do backend...${NC}"
docker-compose exec -T backend composer install --no-interaction --prefer-dist

echo -e "${YELLOW}⚙️  Configurando .env...${NC}"
docker-compose exec -T backend cp .env.example .env
docker-compose exec -T backend php artisan key:generate

echo -e "${YELLOW}🗄️  Executando migrations...${NC}"
docker-compose exec -T backend php artisan migrate --force

echo ""
echo -e "${GREEN}✅ Backend configurado com sucesso!${NC}"
echo ""
echo "📍 Serviços disponíveis:"
echo "   - Backend API: http://localhost:8001/api"
echo "   - Frontend: http://localhost:5173"
echo "   - PostgreSQL: localhost:5434"
echo "   - Redis: localhost:6382"
echo ""
echo "📝 Próximos passos:"
echo "   1. cd frontend && npm install && npm run dev"
echo "   2. Acesse http://localhost:5173"
echo "   3. Crie uma conta em /register"
echo ""
