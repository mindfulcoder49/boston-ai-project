<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PageTemplate from '@/Components/PageTemplate.vue';
import Pagination from '@/Components/Pagination.vue';

defineProps({
  articles: Object,
});
</script>

<template>
  <PageTemplate>
    <Head title="News Articles" />

    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-8 text-gray-900">
            <h1 class="text-4xl font-bold mb-8 border-b pb-4 text-center">Data-Driven News</h1>

            <div v-if="articles.data.length > 0" class="space-y-10">
              <article v-for="article in articles.data" :key="article.id" class="group">
                <div class="mb-2">
                  <span class="text-sm font-semibold text-gray-500 uppercase">{{ article.source_model_name }}</span>
                  <span class="text-gray-400 mx-2">|</span>
                  <time :datetime="article.published_at" class="text-sm text-gray-500">{{ article.published_at }}</time>
                </div>
                <Link :href="route('news.show', article.slug)">
                  <h2 class="text-3xl font-bold text-gray-800 group-hover:text-indigo-600 transition-colors duration-200">
                    {{ article.headline }}
                  </h2>
                </Link>
                <p class="mt-4 text-lg text-gray-600 leading-relaxed">
                  {{ article.summary }}
                </p>
                <Link :href="route('news.show', article.slug)" class="mt-4 inline-block font-semibold text-indigo-600 hover:text-indigo-800">
                  Read more &rarr;
                </Link>
              </article>

              <Pagination :links="articles.links" class="mt-12" />
            </div>
            <div v-else class="text-center py-16">
              <p class="text-xl text-gray-500">No news articles have been published yet.</p>
              <p class="text-md text-gray-400 mt-2">Run `php artisan app:dispatch-news-article-generation-jobs` to create articles.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </PageTemplate>
</template>
