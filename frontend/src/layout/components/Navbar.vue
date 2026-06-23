<script setup>
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { ElMessageBox } from 'element-plus'
import { useAppStore } from '@/stores/app'
import { useUserStore } from '@/stores/user'

const appStore = useAppStore()
const userStore = useUserStore()
const router = useRouter()
const { sidebarCollapsed } = storeToRefs(appStore)

async function handleLogout() {
  try {
    await ElMessageBox.confirm('确定要退出登录吗？', '提示', { type: 'warning' })
  } catch {
    return // 用户取消
  }
  await userStore.logout()
  router.push('/login')
}
</script>

<template>
  <div class="navbar">
    <el-icon class="collapse-btn" :size="20" @click="appStore.toggleSidebar">
      <Fold v-if="sidebarCollapsed" />
      <Expand v-else />
    </el-icon>
    <div class="navbar-right">
      <el-dropdown trigger="click">
        <span class="user-info">
          <el-icon><UserFilled /></el-icon>
          {{ userStore.userInfo?.name || '管理员' }}
          <el-icon><CaretBottom /></el-icon>
        </span>
        <template #dropdown>
          <el-dropdown-menu>
            <el-dropdown-item @click="handleLogout">退出登录</el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>
    </div>
  </div>
</template>

<style scoped>
.navbar {
  height: 50px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 16px;
  background: #fff;
  border-bottom: 1px solid #eee;
}
.collapse-btn {
  cursor: pointer;
}
.user-info {
  display: flex;
  align-items: center;
  gap: 6px;
  cursor: pointer;
  outline: none;
}
</style>
