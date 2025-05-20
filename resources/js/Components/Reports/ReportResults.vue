<template>
  <div class="mt-8">
    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-flex items-center px-6 py-3 font-semibold leading-6 text-sm shadow rounded-lg text-white bg-indigo-600">
        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Generating report...
      </div>
      <p class="mt-2 text-sm text-gray-500">This may take a moment for large datasets</p>
    </div>

    <!-- Results Section -->
    <div v-else>
      <!-- No Results -->
      <div v-if="reportData.length === 0" class="text-center py-12 px-4 bg-white rounded-lg border-2 border-dashed border-gray-300">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No results found</h3>
        <p class="mt-1 text-sm text-gray-500">Try adjusting your search or filter criteria</p>
        <div class="mt-6">
          <button 
            @click="$emit('reset-filters')"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
            </svg>
            Reset Filters
          </button>
        </div>
      </div>

      <!-- Results Found -->
      <div v-else>
        <!-- Results Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 space-y-2 sm:space-y-0">
          <div>
            <h3 class="text-lg font-medium text-gray-900">Report Results</h3>
            <p class="text-sm text-gray-500 mt-1">
              Showing {{ pagination.from || 1 }} to {{ pagination.to || reportData.length }} of {{ pagination.total || reportData.length }} {{ pagination.total === 1 ? 'entry' : 'entries' }}
              <span v-if="filteredCount !== null && filteredCount !== pagination.total" class="text-indigo-600">
                (filtered from {{ filteredCount }} total)
              </span>
            </p>
          </div>
          
          <!-- View Options -->
          <div class="flex items-center space-x-2">
            <!-- View Toggle -->
            <div class="hidden sm:flex bg-gray-100 p-0.5 rounded-lg">
              <button 
                @click="viewMode = 'table'"
                :class="['p-1.5 rounded-md', viewMode === 'table' ? 'bg-white shadow-sm' : 'text-gray-500 hover:text-gray-700']"
                title="Table View"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
              </button>
              <button 
                @click="viewMode = 'card'"
                :class="['p-1.5 rounded-md', viewMode === 'card' ? 'bg-white shadow-sm' : 'text-gray-500 hover:text-gray-700']"
                title="Card View"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
              </button>
            </div>
            
            <!-- Column Visibility -->
            <Menu as="div" class="relative inline-block text-left">
              <div>
                <MenuButton class="inline-flex justify-center w-full rounded-md border border-gray-300 shadow-sm px-3 py-1.5 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                  </svg>
                  <span class="ml-1">Columns</span>
                </MenuButton>
              </div>

              <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                <MenuItems class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-10 p-1">
                  <div class="py-1">
                    <MenuItem v-for="column in allColumns" :key="column.key" v-slot="{ active }" class="flex items-center">
                      <button
                        @click="toggleColumn(column.key)"
                        :class="[
                          active ? 'bg-gray-100 text-gray-900' : 'text-gray-700',
                          'group flex items-center px-4 py-2 text-sm w-full text-left rounded-md'
                        ]"
                      >
                        <svg 
                          v-if="isColumnVisible(column.key)" 
                          class="h-5 w-5 text-indigo-600 mr-2" 
                          xmlns="http://www.w3.org/2000/svg" 
                          viewBox="0 0 20 20" 
                          fill="currentColor"
                        >
                          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        <span v-else class="w-5 h-5 mr-2"></span>
                        {{ column.label }}
                      </button>
                    </MenuItem>
                  </div>
                </MenuItems>
              </transition>
            </Menu>
            
            <!-- Export Dropdown -->
            <Menu as="div" class="relative inline-block text-left">
              <div>
                <MenuButton class="inline-flex justify-center w-full rounded-md border border-gray-300 shadow-sm px-3 py-1.5 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                  </svg>
                  <span class="ml-1">Export</span>
                </MenuButton>
              </div>

              <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                <MenuItems class="origin-top-right absolute right-0 mt-2 w-40 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-10">
                  <div class="py-1">
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="$emit('export', 'pdf')"
                        :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'group flex items-center px-4 py-2 text-sm w-full text-left']"
                      >
                        <svg class="h-5 w-5 text-red-500 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        PDF
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="$emit('export', 'excel')"
                        :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'group flex items-center px-4 py-2 text-sm w-full text-left']"
                      >
                        <svg class="h-5 w-5 text-green-500 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Excel
                      </button>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                      <button
                        @click="$emit('export', 'csv')"
                        :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'group flex items-center px-4 py-2 text-sm w-full text-left']"
                      >
                        <svg class="h-5 w-5 text-blue-500 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        CSV
                      </button>
                    </MenuItem>
                  </div>
                </MenuItems>
              </transition>
            </Menu>
          </div>
        </div>

        <!-- Table View -->
        <div v-if="viewMode === 'table'" class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th 
                  v-for="column in visibleColumns" 
                  :key="column.key"
                  @click="sortBy(column.key)"
                  :class="[
                    'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer',
                    sort.column === column.key ? 'bg-gray-100' : '',
                    column.align ? `text-${column.align}` : 'text-left'
                  ]"
                >
                  <div class="flex items-center">
                    {{ column.label }}
                    <span v-if="sort.column === column.key" class="ml-1">
                      <svg v-if="sort.direction === 'asc'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                      </svg>
                      <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                      </svg>
                    </span>
                  </div>
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr 
                v-for="(item, index) in sortedData" 
                :key="index"
                :class="{ 'bg-gray-50': index % 2 === 0 }"
                class="hover:bg-gray-100"
              >
                <td 
                  v-for="column in visibleColumns" 
                  :key="`${index}-${column.key}`" 
                  :class="[
                    'px-6 py-4 whitespace-nowrap text-sm',
                    column.align ? `text-${column.align}` : 'text-left',
                    column.class || ''
                  ]"
                >
                  <template v-if="column.type === 'date' && item[column.key]">
                    <div class="text-gray-900">{{ formatDate(item[column.key]) }}</div>
                    <div v-if="column.showRelative" class="text-xs text-gray-500">
                      {{ formatRelativeDate(item[column.key]) }}
                    </div>
                  </template>
                  <template v-else-if="column.type === 'currency' && item[column.key] !== null">
                    <div class="font-medium" :class="{
                      'text-green-600': column.showTrend && item[column.trendField] > 0,
                      'text-red-600': column.showTrend && item[column.trendField] < 0,
                      'text-gray-500': !column.showTrend
                    }">
                      {{ formatCurrency(item[column.key]) }}
                      <span v-if="column.showTrend && item[column.trendField] !== 0" 
                            :class="{
                              'text-green-500': item[column.trendField] > 0,
                              'text-red-500': item[column.trendField] < 0
                            }">
                        ({{ item[column.trendField] > 0 ? '+' : '' }}{{ formatCurrency(item[column.trendField]) }})
                      </span>
                    </div>
                  </template>
                  <template v-else-if="column.type === 'status'">
                    <span :class="getStatusClasses(item[column.key])">
                      {{ formatStatus(item[column.key]) }}
                    </span>
                  </template>
                  <template v-else-if="column.type === 'badge'">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                          :class="getBadgeClasses(item[column.key], column.badgeVariant || 'gray')">
                      {{ item[column.key] }}
                    </span>
                  </template>
                  <template v-else-if="column.type === 'progress'">
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                      <div class="bg-blue-600 h-2.5 rounded-full" :style="{ width: `${item[column.key]}%` }"></div>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">{{ item[column.key] }}%</div>
                  </template>
                  <template v-else>
                    <div class="text-gray-900">{{ item[column.key] || '—' }}</div>
                    <div v-if="column.subtext" class="text-xs text-gray-500">
                      {{ item[column.subtext] || '' }}
                    </div>
                  </template>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Card View -->
        <div v-else class="grid gap-4 sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          <div v-for="(item, index) in sortedData" :key="`card-${index}`" class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
                  <component :is="getIconForItem(item)" class="h-6 w-6 text-white" />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      {{ getPrimaryField(item) }}
                    </dt>
                    <dd class="flex items-baseline">
                      <div class="text-2xl font-semibold text-gray-900">
                        {{ getSecondaryField(item) }}
                      </div>
                      <div v-if="getTertiaryField(item)" class="ml-2 flex items-baseline text-sm font-semibold text-green-600">
                        {{ getTertiaryField(item) }}
                      </div>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
            <div class="bg-gray-50 px-4 py-4 sm:px-6">
              <div class="text-sm">
                <a href="#" class="font-medium text-indigo-600 hover:text-indigo-500">
                  View details<span class="sr-only"> for {{ getPrimaryField(item) }}</span>
                </a>
              </div>
            </div>
          </div>
        </div>

        <div v-if="pagination" class="mt-4 flex justify-between items-center">
          <div class="text-sm text-gray-500">
            Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} entries
          </div>
          <div class="flex space-x-2">
            <button 
              @click="$emit('page-change', pagination.current_page - 1)"
              :disabled="!pagination.prev_page_url"
              class="px-3 py-1 border rounded-md text-sm font-medium"
              :class="pagination.prev_page_url ? 'text-gray-700 hover:bg-gray-50' : 'text-gray-300 cursor-not-allowed'"
            >
              Previous
            </button>
            <button 
              @click="$emit('page-change', pagination.current_page + 1)"
              :disabled="!pagination.next_page_url"
              class="px-3 py-1 border rounded-md text-sm font-medium"
              :class="pagination.next_page_url ? 'text-gray-700 hover:bg-gray-50' : 'text-gray-300 cursor-not-allowed'"
            >
              Next
            </button>
          </div>
        </div>
      </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue';
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue';
import { 
  CheckIcon, 
  ChevronDownIcon, 
  ChevronUpIcon,
  DocumentTextIcon,
  CurrencyDollarIcon,
  ClockIcon,
  UserIcon,
  TagIcon,
  CubeIcon,
  HomeIcon,
  OfficeBuildingIcon
} from '@heroicons/vue/outline';

export default {
  components: {
    Menu,
    MenuButton,
    MenuItems,
    MenuItem,
    CheckIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    DocumentTextIcon,
    CurrencyDollarIcon,
    ClockIcon,
    UserIcon,
    TagIcon,
    CubeIcon,
    HomeIcon,
    OfficeBuildingIcon
  },
  
  props: {
    reportData: {
      type: Array,
      required: true,
      default: () => []
    },
    columns: {
      type: Array,
      required: true,
      default: () => []
    },
    loading: {
      type: Boolean,
      default: false
    },
    pagination: {
      type: Object,
      default: null
    },
    filteredCount: {
      type: Number,
      default: null
    },
    initialSort: {
      type: Object,
      default: () => ({ column: 'created_at', direction: 'desc' })
    },
    defaultVisibleColumns: {
      type: Array,
      default: null
    },
    storageKey: {
      type: String,
      default: 'report-columns'
    }
  },
  
  emits: ['page-change', 'export', 'sort-change', 'reset-filters'],
  
  setup(props, { emit }) {
    // State
    const sort = ref({ ...props.initialSort });
    const viewMode = ref('table');
    const visibleColumnKeys = ref([]);
    const allColumns = ref([]);
    
    // Computed
    const visibleColumns = computed(() => {
      return allColumns.value.filter(col => 
        visibleColumnKeys.value.includes(col.key)
      );
    });
    
    const sortedData = computed(() => {
      if (!sort.value.column) return [...props.reportData];
      
      return [...props.reportData].sort((a, b) => {
        let valA = a[sort.value.column];
        let valB = b[sort.value.column];
        
        // Handle null/undefined values
        if (valA === null || valA === undefined) return sort.value.direction === 'asc' ? -1 : 1;
        if (valB === null || valB === undefined) return sort.value.direction === 'asc' ? 1 : -1;
        
        // Convert to numbers if possible for numeric comparison
        if (!isNaN(Number(valA)) && !isNaN(Number(valB))) {
          valA = Number(valA);
          valB = Number(valB);
        } else if (valA instanceof Date || valB instanceof Date) {
          // Handle date comparison
          valA = new Date(valA).getTime();
          valB = new Date(valB).getTime();
        } else {
          // String comparison
          valA = String(valA).toLowerCase();
          valB = String(valB).toLowerCase();
        }
        
        if (valA < valB) return sort.value.direction === 'asc' ? -1 : 1;
        if (valA > valB) return sort.value.direction === 'asc' ? 1 : -1;
        return 0;
      });
    });
    
    const visiblePages = computed(() => {
      if (!props.pagination) return [];
      
      const current = props.pagination.current_page;
      const last = props.pagination.last_page;
      const delta = 2;
      const range = [];
      
      for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
        range.push(i);
      }
      
      if (current - delta > 2) {
        range.unshift('...');
      }
      if (current + delta < last - 1) {
        range.push('...');
      }
      
      return [1, ...range, last];
    });
    
    // Methods
    const sortBy = (columnKey) => {
      if (sort.value.column === columnKey) {
        // Toggle direction if same column
        sort.value.direction = sort.value.direction === 'asc' ? 'desc' : 'asc';
      } else {
        // New column, default to ascending
        sort.value = { column: columnKey, direction: 'asc' };
      }
      
      // Emit sort change event
      emit('sort-change', { ...sort.value });
    };
    
    const toggleColumn = (columnKey) => {
      const index = visibleColumnKeys.value.indexOf(columnKey);
      if (index === -1) {
        visibleColumnKeys.value.push(columnKey);
      } else if (visibleColumnKeys.value.length > 1) {
        // Don't allow hiding all columns
        visibleColumnKeys.value.splice(index, 1);
      }
      
      // Save to localStorage
      if (props.storageKey) {
        localStorage.setItem(props.storageKey, JSON.stringify(visibleColumnKeys.value));
      }
    };
    
    const isColumnVisible = (columnKey) => {
      return visibleColumnKeys.value.includes(columnKey);
    };
    
    const selectAllColumns = () => {
      visibleColumnKeys.value = [...allColumns.value.map(col => col.key)];
      if (props.storageKey) {
        localStorage.setItem(props.storageKey, JSON.stringify(visibleColumnKeys.value));
      }
    };
    
    const deselectAllColumns = () => {
      // Keep at least one column visible
      if (allColumns.value.length > 0) {
        visibleColumnKeys.value = [allColumns.value[0].key];
        if (props.storageKey) {
          localStorage.setItem(props.storageKey, JSON.stringify(visibleColumnKeys.value));
        }
      }
    };
    
    const formatDate = (dateString) => {
      if (!dateString) return '—';
      const options = { year: 'numeric', month: 'short', day: 'numeric' };
      return new Date(dateString).toLocaleDateString(undefined, options);
    };
    
    const formatRelativeDate = (dateString) => {
      if (!dateString) return '';
      
      const now = new Date();
      const date = new Date(dateString);
      const diffInDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));
      
      if (diffInDays === 0) return 'Today';
      if (diffInDays === 1) return 'Yesterday';
      if (diffInDays < 7) return `${diffInDays} days ago`;
      if (diffInDays < 30) return `${Math.floor(diffInDays / 7)} weeks ago`;
      return ''; // Fall back to absolute date
    };
    
    const formatCurrency = (amount) => {
      if (amount === null || amount === undefined) return '—';
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
      }).format(amount);
    };
    
    const formatStatus = (status) => {
      if (!status) return '—';
      return status
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
        .join(' ');
    };
    
    const getStatusClasses = (status) => {
      const baseClasses = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full';
      
      const statusClasses = {
        active: 'bg-green-100 text-green-800',
        inactive: 'bg-gray-100 text-gray-800',
        pending: 'bg-yellow-100 text-yellow-800',
        completed: 'bg-blue-100 text-blue-800',
        failed: 'bg-red-100 text-red-800',
        default: 'bg-gray-100 text-gray-800'
      };
      
      const statusKey = status ? status.toLowerCase() : 'default';
      return `${baseClasses} ${statusClasses[statusKey] || statusClasses.default}`;
    };
    
    const getBadgeClasses = (value, variant = 'gray') => {
      const variants = {
        gray: 'bg-gray-100 text-gray-800',
        red: 'bg-red-100 text-red-800',
        yellow: 'bg-yellow-100 text-yellow-800',
        green: 'bg-green-100 text-green-800',
        blue: 'bg-blue-100 text-blue-800',
        indigo: 'bg-indigo-100 text-indigo-800',
        purple: 'bg-purple-100 text-purple-800',
        pink: 'bg-pink-100 text-pink-800'
      };
      
      return variants[variant] || variants.gray;
    };
    
    const getIconForItem = (item) => {
      // Customize based on your data structure
      if (item.type === 'document') return DocumentTextIcon;
      if (item.type === 'financial') return CurrencyDollarIcon;
      if (item.type === 'user') return UserIcon;
      if (item.type === 'category') return TagIcon;
      if (item.type === 'location') return OfficeBuildingIcon;
      if (item.type === 'asset') return CubeIcon;
      return DocumentTextIcon;
    };
    
    const getPrimaryField = (item) => {
      // Customize based on your data structure
      return item.name || item.title || item.id || '—';
    };
    
    const getSecondaryField = (item) => {
      // Customize based on your data structure
      return item.amount ? formatCurrency(item.amount) : item.description || '';
    };
    
    const getTertiaryField = (item) => {
      // Customize based on your data structure
      if (item.status) return formatStatus(item.status);
      if (item.date) return formatDate(item.date);
      return null;
    };
    
    // Lifecycle
    onMounted(() => {
      // Initialize all columns from props
      allColumns.value = [...props.columns];
      
      // Initialize visible columns
      if (props.defaultVisibleColumns) {
        visibleColumnKeys.value = [...props.defaultVisibleColumns];
      } else if (props.storageKey) {
        // Try to load from localStorage
        const savedColumns = localStorage.getItem(props.storageKey);
        if (savedColumns) {
          try {
            const parsed = JSON.parse(savedColumns);
            // Only use saved columns that exist in the current column set
            visibleColumnKeys.value = parsed.filter(key => 
              props.columns.some(col => col.key === key)
            );
          } catch (e) {
            console.error('Failed to parse saved columns', e);
          }
        }
      }
      
      // If no columns are visible (first load), show all by default
      if (visibleColumnKeys.value.length === 0 && props.columns.length > 0) {
        visibleColumnKeys.value = [...props.columns.map(col => col.key)];
      }
    });
    
    // Watch for column changes
    watch(() => props.columns, (newColumns) => {
      if (newColumns && newColumns.length > 0) {
        allColumns.value = [...newColumns];
        
        // Update visible columns to include any new columns
        const newVisibleColumns = [...visibleColumnKeys.value];
        let hasChanges = false;
        
        // Add any new columns that don't exist in visible columns
        newColumns.forEach(col => {
          if (!newVisibleColumns.includes(col.key)) {
            newVisibleColumns.push(col.key);
            hasChanges = true;
          }
        });
        
        if (hasChanges) {
          visibleColumnKeys.value = newVisibleColumns;
          if (props.storageKey) {
            localStorage.setItem(props.storageKey, JSON.stringify(newVisibleColumns));
          }
        }
      }
    }, { immediate: true });
    
    return {
      // State
      sort,
      viewMode,
      visibleColumnKeys,
      allColumns,
      
      // Computed
      visibleColumns,
      sortedData,
      visiblePages,
      
      // Methods
      sortBy,
      toggleColumn,
      isColumnVisible,
      selectAllColumns,
      deselectAllColumns,
      formatDate,
      formatRelativeDate,
      formatCurrency,
      formatStatus,
      getStatusClasses,
      getBadgeClasses,
      getIconForItem,
      getPrimaryField,
      getSecondaryField,
      getTertiaryField
    };
  }
};
</script>

<style scoped>
/* Table Styles */
.table-container {
  @apply overflow-x-auto shadow ring-1 ring-black ring-opacity-5 rounded-lg;
}

.table {
  @apply min-w-full divide-y divide-gray-300;
}

.table thead {
  @apply bg-gray-50;
}

.table th {
  @apply px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider;
  @apply select-none;
}

.table th.sortable {
  @apply cursor-pointer hover:bg-gray-100;
}

.table th.sort-asc::after {
  content: '↑';
  @apply ml-1 text-gray-400;
}

.table th.sort-desc::after {
  content: '↓';
  @apply ml-1 text-gray-400;
}

.table tbody {
  @apply bg-white divide-y divide-gray-200;
}

.table td {
  @apply px-6 py-4 whitespace-nowrap text-sm text-gray-500;
}

/* Card View */
.card {
  @apply bg-white overflow-hidden shadow rounded-lg;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
  @apply shadow-lg;
  transform: translateY(-2px);
}

/* Status Badges */
.status-badge {
  @apply px-2 inline-flex text-xs leading-5 font-semibold rounded-full;
}

/* Pagination */
.pagination {
  @apply flex items-center justify-between px-4 py-3 bg-white border-t border-gray-200 sm:px-6;
}

.pagination-button {
  @apply relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md;
  @apply bg-white text-gray-700 hover:bg-gray-50;
}

.pagination-button:disabled {
  @apply opacity-50 cursor-not-allowed;
}

.pagination-button.active {
  @apply bg-indigo-50 border-indigo-500 text-indigo-600;
  @apply z-10;
}

/* Loading State */
.loading-spinner {
  @apply animate-spin -ml-1 mr-3 h-5 w-5 text-white;
}

/* Empty State */
.empty-state {
  @apply text-center py-12 px-4 bg-white rounded-lg border-2 border-dashed border-gray-300;
}

/* Responsive Adjustments */
@media (max-width: 640px) {
  .table-container {
    @apply -mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8;
  }
  
  .table {
    @apply -mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8;
  }
  
  .table th, .table td {
    @apply px-3 py-2 text-xs;
  }
  
  .pagination {
    @apply flex-col space-y-2;
  }
  
  .pagination-info {
    @apply mb-2;
  }
}

/* Dark mode support */
.dark .table thead {
  @apply bg-gray-800;
}

.dark .table th {
  @apply text-gray-300;
}

.dark .table tbody {
  @apply bg-gray-800 divide-gray-700;
}

.dark .table td {
  @apply text-gray-300;
}

.dark .card {
  @apply bg-gray-800;
}

.dark .pagination {
  @apply bg-gray-800 border-gray-700;
}

.dark .pagination-button {
  @apply bg-gray-800 text-gray-300 border-gray-700 hover:bg-gray-700;
}

.dark .empty-state {
  @apply bg-gray-800 border-gray-700;
}
</style>
