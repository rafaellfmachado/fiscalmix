<template>
  <div class="app-layout">
    <!-- Sidebar -->
    <aside class="sidebar" :class="{ collapsed: sidebarCollapsed }">
      <div class="sidebar-header">
        <div class="logo">
          <i class="pi pi-chart-line"></i>
          <span v-if="!sidebarCollapsed">FiscalMix</span>
        </div>
        <button @click="toggleSidebar" class="collapse-btn">
          <i :class="sidebarCollapsed ? 'pi pi-angle-right' : 'pi pi-angle-left'"></i>
        </button>
      </div>

      <nav class="sidebar-nav">
        <router-link to="/" class="nav-item" exact-active-class="active">
          <i class="pi pi-home"></i>
          <span v-if="!sidebarCollapsed">Dashboard</span>
        </router-link>
        <router-link to="/companies" class="nav-item" active-class="active">
          <i class="pi pi-building"></i>
          <span v-if="!sidebarCollapsed">Empresas</span>
        </router-link>
        <router-link to="/documents" class="nav-item" active-class="active">
          <i class="pi pi-file"></i>
          <span v-if="!sidebarCollapsed">Documentos</span>
          <Badge v-if="!sidebarCollapsed && stats.totalDocs > 0" :value="stats.totalDocs" severity="info" />
        </router-link>
        <router-link to="/sync" class="nav-item" active-class="active">
          <i class="pi pi-sync"></i>
          <span v-if="!sidebarCollapsed">Sincronização</span>
        </router-link>
        <router-link to="/exports" class="nav-item" active-class="active">
          <i class="pi pi-download"></i>
          <span v-if="!sidebarCollapsed">Exportações</span>
        </router-link>
      </nav>

      <div class="sidebar-footer">
        <div class="user-profile" @click="showUserMenu = !showUserMenu">
          <Avatar :label="userInitials" shape="circle" class="user-avatar" />
          <div v-if="!sidebarCollapsed" class="user-info">
            <span class="user-name">{{ authStore.user?.name }}</span>
            <span class="user-email">{{ authStore.user?.email }}</span>
          </div>
          <i v-if="!sidebarCollapsed" class="pi pi-ellipsis-v"></i>
        </div>
        <Menu v-if="showUserMenu" ref="menu" :model="userMenuItems" popup />
      </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <router-view />
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import Avatar from 'primevue/avatar'
import Badge from 'primevue/badge'
import Menu from 'primevue/menu'

const router = useRouter()
const authStore = useAuthStore()

const sidebarCollapsed = ref(false)
const showUserMenu = ref(false)
const stats = ref({ totalDocs: 0 })

const userInitials = computed(() => {
  const name = authStore.user?.name || ''
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

const userMenuItems = [
  {
    label: 'Configurações',
    icon: 'pi pi-cog',
    command: () => router.push('/settings')
  },
  {
    separator: true
  },
  {
    label: 'Sair',
    icon: 'pi pi-sign-out',
    command: async () => {
      await authStore.logout()
      router.push('/login')
    }
  }
]

function toggleSidebar() {
  sidebarCollapsed.value = !sidebarCollapsed.value
}

onMounted(async () => {
  // Load stats
  try {
    const response = await fetch('/api/documents/stats')
    const data = await response.json()
    stats.value.totalDocs = data.total || 0
  } catch (error) {
    console.error('Error loading stats:', error)
  }
})
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

.app-layout {
  display: flex;
  min-height: 100vh;
  font-family: 'Inter', sans-serif;
  background: #F7F9FC;
}

.sidebar {
  width: 260px;
  background: white;
  border-right: 1px solid #E5E7EB;
  display: flex;
  flex-direction: column;
  transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: sticky;
  top: 0;
  height: 100vh;
}

.sidebar.collapsed {
  width: 80px;
}

.sidebar-header {
  padding: 1.5rem 1.25rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #F3F4F6;
}

.logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 700;
  font-size: 1.25rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.logo i {
  font-size: 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.collapse-btn {
  background: transparent;
  border: none;
  color: #9CA3AF;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 8px;
  transition: all 0.2s;
}

.collapse-btn:hover {
  background: #F3F4F6;
  color: #667eea;
}

.sidebar-nav {
  flex: 1;
  padding: 1rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  overflow-y: auto;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border-radius: 10px;
  color: #6B7280;
  text-decoration: none;
  font-weight: 500;
  font-size: 0.95rem;
  transition: all 0.2s;
  position: relative;
}

.nav-item i {
  font-size: 1.1rem;
  min-width: 20px;
}

.nav-item:hover {
  background: #F9FAFB;
  color: #374151;
}

.nav-item.active {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
  color: #667eea;
  font-weight: 600;
}

.nav-item.active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 3px;
  height: 60%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 0 3px 3px 0;
}

.sidebar.collapsed .nav-item {
  justify-content: center;
  padding: 0.75rem;
}

.sidebar.collapsed .nav-item span {
  display: none;
}

.sidebar-footer {
  padding: 1rem;
  border-top: 1px solid #F3F4F6;
}

.user-profile {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s;
}

.user-profile:hover {
  background: #F9FAFB;
}

.user-avatar {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-weight: 600;
}

.user-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
  min-width: 0;
}

.user-name {
  font-weight: 600;
  font-size: 0.875rem;
  color: #374151;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-email {
  font-size: 0.75rem;
  color: #9CA3AF;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sidebar.collapsed .user-profile {
  justify-content: center;
}

.sidebar.collapsed .user-info,
.sidebar.collapsed .user-profile i {
  display: none;
}

.main-content {
  flex: 1;
  overflow-y: auto;
}

@media (max-width: 768px) {
  .sidebar {
    position: fixed;
    z-index: 1000;
    transform: translateX(-100%);
  }

  .sidebar:not(.collapsed) {
    transform: translateX(0);
  }
}
</style>
