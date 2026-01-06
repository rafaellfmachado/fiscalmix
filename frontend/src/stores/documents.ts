import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

export interface Document {
    id: string
    company: {
        id: string
        razao_social: string
        cnpj: string
    }
    doc_type: string
    access_key: string
    number: number
    series: number
    issue_date: string
    direction: 'inbound' | 'outbound'
    issuer_cnpj: string
    issuer_name: string
    recipient_cnpj: string
    recipient_name: string
    total_value: number
    status: string
    created_at: string
}

export interface DocumentFilters {
    company_id?: string
    doc_type?: string
    direction?: string
    status?: string
    start_date?: string
    end_date?: string
    search?: string
    per_page?: number
}

export const useDocumentsStore = defineStore('documents', () => {
    const documents = ref<Document[]>([])
    const loading = ref(false)
    const currentPage = ref(1)
    const lastPage = ref(1)
    const total = ref(0)
    const filters = ref<DocumentFilters>({
        per_page: 20
    })

    async function fetchDocuments() {
        loading.value = true
        try {
            const response = await axios.get('/api/documents', { params: filters.value })
            documents.value = response.data.data
            currentPage.value = response.data.meta.current_page
            lastPage.value = response.data.meta.last_page
            total.value = response.data.meta.total
        } catch (error) {
            console.error('Error fetching documents:', error)
            throw error
        } finally {
            loading.value = false
        }
    }

    async function fetchDocument(id: string) {
        const response = await axios.get(`/api/documents/${id}`)
        return response.data
    }

    async function downloadXml(id: string) {
        const response = await axios.get(`/api/documents/${id}/xml`, {
            responseType: 'blob'
        })

        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `document_${id}.xml`)
        document.body.appendChild(link)
        link.click()
        link.remove()
    }

    async function fetchStats(companyId?: string) {
        const params = companyId ? { company_id: companyId } : {}
        const response = await axios.get('/api/documents/stats', { params })
        return response.data
    }

    function setFilters(newFilters: DocumentFilters) {
        filters.value = { ...filters.value, ...newFilters }
    }

    function clearFilters() {
        filters.value = { per_page: 20 }
    }

    return {
        documents,
        loading,
        currentPage,
        lastPage,
        total,
        filters,
        fetchDocuments,
        fetchDocument,
        downloadXml,
        fetchStats,
        setFilters,
        clearFilters
    }
})
