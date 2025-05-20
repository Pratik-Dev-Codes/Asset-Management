<template>
  <AppLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Asset Reports
        </h2>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
          <StatCard 
            v-for="stat in stats" 
            :key="stat.label"
            :stat="stat"
          />
        </div>

        <!-- Report Filters -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
          <div class="p-6 bg-white border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Generate Report</h3>
            <AdvancedReportFilters
              :categories="filters.categories"
              :statuses="filters.statuses"
              :initial-filters="initialFilters"
              @apply-filters="fetchReportData"
              @export="exportReport"
            />
          </div>
        </div>

        <!-- Report Results -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <ReportResults
              v-if="reportData"
              :report-data="reportData.data"
              :columns="reportColumns"
              :pagination="reportData.meta"
              :loading="loading"
              @page-change="handlePageChange"
            />
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import AdvancedReportFilters from '@/Components/Reports/AdvancedReportFilters.vue';
import ReportResults from '@/Components/Reports/ReportResults.vue';
import { ref, reactive, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

export default {
  components: {
    AppLayout,
    StatCard,
    AdvancedReportFilters,
    ReportResults,
  },
  
  props: {
    stats: {
      type: Array,
      default: () => [],
    },
    filters: {
      type: Object,
      default: () => ({}),
    },
    report: {
      type: Object,
      default: null,
    },
  },
  
  setup(props) {
    const loading = ref(false);
    const reportData = ref(null);
    const reportColumns = ref([]);
    
    const initialFilters = reactive({
      date_from: new Date(new Date().setFullYear(new Date().getFullYear() - 1)).toISOString().split('T')[0],
      date_to: new Date().toISOString().split('T')[0],
      category_id: '',
      status: '',
      min_price: null,
      max_price: null,
      search: '',
      per_page: 15,
      page: 1,
    });
    
    // Initialize with props if available
    if (props.report) {
      Object.assign(initialFilters, props.report.filters);
      reportData.value = props.report.data;
      reportColumns.value = props.report.columns;
    }
    
    const fetchReportData = async (filters = {}) => {
      try {
        loading.value = true;
        const response = await axios.get(route('reports.assets'), {
          params: {
            ...initialFilters,
            ...filters,
            page: 1, // Reset to first page when filters change
          },
        });
        
        reportData.value = response.data;
        reportColumns.value = response.data.columns || [];
      } catch (error) {
        console.error('Error fetching report data:', error);
        // Handle error (show toast/notification)
      } finally {
        loading.value = false;
      }
    };
    
    const handlePageChange = async (page) => {
      try {
        loading.value = true;
        const response = await axios.get(route('reports.assets'), {
          params: {
            ...initialFilters,
            page,
          },
        });
        
        reportData.value = response.data;
        // Scroll to top of results
        window.scrollTo({ top: 400, behavior: 'smooth' });
      } catch (error) {
        console.error('Error changing page:', error);
        // Handle error
      } finally {
        loading.value = false;
      }
    };
    
    const exportReport = async (format) => {
      try {
        // Show loading indicator
        const loading = ElLoading.service({
          lock: true,
          text: `Preparing ${format.toUpperCase()} export...`,
          background: 'rgba(0, 0, 0, 0.7)',
        });
        
        // Make the export request
        const response = await axios({
          url: route('reports.assets'),
          method: 'GET',
          params: {
            ...initialFilters,
            format,
            download: 1,
          },
          responseType: 'blob',
        });
        
        // Create a download link and trigger it
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `assets_report_${new Date().toISOString().split('T')[0]}.${format}`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        
        // Close loading indicator
        loading.close();
        
        // Show success message
        ElMessage({
          message: 'Export started successfully',
          type: 'success',
        });
      } catch (error) {
        console.error('Error exporting report:', error);
        
        // Show error message
        ElMessage({
          message: 'Failed to export report. Please try again.',
          type: 'error',
        });
      }
    };
    
    // Initial data fetch if needed
    onMounted(() => {
      if (!reportData.value) {
        fetchReportData();
      }
    });
    
    return {
      loading,
      reportData,
      reportColumns,
      initialFilters,
      fetchReportData,
      handlePageChange,
      exportReport,
    };
  },
};
</script>
