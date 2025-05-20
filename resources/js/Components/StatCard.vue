<template>
  <div class="bg-white overflow-hidden shadow rounded-lg">
    <div class="p-5">
      <div class="flex items-center">
        <div class="flex-shrink-0 bg-indigo-500 rounded-md p-3">
          <component 
            :is="stat.icon" 
            class="h-6 w-6 text-white" 
            aria-hidden="true" 
          />
        </div>
        <div class="ml-5 w-0 flex-1">
          <dl>
            <dt class="text-sm font-medium text-gray-500 truncate">
              {{ stat.label }}
              <span 
                v-if="stat.trend" 
                :class="[
                  'ml-2 text-xs font-medium',
                  stat.trend === 'up' ? 'text-green-600' : 'text-red-600'
                ]"
              >
                {{ stat.change }}
                <span v-if="stat.trend === 'up'">↑</span>
                <span v-else>↓</span>
              </span>
            </dt>
            <dd class="flex items-baseline">
              <div class="text-2xl font-semibold text-gray-900">
                {{ stat.value }}
              </div>
            </dd>
          </dl>
        </div>
      </div>
    </div>
    <div class="bg-gray-50 px-5 py-3">
      <div class="text-sm">
        <a 
          href="#" 
          class="font-medium text-indigo-600 hover:text-indigo-500"
          @click.prevent="$emit('view-more')"
        >
          View all
        </a>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'StatCard',
  props: {
    stat: {
      type: Object,
      required: true,
      default: () => ({
        label: '',
        value: '',
        icon: null,
        trend: null, // 'up' or 'down'
        change: null, // e.g., '5%'
      }),
    },
  },
};
</script>
