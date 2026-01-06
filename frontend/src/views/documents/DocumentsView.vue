<template>
  <div class="documents-page">
    <div class="topbar">
      <h1>FiscalMix</h1>
      <div class="user-menu">
        <router-link to="/companies">
          <Button icon="pi pi-building" label="Empresas" text />
        </router-link>
        <Button icon="pi pi-sign-out" label="Sair" text @click="handleLogout" />
      </div>
    </div>

    <div class="content">
      <div class="page-header">
        <h2>Documentos Fiscais</h2>
        <Button label="Filtros" icon="pi pi-filter" @click="showFilters = !showFilters" />
      </div>

      <!-- Filters Panel -->
      <Card v-if="showFilters" class="filters-card">
        <template #content>
          <div class="filters-grid">
            <div>
              <label>Tipo de Documento</label>
              <Dropdown
                v-model="localFilters.doc_type"
                :options="docTypes"
                optionLabel="label"
                optionValue="value"
                placeholder="Todos"
                class="w-full"
              />
            </div>
            <div>
              <label>Direção</label>
              <Dropdown
                v-model="localFilters.direction"
                :options="directions"
                optionLabel="label"
                optionValue="value"
                placeholder="Todas"
                class="w-full"
              />
            </div>
            <div>
              <label>Status</label>
              <Dropdown
                v-model="localFilters.status"
                :options="statuses"
                optionLabel="label"
                optionValue="value"
                placeholder="Todos"
                class="w-full"
              />
            </div>
            <div>
              <label>Data Inicial</label>
              <Calendar v-model="localFilters.start_date" dateFormat="yy-mm-dd" class="w-full" />
            </div>
            <div>
              <label>Data Final</label>
              <Calendar v-model="localFilters.end_date" dateFormat="yy-mm-dd" class="w-full" />
            </div>
            <div>
              <label>Buscar</label>
              <InputText
                v-model="localFilters.search"
                placeholder="Chave, número, CNPJ..."
                class="w-full"
              />
            </div>
          </div>
          <div class="filter-actions">
            <Button label="Limpar" icon="pi pi-times" text @click="clearFilters" />
            <Button label="Aplicar" icon="pi pi-check" @click="applyFilters" />
          </div>
        </template>
      </Card>

      <!-- Documents Table -->
      <Card class="documents-table">
        <template #content>
          <DataTable
            :value="documentsStore.documents"
            :loading="documentsStore.loading"
            stripedRows
            @row-click="showDocument"
          >
            <Column field="doc_type" header="Tipo" style="width: 80px">
              <template #body="{ data }">
                <Tag :value="data.doc_type" :severity="getDocTypeSeverity(data.doc_type)" />
              </template>
            </Column>
            <Column field="number" header="Número" style="width: 100px" />
            <Column field="issue_date" header="Data Emissão" style="width: 120px">
              <template #body="{ data }">
                {{ formatDate(data.issue_date) }}
              </template>
            </Column>
            <Column field="direction" header="Direção" style="width: 100px">
              <template #body="{ data }">
                <Tag
                  :value="data.direction === 'inbound' ? 'Entrada' : 'Saída'"
                  :severity="data.direction === 'inbound' ? 'info' : 'success'"
                />
              </template>
            </Column>
            <Column field="issuer_name" header="Emitente" />
            <Column field="recipient_name" header="Destinatário" />
            <Column field="total_value" header="Valor" style="width: 120px">
              <template #body="{ data }">
                {{ formatCurrency(data.total_value) }}
              </template>
            </Column>
            <Column field="status" header="Status" style="width: 100px">
              <template #body="{ data }">
                <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
              </template>
            </Column>
          </DataTable>

          <Paginator
            :rows="20"
            :totalRecords="documentsStore.total"
            :first="(documentsStore.currentPage - 1) * 20"
            @page="onPageChange"
          />
        </template>
      </Card>
    </div>

    <!-- Document Detail Sidebar -->
    <Sidebar v-model:visible="showDetail" position="right" style="width: 500px">
      <template #header>
        <h3>Detalhes do Documento</h3>
      </template>
      <div v-if="selectedDocument" class="document-detail">
        <div class="detail-section">
          <h4>Informações Gerais</h4>
          <p><strong>Tipo:</strong> {{ selectedDocument.doc_type }}</p>
          <p><strong>Número:</strong> {{ selectedDocument.number }}</p>
          <p><strong>Série:</strong> {{ selectedDocument.series }}</p>
          <p><strong>Chave de Acesso:</strong> {{ selectedDocument.access_key }}</p>
          <p><strong>Data de Emissão:</strong> {{ formatDate(selectedDocument.issue_date) }}</p>
          <p><strong>Status:</strong> {{ selectedDocument.status }}</p>
        </div>

        <div class="detail-section">
          <h4>Emitente</h4>
          <p><strong>CNPJ:</strong> {{ selectedDocument.issuer_cnpj }}</p>
          <p><strong>Nome:</strong> {{ selectedDocument.issuer_name }}</p>
        </div>

        <div class="detail-section">
          <h4>Destinatário</h4>
          <p><strong>CNPJ:</strong> {{ selectedDocument.recipient_cnpj }}</p>
          <p><strong>Nome:</strong> {{ selectedDocument.recipient_name }}</p>
        </div>

        <div class="detail-section">
          <h4>Valores</h4>
          <p><strong>Valor Total:</strong> {{ formatCurrency(selectedDocument.total_value) }}</p>
        </div>

        <div class="detail-actions">
          <Button label="Download XML" icon="pi pi-download" @click="downloadXml" />
        </div>
      </div>
    </Sidebar>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import { useDocumentsStore } from '@/stores/documents'
import Button from 'primevue/button'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import InputText from 'primevue/inputtext'
import Paginator from 'primevue/paginator'
import Sidebar from 'primevue/sidebar'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const documentsStore = useDocumentsStore()

const showFilters = ref(false)
const showDetail = ref(false)
const selectedDocument = ref<any>(null)
const localFilters = ref<any>({})

const docTypes = [
  { label: 'NF-e', value: 'NFE' },
  { label: 'CT-e', value: 'CTE' },
  { label: 'MDF-e', value: 'MDFE' },
  { label: 'NFS-e', value: 'NFSE' }
]

const directions = [
  { label: 'Entrada', value: 'inbound' },
  { label: 'Saída', value: 'outbound' }
]

const statuses = [
  { label: 'Autorizado', value: 'authorized' },
  { label: 'Cancelado', value: 'canceled' },
  { label: 'Negado', value: 'denied' }
]

onMounted(async () => {
  await documentsStore.fetchDocuments()
})

function applyFilters() {
  documentsStore.setFilters(localFilters.value)
  documentsStore.fetchDocuments()
  showFilters.value = false
}

function clearFilters() {
  localFilters.value = {}
  documentsStore.clearFilters()
  documentsStore.fetchDocuments()
}

async function showDocument(event: any) {
  selectedDocument.value = await documentsStore.fetchDocument(event.data.id)
  showDetail.value = true
}

async function downloadXml() {
  if (selectedDocument.value) {
    await documentsStore.downloadXml(selectedDocument.value.id)
    toast.add({ severity: 'success', summary: 'Download iniciado', life: 3000 })
  }
}

function onPageChange(event: any) {
  documentsStore.currentPage = event.page + 1
  documentsStore.fetchDocuments()
}

function formatDate(date: string) {
  return new Date(date).toLocaleDateString('pt-BR')
}

function formatCurrency(value: number) {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value)
}

function getDocTypeSeverity(type: string) {
  const map: any = { NFE: 'success', CTE: 'info', MDFE: 'warning', NFSE: 'secondary' }
  return map[type] || 'secondary'
}

function getStatusSeverity(status: string) {
  const map: any = { authorized: 'success', canceled: 'danger', denied: 'warning' }
  return map[status] || 'secondary'
}

async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}
</script>

<style scoped>
.documents-page {
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
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.filters-card {
  margin-bottom: 1.5rem;
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1rem;
}

.filter-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

.document-detail {
  padding: 1rem 0;
}

.detail-section {
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #eee;
}

.detail-section h4 {
  margin: 0 0 1rem 0;
  color: #333;
}

.detail-section p {
  margin: 0.5rem 0;
  color: #666;
}

.detail-actions {
  margin-top: 2rem;
}
</style>
