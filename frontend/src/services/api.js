import axios from 'axios'

const api = axios.create({
  baseURL: 'http://10.10.11.103.nip.io:8000/api',
  // baseURL: 'http://localhost:8000/api',
})

api.interceptors.request.use((config) => {
  const token = sessionStorage.getItem('token')

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

export default api
