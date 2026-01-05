<template>
  <div class="login-container">
    <Card class="login-card">
      <template #header>
        <div class="text-center py-4">
          <h1 class="text-3xl font-bold text-primary">FiscalMix</h1>
          <p class="text-gray-600 mt-2">Gestão de Documentos Fiscais</p>
        </div>
      </template>

      <template #content>
        <form @submit.prevent="handleLogin">
          <div class="flex flex-column gap-3">
            <div>
              <label for="email" class="block mb-2 font-semibold">E-mail</label>
              <InputText
                id="email"
                v-model="email"
                type="email"
                placeholder="seu@email.com"
                class="w-full"
                :class="{ 'p-invalid': errors.email }"
                required
              />
              <small v-if="errors.email" class="p-error">{{ errors.email }}</small>
            </div>

            <div>
              <label for="password" class="block mb-2 font-semibold">Senha</label>
              <Password
                id="password"
                v-model="password"
                placeholder="••••••••"
                :feedback="false"
                toggleMask
                class="w-full"
                :class="{ 'p-invalid': errors.password }"
                required
              />
              <small v-if="errors.password" class="p-error">{{ errors.password }}</small>
            </div>

            <Button
              type="submit"
              label="Entrar"
              icon="pi pi-sign-in"
              class="w-full"
              :loading="loading"
            />

            <div class="text-center mt-3">
              <router-link to="/register" class="text-primary hover:underline">
                Não tem conta? Cadastre-se
              </router-link>
            </div>
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const loading = ref(false)
const errors = ref<Record<string, string>>({})

async function handleLogin() {
  loading.value = true
  errors.value = {}

  try {
    await authStore.login(email.value, password.value)
    toast.add({
      severity: 'success',
      summary: 'Login realizado',
      detail: 'Bem-vindo ao FiscalMix!',
      life: 3000
    })
    router.push('/')
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      toast.add({
        severity: 'error',
        summary: 'Erro no login',
        detail: error.response?.data?.message || 'Erro ao fazer login',
        life: 5000
      })
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 1rem;
}

.login-card {
  width: 100%;
  max-width: 450px;
}
</style>
