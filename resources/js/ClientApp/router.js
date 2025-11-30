import Search from '../Pages/Client/Search.vue'
import BookingFlow from '../Pages/Client/BookingFlow.vue'

export default [
  { path: '/', component: Search },
  { path: '/masters/:id/book', component: BookingFlow, props: true },
]
