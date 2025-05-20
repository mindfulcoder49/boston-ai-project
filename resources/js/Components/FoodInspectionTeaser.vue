<template>
  <div v-if="(isAuthenticated && hasDetailedFoodData) || (!isAuthenticated && hasTeaserData)" 
       class="my-8 rounded-lg shadow-lg border"
       :class="{
         'bg-green-50 border-green-200': isAuthenticated,
         'bg-sky-50 border-sky-200': !isAuthenticated
       }">

    <!-- Collapsible Header -->
    <div @click="toggleCollapse" 
         class="p-4 cursor-pointer flex justify-between items-center"
         :class="{
           'text-green-800 hover:bg-green-100': isAuthenticated,
           'text-sky-800 hover:bg-sky-100': !isAuthenticated
         }">
      <div class="font-bold text-lg">
        <template v-if="!isAuthenticated">
          <span v-if="mostSevereGroupForTeaser" class="text-amber-800">
            ⚠️ {{ LabelsByLanguageCode[getSingleLanguageCode].guestCollapsedTitle(totalViolationsForTeaser, mostSevereGroupForTeaser.count, mostSevereGroupForTeaser.severityLabel) }}
          </span>
          <span v-else>{{ LabelsByLanguageCode[getSingleLanguageCode].guestCollapsedTitleDefault }}</span>
        </template>
        <template v-else>
          {{ LabelsByLanguageCode[getSingleLanguageCode].loggedInCollapsedTitle }}
        </template>
      </div>
      <svg class="w-6 h-6 transition-transform duration-300" :class="{'rotate-180': !isCollapsed}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
    </div>

    <!-- Collapsible Content -->
    <div v-if="!isCollapsed" class="p-6 border-t"
         :class="{
           'border-green-200': isAuthenticated,
           'border-sky-200': !isAuthenticated
         }">
      <!-- Authenticated User View -->
      <div v-if="isAuthenticated">
        <h3 class="text-3xl font-bold mb-3 tracking-tight text-green-800">{{ LabelsByLanguageCode[getSingleLanguageCode].loggedInTitle }}</h3>
        <p class="text-lg mb-5 text-green-700">{{ LabelsByLanguageCode[getSingleLanguageCode].loggedInSubtitle }}</p>

        <div v-for="item in detailedFoodInspections" :key="item.id || item.licenseno + item.alcivartech_date" class="mb-5 p-4 bg-white text-gray-800 rounded-md shadow">
          <h4 class="text-xl font-semibold text-green-700 mb-1">{{ item.businessname || LabelsByLanguageCode[getSingleLanguageCode].unknownEstablishment }}</h4>
          <p class="text-sm text-gray-500 mb-2">{{ LabelsByLanguageCode[getSingleLanguageCode].addressLabel }}: {{ item.address || 'N/A' }}</p>
          <p class="text-sm text-gray-600 mb-2">
            <strong>{{ item._is_aggregated_food_violation ? LabelsByLanguageCode[getSingleLanguageCode].mostRecentActivityDate : (item.violdttm ? LabelsByLanguageCode[getSingleLanguageCode].violationDate : LabelsByLanguageCode[getSingleLanguageCode].inspectionDate) }}:</strong>
            {{ formatDate(item.alcivartech_date) }}
          </p>

          <div v-if="item._is_aggregated_food_violation && item.violation_summary">
            <p class="text-sm font-medium mt-2 mb-1">{{ LabelsByLanguageCode[getSingleLanguageCode].summaryOfFindings }}:</p>
            <ul class="list-disc list-inside ml-4 text-xs space-y-1">
              <li v-for="summary in item.violation_summary" :key="summary.violdesc">
                <strong>{{ summary.violdesc }}</strong> ({{ summary.entries.length }} {{ summary.entries.length === 1 ? LabelsByLanguageCode[getSingleLanguageCode].recordSingular : LabelsByLanguageCode[getSingleLanguageCode].recordPlural }}):
                <ul class="list-circle list-inside ml-4">
                  <li v-for="entry in summary.entries.slice(0,2)" :key="entry.alcivartech_date + entry.comments">
                    {{ formatDate(entry.alcivartech_date) }}: {{ entry.viol_status || entry.result || 'N/A' }}. {{ LabelsByLanguageCode[getSingleLanguageCode].levelLabel }}: {{entry.viol_level || 'N/A'}}. "{{ truncateComment(entry.comments, 40) }}"
                  </li>
                  <li v-if="summary.entries.length > 2">... {{ LabelsByLanguageCode[getSingleLanguageCode].andXMore(summary.entries.length - 2) }}</li>
                </ul>
              </li>
            </ul>
          </div>
          <div v-else-if="item.violdttm">
            <p class="text-sm mt-2"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].violationDescLabel }}:</strong> {{ item.violdesc || 'N/A' }}</p>
            <p class="text-sm"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].levelLabel }}:</strong> {{ item.viol_level || 'N/A' }}</p>
            <p class="text-sm"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].statusLabel }}:</strong> {{ item.viol_status || item.result || 'N/A' }}</p>
            <p class="text-sm italic"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].commentsLabel }}:</strong> "{{ item.comments || 'N/A' }}"</p>
          </div>
          <div v-else>
            <p class="text-sm mt-2"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].inspectionResultLabel }}:</strong> {{ item.result || 'N/A' }}</p>
            <p class="text-sm italic"><strong>{{ LabelsByLanguageCode[getSingleLanguageCode].commentsLabel }}:</strong> "{{ item.comments || 'N/A' }}"</p>
          </div>
        </div>
        <p v-if="detailedFoodInspections.length === 0" class="text-center text-gray-600 py-4">{{ LabelsByLanguageCode[getSingleLanguageCode].noFoodInspectionData }}</p>
      </div>

      <!-- Guest User Teaser View -->
      <div v-else>
        <h3 class="text-3xl font-bold mb-3 tracking-tight text-sky-800">{{ LabelsByLanguageCode[getSingleLanguageCode].teaserTitle }}</h3>
        <p class="text-lg mb-5 text-sky-700">{{ LabelsByLanguageCode[getSingleLanguageCode].teaserSubtitle }}</p>

        <div v-for="group in sortedViolationGroups" :key="group.severityLabel" class="mb-5 p-4 bg-white text-gray-800 rounded-md shadow">
          <p class="text-xl font-bold mb-2">
            <span :class="{
              'text-amber-600': group.order === severityMap['***'].order, 
              'text-sky-600': group.order !== severityMap['***'].order
            }">{{ group.count }}</span>
            {{ group.severityLabel }} {{ LabelsByLanguageCode[getSingleLanguageCode].alerts }}
          </p>
          <p class="text-sm text-gray-600 mb-2">{{ LabelsByLanguageCode[getSingleLanguageCode].inspectorsNoted }}</p>
          <ul v-if="group.comments.length > 0" class="list-disc list-inside ml-4 text-sm italic text-gray-700 space-y-1">
            <li v-for="(comment, index) in group.comments.slice(0, 2)" :key="index">
              "...{{ truncateComment(comment, 60) }}..."
            </li>
          </ul>
        </div>

        <div class="mt-8 pt-6 border-t border-sky-200 text-center">
          <p class="text-xl font-semibold mb-4 text-sky-800">{{ LabelsByLanguageCode[getSingleLanguageCode].ctaTitle }}</p>
          
          <p class="text-md mb-4 text-sky-700 font-medium">{{ LabelsByLanguageCode[getSingleLanguageCode].guestUnlockDetailsPrompt }}</p>

          <a :href="route('socialite.redirect', 'google') + '?redirect_to=' + route('map.index')"
            class="flex items-center justify-center w-full max-w-xs mx-auto mb-4 px-6 py-3 border border-gray-300 rounded-md shadow-sm text-base font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
            <img class="h-5 w-5 mr-2" src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google logo">
            {{ LabelsByLanguageCode[getSingleLanguageCode].googleLoginButton }}
          </a>

          <p class="text-sm mb-2 text-sky-700">{{ LabelsByLanguageCode[getSingleLanguageCode].dataAccessInfoFree }}</p>
          <p class="text-sm mb-4 text-sky-700">{{ LabelsByLanguageCode[getSingleLanguageCode].dataAccessInfoPremium }}</p>

          <Link :href="route('subscription.index')" 
                class="inline-block bg-sky-600 hover:bg-sky-700 text-white font-bold py-3 px-8 rounded-lg text-lg shadow-md transition-colors">
            {{ LabelsByLanguageCode[getSingleLanguageCode].viewPlansButton }}
          </Link>
          
          <p class="mt-4 text-xs text-sky-600">
            {{ LabelsByLanguageCode[getSingleLanguageCode].manualLoginPrompt }}
            <Link :href="route('login')" class="hover:underline font-medium">{{ LabelsByLanguageCode[getSingleLanguageCode].manualLoginLink }}</Link> / 
            <Link :href="route('register')" class="hover:underline font-medium">{{ LabelsByLanguageCode[getSingleLanguageCode].manualRegisterLink }}</Link>.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, defineProps, ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  dataPoints: {
    type: Array,
    required: true,
  },
  language_codes: {
    type: Array,
    default: () => ['en-US'],
  },
  isAuthenticated: {
    type: Boolean,
    default: false,
  }
});

const isCollapsed = ref(true);

const toggleCollapse = () => {
  isCollapsed.value = !isCollapsed.value;
};

const severityMap = {
  '***': { labelKey: 'highSeverity', order: 1 },
  '**': { labelKey: 'moderateSeverity', order: 2 },
  '*': { labelKey: 'lowSeverity', order: 3 },
  'Unspecified': { labelKey: 'unspecifiedSeverity', order: 4 },
};

const LabelsByLanguageCode = {
  'en-US': {
    teaserTitle: "Food Safety In Your Neighborhood: Know Before You Go.",
    teaserSubtitle: "Your health and your family's well-being are paramount. We provide transparent access to recent food inspection findings. This is a glimpse – log in or register for FREE to see full details, including establishment names and locations.",
    alerts: "Key Findings Reported",
    inspectorsNoted: "Recent inspector observations include:",
    ctaTitle: "Unlock Full Food Safety Transparency – It's Your Right:",
    googleLoginButton: "Login / Register with Google (Free)",
    dataAccessInfoFree: "Access the latest 2 weeks of detailed food safety reports for FREE upon registration.",
    dataAccessInfoPremium: "Need more? Our affordable plans unlock 6 months of data or ALL available historical reports.",
    viewPlansButton: "View Subscription Plans",
    manualLoginPrompt: "Prefer to ",
    manualLoginLink: "Login",
    manualRegisterLink: "Register",
    guestUnlockDetailsPrompt: "Log in or register (it's free!) to see the names and locations of these establishments and access detailed reports.",
    highSeverity: "High Concern",
    moderateSeverity: "Moderate Concern",
    lowSeverity: "Low Concern",
    unspecifiedSeverity: "Unspecified Concern",
    // Logged-in user labels
    loggedInTitle: "Your Local Food Safety Report",
    loggedInSubtitle: "Thank you for logging in! Here are the recent food inspection details for your selected area:",
    unknownEstablishment: "Unknown Establishment",
    addressLabel: "Address",
    mostRecentActivityDate: "Most Recent Activity",
    violationDate: "Violation Date",
    inspectionDate: "Inspection Date",
    summaryOfFindings: "Summary of Findings",
    recordSingular: "record",
    recordPlural: "records",
    andXMore: (count) => `and ${count} more.`,
    violationDescLabel: "Violation",
    levelLabel: "Level",
    statusLabel: "Status/Result",
    commentsLabel: "Inspector Comments",
    inspectionResultLabel: "Inspection Result",
    noFoodInspectionData: "No food inspection data available for this area in the current filter.",
    guestCollapsedTitle: (total, severeCount, severeCategory) => `EMPOWER YOUR CHOICES: ${total} Food Safety Reports Nearby, Including ${severeCount} ${severeCategory} Findings. Click to Uncover Details.`,
    guestCollapsedTitleDefault: "Local Food Safety: Click to See Critical Insights",
    loggedInCollapsedTitle: "View Your Local Food Safety Report",
  },
  // Add other languages with similar structure
  'es-MX': {
    teaserTitle: "Seguridad Alimentaria en Tu Vecindario: Infórmate Antes de Ir.",
    teaserSubtitle: "Tu salud y el bienestar de tu familia son primordiales. Proporcionamos acceso transparente a hallazgos recientes de inspección de alimentos. Esto es un vistazo – inicia sesión o regístrate GRATIS para ver todos los detalles, incluyendo nombres y ubicaciones de establecimientos.",
    alerts: "Hallazgos Clave Reportados",
    inspectorsNoted: "Observaciones recientes de inspectores incluyen:",
    ctaTitle: "Desbloquea la Transparencia Total en Seguridad Alimentaria – Es Tu Derecho:",
    googleLoginButton: "Iniciar Sesión / Registrarse con Google (Gratis)",
    dataAccessInfoFree: "Accede GRATIS a los informes detallados de seguridad alimentaria de las últimas 2 semanas al registrarte.",
    dataAccessInfoPremium: "¿Necesitas más? Nuestros planes asequibles desbloquean 6 meses de datos o TODOS los informes históricos disponibles.",
    viewPlansButton: "Ver Planes de Suscripción",
    manualLoginPrompt: "Prefieres ",
    manualLoginLink: "Iniciar Sesión",
    manualRegisterLink: "Registrarte",
    guestUnlockDetailsPrompt: "Inicia sesión o regístrate (¡es gratis!) para ver los nombres y ubicaciones de estos establecimientos y acceder a informes detallados.",
    highSeverity: "Preocupación Alta",
    moderateSeverity: "Preocupación Moderada",
    lowSeverity: "Preocupación Baja",
    unspecifiedSeverity: "Preocupación no Especificada",
    loggedInTitle: "Tu Informe Local de Seguridad Alimentaria",
    loggedInSubtitle: "¡Gracias por iniciar sesión! Aquí están los detalles recientes de inspección de alimentos para tu área seleccionada:",
    unknownEstablishment: "Establecimiento Desconocido",
    addressLabel: "Dirección",
    mostRecentActivityDate: "Actividad Más Reciente",
    violationDate: "Fecha de Violación",
    inspectionDate: "Fecha de Inspección",
    summaryOfFindings: "Resumen de Hallazgos",
    recordSingular: "registro",
    recordPlural: "registros",
    andXMore: (count) => `y ${count} más.`,
    violationDescLabel: "Violación",
    levelLabel: "Nivel",
    statusLabel: "Estado/Resultado",
    commentsLabel: "Comentarios del Inspector",
    inspectionResultLabel: "Resultado de Inspección",
    noFoodInspectionData: "No hay datos de inspección de alimentos disponibles para esta área en el filtro actual.",
    guestCollapsedTitle: (total, severeCount, severeCategory) => `EMPODERA TUS DECISIONES: ${total} Informes de Seguridad Alimentaria Cercanos, Incluyendo ${severeCount} Hallazgos de ${severeCategory}. Haz Clic para Descubrir Detalles.`,
    guestCollapsedTitleDefault: "Seguridad Alimentaria Local: Clic para Ver Información Crítica",
    loggedInCollapsedTitle: "Ver Tu Informe Local de Seguridad Alimentaria",
  },
    'zh-CN': {
    teaserTitle: "您社区的食品安全：出行前请了解。",
    teaserSubtitle: "您和您家人的健康至关重要。我们提供对近期食品检查结果的透明访问。这只是概览 – 免费登录或注册即可查看完整详情，包括机构名称和位置。",
    alerts: "报告的主要发现",
    inspectorsNoted: "近期检查员的观察包括：",
    ctaTitle: "解锁全面的食品安全透明度 – 这是您的权利：",
    googleLoginButton: "使用Google登录/注册 (免费)",
    dataAccessInfoFree: "注册后免费访问最近两周的详细食品安全报告。",
    dataAccessInfoPremium: "需要更多？我们经济实惠的计划可解锁6个月的数据或所有可用的历史报告。",
    viewPlansButton: "查看订阅计划",
    manualLoginPrompt: "或者 ",
    manualLoginLink: "登录",
    manualRegisterLink: "注册",
    guestUnlockDetailsPrompt: "登录或注册（免费！），即可查看这些机构的名称和位置，并访问详细报告。",
    highSeverity: "高度关注",
    moderateSeverity: "中度关注",
    lowSeverity: "低度关注",
    unspecifiedSeverity: "未指明关注度",
    loggedInTitle: "您当地的食品安全报告",
    loggedInSubtitle: "感谢您的登录！以下是您所选区域最近的食品检查详细信息：",
    unknownEstablishment: "未知机构",
    addressLabel: "地址",
    mostRecentActivityDate: "最近活动日期",
    violationDate: "违规日期",
    inspectionDate: "检查日期",
    summaryOfFindings: "调查结果摘要",
    recordSingular: "条记录",
    recordPlural: "条记录",
    andXMore: (count) => `及另外 ${count} 条。`,
    violationDescLabel: "违规行为",
    levelLabel: "级别",
    statusLabel: "状态/结果",
    commentsLabel: "检查员评论",
    inspectionResultLabel: "检查结果",
    noFoodInspectionData: "当前筛选条件下，该区域无食品检查数据。",
    guestCollapsedTitle: (total, severeCount, severeCategory) => `自主选择，保障健康：附近共有 ${total} 份食品安全报告，其中包括 ${severeCount} 项${severeCategory}发现。点击了解详情。`,
    guestCollapsedTitleDefault: "本地食品安全：点击查看关键信息",
    loggedInCollapsedTitle: "查看您当地的食品安全报告",
  },
  'ht-HT': {
    teaserTitle: "Sekirite Manje nan Kominote Ou: Konnen Anvan Ou Ale.",
    teaserSubtitle: "Sante ou ak byennèt fanmi ou esansyèl. Nou bay aksè transparan a dènye rezilta enspeksyon manje. Sa a se yon apèsi – konekte oswa enskri GRATIS pou wè tout detay, enkli non etablisman ak kote yo ye.",
    alerts: "Konklizyon Kle Yo Rapòte",
    inspectorsNoted: "Obsèvasyon enspektè ki fèk fèt gen ladan:",
    ctaTitle: "Debloke Transparans Total nan Sekirite Manje – Se Dwa Ou:",
    googleLoginButton: "Konekte / Enskri ak Google (Gratis)",
    dataAccessInfoFree: "Aksede GRATIS dènye 2 semèn rapò detaye sou sekirite manje lè ou enskri.",
    dataAccessInfoPremium: "Ou bezwen plis? Plan abòdab nou yo debloke 6 mwa done oswa TOUT rapò istorik ki disponib.",
    viewPlansButton: "Gade Plan Abònman",
    manualLoginPrompt: "Prefere ",
    manualLoginLink: "Konekte",
    manualRegisterLink: "Enskri",
    guestUnlockDetailsPrompt: "Konekte oswa enskri (li gratis!) pou wè non ak kote etablisman sa yo epitou pou aksede rapò detaye.",
    highSeverity: "Gwo Enkyetid",
    moderateSeverity: "Enkyetid Modere",
    lowSeverity: "Ti Enkyetid",
    unspecifiedSeverity: "Enkyetid Pa Espesifye",
    loggedInTitle: "Rapò Sekirite Manje Lokal Ou",
    loggedInSubtitle: "Mèsi dèske ou konekte! Men detay enspeksyon manje ki fèk fèt pou zòn ou chwazi a:",
    unknownEstablishment: "Etablisman Enkoni",
    addressLabel: "Adrès",
    mostRecentActivityDate: "Aktivite Pi Resan",
    violationDate: "Dat Vyolasyon",
    inspectionDate: "Dat Enspeksyon",
    summaryOfFindings: "Rezime Konklizyon",
    recordSingular: "dosye",
    recordPlural: "dosye yo",
    andXMore: (count) => `ak ${count} lòt.`,
    violationDescLabel: "Vyolasyon",
    levelLabel: "Nivo",
    statusLabel: "Estati/Rezilta",
    commentsLabel: "Kòmantè Enspektè",
    inspectionResultLabel: "Rezilta Enspeksyon",
    noFoodInspectionData: "Pa gen done enspeksyon manje ki disponib pou zòn sa a nan filtè aktyèl la.",
    guestCollapsedTitle: (total, severeCount, severeCategory) => `POUVWA CHWA OU: ${total} Rapò Sekirite Manje Toupre, Ak ${severeCount} Konklizyon ${severeCategory}. Klike pou Dekouvri Detay.`,
    guestCollapsedTitleDefault: "Sekirite Manje Lokal: Klike pou Wè Enfòmasyon Kritik",
    loggedInCollapsedTitle: "Gade Rapò Sekirite Manje Lokal Ou",
  },
  'pt-BR': {
    teaserTitle: "Segurança Alimentar na Sua Vizinhança: Saiba Antes de Ir.",
    teaserSubtitle: "Sua saúde e o bem-estar de sua família são primordiais. Oferecemos acesso transparente aos resultados recentes de inspeção de alimentos. Isto é uma prévia – faça login ou registre-se GRATUITAMENTE para ver todos os detalhes, incluindo nomes e locais dos estabelecimentos.",
    alerts: "Principais Descobertas Relatadas",
    inspectorsNoted: "Observações recentes de inspetores incluem:",
    ctaTitle: "Desbloqueie a Transparência Total em Segurança Alimentar – É Seu Direito:",
    googleLoginButton: "Login / Registrar com Google (Grátis)",
    dataAccessInfoFree: "Acesse GRATUITAMENTE os relatórios detalhados de segurança alimentar das últimas 2 semanas ao se registrar.",
    dataAccessInfoPremium: "Precisa de mais? Nossos planos acessíveis desbloqueiam 6 meses de dados ou TODOS os relatórios históricos disponíveis.",
    viewPlansButton: "Ver Planos de Assinatura",
    manualLoginPrompt: "Prefere ",
    manualLoginLink: "Login",
    manualRegisterLink: "Registrar",
    guestUnlockDetailsPrompt: "Faça login ou registre-se (é grátis!) para ver os nomes e locais desses estabelecimentos e acessar relatórios detalhados.",
    highSeverity: "Alta Preocupação",
    moderateSeverity: "Preocupação Moderada",
    lowSeverity: "Baixa Preocupação",
    unspecifiedSeverity: "Preocupação Não Especificada",
    loggedInTitle: "Seu Relatório Local de Segurança Alimentar",
    loggedInSubtitle: "Obrigado por fazer login! Aqui estão os detalhes recentes de inspeção de alimentos para a área selecionada:",
    unknownEstablishment: "Estabelecimento Desconhecido",
    addressLabel: "Endereço",
    mostRecentActivityDate: "Atividade Mais Recente",
    violationDate: "Data da Violação",
    inspectionDate: "Data da Inspeção",
    summaryOfFindings: "Resumo das Descobertas",
    recordSingular: "registro",
    recordPlural: "registros",
    andXMore: (count) => `e mais ${count}.`,
    violationDescLabel: "Violação",
    levelLabel: "Nível",
    statusLabel: "Status/Resultado",
    commentsLabel: "Comentários do Inspetor",
    inspectionResultLabel: "Resultado da Inspeção",
    noFoodInspectionData: "Nenhum dado de inspeção alimentar disponível para esta área no filtro atual.",
    guestCollapsedTitle: (total, severeCount, severeCategory) => `CAPACITE SUAS ESCOLHAS: ${total} Relatórios de Segurança Alimentar Próximos, Incluindo ${severeCount} Descobertas de ${severeCategory}. Clique para Descobrir Detalhes.`,
    guestCollapsedTitleDefault: "Segurança Alimentar Local: Clique para Ver Informações Críticas",
    loggedInCollapsedTitle: "Ver Seu Relatório Local de Segurança Alimentar",
  },
  'vi-VN': {
    teaserTitle: "An Toàn Thực Phẩm Trong Khu Phố Của Bạn: Biết Trước Khi Đi.",
    teaserSubtitle: "Sức khỏe của bạn và gia đình là vô cùng quan trọng. Chúng tôi cung cấp quyền truy cập minh bạch vào các kết quả kiểm tra thực phẩm gần đây. Đây chỉ là một cái nhìn sơ lược – đăng nhập hoặc đăng ký MIỄN PHÍ để xem chi tiết đầy đủ, bao gồm tên và địa điểm của các cơ sở.",
    alerts: "Các Phát Hiện Chính Được Báo Cáo",
    inspectorsNoted: "Các quan sát gần đây của thanh tra bao gồm:",
    ctaTitle: "Mở Khóa Hoàn Toàn Thông Tin Minh Bạch Về An Toàn Thực Phẩm – Đó Là Quyền Của Bạn:",
    googleLoginButton: "Đăng nhập / Đăng ký bằng Google (Miễn phí)",
    dataAccessInfoFree: "Truy cập MIỄN PHÍ các báo cáo an toàn thực phẩm chi tiết trong 2 tuần gần nhất khi đăng ký.",
    dataAccessInfoPremium: "Cần thêm? Các gói cước phải chăng của chúng tôi mở khóa dữ liệu 6 tháng hoặc TẤT CẢ các báo cáo lịch sử có sẵn.",
    viewPlansButton: "Xem Các Gói Đăng Ký",
    manualLoginPrompt: "Hoặc ",
    manualLoginLink: "Đăng nhập",
    manualRegisterLink: "Đăng ký",
    guestUnlockDetailsPrompt: "Đăng nhập hoặc đăng ký (miễn phí!) để xem tên và địa điểm của các cơ sở này và truy cập các báo cáo chi tiết.",
    highSeverity: "Mối Quan Ngại Cao",
    moderateSeverity: "Mối Quan Ngại Vừa",
    lowSeverity: "Mối Quan Ngại Thấp",
    unspecifiedSeverity: "Mối Quan Ngại Không Xác Định",
    loggedInTitle: "Báo cáo An toàn Thực phẩm Địa phương của Bạn",
    loggedInSubtitle: "Cảm ơn bạn đã đăng nhập! Dưới đây là chi tiết kiểm tra thực phẩm gần đây cho khu vực bạn đã chọn:",
    unknownEstablishment: "Cơ sở không xác định",
    addressLabel: "Địa chỉ",
    mostRecentActivityDate: "Hoạt động gần nhất",
    violationDate: "Ngày vi phạm",
    inspectionDate: "Ngày kiểm tra",
    summaryOfFindings: "Tóm tắt Kết quả",
    recordSingular: "hồ sơ",
    recordPlural: "hồ sơ",
    andXMore: (count) => `và ${count} mục khác.`,
    violationDescLabel: "Vi phạm",
    levelLabel: "Cấp độ",
    statusLabel: "Trạng thái/Kết quả",
    commentsLabel: "Nhận xét của Thanh tra",
    inspectionResultLabel: "Kết quả kiểm tra",
    noFoodInspectionData: "Không có dữ liệu kiểm tra thực phẩm cho khu vực này trong bộ lọc hiện tại.",
    guestCollapsedTitle: (total, severeCount, severeCategory) => `NÂNG CAO LỰA CHỌN CỦA BẠN: ${total} Báo Cáo An Toàn Thực Phẩm Gần Đây, Bao Gồm ${severeCount} Phát Hiện ${severeCategory}. Nhấp để Khám Phá Chi Tiết.`,
    guestCollapsedTitleDefault: "An Toàn Thực Phẩm Địa Phương: Nhấp để Xem Thông Tin Quan Trọng",
    loggedInCollapsedTitle: "Xem Báo cáo An toàn Thực phẩm Địa phương của Bạn",
  }
};

const getSingleLanguageCode = computed(() => {
  if (props.language_codes && props.language_codes.length > 0 && LabelsByLanguageCode[props.language_codes[0]]) {
    return props.language_codes[0];
  }
  return 'en-US';
});

const foodInspectionViolationsForTeaser = computed(() => {
  // This is the existing logic for the teaser (non-logged-in users)
  const violations = [];
  props.dataPoints.forEach(dp => {
    if (dp.alcivartech_type === 'Food Inspection') {
      if (dp._is_aggregated_food_violation && dp.violation_summary) {
        dp.violation_summary.forEach(summaryItem => {
          summaryItem.entries.forEach(entry => {
            if (entry.viol_level && entry.comments) {
              violations.push({
                level: entry.viol_level,
                comment: entry.comments,
              });
            }
          });
        });
      } else if (dp.violdttm && dp.viol_level && dp.comments) {
        violations.push({
          level: dp.viol_level,
          comment: dp.comments,
        });
      }
    }
  });
  return violations;
});

const groupedViolations = computed(() => {
  // For teaser
  const groups = {};
  const currentLabels = LabelsByLanguageCode[getSingleLanguageCode.value];
  foodInspectionViolationsForTeaser.value.forEach(violation => {
    const rawLevel = violation.level || 'Unspecified';
    const severityInfo = severityMap[rawLevel] || severityMap['Unspecified'];
    const severityLabel = currentLabels[severityInfo.labelKey];
    if (!groups[severityLabel]) {
      groups[severityLabel] = { count: 0, comments: [], order: severityInfo.order };
    }
    groups[severityLabel].count++;
    if (violation.comment && groups[severityLabel].comments.length < 5) {
      groups[severityLabel].comments.push(violation.comment);
    }
  });
  for (const key in groups) {
    groups[key].comments.sort(() => 0.5 - Math.random());
  }
  return groups;
});

const sortedViolationGroups = computed(() => {
  // For teaser
  return Object.entries(groupedViolations.value)
    .map(([severityLabel, data]) => ({ severityLabel, ...data }))
    .sort((a, b) => a.order - b.order);
});

const totalViolationsForTeaser = computed(() => {
  return foodInspectionViolationsForTeaser.value.length;
});

const mostSevereGroupForTeaser = computed(() => {
  if (sortedViolationGroups.value.length > 0) {
    const mostSevereRaw = sortedViolationGroups.value[0];
    return {
        count: mostSevereRaw.count,
        severityLabel: mostSevereRaw.severityLabel // This is already localized
    };
  }
  return null;
});


const hasTeaserData = computed(() => {
  // For teaser
  return foodInspectionViolationsForTeaser.value.length > 0 && Object.keys(groupedViolations.value).length > 0;
});

const detailedFoodInspections = computed(() => {
  if (!props.isAuthenticated) return [];
  return props.dataPoints.filter(dp => dp.alcivartech_type === 'Food Inspection')
    .sort((a,b) => new Date(b.alcivartech_date) - new Date(a.alcivartech_date)); // Show most recent first
});

const hasDetailedFoodData = computed(() => {
    return props.isAuthenticated && detailedFoodInspections.value.length > 0;
});

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return dateString;
  return date.toLocaleDateString(getSingleLanguageCode.value, { year: 'numeric', month: 'short', day: 'numeric' });
};

const truncateComment = (comment, maxLength) => {
  if (!comment) return '';
  return comment.length <= maxLength ? comment : comment.substring(0, maxLength).trim() + '...';
};

</script>

<style scoped>
/* Styles can be adjusted if needed */
.list-circle {
    list-style-type: circle;
}
.rotate-180 {
  transform: rotate(180deg);
}
</style>
