<template>
  <div class="auth-container">
    <div class="auth-background">
      <div class="gradient-orb orb-1"></div>
      <div class="gradient-orb orb-2"></div>
      <div class="gradient-orb orb-3"></div>
    </div>

    <div class="auth-card">
      <div class="auth-header">
        <div class="logo">
          <i class="pi pi-chart-line"></i>
          <span>FiscalMix</span>
        </div>
        <h1>Bem-vindo de volta</h1>
        <p>Entre com suas credenciais para continuar</p>
      </div>

      <form @submit.prevent="handleLogin" class="auth-form">
        <div class="form-group">
          <label for="email">
            <i class="pi pi-envelope"></i>
            E-mail
          </label>
          <InputText
            id="email"
            v-model="email"
            type="email"
            placeholder="seu@email.com"
            :class="{ 'p-invalid': errors.email }"
            class="premium-input"
          />
          <small v-if="errors.email" class="p-error">{{ errors.email }}</small>
        </div>

        <div class="form-group">
          <label for="password">
            <i class="pi pi-lock"></i>
            Senha
          </label>
          <Password
            id="password"
            v-model="password"
            placeholder="••••••••"
            :feedback="false"
            toggleMask
            :class="{ 'p-invalid': errors.password }"
            class="premium-input"
          />
          <small v-if="errors.password" class="p-error">{{ errors.password }}</small>
        </div>

        <div class="form-actions">
          <a href="#" class="forgot-link">Esqueceu a senha?</a>
        </div>

        <Button
          type="submit"
          label="Entrar"
          :loading="loading"
          class="premium-button"
          icon="pi pi-sign-in"
          iconPos="right"
        />

        <div class="divider">
          <span>ou</span>
        </div>

        <router-link to="/register" class="register-link">
          <Button
            label="Criar nova conta"
            outlined
            class="premium-button-outline"
            icon="pi pi-user-plus"
          />
        </router-link>
      </form>
    </div>

    <div class="auth-footer">
      <p>© 2026 FiscalMix. Gestão fiscal inteligente.</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const loading = ref(false)
const errors = ref<any>({})

async function handleLogin() {
  errors.value = {}
  
  if (!email.value) {
    errors.value.email = 'E-mail é obrigatório'
    return
  }
  if (!password.value) {
    errors.value.password = 'Senha é obrigatória'
    return
  }

  loading.value = true
  try {
    await authStore.login(email.value, password.value)
    toast.add({
      severity: 'success',
      summary: 'Login realizado!',
      detail: 'Bem-vindo ao FiscalMix',
      life: 3000
    })
    router.push('/')
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Erro no login',
      detail: error.response?.data?.message || 'Credenciais inválidas',
      life: 5000
    })
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap');

.auth-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
  font-family: 'Inter', sans-serif;
}

.auth-background {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  z-index: 0;
}

.gradient-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.6;
  animation: float 20s ease-in-out infinite;
}

.orb-1 {
  width: 500px;
  height: 500px;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  top: -10%;
  left: -10%;
  animation-delay: 0s;
}

.orb-2 {
  width: 400px;
  height: 400px;
  background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
  bottom: -10%;
  right: -10%;
  animation-delay: 7s;
}

.orb-3 {
  width: 300px;
  height: 300px;
  background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  animation-delay: 14s;
}

@keyframes float {
  0%, 100% {
    transform: translate(0, 0) scale(1);
  }
  33% {
    transform: translate(30px, -30px) scale(1.1);
  }
  66% {
    transform: translate(-20px, 20px) scale(0.9);
  }
}

.auth-card {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 440px;
  padding: 3rem;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  box-shadow: 
    0 8px 32px rgba(0, 0, 0, 0.1),
    0 0 0 1px rgba(255, 255, 255, 0.2) inset;
  animation: slideUp 0.6s ease-out;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.auth-header {
  text-align: center;
  margin-bottom: 2.5rem;
}

.logo {
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  font-family: 'Outfit', sans-serif;
  font-size: 1.75rem;
  font-weight: 800;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 1.5rem;
}

.logo i {
  font-size: 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.auth-header h1 {
  font-family: 'Outfit', sans-serif;
  font-size: 2rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0 0 0.5rem 0;
}

.auth-header p {
  color: #718096;
  font-size: 0.95rem;
  margin: 0;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: #2d3748;
  font-size: 0.9rem;
}

.form-group label i {
  color: #667eea;
}

:deep(.premium-input input),
:deep(.premium-input.p-inputtext) {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 0.95rem;
  transition: all 0.3s ease;
  background: white;
}

:deep(.premium-input input:focus),
:deep(.premium-input.p-inputtext:focus) {
  border-color: #667eea;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
  outline: none;
}

:deep(.premium-input input:hover),
:deep(.premium-input.p-inputtext:hover) {
  border-color: #cbd5e0;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: -0.5rem;
}

.forgot-link {
  color: #667eea;
  font-size: 0.875rem;
  font-weight: 500;
  text-decoration: none;
  transition: color 0.2s;
}

.forgot-link:hover {
  color: #764ba2;
  text-decoration: underline;
}

:deep(.premium-button) {
  width: 100%;
  padding: 0.875rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 12px;
  font-weight: 600;
  font-size: 1rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

:deep(.premium-button:hover) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

:deep(.premium-button:active) {
  transform: translateY(0);
}

.divider {
  display: flex;
  align-items: center;
  text-align: center;
  color: #a0aec0;
  font-size: 0.875rem;
  margin: 0.5rem 0;
}

.divider::before,
.divider::after {
  content: '';
  flex: 1;
  border-bottom: 1px solid #e2e8f0;
}

.divider span {
  padding: 0 1rem;
}

.register-link {
  text-decoration: none;
}

:deep(.premium-button-outline) {
  width: 100%;
  padding: 0.875rem;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-weight: 600;
  color: #4a5568;
  background: white;
  transition: all 0.3s ease;
}

:deep(.premium-button-outline:hover) {
  border-color: #667eea;
  color: #667eea;
  background: rgba(102, 126, 234, 0.05);
}

.auth-footer {
  position: absolute;
  bottom: 2rem;
  left: 0;
  right: 0;
  text-align: center;
  color: rgba(255, 255, 255, 0.9);
  font-size: 0.875rem;
  z-index: 1;
}

.p-error {
  color: #e53e3e;
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

@media (max-width: 640px) {
  .auth-card {
    margin: 1rem;
    padding: 2rem 1.5rem;
  }

  .auth-header h1 {
    font-size: 1.75rem;
  }
}
</style>
