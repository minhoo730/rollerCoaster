import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    name: 'home',
    component: () => import('@/pages/home/IndexPage.vue'),
  },
  {
    path: '/markets/:market?',
    name: 'markets',
    component: () => import('@/pages/markets/IndexPage.vue'),
    props: true,
  },
  {
    path: '/stocks/:market/:code',
    name: 'stock-detail',
    component: () => import('@/pages/stocks/IndexPage.vue'),
    props: true,
  },
  {
    path: '/community/:board?',
    name: 'community',
    component: () => import('@/pages/community/IndexPage.vue'),
    props: true,
  },
  {
    path: '/community/:board/:postId',
    name: 'community-post',
    component: () => import('@/pages/community/IndexPage.vue'),
    props: true,
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    redirect: '/',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  },
})

export default router