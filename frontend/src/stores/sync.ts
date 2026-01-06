import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

export interface SyncRun {
    id: string
    company: {
        id: string
        razao_social: string
    }
    connector_type: string
    nsu_start: number
    ult_nsu: number
    docs_found: number
    docs_saved: number
    status: 'pending' | 'running' | 'completed' | 'failed'
    error_message?: string
    created_at: string
    completed_at?: string
}

export const useSyncStore = defineStore('sync', () => {
    const syncRuns = ref<SyncRun[]>([])
    const loading = ref(false)
    const currentSync = ref<SyncRun | null>(null)

    async function triggerSync(companyId: string) {
        loading.value = true
        try {
            const response = await axios.post(`/api/companies/${companyId}/sync`)
            currentSync.value = response.data
            return response.data
        } catch (error) {
            console.error('Error triggering sync:', error)
            throw error
        } finally {
            loading.value = false
        }
    }

    async function fetchSyncRuns(companyId: string) {
        loading.value = true
        try {
            const response = await axios.get(`/api/companies/${companyId}/sync-runs`)
            syncRuns.value = response.data.data
        } catch (error) {
            console.error('Error fetching sync runs:', error)
            throw error
        } finally {
            loading.value = false
        }
    }

    async function fetchSyncRun(id: string) {
        const response = await axios.get(`/api/sync-runs/${id}`)
        return response.data
    }

    return {
        syncRuns,
        loading,
        currentSync,
        triggerSync,
        fetchSyncRuns,
        fetchSyncRun
    }
})
