# Configuração de E-mail - FiscalMix

## 📧 Visão Geral

O FiscalMix utiliza e-mail para:
- ✅ **Verificação de conta** (confirmação de e-mail após registro)
- 🔑 **Recuperação de senha** (reset password)
- 📨 **Notificações** (certificados expirando, sync falhou, etc.)

---

## 🔧 Configuração Rápida

### 1. Mailtrap (Desenvolvimento/Testes)

**Recomendado para desenvolvimento**. Captura todos os e-mails sem enviar de verdade.

1. Crie uma conta gratuita em [mailtrap.io](https://mailtrap.io)
2. Copie as credenciais SMTP
3. Configure no `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=seu_username_mailtrap
MAIL_PASSWORD=sua_senha_mailtrap
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@fiscalmix.com"
MAIL_FROM_NAME="FiscalMix"
```

---

### 2. Gmail (Produção - Pequeno Volume)

**Limite**: ~500 e-mails/dia

1. Ative a verificação em 2 etapas na sua conta Google
2. Gere uma "Senha de App" em https://myaccount.google.com/apppasswords
3. Configure no `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seu-email@gmail.com
MAIL_PASSWORD=sua-senha-de-app-16-digitos
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="seu-email@gmail.com"
MAIL_FROM_NAME="FiscalMix"
```

⚠️ **Importante**: Use a senha de app de 16 dígitos, NÃO a senha normal da conta.

---

### 3. SendGrid (Produção - Alto Volume)

**Limite**: 100 e-mails/dia (free) ou ilimitado (pago)

1. Crie uma conta em [sendgrid.com](https://sendgrid.com)
2. Crie uma API Key em Settings → API Keys
3. Configure no `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.sua-api-key-aqui
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@fiscalmix.com"
MAIL_FROM_NAME="FiscalMix"
```

⚠️ **Importante**: O username é literalmente `apikey`, não substitua.

---

### 4. Mailgun (Produção - Alternativa)

**Limite**: 5.000 e-mails/mês (free)

1. Crie uma conta em [mailgun.com](https://mailgun.com)
2. Adicione e verifique seu domínio
3. Copie as credenciais SMTP
4. Configure no `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@seu-dominio.mailgun.org
MAIL_PASSWORD=sua-senha-mailgun
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@seu-dominio.com"
MAIL_FROM_NAME="FiscalMix"
```

---

## ⚙️ Configurações Adicionais

### Tempo de Expiração dos Links

```env
# Link de verificação de e-mail (padrão: 60 minutos)
EMAIL_VERIFICATION_EXPIRE=60

# Link de reset de senha (padrão: 60 minutos)
PASSWORD_RESET_EXPIRE=60
```

### URLs do Frontend

```env
# URL base do frontend
FRONTEND_URL=http://localhost:5173

# Página de verificação de e-mail
FRONTEND_VERIFY_EMAIL_URL=http://localhost:5173/verify-email

# Página de reset de senha
FRONTEND_RESET_PASSWORD_URL=http://localhost:5173/reset-password
```

---

## 🧪 Testando a Configuração

### Via Tinker (Laravel)

```bash
docker-compose exec backend php artisan tinker
```

```php
Mail::raw('Teste de e-mail do FiscalMix', function ($message) {
    $message->to('seu-email@exemplo.com')
            ->subject('Teste SMTP');
});
```

### Via Endpoint (API)

Após implementar o endpoint de registro:

```bash
curl -X POST http://localhost:8001/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Teste",
    "email": "teste@exemplo.com",
    "password": "senha123",
    "password_confirmation": "senha123",
    "account_name": "Teste Account"
  }'
```

Verifique o e-mail de verificação no Mailtrap ou na caixa de entrada.

---

## 🔍 Troubleshooting

### Erro: "Connection refused"

**Causa**: Credenciais SMTP incorretas ou firewall bloqueando.

**Solução**:
1. Verifique username e password
2. Teste a porta com: `telnet smtp.example.com 587`
3. Verifique se o firewall permite conexões SMTP

### Erro: "Authentication failed"

**Causa**: Senha incorreta ou autenticação 2FA não configurada.

**Solução**:
- **Gmail**: Use senha de app, não a senha normal
- **SendGrid**: Username deve ser `apikey`
- **Mailgun**: Verifique se o domínio está verificado

### E-mails não chegam (Gmail/SendGrid)

**Causa**: E-mails podem estar indo para spam.

**Solução**:
1. Configure SPF e DKIM no seu domínio
2. Use um domínio verificado (não @gmail.com em produção)
3. Aqueça o IP enviando poucos e-mails inicialmente

### Erro: "TLS negotiation failed"

**Causa**: Porta ou encryption incorretos.

**Solução**:
- Porta 587: Use `MAIL_ENCRYPTION=tls`
- Porta 465: Use `MAIL_ENCRYPTION=ssl`
- Porta 25: Use `MAIL_ENCRYPTION=null` (não recomendado)

---

## 📊 Comparação de Provedores

| Provedor | Free Tier | Melhor Para | Dificuldade |
|----------|-----------|-------------|-------------|
| **Mailtrap** | Ilimitado (dev) | Desenvolvimento | ⭐ Fácil |
| **Gmail** | 500/dia | Testes/MVP | ⭐⭐ Médio |
| **SendGrid** | 100/dia | Produção | ⭐⭐ Médio |
| **Mailgun** | 5k/mês | Produção | ⭐⭐⭐ Difícil |
| **AWS SES** | 62k/mês | Grande escala | ⭐⭐⭐⭐ Muito difícil |

---

## 🚀 Recomendações

### Desenvolvimento
```env
MAIL_MAILER=log  # Salva e-mails em storage/logs/laravel.log
```
ou
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io  # Captura sem enviar
```

### Staging/Homologação
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net  # Free tier: 100/dia
```

### Produção
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net  # Pago: ilimitado
# ou
MAIL_MAILER=ses  # AWS SES (mais barato em escala)
```

---

## 📝 Checklist de Configuração

- [ ] Escolher provedor SMTP
- [ ] Criar conta e obter credenciais
- [ ] Configurar `.env` com credenciais
- [ ] Testar envio via Tinker
- [ ] Configurar SPF/DKIM (produção)
- [ ] Testar registro de usuário
- [ ] Testar recuperação de senha
- [ ] Monitorar taxa de entrega

---

**Última atualização**: 2026-01-05  
**Versão**: 1.0
