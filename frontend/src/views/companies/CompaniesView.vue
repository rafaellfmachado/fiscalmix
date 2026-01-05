<template>
  <div class="companies-page">
    <div class="topbar">
      <h1>FiscalMix</h1>
      <Button icon="pi pi-sign-out" label="Sair" text @click="handleLogout" />
    </div>

    <div class="content">
      <div class="page-header">
        <h2>Empresas</h2>
        <Button label="Nova Empresa" icon="pi pi-plus" @click="showDialog = true" />
      </div>

      <div class="companies-grid">
        <Card v-for="company in companies" :key="company.id" class="company-card">
          <template #title>{{ company.razao_social }}</template>
          <template #subtitle>CNPJ: {{ company.cnpj }}</template>
          <template #content>
            <p><strong>UF:</strong> {{ company.uf }}</p>
            <p><strong>Status:</strong> {{ company.status }}</p>
          </template>
        </Card>

        <div v-if="companies.length === 0" class="empty-state">
          <i class="pi pi-building" style="font-size: 3rem; color: #ccc;"></i>
          <p>Nenhuma empresa cadastrada</p>
          <Button label="Cadastrar Primeira Empresa" @click="showDialog = true" />
        </div>
      </div>
    </div>

    <Dialog v-model:visible="showDialog" header="Nova Empresa" :style="{ width: '500px' }">
      <div class="flex flex-column gap-3">
        <div>
          <label>CNPJ</label>
          <InputText v-model="newCompany.cnpj" placeholder="12345678000190" class="w-full" />
        </div>
        <div>
          <label>Razão Social</label>
          <InputText v-model="newCompany.razao_social" class="w-full" />
        </div>
        <div>
          <label>UF</label>
          <InputText v-model="newCompany.uf" maxlength="2" class="w-full" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" text @click="showDialog = false" />
        <Button label="Salvar" @click="createCompany" :loading="loading" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const companies = ref<any[]>([])
const showDialog = ref(false)
const loading = ref(false)
const newCompany = ref({
  cnpj: '',
  razao_social: '',
  uf: ''
})

onMounted(async () => {
  await loadCompanies()
})

async function loadCompanies() {
  try {
    const response = await axios.get('/api/companies')
    companies.value = response.data.data
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erro', detail: 'Erro ao carregar empresas', life: 3000 })
  }
}

async function createCompany() {
  loading.value = true
  try {
    await axios.post('/api/companies', newCompany.value)
    toast.add({ severity: 'success', summary: 'Sucesso', detail: 'Empresa criada!', life: 3000 })
    showDialog.value = false
    newCompany.value = { cnpj: '', razao_social: '', uf: '' }
    await loadCompanies()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Erro', detail: error.response?.data?.message || 'Erro ao criar empresa', life: 5000 })
  } finally {
    loading.value = false
  }
}

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}
</script>

<style scoped>
.companies-page {
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

.content {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.companies-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.empty-state {
  grid-column: 1 / -1;
  text-align: center;
  padding: 3rem;
  color: #6B7280;
}
</style>
