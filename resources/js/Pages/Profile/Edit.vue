<script setup>
import PageTemplate from '@/Components/PageTemplate.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, inject, ref } from 'vue';

const props = defineProps({
    mustVerifyEmail: Boolean,
    status: String,
    // currentPlanKey: String, // Removed, planKey is now per subscription
    subscriptionsList: Array, // Changed from subscriptionDetails: Object
    socialLoginDetails: Object,
});

const translations = inject('translations');
const language_codes = ref(['en-US']);

const getSingleLanguageCode = computed(() => {
    return (translations.LabelsByLanguageCode && translations.LabelsByLanguageCode[language_codes.value[0]]) ? language_codes.value[0] : 'en-US';
});

// Helper to get display name for a plan key
const getPlanDisplayName = (planKey, defaultName) => {
    const langCode = getSingleLanguageCode.value;
    if (planKey === 'basic') return translations.LabelsByLanguageCode[langCode]?.basicPlanTitle || defaultName;
    if (planKey === 'pro') return translations.LabelsByLanguageCode[langCode]?.proPlanTitle || defaultName;
    if (planKey === 'free') return translations.LabelsByLanguageCode[langCode]?.freeTierTitle || defaultName;
    return defaultName;
};

// Helper to get formatted status string
const getFormattedSubscriptionStatus = (subscription) => {
    const langCode = getSingleLanguageCode.value;
    if (!subscription) return '';
    const status = subscription.status;

    if (subscription.isOnTrial) {
        return translations.LabelsByLanguageCode[langCode]?.statusTrialing || 'Trialing';
    }
    if (subscription.isCancelled && subscription.isOnGracePeriod) {
        return translations.LabelsByLanguageCode[langCode]?.statusCanceledOnGrace(subscription.endsAt) || `Canceled (ends ${subscription.endsAt})`;
    }
    if (subscription.isCancelled) {
         return translations.LabelsByLanguageCode[langCode]?.statusCanceled || 'Canceled';
    }
    if (status === 'active') {
        return translations.LabelsByLanguageCode[langCode]?.statusActive || 'Active';
    }
    if (status === 'past_due') {
        return translations.LabelsByLanguageCode[langCode]?.statusPastDue || 'Past Due';
    }
    if (status === 'incomplete') {
        return translations.LabelsByLanguageCode[langCode]?.statusIncomplete || 'Incomplete';
    }
    if (status === 'free') {
        return translations.LabelsByLanguageCode[langCode]?.statusFree || 'Free Tier';
    }
    return status; // Fallback to raw status
};


// Add translations for this page (ensure they are loaded globally or as needed)
if (translations.LabelsByLanguageCode && translations.LabelsByLanguageCode['en-US']) {
    translations.LabelsByLanguageCode['en-US'] = {
        ...translations.LabelsByLanguageCode['en-US'],
        profileAccountInfoTitle: 'Account Information',
        profileSocialLoginLabel: 'Social Login',
        profileNoSocialLogin: 'Not linked to a social account.',
        profileSubscriptionsTitle: 'Your Subscriptions', // Pluralized
        profileCurrentPlanLabel: 'Plan', // Generic label for plan name
        profileSubscriptionNameLabel: 'Subscription Type',
        profileSubscriptionStatusLabel: 'Status',
        profileRenewsOnLabel: 'Renews on',
        profileEndsOnLabel: 'Ends on',
        profileTrialEndsOnLabel: 'Trial ends on',
        profileManageBillingButton: 'Manage Billing & Subscriptions', // Pluralized
        profileViewPlansButton: 'View Subscription Plans',

        statusActive: 'Active',
        statusTrialing: 'Trialing',
        statusPastDue: 'Past Due',
        statusCanceled: 'Canceled',
        statusCanceledOnGrace: (endDate) => `Canceled (access until ${endDate})`,
        statusIncomplete: 'Incomplete',
        statusFree: 'Free Tier',

        freeTierTitle: 'Registered User Features (Free)',
        basicPlanTitle: 'Resident Awareness',
        proPlanTitle: 'Pro Insights',
    };
}

const hasActivePaidSubscription = computed(() => {
    if (!props.subscriptionsList) return false;
    return props.subscriptionsList.some(sub => sub.isActive || sub.isOnGracePeriod || sub.status === 'past_due' || sub.status === 'incomplete');
});

</script>

<template>
    <Head title="Profile" />

    <PageTemplate>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Profile</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <!-- Account Information Section (remains the same) -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">
                                {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.profileAccountInfoTitle || 'Account Information' }}
                            </h2>
                        </header>
                        <div class="mt-4 space-y-3">
                            <div>
                                <span class="font-medium text-gray-700">{{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.profileSocialLoginLabel || 'Social Login' }}:</span>
                                <template v-if="socialLoginDetails && socialLoginDetails.providerName">
                                    <span class="ml-2 text-gray-600 capitalize">{{ socialLoginDetails.providerName }}</span>
                                    <img v-if="socialLoginDetails.providerAvatar" :src="socialLoginDetails.providerAvatar" alt="Avatar" class="inline-block h-8 w-8 rounded-full ml-2"/>
                                </template>
                                <span v-else class="ml-2 text-gray-600">{{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.profileNoSocialLogin || 'Not linked to a social account.' }}</span>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Subscription Information Section -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 ">
                                {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.profileSubscriptionsTitle || 'Your Subscriptions' }}
                            </h2>
                        </header>

                        <div v-if="subscriptionsList && subscriptionsList.length > 0">
                            <div v-for="(subscription, index) in subscriptionsList" :key="index" class="mt-4 pt-4 border-t first:border-t-0 first:pt-0">
                                <div class="space-y-2">
                                    <div>
                                        <span class="font-medium text-gray-700">{{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.profileSubscriptionNameLabel || 'Subscription Type' }}:</span>
                                        <span class="ml-2 text-gray-600 capitalize">{{ subscription.name.replace('_', ' ') }}</span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700">{{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.profileCurrentPlanLabel || 'Plan' }}:</span>
                                        <span class="ml-2 text-gray-600">{{ getPlanDisplayName(subscription.planKey, subscription.planName) }}</span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700">{{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.profileSubscriptionStatusLabel || 'Status' }}:</span>
                                        <span class="ml-2 text-gray-600">{{ getFormattedSubscriptionStatus(subscription) }}</span>
                                    </div>
                                    <div v-if="subscription.isOnTrial && subscription.trialEndsAt">
                                        <span class="font-medium text-gray-700">{{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.profileTrialEndsOnLabel || 'Trial ends on' }}:</span>
                                        <span class="ml-2 text-gray-600">{{ subscription.trialEndsAt }}</span>
                                    </div>
                                    <div v-if="subscription.isActive && !subscription.isOnTrial && !subscription.isCancelled && subscription.currentPeriodEnd">
                                        <span class="font-medium text-gray-700">{{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.profileRenewsOnLabel || 'Renews on' }}:</span>
                                        <span class="ml-2 text-gray-600">{{ subscription.currentPeriodEnd }}</span>
                                    </div>
                                    <!-- EndsAt for cancelled on grace period is handled by getFormattedSubscriptionStatus -->
                                </div>
                            </div>
                        </div>
                        <div v-else class="mt-4 text-gray-600">
                            No subscription details found.
                        </div>

                        <div class="mt-6 space-y-4 md:space-y-0 md:flex md:space-x-4">
                            <Link
                                v-if="hasActivePaidSubscription"
                                :href="route('billing')"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.profileManageBillingButton || 'Manage Billing & Subscriptions' }}
                            </Link>
                            <Link
                                :href="route('subscription.index')"
                                class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.profileViewPlansButton || 'View Subscription Plans' }}
                            </Link>
                        </div>
                    </section>
                </div>

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <UpdatePasswordForm class="max-w-xl" :has-provider="!!(socialLoginDetails && socialLoginDetails.providerName)" />
                </div>

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <DeleteUserForm class="max-w-xl" :has-provider="!!(socialLoginDetails && socialLoginDetails.providerName)" />
                </div>
            </div>
        </div>
    </PageTemplate>
</template>
