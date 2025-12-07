<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-4 md:p-6">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-3xl md:text-4xl font-bold text-slate-800 mb-2">Dashboard Quản Lý</h1>
        <p class="text-slate-600">Tổng quan tài liệu và thống kê</p>
      </div>

      <!-- Stats Cards - Compact with Animation -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4 mb-6">
        <div v-for="(s, index) in stats" :key="s.title" 
             class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 p-4 cursor-pointer group relative overflow-hidden"
             @mouseenter="hoveredCard = index"
             @mouseleave="hoveredCard = null">
          <!-- Animated Background Gradient -->
          <div class="absolute inset-0 bg-gradient-to-br opacity-0 group-hover:opacity-10 transition-opacity duration-300"
               :class="s.trend === 'up' ? 'from-emerald-400 to-blue-500' : 'from-rose-400 to-orange-500'"></div>
          
          <div class="relative z-10">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs font-medium text-slate-600 uppercase tracking-wide transition-colors group-hover:text-slate-800">
                {{ s.title }}
              </span>
              <i :class="[
                'text-xl transition-all duration-300 group-hover:scale-125 group-hover:rotate-12',
                s.trend === 'up' ? 'bi bi-arrow-up text-emerald-500 group-hover:text-emerald-600' : 'bi bi-arrow-down text-rose-500 group-hover:text-rose-600'
              ]"></i>
            </div>
            
            <p class="text-2xl md:text-3xl font-bold text-slate-800 mb-1 transition-all duration-300 group-hover:scale-110 group-hover:text-indigo-600">
              {{ s.value }}
            </p>
            
            <div class="flex items-center gap-1">
              <p class="text-xs transition-colors duration-300" 
                 :class="s.trend === 'up' ? 'text-emerald-600 group-hover:text-emerald-700' : 'text-rose-600 group-hover:text-rose-700'">
                {{ s.change }}
              </p>
              <!-- Pulse indicator on hover -->
              <span v-if="hoveredCard === index" class="inline-flex h-2 w-2 rounded-full bg-current animate-pulse"></span>
            </div>
          </div>
          
          <!-- Bottom border animation -->
          <div class="absolute bottom-0 left-0 h-1 bg-gradient-to-r transition-all duration-300 w-0 group-hover:w-full"
               :class="s.trend === 'up' ? 'from-emerald-400 to-blue-500' : 'from-rose-400 to-orange-500'"></div>
        </div>
      </div>

      <!-- Charts - Side by Side -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Line Chart -->
        <div class="bg-white rounded-xl shadow-sm p-5">
          <h2 class="text-lg font-semibold text-slate-800 mb-4">Xu Hướng Tài Liệu</h2>
          <div class="h-64">
            <Line v-if="lineChartData" :data="lineChartData" :options="lineChartOptions" />
          </div>
        </div>

        <!-- Pie Chart -->
        <div class="bg-white rounded-xl shadow-sm p-5">
          <h2 class="text-lg font-semibold text-slate-800 mb-4">Phân Bố Loại</h2>
          <div class="h-64 flex items-center justify-center">
            <Doughnut v-if="pieChartData" :data="pieChartData" :options="pieChartOptions" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Line, Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js'

// Register Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler
)

const stats = ref([])
const lineChartData = ref(null)
const pieChartData = ref(null)
const hoveredCard = ref(null)

const lineChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: true,
      position: 'bottom',
      labels: { 
        padding: 12,
        font: { size: 11 },
        boxWidth: 12
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: { color: 'rgba(0, 0, 0, 0.05)' }
    },
    x: {
      grid: { display: false }
    }
  }
}

const pieChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: true,
      position: 'bottom',
      labels: { 
        padding: 12,
        font: { size: 11 },
        boxWidth: 12
      }
    }
  },
  cutout: '65%'
}

const fetchData = async () => {
  try {
    const res = await fetch('/dashboard', {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    const data = await res.json()
    stats.value = data.stats || []
    
    // Setup Line Chart Data
    const trend = data.documentTrend || []
    lineChartData.value = {
      labels: trend.map(d => d.date),
      datasets: [
        {
          label: 'Tài liệu',
          data: trend.map(d => d.documents),
          borderColor: '#3b82f6',
          backgroundColor: 'rgba(59, 130, 246, 0.08)',
          borderWidth: 2,
          tension: 0.4,
          fill: true,
          pointRadius: 3,
          pointHoverRadius: 5
        },
        {
          label: 'Lượt xem',
          data: trend.map(d => d.views),
          borderColor: '#10b981',
          backgroundColor: 'rgba(16, 185, 129, 0.08)',
          borderWidth: 2,
          tension: 0.4,
          fill: true,
          pointRadius: 3,
          pointHoverRadius: 5
        }
      ]
    }

    // Setup Pie Chart Data
    const types = data.documentTypes || []
    pieChartData.value = {
      labels: types.map(t => t.name),
      datasets: [{
        data: types.map(t => t.value),
        backgroundColor: types.map(t => t.color || '#94a3b8'),
        borderColor: '#fff',
        borderWidth: 2
      }]
    }
  } catch (error) {
    console.error('Lỗi tải dữ liệu:', error)
  }
}

onMounted(() => {
  fetchData()

  // Load Bootstrap Icons
  const link = document.createElement('link')
  link.rel = 'stylesheet'
  link.href = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css'
  document.head.appendChild(link)
})
</script>