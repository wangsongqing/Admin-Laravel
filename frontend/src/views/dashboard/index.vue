<script setup>
import { useUserStore } from '@/stores/user'

const userStore = useUserStore()
</script>

<template>
  <el-card>
    <h2>欢迎回来，{{ userStore.userInfo?.name || '管理员' }} 👋</h2>
    <p style="color: #909399; margin-top: 8px">
      这是一个 Vue 3 + Element Plus + Laravel 的前后端分离后台管理系统骨架。
    </p>
    <el-row :gutter="20" style="margin-top: 20px">
      <el-col :span="8">
        <el-card shadow="hover">
          <div class="stat-label">前端框架</div>
          <div class="stat-value">Vue 3 + Vite</div>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card shadow="hover">
          <div class="stat-label">后端框架</div>
          <div class="stat-value">Laravel 10</div>
        </el-card>
      </el-col>
      <el-col :span="8">
        <el-card shadow="hover">
          <div class="stat-label">鉴权方案</div>
          <div class="stat-value">Passport</div>
        </el-card>
      </el-col>
    </el-row>
  </el-card>
</template>

<style scoped>
.stat-label {
  color: #909399;
  font-size: 14px;
}
.stat-value {
  font-size: 24px;
  font-weight: 600;
  margin-top: 8px;
}
</style>
