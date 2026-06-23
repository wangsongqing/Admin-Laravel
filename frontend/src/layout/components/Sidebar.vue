<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { routes } from '@/router'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'

const route = useRoute()
const appStore = useAppStore()
const userStore = useUserStore()
const { sidebarCollapsed } = storeToRefs(appStore)

// 仅渲染有子菜单、非隐藏、且用户有权限的路由
const menuRoutes = computed(() =>
  routes
    .filter((r) => !r.meta?.hidden && r.children?.length)
    .map((r) => ({
      ...r,
      children: r.children.filter(
        (c) => !c.meta?.hidden && userStore.hasPermission(c.meta?.permission),
      ),
    }))
    .filter((r) => r.children.length > 0),
)
const activeMenu = computed(() => route.path)

function resolvePath(base, child) {
  if (child.startsWith('/')) return child
  return `${base.replace(/\/$/, '')}/${child}`
}
</script>

<template>
  <div class="sidebar-logo">
    <el-icon :size="22"><Platform /></el-icon>
    <span v-show="!sidebarCollapsed" class="sidebar-title">后台管理</span>
  </div>
  <el-menu
    :default-active="activeMenu"
    :collapse="sidebarCollapsed"
    :collapse-transition="false"
    background-color="#304156"
    text-color="#bfcbd9"
    active-text-color="#409eff"
    router
  >
    <template v-for="r in menuRoutes" :key="r.path">
      <el-sub-menu v-if="r.children.length > 1" :index="r.path">
        <template #title>
          <el-icon v-if="r.meta?.icon"><component :is="r.meta.icon" /></el-icon>
          <span>{{ r.meta?.title }}</span>
        </template>
        <el-menu-item
          v-for="c in r.children"
          :key="c.path"
          :index="resolvePath(r.path, c.path)"
        >
          <el-icon v-if="c.meta?.icon"><component :is="c.meta.icon" /></el-icon>
          <template #title>{{ c.meta?.title }}</template>
        </el-menu-item>
      </el-sub-menu>

      <el-menu-item v-else :index="resolvePath(r.path, r.children[0].path)">
        <el-icon v-if="r.children[0].meta?.icon">
          <component :is="r.children[0].meta.icon" />
        </el-icon>
        <template #title>{{ r.children[0].meta?.title }}</template>
      </el-menu-item>
    </template>
  </el-menu>
</template>

<style scoped>
.sidebar-logo {
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: #fff;
  font-size: 16px;
  font-weight: 600;
  background-color: #2b3a4d;
  white-space: nowrap;
  overflow: hidden;
}
.el-menu {
  border-right: none;
}
</style>
