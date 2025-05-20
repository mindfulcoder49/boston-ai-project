<template>
  <PageTemplate>
    <Head :title="report.title || 'View Report'" />

    <div class="container mx-auto px-4 py-8">
      <div class="bg-white shadow-xl rounded-lg p-6 md:p-8">
        <div class="flex justify-between items-start mb-6">
          <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800">
              {{ report.title }}
            </h1>
            <p class="text-sm text-gray-500 mt-1">
              {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.locationLabel || 'Location' }}: {{ report.location_name }}
            </p>
            <p class="text-sm text-gray-500">
              {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.generatedAtLabel || 'Generated' }}: {{ report.generated_at }}
            </p>
          </div>
          <a :href="report.download_url"
             target="_blank"
             class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 active:bg-green-700 focus:outline-none focus:border-green-700 focus:ring focus:ring-green-200 disabled:opacity-25 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.downloadReportButton || 'Download Report' }}
          </a>
        </div>

        <div class="prose prose-sm sm:prose lg:prose-lg xl:prose-xl max-w-none report-content p-4 border border-gray-200 rounded-md bg-gray-50">
          <!-- Using v-html for markdown. -->
          <div v-html="renderedMarkdownContent"></div>
        </div>

        <div class="mt-8 text-center">
          <Link :href="route('reports.index')" class="text-indigo-600 hover:text-indigo-800 font-medium">
            &larr; {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.backToReportHistoryLink || 'Back to Report History' }}
          </Link>
        </div>
      </div>
    </div>
  </PageTemplate>
</template>

<script setup>
import PageTemplate from '@/Components/PageTemplate.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, inject, ref } from 'vue';
import markdownit from 'markdown-it';
import markdownItLinkAttributes from 'markdown-it-link-attributes';

const props = defineProps({
  report: Object,
});

const md = markdownit({
  html: true,
  linkify: true,
  typographer: true,
  breaks: true,
});

md.use(markdownItLinkAttributes, {
  attrs: {
    target: "_blank",
    rel: "noopener",
  },
});

const translations = inject('translations');
const language_codes = ref(['en-US']);

const getSingleLanguageCode = computed(() => {
  return (translations.LabelsByLanguageCode && translations.LabelsByLanguageCode[language_codes.value[0]]) ? language_codes.value[0] : 'en-US';
});

const renderedMarkdownContent = computed(() => {
  if (props.report && props.report.content) {
    return md.render(props.report.content);
  }
  return '';
});

// Add translations for this page
translations.LabelsByLanguageCode['en-US'] = {
  ...translations.LabelsByLanguageCode['en-US'],
  locationLabel: 'Location',
  generatedAtLabel: 'Generated',
  downloadReportButton: 'Download Report',
  backToReportHistoryLink: 'Back to Report History',
};
translations.LabelsByLanguageCode['es-MX'] = {
  ...translations.LabelsByLanguageCode['es-MX'],
  locationLabel: 'Ubicación',
  generatedAtLabel: 'Generado',
  downloadReportButton: 'Descargar Reporte',
  backToReportHistoryLink: 'Volver al Historial de Reportes',
};

</script>

<style scoped>
.report-content :deep(pre) { /* Apply to pre tags within the v-html rendered content */
  font-family: inherit;
  font-size: 0.9rem;
  line-height: 1.6;
  background-color: #f9fafb; /* bg-gray-50 */
  padding: 1rem;
  border-radius: 0.375rem; /* rounded-md */
  overflow-x: auto;
  white-space: pre-wrap; /* Ensure pre content wraps */
  word-wrap: break-word; /* Ensure long words break */
}
/* Tailwind's prose classes should handle most other styling for p, h1-h6, ul, ol, etc. */
/* If you install @tailwindcss/typography, its 'prose' classes are very helpful here.
   The .report-content div already has prose classes.
*/
</style>
