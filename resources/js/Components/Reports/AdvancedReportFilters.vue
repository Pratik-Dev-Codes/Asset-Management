<template>
  <div class="space-y-4">
    <div class="flex flex-wrap gap-4">
      <!-- Date Range -->
      <div class="w-full md:w-1/4">
        <label class="block text-sm font-medium text-gray-700">Date Range</label>
        <div class="flex space-x-2">
          <input 
            type="date" 
            v-model="filters.date_from" 
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
          <span class="self-center">to</span>
          <input 
            type="date" 
            v-model="filters.date_to" 
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
        </div>
      </div>

      <!-- Category -->
      <div class="w-full md:w-1/4">
        <label class="block text-sm font-medium text-gray-700">Category</label>
        <select 
          v-model="filters.category_id" 
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        >
          <option value="">All Categories</option>
          <option v-for="category in categories" :key="category.id" :value="category.id">
            {{ category.name }}
          </option>
        </select>
      </div>

      <!-- Status -->
      <div class="w-full md:w-1/4">
        <label class="block text-sm font-medium text-gray-700">Status</label>
        <select 
          v-model="filters.status" 
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        >
          <option value="">All Statuses</option>
          <option v-for="status in statuses" :key="status" :value="status">
            {{ status }}
          </option>
        </select>
      </div>

      <!-- Price Range -->
      <div class="w-full md:w-1/4">
        <label class="block text-sm font-medium text-gray-700">Price Range</label>
        <div class="flex space-x-2">
          <input 
            type="number" 
            v-model.number="filters.min_price" 
            placeholder="Min"
            class="mt-1 block w-1/2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
          <input 
            type="number" 
            v-model.number="filters.max_price" 
            placeholder="Max"
            class="mt-1 block w-1/2 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
        </div>
      </div>
    </div>

    <div class="flex justify-between items-center">
      <div class="flex items-center space-x-2">
        <button 
          @click="$emit('apply-filters', filters)"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Apply Filters
        </button>
        <button 
          @click="resetFilters"
          class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          Reset
        </button>
      </div>
      
      <div class="flex items-center space-x-2">
        <span class="text-sm text-gray-500">Export:</span>
        <button 
          @click="$emit('export', 'pdf')"
          class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
        >
          PDF
        </button>
        <button 
          @click="$emit('export', 'excel')"
          class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-green-700 bg-green-100 hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
        >
          Excel
        </button>
        <button 
          @click="$emit('export', 'csv')"
          class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
          CSV
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    initialFilters: {
      type: Object,
      default: () => ({
        date_from: new Date(new Date().setFullYear(new Date().getFullYear() - 1)).toISOString().split('T')[0],
        date_to: new Date().toISOString().split('T')[0],
        category_id: '',
        status: '',
        min_price: null,
        max_price: null,
      })
    },
    categories: {
      type: Array,
      default: () => []
    },
    statuses: {
      type: Array,
      default: () => []
    }
  },
  
  data() {
    return {
      filters: { ...this.initialFilters }
    };
  },
  
  methods: {
    resetFilters() {
      this.filters = { ...this.initialFilters };
      this.$emit('apply-filters', this.filters);
    }
  },
  
  watch: {
    initialFilters: {
      handler(newVal) {
        this.filters = { ...newVal };
      },
      deep: true
    }
  }
};
</script>
