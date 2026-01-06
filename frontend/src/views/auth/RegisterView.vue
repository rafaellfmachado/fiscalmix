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
        <h1>Crie sua conta</h1>
        <p>Comece a gerenciar seus documentos fiscais agora</p>
      </div>

      <form @submit.prevent="handleRegister" class="auth-form">
        <div class="form-row">
          <div class="form-group">
            <label for="name">
              <i class="pi pi-user"></i>
              Nome
            </label>
            <InputText
              id="name"
              v-model="name"
              placeholder="Seu nome completo"
              :class="{ 'p-invalid': errors.name }"
              class="premium-input"
            />
            <small v-if="errors.name" class="p-error">{{ errors.name }}</small>
          </div>

          <div class="form-group">
            <label for="account_name">
              <i class="pi pi-building"></i>
              Empresa
            </label>
            <InputText
              id="account_name"
              v-model="accountName"
              placeholder="Nome da empresa"
              :class="{ 'p-invalid': errors.account_name }"
              class="premium-input"
            />
            <small v-if="errors.account_name" class="p-error">{{ errors.account_name }}</small>
          </div>
        </div>

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
            placeholder="Mínimo 8 caracteres"
            toggleMask
            :class="{ 'p-invalid': errors.password }"
            class="premium-input"
          >
            <template #footer>
              <div class="password-strength">
                <div class="strength-bar" :class="passwordStrength"></div>
              </div>
            </template>
          </Password>
          <small v-if="errors.password" class="p-error">{{ errors.password }}</small>
        </div>

        <div class="form-group">
          <label for="password_confirmation">
            <i class="pi pi-lock"></i>
            Confirmar Senha
          </label>
          <Password
            id="password_confirmation"
            v-model="passwordConfirmation"
            placeholder="Digite a senha novamente"
            :feedback="false"
            toggleMask
            :class="{ 'p-invalid': errors.password_confirmation }"
            class="premium-input"
          />
          <small v-if="errors.password_confirmation" class="p-error">{{ errors.password_confirmation }}</small>
        </div>

        <Button
          type="submit"
          label="Criar Conta"
          :loading="loading"
          class="premium-button"
          icon="pi pi-check"
          iconPos="right"
        />

        <div class="divider">
          <span>ou</span>
        </div>

        <router-link to="/login" class="login-link">
          <Button
            label="Já tenho uma conta"
            outlined
            class="premium-button-outline"
            icon="pi pi-sign-in"
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
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const name = ref('')
const email = ref('')
const accountName = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const loading = ref(false)
const errors = ref<any>({})

const passwordStrength = computed(() => {
  const pwd = password.value
  if (pwd.length === 0) return ''
  if (pwd.length < 6) return 'weak'
  if (pwd.length < 10) return 'medium'
  return 'strong'
})

async function handleRegister() {
  errors.value = {}
  
  if (!name.value) errors.value.name = 'Nome é obrigatório'
  if (!accountName.value) errors.value.account_name = 'Nome da empresa é obrigatório'
  if (!email.value) errors.value.email = 'E-mail é obrigatório'
  if (!password.value) errors.value.password = 'Senha é obrigatória'
  if (password.value !== passwordConfirmation.value) {
    errors.value.password_confirmation = 'As senhas não coincidem'
  }

  if (Object.keys(errors.value).length > 0) return

  loading.value = true
  try {
    await authStore.register({
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
      account_name: accountName.value
    })
    toast.add({
      severity: 'success',
      summary: 'Conta criada!',
      detail: 'Bem-vindo ao FiscalMix',
      life: 3000
    })
    router.push('/')
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Erro no cadastro',
      detail: error.response?.data?.message || 'Erro ao criar conta',
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
  padding: 2rem 1rem;
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
  0%, 100% { transform: translate(0, 0) scale(1); }
  33% { transform: translate(30px, -30px) scale(1.1); }
  66% { transform: translate(-20px, 20px) scale(0.9); }
}

.auth-card {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 520px;
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
  from { opacity: 0; transform: translateY(30px); }
  to { opacity: 1; transform: translateY(0); }
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

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
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

.password-strength {
  margin-top: 0.5rem;
}

.strength-bar {
  height: 4px;
  border-radius: 2px;
  transition: all 0.3s;
  background: #e2e8f0;
}

.strength-bar.weak {
  width: 33%;
  background: #f56565;
}

.strength-bar.medium {
  width: 66%;
  background: #ed8936;
}

.strength-bar.strong {
  width: 100%;
  background: #48bb78;
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

.login-link {
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
}

@media (max-width: 640px) {
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .auth-card {
    padding: 2rem 1.5rem;
  }
}
</style>
