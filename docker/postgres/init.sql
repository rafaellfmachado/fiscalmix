-- Criar extensão para UUIDs
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Configurar timezone
SET timezone = 'America/Sao_Paulo';

-- Criar usuário e database (já criados pelo POSTGRES_USER/DB)
-- Apenas garantir permissões
GRANT ALL PRIVILEGES ON DATABASE fiscalmix TO fiscalmix;
