<script setup>
import PageTemplate from '@/Components/PageTemplate.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, Link, useForm } from '@inertiajs/vue3'; // Import useForm
import { computed, inject, ref } from 'vue';
import InputError from '@/Components/InputError.vue'; // For displaying form errors
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    mustVerifyEmail: Boolean,
    status: String,
    subscriptionsList: Array,
    socialLoginDetails: Object,
    errors: Object, // Inertia passes validation errors here
    status_error: String, // Custom prop for redeem code error message if not using errors bag
});

const translations = inject('translations');
const language_codes = ref(['en-US']);

const redeemForm = useForm({ // Form for redeeming code
    redeem_code: '',
});

const submitRedeemCode = () => {
    redeemForm.post(route('profile.redeemCode'), {
        preserveScroll: true,
        onSuccess: () => {
            redeemForm.reset('redeem_code');
            // props.status will be updated by the backend redirect's flash message
        },
        onError: () => {
            // Errors are automatically populated in props.errors or redeemForm.errors
            // If redeemForm.errors.redeem_code is not showing, ensure backend sends it correctly
        }
    });
};

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
    if (status === 'active_manual') { // New case for manually assigned active plans
        return translations.LabelsByLanguageCode[langCode]?.statusActiveManual || 'Active (Manual)';
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
        statusActiveManual: 'Active (Manually Assigned)', // New translation

        freeTierTitle: 'Registered User Features (Free)',
        basicPlanTitle: 'Resident Awareness',
        proPlanTitle: 'Pro Insights',
        redeemCodeTitle: 'Redeem Subscription Code',
        redeemCodeInputLabel: 'Enter Code',
        redeemCodeButton: 'Redeem Code',
        redeemCodeSuccess: 'Code redeemed successfully!', // Generic, backend provides specific
        redeemCodeError: 'Invalid or expired redemption code.', // Generic, backend provides specific
    };
}

const hasActivePaidSubscription = computed(() => {
    if (!props.subscriptionsList) return false;
    // Show "Manage Billing" only for active Stripe subscriptions
    return props.subscriptionsList.some(sub =>
        sub.source === 'stripe' &&
        (sub.isActive || sub.isOnGracePeriod || sub.status === 'past_due' || sub.status === 'incomplete')
    );
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
                <!-- Display general status messages (e.g., profile updated, code redeemed successfully) -->
                <div v-if="status && !status_error && !redeemForm.hasErrors" class="mb-4 font-medium text-sm text-green-600 bg-green-100 p-3 rounded-md">
                    {{ status }}
                </div>
                <!-- Display redeem code specific error from status_error (if backend sets it) -->
                 <div v-if="status_error" class="mb-4 font-medium text-sm text-red-600 bg-red-100 p-3 rounded-md">
                    {{ status_error }}
                </div>


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
                                <!-- ... existing subscription details display ... -->
                                <div class="space-y-2">
                                    <div>
                                        <span class="font-medium text-gray-700">{{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.profileSubscriptionNameLabel || 'Subscription Type' }}:</span>
                                        <!-- subscription.name will be 'manual', 'stripe', or 'default' -->
                                        <span class="ml-2 text-gray-600 capitalize">{{ subscription.name.replace('_', ' ') }}
                                            <span v-if="subscription.source === 'manual'" class="text-sm text-gray-500">({{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.sourceManual || 'Manual' }})</span>
                                            <span v-else-if="subscription.source === 'stripe'" class="text-sm text-gray-500">({{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.sourceStripe || 'Stripe' }})</span>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700">{{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.profileCurrentPlanLabel || 'Plan' }}:</span>
                                        <!-- getPlanDisplayName will use subscription.planName for manual plans, which is descriptive -->
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

                    <!-- Redeem Code Section -->
                    <section class="mt-8 pt-6 border-t">
                        <header>
                             <h2 class="text-lg font-medium text-gray-900">
                                {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.redeemCodeTitle || 'Redeem Subscription Code' }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-600">
                                If you have a redemption code, enter it here to update your subscription.
                            </p>
                        </header>

                        <form @submit.prevent="submitRedeemCode" class="mt-6 space-y-6 max-w-xl">
                            <div>
                                <InputLabel for="redeem_code" :value="translations.LabelsByLanguageCode[getSingleLanguageCode]?.redeemCodeInputLabel || 'Enter Code'" />
                                <TextInput
                                    id="redeem_code"
                                    v-model="redeemForm.redeem_code"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    autocomplete="off"
                                />
                                <InputError :message="redeemForm.errors.redeem_code || (errors && errors.redeem_code ? errors.redeem_code[0] : '')" class="mt-2" />

                            </div>

                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="redeemForm.processing">
                                    {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.redeemCodeButton || 'Redeem Code' }}
                                </PrimaryButton>

                                <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                                    <p v-if="redeemForm.recentlySuccessful" class="text-sm text-gray-600">
                                        {{ translations.LabelsByLanguageCode[getSingleLanguageCode]?.redeemCodeSuccess || 'Code redeemed successfully!' }}
                                    </p>
                                </Transition>
                            </div>
                        </form>
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
