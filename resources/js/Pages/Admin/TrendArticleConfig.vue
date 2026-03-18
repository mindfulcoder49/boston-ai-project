<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    trend:           { type: Object, required: true },
    summary:         { type: Object, default: null },
    config:          { type: Object, default: null },
    existingArticle: { type: Object, default: null },
    defaultPrompt:   { type: String, default: '' },
});

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

// ── Form state ────────────────────────────────────────────────────────────────

const introPrompt     = ref(props.config?.intro_prompt ?? null);
const useDefaultPrompt = ref(!props.config?.intro_prompt);
const configStatus    = ref(props.config?.status ?? 'draft');

// Categories
const allCategories  = computed(() => props.summary?.all_categories ?? props.summary?.top_categories ?? []);
const selectedCategories = ref(
    props.config?.included_categories ? [...props.config.included_categories] : null
);
// null = all included; array = specific selection
const categoryMode   = ref(props.config?.included_categories ? 'custom' : 'all');

function toggleCategoryMode(mode) {
    categoryMode.value = mode;
    if (mode === 'all') {
        selectedCategories.value = null;
    } else if (mode === 'custom' && selectedCategories.value === null) {
        selectedCategories.value = [...allCategories.value];
    }
}

function toggleCategory(cat) {
    if (!selectedCategories.value) selectedCategories.value = [...allCategories.value];
    const idx = selectedCategories.value.indexOf(cat);
    if (idx >= 0) selectedCategories.value.splice(idx, 1);
    else selectedCategories.value.push(cat);
}

function isCategorySelected(cat) {
    return selectedCategories.value === null || selectedCategories.value.includes(cat);
}

// Finding types
const availableWindows = computed(() => props.summary?.available_windows ?? Object.keys(props.summary?.top_trends_by_window ?? {}));

const allFindingTypes  = computed(() => {
    const types = ['anomaly'];
    for (const w of availableWindows.value) types.push(w);
    return types;
});

const findingTypeLabel = (key) => {
    if (key === 'anomaly') return `Anomaly (${props.trend.analysis_weeks_anomaly}w baseline)`;
    const num = key.replace(/[^0-9]/g, '');
    return `Trend — ${num}w window (${key})`;
};

const selectedFindingTypes = ref(
    props.config?.included_finding_types ? [...props.config.included_finding_types] : null
);
const findingTypeMode = ref(props.config?.included_finding_types ? 'custom' : 'all');

function toggleFindingTypeMode(mode) {
    findingTypeMode.value = mode;
    if (mode === 'all') selectedFindingTypes.value = null;
    else if (mode === 'custom' && selectedFindingTypes.value === null) {
        selectedFindingTypes.value = [...allFindingTypes.value];
    }
}

function toggleFindingType(key) {
    if (!selectedFindingTypes.value) selectedFindingTypes.value = [...allFindingTypes.value];
    const idx = selectedFindingTypes.value.indexOf(key);
    if (idx >= 0) selectedFindingTypes.value.splice(idx, 1);
    else selectedFindingTypes.value.push(key);
}

function isFindingTypeSelected(key) {
    return selectedFindingTypes.value === null || selectedFindingTypes.value.includes(key);
}

// ── Token estimation ──────────────────────────────────────────────────────────

const tokenCount    = ref(null);
const tokenBusy     = ref(false);
const tokenError    = ref(null);
const promptPreview = ref(null); // { system_prompt, user_prompt }
const showPrompt    = ref(false);

async function estimateTokens() {
    tokenBusy.value    = true;
    tokenError.value   = null;
    tokenCount.value   = null;
    promptPreview.value = null;
    showPrompt.value   = false;
    try {
        const res = await fetch(route('admin.news-articles.estimate-tokens-preview'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify({
                source_type:             'trend',
                trend_id:                props.trend.id,
                intro_prompt:            useDefaultPrompt.value ? null : (introPrompt.value || null),
                included_categories:     categoryMode.value === 'custom' ? selectedCategories.value : null,
                included_finding_types:  findingTypeMode.value === 'custom' ? selectedFindingTypes.value : null,
            }),
        });
        const data = await res.json();
        if (data.error) {
            tokenError.value = data.error;
        } else {
            tokenCount.value    = data.input_tokens;
            promptPreview.value = { system: data.system_prompt, user: data.user_prompt };
        }
    } catch (e) {
        tokenError.value = e.message;
    } finally {
        tokenBusy.value = false;
    }
}

// ── Save & generate ───────────────────────────────────────────────────────────

const saving   = ref(false);
const saveMsg  = ref(null);
const saveMsgOk = ref(true);
const configId = ref(props.config?.id ?? null);

async function saveConfig() {
    saving.value  = true;
    saveMsg.value = null;
    try {
        const res = await fetch(route('admin.news-articles.trends.save-config', props.trend.id), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify({
                intro_prompt:            useDefaultPrompt.value ? null : (introPrompt.value || null),
                included_categories:     categoryMode.value === 'custom' ? selectedCategories.value : null,
                included_finding_types:  findingTypeMode.value === 'custom' ? selectedFindingTypes.value : null,
                status:                  configStatus.value,
            }),
        });
        const data = await res.json();
        saveMsgOk.value  = data.success;
        saveMsg.value    = data.message;
        if (data.config_id) configId.value = data.config_id;
    } catch (e) {
        saveMsgOk.value = false;
        saveMsg.value   = e.message;
    } finally {
        saving.value = false;
    }
}

const generating  = ref(false);
const generateMsg = ref(null);
const generateOk  = ref(true);

async function saveAndGenerate() {
    await saveConfig();
    if (!saveMsgOk.value) return;
    if (!configId.value) return;
    generating.value  = true;
    generateMsg.value = null;
    try {
        const res = await fetch(route('admin.news-articles.configs.generate', configId.value), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: JSON.stringify({}),
        });
        const data = await res.json();
        generateOk.value  = data.success;
        generateMsg.value = data.message;
    } catch (e) {
        generateOk.value  = false;
        generateMsg.value = e.message;
    } finally {
        generating.value = false;
    }
}
</script>

<template>
    <AdminLayout>
        <Head :title="`Configure: ${trend.title}`" />

        <div class="max-w-4xl mx-auto space-y-6">

            <!-- Breadcrumb -->
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <Link :href="route('admin.news-articles.index')" class="hover:text-indigo-600">News Article Generator</Link>
                <span>›</span>
                <span class="text-gray-800 font-medium truncate">{{ trend.title }}</span>
            </div>

            <!-- Header -->
            <div class="bg-white rounded-lg border shadow-sm p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">{{ trend.title }}</h1>
                        <div class="flex flex-wrap gap-2 mt-2 text-xs text-gray-500">
                            <span class="bg-gray-100 px-2 py-0.5 rounded">{{ trend.city }}</span>
                            <span class="bg-gray-100 px-2 py-0.5 rounded font-mono">res {{ trend.h3_resolution }}</span>
                            <span v-if="trend.last_run" class="bg-gray-100 px-2 py-0.5 rounded">Last run: {{ trend.last_run }}</span>
                            <span class="bg-gray-100 px-2 py-0.5 rounded font-mono">{{ trend.job_id }}</span>
                        </div>
                        <div class="flex gap-4 mt-2 text-xs text-gray-500">
                            <span v-if="summary">
                                <span class="text-amber-600 font-semibold">⚠ {{ summary.anomaly_count }}</span> anomalies ·
                                <span class="text-blue-600 font-semibold">↗ {{ summary.trend_count }}</span> trends ·
                                {{ summary.affected_h3_count }} areas
                            </span>
                            <span v-else class="text-gray-400 italic">No summary cached</span>
                        </div>
                    </div>
                    <div v-if="existingArticle" class="text-right shrink-0">
                        <span class="inline-block px-2 py-0.5 rounded text-xs font-medium" :class="{
                            'text-green-700 bg-green-50': existingArticle.status === 'published',
                            'text-amber-700 bg-amber-50': existingArticle.status === 'draft' || existingArticle.status === 'generating',
                            'text-red-700 bg-red-50':     existingArticle.status === 'error',
                        }">{{ existingArticle.status }}</span>
                        <Link v-if="existingArticle.status === 'published'" :href="route('news.show', existingArticle.slug)" class="block text-xs text-indigo-600 hover:underline mt-1 max-w-[180px] truncate">{{ existingArticle.title }}</Link>
                    </div>
                </div>
            </div>

            <!-- Intro Prompt -->
            <div class="bg-white rounded-lg border shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold text-gray-800">Intro Prompt (System Prompt)</h2>
                    <label class="flex items-center gap-2 text-xs text-gray-600 cursor-pointer">
                        <input type="checkbox" v-model="useDefaultPrompt" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        Use default
                    </label>
                </div>
                <textarea
                    v-if="!useDefaultPrompt"
                    v-model="introPrompt"
                    rows="10"
                    class="w-full text-sm font-mono border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Enter a custom system prompt for this article..."
                ></textarea>
                <div v-else class="text-xs text-gray-500 bg-gray-50 rounded border p-3 font-mono whitespace-pre-wrap max-h-40 overflow-y-auto">{{ defaultPrompt }}</div>
            </div>

            <!-- Categories -->
            <div class="bg-white rounded-lg border shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold text-gray-800">Categories to Include</h2>
                    <div class="flex gap-2">
                        <button @click="toggleCategoryMode('all')" class="text-xs px-2 py-1 rounded border transition-colors" :class="categoryMode === 'all' ? 'bg-indigo-600 text-white border-indigo-600' : 'hover:bg-gray-50'">All</button>
                        <button @click="toggleCategoryMode('custom')" class="text-xs px-2 py-1 rounded border transition-colors" :class="categoryMode === 'custom' ? 'bg-indigo-600 text-white border-indigo-600' : 'hover:bg-gray-50'">Custom</button>
                    </div>
                </div>

                <p v-if="categoryMode === 'all'" class="text-sm text-gray-500 italic">All categories will be included in the prompt.</p>

                <div v-else>
                    <p class="text-xs text-gray-500 mb-2">
                        Select which secondary_group values to include. Unselected categories will be stripped from the data before sending to GPT.
                        <span class="text-amber-600">({{ selectedCategories?.length ?? 0 }} of {{ allCategories.length }} selected)</span>
                    </p>
                    <div v-if="allCategories.length === 0" class="text-xs text-gray-400 italic">No categories available — ensure the summary is cached.</div>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-x-4 gap-y-1.5 max-h-64 overflow-y-auto pr-1">
                        <label v-for="cat in allCategories" :key="cat" class="flex items-center gap-2 text-sm cursor-pointer select-none" :class="{ 'opacity-50': !isCategorySelected(cat) }">
                            <input type="checkbox" :checked="isCategorySelected(cat)" @change="toggleCategory(cat)" class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 flex-shrink-0">
                            <span class="truncate" :class="{ 'line-through': !isCategorySelected(cat) }">{{ cat }}</span>
                        </label>
                    </div>
                    <div class="flex gap-2 mt-2">
                        <button @click="selectedCategories = [...allCategories]" class="text-xs text-indigo-600 hover:underline">Select all</button>
                        <button @click="selectedCategories = []" class="text-xs text-indigo-600 hover:underline">Clear all</button>
                    </div>
                </div>
            </div>

            <!-- Finding Types -->
            <div class="bg-white rounded-lg border shadow-sm p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold text-gray-800">Finding Types to Include</h2>
                    <div class="flex gap-2">
                        <button @click="toggleFindingTypeMode('all')" class="text-xs px-2 py-1 rounded border transition-colors" :class="findingTypeMode === 'all' ? 'bg-indigo-600 text-white border-indigo-600' : 'hover:bg-gray-50'">All</button>
                        <button @click="toggleFindingTypeMode('custom')" class="text-xs px-2 py-1 rounded border transition-colors" :class="findingTypeMode === 'custom' ? 'bg-indigo-600 text-white border-indigo-600' : 'hover:bg-gray-50'">Custom</button>
                    </div>
                </div>

                <p v-if="findingTypeMode === 'all'" class="text-sm text-gray-500 italic">All finding types (anomalies + all trend windows) will be included.</p>

                <div v-else class="space-y-2">
                    <label v-for="key in allFindingTypes" :key="key" class="flex items-center gap-3 text-sm cursor-pointer select-none" :class="{ 'opacity-50': !isFindingTypeSelected(key) }">
                        <input type="checkbox" :checked="isFindingTypeSelected(key)" @change="toggleFindingType(key)" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span>{{ findingTypeLabel(key) }}</span>
                    </label>
                    <p v-if="allFindingTypes.length === 0" class="text-xs text-gray-400 italic">No finding types found — ensure the summary is cached.</p>
                </div>
            </div>

            <!-- Status -->
            <div class="bg-white rounded-lg border shadow-sm p-5">
                <h2 class="font-semibold text-gray-800 mb-3">Config Status</h2>
                <div class="flex gap-4">
                    <label v-for="opt in [['draft','Draft'],['finalized','Finalized'],['active_auto','Active for Auto-Run']]" :key="opt[0]" class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="radio" v-model="configStatus" :value="opt[0]" class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        {{ opt[1] }}
                    </label>
                </div>
                <p class="text-xs text-gray-400 mt-1">Set to "Active for Auto-Run" for the artisan command <code class="font-mono bg-gray-100 px-1 rounded">app:run-auto-news-generation</code> to include this config.</p>
            </div>

            <!-- Prompt Preview (shown after estimation) -->
            <div v-if="promptPreview" class="bg-white rounded-lg border shadow-sm overflow-hidden">
                <button
                    @click="showPrompt = !showPrompt"
                    class="w-full flex items-center justify-between px-5 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                >
                    <span>Prompt Preview <span class="text-xs font-normal text-gray-400 ml-1">({{ tokenCount?.toLocaleString() }} tokens)</span></span>
                    <span class="text-gray-400 text-xs">{{ showPrompt ? '▲ hide' : '▼ show' }}</span>
                </button>
                <div v-if="showPrompt" class="border-t divide-y divide-gray-100">
                    <div class="px-5 py-3">
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">System Prompt</div>
                        <pre class="text-xs text-gray-700 whitespace-pre-wrap bg-gray-50 rounded p-3 max-h-48 overflow-y-auto font-mono">{{ promptPreview.system }}</pre>
                    </div>
                    <div class="px-5 py-3">
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">User Prompt</div>
                        <pre class="text-xs text-gray-700 whitespace-pre-wrap bg-gray-50 rounded p-3 max-h-96 overflow-y-auto font-mono">{{ promptPreview.user }}</pre>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="sticky bottom-4 bg-white border rounded-lg shadow-lg p-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <!-- Estimate Tokens -->
                    <button @click="estimateTokens" :disabled="tokenBusy" class="px-4 py-2 text-sm font-medium rounded-md border border-gray-300 hover:bg-gray-50 disabled:opacity-50 transition-colors">
                        {{ tokenBusy ? 'Estimating…' : 'Estimate Tokens' }}
                    </button>
                    <span v-if="tokenCount !== null" class="text-sm font-semibold text-gray-700">{{ tokenCount.toLocaleString() }} tokens</span>
                    <span v-if="tokenError" class="text-xs text-red-600">Error: {{ tokenError }}</span>
                </div>

                <div class="flex items-center gap-3">
                    <span v-if="saveMsg" class="text-xs" :class="saveMsgOk ? 'text-green-600' : 'text-red-600'">{{ saveMsg }}</span>
                    <span v-if="generateMsg" class="text-xs" :class="generateOk ? 'text-green-600' : 'text-red-600'">{{ generateMsg }}</span>
                    <button @click="saveConfig" :disabled="saving" class="px-4 py-2 text-sm font-medium rounded-md bg-white border border-gray-300 hover:bg-gray-50 disabled:opacity-50 transition-colors">
                        {{ saving ? 'Saving…' : 'Save Configuration' }}
                    </button>
                    <button @click="saveAndGenerate" :disabled="saving || generating" class="px-4 py-2 text-sm font-medium rounded-md bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                        {{ generating ? 'Queuing…' : 'Save & Generate Now' }}
                    </button>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
