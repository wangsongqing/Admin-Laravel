<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { useUserStore } from '@/stores/user'

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()

const loginFormRef = ref()
const loading = ref(false)
const loginForm = reactive({
  phone: '13800138000',
  password: 'password',
})
const rules = {
  phone: [
    { required: true, message: '请输入手机号', trigger: 'blur' },
    { pattern: /^1\d{10}$/, message: '手机号格式错误', trigger: 'blur' },
  ],
  password: [{ required: true, message: '请输入密码', trigger: 'blur' }],
}

async function handleLogin() {
  try {
    await loginFormRef.value.validate()
  } catch {
    return // 校验未通过
  }
  loading.value = true
  try {
    await userStore.login(loginForm)
    ElMessage.success('登录成功')
    router.push(route.query.redirect || '/')
  } catch {
    // 错误已在 axios 拦截器统一提示
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-container">
    <el-card class="login-card">
      <template #header>
        <div class="login-title">后台管理系统</div>
      </template>
      <el-form
        ref="loginFormRef"
        :model="loginForm"
        :rules="rules"
        label-position="top"
      >
        <el-form-item label="手机号" prop="phone">
          <el-input
            v-model="loginForm.phone"
            placeholder="请输入手机号"
            maxlength="11"
            clearable
          />
        </el-form-item>
        <el-form-item label="密码" prop="password">
          <el-input
            v-model="loginForm.password"
            type="password"
            show-password
            placeholder="请输入密码"
            @keyup.enter="handleLogin"
          />
        </el-form-item>
        <el-button
          type="primary"
          :loading="loading"
          style="width: 100%"
          @click="handleLogin"
        >
          登 录
        </el-button>
      </el-form>
      <p class="login-tip">默认账号：13800138000 / 密码：password</p>
    </el-card>
  </div>
</template>

<style scoped>
.login-container {
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea, #764ba2);
}
.login-card {
  width: 380px;
}
.login-title {
  text-align: center;
  font-size: 20px;
  font-weight: 600;
}
.login-tip {
  margin-top: 12px;
  text-align: center;
  font-size: 12px;
  color: #909399;
}
</style>
