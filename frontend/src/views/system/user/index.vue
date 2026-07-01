<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  getUserList,
  getRoleOptions,
  createUser,
  updateUser,
  toggleUserStatus,
} from '@/api/user'

const formRef = ref()

const state = reactive({
  list: [],
  total: 0,
  loading: false,
  query: { keyword: '', status: '', page: 1, pageSize: 10 },

  // 角色字典（编辑界面下拉来源）
  roleOptions: [],

  // 新增/编辑对话框
  dialogVisible: false,
  dialogMode: 'create', // create | edit
  submitting: false,
  form: {
    id: null,
    name: '',
    phone: '',
    email: '',
    password: '',
    roleIds: [],
    status: true,
  },
})

// 校验规则：密码新增必填、编辑可选（留空表示不改）
const formRules = computed(() => ({
  name: [{ required: true, message: '请输入用户名', trigger: 'blur' }],
  phone: [
    { required: true, message: '请输入手机号', trigger: 'blur' },
    { pattern: /^1\d{10}$/, message: '手机号格式错误', trigger: 'blur' },
  ],
  email: [{ type: 'email', message: '邮箱格式错误', trigger: 'blur' }],
  password:
    state.dialogMode === 'create'
      ? [
          { required: true, message: '请输入密码', trigger: 'blur' },
          { min: 6, max: 32, message: '密码 6-32 位', trigger: 'blur' },
        ]
      : [{ min: 6, max: 32, message: '密码 6-32 位', trigger: 'blur' }],
}))

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

async function fetchRoleOptions() {
  if (state.roleOptions.length) return
  const data = await getRoleOptions()
  state.roleOptions = data.list
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
  state.form = {
    id: null,
    name: '',
    phone: '',
    email: '',
    password: '',
    roleIds: [],
    status: true,
  }
  state.dialogVisible = true
  fetchRoleOptions()
  formRef.value?.clearValidate()
}

function openEdit(row) {
  state.dialogMode = 'edit'
  state.form = {
    id: row.id,
    name: row.name,
    phone: row.phone,
    email: row.email || '',
    password: '', // 留空表示不改
    roleIds: (row.roles || []).map((r) => r.id),
    status: row.status ?? true,
  }
  state.dialogVisible = true
  fetchRoleOptions()
  formRef.value?.clearValidate()
}

async function handleSubmit() {
  try {
    await formRef.value.validate()
  } catch {
    return // 校验未过
  }
  const payload = {
    name: state.form.name.trim(),
    phone: state.form.phone.trim(),
    email: state.form.email?.trim() || null,
    roleIds: state.form.roleIds,
    status: state.form.status,
  }
  if (state.form.password) payload.password = state.form.password

  state.submitting = true
  try {
    if (state.dialogMode === 'create') {
      await createUser(payload)
      ElMessage.success('创建成功')
    } else {
      await updateUser(state.form.id, payload)
      ElMessage.success('更新成功')
    }
    state.dialogVisible = false
    fetchData()
  } finally {
    state.submitting = false
  }
}

async function handleToggleStatus(row) {
  const next = !row.status
  const actionText = next ? '启用' : '停用'
  try {
    await ElMessageBox.confirm(
      `确定要${actionText}用户「${row.name}」吗？`,
      '提示',
      { confirmButtonText: '确定', cancelButtonText: '取消', type: 'warning' },
    )
  } catch {
    return // 取消
  }
  try {
    await toggleUserStatus(row.id, next)
    ElMessage.success(`${actionText}成功`)
    fetchData()
  } catch (err) {
    ElMessage.error(err.message || `${actionText}失败`)
  }
}

onMounted(fetchData)
</script>

<template>
  <el-card>
    <div class="toolbar">
      <el-input
        v-model="state.query.keyword"
        placeholder="搜索用户名 / 手机号 / 邮箱"
        style="width: 260px"
        clearable
        @keyup.enter="handleSearch"
        @clear="handleSearch"
      />
      <el-select
        v-model="state.query.status"
        placeholder="状态筛选"
        style="width: 120px"
        clearable
        @change="handleSearch"
      >
        <el-option label="全部" value="" />
        <el-option label="启用" :value="1" />
        <el-option label="停用" :value="0" />
      </el-select>
      <el-button type="primary" @click="handleSearch">搜索</el-button>
      <el-button
        type="success"
        v-permission="'system_user_write'"
        @click="openCreate"
      >
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
      <el-table-column prop="name" label="用户名" width="140" />
      <el-table-column prop="phone" label="手机号" width="140" />
      <el-table-column prop="email" label="邮箱">
        <template #default="{ row }">
          <span>{{ row.email || '—' }}</span>
        </template>
      </el-table-column>
      <el-table-column label="角色" width="200">
        <template #default="{ row }">
          <el-tag
            v-for="r in row.roles"
            :key="r.id"
            size="small"
            style="margin: 2px 6px 2px 0"
          >
            {{ r.name }}
          </el-tag>
          <span v-if="!row.roles?.length" class="muted">—</span>
        </template>
      </el-table-column>
      <el-table-column label="状态" width="90">
        <template #default="{ row }">
          <el-tag :type="row.status ? 'success' : 'danger'" size="small">
            {{ row.status ? '启用' : '停用' }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="created_at" label="创建时间" width="180" />
      <el-table-column label="操作" width="180" fixed="right">
        <template #default="{ row }">
          <el-button
            v-permission="'system_user_write'"
            link
            type="primary"
            @click="openEdit(row)"
          >
            编辑
          </el-button>
          <el-button
            v-permission="'system_user_write'"
            link
            :type="row.status ? 'danger' : 'success'"
            @click="handleToggleStatus(row)"
          >
            {{ row.status ? '停用' : '启用' }}
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
      :title="state.dialogMode === 'create' ? '新增用户' : '编辑用户'"
      width="520px"
    >
      <el-form
        ref="formRef"
        :model="state.form"
        :rules="formRules"
        label-width="80px"
        @submit.prevent
      >
        <el-form-item label="用户名" prop="name">
          <el-input v-model="state.form.name" placeholder="请输入用户名" maxlength="50" />
        </el-form-item>
        <el-form-item label="手机号" prop="phone">
          <el-input v-model="state.form.phone" placeholder="11 位手机号" maxlength="11" />
        </el-form-item>
        <el-form-item label="邮箱" prop="email">
          <el-input v-model="state.form.email" placeholder="可选" maxlength="100" />
        </el-form-item>
        <el-form-item label="密码" prop="password">
          <el-input
            v-model="state.form.password"
            type="password"
            show-password
            :placeholder="state.dialogMode === 'create' ? '6-32 位' : '留空表示不修改'"
            maxlength="32"
          />
        </el-form-item>
        <el-form-item label="状态">
          <el-switch
            v-model="state.form.status"
            :active-value="true"
            :inactive-value="false"
            active-text="启用"
            inactive-text="停用"
          />
        </el-form-item>
        <el-form-item label="角色">
          <el-select
            v-model="state.form.roleIds"
            multiple
            filterable
            placeholder="请选择角色（可不分配）"
            style="width: 100%"
          >
            <el-option
              v-for="r in state.roleOptions"
              :key="r.id"
              :label="r.name"
              :value="r.id"
            />
          </el-select>
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
</style>
