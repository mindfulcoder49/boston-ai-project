<template>
  <PageTemplate>
    <Head title="Report History" />

    <div class="container mx-auto px-4 py-8">
      <h1 class="text-3xl font-bold text-center text-gray-800 mb-10">
        {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.reportHistoryTitle || 'Your Report History' }}
      </h1>

      <div v-if="reports.data && reports.data.length > 0" class="bg-white shadow-md rounded-lg">
        <div class="overflow-x-auto">
          <table class="min-w-full leading-normal">
            <thead>
              <tr>
                <th class="px-2 py-2 sm:px-5 sm:py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.reportTitleHeader || 'Report Title' }}
                </th>
                <th class="px-2 py-2 sm:px-5 sm:py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.locationHeader || 'Location' }}
                </th>
                <th class="px-2 py-2 sm:px-5 sm:py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.generatedAtHeader || 'Generated At' }}
                </th>
                <th class="px-2 py-2 sm:px-5 sm:py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                  {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.actionsHeader || 'Actions' }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="report in reports.data" :key="report.id" class="hover:bg-gray-50">
                <td class="px-2 py-3 sm:px-5 sm:py-4 border-b border-gray-200 bg-white text-sm">
                  <p class="text-gray-900 whitespace-normal">{{ report.title }}</p>
                </td>
                <td class="px-2 py-3 sm:px-5 sm:py-4 border-b border-gray-200 bg-white text-sm">
                  <p class="text-gray-900 whitespace-normal">{{ report.location_name }}</p>
                </td>
                <td class="px-2 py-3 sm:px-5 sm:py-4 border-b border-gray-200 bg-white text-sm">
                  <p class="text-gray-900 whitespace-normal">{{ report.generated_at }}</p>
                </td>
                <td class="px-2 py-3 sm:px-5 sm:py-4 border-b border-gray-200 bg-white text-sm">
                  <Link :href="report.view_url" class="text-indigo-600 hover:text-indigo-900 mr-3">
                    {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.viewAction || 'View' }}
                  </Link>
                  <a :href="report.download_url" target="_blank" class="text-green-600 hover:text-green-900">
                    {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.downloadAction || 'Download' }}
                  </a>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
         <!-- Pagination -->
        <div v-if="reports.links && reports.data.length > 0" class="px-3 py-3 sm:px-5 sm:py-5 bg-white border-t flex flex-col xs:flex-row items-center xs:justify-between">
            <div class="flex items-center flex-wrap justify-center xs:justify-start">
                <template v-for="(link, key) in reports.links" :key="key">
                    <div v-if="link.url === null" class="mr-1 mb-1 px-3 py-2 sm:px-4 sm:py-3 text-sm leading-4 text-gray-400 border rounded">
                        <div v-html="link.label" />
                    </div>
                    <Link v-else
                          class="mr-1 mb-1 px-3 py-2 sm:px-4 sm:py-3 text-sm leading-4 border rounded hover:bg-blue-500 hover:text-white focus:border-indigo-500 focus:text-indigo-500"
                          :class="{ 'bg-blue-500 text-white': link.active }"
                          :href="link.url"
                          preserve-scroll>
                          <div v-html="link.label" />
                    </Link>
                </template>
            </div>
             <div class="text-sm text-gray-500 mt-2 xs:mt-0">
                Showing {{ reports.from }} to {{ reports.to }} of {{ reports.total }} results
            </div>
        </div>
      </div>
      <div v-else class="text-center py-10">
        <p class="text-gray-600 text-lg">
          {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.noReportsFound || 'No reports found. Reports will appear here once they are generated for your saved locations.' }}
        </p>
      </div>
    </div>
  </PageTemplate>
</template>

<script setup>
import PageTemplate from '@/Components/PageTemplate.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, inject, ref } from 'vue';

const props = defineProps({
  reports: Object, // Paginated reports
});

const translations = inject('translations');
const language_codes = ref(['en-US']); // Assuming default

const getSingleLanguageCode = computed(() => {
  return (translations.LabelsByLanguageCode && translations.LabelsByLanguageCode[language_codes.value[0]]) ? language_codes.value[0] : 'en-US';
});

// Add translations for this page
translations.LabelsByLanguageCode['en-US'] = {
  ...translations.LabelsByLanguageCode['en-US'],
  reportHistoryTitle: 'Your Report History',
  reportTitleHeader: 'Report Title',
  locationHeader: 'Location',
  generatedAtHeader: 'Generated At',
  actionsHeader: 'Actions',
  viewAction: 'View',
  downloadAction: 'Download',
  noReportsFound: 'No reports found. Reports will appear here once they are generated for your saved locations.',
};
translations.LabelsByLanguageCode['es-MX'] = {
  ...translations.LabelsByLanguageCode['es-MX'],
  reportHistoryTitle: 'Historial de Reportes',
  reportTitleHeader: 'Título del Reporte',
  locationHeader: 'Ubicación',
  generatedAtHeader: 'Generado el',
  actionsHeader: 'Acciones',
  viewAction: 'Ver',
  downloadAction: 'Descargar',
  noReportsFound: 'No se encontraron reportes. Los reportes aparecerán aquí una vez que se generen para sus ubicaciones guardadas.',
};

</script>
