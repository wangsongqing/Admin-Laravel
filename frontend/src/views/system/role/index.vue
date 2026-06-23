<script setup>
import { onMounted, reactive } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  getRoleList,
  getPermissionList,
  createRole,
  updateRole,
  deleteRole,
} from '@/api/role'

// 权限名 → 友好展示
const PERM_LABEL = {
  system_user_read: '用户·查看',
  system_user_write: '用户·编辑',
  system_role_read: '角色·查看',
  system_role_write: '角色·编辑',
}
function permLabel(name) {
  return PERM_LABEL[name] || name
}

// 超级管理员角色名，与后端 RoleService::SUPER_ADMIN 一致，前端额外保护
const SUPER_ADMIN = 'admin'

const state = reactive({
  list: [],
  total: 0,
  loading: false,
  query: { keyword: '', page: 1, pageSize: 10 },

  // 权限字典（编辑对话框复选框来源）
  allPermissions: [],

  // 新增/编辑对话框
  dialogVisible: false,
  dialogMode: 'create', // create | edit
  submitting: false,
  form: { id: null, name: '', permissions: [] },
})

async function fetchData() {
  state.loading = true
  try {
    const data = await getRoleList(state.query)
    state.list = data.list
    state.total = data.total
  } finally {
    state.loading = false
  }
}

async function fetchPermissions() {
  // 字典可复用，避免每次开对话框重复请求
  if (state.allPermissions.length) return
  const data = await getPermissionList()
  state.allPermissions = data.list
}

function handleSearch() {
  state.query.page = 1
  fetchData()
}

function handlePageChange(page) {
  state.query.page = page
  fetchData()
}

function openCreate() {
  state.dialogMode = 'create'
  state.form = { id: null, name: '', permissions: [] }
  state.dialogVisible = true
  fetchPermissions()
}

function openEdit(row) {
  state.dialogMode = 'edit'
  state.form = {
    id: row.id,
    name: row.name,
    permissions: [...(row.permissions || [])],
  }
  state.dialogVisible = true
  fetchPermissions()
}

// admin 角色在对话框中名称只读
const isSuperRole = () => state.form.name === SUPER_ADMIN

async function handleSubmit() {
  const name = state.form.name?.trim()
  if (!name) {
    ElMessage.warning('请输入角色名')
    return
  }
  const payload = {
    name,
    permissions: state.form.permissions,
  }
  state.submitting = true
  try {
    if (state.dialogMode === 'create') {
      await createRole(payload)
      ElMessage.success('创建成功')
    } else {
      await updateRole(state.form.id, payload)
      ElMessage.success('更新成功')
    }
    state.dialogVisible = false
    fetchData()
  } finally {
    state.submitting = false
  }
}

async function handleDelete(row) {
  try {
    await ElMessageBox.confirm(
      `确定删除角色「${row.name}」吗？`,
      '删除确认',
      { type: 'warning', confirmButtonText: '删除', cancelButtonText: '取消' },
    )
  } catch {
    return // 用户取消
  }
  await deleteRole(row.id)
  ElMessage.success('删除成功')
  fetchData()
}

onMounted(fetchData)
</script>

<template>
  <el-card>
    <div class="toolbar">
      <el-input
        v-model="state.query.keyword"
        placeholder="搜索角色名"
        style="width: 240px"
        clearable
        @keyup.enter="handleSearch"
        @clear="handleSearch"
      />
      <el-button type="primary" @click="handleSearch">搜索</el-button>
      <el-button
        type="success"
        v-permission="'system_role_write'"
        @click="openCreate"
      >
        新增角色
      </el-button>
    </div>

    <el-table
      v-loading="state.loading"
      :data="state.list"
      border
      style="width: 100%; margin-top: 16px"
    >
      <el-table-column prop="id" label="ID" width="80" />
      <el-table-column prop="name" label="角色名" width="180">
        <template #default="{ row }">
          <span>{{ row.name }}</span>
          <el-tag
            v-if="row.name === SUPER_ADMIN"
            size="small"
            type="danger"
            effect="plain"
            style="margin-left: 8px"
          >
            超管
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column label="权限">
        <template #default="{ row }">
          <el-tag
            v-for="p in row.permissions"
            :key="p"
            size="small"
            style="margin: 2px 6px 2px 0"
          >
            {{ permLabel(p) }}
          </el-tag>
          <span v-if="!row.permissions?.length" class="muted">—</span>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" label="创建时间" width="180" />
      <el-table-column label="操作" width="160" fixed="right">
        <template #default="{ row }">
          <el-button
            v-permission="'system_role_write'"
            link
            type="primary"
            @click="openEdit(row)"
          >
            编辑
          </el-button>
          <el-button
            v-if="row.name !== SUPER_ADMIN"
            v-permission="'system_role_write'"
            link
            type="danger"
            @click="handleDelete(row)"
          >
            删除
          </el-button>
        </template>
      </el-table-column>
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

    <!-- 新增 / 编辑对话框 -->
    <el-dialog
      v-model="state.dialogVisible"
      :title="state.dialogMode === 'create' ? '新增角色' : '编辑角色'"
      width="520px"
    >
      <el-form label-width="80px" @submit.prevent>
        <el-form-item label="角色名">
          <el-input
            v-model="state.form.name"
            placeholder="请输入角色名"
            :disabled="isSuperRole()"
            maxlength="50"
          />
          <div v-if="isSuperRole()" class="hint">超级管理员角色不可改名</div>
        </el-form-item>
        <el-form-item label="权限">
          <el-checkbox-group v-model="state.form.permissions">
            <el-checkbox
              v-for="p in state.allPermissions"
              :key="p.id"
              :value="p.name"
              style="width: 140px"
            >
              {{ permLabel(p.name) }}
            </el-checkbox>
          </el-checkbox-group>
          <div v-if="!state.allPermissions.length" class="muted">暂无可分配权限</div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="state.dialogVisible = false">取消</el-button>
        <el-button
          type="primary"
          :loading="state.submitting"
          @click="handleSubmit"
        >
          确定
        </el-button>
      </template>
    </el-dialog>
  </el-card>
</template>

<style scoped>
.toolbar {
  display: flex;
  gap: 12px;
}
.muted {
  color: #909399;
  font-size: 13px;
}
.hint {
  color: #e6a23c;
  font-size: 12px;
  margin-top: 4px;
}
</style>
