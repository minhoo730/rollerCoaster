<template>
  <header class="sticky top-0 z-20 border-b border-border/80 bg-white/86 backdrop-blur-xl">
    <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8">
      <a href="/" class="flex items-center gap-3">
        <span class="grid h-10 w-10 place-items-center rounded-xl bg-primary text-base font-bold text-white">R</span>
        <span>
          <span class="block text-title-sm font-semibold text-text-primary">RollerCoaster</span>
          <span class="block text-caption text-text-secondary">시장 현황 기반 주식 커뮤니티</span>
        </span>
      </a>

      <form class="flex w-full gap-2 lg:max-w-md" @submit.prevent="submitSearch">
        <label class="sr-only" for="stock-search">종목 검색</label>
        <input
          id="stock-search"
          :value="searchCode"
          class="h-11 min-w-0 flex-1 rounded-lg border border-border bg-surface px-4 text-body outline-none transition focus:border-primary focus:ring-4 focus:ring-primary/10"
          placeholder="지수·종목명·종목코드 검색"
          @input="$emit('update:searchCode', $event.target.value)"
        >
        <button class="h-11 rounded-lg bg-primary px-4 text-body-sm font-semibold text-white transition hover:bg-primary-hover" type="submit">
          검색
        </button>
      </form>
    </div>
  </header>
</template>

<script setup>
const props = defineProps({
  searchCode: {
    type: String,
    required: true,
  },
})

const emit = defineEmits(['update:searchCode', 'search'])

function submitSearch() {
  if (!props.searchCode.trim()) {
    return
  }

  emit('search')
}
</script>
