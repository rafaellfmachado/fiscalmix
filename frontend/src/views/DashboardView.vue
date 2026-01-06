<template>
  <div class="dashboard">
    <!-- Header -->
    <div class="dashboard-header">
      <div>
        <h1>Dashboard</h1>
        <p>Visão geral dos seus documentos fiscais</p>
      </div>
      <Button label="Nova Sincronização" icon="pi pi-sync" @click="router.push('/sync')" />
    </div>

    <!-- Stats Cards (Monday.com inspired) -->
    <div class="stats-grid">
      <Card class="stat-card stat-primary">
        <template #content>
          <div class="stat-content">
            <div class="stat-icon">
              <i class="pi pi-file"></i>
            </div>
            <div class="stat-info">
              <span class="stat-label">Total de Documentos</span>
              <span class="stat-value">{{ stats.total || 0 }}</span>
              <span class="stat-trend positive">
                <i class="pi pi-arrow-up"></i> 12% este mês
              </span>
            </div>
          </div>
        </template>
      </Card>

      <Card class="stat-card stat-success">
        <template #content>
          <div class="stat-content">
            <div class="stat-icon">
              <i class="pi pi-check-circle"></i>
            </div>
            <div class="stat-info">
              <span class="stat-label">Autorizados</span>
              <span class="stat-value">{{ stats.by_status?.authorized || 0 }}</span>
              <span class="stat-trend positive">
                <i class="pi pi-arrow-up"></i> 8% este mês
              </span>
            </div>
          </div>
        </template>
      </Card>

      <Card class="stat-card stat-info">
        <template #content>
          <div class="stat-content">
            <div class="stat-icon">
              <i class="pi pi-arrow-down"></i>
            </div>
            <div class="stat-info">
              <span class="stat-label">Entrada</span>
              <span class="stat-value">{{ stats.by_direction?.inbound || 0 }}</span>
              <span class="stat-trend neutral">
                <i class="pi pi-minus"></i> 0% este mês
              </span>
            </div>
          </div>
        </template>
      </Card>

      <Card class="stat-card stat-warning">
        <template #content>
          <div class="stat-content">
            <div class="stat-icon">
              <i class="pi pi-arrow-up"></i>
            </div>
            <div class="stat-info">
              <span class="stat-label">Saída</span>
              <span class="stat-value">{{ stats.by_direction?.outbound || 0 }}</span>
              <span class="stat-trend positive">
                <i class="pi pi-arrow-up"></i> 15% este mês
              </span>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Charts Row (HubSpot/Salesforce inspired) -->
    <div class="charts-row">
      <Card class="chart-card">
        <template #title>Documentos por Tipo</template>
        <template #content>
          <div class="doc-types">
            <div v-for="(count, type) in stats.by_type" :key="type" class="doc-type-item">
              <div class="doc-type-bar">
                <div class="doc-type-fill" :style="{ width: getPercentage(count, stats.total) + '%' }"></div>
              </div>
              <div class="doc-type-info">
                <Tag :value="type" :severity="getDocTypeSeverity(type)" />
                <span class="doc-type-count">{{ count }}</span>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <Card class="chart-card">
        <template #title>Valor Total</template>
        <template #content>
          <div class="total-value">
            <span class="currency">R$</span>
            <span class="value">{{ formatCurrency(stats.total_value || 0) }}</span>
          </div>
          <div class="value-breakdown">
            <div class="breakdown-item">
              <span class="label">Entrada</span>
              <span class="amount">R$ 0,00</span>
            </div>
            <div class="breakdown-item">
              <span class="label">Saída</span>
              <span class="amount">R$ 0,00</span>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Recent Activity (Asana inspired) -->
    <Card class="activity-card">
      <template #title>Atividade Recente</template>
      <template #content>
        <div class="activity-list">
          <div class="activity-item">
            <div class="activity-icon success">
              <i class="pi pi-check"></i>
            </div>
            <div class="activity-content">
              <p class="activity-title">Sincronização concluída</p>
              <p class="activity-desc">15 novos documentos importados</p>
            </div>
            <span class="activity-time">Há 2 horas</span>
          </div>
          <div class="activity-item">
            <div class="activity-icon info">
              <i class="pi pi-file"></i>
            </div>
            <div class="activity-content">
              <p class="activity-title">Novo documento recebido</p>
              <p class="activity-desc">NF-e #12345 - Fornecedor ABC</p>
            </div>
            <span class="activity-time">Há 5 horas</span>
          </div>
          <div class="activity-item">
            <div class="activity-icon warning">
              <i class="pi pi-exclamation-triangle"></i>
            </div>
            <div class="activity-content">
              <p class="activity-title">Certificado próximo do vencimento</p>
              <p class="activity-desc">Certificado A1 vence em 15 dias</p>
            </div>
            <span class="activity-time">Ontem</span>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'

const router = useRouter()
const stats = ref<any>({})

onMounted(async () => {
  try {
    const response = await axios.get('/api/documents/stats')
    stats.value = response.data
  } catch (error) {
    console.error('Error loading stats:', error)
  }
})

function getPercentage(value: number, total: number) {
  return total > 0 ? (value / total) * 100 : 0
}

function formatCurrency(value: number) {
  return new Intl.NumberFormat('pt-BR').format(value)
}

function getDocTypeSeverity(type: string) {
  const map: any = { NFE: 'success', CTE: 'info', MDFE: 'warning', NFSE: 'secondary' }
  return map[type] || 'secondary'
}
</script>

<style scoped>
.dashboard {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
}

.dashboard-header h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #1F2937;
  margin: 0 0 0.5rem 0;
}

.dashboard-header p {
  color: #6B7280;
  margin: 0;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  border-radius: 16px;
  border: none;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: all 0.3s;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.stat-content {
  display: flex;
  gap: 1rem;
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.stat-primary .stat-icon {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.stat-success .stat-icon {
  background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
  color: white;
}

.stat-info .stat-icon {
  background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
  color: white;
}

.stat-warning .stat-icon {
  background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
  color: white;
}

.stat-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.stat-label {
  font-size: 0.875rem;
  color: #6B7280;
  font-weight: 500;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: #1F2937;
}

.stat-trend {
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.stat-trend.positive {
  color: #10b981;
}

.stat-trend.neutral {
  color: #6b7280;
}

.charts-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.chart-card {
  border-radius: 16px;
  border: none;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.doc-types {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.doc-type-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.doc-type-bar {
  height: 8px;
  background: #F3F4F6;
  border-radius: 4px;
  overflow: hidden;
}

.doc-type-fill {
  height: 100%;
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  border-radius: 4px;
  transition: width 0.5s ease;
}

.doc-type-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.doc-type-count {
  font-weight: 600;
  color: #374151;
}

.total-value {
  text-align: center;
  padding: 2rem 0;
}

.currency {
  font-size: 1.5rem;
  color: #9CA3AF;
  font-weight: 600;
}

.value {
  font-size: 3rem;
  font-weight: 700;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  margin-left: 0.5rem;
}

.value-breakdown {
  display: flex;
  justify-content: space-around;
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid #F3F4F6;
}

.breakdown-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  align-items: center;
}

.breakdown-item .label {
  font-size: 0.875rem;
  color: #6B7280;
}

.breakdown-item .amount {
  font-weight: 600;
  color: #374151;
}

.activity-card {
  border-radius: 16px;
  border: none;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.activity-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.activity-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem;
  border-radius: 12px;
  transition: background 0.2s;
}

.activity-item:hover {
  background: #F9FAFB;
}

.activity-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.activity-icon.success {
  background: rgba(72, 187, 120, 0.1);
  color: #48bb78;
}

.activity-icon.info {
  background: rgba(66, 153, 225, 0.1);
  color: #4299e1;
}

.activity-icon.warning {
  background: rgba(237, 137, 54, 0.1);
  color: #ed8936;
}

.activity-content {
  flex: 1;
}

.activity-title {
  font-weight: 600;
  color: #374151;
  margin: 0 0 0.25rem 0;
}

.activity-desc {
  font-size: 0.875rem;
  color: #6B7280;
  margin: 0;
}

.activity-time {
  font-size: 0.875rem;
  color: #9CA3AF;
  flex-shrink: 0;
}

@media (max-width: 768px) {
  .charts-row {
    grid-template-columns: 1fr;
  }
}
</style>
