<template>
    <PageTemplate>
        <Head title="Help & Contact - BostonScope" />

        <div class="bg-gray-50 py-12 sm:py-16">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl mx-auto">
                    <h1 class="text-4xl font-bold tracking-tight text-center text-gray-900 mb-12">
                        Help & Contact
                    </h1>

                    <!-- FAQ Section -->
                    <section class="mb-16">
                        <h2 class="text-3xl font-semibold text-gray-800 mb-6 border-b pb-3">
                            Frequently Asked Questions (FAQ)
                        </h2>
                        <div class="space-y-8 prose prose-lg lg:prose-xl max-w-none">
                            <div>
                                <h3>How do I subscribe to a plan?</h3>
                                <p>
                                    You can subscribe to a plan by visiting our <Link :href="route('subscription.index')" class="text-blue-600 hover:text-blue-800 underline">Subscription Page</Link> and choosing the plan that best suits your needs. If you are not logged in, you will be prompted to log in or register.
                                </p>
                            </div>
                            <div>
                                <h3>How can I manage my subscription?</h3>
                                <p>
                                    If you have an active subscription, you can manage it, update payment details, or cancel by visiting the <Link :href="route('billing')" class="text-blue-600 hover:text-blue-800 underline">Billing Portal</Link>.
                                </p>
                            </div>
                            <div>
                                <h3>What data does BostonScope use?</h3>
                                <p>
                                    BostonScope utilizes publicly available data from sources like Boston's Analyze Boston open data portal, including crime incident reports, 311 service requests, building permits, and property violations. We process and present this data to offer valuable insights.
                                </p>
                            </div>
                            <div>
                                <h3>How do I reset my password?</h3>
                                <p>
                                    If you've forgotten your password, you can reset it by clicking the "Forgot your password?" link on the <Link :href="route('login')" class="text-blue-600 hover:text-blue-800 underline">login page</Link>. If you signed up using a social media account (e.g., Google), you typically manage your password through that provider.
                                </p>
                            </div>
                            <div>
                                <h3>How are the AI summaries generated?</h3>
                                <p>
                                    The AI-generated summaries and insights on BostonScope are powered by advanced language models (like Google's Gemini). They process the raw data points for a selected area and time frame to provide concise overviews. While helpful, always cross-reference important information.
                                </p>
                            </div>
                            <!-- Add more relevant FAQs here -->
                        </div>
                    </section>

                    <!-- Contact Support Section -->
                    <section class="mb-16">
                        <h2 class="text-3xl font-semibold text-gray-800 mb-6 border-b pb-3">
                            Contact Support
                        </h2>
                        <div class="prose prose-lg lg:prose-xl max-w-none">
                            <p>
                                If you cannot find an answer to your question in our FAQ, or if you need further assistance with your account, billing, or technical issues, please feel free to contact our support team.
                            </p>
                            <p>
                                <strong>Email:</strong> <a href="mailto:help@bostonscope.com" class="text-blue-600 hover:text-blue-800 underline">help@bostonscope.com</a>
                            </p>
                            <p>
                                We aim to respond to all inquiries within 24-48 business hours.
                            </p>
                        </div>
                    </section>

                    <!-- Feedback Form Section -->
                    <section>
                        <h2 class="text-3xl font-semibold text-gray-800 mb-6 border-b pb-3">
                            Help Us Improve BostonScope
                        </h2>
                        <div class="bg-white p-6 sm:p-8 rounded-lg shadow-lg">
                            <p class="text-gray-700 mb-6">
                                We value your feedback and suggestions! If you've encountered a bug, have an idea for a new feature, or want to share your experience, please let us know.
                            </p>
                            <form @submit.prevent="submitFeedback" class="space-y-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                                    <input type="text" id="name" v-model="feedbackForm.name" required
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input type="email" id="email" v-model="feedbackForm.email" required
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                                </div>

                                <div>
                                    <label for="feedback" class="block text-sm font-medium text-gray-700 mb-1">Feedback / Issue Description</label>
                                    <textarea id="feedback" v-model="feedbackForm.feedback" rows="5" required
                                              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                                </div>

                                <div>
                                    <label for="image" class="block text-sm font-medium text-gray-700 mb-1">
                                        Attach Screenshot (Optional)
                                        <span class="text-xs text-gray-500"> - helpful for bug reports!</span>
                                    </label>
                                    <input type="file" id="image" @change="handleFileUpload" accept="image/*"
                                           class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                </div>

                                <div class="pt-2">
                                    <button type="submit"
                                            :disabled="feedbackForm.processing"
                                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                                        {{ feedbackForm.processing ? 'Submitting...' : 'Submit Feedback' }}
                                    </button>
                                </div>
                            </form>

                            <div v-if="successMessage" class="mt-6 p-4 text-sm text-green-700 bg-green-100 rounded-md border border-green-300">
                                {{ successMessage }}
                            </div>
                            <div v-if="errorMessage" class="mt-6 p-4 text-sm text-red-700 bg-red-100 rounded-md border border-red-300">
                                {{ errorMessage }}
                            </div>
                        </div>
                    </section>

                </div>
            </div>
        </div>
    </PageTemplate>
</template>

<script setup>
import PageTemplate from '@/Components/PageTemplate.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const page = usePage();

// Initialize form data
const feedbackForm = useForm({
  name: '',
  email: '',
  feedback: '',
  image: null, // For the image file
});

const successMessage = ref('');
const errorMessage = ref('');

// Pre-fill name and email if user is logged in
onMounted(() => {
    if (page.props.auth.user) {
        feedbackForm.name = page.props.auth.user.name || '';
        feedbackForm.email = page.props.auth.user.email || '';
    }
});

// Handle file selection for image upload
const handleFileUpload = (event) => {
  const file = event.target.files[0];
  feedbackForm.image = file; // Attach the selected image to the form data
};

const submitFeedback = () => {
  successMessage.value = ''; // Clear previous messages
  errorMessage.value = '';

  // Use FormData to allow file uploads
  feedbackForm.post(route('feedback.store'), { // Assuming you have a named route 'feedback.store'
    forceFormData: true, // Necessary to handle file uploads in Inertia.js
    onSuccess: () => {
      successMessage.value = 'Thank you for your feedback! We appreciate you helping us improve BostonScope.';
      feedbackForm.reset('feedback', 'image'); // Reset only feedback and image, keep name/email if prefilled
      const imageInput = document.getElementById('image');
      if (imageInput) {
        imageInput.value = ''; // Clear the file input display
      }
    },
    onError: (errors) => {
      if (errors && Object.keys(errors).length > 0) {
        // If specific validation errors come back from Laravel
        let specificError = Object.values(errors)[0]; // Get the first error message
        errorMessage.value = `Please correct the following: ${specificError}`;
      } else {
        errorMessage.value = 'There was an issue submitting your feedback. Please check your connection and try again.';
      }
    },
    onFinish: () => {
        // feedbackForm.processing is automatically handled by useForm
    }
  });
};
</script>

<style scoped>
/* Tailwind's @tailwindcss/typography plugin will style most of the FAQ content.
   The form elements are styled directly with Tailwind classes.
   Specific prose overrides if necessary: */
.prose h3 {
    @apply text-xl font-semibold text-gray-700 mt-6 mb-2;
}
.prose p {
    @apply mb-4 leading-relaxed text-gray-600;
}
.prose ul {
    @apply list-disc pl-6 mb-4 space-y-1 text-gray-600;
}
</style>