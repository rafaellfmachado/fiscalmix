<template>
  <div class="sync-page">
    <div class="topbar">
      <h1>FiscalMix</h1>
      <div class="user-menu">
        <router-link to="/companies">
          <Button icon="pi pi-building" label="Empresas" text />
        </router-link>
        <router-link to="/documents">
          <Button icon="pi pi-file" label="Documentos" text />
        </router-link>
        <Button icon="pi pi-sign-out" label="Sair" text @click="handleLogout" />
      </div>
    </div>

    <div class="content">
      <div class="page-header">
        <h2>Sincronização</h2>
      </div>

      <!-- Trigger Sync -->
      <Card class="trigger-card">
        <template #title>Nova Sincronização</template>
        <template #content>
          <div class="trigger-form">
            <div class="form-field">
              <label>Selecione a Empresa</label>
              <Dropdown
                v-model="selectedCompanyId"
                :options="companies"
                optionLabel="razao_social"
                optionValue="id"
                placeholder="Escolha uma empresa"
                class="w-full"
              />
            </div>
            <Button
              label="Sincronizar Agora"
              icon="pi pi-sync"
              :loading="syncStore.loading"
              :disabled="!selectedCompanyId"
              @click="triggerSync"
            />
          </div>
        </template>
      </Card>

      <!-- Current Sync Result -->
      <Card v-if="syncStore.currentSync" class="result-card">
        <template #title>Resultado da Última Sincronização</template>
        <template #content>
          <div class="sync-result">
            <div class="result-item">
              <span class="label">Status:</span>
              <Tag
                :value="syncStore.currentSync.status"
                :severity="getStatusSeverity(syncStore.currentSync.status)"
              />
            </div>
            <div class="result-item">
              <span class="label">NSU Inicial:</span>
              <span>{{ syncStore.currentSync.nsu_start }}</span>
            </div>
            <div class="result-item">
              <span class="label">Último NSU:</span>
              <span>{{ syncStore.currentSync.ult_nsu }}</span>
            </div>
            <div class="result-item">
              <span class="label">Documentos Encontrados:</span>
              <span>{{ syncStore.currentSync.docs_found }}</span>
            </div>
            <div class="result-item">
              <span class="label">Documentos Salvos:</span>
              <span>{{ syncStore.currentSync.docs_saved }}</span>
            </div>
            <div v-if="syncStore.currentSync.message" class="result-message">
              <Message :severity="syncStore.currentSync.status === 'completed' ? 'success' : 'info'">
                {{ syncStore.currentSync.message }}
              </Message>
            </div>
          </div>
        </template>
      </Card>

      <!-- Sync History -->
      <Card class="history-card">
        <template #title>Histórico de Sincronizações</template>
        <template #content>
          <div v-if="selectedCompanyId" class="history-actions">
            <Button
              label="Carregar Histórico"
              icon="pi pi-refresh"
              @click="loadHistory"
              :loading="loading"
            />
          </div>
          <DataTable
            v-if="syncStore.syncRuns.length > 0"
            :value="syncStore.syncRuns"
            stripedRows
          >
            <Column field="created_at" header="Data/Hora" style="width: 180px">
              <template #body="{ data }">
                {{ formatDateTime(data.created_at) }}
              </template>
            </Column>
            <Column field="connector_type" header="Conector" style="width: 120px" />
            <Column field="nsu_start" header="NSU Inicial" style="width: 100px" />
            <Column field="ult_nsu" header="Último NSU" style="width: 100px" />
            <Column field="docs_found" header="Encontrados" style="width: 100px" />
            <Column field="docs_saved" header="Salvos" style="width: 100px" />
            <Column field="status" header="Status" style="width: 120px">
              <template #body="{ data }">
                <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
              </template>
            </Column>
            <Column field="completed_at" header="Concluído" style="width: 180px">
              <template #body="{ data }">
                {{ data.completed_at ? formatDateTime(data.completed_at) : '-' }}
              </template>
            </Column>
          </DataTable>
          <div v-else class="empty-state">
            <p>Selecione uma empresa e clique em "Carregar Histórico"</p>
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import { useSyncStore } from '@/stores/sync'
import axios from 'axios'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Dropdown from 'primevue/dropdown'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Message from 'primevue/message'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const syncStore = useSyncStore()

const companies = ref<any[]>([])
const selectedCompanyId = ref<string | null>(null)
const loading = ref(false)

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

async function triggerSync() {
  if (!selectedCompanyId.value) return

  try {
    await syncStore.triggerSync(selectedCompanyId.value)
    toast.add({
      severity: 'success',
      summary: 'Sincronização Iniciada',
      detail: `${syncStore.currentSync?.docs_saved || 0} documentos salvos`,
      life: 5000
    })
    // Reload history
    await loadHistory()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Erro na Sincronização',
      detail: error.response?.data?.error || 'Erro ao sincronizar',
      life: 5000
    })
  }
}

async function loadHistory() {
  if (!selectedCompanyId.value) return

  loading.value = true
  try {
    await syncStore.fetchSyncRuns(selectedCompanyId.value)
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erro', detail: 'Erro ao carregar histórico', life: 3000 })
  } finally {
    loading.value = false
  }
}

function formatDateTime(date: string) {
  return new Date(date).toLocaleString('pt-BR')
}

function getStatusSeverity(status: string) {
  const map: any = {
    pending: 'info',
    running: 'warning',
    completed: 'success',
    failed: 'danger'
  }
  return map[status] || 'secondary'
}

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}
</script>

<style scoped>
.sync-page {
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

.page-header {
  margin-bottom: 2rem;
}

.trigger-card,
.result-card,
.history-card {
  margin-bottom: 2rem;
}

.trigger-form {
  display: flex;
  gap: 1rem;
  align-items: flex-end;
}

.form-field {
  flex: 1;
}

.form-field label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.sync-result {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.result-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.result-item .label {
  font-weight: 500;
  color: #666;
  font-size: 0.9rem;
}

.result-message {
  grid-column: 1 / -1;
  margin-top: 1rem;
}

.history-actions {
  margin-bottom: 1rem;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #999;
}
</style>
