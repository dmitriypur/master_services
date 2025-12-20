<template>
  <div class="max-w-2xl mx-auto py-6 space-y-6 pb-20">
    <!-- Кнопки навигации перенесены вниз -->
    
    <Card>
      <template #title>Настройки мастера</template>
      <template #content>
        <form @submit.prevent="submit" class="flex flex-col gap-4">
          
          <div class="flex flex-col gap-2">
            <label for="city" class="font-medium">Город <span class="text-red-500">*</span></label>
            <Select v-model="form.city_id" :options="cities" optionLabel="name" optionValue="id" placeholder="Выберите город" :invalid="!!form.errors.city_id" class="w-full" />
            <small v-if="form.errors.city_id" class="text-red-500">{{ form.errors.city_id }}</small>
          </div>

          <div class="flex flex-col gap-2">
            <label for="address" class="font-medium">Адрес <span class="text-red-500">*</span></label>
            <InputText id="address" v-model="form.address" placeholder="Улица, дом, кабинет" :invalid="!!form.errors.address" />
            <small v-if="form.errors.address" class="text-red-500">{{ form.errors.address }}</small>
          </div>

          <div class="flex flex-col gap-2">
            <label for="phone" class="font-medium">Телефон <span class="text-red-500">*</span></label>
            <InputMask id="phone" v-model="form.phone" mask="89999999999" placeholder="89990000000" :invalid="!!form.errors.phone" />
            <small v-if="form.errors.phone" class="text-red-500">{{ form.errors.phone }}</small>
          </div>

          <div class="flex flex-col gap-2">
            <label class="font-medium">Дни недели <span class="text-red-500">*</span></label>
            <div class="flex flex-wrap gap-2">
               <SelectButton v-model="form.work_days" :options="weekDays" optionLabel="label" optionValue="key" multiple aria-labelledby="multiple" />
            </div>
            <small v-if="form.errors.work_days" class="text-red-500">{{ form.errors.work_days }}</small>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="flex flex-col gap-2">
              <label for="work_time_from" class="font-medium">Время с <span class="text-red-500">*</span></label>
              <InputText id="work_time_from" v-model="form.work_time_from" type="time" :invalid="!!form.errors.work_time_from" />
              <small v-if="form.errors.work_time_from" class="text-red-500">{{ form.errors.work_time_from }}</small>
            </div>
            <div class="flex flex-col gap-2">
              <label for="work_time_to" class="font-medium">Время до <span class="text-red-500">*</span></label>
              <InputText id="work_time_to" v-model="form.work_time_to" type="time" :invalid="!!form.errors.work_time_to" />
              <small v-if="form.errors.work_time_to" class="text-red-500">{{ form.errors.work_time_to }}</small>
            </div>
          </div>

          <div class="flex flex-col gap-2">
            <label for="slot_duration_min" class="font-medium">Шаг слота (мин) <span class="text-red-500">*</span></label>
            <Select 
                id="slot_duration_min" 
                v-model="form.slot_duration_min" 
                :options="[5, 10, 15, 20, 30, 60]" 
                placeholder="Выберите шаг" 
                class="w-full"
            />
            <small class="text-gray-500">Минимальный шаг сетки календаря. Если у вас есть услуги по 20 или 40 минут, лучше выбрать 5 или 10.</small>
          </div>

          <div class="flex flex-col gap-2">
            <label class="font-medium">Услуги <span class="text-red-500">*</span></label>
            <CascadingServiceSelect v-model="selectedServiceIds" />
            <small v-if="form.errors.services" class="text-red-500">{{ form.errors.services }}</small>

            <div v-if="selectedServices.length" class="mt-4 space-y-3">
              <div v-for="srv in selectedServices" :key="srv.id" class="rounded-lg border bg-white p-3">
                <div class="font-medium text-gray-900">{{ srv.name }}</div>
                <div class="mt-3 grid grid-cols-2 gap-3">
                  <div class="flex flex-col gap-1">
                    <label class="text-sm text-gray-600">Цена (₽)</label>
                    <InputText v-model.number="srv.price" type="number" inputmode="numeric" min="0" />
                  </div>
                  <div class="flex flex-col gap-1">
                    <label class="text-sm text-gray-600">Время (мин)</label>
                    <InputText v-model.number="srv.duration" type="number" inputmode="numeric" min="1" />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="pt-4">
            <Button type="submit" label="Сохранить" icon="pi pi-check" class="w-full" :loading="form.processing" />
          </div>
        </form>
      </template>
    </Card>

    <!-- Плавающая панель навигации внизу -->
    <div class="fixed bottom-4 left-0 right-0 flex justify-center gap-4 px-4 z-10" v-if="user?.is_active">
       <Link href="/master/calendar">
         <Button label="В календарь" icon="pi pi-calendar" severity="success" raised rounded />
       </Link>
       <Link href="/master/clients">
         <Button label="Клиенты" icon="pi pi-users" severity="info" raised rounded />
       </Link>
    </div>
  </div>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import MasterLayout from '../../Layouts/MasterLayout.vue'
import CascadingServiceSelect from '../../components/UI/CascadingServiceSelect.vue'

// PrimeVue Components
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputMask from 'primevue/inputmask'
import Select from 'primevue/select'
import SelectButton from 'primevue/selectbutton'
import Card from 'primevue/card'

const props = defineProps({ user: Object, cities: Array, settings: Object, servicesOptions: Array, selectedServiceIds: Array, selectedServices: Array })
defineOptions({ layout: MasterLayout })

const form = useForm({
  city_id: props.user?.city_id ?? null,
  address: props.settings?.address ?? '',
  phone: props.user?.phone ?? '',
  work_days: props.settings?.work_days ?? [],
  work_time_from: props.settings?.work_time_from ?? '',
  work_time_to: props.settings?.work_time_to ? props.settings.work_time_to.substring(0, 5) : '',
  slot_duration_min: props.settings?.slot_duration_min ?? 15,
  services: []
})

const servicesById = computed(() => {
  const map = new Map()
  ;(props.servicesOptions ?? []).forEach(s => map.set(Number(s.id), s.name))
  return map
})

const selectedServiceIds = ref((props.selectedServiceIds ?? []).map(Number))
const selectedServices = ref((props.selectedServices ?? []).map(s => ({
  id: Number(s.id),
  name: s.name,
  price: s.price ?? null,
  duration: Number(s.duration ?? 60),
})))

watch(selectedServiceIds, (ids) => {
  const uniqueIds = [...new Set((ids ?? []).map(Number).filter(Boolean))]
  const existingById = new Map(selectedServices.value.map(s => [Number(s.id), s]))

  selectedServices.value = uniqueIds.map(id => {
    const existing = existingById.get(id)
    if (existing) return existing
    return {
      id,
      name: servicesById.value.get(id) ?? `#${id}`,
      price: null,
      duration: 60,
    }
  })
}, { immediate: true })

const weekDays = [
  { key: 1, label: 'Пн' },
  { key: 2, label: 'Вт' },
  { key: 3, label: 'Ср' },
  { key: 4, label: 'Чт' },
  { key: 5, label: 'Пт' },
  { key: 6, label: 'Сб' },
  { key: 7, label: 'Вс' },
]

function submit() {
    // Приводим данные к нужному формату перед отправкой
    const payload = {
      ...form.data(),
      // Фильтруем null и undefined, и убеждаемся что числа
      work_days: form.work_days
        .filter(d => d !== null && d !== undefined)
        .map(Number),
      services: selectedServices.value.map(s => ({
        id: Number(s.id),
        price: s.price === null || s.price === '' || Number.isNaN(Number(s.price)) ? null : Number(s.price),
        duration: Number(s.duration),
      }))
    }
    
    // Используем обычный Inertia router для ручной отправки
    form.defaults(payload)
    form.work_days = payload.work_days 
    form.services = payload.services
    
    form.put('/master/settings', {
      onSuccess: () => {
        // Можно добавить Toast
      },
      onError: (errors) => {
        console.log('Errors:', errors)
      }
    })
  }
  </script>

<style scoped>
</style>
