<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PageTemplate from '@/Components/PageTemplate.vue';
import { marked } from 'marked';
import { computed } from 'vue';

const props = defineProps({
  article: Object,
});

const renderedContent = computed(() => {
  if (props.article && props.article.content) {
    return marked(props.article.content);
  }
  return '';
});
</script>

<template>
  <PageTemplate>
    <Head :title="article.headline" />

    <div class="py-12 bg-gray-50">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <article class="p-8 sm:p-12">
            <header class="mb-8 border-b pb-6">
              <div class="mb-4">
                <span class="text-sm font-semibold text-indigo-600 uppercase">{{ article.source_model_name }}</span>
              </div>
              <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 leading-tight">
                {{ article.headline }}
              </h1>
              <p class="mt-4 text-md text-gray-500">
                Published on <time :datetime="article.published_at">{{ article.published_at }}</time>
              </p>
            </header>

            <div
              class="prose prose-lg max-w-none prose-indigo prose-a:text-indigo-600 hover:prose-a:text-indigo-800 prose-headings:font-bold"
              v-html="renderedContent"
            ></div>

            <footer class="mt-12 pt-8 border-t">
              <div v-if="article.source_report_url">
                <Link :href="article.source_report_url" class="text-indigo-600 hover:text-indigo-800 hover:underline">
                  &larr; View the original data report
                </Link>
              </div>
               <div class="mt-4">
                <Link :href="route('news.index')" class="text-gray-600 hover:text-gray-800 hover:underline">
                  &larr; Back to all articles
                </Link>
              </div>
            </footer>
          </article>
        </div>
      </div>
    </div>
  </PageTemplate>
</template>

<style>
.prose h2 {
  font-size: 1.875rem; /* 30px */
  margin-top: 2.5em;
  margin-bottom: 1em;
}
.prose h3 {
  font-size: 1.5rem; /* 24px */
  margin-top: 2em;
  margin-bottom: 0.8em;
}
</style>
