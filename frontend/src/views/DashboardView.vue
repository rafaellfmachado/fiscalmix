<template>
  <div class="dashboard">
    <div class="topbar">
      <h1>FiscalMix</h1>
      <div class="user-menu">
        <span>{{ authStore.user?.name }}</span>
        <Button
          icon="pi pi-sign-out"
          label="Sair"
          text
          @click="handleLogout"
        />
      </div>
    </div>

    <div class="content">
      <div class="welcome-card">
        <h2>Bem-vindo ao FiscalMix! 🎉</h2>
        <p>Sua conta <strong>{{ authStore.user?.account.name }}</strong> está ativa.</p>
        <p class="mt-3">Plano atual: <strong>{{ authStore.user?.account.plan }}</strong></p>
        
        <div class="mt-4">
          <router-link to="/companies">
            <Button label="Gerenciar Empresas" icon="pi pi-building" />
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import Button from 'primevue/button'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

onMounted(async () => {
  if (!authStore.user) {
    await authStore.fetchUser()
  }
})

async function handleLogout() {
  await authStore.logout()
  toast.add({
    severity: 'info',
    summary: 'Logout',
    detail: 'Até logo!',
    life: 3000
  })
  router.push('/login')
}
</script>

<style scoped>
.dashboard {
  min-height: 100vh;
  background: #F3F4F6;
}

.topbar {
  background: white;
  padding: 1rem 2rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.topbar h1 {
  color: var(--primary-color);
  font-size: 1.5rem;
  margin: 0;
}

.user-menu {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.content {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.welcome-card {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.welcome-card h2 {
  margin: 0 0 1rem 0;
  color: #1F2937;
}

.welcome-card p {
  color: #6B7280;
  line-height: 1.6;
}

.mt-3 {
  margin-top: 0.75rem;
}

.mt-4 {
  margin-top: 1rem;
}
</style>
