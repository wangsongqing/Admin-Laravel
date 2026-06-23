<script setup>
import { storeToRefs } from 'pinia'
import Sidebar from './components/Sidebar.vue'
import Navbar from './components/Navbar.vue'
import AppMain from './components/AppMain.vue'
import { useAppStore } from '@/stores/app'

const appStore = useAppStore()
const { sidebarCollapsed } = storeToRefs(appStore)
</script>

<template>
  <el-container class="app-wrapper">
    <el-aside :width="sidebarCollapsed ? '64px' : '210px'" class="app-aside">
      <Sidebar />
    </el-aside>
    <el-container>
      <el-header class="app-header">
        <Navbar />
      </el-header>
      <el-main class="app-main">
        <AppMain />
      </el-main>
    </el-container>
  </el-container>
</template>
