<template>
  <div class="cascading-service-select space-y-6">
    <!-- Список добавленных категорий -->
    <div v-for="(row, index) in rows" :key="index" class="relative p-5 bg-gray-50 rounded-xl border border-gray-200 transition-all hover:shadow-md">
      
      <!-- Кнопка удаления -->
      <button 
        @click="removeRow(index)" 
        type="button"
        class="absolute -top-2 -right-2 bg-white text-gray-400 hover:text-red-500 rounded-full p-1 shadow-sm border border-gray-200 transition-colors z-10"
        title="Удалить группу услуг"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <!-- Категория -->
        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Категория</label>
          <div class="relative">
            <select 
              v-model="row.category_id" 
              @change="onCategoryChange(row)"
              class="block w-full pl-3 pr-10 py-2.5 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-lg shadow-sm bg-white"
            >
              <option :value="null" disabled>Выберите...</option>
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
            </select>
          </div>
        </div>

        <!-- Подкатегория -->
        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Подкатегория</label>
          <select 
            v-model="row.subcategory_id" 
            @change="onSubcategoryChange(row)"
            :disabled="!row.category_id || row.subcategories.length === 0"
            class="block w-full pl-3 pr-10 py-2.5 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-lg shadow-sm bg-white disabled:bg-gray-100 disabled:text-gray-400"
          >
            <option :value="null" disabled>{{ row.category_id ? 'Выберите...' : '—' }}</option>
            <option v-for="sub in row.subcategories" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
          </select>
        </div>
      </div>

      <!-- Услуги (Чекбоксы) -->
      <div v-if="row.subcategory_id && row.services.length > 0" class="mt-4 pt-4 border-t border-gray-200">
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
          Выберите услуги <span class="text-indigo-500 text-[10px] ml-1 font-normal normal-case">(можно несколько)</span>
        </label>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
          <label 
            v-for="srv in row.services" 
            :key="srv.id" 
            class="relative flex items-start py-2 px-3 rounded-lg border cursor-pointer transition-all hover:bg-white"
            :class="row.selected_service_ids.includes(srv.id) 
              ? 'bg-indigo-50 border-indigo-200 shadow-sm ring-1 ring-indigo-200' 
              : 'bg-gray-50 border-transparent hover:border-gray-200'"
          >
            <div class="min-w-0 flex-1 text-sm">
              <div class="font-medium text-gray-900 select-none">{{ srv.name }}</div>
            </div>
            <div class="ml-3 flex items-center h-5">
              <input 
                type="checkbox" 
                :value="srv.id"
                v-model="row.selected_service_ids"
                @change="emitUpdate"
                class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
              />
            </div>
          </label>
        </div>
      </div>
      
      <div v-else-if="row.subcategory_id && row.services.length === 0" class="text-sm text-gray-500 italic mt-2">
        В этой подкатегории нет доступных услуг.
      </div>
    </div>

    <!-- Кнопка добавления -->
    <button 
      @click="addRow" 
      type="button"
      class="group relative w-full flex justify-center py-3 px-4 border-2 border-dashed border-gray-300 rounded-xl text-sm font-medium text-gray-600 hover:text-indigo-600 hover:border-indigo-300 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all"
    >
      <span class="flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
        </svg>
        Добавить категорию услуг
      </span>
    </button>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['update:modelValue']);

const categories = ref([]);
const rows = ref([]);

// Загрузка корневых категорий
const fetchCategories = async () => {
  try {
    const response = await axios.get('/api/services/by-parent?parent_id=-1');
    categories.value = response.data;
  } catch (e) {
    console.error('Failed to load categories', e);
  }
};

// Загрузка дочерних элементов
const fetchChildren = async (parentId) => {
  try {
    const response = await axios.get(`/api/services/by-parent?parent_id=${parentId}`);
    return response.data;
  } catch (e) {
    console.error(`Failed to load children for ${parentId}`, e);
    return [];
  }
};

// Инициализация строк при загрузке существующих данных
const initRows = async () => {
  // Загружаем категории в любом случае
  await fetchCategories();

  if (!props.modelValue || props.modelValue.length === 0) {
    addRow();
    return;
  }

  try {
    // Запрашиваем цепочки для существующих ID
    const response = await axios.post('/api/services/resolve-chain', {
      ids: props.modelValue
    });
    
    const chains = response.data;
    
    // Группируем услуги по подкатегориям, чтобы не создавать 100500 строк
    // Ключ: category_id + '-' + subcategory_id
    const grouped = {};
    
    chains.forEach(chain => {
        const key = `${chain.category_id}-${chain.subcategory_id}`;
        if (!grouped[key]) {
            grouped[key] = {
                category_id: chain.category_id,
                subcategory_id: chain.subcategory_id,
                service_ids: []
            };
        }
        grouped[key].service_ids.push(chain.service_id);
    });
    
    // Создаем строки для каждой группы
    for (const key in grouped) {
        const group = grouped[key];
        
        // Загружаем данные для селектов
        const subcategories = await fetchChildren(group.category_id);
        const services = await fetchChildren(group.subcategory_id);
        
        rows.value.push({
            category_id: group.category_id,
            subcategory_id: group.subcategory_id,
            selected_service_ids: group.service_ids, // Массив ID
            subcategories: subcategories,
            services: services
        });
    }
    
    if (rows.value.length === 0) {
        addRow();
    }

  } catch (e) {
    console.error('Failed to resolve service chains', e);
    // Fallback
    addRow();
  }
};

const addRow = () => {
  rows.value.push({
    category_id: null,
    subcategory_id: null,
    selected_service_ids: [], // Множественный выбор
    subcategories: [],
    services: []
  });
};

const removeRow = (index) => {
  rows.value.splice(index, 1);
  emitUpdate();
};

const onCategoryChange = async (row) => {
  row.subcategory_id = null;
  row.selected_service_ids = [];
  row.subcategories = [];
  row.services = [];
  
  if (row.category_id) {
    row.subcategories = await fetchChildren(row.category_id);
  }
  emitUpdate();
};

const onSubcategoryChange = async (row) => {
  row.selected_service_ids = [];
  row.services = [];
  
  if (row.subcategory_id) {
    row.services = await fetchChildren(row.subcategory_id);
  }
  emitUpdate();
};

const emitUpdate = () => {
  // Собираем ВСЕ выбранные ID со всех строк
  let allSelectedIds = [];
  
  rows.value.forEach(row => {
      if (row.selected_service_ids && row.selected_service_ids.length > 0) {
          allSelectedIds = allSelectedIds.concat(row.selected_service_ids);
      }
  });
  
  // Убираем дубликаты на всякий случай
  const uniqueIds = [...new Set(allSelectedIds)];
    
  emit('update:modelValue', uniqueIds);
};

onMounted(() => {
  initRows();
});
</script>
