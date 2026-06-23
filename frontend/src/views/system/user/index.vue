<script setup>
import { onMounted, reactive } from 'vue'
import { ElMessage } from 'element-plus'
import { getUserList } from '@/api/user'

const state = reactive({
  list: [],
  total: 0,
  loading: false,
  query: { keyword: '', page: 1, pageSize: 10 },
})

async function fetchData() {
  state.loading = true
  try {
    const data = await getUserList(state.query)
    state.list = data.list
    state.total = data.total
  } finally {
    state.loading = false
  }
}

function handleSearch() {
  state.query.page = 1
  fetchData()
}

function handlePageChange(page) {
  state.query.page = page
  fetchData()
}

// 演示按钮级权限：仅拥有 system_user_write 才可点
function handleAdd() {
  ElMessage.info('新增用户（演示按钮级权限：system_user_write）')
}

onMounted(fetchData)
</script>

<template>
  <el-card>
    <div class="toolbar">
      <el-input
        v-model="state.query.keyword"
        placeholder="搜索用户名 / 手机号"
        style="width: 240px"
        clearable
        @keyup.enter="handleSearch"
        @clear="handleSearch"
      />
      <el-button type="primary" @click="handleSearch">搜索</el-button>
      <!-- 按钮级权限：没有 system_user_write 的用户看不到此按钮 -->
      <el-button type="success" v-permission="'system_user_write'" @click="handleAdd">
        新增用户
      </el-button>
    </div>

    <el-table
      v-loading="state.loading"
      :data="state.list"
      border
      style="width: 100%; margin-top: 16px"
    >
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column prop="name" label="用户名" />
      <el-table-column prop="email" label="邮箱" />
      <el-table-column prop="created_at" label="创建时间" />
    </el-table>

    <el-pagination
      style="margin-top: 16px; justify-content: flex-end"
      background
      layout="total, prev, pager, next"
      :total="state.total"
      :page-size="state.query.pageSize"
      :current-page="state.query.page"
      @current-change="handlePageChange"
    />
  </el-card>
</template>

<style scoped>
.toolbar {
  display: flex;
  gap: 12px;
}
</style>
