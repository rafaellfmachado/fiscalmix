<template>
  <div class="register-container">
    <Card class="register-card">
      <template #header>
        <div class="text-center py-4">
          <h1 class="text-3xl font-bold text-primary">Criar Conta</h1>
          <p class="text-gray-600 mt-2">Comece a usar o FiscalMix gratuitamente</p>
        </div>
      </template>

      <template #content>
        <form @submit.prevent="handleRegister">
          <div class="flex flex-column gap-3">
            <div>
              <label for="account_name" class="block mb-2 font-semibold">Nome da Empresa</label>
              <InputText
                id="account_name"
                v-model="form.account_name"
                placeholder="Minha Empresa Ltda"
                class="w-full"
                required
              />
            </div>

            <div>
              <label for="name" class="block mb-2 font-semibold">Seu Nome</label>
              <InputText
                id="name"
                v-model="form.name"
                placeholder="João Silva"
                class="w-full"
                required
              />
            </div>

            <div>
              <label for="email" class="block mb-2 font-semibold">E-mail</label>
              <InputText
                id="email"
                v-model="form.email"
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
                v-model="form.password"
                placeholder="Mínimo 8 caracteres"
                toggleMask
                class="w-full"
                :class="{ 'p-invalid': errors.password }"
                required
              />
              <small v-if="errors.password" class="p-error">{{ errors.password }}</small>
            </div>

            <div>
              <label for="password_confirmation" class="block mb-2 font-semibold">Confirmar Senha</label>
              <Password
                id="password_confirmation"
                v-model="form.password_confirmation"
                placeholder="Digite a senha novamente"
                :feedback="false"
                toggleMask
                class="w-full"
                required
              />
            </div>

            <Button
              type="submit"
              label="Criar Conta"
              icon="pi pi-user-plus"
              class="w-full"
              :loading="loading"
            />

            <div class="text-center mt-3">
              <router-link to="/login" class="text-primary hover:underline">
                Já tem conta? Faça login
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

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  account_name: ''
})

const loading = ref(false)
const errors = ref<Record<string, string>>({})

async function handleRegister() {
  loading.value = true
  errors.value = {}

  try {
    await authStore.register(form.value)
    toast.add({
      severity: 'success',
      summary: 'Conta criada!',
      detail: 'Faça login para continuar',
      life: 5000
    })
    router.push('/login')
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      toast.add({
        severity: 'error',
        summary: 'Erro no cadastro',
        detail: error.response?.data?.message || 'Erro ao criar conta',
        life: 5000
      })
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.register-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 1rem;
}

.register-card {
  width: 100%;
  max-width: 500px;
}
</style>
