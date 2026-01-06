<template>
  <div class="admin-panel">
    <div class="admin-header">
      <div>
        <h1>Painel Administrativo</h1>
        <p>Gerenciar clientes e contas do SaaS</p>
      </div>
      <div class="admin-actions">
        <Button 
          v-if="impersonating" 
          label="Sair do Impersonate" 
          severity="danger" 
          icon="pi pi-sign-out"
          @click="stopImpersonate" 
        />
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="admin-stats">
      <Card class="stat-card">
        <template #content>
          <div class="stat-content">
            <i class="pi pi-users stat-icon"></i>
            <div>
              <span class="stat-label">Total de Contas</span>
              <span class="stat-value">{{ accounts.length }}</span>
            </div>
          </div>
        </template>
      </Card>

      <Card class="stat-card">
        <template #content>
          <div class="stat-content">
            <i class="pi pi-building stat-icon"></i>
            <div>
              <span class="stat-label">Empresas Cadastradas</span>
              <span class="stat-value">{{ totalCompanies }}</span>
            </div>
          </div>
        </template>
      </Card>

      <Card class="stat-card">
        <template #content>
          <div class="stat-content">
            <i class="pi pi-file stat-icon"></i>
            <div>
              <span class="stat-label">Documentos Totais</span>
              <span class="stat-value">{{ totalDocuments }}</span>
            </div>
          </div>
        </template>
      </Card>

      <Card class="stat-card">
        <template #content>
          <div class="stat-content">
            <i class="pi pi-chart-line stat-icon"></i>
            <div>
              <span class="stat-label">Contas Ativas</span>
              <span class="stat-value">{{ activeAccounts }}</span>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Accounts Table -->
    <Card class="accounts-table">
      <template #title>Contas Cadastradas</template>
      <template #content>
        <DataTable 
          :value="accounts" 
          :loading="loading"
          stripedRows
          paginator
          :rows="10"
        >
          <Column field="name" header="Nome da Conta" sortable>
            <template #body="{ data }">
              <div class="account-name">
                <Avatar :label="data.name.charAt(0)" shape="circle" />
                <span>{{ data.name }}</span>
              </div>
            </template>
          </Column>
          <Column field="created_at" header="Criado em" sortable>
            <template #body="{ data }">
              {{ formatDate(data.created_at) }}
            </template>
          </Column>
          <Column field="users_count" header="Usuários" sortable />
          <Column field="companies_count" header="Empresas" sortable />
          <Column field="documents_count" header="Documentos" sortable />
          <Column header="Ações" style="width: 200px">
            <template #body="{ data }">
              <div class="action-buttons">
                <Button 
                  icon="pi pi-eye" 
                  text 
                  rounded
                  severity="info"
                  v-tooltip.top="'Ver Detalhes'"
                  @click="viewAccount(data)"
                />
                <Button 
                  icon="pi pi-user" 
                  text 
                  rounded
                  severity="warning"
                  v-tooltip.top="'Impersonate'"
                  @click="impersonateAccount(data)"
                />
                <Button 
                  icon="pi pi-trash" 
                  text 
                  rounded
                  severity="danger"
                  v-tooltip.top="'Excluir'"
                  @click="confirmDelete(data)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Account Detail Dialog -->
    <Dialog v-model:visible="showDetailDialog" header="Detalhes da Conta" :style="{ width: '600px' }">
      <div v-if="selectedAccount" class="account-details">
        <div class="detail-row">
          <span class="label">Nome:</span>
          <span class="value">{{ selectedAccount.name }}</span>
        </div>
        <div class="detail-row">
          <span class="label">ID:</span>
          <span class="value">{{ selectedAccount.id }}</span>
        </div>
        <div class="detail-row">
          <span class="label">Criado em:</span>
          <span class="value">{{ formatDateTime(selectedAccount.created_at) }}</span>
        </div>
        <div class="detail-row">
          <span class="label">Última atualização:</span>
          <span class="value">{{ formatDateTime(selectedAccount.updated_at) }}</span>
        </div>
        <Divider />
        <h3>Estatísticas</h3>
        <div class="stats-grid">
          <div class="stat-item">
            <i class="pi pi-users"></i>
            <span>{{ selectedAccount.users_count }} Usuários</span>
          </div>
          <div class="stat-item">
            <i class="pi pi-building"></i>
            <span>{{ selectedAccount.companies_count }} Empresas</span>
          </div>
          <div class="stat-item">
            <i class="pi pi-file"></i>
            <span>{{ selectedAccount.documents_count }} Documentos</span>
          </div>
        </div>
      </div>
    </Dialog>

    <!-- Delete Confirmation -->
    <Dialog v-model:visible="showDeleteDialog" header="Confirmar Exclusão" :style="{ width: '450px' }">
      <div class="delete-confirmation">
        <i class="pi pi-exclamation-triangle warning-icon"></i>
        <p>Tem certeza que deseja excluir a conta <strong>{{ selectedAccount?.name }}</strong>?</p>
        <p class="warning-text">Esta ação não pode ser desfeita e todos os dados serão permanentemente removidos.</p>
      </div>
      <template #footer>
        <Button label="Cancelar" text @click="showDeleteDialog = false" />
        <Button label="Excluir" severity="danger" @click="deleteAccount" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Avatar from 'primevue/avatar'
import Dialog from 'primevue/dialog'
import Divider from 'primevue/divider'

const router = useRouter()
const toast = useToast()

const accounts = ref<any[]>([])
const loading = ref(false)
const impersonating = ref(false)
const showDetailDialog = ref(false)
const showDeleteDialog = ref(false)
const selectedAccount = ref<any>(null)

const totalCompanies = computed(() => 
  accounts.value.reduce((sum, acc) => sum + (acc.companies_count || 0), 0)
)

const totalDocuments = computed(() => 
  accounts.value.reduce((sum, acc) => sum + (acc.documents_count || 0), 0)
)

const activeAccounts = computed(() => 
  accounts.value.filter(acc => acc.users_count > 0).length
)

onMounted(async () => {
  await loadAccounts()
  checkImpersonateStatus()
})

async function loadAccounts() {
  loading.value = true
  try {
    // Mock data for now - replace with real API
    accounts.value = [
      {
        id: '1',
        name: 'Empresa Demo Ltda',
        created_at: '2024-01-15T10:00:00Z',
        updated_at: '2024-01-20T15:30:00Z',
        users_count: 3,
        companies_count: 2,
        documents_count: 150
      },
      {
        id: '2',
        name: 'Tech Solutions SA',
        created_at: '2024-02-01T09:00:00Z',
        updated_at: '2024-02-10T11:20:00Z',
        users_count: 5,
        companies_count: 4,
        documents_count: 320
      },
      {
        id: '3',
        name: 'Comércio ABC',
        created_at: '2024-02-15T14:00:00Z',
        updated_at: '2024-02-18T16:45:00Z',
        users_count: 2,
        companies_count: 1,
        documents_count: 75
      }
    ]
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erro', detail: 'Erro ao carregar contas', life: 3000 })
  } finally {
    loading.value = false
  }
}

function checkImpersonateStatus() {
  impersonating.value = !!localStorage.getItem('impersonate_account_id')
}

function viewAccount(account: any) {
  selectedAccount.value = account
  showDetailDialog.value = true
}

async function impersonateAccount(account: any) {
  try {
    // Store impersonate info
    localStorage.setItem('impersonate_account_id', account.id)
    localStorage.setItem('impersonate_account_name', account.name)
    
    toast.add({
      severity: 'success',
      summary: 'Impersonate Ativo',
      detail: `Você está agora visualizando como: ${account.name}`,
      life: 5000
    })
    
    impersonating.value = true
    
    // Redirect to dashboard
    router.push('/')
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erro', detail: 'Erro ao fazer impersonate', life: 3000 })
  }
}

function stopImpersonate() {
  localStorage.removeItem('impersonate_account_id')
  localStorage.removeItem('impersonate_account_name')
  impersonating.value = false
  
  toast.add({
    severity: 'info',
    summary: 'Impersonate Encerrado',
    detail: 'Você voltou para sua conta admin',
    life: 3000
  })
  
  router.push('/admin')
}

function confirmDelete(account: any) {
  selectedAccount.value = account
  showDeleteDialog.value = true
}

async function deleteAccount() {
  try {
    // API call to delete account
    toast.add({
      severity: 'success',
      summary: 'Conta Excluída',
      detail: 'A conta foi removida com sucesso',
      life: 3000
    })
    
    showDeleteDialog.value = false
    await loadAccounts()
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Erro', detail: 'Erro ao excluir conta', life: 3000 })
  }
}

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('pt-BR')
}

function formatDateTime(date: string) {
  return new Date(date).toLocaleString('pt-BR')
}
</script>

<style scoped>
.admin-panel {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.admin-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
}

.admin-header h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #1F2937;
  margin: 0 0 0.5rem 0;
}

.admin-header p {
  color: #6B7280;
  margin: 0;
}

.admin-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  border-radius: 16px;
  border: none;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.stat-content {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  font-size: 2rem;
  color: #667eea;
}

.stat-label {
  display: block;
  font-size: 0.875rem;
  color: #6B7280;
  margin-bottom: 0.25rem;
}

.stat-value {
  display: block;
  font-size: 2rem;
  font-weight: 700;
  color: #1F2937;
}

.accounts-table {
  border-radius: 16px;
  border: none;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.account-name {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.account-details {
  padding: 1rem 0;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-bottom: 1px solid #F3F4F6;
}

.detail-row .label {
  font-weight: 600;
  color: #6B7280;
}

.detail-row .value {
  color: #1F2937;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
  margin-top: 1rem;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 1rem;
  background: #F9FAFB;
  border-radius: 12px;
}

.stat-item i {
  font-size: 1.5rem;
  color: #667eea;
}

.delete-confirmation {
  text-align: center;
  padding: 1rem;
}

.warning-icon {
  font-size: 3rem;
  color: #f59e0b;
  margin-bottom: 1rem;
}

.warning-text {
  color: #ef4444;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}
</style>
